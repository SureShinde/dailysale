<?php

class Fiuze_Deals_Model_Resource_Deals extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Resource initialization
     */
    protected function _construct()
    {
        $this->_init('fiuze_deals/deals', 'entity_id');
    }
}

