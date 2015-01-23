<?php

class Bronto_Common_Block_Adminhtml_System_Config_Form_Field_Support extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * Override for disabling support information until API token is set
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        if (!Mage::helper('bronto_verify/apitoken')->getStatus()) {
            $element->setDisabled('disabled');
        }

        return parent::_getElementHtml($element);
    }
}
