<?php

/**
 * @package   Bronto\Email
 * @copyright 2011-2012 Bronto Software, Inc.
 */
class Bronto_Email_Block_Adminhtml_System_Config_Templates_Field extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $realpath  = str_replace('-', '/', str_replace('bronto_email_templates_', '', $element->getId()));
        $realValue = Mage::helper('bronto_email')->getAdminScopedConfig($realpath);
        $element->setValue($realValue);
        $element->setPath($realpath);

        return parent::render($element);
    }

}
