<?php

/**
 * @package   Bronto\Order
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Order_Block_Adminhtml_Widget_Button_Reset extends Mage_Adminhtml_Block_Widget_Button
{
    /**
     * Internal constructor not depended on params. Can be used for object initialization
     */
    protected function _construct()
    {
        $this->setLabel('Reset All Orders');
        $this->setOnClick("deleteConfirm('This will mark all orders as not-imported and will cause the importer to re-process each order again.\\n\\nAre you sure you want to do this?', '" . Mage::helper('bronto_order')->getScopeUrl('*/order/reset') . "'); return false;");
        $this->setClass('delete bronto-cron-reset');

        if (!Mage::helper('bronto_order')->isModuleActive() || (!Mage::helper('bronto_order')->isDebugEnabled())) {
            $this->setDisabled(true)->setClass('disabled');
            if (!Mage::helper('bronto_customer')->isDebugEnabled()) {
                $this->setTitle('Enable Debug in the General section to ' . $this->getLabel() . '.');
            }
        }
    }
}
