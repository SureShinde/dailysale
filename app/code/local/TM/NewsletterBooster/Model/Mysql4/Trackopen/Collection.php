<?php

class TM_NewsletterBooster_Model_Mysql4_Trackopen_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('newsletterbooster/trackopen');
    }
}