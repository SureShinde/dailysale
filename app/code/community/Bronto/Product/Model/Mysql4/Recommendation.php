<?php

class Bronto_Product_Model_Mysql4_Recommendation extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     *  @see parent
     */
    public function _construct()
    {
        $this->_init('bronto_product/recommendation', 'entity_id');
    }
}
