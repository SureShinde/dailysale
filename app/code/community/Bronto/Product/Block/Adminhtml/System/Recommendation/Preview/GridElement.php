<?php

class Bronto_Product_Block_Adminhtml_System_Recommendation_Preview_GridElement extends Varien_Data_Form_Element_Abstract
{
    private $_block;

    /**
     * Wraps the block to return the html
     * @param Mage_Adminhtml_Block_Abstract
     */
    public function __construct($block)
    {
        $this->_block = $block;
    }

    /**
     * Returns the html of the wrapped block
     * @return string
     */
    public function toHtml()
    {
        return $this->_block->toHtml();
    }
}
