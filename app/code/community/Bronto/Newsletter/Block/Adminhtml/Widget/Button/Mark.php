<?php

class Bronto_Newsletter_Block_Adminhtml_Widget_Button_Mark extends Mage_Adminhtml_Block_Widget_Button
{
    /**
     * @see parent
     */
    protected function _construct()
    {
        $this->setLabel('Mark All Imported');
        $this->setOnClick("deleteConfirm('This will mark all subscribers as imported and will not send the information to Bronto.\\n\\nAre you sure you want to do this?', '" . Mage::helper('bronto_customer')->getScopeUrl('*/newsletter/mark') . "'); return false;");
        $this->setClass('bronto-cron-mark');

        if (!Mage::helper('bronto_newsletter')->isModuleActive() || (!Mage::helper('bronto_common')->isDebugEnabled())) {
            $this->setDisabled(true)->setClass('disabled');
        }
    }
}
