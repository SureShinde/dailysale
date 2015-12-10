<?php

/**
 * @package   Bronto\Order
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Order_Model_System_Config_Backend_Cron extends Bronto_Common_Model_System_Config_Backend_Cron
{
    /**
     * @var string
     */
    protected $_cron_string_path = 'crontab/jobs/bronto_order_import/schedule/cron_expr';

    /**
     * @var string
     */
    protected $_cron_model_path = 'crontab/jobs/bronto_order_import/run/model';

    /**
     * @return string
     */
    public function getCronStringPath()
    {
        return Mage::helper('bronto_order')->getCronStringPath();
    }

    /**
     * @return string
     */
    public function getCronModelPath()
    {
        return Mage::helper('bronto_order')->getCronModelPath();
    }
}
