<?php

class Turnto_Admin_Model_Resource_Rating extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('turnto_admin/rating','id');
    }
}