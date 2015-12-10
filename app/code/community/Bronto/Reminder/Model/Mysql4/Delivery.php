<?php

/**
 * @package   Bronto\Reminder
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Reminder_Model_Mysql4_Delivery extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('bronto_reminder/delivery', 'log_id');
    }
}
