<?php

class Fiuze_Bestsellercron_Model_Resource_TaskLogs_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{

    protected function _construct()
    {
        $this->_init('bestsellercron/taskLogs');
    }

}