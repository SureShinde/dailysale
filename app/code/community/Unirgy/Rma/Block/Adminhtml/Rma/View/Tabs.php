<?php

class Unirgy_Rma_Block_Adminhtml_Rma_View_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function getOrder()
    {
        if ($this->hasOrder()) {
            return $this->getData('order');
        }
        if (Mage::registry('current_order')) {
            return Mage::registry('current_order');
        }
        if (Mage::registry('order')) {
            return Mage::registry('order');
        }
        Mage::throwException(Mage::helper('udropship')->__('Cannot get the order instance.'));
    }

    public function __construct()
    {
        parent::__construct();
        $this->setId('urma_rma_view_tabs');
        $this->setDestElementId('urma_rma_view');
        $this->setTitle(Mage::helper('udropship')->__('Return View'));
    }

}