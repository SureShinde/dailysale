<?php

class TM_NewsletterBooster_Model_Mysql4_Gateway_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('newsletterbooster/gateway');
    }

//    public function addStatusFilter($status = 1)
//    {
//        $this->getSelect()->where('main_table.status=?', $status);
//        return $this;
//    }
}