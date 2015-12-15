<?php

/**
 * @category Bronto
 * @package  Common
 */
class Bronto_Common_Model_System_Config_Source_Cron_Frequency
{

    /**
     * Description for const
     */
    const CRON_MINUTELY = 'I';

    /**
     * Description for const
     */
    const CRON_HOURLY = 'H';

    /**
     * Description for const
     */
    const CRON_DAILY = 'D';

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return mixed  Return description (if any) ...
     * @access public
     */
    public function toOptionArray()
    {
        return array(
            self::CRON_MINUTELY => Mage::helper('cron')->__('Minute Intervals'),
            self::CRON_HOURLY   => Mage::helper('cron')->__('Hourly'),
            self::CRON_DAILY    => Mage::helper('cron')->__('Daily'),
        );
    }
}