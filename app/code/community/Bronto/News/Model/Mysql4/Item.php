<?php

class Bronto_News_Model_Mysql4_Item extends Mage_Core_Model_Mysql4_Abstract
{

    /**
     * @see parent
     */
    protected function _construct()
    {
        $this->_init('bronto_news/item', 'item_id');
    }
}
