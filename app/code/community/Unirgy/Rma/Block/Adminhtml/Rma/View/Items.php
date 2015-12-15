<?php

class Unirgy_Rma_Block_Adminhtml_Rma_View_Items extends Mage_Adminhtml_Block_Sales_Items_Abstract
{
    public function getRma()
    {
        return Mage::registry('current_rma');
    }

    public function getOrder()
    {
        return $this->getRma()->getOrder();
    }

    public function getSource()
    {
        return $this->getRma();
    }
}