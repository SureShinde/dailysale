<?php

/**
 * @package     Bronto\Customer
 * @copyright   2011-2012 Bronto Software, Inc.
 */
class Bronto_Customer_Block_Adminhtml_Widget_Button_Sync extends Mage_Adminhtml_Block_Widget_Button
{
    /**
     * Internal constructor not depended on params. Can be used for object initialization
     */
    protected function _construct()
    {
        $this->setLabel($this->__('Sync Customers to Queue'));
        $this->setOnClick("deleteConfirm('This will ensure all Magento customers are in the queue to import into Bronto\\n\\nThis is meant to be used when the customer count does not match the total number of customers in the Magento admin\\n\\nWould you like to continue?', '" . Mage::helper('bronto_customer')->getScopeUrl('*/customer/sync') . "'); return false;");
        $this->setClass('save bronto-cron-sync');

        if (!Mage::helper('bronto_customer')->isModuleActive()) {
            $this->setDisabled(true)->setClass('disabled');
        }
    }
}
