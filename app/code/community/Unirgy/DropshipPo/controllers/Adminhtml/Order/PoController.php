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
 
class Unirgy_DropshipPo_Adminhtml_Order_PoController extends Mage_Adminhtml_Controller_Action
{
    protected function _getItemQtys()
    {
        $data = $this->getRequest()->getParam('udpo');
        if (isset($data['items'])) {
            $qtys = $data['items'];
        } else {
            $qtys = array();
        }
        return $qtys;
    }
	protected function _getShipmentItemQtys()
    {
        $data = $this->getRequest()->getParam('shipment');
        if (isset($data['items'])) {
            $qtys = $data['items'];
        } else {
            $qtys = array();
        }
        return $qtys;
    }
    protected function _getItemVendors()
    {
        $data = $this->getRequest()->getParam('udpo');
        if (isset($data['vendors'])) {
            $qtys = $data['vendors'];
        } else {
            $qtys = array();
        }
        return $qtys;
    }
    protected function _getItemCosts()
    {
        $data = $this->getRequest()->getParam('udpo');
        if (isset($data['costs'])) {
            $costs = $data['costs'];
        } else {
            $costs = array();
        }
        return $costs;
    }
    protected function _getItemDefaultVendorCosts()
    {
        $data = $this->getRequest()->getParam('udpo');
        if (isset($data['default_vendor_costs'])) {
            $costs = $data['default_vendor_costs'];
        } else {
            $costs = array();
        }
        return $costs;
    }
    protected function _initPo($forSave=true)
    {
        $udpoId = $this->getRequest()->getParam('udpo_id');
        $po = Mage::getModel('udpo/po')->load($udpoId);
        if (!$po->getId()) {
            $this->_getSession()->addError($this->__('This purchase order no longer exists.'));
            $this->_redirect('*/*/');
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            return false;
        }
        if (!$forSave) {
            $po->getOrder()->setShippingAmount($po->getShippingAmount());
            $po->getOrder()->setIsVirtual($po->getIsVirtual());
        }
        Mage::register('current_udpo', $po);
        Mage::register('current_order', $po->getOrder());

        return $po;
    }
    
	protected function _initShipment($udpo, $setQtyShippedFlag)
    {
        $this->_title($this->__('Sales'))->_title($this->__('Purchase Orders'))->_title($this->__('Shipments'));

        $shipment = false;

        if (!$udpo->getId()) {
            $this->_getSession()->addError($this->__('The po no longer exists.'));
            return false;
        }
        if (!$udpo->canCreateShipment()) {
            $this->_getSession()->addError($this->__('Cannot do shipment for the po.'));
            return false;
        }
        $_savedQtys = $this->_getShipmentItemQtys();
        $savedQtys = array();
        $poItems = $udpo->getItemsCollection();
        foreach ($_savedQtys as $_oid => $_sq) {
        	$savedQtys[$poItems->getItemByColumnValue('order_item_id', $_oid)->getId()] = $_sq;
        }
        $udpo->setUdpoNoSplitPoFlag(true);
        $shipment = Mage::helper('udpo')->createShipmentFromPo($udpo, $savedQtys, false, $setQtyShippedFlag);

        $tracks = $this->getRequest()->getPost('tracking');
        if ($tracks) {
            foreach ($tracks as $data) {
                $track = Mage::getModel('sales/order_shipment_track')
                    ->addData($data);
                $shipment->addTrack($track);
            }
        }

        Mage::register('current_shipment', $shipment);
        return $shipment;
    }
    
	public function newShipmentAction()
    {
    	$udpo = $this->_initPo(false);
        if ($shipment = $this->_initShipment($udpo, false)) {
            $this->_title($this->__('New Shipment'));

            if ($comment = Mage::getSingleton('adminhtml/session')->getShipmentCommentText(true)) {
                $shipment->setCommentText($comment);
            }

            $this->loadLayout()
                ->_setActiveMenu('sales/udropship/udpo')
                ->renderLayout();
                
        } else {
            $this->_redirect('*/*/view', array('udpo_id'=>$this->getRequest()->getParam('udpo_id')));
        }
    }
    
	public function saveShipmentAction()
    {
        $data = $this->getRequest()->getPost('shipment');
        if (!empty($data['comment_text'])) {
            Mage::getSingleton('adminhtml/session')->setCommentText($data['comment_text']);
        }

        $hlp = Mage::helper('udropship');
        $udpoHlp = Mage::helper('udpo');
        
        try {
        	$session = $this->_getSession();
        	$udpo = $this->_initPo();
        	$order = $udpo->getOrder();
        	if (isset($data['use_label_shipping_amount'])) {
                $udpo->setUseLabelShippingAmount(true);
            } elseif ($data['shipping_amount']) {
                $udpo->setShipmentShippingAmount($data['shipping_amount']);
            }
            if ($shipment = $this->_initShipment($udpo, true)) {

                $comment = '';
                if (!empty($data['comment_text'])) {
                    Mage::helper('udropship')->addShipmentComment(
                        $shipment,
                        $data['comment_text'], true, false, isset($data['comment_customer_notify'])
                    );
                    if (!empty($data['comment_customer_notify'])) {
                        $comment = $data['comment_text'];
                    }
                }

                if (!empty($data['send_email'])) {
                    $shipment->setEmailSent(true);
                }

                $shipment->getOrder()->setCustomerNoteNotify(!empty($data['send_email']));
                Mage::dispatchEvent('udpo_po_shipment_save_before', array('order'=>$order, 'udpo'=>$udpo, 'shipments'=>array($shipment)));

		        $transaction = Mage::getModel('core/resource_transaction');
		        $order->getShipmentsCollection()->addItem($shipment);
		        $udpo->getShipmentsCollection()->addItem($shipment);
		        $transaction->addObject($shipment);
		        $shipment->setNoInvoiceFlag(true);
		        $transaction->addObject($order->setIsInProcess(true))->addObject($udpo->setData('___dummy',1))->save();
		        
		        Mage::dispatchEvent('udpo_po_shipment_save_after', array('order'=>$order, 'udpo'=>$udpo, 'shipments'=>array($shipment)));
        
                $shipment->sendEmail(!empty($data['send_email']), $comment);
                $this->_getSession()->addSuccess($this->__('The shipment has been created.'));
                Mage::getSingleton('adminhtml/session')->getCommentText(true);
                
             	if (!empty($data['generate_label'])) {
                    $labelData = array();
                    foreach (array('weight','value','length','width','height','reference','package_count') as $_glKey) {
                        if (isset($data['label_info'][$_glKey])) {
                            $labelData[$_glKey] = $data['label_info'][$_glKey];
                        }
                    }
	
	                // generate label
	                $batch = Mage::getModel('udropship/label_batch')
	                    ->setVendor($udpo->getVendor())
	                    ->processShipments(array($shipment), $labelData, array('mark_shipped'=>!empty($data['mark_as_shipped'])));
	
	                if (empty($data['mark_as_shipped'])) {
	                	$udpoHlp->processPoStatusSave($udpo, Unirgy_DropshipPo_Model_Source::UDPO_STATUS_READY, true);
	                }
	                    
	                // if batch of 1 label is successfull
	                if ($batch->getShipmentCnt() && $batch->getLastTrack()) {
	                    $session->addSuccess('Label was succesfully created');
	                } else {
	                    if ($batch->getErrors()) {
	                        foreach ($batch->getErrors() as $error=>$cnt) {
	                            $session->addError($hlp->__($error, $cnt));
	                        }
	                    }
	                }
             	} elseif (!empty($data['mark_as_shipped'])) {
             		$hlp->completeShipment($shipment, true);
             		$hlp->completeUdpoIfShipped($shipment, true);
            		$hlp->completeOrderIfShipped($shipment, true);
             	} else {
             		$udpoHlp->processPoStatusSave($udpo, Unirgy_DropshipPo_Model_Source::UDPO_STATUS_READY, true);
             	}
             	
            	if (!empty($data['do_invoice'])) {
            		$shipment->setNoInvoiceFlag(false);
		        	$shipment->setDoInvoiceFlag(true);
		        	if (Mage::helper('udpo')->invoiceShipment($shipment)) {
		        		$session->addSuccess($udpoHlp->__('Shipment was succesfully invoiced'));
		        	} else {
		        		$session->addError($udpoHlp->__('Shipment was not invoiced'));
		        	}
		        }
                
                $this->_redirect('*/*/view', array('udpo_id' => $shipment->getUdpoId()));
                return;
            } else {
            	$this->_getSession()->addError($this->__('Cannot initialize shipment.'));
            }
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
        	Mage::logException($e);
            $this->_getSession()->addError($this->__('Cannot save shipment.'));
        }
        $this->_redirect('*/*/newShipment', array('udpo_id' => $this->getRequest()->getParam('udpo_id')));
    }
    
    protected function checkStockAvailability($order, $vendors, $noError=false)
    {
        if (Mage::getStoreConfigFlag('udropship/stock/reassign_skip_stockcheck', $order->getStore())) {
            return $this;
        }
        $items = Mage::getModel('sales/order')->load($order->getId())->getAllItems();
        foreach ($items as $_item) {
            $_item->setUdropshipExtraStockQty(array(
                $_item->getUdropshipVendor() => max(
                    $_item->getQtyOrdered()-$_item->getUdpoQtyReverted(), 0
            )));
        	$_item->setUdpoCreateQty(
                Mage::helper('udropship')->getOrderItemById($order, $_item->getId())->getUdpoCreateQty()
            );
        }
        
        Mage::helper('udropship/protected')->reassignApplyStockAvailability($items);

        foreach ($items as $item) {
            Mage::helper('udropship')->getOrderItemById($order, $item->getId())->setData(
                '_udropship_stock_levels', $item->getData('udropship_stock_levels')
            );
        }

        if (!$noError) {
            $hasOutOfStockError = '';
            foreach ($items as $item) {
                if (!isset($vendors[$item->getId()])) continue;
                if ($item->getProductType()=='configurable') {
                    $children = $item->getChildrenItems() ? $item->getChildrenItems() : $item->getChildren();
                    foreach ($children as $child) {
                        if (!$child->getData("udropship_stock_levels/{$vendors[$item->getId()]['id']}/status")
                            && $child->getUdropshipVendor()!=$vendors[$item->getId()]['id']
                        ) {
                            $hasOutOfStockError .= Mage::helper('udropship')->__(
                                "%s x %s is not available at vendor '%s'",
                                Mage::helper('udropship')->getItemStockCheckQty($child), $child->getSku(),
                                Mage::helper('udropship')->getVendorName($vendors[$item->getId()]['id'])
                            )."\n";
                        }
                        break;
                    }
                } else {
                    if (!$item->getData("udropship_stock_levels/{$vendors[$item->getId()]['id']}/status")
                        && $item->getUdropshipVendor()!=$vendors[$item->getId()]['id']
                    ) {
                        $hasOutOfStockError .= Mage::helper('udropship')->__(
                            "%s x %s is not available at vendor '%s'",
                            Mage::helper('udropship')->getItemStockCheckQty($item), $item->getSku(),
                            Mage::helper('udropship')->getVendorName($vendors[$item->getId()]['id'])
                        )."\n";
                    }
                }
            }
            if (!empty($hasOutOfStockError)) Mage::throwException(trim($hasOutOfStockError));
        }
        return $this;
    }

    protected function __initOrder()
    {
        $id = $this->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($id);

        if (!$order->getId()) {
            $this->_getSession()->addError($this->__('This order no longer exists.'));
            $this->_redirect('*/*/');
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            return false;
        }

        Mage::register('sales_order', $order);
        Mage::register('current_order', $order);

        return $order;
    }

    protected function _initOrder()
    {
        $order = $this->__initOrder();

        $hlp   = Mage::helper('udropship');
        $hlpd  = Mage::helper('udropship/protected');
        $poHlp = Mage::helper('udpo');
        $totalCost = 0;
        $totalCostByVendor = array();
        $qtys = $this->_getItemQtys();
        $vendors = $this->_getItemVendors();
        $costs = $this->_getItemCosts();
        $defVendorCosts = $this->_getItemDefaultVendorCosts();
        $vMethods = array();
        if (!$poHlp->checkCreatePoQtys($order, $qtys)) {
            Mage::throwException($poHlp->__('Cannot create PO with this qtys'));
        }
        $isVirtual = array();
        $udpoCreateQtys = array();
        foreach ($order->getAllItems() as $item) {
            if ($item->isDummy(true)) {
                $parentItem = $item->getParentItem();
                if ($parentItem && !empty($vendors[$parentItem->getId()])) {
                    $item->setUdpoUdropshipVendor($parentItem->getUdpoUdropshipVendor());
                    if ($parentItem->getProductType()=='configurable') {
                        $item->setUdpoBaseCost($parentItem->getUdpoBaseCost());
                    }
                }
                continue;
            }
            if (isset($qtys[$item->getId()]) && abs($qtys[$item->getId()]) < 0.001) continue; 
            if (isset($qtys[$item->getId()])) {
                $item->setUdpoCreateQty($qtys[$item->getId()]);
            } else {
                $item->setUdpoCreateQty($poHlp->getOrderItemQtyToUdpo($item));
            }
            if (!empty($vendors[$item->getId()])) {
                $item->setUdpoUdropshipVendor($vendors[$item->getId()]['id']);
            } else {
                $children = $item->getChildrenItems() ? $item->getChildrenItems() : $item->getChildren();
                $item->setUdpoBaseCost($hlp->getItemBaseCost($item));
                if (!empty($children)) {
                    foreach ($children as $child) { 
                        $item->setUdpoUdropshipVendor($child->getUdropshipVendor());
                        break;
                    }
                } else {
                    $item->setUdpoUdropshipVendor($item->getUdropshipVendor());
                }
            }
            $udpoVid = $item->getUdpoUdropshipVendor();
            if (!isset($totalCostByVendor[$udpoVid])) {
                $totalCostByVendor[$udpoVid] = 0;
            }
            if (!$item->getHasChildren() || $item->getProductType()=='configurable') {
                $item->setUdpoOrigBaseCost($item->getUdpoBaseCost());
                if (isset($costs[$item->getId()])) {
                    $item->setUdpoBaseCost($costs[$item->getId()]);
                    $item->setUdpoCustomCost(true);
                } elseif ($item->getProductType()=='configurable') {
                    foreach ($item->getChildrenItems() as $child) {
                        if (isset($defVendorCosts[$child->getId()][$udpoVid])) {
                            $child->setUdpoBaseCost($defVendorCosts[$child->getId()][$udpoVid]);
                            $item->setUdpoBaseCost($defVendorCosts[$child->getId()][$udpoVid]);
                        }
                    }
                } elseif (isset($defVendorCosts[$item->getId()][$udpoVid])) {
                    $item->setUdpoBaseCost($defVendorCosts[$item->getId()][$udpoVid]);
                }
                $totalCostByVendor[$udpoVid] += $item->getUdpoCreateQty()*$item->getUdpoBaseCost();
                $totalCost += $item->getUdpoCreateQty()*$item->getUdpoBaseCost();
            } else {
                foreach ($item->getChildrenItems() as $child) {
                    $child->setUdpoBaseCost($hlp->getItemBaseCost($child));
                    $child->setUdpoOrigBaseCost($child->getUdpoBaseCost());
                    if (isset($costs[$child->getId()])) {
                        $child->setUdpoBaseCost($costs[$child->getId()]);
                        $child->setUdpoCustomCost(true);
                    } elseif (isset($defVendorCosts[$child->getId()][$udpoVid])) {
                        $child->setUdpoBaseCost($defVendorCosts[$child->getId()][$udpoVid]);
                    }
                    $_costToAdd = $child->getUdpoBaseCost()*$child->getQtyOrdered()*$item->getUdpoCreateQty()/$item->getQtyOrdered();
                    $totalCostByVendor[$udpoVid] += $_costToAdd;
                    $totalCost += $_costToAdd;
                }
            }
            if (empty($vMethods[$udpoVid])) {
                $vMethods[$udpoVid] = array();
            }
            if (empty($udpoCreateQtys[$udpoVid])) {
                $udpoCreateQtys[$udpoVid] = 0;
            }
            $udpoCreateQtys[$udpoVid] += $item->getUdpoCreateQty();
            if (empty($isVirtual[$udpoVid])) {
                $isVirtual[$udpoVid] = true;
            }
            if (!$item->getIsVirtual()) {
                $isVirtual[$udpoVid] = false;
            }
        }
        //if (!empty($vendors)) $this->checkStockAvailability($order, $vendors);
        $this->checkStockAvailability($order, $vendors, empty($vendors));
        $__vMethods = $vMethods;
        $vMethods = array();
        foreach ($__vMethods as $vId => $vMethod) {
            if ($udpoCreateQtys[$vId]>0) {
                $vMethods[$vId] = !$isVirtual[$vId] ? $vMethod : false;
            }
        }
        $hlp->initVendorShippingMethodsForHtmlSelect($order, $vMethods);
        $order->setTotalCostByVendor($totalCostByVendor);
        $order->setTotalCost($totalCost);
        $orderVendorRates = $hlpd->getOrderVendorRates($order);
        
        $totalShipping = 0;
        $udpoVendorRates = $this->getRequest()->getParam('vendor_rates', array());
        $_udpoVendorRates = array();
        foreach ($vMethods as $vId => $vMethod) {
            if (!isset($udpoVendorRates[$vId]) && isset($orderVendorRates[$vId])) {
                $udpoVendorRates[$vId] = $orderVendorRates[$vId];
            }
            $udpoVendorRates[$vId]['price'] = !empty($udpoVendorRates[$vId]['price']) && $vMethod ? $udpoVendorRates[$vId]['price'] : 0;
            $udpoVendorRates[$vId]['udpo_methods'] = $vMethod;
            $_udpoVendorRates[$vId] = $udpoVendorRates[$vId];
            
            $totalShipping += $udpoVendorRates[$vId]['price'];
        }
        unset($vRate);
        $order->setTotalShippingAmount($totalShipping);
        $order->setUdpoVendorRates($_udpoVendorRates);

        Mage::register('is_udpo_page', true);

        Mage::dispatchEvent('udpo_adminhtml_order_init_after', array('order'=>$order));

        return $order;
    }
    
    public function updateQtyAction()
    {
        try {
            $this->_initOrder();
            $this->loadLayout();
            $response = $this->getLayout()->getBlock('order_items')->toHtml();
            if (($multi = Mage::getConfig()->getNode('modules/Unirgy_DropshipMulti')) && $multi->is('active')) {
                $response .= $this->getLayout()->getBlock('udmulti_po_js')->toHtml();
            }
        } catch (Mage_Core_Exception $e) {
        	Mage::logException($e);
            $response = array(
                'error'     => true,
                'message'   => $e->getMessage()
            );
            $response = Zend_Json::encode($response);
        } catch (Exception $e) {
        	Mage::logException($e);
            $response = array(
                'error'     => true,
                'message'   => $this->__('Cannot update item quantity.')
            );
            $response = Zend_Json::encode($response);
        }
        $this->getResponse()->setBody($response);
    }
    
    public function startAction()
    {
        $this->_redirect('*/*/new', array('order_id'=>$this->getRequest()->getParam('order_id')));
    }

    public function newAction()
    {
        if ($order = $this->_initOrder()) {
            $this->_title($this->__('New Purchase Order'));

            $this->loadLayout()
                ->_setActiveMenu('sales/order')
                ->renderLayout();
        } else {
            $this->_redirect('*/sales_order/view', array('order_id'=>$this->getRequest()->getParam('order_id')));
        }
    }
    
    public function viewAction()
    {
        if ($po = $this->_initPo(false)) {
            $this->_title($this->__('View Purchase Order'));

            $this->loadLayout();
            /*
            $this->getLayout()->getBlock('udpo_po_view')
                ->updateBackButtonUrl($this->getRequest()->getParam('come_from'));
                */
            $this->_setActiveMenu('sales/order')
                ->renderLayout();
        }
        else {
            $this->_forward('noRoute');
        }
    }
    
    public function saveAction()
    {
        $data = $this->getRequest()->getPost('udpo');
        if (!empty($data['comment_text'])) {
            Mage::getSingleton('adminhtml/session')->setCommentText($data['comment_text']);
        }

        try {
            if ($order = $this->_initOrder()) {
                $order->setUdpoNoSplitPoFlag(true);
                $order->setSkipLockedCheckFlag(true);
                $order->setIsManualPoFlag(true);
                $posCreated = Mage::helper('udpo')->splitOrderToPos($order, $this->_getItemQtys(), isset($data['comment_vendor_notify']) ? $data['comment_text'] : '');
                $this->_getSession()->addSuccess($this->__('Created %s Purchase Orders', $posCreated));
                if (!empty($data['comment_text'])) {
                    Mage::helper('udpo')->initOrderUdposCollection($order, true);
                    $commentVisibleToVendor = isset($data['comment_vendor_notify']) || isset($data['comment_visible_to_vendor']);
                    foreach ($order->getLastCreatedUdpos() as $_po) {
                        $_po->setUseCommentUsername(Mage::getSingleton('admin/session')->getUser()->getUsername());
                        $_po->addComment(
                            $data['comment_text'],
                            isset($data['comment_vendor_notify']),
                            $commentVisibleToVendor
                        );
                        $_po->saveComments();
                    }
                }
                Mage::getSingleton('adminhtml/session')->getCommentText(true);
                $this->_redirect('adminhtml/sales_order/view', array('order_id' => $order->getId()));
                return;
            } else {
                $this->_forward('noRoute');
                return;
            }
        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($this->__('Cannot save po.'));
        }
        $this->_redirect('*/*/new', array('order_id' => $this->getRequest()->getParam('order_id')));
    }
    
    public function addCommentAction()
    {
        try {
            $this->getRequest()->setParam(
                'udpo_id',
                $this->getRequest()->getParam('id')
            );
            $data = $this->getRequest()->getPost('comment');
            $udpo = $this->_initPo();
            if (empty($data['comment']) && $data['status']==$udpo->getUdropshipStatus()) {
                Mage::throwException($this->__('Comment text field cannot be empty.'));
            }
            $isVendorNotified  = isset($data['is_vendor_notified']);
            $isVisibleToVendor = isset($data['is_vendor_notified']) || isset($data['is_visible_to_vendor']);
            
            $udpo->setUseCommentUsername(Mage::getSingleton('admin/session')->getUser()->getUsername());
            
            $hlp = Mage::helper('udropship');
            $udpoHlp = Mage::helper('udpo');
            $poStatus = $data['status'];
            
            $poStatusShipped = Unirgy_DropshipPo_Model_Source::UDPO_STATUS_SHIPPED;
            $poStatusDelivered = Unirgy_DropshipPo_Model_Source::UDPO_STATUS_DELIVERED;
            $poStatusCanceled = Unirgy_DropshipPo_Model_Source::UDPO_STATUS_CANCELED;
            
            $poStatusSaveRes = true;
            if ($this->getRequest()->getParam('force_status_change_flag')) {
                $udpo->setForceStatusChangeFlag(true);
            }
            if ($poStatus!=$udpo->getUdropshipStatus()) {
                $oldStatus = $udpo->getUdropshipStatus();
                if ($oldStatus==$poStatusCanceled && !$udpo->getForceStatusChangeFlag()) {
                    Mage::throwException(Mage::helper('udpo')->__('Canceled purchase order cannot be reverted'));
                }
                if ($poStatus==$poStatusShipped || $poStatus==$poStatusDelivered) {
                    foreach ($udpo->getShipmentsCollection() as $_s) {
                        $hlp->completeShipment($_s, true, $poStatus==$poStatusDelivered);
                    }
                    if (isset($_s)) {
                        $hlp->completeOrderIfShipped($_s, true);
                    }
                    $poStatusSaveRes = $udpoHlp->processPoStatusSave($udpo, $poStatus, true, null, $data['comment'], $isVendorNotified, $isVisibleToVendor);
                } elseif ($poStatus == $poStatusCanceled) {
                    $udpo->setFullCancelFlag(isset($data['full_cancel']));
                    $udpo->setNonshippedCancelFlag(isset($data['nonshipped_cancel']));
                    Mage::helper('udpo')->cancelPo($udpo, true);
                    $poStatusSaveRes = $udpoHlp->processPoStatusSave($udpo, $poStatus, true, null, $data['comment'], $isVendorNotified, $isVisibleToVendor);
                } else {
                    $poStatusSaveRes = $udpoHlp->processPoStatusSave($udpo, $poStatus, true, null, $data['comment'], $isVendorNotified, $isVisibleToVendor);
                }
            } else {
                $udpo->addComment($data['comment'], $isVendorNotified, $isVisibleToVendor);
                if (isset($data['is_vendor_notified'])) {
                    Mage::helper('udpo')->sendPoCommentNotificationEmail($udpo, $data['comment']);
                    Mage::helper('udropship')->processQueue();
                }
                $udpo->saveComments();
            }

            if ($poStatus == $poStatusCanceled) {
            	if ($udpo->getCurrentlyCanceledQty()>0) {
            		$this->_getSession()->addSuccess(Mage::helper('udpo')->__('%s items were canceled', $udpo->getCurrentlyCanceledQty()));
            	} else {
            		$this->_getSession()->addNotice(Mage::helper('udpo')->__('There were no items to cancel that match requested condition'));
            	}
            	if (!$poStatusSaveRes) {
            		$this->_getSession()->addNotice(Mage::helper('udpo')->__('Status cannot be changed to canceled because po still have processing items'));
            	} else {
            		$this->_getSession()->addNotice(Mage::helper('udpo')->__('Po Status changed to canceled'));
            	}
                $response = array(
                    'ajaxExpired'  => true,
                    'ajaxRedirect' => $this->getUrl('*/*/view', array('udpo_id' => $this->getRequest()->getParam('udpo_id')))
                );
                $response = Zend_Json::encode($response);
            } elseif ($poStatusSaveRes) {
                $this->loadLayout();
                $response = $this->getLayout()->getBlock('udpo_comments')->toHtml();
            } else {
                $response = array(
                    'error'     => true,
                    'message'   => Mage::helper('udpo')->__('Cannot change status from %s to %s', Mage::helper('udpo')->getPoStatusName($udpo->getUdropshipStatus()), Mage::helper('udpo')->getPoStatusName($poStatus))
                );
                $response = Zend_Json::encode($response);
            }
        } catch (Mage_Core_Exception $e) {
            $response = array(
                'error'     => true,
                'message'   => $e->getMessage()
            );
            $response = Zend_Json::encode($response);
        } catch (Exception $e) {
            Mage::logException($e);
            $response = array(
                'error'     => true,
                'message'   => $this->__('Cannot add new comment.')
            );
            $response = Zend_Json::encode($response);
        }
        $this->getResponse()->setBody($response);
    }
    
    public function udposTabAction()
    {
        $this->__initOrder();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('udpo/adminhtml_salesOrderViewTab_udpos')->toHtml()
        );
    }
    
    public function invoicesAction()
    {
        $this->_initPo(false);
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('udpo/adminhtml_po_view_tab_invoices')->toHtml()
        );
    }

    /**
     * Generate shipments grid for ajax request
     */
    public function shipmentsAction()
    {
        $this->_initPo(false);
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('udpo/adminhtml_po_view_tab_shipments')->toHtml()
        );
    }

    public function editCostsAction()
    {
        if ($po = $this->_initPo(false)) {
            $this->_title($this->__('New Purchase Order'));

            $this->loadLayout()
                ->_setActiveMenu('sales/order')
                ->renderLayout();
        } else {
            $this->_forward('noRoute');
        }
    }

    public function saveCostsAction()
    {
        $data = $this->getRequest()->getPost('udpo');

        if ($po = $this->_initPo(false)) {
            try {
                $order = $po->getOrder();
                $_orderRate = $order->getBaseToOrderRate() > 0 ? $order->getBaseToOrderRate() : 1;

                if (isset($data['shipping_amount'])) {
                    $po->setData('base_shipping_amount', $data['shipping_amount']);
                    $po->setData('shipping_amount', $data['shipping_amount']*$_orderRate);
                }

                if (is_array($data['costs'])) {
                    $costsDiff = 0;
                    foreach ($data['costs'] as $itemId => $itemCost) {
                        if (($item = $po->getItemById($itemId))) {
                            $costsDiff += ($itemCost-$item->getBaseCost())*$item->getQty();
                            $item->setBaseCost($itemCost);
                        }
                    }
                    $po->setTotalCost($po->getTotalCost()+$costsDiff);
                }

                $po->save();

                $commentVisibleToVendor = isset($data['comment_vendor_notify']) || isset($data['comment_visible_to_vendor']);
                $po->setUseCommentUsername(Mage::getSingleton('admin/session')->getUser()->getUsername());
                $po->addComment(
                    $data['comment_text'],
                    isset($data['comment_vendor_notify']),
                    $commentVisibleToVendor
                );
                $po->saveComments();
                if (isset($data['comment_vendor_notify'])) {
                    Mage::helper('udpo')->sendPoCommentNotificationEmail($po, $data['comment_text']);
                    Mage::helper('udropship')->processQueue();
                }

                $this->_getSession()->addSuccess(Mage::helper('udpo')->__('Costs were successfully updated'));

            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_getSession()->addError($this->__('Cannot save shipment.'));
            }
            $this->_redirect('*/*/view', array('udpo_id' => $po->getId()));
        } else {
            $this->_forward('noRoute');
        }
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/udropship/udpo')
            && (
                !in_array($this->getRequest()->getActionName(), array('editCosts', 'saveCosts'))
                || Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/udpo_edit_cost')
            );
    }
}
