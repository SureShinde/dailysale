<?php

/**
 * @package   Bronto\Reminder
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Reminder_Block_Adminhtml_System_Config_Cron extends Bronto_Common_Block_Adminhtml_System_Config_Cron
{
    /**
     * @var string
     */
    protected $_jobCode = 'bronto_reminder_send_notification';

    /**
     * Determine if should show the cron table
     *
     * @return mixed
     */
    public function showCronTable()
    {
        return Mage::helper('bronto_reminder')->canUseMageCron();
    }
}
