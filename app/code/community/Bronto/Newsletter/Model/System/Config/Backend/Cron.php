<?php

/**
 * @package     Newsletter
 * @copyright   2011-2013 Bronto Software, Inc.
 */
class Bronto_Newsletter_Model_System_Config_Backend_Cron extends Bronto_Common_Model_System_Config_Backend_Cron
{
    protected $_cron_string_path = 'crontab/jobs/bronto_newsletter_import/schedule/cron_expr';
    protected $_cron_model_path = 'crontab/jobs/bronto_newsletter_import/run/model';

    /**
     * @return string
     */
    public function getCronStringPath()
    {
        return Mage::helper('bronto_newsletter')->getCronStringPath();
    }

    /**
     * @return string
     */
    public function getCronModelPath()
    {
        return Mage::helper('bronto_newsletter')->getCronModelPath();
    }
}
