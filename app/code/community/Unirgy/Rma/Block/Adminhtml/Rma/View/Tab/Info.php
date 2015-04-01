<?php

class Unirgy_Rma_Block_Adminhtml_Rma_View_Tab_Info
    extends Mage_Adminhtml_Block_Sales_Order_Abstract
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    public function getRma()
    {
        return Mage::registry('current_rma');
    }
    
    public function getOrder()
    {
        return Mage::registry('current_order');
    }

    public function getSource()
    {
        return $this->getRma();
    }
    
    /**
     * ######################## TAB settings #################################
     */
    public function getTabLabel()
    {
        return Mage::helper('sales')->__('Information');
    }

    public function getTabTitle()
    {
        return Mage::helper('sales')->__('Order Information');
    }

    public function canShowTab()
    {
        return true;
    }

    public function isHidden()
    {
        return false;
    }
}