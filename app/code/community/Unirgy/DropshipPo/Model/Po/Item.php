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

class Unirgy_DropshipPo_Model_Po_Item extends Mage_Core_Model_Abstract
{
    protected $_eventPrefix = 'udpo_po_item';
    protected $_eventObject = 'po_item';

    protected $_po = null;
    protected $_orderItem = null;
    protected $_stockPoItem = null;

    function _construct()
    {
        $this->_init('udpo/po_item');
    }

    public function setPo(Unirgy_DropshipPo_Model_Po $po)
    {
        $this->_po = $po;
        return $this;
    }

    public function getPo()
    {
        return $this->_po;
    }

    public function setOrderItem(Mage_Sales_Model_Order_Item $item)
    {
        $this->_orderItem = $item;
        $this->setOrderItemId($item->getId());
        return $this;
    }

    public function setStockPoItem(Unirgy_DropshipStockPo_Model_Po_Item $item)
    {
        $this->_stockPoItem = $item;
        $this->setUstockpoItemId($item->getId());
        return $this;
    }

    public function getOrderItem()
    {
        if (is_null($this->_orderItem)) {
            if ($this->getPo()
            	&& ($orderItem = Mage::helper('udropship')->getOrderItemById($this->getPo()->getOrder(), $this->getOrderItemId()))
            ) {
                $this->_orderItem = $orderItem;
            }
            else {
                $this->_orderItem = Mage::getModel('sales/order_item')
                    ->load($this->getOrderItemId());
            }
        }
        return $this->_orderItem;
    }

    public function getStockPoItem()
    {
        if (is_null($this->_stockPoItem)) {
            if ($this->getPo()
                && ($stockPo = $this->getPo()->getStockPo())
            	&& ($stockPoItem = $stockPo->getItemById($this->getUstockpoItemId()))
            ) {
                $this->_stockPoItem = $stockPoItem;
            }
            else {
                $this->_stockPoItem = Mage::getModel('ustockpo/po_item')
                    ->load($this->getUstockpoItemId());
            }
            $this->_stockPoItem->setUdpoItem($this);
        }
        return $this->_stockPoItem;
    }
    
    public function getQtyToShip()
    {
        if ($this->getOrderItem()->isDummy(true)) {
            return 0;
        }
        return max(0, min($this->getOrderItem()->getQtyToShip(), $this->getQty()-$this->getQtyShipped()-$this->getQtyCanceled()));
    }
    
    public function getQtyToInvoice()
    {
        if ($this->getOrderItem()->isDummy()) {
            return 0;
        }
        return max(0, min($this->getOrderItem()->getQtyToInvoice(), $this->getQty()-$this->getQtyInvoiced()-$this->getQtyCanceled()));
    }
    
    public function getQtyToCancel()
    {
        //return min($this->getQtyToShip(), $this->getQtyToInvoice());
        return $this->getQtyToShip();
    }

    public function setQty($qty)
    {
        if ($this->getOrderItem()->getIsQtyDecimal()) {
            $qty = (float) $qty;
        }
        else {
            $qty = (int) $qty;
        }
        $qty = $qty > 0 ? $qty : 0;
        /**
         * Check qty availability
         */
        if ($qty <= Mage::helper('udpo')->getOrderItemQtyToUdpo($this->getOrderItem()) || $this->getOrderItem()->isDummy(true)) {
            $this->setData('qty', $qty);
        }
        else {
            Mage::throwException(
                Mage::helper('sales')->__('Invalid qty to create purchase order for item "%s"', $this->getName())
            );
        }
        return $this;
    }

    public function register()
    {
        $this->getOrderItem()->setQtyUdpo(
            $this->getOrderItem()->getQtyUdpo()+$this->getQty()
        );
        return $this;
    }
    
    public function cancel()
    {
        $this->getOrderItem()->setQtyUdpo(
            $this->getOrderItem()->getQtyUdpo()-$this->getQtyToCancel()
        );
        $this->getPo()->setCurrentlyCanceledQty($this->getPo()->getCurrentlyCanceledQty()+$this->getQtyToCancel());
        $this->setCurrentlyCanceledQty($this->getQtyToCancel());
        $this->setQtyCanceled($this->getQtyCanceled() + $this->getQtyToCancel());
        return $this;
    }

    public function getUdropshipVendor()
    {
        return $this->getPo()->getUdropshipVendor();
    }

    protected function _beforeSave()
    {
        parent::_beforeSave();

        if (!$this->getParentId() && $this->getPo()) {
            $this->setParentId($this->getPo()->getId());
        }

        return $this;
    }

}
