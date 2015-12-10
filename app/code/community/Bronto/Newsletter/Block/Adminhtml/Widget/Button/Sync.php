<?php

/**
 * @package     Bronto\Newsletter
 * @copyright   2011-2012 Bronto Software, Inc.
 */
class Bronto_Newsletter_Block_Adminhtml_Widget_Button_Sync extends Mage_Adminhtml_Block_Widget_Button
{
    /**
     * Internal constructor not depended on params. Can be used for object initialization
     */
    protected function _construct()
    {
        $this->setLabel($this->__('Sync Subscribers to Queue'));
        $this->setOnClick("deleteConfirm('This will ensure all Magento subscribers are in the queue to import into Bronto\\n\\nThis is meant to be used when the subscriber count does not match the total number of subscriber in the Magento admin\\n\\nWould you like to continue?', '" . Mage::helper('bronto_newsletter')->getScopeUrl('*/newsletter/sync') . "'); return false;");
        $this->setClass('save bronto-cron-sync');

        if (!Mage::helper('bronto_newsletter')->isModuleActive()) {
            $this->setDisabled(true)->setClass('disabled');
        }
    }
}
