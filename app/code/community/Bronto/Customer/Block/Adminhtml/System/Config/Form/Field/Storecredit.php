<?php

/**
 * @package   Bronto\Common
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Customer_Block_Adminhtml_System_Config_Form_Field_Storecredit
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Get element ID of the dependent field's parent row
     *
     * @param object $element
     *
     * @return String
     */
    protected function _getRowElementId($element)
    {
        return 'row_' . $element->getId();
    }

    /**
     * Override method to render element only if module enabled
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return String
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        // If Reward Points Module is installed
        if (!Mage::helper('bronto_common')->isModuleInstalled('Enterprise_CustomerBalance')) {
            return '';
        }

        return parent::render($element);
    }
}






