<?php

class Bronto_News_Model_Mysql4_Item_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{

    /**
     * @see parent
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('bronto_news/item');
    }

    /**
     * Orders the items by publication date
     *
     * @return Bronto_News_Model_Mysql4_Item_Collection
     */
    public function orderByMostRecent()
    {
        return $this->addOrder('pub_date');
    }
}
