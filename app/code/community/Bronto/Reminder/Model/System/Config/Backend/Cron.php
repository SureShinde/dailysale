<?php

/**
 * @package   Bronto\Reminder
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Reminder_Model_System_Config_Backend_Cron extends Bronto_Common_Model_System_Config_Backend_Cron
{
    /**
     * @var string
     */
    protected $_cron_string_path = 'crontab/jobs/bronto_reminder_send_notification/schedule/cron_expr';

    /**
     * @var string
     */
    protected $_cron_model_path = 'crontab/jobs/bronto_reminder_send_notification/run/model';

    /**
     * @return string
     */
    public function getCronStringPath()
    {
        return Mage::helper('bronto_reminder')->getCronStringPath();
    }

    /**
     * @return string
     */
    public function getCronModelPath()
    {
        return Mage::helper('bronto_reminder')->getCronModelPath();
    }
}
