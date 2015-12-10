<?php

class Fiuze_Track_Model_Mysql4_ScheduleAftership_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * Initialize resource collection
     *
     */
    public function _construct()
    {
        $this->_init('track/scheduleAftership');
    }
}
