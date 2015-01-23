<?php

/**
 * @package   Bronto\Order
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Order_Block_Adminhtml_Widget_Button_Sync extends Mage_Adminhtml_Block_Widget_Button
{
    /**
     * Internal constructor not depended on params. Can be used for object initialization
     */
    protected function _construct()
    {
        $this->setLabel($this->__('Sync Orders to Queue'));
        $this->setOnClick("deleteConfirm('This will ensure all Magento orders are in the queue to import into Bronto\\n\\nThis is meant to be used when the order count does not match the total number of orders in the Magento admin\\n\\nWould you like to continue?', '" . Mage::helper('bronto_order')->getScopeUrl('*/order/sync') . "'); return false;");
        $this->setClass('save bronto-cron-sync');

        if (!Mage::helper('bronto_order')->isModuleActive()) {
            $this->setDisabled(true)->setClass('disabled');
        }
    }
}
