<?php

class Bronto_Customer_Block_Adminhtml_Widget_Button_Mark extends Mage_Adminhtml_Block_Widget_Button
{
    /**
     * @see parent
     */
    protected function _construct()
    {
        $this->setLabel('Mark All Imported');
        $this->setOnClick("deleteConfirm('This will mark all customers as imported and will not send the information to Bronto.\\n\\nAre you sure you want to do this?', '" . Mage::helper('bronto_customer')->getScopeUrl('*/customer/mark') . "'); return false;");
        $this->setClass('bronto-cron-mark');

        if (!Mage::helper('bronto_customer')->isModuleActive() || (!Mage::helper('bronto_common')->isDebugEnabled())) {
            $this->setDisabled(true)->setClass('disabled');
        }
    }
}
