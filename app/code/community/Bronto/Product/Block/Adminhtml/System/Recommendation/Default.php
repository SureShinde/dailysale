<?php

class Bronto_Product_Block_Adminhtml_System_Recommendation_Default extends Mage_Adminhtml_Block_Template
{
    const DEFAULT_TEMPLATE = 'bronto/product/recommendation/default.phtml';

    /**
     * @see parent
     */
    protected function _construct()
    {
        $this->setTemplate(self::DEFAULT_TEMPLATE);
    }
}
