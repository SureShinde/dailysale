<?php

/**
 * @package   Bronto\Common
 * @copyright 2011-2012 Bronto Software, Inc.
 */
class Bronto_Common_Block_Adminhtml_System_Config_Form_Field_List extends Bronto_Common_Block_Adminhtml_System_Config_Form_Field_Hidden
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
        if (!Mage::app()->isSingleStoreMode()) {
            $request = Mage::app()->getRequest();
            if (!$request->getParam('store') && !$request->getParam('website') && !$request->getParam('group')) {
                $element->setCanUseDefaultValue(false)
                    ->setDisabled('disabled')
                    ->setValue(null);
            }
        }

        return parent::_getElementHtml($element);
    }

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        if (!Mage::app()->isSingleStoreMode()) {
            $request = Mage::app()->getRequest();
            if (!$request->getParam('store') && !$request->getParam('website') && !$request->getParam('group')) {
                return null;
            }
        }

        return parent::render($element);
    }
}
