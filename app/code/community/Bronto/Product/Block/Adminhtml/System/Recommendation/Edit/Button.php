<?php

class Bronto_Product_Block_Adminhtml_System_Recommendation_Edit_Button extends Mage_Adminhtml_Block_Template implements Varien_Data_Form_Element_Renderer_Interface
{
    protected $_element;
    protected $_displayLabel = true;

    /**
     * @see parent
     */
    protected function _construct()
    {
        $this->setTemplate('bronto/product/recommendation/button.phtml');
    }

    /**
     * Sets whether or not the content should be displayed in label
     *
     * @param bool $display
     * @return Bronto_Product_Block_Adminhtml_System_Recommendation_Edit_Button
     */
    public function setDisplayInLabel($display)
    {
        $this->_displayLabel = $display;
        return $this;
    }

    /**
     * Should this button display in a label
     *
     * @return bool
     */
    public function isDisplayInLabel()
    {
        return $this->_displayLabel;
    }

    /**
     * Gets the wrapped element
     *
     * @return Varien_Data_Form_Element_Abstract
     */
    public function getElement()
    {
        return $this->_element;
    }

    /**
     * @see parent
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $this->_element = $element;
        return $this->toHtml();
    }
}
