<?php

class Unirgy_DropshipMicrositePro_Block_Vendor_Register_Renderer_Fieldset extends Mage_Core_Block_Template implements Varien_Data_Form_Element_Renderer_Interface
{
    protected $_element;

    protected function _construct()
    {
        $this->setTemplate('unirgy/udmspro/vendor/register/renderer/fieldset.phtml');
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

    public function getChildElementHtml($elem)
    {
        return $this->getElement()->getForm()->getElement($elem)->toHtml();
    }

    protected $_jsBlockForChild;
    public function getChildElementJs($elem)
    {
        if (null === $this->_jsBlockForChild) {
            $this->_jsBlockForChild = Mage::getSingleton('core/layout')->createBlock('udmspro/vendor_register_renderer_fieldsetElementJs');
        }
        return $this->_jsBlockForChild->render($this->getElement()->getForm()->getElement($elem));
    }
}