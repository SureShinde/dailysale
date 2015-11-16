<?php

class Fiuze_Bestsellercron_Model_Resource_TaskLogs extends Mage_Core_Model_Resource_Db_Abstract
{

    protected function _construct()
    {
        $this->_init('bestsellercron/fiuze_task_logs', 'fiuze_task_logs_id');
    }

}