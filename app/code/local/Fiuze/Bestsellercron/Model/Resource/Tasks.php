<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 31.08.15
 * Time: 14:36
 */ 
class Fiuze_Bestsellercron_Model_Resource_Tasks extends Mage_Core_Model_Resource_Db_Abstract
{

    protected function _construct()
    {
        $this->_init('bestsellercron/fiuze_tasks', 'fiuze_tasks_id');
    }

}