<?php

class Bronto_Product_Block_Adminhtml_System_Config_Information extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    /**
     * Returns the description for the module in a read-only html block
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $url = Mage::helper('adminhtml')->getUrl('*/recommendations');
        $element->setComment(str_replace('{url}', $url, $element->getComment()));
        return parent::render($element);
    }
}
