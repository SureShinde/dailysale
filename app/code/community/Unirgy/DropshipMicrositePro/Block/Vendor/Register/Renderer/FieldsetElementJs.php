<?php

class Unirgy_DropshipMicrositePro_Block_Vendor_Register_Renderer_FieldsetElementJs extends Mage_Core_Block_Template
{
    protected $_element;

    protected function _construct()
    {
        $this->setTemplate('unirgy/udmspro/vendor/register/renderer/fieldset_element_js.phtml');
    }

    public function getElement()
    {
        return $this->_element;
    }

    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $this->_element = $element;
        return $this->toHtml();
    }

    public function getElementHtml()
    {
        $html = $this->_element->getElementHtml();
        return $html;
    }
}