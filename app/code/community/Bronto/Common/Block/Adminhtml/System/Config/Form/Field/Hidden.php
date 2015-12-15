<?php

/**
 * @package   Bronto\Common
 * @copyright 2011-2012 Bronto Software, Inc.
 */
class Bronto_Common_Block_Adminhtml_System_Config_Form_Field_Hidden extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        if (!extension_loaded('soap') || !extension_loaded('openssl') || !Mage::helper('bronto_common')->getApiToken()) {
            return null;
        }

        return parent::render($element);
    }
}
