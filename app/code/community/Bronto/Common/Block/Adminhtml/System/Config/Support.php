<?php

class Bronto_Common_Block_Adminhtml_System_Config_Support extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{

    /**
     * Prepare layout with help hover
     *
     * @return Bronto_Common_Block_Adminhtml_System_Config_Support
     */
    protected function _prepareLayout()
    {
        if ($head = $this->getLayout()->getBlock('head')) {
            $head->addCss('bronto/cron.css');
        }

        return parent::_prepareLayout();
    }

    /**
     * Collapsed or expanded fieldset when page loaded?
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return bool
     */
    protected function _getCollapseState($element)
    {
        $helper = Mage::helper('bronto_common/support');

        if (!$helper->isRegistered()) {
            return 1;
        }

        return parent::_getCollapseState($element);
    }
}
