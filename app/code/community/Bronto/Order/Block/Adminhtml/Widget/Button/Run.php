<?php

/**
 * @package   Bronto\Order
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Order_Block_Adminhtml_Widget_Button_Run
    extends Mage_Adminhtml_Block_Widget_Button
{
    /**
     * Internal constructor not depended on params. Can be used for object initialization
     */
    protected function _construct()
    {
        $this->setLabel('Run Now');
        $this->setOnClick(
            "setLocation('" . Mage::helper('bronto_order')->getScopeUrl('*/order/run') . "'); return false;"
        );
        $this->setClass('bronto-cron-run');

        // Check to see if this module meets requirements
        if (!Mage::helper('bronto_order')->isModuleActive()) {
            $this->setDisabled(true)->setClass('disabled');
        }
    }
}
