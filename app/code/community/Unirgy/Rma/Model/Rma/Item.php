<?php

class Unirgy_Rma_Model_Rma_Item extends Mage_Core_Model_Abstract
{
    protected $_eventPrefix = 'urma_rma_item';
    protected $_eventObject = 'rma_item';

    protected $_rma = null;
    protected $_orderItem = null;

    function _construct()
    {
        $this->_init('urma/rma_item');
    }

    public function setRma(Unirgy_Rma_Model_Rma $rma)
    {
        $this->_rma = $rma;
        return $this;
    }

    public function getRma()
    {
        return $this->_rma;
    }

    public function setOrderItem(Mage_Sales_Model_Order_Item $item)
    {
        $this->_orderItem = $item;
        $this->setOrderItemId($item->getId());
        return $this;
    }

    public function getOrderItem()
    {
        if (is_null($this->_orderItem)) {
            if ($this->getRma()
            	&& ($orderItem = Mage::helper('udropship')->getOrderItemById($this->getRma()->getOrder(), $this->getOrderItemId()))
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
        if ($qty <= $this->getOrderItem()->getQtyOrdered() || $this->getOrderItem()->isDummy(true)) {
            $this->setData('qty', $qty);
        }
        else {
            Mage::throwException(
                Mage::helper('udropship')->__('Invalid qty to create rma for item "%s"', $this->getName())
            );
        }
        return $this;
    }

    public function getItemConditionName()
    {
        return Mage::helper('urma')->getItemConditionTitle($this->getItemCondition());
    }

    public function register()
    {
        return $this;
    }
    
    protected function _beforeSave()
    {
        parent::_beforeSave();

        if (!$this->getParentId() && $this->getRma()) {
            $this->setParentId($this->getRma()->getId());
        }

        return $this;
    }

}
