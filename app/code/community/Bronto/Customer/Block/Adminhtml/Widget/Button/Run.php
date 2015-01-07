<?php

/**
 * @package     Bronto\Customer
 * @copyright   2011-2012 Bronto Software, Inc.
 */
class Bronto_Customer_Block_Adminhtml_Widget_Button_Run extends Mage_Adminhtml_Block_Widget_Button
{
    /**
     * Internal constructor not depended on params. Can be used for object initialization
     */
    protected function _construct()
    {
        $this->setLabel('Run Now');
        $this->setOnClick("setLocation('" . Mage::helper('bronto_customer')->getScopeUrl('*/customer/run') . "'); return false;");
        $this->setClass('bronto-cron-run');

        if (!Mage::helper('bronto_customer')->isModuleActive()) {
            $this->setDisabled(true)->setClass('disabled');
        }
    }
}
