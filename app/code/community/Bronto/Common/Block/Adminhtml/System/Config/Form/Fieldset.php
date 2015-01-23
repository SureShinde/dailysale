<?php

class Bronto_Common_Block_Adminhtml_System_Config_Form_Fieldset extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{

    /**
     * Collapsed or expanded fieldset when page loaded?
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return bool
     */
    protected function _getCollapseState($element)
    {
        $user  = Mage::getSingleton('admin/session')->getUser();
        $extra = $user->getExtra();
        if (!isset($extra['configState'][$element->getId()])) {
            return 1;
        }

        return parent::_getCollapseState($element);
    }
}
