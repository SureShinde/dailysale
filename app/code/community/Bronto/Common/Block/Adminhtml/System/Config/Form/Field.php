<?php

/**
 * @package   Bronto\Common
 * @copyright 2011-2012 Bronto Software, Inc.
 */
class Bronto_Common_Block_Adminhtml_System_Config_Form_Field extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Override method to output our custom HTML with JavaScript
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return String
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        if (!extension_loaded('soap') || !extension_loaded('openssl')) {
            $element->setDisabled('disabled')->setValue(0);
        } else {
            // Get Config Link
            $configLink = Mage::helper('bronto_common')->getScopeUrl('/system_config/edit/section/bronto');

            if (!Mage::helper('bronto_verify/apitoken')->getStatus()) {
                if (trim($element->getLabel()) === 'Enable Module') {
                    $element->setDisabled('disabled')->setValue(0);
                    $link = '<a href="' . $configLink . '">Fix it Here</a>';
                    $element->setComment('<span style="color:red;font-weight: bold">A valid Bronto API key is required. ' . $link . '</span>');
                }
            } else if (!Mage::helper('bronto_common')->isEnabled()) {
                if (trim($element->getLabel()) === 'Enable Module') {
                    $element->setDisabled('disabled')->setValue(0);
                    $link = '<a href="' . $configLink . '">Enable It Here</a>';
                    $element->setComment('<span style="color:red;font-weight: bold">The Bronto Extension for Magento is not enabled. ' . $link . '</span>');
                }
            } else if (!Mage::helper('bronto_common/support')->isRegistered()) {
                if (trim($element->getLabel()) !== 'Enable Module' || !$element->getValue()) {
                    $link = '<a href="' . $configLink . '#bronto_support-head">Register Here</a>';
                    $element->setDisabled('disabled');
                    $element->setComment('<span style="color:red;font-weight:bold">Extension registration is required. ' . $link . '</span>');
                }
            }
        }

        return parent::_getElementHtml($element);
    }
}
