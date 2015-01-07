<?php

class Bronto_Common_Block_Adminhtml_System_Config_Cron_Error extends Bronto_Common_Block_Adminhtml_System_Config_Cron
{
    protected $_jobCode = 'bronto_common_errors';

    /**
     * @return bool
     */
    public function showCronTable()
    {
        return Mage::helper('bronto_common/api')->canUseMageCron();
    }
}
