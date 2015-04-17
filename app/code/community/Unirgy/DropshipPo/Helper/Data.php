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

class Unirgy_DropshipPo_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function isActive()
    {
        return true;
    }

    protected function _processObjectSave($save, $object)
    {
        if ($save===true) {
            $object->save();
        } elseif ($save instanceof Mage_Core_Model_Resource_Transaction) {
            $save->addObject($object);
        }
    }

    public function registerShipmentItem($item, $save)
    {
        $item->register();
        $this->_processObjectSave($save, $item);
        $poItem = $this->getShipmentPoItem($item);
        if ($poItem->getId()) {
            $poItem->setQtyShipped(
                $poItem->getQtyShipped()+$item->getQty()
            );
            $this->_processObjectSave($save, $poItem);
        }
    }

    public function revertCompleteShipment($shipment, $save)
    {
        foreach ($shipment->getAllItems() as $sItem) {
            $sItem->setQtyShipped(0);
            $this->_processObjectSave($save, $sItem);
        }
    }

    public function completeShipmentItem($item, $save)
    {
        $item->setQtyShipped(
            $item->getQtyShipped()+$this->getShipmentItemQtyToShip($item)
        );
        $this->_processObjectSave($save, $item);
    }

    public function completeUdpoIfShipped($shipment, $save=false, $force=true)
    {
        if (($po = $this->getShipmentPo($shipment))) {
            return $this->processPoStatusSave($po, Unirgy_DropshipPo_Model_Source::UDPO_STATUS_DELIVERED, $save)
                || $this->processPoStatusSave($po, Unirgy_DropshipPo_Model_Source::UDPO_STATUS_SHIPPED, $save)
                || $this->processPoStatusSave($po, Unirgy_DropshipPo_Model_Source::UDPO_STATUS_PARTIAL, $save);
        }
        return false;
    }

    public function splitOrderToPos($order, $qtys=array(), $comment='')
    {
        return Mage::helper('udpo/protected')->splitOrderToPos($order, $qtys, $comment);
    }

    public function sendVendorNotification($po, $comment='')
    {
        $vendor = $po->getVendor();
        $method = $vendor->getNewOrderNotifications();

        if (!$method || $method=='0') {
            return $this;
        }

        $data = compact('vendor', 'po', 'method');
        if ($method=='1') {
            $this->sendNewPoNotificationEmail($po, $comment);
        } else {
            $config = Mage::getConfig()->getNode('global/udropship/notification_methods/'.$method);
            if ($config) {
                $cb = explode('::', (string)$config->callback);
                $obj = Mage::getSingleton($cb[0]);
                $method = $cb[1];
                $obj->$method($data);
            }
        }
        Mage::dispatchEvent('udpo_send_vendor_notification', $data);

        return $this;
    }

    public $createReturnAllShipments=false;
    public function createShipmentFromPo($udpo, $qtys=array(), $save=true, $setQtyShippedFlag=true, $noInvoiceFlag=false)
    {
        if (!Mage::helper('udropship')->isActive($udpo->getOrder()->getStore())) {
            return false;
        }

        $order = $udpo->getOrder();
        $hlp = Mage::helper('udropship');
        $hlpd = Mage::helper('udropship/protected');
        $convertor = Mage::getModel('sales/convert_order');
        $enableVirtual = Mage::getStoreConfig('udropship/misc/enable_virtual', $order->getStoreId());

        $shippingMethod = Mage::helper('udropship')->explodeOrderShippingMethod($order);

        $items = $udpo->getAllItems();

        $orderToPoItemMap = array();
        foreach ($items as $poItem) {
            $orderToPoItemMap[$poItem->getOrderItemId()] = $poItem;
        }

        $shipmentIncrement = Mage::getStoreConfig('udropship/purchase_order/shipment_increment_type', $order->getStoreId());

        if ($shipmentIncrement == Unirgy_DropshipPo_Model_Source::SHIPMENT_INCREMENT_ORDER_BASED) {
            $shipmentIncrementBase = $order->getIncrementId();
            $shipmentIndex = $order->getShipmentsCollection()->count();
        } elseif ($shipmentIncrement == Unirgy_DropshipPo_Model_Source::SHIPMENT_INCREMENT_PO_BASED) {
            $shipmentIncrementBase = $udpo->getIncrementId();
            $shipmentIndex = $udpo->getShipmentsCollection()->count();
        }

        $orderToShipItemMap = array();

        $shipments = array();
        $canShipItemFlags = array();
        foreach ($items as $poItem) {
            $orderItem = $poItem->getOrderItem();
            $canShipItemFlags[$poItem->getId()] = $this->_canShipItem($orderItem, $poItem, $orderToPoItemMap, $qtys);
        }
        foreach ($items as $poItem) {
            $orderItem = $poItem->getOrderItem();

            if (!$canShipItemFlags[$poItem->getId()]) {
                continue;
            }

            $vId = $udpo->getUdropshipVendor();
            $vendor = $hlp->getVendor($vId);

            $vIds = array();
            if ($orderItem->getHasChildren()) {
                $children = $orderItem->getChildrenItems() ? $orderItem->getChildrenItems() : $orderItem->getChildren();
                foreach ($children as $child) {
                    if (!isset($orderToPoItemMap[$child->getId()]) || !$canShipItemFlags[$orderToPoItemMap[$child->getId()]->getId()]) continue;
                    $udpoKey = $vId;
                    if (!$udpo->getUdpoNoSplitPoFlag()) {
                    if (Mage::helper('udropship')->isSeparateShipment($child, $vId) && $orderItem->isShipSeparately()) {
                        $udpoKey .= '-'.($child->getUdpoSeqNumber() ? $child->getUdpoSeqNumber() : $child->getId());
                    } elseif (Mage::helper('udropship')->isSeparateShipment($orderItem, $vId)) {
                        $udpoKey .= '-'.($orderItem->getUdpoSeqNumber() ? $orderItem->getUdpoSeqNumber() : $orderItem->getId());
                    }}
                    $vIds[$udpoKey] = $vId;
                }
                if (empty($vIds)) {
                    $udpoKey = $vId;
                    $vIds[$udpoKey] = $vId;
                }
            } else {
                $udpoKey = $vId;
                $oiParent = $orderItem->getParentItem();
                if (!$udpo->getUdpoNoSplitPoFlag()) {
                if (Mage::helper('udropship')->isSeparateShipment($orderItem, $vId)
                    && (!$oiParent || $oiParent->isShipSeparately())
                ) {
                    $udpoKey .= '-'.($orderItem->getUdpoSeqNumber() ? $orderItem->getUdpoSeqNumber() : $orderItem->getId());
                } elseif ($oiParent && Mage::helper('udropship')->isSeparateShipment($oiParent, $vId)) {
                    $udpoKey .= '-'.($oiParent->getUdpoSeqNumber() ? $oiParent->getUdpoSeqNumber() : $oiParent->getId());
                }}
                $vIds[$udpoKey] = $vId;
            }

            foreach ($vIds as $udpoKey=>$vId) {
            $vendor = $hlp->getVendor($vId);

            if (empty($shipments[$udpoKey])) {
                $shipmentStatus = (int)Mage::getStoreConfig('udropship/vendor/default_shipment_status', $order->getStoreId());
                if ('999' != $vendor->getData('initial_shipment_status')) {
                    $shipmentStatus = $vendor->getData('initial_shipment_status');
                }
                $shipments[$udpoKey] = $convertor->toShipment($order)
                    ->setUdpo($udpo)
                    ->setUdpoId($udpo->getId())
                    ->setUdpoIncrementId($udpo->getIncrementId())
                    ->setUdropshipVendor($vId)
                    ->setUdropshipStatus($shipmentStatus)
                    ->setTotalQty(0)
                    ->setShippingAmount(0)
                    ->setBaseShippingAmount(0)
                    ->setShippingAmountIncl(0)
                    ->setBaseShippingAmountIncl(0)
                    ->setShippingTax(0)
                    ->setBaseShippingTax(0);

                if ($shipmentIncrement == Unirgy_DropshipPo_Model_Source::SHIPMENT_INCREMENT_ORDER_BASED
                    || $shipmentIncrement == Unirgy_DropshipPo_Model_Source::SHIPMENT_INCREMENT_PO_BASED
                ) {
                    $shipmentIndex++;
                    $shipments[$udpoKey]->setIncrementId(sprintf('%s-%s', $shipmentIncrementBase, $shipmentIndex));
                }

                $_orderRate = $udpo->getOrder()->getBaseToOrderRate() > 0 ? $udpo->getOrder()->getBaseToOrderRate() : 1;
                $_baseSa = $udpo->hasShipmentShippingAmount() ? $udpo->getShipmentShippingAmount() : $udpo->getBaseShippingAmountLeft();
                $_sa = Mage::app()->getStore()->roundPrice($_orderRate*$_baseSa);
                $shipments[$udpoKey]
                    ->setShippingAmount($_sa)
                    ->setBaseShippingAmount($_baseSa)
                    ->setShippingAmountIncl($udpo->getShippingAmountIncl())
                    ->setBaseShippingAmountIncl($udpo->getBaseShippingAmountIncl())
                    ->setShippingTax($udpo->getShippingTax())
                    ->setBaseShippingTax($udpo->getBaseShippingTax())
                    ->setUdropshipMethod($udpo->getUdropshipMethod())
                    ->setUdropshipMethodDescription($udpo->getUdropshipMethodDescription())
                ;
            }
            if ($orderItem->isDummy(true)) {
                if ($orderItem->getParentItem()) {
                    $qty = $orderItem->getQtyOrdered()/$orderItem->getParentItem()->getQtyOrdered();
                } else {
                    $qty = 1;
                }
            } else {
                if (isset($qtys[$poItem->getId()])) {
                    $qty = $qtys[$poItem->getId()];
                } else {
                    $qty = $poItem->getQtyToShip();
                }
            }

            $item = $convertor->itemToShipmentItem($orderItem)->setUdpoItem($poItem)->setUdpoItemId($poItem->getId());

            $orderToShipItemMap[$orderItem->getId().'-'.$vId] = $item;

            $this->setShipmentItemQty($item, $poItem, $qty);

            if (!$orderItem->getHasChildren()
                || $orderItem->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE
            ) {
                if (abs($orderItem->getBaseCost())<0.001) {
                    $item->setBaseCost($orderItem->getBasePrice());
                } else {
                    $item->setBaseCost($orderItem->getBaseCost());
                }
            }

            //$item->register();
            if ($setQtyShippedFlag) {
                $poItem->setQtyShipped(
                    $poItem->getQtyShipped()+$item->getQty()
                );
                $orderItem->setQtyShipped(
                    $orderItem->getQtyShipped()+$item->getQty()
                );
            }

            $_totQty = $item->getQty();
            if (($_parentItem = $orderItem->getParentItem())
                && isset($orderToShipItemMap[$_parentItem->getId().'-'.$vId])
            ) {
                $_totQty *= $orderToShipItemMap[$_parentItem->getId().'-'.$vId]->getQty();
            }

            $shipments[$udpoKey]->addItem($item);
            if (!$orderItem->isDummy(true)) {
                $qtyOrdered = $orderItem->getQtyOrdered();
                $_rowDivider = $_totQty/($qtyOrdered>0 ? $qtyOrdered : 1);
                $iTax = $orderItem->getBaseTaxAmount()*($_rowDivider>0 ? $_rowDivider : 1);
                $iDiscount = $orderItem->getBaseDiscountAmount()*($_rowDivider>0 ? $_rowDivider : 1);
                $shipments[$udpoKey]
                    ->setBaseTaxAmount($shipments[$udpoKey]->getBaseTaxAmount()+$iTax)
                    ->setBaseDiscountAmount($shipments[$udpoKey]->getBaseDiscountAmount()+$iDiscount)
                    ->setBaseTotalValue($shipments[$udpoKey]->getBaseTotalValue()+$orderItem->getBasePrice()*$_totQty)
                    ->setTotalValue($shipments[$udpoKey]->getTotalValue()+$orderItem->getPrice()*$_totQty)
                    ->setTotalQty($shipments[$udpoKey]->getTotalQty()+$qty)
                ;
            }
            if ($orderItem->getParentItem()) {
                $weightType = $orderItem->getParentItem()->getProductOptionByCode('weight_type');
                if (null !== $weightType && !$weightType) {
                    $shipments[$udpoKey]->setTotalWeight($shipments[$udpoKey]->getTotalWeight()+$orderItem->getWeight()*$_totQty);
                }
            } else {
                $weightType = $orderItem->getProductOptionByCode('weight_type');
                if (null === $weightType || $weightType) {
                    $shipments[$udpoKey]->setTotalWeight($shipments[$udpoKey]->getTotalWeight()+$orderItem->getWeight()*$_totQty);
                }
            }
            if (!$orderItem->getHasChildren()) {
                $shipments[$udpoKey]->setTotalCost(
                    $shipments[$udpoKey]->getTotalCost()+$item->getBaseCost()*$_totQty
                );
            }
            $shipments[$udpoKey]->setCommissionPercent($vendor->getCommissionPercent());
            $shipments[$udpoKey]->setTransactionFee($vendor->getTransactionFee());
            }
        }

        if (!$save) {
            reset($shipments);
            return count($shipments)>0 ? ($this->createReturnAllShipments ? $shipments : current($shipments)) : false;
        }

        if (empty($shipments)) return false;

        Mage::dispatchEvent('udpo_po_shipment_save_before', array('order'=>$order, 'udpo'=>$udpo, 'shipments'=>$shipments));

        $udpoSplitWeights = array();
        foreach ($shipments as $_vUdpoKey => $_vUdpo) {
            if (empty($udpoSplitWeights[$_vUdpo->getUdropshipVendor().'-'])) {
                $udpoSplitWeights[$_vUdpo->getUdropshipVendor().'-']['weights'] = array();
                $udpoSplitWeights[$_vUdpo->getUdropshipVendor().'-']['total_weight'] = 0;
            }
            $weight = $_vUdpo->getTotalWeight()>0 ? $_vUdpo->getTotalWeight() : .001;
            $udpoSplitWeights[$_vUdpo->getUdropshipVendor().'-']['weights'][$_vUdpoKey] = $weight;
            $udpoSplitWeights[$_vUdpo->getUdropshipVendor().'-']['total_weight'] += $weight;
        }

        $transaction = Mage::getModel('core/resource_transaction');
        foreach ($shipments as $shipment) {
            Mage::helper('udropship')->addVendorSkus($shipment);
            $shipment->setNoInvoiceFlag($noInvoiceFlag);
            if (empty($udpoNoSplitWeights[$shipment->getUdropshipVendor().'-'])
                && !empty($udpoSplitWeights[$shipment->getUdropshipVendor().'-']['weights'][$udpoKey])
                && count($udpoSplitWeights[$shipment->getUdropshipVendor().'-']['weights'])>1
            ) {
                $_splitWeight = $udpoSplitWeights[$shipment->getUdropshipVendor().'-']['weights'][$udpoKey];
                $_totalWeight = $udpoSplitWeights[$shipment->getUdropshipVendor().'-']['total_weight'];
                $shipment->setShippingAmount($shipment->getShippingAmount()*$_splitWeight/$_totalWeight);
                $shipment->setBaseShippingAmount($shipment->getBaseShippingAmount()*$_splitWeight/$_totalWeight);
                $shipment->setShippingAmountIncl($shipment->getShippingAmountIncl()*$_splitWeight/$_totalWeight);
                $shipment->setBaseShippingAmountIncl($shipment->getBaseShippingAmountIncl()*$_splitWeight/$_totalWeight);
                $shipment->setShippingTax($shipment->getShippingTax()*$_splitWeight/$_totalWeight);
                $shipment->setBaseShippingTax($shipment->getBaseShippingTax()*$_splitWeight/$_totalWeight);
            }
            $order->getShipmentsCollection()->addItem($shipment);
            $udpo->getShipmentsCollection()->addItem($shipment);
            $transaction->addObject($shipment);
        }
        $transaction->addObject($order->setIsInProcess(true))->addObject($udpo->setData('___dummy',1))->save();

        $shipped = count($shipments);
        foreach ($shipments as $shipment) {
            if (!in_array($shipment->getUdropshipStatus(), array(Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_SHIPPED,Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_DELIVERED))) {
                $shipped = false;
                break;
            }
        }
        //        if ($shipped) {
        //            foreach ($shipments as $shipment) {
        //                $this->completeUdpoIfShipped($shipment, true);
        //                break;
        //            }
        //        } else {
        //            $this->processPoStatusSave($udpo, Unirgy_DropshipPo_Model_Source::UDPO_STATUS_READY, true);
        //        }

        Mage::dispatchEvent('udpo_po_shipment_save_after', array('order'=>$order, 'udpo'=>$udpo, 'shipments'=>$shipments));

        /* no need to send notification because shipments created by vendor
        // send vendor notifications
        foreach ($shipments as $shipment) {
            $hlp->sendVendorNotification($shipment);
        }

        $hlp->processQueue();
        */

        reset($shipments);

        return count($shipments)>0 ? ($this->createReturnAllShipments ? $shipments : current($shipments)) : false;
    }

    public function completeShipment($shipment) {}

    public function invoiceShipment($shipment)
    {
    	if ($shipment->getNoInvoiceFlag()) return false;
        if (!($udpo = $this->getShipmentPo($shipment))) return false;
        $autoInvoiceFlag = Mage::getStoreConfig('udropship/purchase_order/autoinvoice_shipment', $udpo->getStoreId());
        if (!$shipment->getDoInvoiceFlag()) {
	        if (!$autoInvoiceFlag) return false;
	        $autoInvoiceStatuses = Mage::getStoreConfig('udropship/purchase_order/autoinvoice_shipment_statuses', $udpo->getStoreId());
	        if (!is_array($autoInvoiceStatuses)) {
	            $autoInvoiceStatuses = explode(',', $autoInvoiceStatuses);
	        }
	        if (!in_array($shipment->getUdropshipStatus(), $autoInvoiceStatuses)) return false;
        }
        if (!$udpo->canInvoiceShipment($shipment)) {
            if (!$udpo->getOrder()->getInvoiceCollection()->getItemByColumnValue('shipment_id', $shipment->getId())) {
                $udpo->addComment($this->__('Cannot autoinvoice shipment # %s', $shipment->getIncrementId()), false, false);
                $udpo->saveComments();
            }
            return false;
        }
        if (Unirgy_DropshipPo_Model_Source::AUTOINVOICE_SHIPMENT_YES == $autoInvoiceFlag
            && !$shipment->getOrder()->getPayment()->canCapturePartial()
        ) {
            $udpo->addComment($this->__('Cannot autoinvoice shipment # %s: order payment method does not allow partial capture', $shipment->getIncrementId()), false, false);
            $udpo->saveComments();
            return false;
        } elseif (Unirgy_DropshipPo_Model_Source::AUTOINVOICE_SHIPMENT_ORDER == $autoInvoiceFlag
            && !$shipment->getOrder()->getPayment()->canCapture()
        ) {
            $udpo->addComment($this->__('Cannot autoinvoice shipment # %s: order payment method does not allow online capture', $shipment->getIncrementId()), false, false);
            //            $udpo->saveComments();
            return false;
        }

        $isItemsRegistered = $isFullRegistered = false;
        $udpo->getResource()->beginTransaction();
        try {

            if (Unirgy_DropshipPo_Model_Source::AUTOINVOICE_SHIPMENT_ORDER == $autoInvoiceFlag) {

                $invoice = Mage::getModel('sales/service_order', $shipment->getOrder())->prepareInvoice();

                $invoice->getOrder()->getPayment()->unsParentTransactionId();
                $invoice->getOrder()->getPayment()->unsTransactionId();

                if ($invoice->getBaseGrandTotal()>0 && $shipment->getOrder()->getPayment()->canCapture()) {
                    $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
                } else {
                    $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
                }

                $isItemsRegistered = true;

                $invoice->register();
                $invoice->getOrder()->setIsInProcess(true);

                $isFullRegistered = true;

                Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder())
                    ->save();

            } else {

                $qtys = array();
                foreach ($shipment->getAllItems() as $sItem) {
                    $qtys[$sItem->getUdpoItemId()] = $sItem->getQty();
                }

                $order = $udpo->getOrder();
                $hlp = Mage::helper('udropship');
                $hlpd = Mage::helper('udropship/protected');
                $convertor = Mage::getModel('sales/convert_order');

                $poItems = $udpo->getAllItems();

                $orderToPoItemMap = array();
                foreach ($poItems as $poItem) {
                    $orderToPoItemMap[$poItem->getOrderItemId()] = $poItem;
                }

                $invoice = $convertor->toInvoice($order)->setUdpo($udpo)->setUdpoId($udpo->getId())->setShipmentId($shipment->getId());
                $totalQty = 0;

                $hasItemToInvoice = false;

                foreach ($qtys as $poItemId => $qty) {
                    $poItem    = $udpo->getItemById($poItemId);
                    $orderItem = $poItem->getOrderItem();

                    if (!$this->_canInvoiceItem($orderItem, $poItem, $orderToPoItemMap, $qtys)) {
                        continue;
                    }

                    $hasItemToInvoice = true;

                    $item = $convertor->itemToInvoiceItem($orderItem)->setUdpoItem($poItem)->setUdpoItemId($poItemId);

                    if ($orderItem->isDummy()) {
                        $qty = 1;
                    } else {
                        $totalQty += $qty;
                    }
                    $item->setQty($qty);
                    $invoice->addItem($item);

                    $poItem->setQtyInvoiced(
                        $poItem->getQtyInvoiced()+$item->getQty()
                    );
                }

                $invoice->setBaseShippingAmount($shipment->getBaseShippingAmount());

                $invoice->setTotalQty($totalQty);
                $invoice->collectTotals();
                $order->getInvoiceCollection()->addItem($invoice);

                $order->getPayment()->unsParentTransactionId();
                $order->getPayment()->unsTransactionId();

                if ($invoice->getBaseGrandTotal()>0) {
                    $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
                } else {
                    $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
                }

                $isItemsRegistered = true;

                $invoice->register();

                $isFullRegistered = true;

                Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($order->setData('___dummy',1))
                    ->addObject($udpo->setData('___dummy',1))
                    ->save();

                $udpo->addComment($this->__('created invoice # %s for shipment # %s', $invoice->getIncrementId(), $shipment->getIncrementId()), false, false)->saveComments();
            }

            $udpo->getResource()->commit();

        } catch (Exception $e) {
            if (isset($invoice)) {
                if ($isFullRegistered) {
                    $invoice->cancel();
                } elseif ($isItemsRegistered) {
                    foreach ($invoice->getAllItems() as $item) {
                        $item->cancel();
                    }
                }
            }
            $udpo->getResource()->rollBack();
            $udpo->addComment($this->__('Autoinvoice Error for shipment # %s: %s', $shipment->getIncrementId(), $e->getMessage()), false, false);
            $udpo->saveComments();
            Mage::logException($e);
        }
        return true;
    }

    public function canCreatePo($order)
    {
        if ($order->canUnhold()) {
            return false;
        }
        foreach ($order->getAllItems() as $item) {
            if ($this->getOrderItemQtyToUdpo($item)>0 && (!$item->getLockedDoUdpo() || $order->getSkipLockedCheckFlag())) {
                return true;
            }
        }
        return false;
    }

    public function checkCreatePoQtys($order, $qtys)
    {
        $result = true;
        foreach ($qtys as $itemId => $qty) {
            if (($oItem = Mage::helper('udropship')->getOrderItemById($order, $itemId))) {
                $result = $result && ($qty <= $this->getOrderItemQtyToUdpo($oItem) || $oItem->isDummy(true));
            }
        }
        return $result;
    }

    public function canPoItem($item, $qtys=array())
    {
        return $this->_canPoItem($item, $qtys);
    }
    protected function _canPoItem($item, $qtys=array())
    {
        if ($item->getLockedDoUdpo() && !$item->getOrder()->getSkipLockedCheckFlag()) {
            return false;
        }
        if ($item->isDummy(true)) {
            if ($item->getHasChildren()) {
                $children = $item->getChildrenItems() ? $item->getChildrenItems() : $item->getChildren();
                foreach ($children as $child) {
                    if ($this->_canPoItem($child, $qtys)) {
                        return true;
                    }
                }
                return false;
            } else if($item->getParentItem()) {
                return $this->_canPoItem($item->getParentItem(), $qtys);
            }
        } else {
            if (empty($qtys)) {
                return $this->getOrderItemQtyToUdpo($item)>0;
            } else {
                return isset($qtys[$item->getId()]) && $qtys[$item->getId()] > 0;
            }
        }
    }

    protected function _canShipItem($orderItem, $poItem, $orderToPoItemMap, $qtys=array())
    {
        $sId = $orderItem->getOrder() ? $orderItem->getOrder()->getStoreId() : null;
        $enableVirtual = Mage::getStoreConfig('udropship/misc/enable_virtual', $sId);
        if ($orderItem->getIsVirtual() && !$enableVirtual || $orderItem->getLockedDoShip()) {
            return false;
        }
        if ($orderItem->isDummy(true)) {
            if ($orderItem->getHasChildren()) {
                foreach ($orderItem->getChildrenItems() as $child) {
                    if ($child->getIsVirtual() && !$enableVirtual) {
                        continue;
                    }
                    if (isset($orderToPoItemMap[$child->getId()])
                        && ($poChild = $orderToPoItemMap[$child->getId()])
                    ) {
                        if (empty($qtys)) {
                            if ($poChild->getQtyToShip() > 0 && !$child->getLockedDoShip()) {
                                return true;
                            }
                        } else {
                            if (isset($qtys[$poChild->getId()]) && $qtys[$poChild->getId()] > 0 && !$child->getLockedDoShip()) {
                                return true;
                            }
                        }
                    }
                }
                return false;
            } else if (($parent = $orderItem->getParentItem())
                && isset($orderToPoItemMap[$parent->getId()])
                && ($poParent = $orderToPoItemMap[$parent->getId()])
                && !$parent->getLockedDoShip()
            ) {
                if (empty($qtys)) {
                    return $poParent->getQtyToShip() > 0;
                } else {
                    return isset($qtys[$poParent->getId()]) && $qtys[$poParent->getId()] > 0;
                }
            }
        } else {
            if (empty($qtys)) {
                return $poItem->getQtyToShip() > 0;
            } else {
                return isset($qtys[$poItem->getId()]) && $qtys[$poItem->getId()] > 0;
            }
        }
        return false;
    }

    protected function _canInvoiceItem($orderItem, $poItem, $orderToPoItemMap, $qtys=array())
    {
        if ($orderItem->getLockedDoInvoice()) {
            return false;
        }
        if ($orderItem->isDummy()) {
            if ($orderItem->getHasChildren()) {
                foreach ($orderItem->getChildrenItems() as $child) {
                    if (isset($orderToPoItemMap[$child->getId()])
                        && ($poChild = $orderToPoItemMap[$child->getId()])
                    ) {
                        if (empty($qtys)) {
                            if ($poChild->getQtyToInvoice() > 0 && !$child->getLockedDoInvoice()) {
                                return true;
                            }
                        } else {
                            if (isset($qtys[$poChild->getId()]) && $qtys[$poChild->getId()] > 0 && !$child->getLockedDoInvoice()) {
                                return true;
                            }
                        }
                    }
                }
                return false;
            } else if (($parent = $orderItem->getParentItem())
                && isset($orderToPoItemMap[$parent->getId()])
                && ($poParent = $orderToPoItemMap[$parent->getId()])
                && !$parent->getLockedDoInvoice()
            ) {
                if (empty($qtys)) {
                    return $poParent->getQtyToInvoice() > 0;
                } else {
                    return isset($qtys[$poParent->getId()]) && $qtys[$poParent->getId()] > 0;
                }
            }
        } else {
            if (empty($qtys)) {
                return $poItem->getQtyToInvoice() > 0;
            } else {
                return isset($qtys[$poItem->getId()]) && $qtys[$poItem->getId()] > 0;
            }
        }
        return false;
    }

    public function setShipmentItemQty($shipmentItem, $poItem, $qty)
    {
        if ($qty <= $poItem->getQtyToShip() || $shipmentItem->getOrderItem()->isDummy(true)) {
            return $shipmentItem->setQty($qty);
        }
        else {
            Mage::throwException(
                Mage::helper('sales')->__('Invalid qty to ship for item "%s"', $shipmentItem->getName())
            );
        }
    }

    public function setInvoiceItemQty($iItem, $poItem, $qty)
    {
        if ($qty <= $poItem->getQtyToInvoice() || $iItem->getOrderItem()->isDummy()) {
            return $iItem->setQty($qty);
        }
        else {
            Mage::throwException(
                Mage::helper('sales')->__('Invalid qty to invoice for item "%s"', $iItem->getName())
            );
        }
    }


    public function getOrderItemQtyToUdpo($item, $skipDummy=false)
    {
        if ($item->isDummy(true) && !$skipDummy) {
            return 0;
        }
        $qty = $item->getQtyOrdered()
            - $item->getQtyUdpo()
            - $item->getQtyRefunded()
            - $item->getQtyCanceled();
        return max($qty, 0);
    }

    public function toUdpo($order)
    {
        $udpo = Mage::getModel('udpo/po');
        $udpo->setOrder($order)
            ->setStoreId($order->getStoreId())
            ->setCustomerId($order->getCustomerId())
            ->setBillingAddressId($order->getBillingAddressId())
            ->setShippingAddressId($order->getShippingAddressId());
        Mage::helper('core')->copyFieldset('sales_convert_order', 'to_udpo', $order, $udpo);
        return $udpo;
    }

    public function itemToUdpoItem($orderItem)
    {
        $udpoItem = Mage::getModel('udpo/po_item');
        $udpoItem->setOrderItem($orderItem)
            ->setProductId($orderItem->getProductId());
        Mage::helper('core')->copyFieldset('sales_convert_order_item', 'to_udpo_item', $orderItem, $udpoItem);
        return $udpoItem;
    }

    public function initOrderUdposCollection($order, $forceReload=false)
    {
        if (!$order->hasUdposCollection() || $forceReload) {
            $udposCollection = Mage::getResourceModel('udpo/po_collection')
                ->setOrderFilter($order);
            $order->setUdposCollection($udposCollection);

            if ($order->getId()) {
                foreach ($udposCollection as $udpo) {
                    $udpo->setOrder($order);
                }
            }
        }
        return $this;
    }

    public function getUdpoStatusName($po)
    {
        $statuses = Mage::getSingleton('udpo/source')->setPath('po_statuses')->toOptionHash();
        $id = $po->getUdropshipStatus();
        return isset($statuses[$id]) ? $statuses[$id] : 'Unknown';
    }

    protected $_vendorShipmentCollection;

    public function getVendorShipmentCollection()
    {
        if (!$this->_vendorShipmentCollection) {
            $collection = Mage::getModel('sales/order_shipment')->getCollection();
            $poIds = array();
            foreach ($this->getVendorPoCollection() as $po) {
                $poIds[] = $po->getId();
            }
            if (!empty($poIds)) {
                $collection->getSelect()->where('udpo_id in (?)', $poIds);
            } else {
                $collection->getSelect()->where('false');
            }
            $collection->getSelect()->where('udropship_vendor=?', Mage::getSingleton('udropship/session')->getVendorId());
            $this->_vendorShipmentCollection = $collection;
        }
        return $this->_vendorShipmentCollection;
    }

    protected $_vendorPoCollection;

    public function getVendorPoCollection()
    {
        if (!$this->_vendorPoCollection) {
            $vendorId = Mage::getSingleton('udropship/session')->getVendorId();
            $vendor = Mage::helper('udropship')->getVendor($vendorId);
            $collection = Mage::getModel('udpo/po')->getCollection();

            $orderTableQted = $collection->getResource()->getReadConnection()->quoteIdentifier('sales/order');
            $collection->join('sales/order', "$orderTableQted.entity_id=main_table.order_id", array(
                'order_increment_id' => 'increment_id',
                'order_created_at' => 'created_at',
                'shipping_method',
            ));

            $collection->addAttributeToFilter('udropship_vendor', $vendorId);

            $r = Mage::app()->getRequest();

            if (($v = $r->getParam('filter_order_id_from'))) {
                $collection->addAttributeToFilter("$orderTableQted.increment_id", array('gteq'=>$v));
            }
            if (($v = $r->getParam('filter_order_id_to'))) {
                $collection->addAttributeToFilter("$orderTableQted.increment_id", array('lteq'=>$v));
            }

            if (($v = $r->getParam('filter_order_date_from'))) {
                $_filterDate = Mage::app()->getLocale()->date();
                $_filterDate->set($v, Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT));
                $_filterDate->setTimezone(Mage_Core_Model_Locale::DEFAULT_TIMEZONE);
                $collection->addAttributeToFilter("$orderTableQted.created_at", array('gteq'=>$_filterDate->toString(Varien_Date::DATETIME_INTERNAL_FORMAT)));
            }
            if (($v = $r->getParam('filter_order_date_to'))) {
                $_filterDate = Mage::app()->getLocale()->date();
                $_filterDate->set($v, Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT));
                $_filterDate->addDay(1);
                $_filterDate->setTimezone(Mage_Core_Model_Locale::DEFAULT_TIMEZONE);
                $collection->addAttributeToFilter("$orderTableQted.created_at", array('lteq'=>$_filterDate->toString(Varien_Date::DATETIME_INTERNAL_FORMAT)));
            }

            if (($v = $r->getParam('filter_po_id_from'))) {
                $collection->addAttributeToFilter('main_table.increment_id', array('gteq'=>$v));
            }
            if (($v = $r->getParam('filter_po_id_to'))) {
                $collection->addAttributeToFilter('main_table.increment_id', array('lteq'=>$v));
            }

            if (($v = $r->getParam('filter_po_date_from'))) {
                $_filterDate = Mage::app()->getLocale()->date();
                $_filterDate->set($v, Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT));
                $_filterDate->setTimezone(Mage_Core_Model_Locale::DEFAULT_TIMEZONE);
                $collection->addAttributeToFilter('main_table.created_at', array('gteq'=>$_filterDate->toString(Varien_Date::DATETIME_INTERNAL_FORMAT)));
            }
            if (($v = $r->getParam('filter_po_date_to'))) {
                $_filterDate = Mage::app()->getLocale()->date();
                $_filterDate->set($v, Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT));
                $_filterDate->addDay(1);
                $_filterDate->setTimezone(Mage_Core_Model_Locale::DEFAULT_TIMEZONE);
                $collection->addAttributeToFilter('main_table.created_at', array('lteq'=>$_filterDate->toString(Varien_Date::DATETIME_INTERNAL_FORMAT)));
            }

            if (($v = $r->getParam('filter_method'))) {
                if (array_key_exists('VIRTUAL_PO', $v)) {
                    $collection->addFieldToFilter(
                        array('main_table.udropship_method', 'main_table.is_virtual'),
                        array(array('in'=>array_keys($v)), '1')
                    );
                } else {
                    $collection->addAttributeToFilter('main_table.udropship_method', array('in'=>array_keys($v)));
                }
            }

            if (!$r->getParam('apply_filter') && $vendor->getData('vendor_po_grid_status_filter')) {
                $filterStatuses = $vendor->getData('vendor_po_grid_status_filter');
                $filterStatuses = array_combine($filterStatuses, array_fill(0, count($filterStatuses), 1));
                $r->setParam('filter_status', $filterStatuses);
            }

            if (($v = $r->getParam('filter_status'))) {
                $collection->addAttributeToFilter('main_table.udropship_status', array('in'=>array_keys($v)));
            }

            if (!$r->getParam('sort_by') && $vendor->getData('vendor_po_grid_sortby')) {
                $r->setParam('sort_by', $vendor->getData('vendor_po_grid_sortby'));
                $r->setParam('sort_dir', $vendor->getData('vendor_po_grid_sortdir'));
            }
            if (($v = $r->getParam('sort_by'))) {
                $map = array('order_date'=>'order_created_at', 'po_date'=>'created_at');
                if (isset($map[$v])) {
                    $v = $map[$v];
                }
                $collection->setOrder($v, $r->getParam('sort_dir'));
            }
            $this->_vendorPoCollection = $collection;
        }
        return $this->_vendorPoCollection;
    }

    public function getOrderItemVendorName($orderItem)
    {
        if ($orderItem->getHasChildren() && $orderItem->isDummy(true)) {
            foreach ($orderItem->getChildrenItems() as $child) {
                $vendor = Mage::helper('udropship')->getVendor($child->getUdropshipVendor());
                break;
            }
        } else {
            $vendor = Mage::helper('udropship')->getVendor($orderItem->getUdropshipVendor());
        }
        return $vendor && $vendor->getId() ? $vendor->getVendorName() : '';
    }

    public function getVendorPoMultiPdf($udpos)
    {
        foreach ($udpos as $udpo) {
            Mage::helper('udropship')->assignVendorSkus($udpo);
            $tracks = $udpo->getOrder()->getTracksCollection();
            $tracks->load();
            foreach ($tracks as $id=>$track) {
                $tracks->removeItemByKey($id);
            }
            if ($udpo->getUdropshipMethodDescription()) {
                $udpo->getOrder()->setData('__orig_shipping_description', $udpo->getOrder()->getShippingDescription());
                $udpo->getOrder()->setShippingDescription($udpo->getUdropshipMethodDescription());
            }
        }
        $pdf = Mage::getModel('udpo/pdf_po')
            ->setUseFont(Mage::getStoreConfig('udropship/vendor/pdf_use_font'))
            ->getPdf($udpos);
        foreach ($udpos as $udpo) {
            Mage::helper('udropship')->unassignVendorSkus($udpo);
            if ($udpo->getOrder()->hasData('__orig_shipping_description')) {
                $udpo->getOrder()->setShippingDescription($udpo->getOrder()->getData('__orig_shipping_description'));
                $udpo->getOrder()->unsetData('__orig_shipping_description');
            }
        }
        return $pdf;
    }

    public function sendNewPoNotificationEmail($po, $comment='')
    {
        $order = $po->getOrder();
        $store = $order->getStore();

        $vendor = $po->getVendor();

        $hlp = Mage::helper('udropship');
        $udpoHlp = Mage::helper('udpo');
        $data = array();

        if (!$po->getResendNotificationFlag()
            && ($store->getConfig('udropship/vendor/attach_packingslip') && $vendor->getAttachPackingslip()
            || $store->getConfig('udropship/vendor/attach_shippinglabel') && $vendor->getAttachShippinglabel() && $vendor->getLabelType())
        ) {
            $udpoHlp->createReturnAllShipments=true;
            if ($shipments = $udpoHlp->createShipmentFromPo($po, array(), true, true, true)) {
                foreach ($shipments as $shipment) {
                    $shipment->setNewShipmentFlag(true);
                    $shipment->setDeleteOnFailedLabelRequestFlag(true);
                }
            }
            $udpoHlp->createReturnAllShipments=false;
        }

        if ($po->getResendNotificationFlag()) {
            foreach ($po->getShipmentsCollection() as $_shipment) {
                if ($_shipment->getUdropshipStatus()!=Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_CANCELED) {
                    $shipments[] = $_shipment;
                    break;
                }
            }
        }

        $adminTheme = explode('/', Mage::getStoreConfig('udropship/admin/interface_theme', 0));

        if ($store->getConfig('udropship/purchase_order/attach_po_pdf') && $vendor->getAttachPoPdf()) {
            $hlp->setDesignStore(0, 'adminhtml', $adminTheme);

            $orderShippingAmount = $order->getShippingAmount();
            $order->setShippingAmount($po->getShippingAmount());

            $pdf = Mage::helper('udpo')->getVendorPoMultiPdf(array($po));

            $order->setShippingAmount($orderShippingAmount);

            $data['_ATTACHMENTS'][] = array(
                'content'=>$pdf->render(),
                'filename'=>'purchase_order-'.$po->getIncrementId().'-'.$vendor->getId().'.pdf',
                'type'=>'application/x-pdf',
            );
            $hlp->setDesignStore();
        }

        if ($store->getConfig('udropship/vendor/attach_packingslip') && $vendor->getAttachPackingslip() && !empty($shipments)) {
            $hlp->setDesignStore(0, 'adminhtml', $adminTheme);

            foreach ($shipments as $shipment) {
            $orderShippingAmount = $order->getShippingAmount();
            $order->setShippingAmount($shipment->getShippingAmount());

            $pdf = Mage::helper('udropship')->getVendorShipmentsPdf(array($shipment));

            $order->setShippingAmount($orderShippingAmount);
            $shipment->setDeleteOnFailedLabelRequestFlag(false);

            $data['_ATTACHMENTS'][] = array(
                'content'=>$pdf->render(),
                'filename'=>'packingslip-'.$po->getIncrementId().'-'.$vendor->getId().'.pdf',
                'type'=>'application/x-pdf',
            );
            }
            $hlp->setDesignStore();
        }

        if ($store->getConfig('udropship/vendor/attach_shippinglabel') && $vendor->getAttachShippinglabel()
            && $vendor->getLabelType() && !empty($shipments)
        ) {
            foreach ($shipments as $shipment) {
            try {
                $hlp->unassignVendorSkus($shipment);
                $hlp->unassignVendorSkus($po);
                foreach ($shipment->getAllItems() as $sItem) {
                    $firstOrderItem = $sItem->getOrderItem();
                    break;
                }
                if (!isset($firstOrderItem) || !$firstOrderItem->getUdpompsManual()) {
                    if (!$po->getResendNotificationFlag()) {
                        $batch = Mage::getModel('udropship/label_batch')->setVendor($vendor)->processShipments(array($shipment));
                        if ($batch->getErrors()) {
                            if (Mage::app()->getRequest()->getRouteName()=='udropship') {
                                Mage::throwException($batch->getErrorMessages());
                            } else {
                                Mage::helper('udropship/error')->sendLabelRequestFailedNotification($shipment, $batch->getErrorMessages());
                            }
                        } else {
                            if ($batch->getShipmentCnt()>1) {
                                $labelModel = Mage::helper('udropship')->getLabelTypeInstance($batch->getLabelType());
                                $data['_ATTACHMENTS'][] = $labelModel->renderBatchContent($batch);
                            } else {
                                $labelModel = $hlp->getLabelTypeInstance($batch->getLabelType());
                                foreach ($shipment->getAllTracks() as $track) {
                                    $data['_ATTACHMENTS'][] = $labelModel->renderTrackContent($track);
                                }
                            }
                        }
                    } else {
                        $batchIds = array();
                        foreach ($shipment->getAllTracks() as $track) {
                            $batchIds[$track->getBatchId()][] = $track;
                        }
                        foreach ($batchIds as $batchId => $tracks) {
                            $batch = Mage::getModel('udropship/label_batch')->load($batchId);
                            if (!$batch->getId()) continue;
                            if (count($tracks)>1) {
                                $labelModel = Mage::helper('udropship')->getLabelTypeInstance($batch->getLabelType());
                                $data['_ATTACHMENTS'][] = $labelModel->renderBatchContent($batch);
                            } else {
                                reset($tracks);
                                $labelModel = Mage::helper('udropship')->getLabelTypeInstance($batch->getLabelType());
                                $data['_ATTACHMENTS'][] = $labelModel->renderTrackContent(current($tracks));
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                // ignore if failed
            }
            }
        }

    	if (!empty($shipments)) {
            foreach ($shipments as $shipment) {
                if ($shipment->getNewShipmentFlag() && !$shipment->isDeleted()) {
                    $shipment->setNoInvoiceFlag(false);
                    $hlp->unassignVendorSkus($shipment);
                    $hlp->unassignVendorSkus($po);
                    $udpoHlp->invoiceShipment($shipment);
                }
            }
        }

        $hlp->setDesignStore($store);
        $shippingAddress = $order->getShippingAddress();
        if (!$shippingAddress) {
            $shippingAddress = $order->getBillingAddress();
        }
        $hlp->assignVendorSkus($po);
        $data += array(
            'po'              => $po,
            'order'           => $order,
            'vendor'          => $vendor,
            'comment'         => $comment,
            'store_name'      => $store->getName(),
            'vendor_name'     => $vendor->getVendorName(),
            'po_id'           => $po->getIncrementId(),
            'order_id'        => $order->getIncrementId(),
            'customer_info'   => Mage::helper('udropship')->formatCustomerAddress($shippingAddress, 'html', $vendor),
            'shipping_method' => $po->getUdropshipMethodDescription() ? $po->getUdropshipMethodDescription() : $vendor->getShippingMethodName($order->getShippingMethod(), true),
            'po_url'          => Mage::getUrl('udpo/vendor/', array('_query'=>'filter_po_id_from='.$po->getIncrementId().'&filter_po_id_to='.$po->getIncrementId())),
            'po_pdf_url'      => Mage::getUrl('udpo/vendor/udpoPdf', array('udpo_id'=>$po->getId())),
        );

        $template = $vendor->getEmailTemplate();
        if (!$template) {
            $template = $store->getConfig('udropship/purchase_order/new_po_vendor_email_template');
        }
        $identity = $store->getConfig('udropship/vendor/vendor_email_identity');

        $data['_BCC'] = $vendor->getNewOrderCcEmails();
        if (($emailField = $store->getConfig('udropship/vendor/vendor_notification_field'))) {
            $email = $vendor->getData($emailField) ? $vendor->getData($emailField) : $vendor->getEmail();
        } else {
            $email = $vendor->getEmail();
        }
        Mage::getModel('udropship/email')->sendTransactional($template, $identity, $email, $vendor->getVendorName(), $data);
        $hlp->unassignVendorSkus($po);

        $hlp->setDesignStore();
    }

    public function sendPoCommentNotificationEmail($po, $comment)
    {
        $order = $po->getOrder();
        $store = $order->getStore();

        $vendor = $po->getVendor();

        $hlp = Mage::helper('udropship');
        $udpoHlp = Mage::helper('udpo');
        $data = array();

        $hlp->setDesignStore($store);

        $data += array(
            'po'              => $po,
            'order'           => $order,
            'vendor'          => $vendor,
            'comment'         => $comment,
            'store_name'      => $store->getName(),
            'vendor_name'     => $vendor->getVendorName(),
            'po_id'           => $po->getIncrementId(),
            'po_status'       => $po->getUdropshipStatusName(),
            'order_id'        => $order->getIncrementId(),
            'po_url'          => Mage::getUrl('udpo/vendor/', array('_query'=>'filter_po_id_from='.$po->getIncrementId().'&filter_po_id_to='.$po->getIncrementId())),
            'po_pdf_url'      => Mage::getUrl('udpo/vendor/udpoPdf', array('udpo_id'=>$po->getId())),
        );

        $template = $store->getConfig('udropship/purchase_order/po_comment_vendor_email_template');
        $identity = $store->getConfig('udropship/vendor/vendor_email_identity');

        $data['_BCC'] = $vendor->getNewOrderCcEmails();
        if (($emailField = $store->getConfig('udropship/vendor/vendor_notification_field'))) {
            $email = $vendor->getData($emailField) ? $vendor->getData($emailField) : $vendor->getEmail();
        } else {
            $email = $vendor->getEmail();
        }
        Mage::getModel('udropship/email')->sendTransactional($template, $identity, $email, $vendor->getVendorName(), $data);

        $hlp->setDesignStore();
    }

    public function sendVendorComment($udpo, $comment)
    {
        $order = $udpo->getOrder();
        $store = $order->getStore();
        $to = $store->getConfig('udropship/admin/vendor_comments_receiver');
        $subject = $store->getConfig('udropship/admin/vendor_po_comments_subject');
        $template = $store->getConfig('udropship/admin/vendor_po_comments_template');
        $vendor = Mage::helper('udropship')->getVendor($udpo->getUdropshipVendor());
        $ahlp = Mage::getModel('adminhtml/url');

        if ($subject && $template && $vendor->getId()) {
            $toEmail = $store->getConfig('trans_email/ident_'.$to.'/email');
            $toName = $store->getConfig('trans_email/ident_'.$to.'/name');
            $data = array(
                'vendor_name'   => $vendor->getVendorName(),
                'order_id'      => $order->getIncrementId(),
                'po_id'         => $udpo->getIncrementId(),
                'vendor_url'    => $ahlp->getUrl('udropship/adminhtml_vendor/edit', array(
                    'id'        => $vendor->getId()
                )),
                'order_url'     => $ahlp->getUrl('adminhtml/sales_order/view', array(
                    'order_id'  => $order->getId()
                )),
                'po_url'  => $ahlp->getUrl('udpoadmin/order_po/view', array(
                    'udpo_id'  => $udpo->getId(),
                    'order_id' => $order->getId(),
                )),
                'comment'      => $comment,
            );
            foreach ($data as $k=>$v) {
                $subject = str_replace('{{'.$k.'}}', $v, $subject);
                $template = str_replace('{{'.$k.'}}', $v, $template);
            }

            $mail = Mage::getModel('core/email')
                ->setFromEmail($vendor->getEmail())
                ->setFromName($vendor->getVendorName())
                ->setToEmail($toEmail)
                ->setToName($toName)
                ->setSubject($subject)
                ->setBody($template)
                ->send();
            //mail('"'.$toName.'" <'.$toEmail.'>', $subject, $template, 'From: "'.$vendor->getVendorName().'" <'.$vendor->getEmail().'>');
        }

        $udpo->addComment($this->__($vendor->getVendorName().': '.$comment), false, true)->saveComments();

        return $this;
    }

    public function getShipmentPo($shipment)
    {
        if (!$shipment->hasUdpo() && $shipment->getUdpoId()
            && ($po = Mage::getModel('udpo/po')->load($shipment->getUdpoId())) && $po->getId()
        ) {
            $shipment->setUdpo($po->setOrder($shipment->getOrder()));
        } elseif (!$shipment->hasUdpo()) {
            $shipment->setUdpo(false);
        }
        return $shipment->getUdpo();
    }

    public function getShipmentPoItem($sItem)
    {
        if (!$sItem->hasUdpoItem() && $sItem->getUdpoItemId()) {
            if (($shipment = $sItem->getShipment())
                && ($po = $this->getShipmentPo($shipment))
                && ($poItem = $po->getItemById($sItem->getUdpoItemId()))
            ) {
                $sItem->setUdpoItem($poItem);
            } elseif (($poItem = Mage::getModel('udpo/po_item')->load($sItem->getUdpoItemId())) && $poItem->getId()) {
                $sItem->setUdpoItem($poItem);
            }
        } elseif (!$sItem->hasUdpoItem()) {
            $sItem->setUdpoItem(false);
        }
        return $sItem->getUdpoItem();
    }

    public function getInvoicePo($invoice)
    {
        if (!$invoice->hasUdpo() && $invoice->getUdpoId()
            && ($po = Mage::getModel('udpo/po')->load($invoice->getUdpoId())) && $po->getId()
        ) {
            $invoice->setUdpo($po);
        } elseif (!$invoice->hasUdpo()) {
            $invoice->setUdpo(false);
        }
        return $invoice->getUdpo();
    }

    public function getInvoicePoItem($iItem)
    {
        if (!$iItem->hasUdpoItem() && $iItem->getUdpoItemId()) {
            if (($invoice = $iItem->getInvoice())
                && ($po = $this->getInvoicePo($invoice))
                && ($poItem = $po->getItemById($iItem->getUdpoItemId()))
            ) {
                $iItem->setUdpoItem($poItem);
            } elseif (($poItem = Mage::getModel('udpo/po_item')->load($iItem->getUdpoItemId())) && $poItem->getId()) {
                $iItem->setUdpoItem($poItem);
            }
        } elseif (!$iItem->hasUdpoItem()) {
            $iItem->setUdpoItem(false);
        }
        return $iItem->getUdpoItem();
    }

    public function getShipmentItemQtyToCancel($shipmentItem)
    {
        return $this->getShipmentItemQtyToShip($shipmentItem);
    }

    public function getShipmentItemQtyToShip($sItem)
    {
        $oItem = $sItem->getOrderItem();
        if ($oItem->isDummy(true)) {
            return 0;
        }
        $qty = $sItem->getQty() - $sItem->getQtyShipped() - $sItem->getQtyCanceled();
        return max($qty, 0);
    }

    public function cancelShipmentItem($sItem, $save)
    {
        if (($poItem = $this->getShipmentPoItem($sItem))) {
            $poItem->setQtyShipped(
                $poItem->getQtyShipped()-$this->getShipmentItemQtyToCancel($sItem)
            );
            $this->_processObjectSave($save, $poItem);
        }
        $oItem = $sItem->getOrderItem();
        $oItem->setQtyShipped(
            $oItem->getQtyShipped()-$this->getShipmentItemQtyToCancel($sItem)
        );
        $this->_processObjectSave($save, $oItem);
        $sItem->setQtyCanceled(
            $sItem->getQtyCanceled()+$this->getShipmentItemQtyToCancel($sItem)
        );
        $this->_processObjectSave($save, $sItem);
    }

    public function cancelShipment($shipment, $save)
    {
        $fullCancel = true;
        foreach ($shipment->getAllItems() as $item) {
            $this->cancelShipmentItem($item, $save);
            $fullCancel = $fullCancel && ($item->getOrderItem()->isDummy(true) || $item->getQtyShipped()<=0);
        }
        if ($fullCancel) {
            $shipment->setUdropshipStatus(Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_CANCELED);
        }
        $this->_processObjectSave($save, $shipment);
        return $fullCancel;
    }

    public function cancelPo($po, $save, $vendor=false)
    {
        $po->getResource()->beginTransaction();
        try {
            foreach ($po->getShipmentsCollection() as $shipment) {
            	if ($po->getFullCancelFlag()) {
            		$this->revertCompleteShipment($shipment, true);
            		$this->cancelShipment($shipment, $save);
            	} elseif ($po->getNonshippedCancelFlag()) {
            		$this->cancelShipment($shipment, $save);
            	}
            }
            $po->cancel()->save();
            $po->getOrder()->setData('___dummy',1)->save();
            $po->getResource()->commit();
            return true;
        } catch (Exception $e) {
            $po->getResource()->rollBack();
            return false;
        }
    }
    
    public function processLabelRequestError($shipment, $error)
    {
    	if ($shipment->getCancelOnFailedLabelRequestFlag()
    		|| $shipment->getDeleteOnFailedLabelRequestFlag()
    	) {
    		$this->revertCompleteShipment($shipment, true);
        	$this->cancelShipment($shipment, true);
    	}
        if ($shipment->getDeleteOnFailedLabelRequestFlag()) {
        	$shipment->isDeleted(true);
        	$odlSA = Mage::registry('isSecureArea');
        	Mage::unregister('isSecureArea');
        	Mage::register('isSecureArea', true);
			$shipment->delete();
			if (!is_null($odlSA)) Mage::register('isSecureArea', $odlSA);
			else Mage::unregister('isSecureArea');
        }
		if (($udpo = $this->getShipmentPo($shipment))
			&& ($shipment->getCancelOnFailedLabelRequestFlag() || $shipment->getDeleteOnFailedLabelRequestFlag())
		) {
			if ($shipment->getDeleteOnFailedLabelRequestFlag()) {
				if ($shipment->getNewShipmentFlag()) {
					$comment = $this->__('Shipment was not created due to label request error: %s', $error);
				} else {
					$comment = $this->__('Shipment was deleted due to label request error: %s', $error);
				}
			} else {
				$comment = $this->__('Shipment was canceled due to label request error: %s', $error);
			}
			$udpo->addComment($comment, false, $shipment->getCreatedByVendorFlag())->getCommentsCollection()->save();
		}
		return $this;
    }

    public function getAllowedPoStatusesHash($po)
    {
        $confSrc = Mage::getSingleton('udpo/source');
        $poStatuses = $this->getAllowedPoStatuses($po);
        $poStatusesHash = array();
        foreach ($confSrc->setPath('po_statuses')->toOptionHash() as $_k => $_v) {
            if ($_k=='' || in_array($_k, $poStatuses)) {
                $poStatusesHash[$_k] = $_v;
            }
        }
        return $poStatusesHash;
    }
    public function getAllowedPoStatuses($po, $auto=false)
    {
        $confSrc = Mage::getSingleton('udpo/source');
        $allowedStatuses = $confSrc->getNonSecurePoStatuses();
        if ($po->hasShippedItem() && !$po->hasItemToShip() && !$po->hasCanceledItem() && $po->isShipmentsDelivered()) {
            $allowedStatuses = $confSrc->getAllowedPoStatusesForDelivered();
        } elseif ($po->hasShippedItem() && !$po->hasItemToShip() && !$po->hasCanceledItem() && $po->isShipmentsShipped()) {
            $allowedStatuses = $confSrc->getAllowedPoStatusesForShipped($auto);
        } elseif ($po->hasCanceledItem() && !$po->hasItemToShip() && !$po->hasShippedItem()) {
            $allowedStatuses = $confSrc->getAllowedPoStatusesForCanceled();
        } elseif ($po->hasShippedItem() && ($po->hasItemToShip() || $po->hasCanceledItem()) && $po->isShipmentsShipped(false)) {
            $allowedStatuses = $confSrc->getAllowedPoStatusesForPartialShipped();
        } else {
            $allowedStatuses[] = Unirgy_DropshipPo_Model_Source::UDPO_STATUS_CANCELED;
        }
        if (!in_array($po->getUdropshipStatus(), $allowedStatuses)) {
            $allowedStatuses[] = $po->getUdropshipStatus();
        }
        return $allowedStatuses;
    }
    public function getAllowedPoStatusesJson($po, $auto=false)
    {
        return Mage::helper('core')->jsonEncode(array_map('strval', $this->getAllowedPoStatuses($po, $auto)));
    }

    public function processPoStatusSave($po, $status, $save, $vendor=false, $comment='', $isVendorNotified=null, $isVisibleToVendor=null)
    {
        $allowedStatuses   =  $this->getAllowedPoStatuses($po, $vendor===false);
        $isVendorNotified  = is_null($isVendorNotified) ? false : $isVendorNotified;
        $isVisibleToVendor = is_null($isVisibleToVendor) ? true : $isVisibleToVendor;
        if ($po->getUdropshipStatus()!=$status
            && (in_array($status, $allowedStatuses) || $po->getForceStatusChangeFlag())
        ) {
            $oldStatus = $po->getUdropshipStatus();
            Mage::dispatchEvent(
                'udpo_po_status_save_before',
                array('po'=>$po, 'old_status'=>$oldStatus, 'new_status'=>$status)
            );
            $po->setUdropshipStatus($status);
            $_comment = '';
            if ($vendor) {
                $_comment = $this->__("[%s changed PO status from '%s' to '%s']",
                    $vendor->getVendorName(),
                    $this->getPoStatusName($oldStatus),
                    $this->getPoStatusName($status)
                );
            } else {
                $_comment = $this->__("[PO status changed from '%s' to '%s']",
                    $this->getPoStatusName($oldStatus),
                    $this->getPoStatusName($status)
                );
            }
            if (!empty($comment)) {
                $_comment = $comment."\n\n".$_comment;
            }
            $po->addComment($_comment, $isVendorNotified, $isVisibleToVendor);
            if ($isVendorNotified) {
                Mage::helper('udpo')->sendPoCommentNotificationEmail($po, $_comment);
                Mage::helper('udropship')->processQueue();
            }
            $po->getResource()->saveAttribute($po, 'udropship_status');
            $po->saveComments();
            Mage::dispatchEvent(
                'udpo_po_status_save_after',
                array('po'=>$po, 'old_status'=>$oldStatus, 'new_status'=>$status)
            );
            return true;
        } elseif (0 && $vendor) {
            $oldStatus = $po->getUdropshipStatus();
            $po->addComment($this->__("%s tried to change PO status from '%s' to '%s'",
                $vendor->getVendorName(),
                $this->getPoStatusName($oldStatus),
                $this->getPoStatusName($status)
            ), false, true);
            $po->getResource()->saveAttribute($po, 'udropship_status');
            $po->saveComments();
        }
        return false;
    }

    public function getPoStatusName($status)
    {
        $statuses = Mage::getSingleton('udpo/source')->setPath('po_statuses')->toOptionHash();
        return isset($statuses[$status]) ? $statuses[$status] : (in_array($status, $statuses) ? $status : 'Unknown');
    }

    public function getVendorUdpoStatuses()
    {
        if (Mage::getStoreConfig('udropship/vendor/is_restrict_udpo_status')) {
            $udpoStatuses = Mage::getStoreConfig('udropship/vendor/restrict_udpo_status');
            if (!is_array($udpoStatuses)) {
                $udpoStatuses = explode(',', $udpoStatuses);
            }
            return Mage::getSingleton('udpo/source')->setPath('po_statuses')->getOptionLabel($udpoStatuses);
        } else {
            return Mage::getSingleton('udpo/source')->setPath('po_statuses')->toOptionHash();
        }
    }

    public function assignVendorSkus($po)
    {
        Mage::helper('udropship')->assignVendorSkus($po);
        return $this;
    }

    public function unassignVendorSkus($po)
    {
        Mage::helper('udropship')->unassignVendorSkus($po);
        return $this;
    }
    
}
