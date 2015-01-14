<?php

/**
 * @package     Bronto\Newsletter
 * @copyright   2011-2012 Bronto Software, Inc.
 */
class Bronto_Newsletter_Block_Adminhtml_Widget_Button_Reset extends Mage_Adminhtml_Block_Widget_Button
{
    /**
     * Internal constructor not depended on params. Can be used for object initialization
     */
    protected function _construct()
    {
        $this->setLabel('Reset All Subscribers');
        $this->setOnClick("deleteConfirm('This will mark all subscribers as not-imported and will cause the importer to re-process each subscriber again.\\n\\nAre you sure you want to do this?', '" . Mage::helper('bronto_newsletter')->getScopeUrl('*/newsletter/reset') . "'); return false;");
        $this->setClass('delete  bronto-cron-reset');

        if (!Mage::helper('bronto_newsletter')->isModuleActive() || (!Mage::helper('bronto_newsletter')->isDebugEnabled())) {
            $this->setDisabled(true)->setClass('disabled');
            if (!Mage::helper('bronto_customer')->isDebugEnabled()) {
                $this->setTitle('Enable Debug in the General section to ' . $this->getLabel() . '.');
            }
        }
    }
}
