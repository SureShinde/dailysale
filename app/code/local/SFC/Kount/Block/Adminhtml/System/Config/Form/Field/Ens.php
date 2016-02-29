<?php
// @codingStandardsIgnoreStart
/**
 * StoreFront Consulting Kount Magento Extension
 *
 * PHP version 5
 *
 * @category  SFC
 * @package   SFC_Kount
 * @copyright 2009-2015 StoreFront Consulting, Inc. All Rights Reserved.
 *
 */
// @codingStandardsIgnoreEnd

class SFC_Kount_Block_Adminhtml_System_Config_Form_Field_Ens extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Override method to output our custom HTML with JavaScript
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return String
     */
    // @codingStandardsIgnoreStart
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        // Build url
        $url = $this->getUrl('kount/ens', array('_forced_secure' => true));
        // Strip everything after key string
        $keyStr = '/ens/';
        $ensPos = strpos($url, $keyStr);
        if($ensPos !== FALSE) {
            $url = substr($url, 0, $ensPos + strlen($keyStr));
        }

        // Return the processed url
        return $url;
    }
    // @codingStandardsIgnoreEnd
}