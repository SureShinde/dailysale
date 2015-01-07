<?php

class Bronto_Common_Block_Adminhtml_System_Config_Cron_Queue extends Bronto_Common_Block_Adminhtml_System_Config_Cron
{
    protected $_jobCode = 'bronto_common_queue';

    /**
     * @see parent
     */
    protected function _prepareLayout()
    {
        $this->addButton($this->getLayout()->createBlock('bronto_common/adminhtml_widget_queue_button_run'));
        return parent::_prepareLayout();
    }

    /**
     * @see parent
     */
    public function showCronTable()
    {
        return Mage::helper('bronto_common/api')->queueCanUseMageCron();
    }
}
