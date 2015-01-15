<?php


class Fiuze_Deals_Model_Resource_Deals_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('fiuze_deals/deals');
    }
}