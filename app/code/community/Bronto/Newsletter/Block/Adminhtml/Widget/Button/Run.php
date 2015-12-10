<?php

/**
 * @package     Bronto\Newsletter
 * @copyright   2011-2012 Bronto Software, Inc.
 */
class Bronto_Newsletter_Block_Adminhtml_Widget_Button_Run extends Mage_Adminhtml_Block_Widget_Button
{
    /**
     * Internal constructor not depended on params. Can be used for object initialization
     */
    protected function _construct()
    {
        $this->setLabel('Run Now');
        $this->setOnClick("setLocation('" . Mage::helper('bronto_newsletter')->getScopeUrl('*/newsletter/run') . "'); return false;");
        $this->setClass('bronto-cron-run');

        // Check to see if this module meets requirements
        if (!Mage::helper('bronto_newsletter')->isModuleActive()) {
            $this->setDisabled(true)->setClass('disabled');
        }
    }
}
