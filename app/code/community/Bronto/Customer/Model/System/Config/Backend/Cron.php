<?php

/**
 * @package     Bronto\Customer
 * @copyright   2011-2012 Bronto Software, Inc.
 */
class Bronto_Customer_Model_System_Config_Backend_Cron extends Bronto_Common_Model_System_Config_Backend_Cron
{
    protected $_cron_string_path = 'crontab/jobs/bronto_customer_import/schedule/cron_expr';
    protected $_cron_model_path = 'crontab/jobs/bronto_customer_import/run/model';

    /**
     * @return string
     */
    public function getCronStringPath()
    {
        return Mage::helper('bronto_customer')->getCronStringPath();
    }

    /**
     * @return string
     */
    public function getCronModelPath()
    {
        return Mage::helper('bronto_customer')->getCronModelPath();
    }
}
