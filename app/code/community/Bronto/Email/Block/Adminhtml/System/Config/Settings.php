<?php

/**
 * @package     Bronto\Reminder
 * @copyright   2011-2012 Bronto Software, Inc.
 */
class Bronto_Email_Block_Adminhtml_System_Config_Settings
    extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    /**
     * Return header comment part of html for fieldset
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $url = Mage::helper('adminhtml')->getUrl('*/system_email_template');
        $element->setComment("Additional configuration located at: <strong>System &rsaquo; <a href=\"{$url}\">Transactional Emails</a></strong><br/><br/>");

        return parent::render($element);
    }
}
