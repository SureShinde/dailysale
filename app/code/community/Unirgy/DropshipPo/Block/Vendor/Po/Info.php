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

class Unirgy_DropshipPo_Block_Vendor_Po_Info extends Mage_Sales_Block_Items_Abstract
{
    protected function _construct()
    {
        Mage_Core_Block_Template::_construct();
        $this->addItemRender('default', 'sales/order_item_renderer_default', 'sales/order/shipment/items/renderer/default.phtml');
    }

    public function getVendor()
    {
        return Mage::getSingleton('udropship/session')->getVendor();
    }
    public function isShowTotals()
    {
        return Mage::helper('udropship')->getVendorFallbackFlagField(
            $this->getVendor(),
            'portal_show_totals', 'udropship/vendor/portal_show_totals'
        );
    }

    public function getPo()
    {
        if (!$this->hasData('po')) {
            $id = (int)$this->getRequest()->getParam('id');
            $po = Mage::getModel('udpo/po')->load($id);
            $this->setData('po', $po);
            Mage::helper('udropship')->assignVendorSkus($po);
            Mage::helper('udropship/item')->hideVendorIdOption($po);
            if ($this->isShowTotals()) {
                Mage::helper('udropship/item')->initPoTotals($po);
            }
        }
        return $this->getData('po');
    }

    public function getRemainingWeight()
    {
        $weight = 0;
        $parentItems = array();
        foreach ($this->getPo()->getAllItems() as $item) {
            $orderItem = $item->getOrderItem();

            $children = $orderItem->getChildrenItems() ? $orderItem->getChildrenItems() : $orderItem->getChildren();
            if ($children) {
                $parentItems[$orderItem->getId()] = $item;
            }
            $__qty = $item->getQtyToShip();
            if ($orderItem->isDummy(true)) {
                if (($_parentItem = $orderItem->getParentItem())) {
                    $__qty = $orderItem->getQtyOrdered()/$_parentItem->getQtyOrdered();
                    if (@$parentItems[$_parentItem->getId()]) {
                        $__qty *= $parentItems[$_parentItem->getId()]->getQty();
                    }
                } else {
                    $__qty = max(1,$item->getQty());
                }
            }

            if ($orderItem->getParentItem()) {
                $weightType = $orderItem->getParentItem()->getProductOptionByCode('weight_type');
                if (null !== $weightType && !$weightType) {
                    $weight += $item->getWeight()*$__qty;
                }
            } else {
                $weightType = $orderItem->getProductOptionByCode('weight_type');
                if (null === $weightType || $weightType) {
                    $weight += $item->getWeight()*$__qty;
                }
            }
        }
        return $weight;
    }
    
    public function getRemainingShippingAmount()
    {
        $sa = 0;
        $po = $this->getPo();
        foreach ($po->getShipmentsCollection() as $_s) {
            $sa += $_s->getBaseShippingAmount();
        }
        return max(0,$po->getBaseShippingAmount()-$sa);
    }

    public function getRemainingValue()
    {
        $value = 0;
        foreach ($this->getPo()->getAllItems() as $item) {
            $value += $item->getPrice()*$item->getQtyToShip();
        }
        return $value;
    }

    public function getPoItemsJson($po)
    {
        $items = array();
        foreach ($po->getAllItems() as $item) {
            if ($item->getOrderItem()->isDummy(true)) continue;
            $items[$item->getId()] = array(
                'product_url' => $item->getProduct() && $item->getProduct()->getProductUrl() ? $item->getProduct()->getProductUrl() : '',
                'order_item_id'=> $item->getOrderItem()->getId(),
                'item_id'      => $item->getId(),
                'is_dummy'     => (int)$item->getOrderItem()->isDummy(true),
                'is_virtual'   => (int)$item->getIsVirtual(),
                'qty_shipped'  => (int)$item->getQtyShipped(),
                'qty_to_ship'  => (int)$item->getQtyToShip(),
                'qty_canceled' => (int)$item->getQtyCanceled(),
                'weight' => $item->getWeight(),
                'price' => $item->getPrice(),
            );
        }
        return Zend_Json::encode($items);
    }

    public function getCarriers()
    {
        $carriers = array();
        $carrierInstances = Mage::getSingleton('shipping/config')->getAllCarriers(
            $this->getPo()->getStoreId()
        );
        $carriers[''] = Mage::helper('sales')->__('* Use PO carrier *');
        $carriers['custom'] = Mage::helper('sales')->__('Custom Value');
        foreach ($carrierInstances as $code => $carrier) {
            if ($carrier->isTrackingAvailable()) {
                $carriers[$code] = $carrier->getConfigData('title');
            }
        }
        return $carriers;
    }

    public function getCarrierTitle($code)
    {
        if ($carrier = Mage::getSingleton('shipping/config')->getCarrierInstance($code)) {
            return $carrier->getConfigData('title');
        }
        else {
            return Mage::helper('sales')->__('Custom Value');
        }
        return false;
    }
    
}
