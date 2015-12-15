<?php

class Unirgy_Rma_Block_Email_Rma_Items extends Mage_Sales_Block_Items_Abstract
{
    protected function _prepareItem(Mage_Core_Block_Abstract $renderer)
    {
        $renderer->getItem()->setOrder($this->getOrder());
        $renderer->getItem()->setSource($this->getPo());
    }
}