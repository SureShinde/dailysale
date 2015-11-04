<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    Unirgy_DropshipPo
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */
 
class Unirgy_DropshipPo_Model_Observer
{
    public function adminhtml_version($observer)
    {
        Mage::helper('udropship')->addAdminhtmlVersion('Unirgy_DropshipPo');
    }
    public function adminhtml_order_add_create_po_button()
    {
        $layout = Mage::app()->getLayout();
        if (($soeBlock = $layout->getBlock('sales_order_edit'))
        	//&& Mage::helper('udropship')->isUdropshipOrder(Mage::registry('sales_order'))
            && Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/udpo')
            && Mage::registry('sales_order')
            && Mage::helper('udpo')->canCreatePo(Mage::registry('sales_order')->setSkipLockedCheckFlag(true))
        ) {
            $soeBlock->addButton('create_udpo', array(
                'label'     => Mage::helper('udpo')->__('Create PO'),
                'onclick'   => 'setLocation(\'' . $soeBlock->getUrl('udpoadmin/order_po/start') . '\')',
            ));
        }
    }
    public function udpoadmin_order_po_view($observer)
    {
        if (($soi = Mage::app()->getLayout()->getBlock('order_info'))
            && ($po = Mage::registry('current_udpo'))
            && ($vName = Mage::helper('udropship')->getVendorName($po->getUdropshipVendor()))
        ) {
            if (Mage::helper('udropship')->isModuleActive('ustockpo')
                && (($svName = Mage::helper('udropship')->getVendorName($po->getUstockVendor())))
            ) {
                $soi->setStockVendorName($svName);
            }
            $soi->setVendorName($vName);
            if (($stId = $po->getStatementId())) {
                $soi->setStatementId($stId);
                if (($st = Mage::getModel('udropship/vendor_statement')->load($stId, 'statement_id')) && $st->getId()) {
                    $soi->setStatementUrl(Mage::getModel('adminhtml/url')->getUrl('adminhtml/vendor_statement/edit', array('id'=>$st->getId())));
                }
            }
            if (Mage::helper('udropship')->isUdpayoutActive() && ($ptId = $po->getPayoutId())) {
                $soi->setPayoutId($ptId);
                if (($pt = Mage::getModel('udpayout/payout')->load($ptId)) && $pt->getId()) {
                    $soi->setPayoutUrl(Mage::getModel('adminhtml/url')->getUrl('udpayoutadmin/payout/edit', array('id'=>$pt->getId())));
                }
            }
        }
    }
    
    public function sales_order_shipment_save_commit_after($observer)
    {
        Mage::helper('udpo')->invoiceShipment($observer->getEvent()->getShipment());
    }
    public function udropship_shipment_status_save_after($observer)
    {
        Mage::helper('udpo')->invoiceShipment($observer->getEvent()->getShipment());
    }
    
    public function udropship_shipment_label_request_failed($observer)
    {
        Mage::helper('udpo')->processLabelRequestError(
        	$observer->getEvent()->getShipment(), 
        	$observer->getEvent()->getError()
        );
    }

    public function udpo_po_status_save_after($observer)
    {
        $this->_udpo_po_save_before($observer, true);
        $this->_notifyByStatus($observer->getPo());
    }
    public function udpo_po_save_before($observer)
    {
        $this->_udpo_po_save_before($observer, false);
    }
    protected function _udpo_po_save_before($observer, $isStatusEvent)
    {
        $po = $observer->getEvent()->getPo();
        if ($po->getUdropshipVendor()
            && ($vendor = Mage::helper('udropship')->getVendor($po->getUdropshipVendor()))
            && $vendor->getId()
            && (!$po->getStatementDate() || $po->getStatementDate() == '0000-00-00 00:00:00')
            && $vendor->getStatementPoType() == 'po'
        ) {
            $stPoStatuses = $vendor->getStatementPoStatus();
            if (!is_array($stPoStatuses)) {
                $stPoStatuses = explode(',', $stPoStatuses);
            }
            if (in_array($po->getUdropshipStatus(), $stPoStatuses)) {
                $po->setStatementDate(now());
                $po->setUpdatedAt(now());
                if ($isStatusEvent) {
                    $po->getResource()->saveAttribute($po, 'statement_date');
                    $po->getResource()->saveAttribute($po, 'updated_at');
                }
            }
        }
    }

    public function sales_model_service_quote_submit_before($observer)
    {
        $order = $observer->getEvent()->getOrder();
        foreach ($order->getAllItems() as $item) {
            if ($item->getParentItem()) {
                $item->setUdpoSeqNumber($item->getParentItem()->getUdpoSeqNumber());
            }
        }
    }

    public function core_block_abstract_to_html_after($observer)
    {
        $block = $observer->getBlock();
        $transport = $observer->getTransport();
        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_Create_Totals
            && $block->getNameInLayout() == 'totals'
        ) {
            $transport->setHtml(
                $transport->getHtml()
                .$block->getLayout()
                    ->createBlock(
                        'core/template', 'noautopo_flag',
                        array('template'=>'udpo/sales/createorder/noautopo_flag.phtml')
                    )->toHtml()
            );
        }
    }

    public function sales_order_save_before($observer)
    {
        $order = $observer->getOrder();
        if (($postOrderData =Mage::app()->getRequest()->getPost('order'))
            && !empty($postOrderData['noautopo_flag'])
        ) {
            $order->setData('noautopo_flag', $postOrderData['noautopo_flag']);
        }
    }

    public function udpo_po_save_after($observer)
    {
        $this->_notifyByStatus($observer->getPo());
    }

    protected function _notifyByStatus($po)
    {
        try {
            $v = Mage::helper('udropship')->getVendor($po->getUdropshipVendor());
            $notifyOnPoStatus = $v->getData('notify_by_udpo_status');
            if (!is_array($notifyOnPoStatus)) {
                $notifyOnPoStatus = explode(',', $notifyOnPoStatus);
            }
            if ($v->getId() && $v->getData("new_order_notifications") == '-1'
                && in_array($po->getUdropshipStatus(), $notifyOnPoStatus)
                && !$po->getData('is_vendor_notified')
            ) {
                $po->setData('is_vendor_notified', 1);
                Mage::helper('udpo')->sendNewPoNotificationEmail($po);
                Mage::helper('udropship')->processQueue();
                $po->getResource()->saveAttribute($po, 'is_vendor_notified');
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }
    
    public function udpo_order_save_after($observer)
    {
        $order = $observer->getOrder();
        $udpos = $observer->getUdpos();
        $sId = $order->getStoreId();
        $isMulti = Mage::helper('udropship')->isUdmultiActive();
        $localVid = Mage::helper('udropship')->getLocalVendorId($sId);
        $isLocalIfInStock = 'local_if_in_stock'==Mage::getStoreConfig('udropship/stock/availability', $sId);
        $this->_attachVendorProducts($udpos, $order);
        $result = new Varien_Object(array(
            'oiQtyUsed' => array(),
            'oiQtyReverts' => array(),
            'siQtyCors' => array(),
            'vpQtyCors' => array(),
        ));
        foreach ($udpos as $udpo) {
            $parentItems = array();
            foreach ($udpo->getAllItems() as $item) {
                $oItem = $item->getOrderItem();
                $product = $item->getProduct();
                $children = $oItem->getChildrenItems() ? $oItem->getChildrenItems() : $oItem->getChildren();
                if ($children) {
                    $parentItems[$oItem->getId()] = $item;
                } else {
                    $qty = $this->_correctItemQty($item, $parentItems);
                    if ($oItem->getUdropshipVendor()!=$item->getUdropshipVendor()
                        && ($isMulti || !$isLocalIfInStock || $localVid==$oItem->getUdropshipVendor())
                    ) {
                        $this->_applyQtyCorrection($oItem, $item, $qty, $result, true);
                    }
                    if ($isMulti || !$isLocalIfInStock || $localVid==$item->getUdropshipVendor()) {
                        $this->_applyQtyCorrection($oItem, $item, -$qty, $result, false);
                    }
                }
            }
        }
        $this->_saveQtyCorrections($result);
    }

    protected function _correctItemQty($item, $parentItems, $getter='getQty')
    {
        $oItem = $item->getOrderItem();
        $qty = max(1, $oItem->isDummy(true) ? 1 : $item->$getter());
        $oParent = $oItem->getParentItem();
        if ($oParent && $parentItems[$oParent->getId()]) {
            $parent = $parentItems[$oParent->getId()];
            $qty *= max(1, $oParent->isDummy(true) ? 1 : $parent->$getter());
        }
        return $qty;
    }

    protected function _saveQtyCorrections($result)
    {
        $oiQtyReverts = $result->getData('oiQtyReverts');
        $oiQtyUsed    = $result->getData('oiQtyUsed');
        $siQtyCors    = $result->getData('siQtyCors');
        $vpQtyCors    = $result->getData('vpQtyCors');
        foreach ($oiQtyUsed as $_oiQtyUsed) {
            $oItem = $_oiQtyUsed['order_item'];
            $oItem->getResource()->saveAttribute($oItem, 'udpo_qty_used');
        }
        foreach ($oiQtyReverts as $oiQtyRevert) {
            $oItem = $oiQtyRevert['order_item'];
            $oItem->getResource()->saveAttribute($oItem, 'udpo_qty_reverted');
        }
        foreach ($siQtyCors as $siQtyCor) {
            $stockItem = $siQtyCor['stock_item'];
            if ($siQtyCor['qty']!=0) {
                $stockItem->setQty($stockItem->getQty()+$siQtyCor['qty']);
                $stockItem->setIsInStock($stockItem->getQty()>0)->save();
            }
        }
        foreach ($vpQtyCors as $vpQtyCor) {
            $vp = $vpQtyCor['vendor_product'];
            if ($vp->getStockQty() !== '' && null !== $vp->getStockQty() && $vpQtyCor['qty']!=0) {
                $vp->setStockQty($vp->getStockQty()+$vpQtyCor['qty']);
                $vp->save();
            }
        }
        return $this;
    }

    protected function _attachVendorProducts($udpos, $order)
    {
        $sId = $order->getStoreId();
        $isMulti = Mage::helper('udropship')->isUdmultiActive();
        $localVid = Mage::helper('udropship')->getLocalVendorId($sId);
        $isLocalIfInStock = 'local_if_in_stock'==Mage::getStoreConfig('udropship/stock/availability', $sId);
        $vIds = $pIds = array();
        foreach ($udpos as $udpo) {
            foreach ($udpo->getAllItems() as $item) {
                $pIds[] = $item->getProductId();
                $vIds[] = $item->getUdropshipVendor();
                $vIds[] = $item->getOrderItem()->getUdropshipVendor();
            }
        }
        $vpCol = Mage::getModel('udropship/vendor_product')->getCollection()
            ->addVendorFilter($vIds)
            ->addProductFilter($pIds);
        $prods = Mage::getModel('catalog/product')->getCollection()
            ->setStoreId($sId)
            ->addIdFilter($pIds)
            ->addAttributeToSelect(Mage::getSingleton('sales/quote_config')->getProductAttributes())
            ->addStoreFilter();
        Mage::getModel('cataloginventory/stock')->addItemsToProducts($prods);
        foreach ($udpos as $udpo) {
            foreach ($udpo->getAllItems() as $item) {
                $oItem = $item->getOrderItem();
                if ($prod = $prods->getItemById($item->getProductId())) {
                    $item->setProduct($prod);
                    $oItem->setProduct($prod);
                }
            }
        }
        foreach ($udpos as $udpo) {
            foreach ($udpo->getAllItems() as $item) {
                $oItem = $item->getOrderItem();
                foreach ($vpCol as $vp) {
                    if ($vp->getVendorId()==$item->getUdropshipVendor()
                        && $item->getProductId()==$vp->getProductId()
                    ) {
                        $item->setVendorProduct($vp);
                    }
                    if ($vp->getVendorId()==$oItem->getUdropshipVendor()
                        && $oItem->getProductId()==$vp->getProductId()
                    ) {
                        $oItem->setVendorProduct($vp);
                    }
                }
            }
        }
        return $this;
    }

    protected $_isPoCancel=false;
    public function udpo_po_cancel($observer)
    {
        $this->_isPoCancel=true;
        $order = $observer->getOrder();
        $udpo = $observer->getUdpo();
        $sId = $order->getStoreId();
        $isMulti = Mage::helper('udropship')->isUdmultiActive();
        $localVid = Mage::helper('udropship')->getLocalVendorId($sId);
        $isLocalIfInStock = 'local_if_in_stock'==Mage::getStoreConfig('udropship/stock/availability', $sId);
        $this->_attachVendorProducts(array($udpo), $order);
        $result = new Varien_Object(array(
            'oiQtyReverts' => array(),
            'oiQtyUsed' => array(),
            'siQtyCors' => array(),
            'vpQtyCors' => array(),
        ));
        $itemsToSave = array();
        $parentItems = array();
        foreach ($udpo->getAllItems() as $item) {
            $oItem = $item->getOrderItem();
            $product = $item->getProduct();
            $children = $oItem->getChildrenItems() ? $oItem->getChildrenItems() : $oItem->getChildren();
            if ($children) {
                $parentItems[$oItem->getId()] = $item;
            } else {
                $qty = $this->_correctItemQty($item, $parentItems, 'getCurrentlyCanceledQty');
                if ($isMulti || !$isLocalIfInStock || $localVid==$item->getUdropshipVendor()) {
                    $this->_applyQtyCorrection($oItem, $item, $qty, $result, false);
                }
            }
        }
        $this->_saveQtyCorrections($result);
        $this->_isPoCancel=false;
    }

    protected function _applyQtyCorrection($oItem, $item, $qty, $result, $revOIQty=false)
    {
        $oiQtyReverts = $result->getData('oiQtyReverts');
        $oiQtyUsed    = $result->getData('oiQtyUsed');
        $siQtyCors    = $result->getData('siQtyCors');
        $vpQtyCors    = $result->getData('vpQtyCors');
        $sId = $oItem->getOrder()->getStoreId();
        $product = $item->getProduct();
        $stockItem = $product && $product->getStockItem() ? $product->getStockItem() : false;
        $isMulti = Mage::helper('udropship')->isUdmultiActive();
        $localVid = Mage::helper('udropship')->getLocalVendorId($sId);
        $isLocalIfInStock = 'local_if_in_stock'==Mage::getStoreConfig('udropship/stock/availability', $sId);
        $vId = $revOIQty ? $oItem->getUdropshipVendor() : $item->getUdropshipVendor();
        $vp = $revOIQty ? $oItem->getVendorProduct() : $item->getVendorProduct();
        $_oiQtyRevert = $_oiQtyUsed = $_siQtyCor = $_vpQtyCor = null;
        $_qtyUsed = $oItem->getUdpoQtyReverted()+$oItem->getUdpoQtyUsed();
        $_qtyLeft = max(0, $oItem->getQtyOrdered()-$_qtyUsed);
        if ($revOIQty) {
            $qty = min($qty, $_qtyLeft);
            $_oiQtyRevert = $qty;
        } elseif ($oItem->getVendorProduct()==$item->getVendorProduct()) {
            $_oiQtyUsed = -$qty;
            if ($this->_isPoCancel) {
                $_qtyUsed = max(0, min(
                    $oItem->getUdpoQtyUsed()+$_oiQtyUsed,
                    $oItem->getQtyOrdered()-$oItem->getUdpoQtyReverted()
                ));
                $_qtyUsed += $oItem->getUdpoQtyReverted();
                $_qtyLeft = max(0, $oItem->getQtyOrdered()-$_qtyUsed);
            }
            $qty = ($qty<0 ? -1 : 1)*max(0, abs($qty)-$_qtyLeft);
        }
        if ($isMulti) {
            if ($vp && '' !== $vp->getStockQty() && null !== $vp->getStockQty()) {
                $_siQtyCor = $_vpQtyCor = $qty;
            }
        } elseif (!$isLocalIfInStock || $localVid==$vId) {
            $_siQtyCor = $qty;
        }
        if ($_vpQtyCor !== null && $vp) {
            if (!isset($vpQtyCors[$vp->getId()])) {
                $vpQtyCors[$vp->getId()] = array(
                    'vendor_product'=>$vp,
                    'qty'=>0
                );
            }
            $vpQtyCors[$vp->getId()]['qty'] += $_vpQtyCor;
        }
        if ($_siQtyCor !== null && $stockItem) {
            if (!isset($siQtyCors[$stockItem->getId()])) {
                $siQtyCors[$stockItem->getId()] = array(
                    'stock_item'=>$stockItem,
                    'qty'=>0
                );
            }
            $siQtyCors[$stockItem->getId()]['qty'] += $_siQtyCor;
        }
        if ($revOIQty && $_oiQtyRevert !== null) {
            if (!isset($oiQtyReverts[$oItem->getId()])) {
                $oiQtyReverts[$oItem->getId()] = array(
                    'order_item'=>$oItem,
                    'qty'=>0
                );
            }
            $oiQtyReverts[$oItem->getId()]['qty'] += $_oiQtyRevert;
            $oItem->setUdpoQtyReverted(max(0, min(
                $oItem->getUdpoQtyReverted()+$_oiQtyRevert,
                $oItem->getQtyOrdered()-$oItem->getUdpoQtyUsed()
            )));
        }
        if ($_oiQtyUsed !== null) {
            if (!isset($oiQtyUsed[$oItem->getId()])) {
                $oiQtyUsed[$oItem->getId()] = array(
                    'order_item'=>$oItem,
                    'qty'=>0
                );
            }
            $oiQtyUsed[$oItem->getId()]['qty'] += $_oiQtyUsed;
            $oItem->setUdpoQtyUsed(max(0, min(
                $oItem->getUdpoQtyUsed()+$_oiQtyUsed,
                $oItem->getQtyOrdered()-$oItem->getUdpoQtyReverted()
            )));
        }
        $result->setData('oiQtyReverts', $oiQtyReverts);
        $result->setData('oiQtyUsed', $oiQtyUsed);
        $result->setData('siQtyCors', $siQtyCors);
        $result->setData('vpQtyCors', $vpQtyCors);
        return $this;
    }

}