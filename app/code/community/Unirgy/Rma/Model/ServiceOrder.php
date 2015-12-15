<?php

class Unirgy_Rma_Model_ServiceOrder extends Mage_Sales_Model_Service_Order
{
    public function __construct(Mage_Sales_Model_Order $order)
    {
        $this->_order       = $order;
        $this->_convertor   = Mage::getModel('urma/convertOrder');
    }
    public function prepareRma($qtys = array())
    {
        $hlpr = Mage::helper('urma');
        $totalQty = 0;
        $rma = $this->_convertor->toRma($this->_order);
        foreach ($this->_order->getAllItems() as $orderItem) {
            if (!$this->_canRmaItem($orderItem, $qtys)) {
                continue;
            }

            $item = $this->_convertor->itemToRmaItem($orderItem);
            if ($orderItem->isDummy(true)) {
                $qty = 1;
            } else {
                if (isset($qtys[$orderItem->getId()])) {
                    $qty = min($qtys[$orderItem->getId()], $hlpr->getOrderItemQtyToUrma($orderItem));
                } elseif (!count($qtys)) {
                    $qty = $hlpr->getOrderItemQtyToUrma($orderItem);
                } else {
                    continue;
                }
            }

            $totalQty += $qty;
            $item->setQty($qty);
            $rma->addItem($item);
        }
        $rma->setTotalQty($totalQty);
        return $rma;
    }
    public function prepareRmaForSave($qtys = array(), $conditions=array())
    {
        $hlpr = Mage::helper('urma');
        $totalQtys = array();
        $rmaItems = array();
        foreach ($this->_order->getAllItems() as $orderItem) {
            if (!$this->_canRmaItem($orderItem, $qtys)) {
                continue;
            }

            $item = $this->_convertor->itemToRmaItem($orderItem);
            if ($orderItem->isDummy(true)) {
                $qty = 1;
            } else {
                if (isset($qtys[$orderItem->getId()])) {
                    $qty = min($qtys[$orderItem->getId()], $hlpr->getOrderItemQtyToUrma($orderItem));
                } elseif (!count($qtys)) {
                    $qty = $hlpr->getOrderItemQtyToUrma($orderItem);
                } else {
                    continue;
                }
            }
            if ($qty<=0) continue;
            $vId = $orderItem->getUdropshipVendor();

            $rmaItems[$vId][] = $item;

            if (empty($totalQtys[$vId])) {
                $totalQtys[$vId] = 0;
            }
            $totalQtys[$vId] += $qty;

            $item->setQty($qty);
            $orderItem->setQtyUrma(
                $orderItem->getQtyUrma()+$item->getQty()
            );

            $item->setItemCondition(@$conditions[$orderItem->getId()]);

        }
        if (empty($rmaItems)) {
            Mage::throwException(
                Mage::getStoreConfig('urma/message/customer_no_items')
            );
        }
        $rmas = array();
        foreach ($rmaItems as $vId=>$items) {
            if (empty($items)) continue;
            $shipment = null;
            foreach ($this->_order->getShipmentsCollection() as $_shipment) {
                if ($_shipment->getUdropshipVendor()==$vId) {
                    $shipment = $_shipment;
                    break;
                }
            }
            if (null == $shipment) continue;
            $rma = $this->_convertor->toRma($this->_order);
            $rma->setUdropshipVendor($vId);
            $rma->setUdropshipMethod($shipment->getUdropshipMethod());
            $rma->setUdropshipMethodDescription($shipment->getUdropshipMethodDescription());
            $rma->setTotalQty($totalQtys[$vId]);
            $rma->setShipmentId($shipment->getId());
            $rma->setShipmentIncrementId($shipment->getIncrementId());
            if (Mage::helper('udropship')->isUdpoActive() && ($po=Mage::helper('udpo')->getShipmentPo($shipment))) {
                $rma->setUdpoId($po->getId());
                $rma->setUdpoIncrementId($po->getIncrementId());
            }
            $rmas[$vId] = $rma;
            foreach ($items as $item) {
                $rma->addItem($item);
            }

        }
        foreach ($rmas as $rma) {
            //Mage::helper('urma')->addVendorSkus($rma);
            Mage::helper('udropship')->addVendorSkus($rma);
        }
        return $rmas;
    }

    protected function _canRmaItem($item, $qtys=array())
    {
        $hlpr = Mage::helper('urma');
        if ($item->isDummy(true)) {
            if ($item->getHasChildren()) {
                if ($item->isShipSeparately()) {
                    return true;
                }
                foreach ($item->getChildrenItems() as $child) {
                    if ($child->getIsVirtual()) {
                        continue;
                    }
                    if (empty($qtys)) {
                        if ($hlpr->getOrderItemQtyToUrma($child) > 0) {
                            return true;
                        }
                    } else {
                        if (isset($qtys[$child->getId()]) && $qtys[$child->getId()] > 0) {
                            return true;
                        }
                    }
                }
                return false;
            } else if($item->getParentItem()) {
                $parent = $item->getParentItem();
                if (empty($qtys)) {
                    return $hlpr->getOrderItemQtyToUrma($parent) > 0;
                } else {
                    return isset($qtys[$parent->getId()]) && $qtys[$parent->getId()] > 0;
                }
            }
        } else {
            return $hlpr->getOrderItemQtyToUrma($item)>0;
        }
    }
}