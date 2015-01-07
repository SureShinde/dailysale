<?php

class Bronto_Common_Model_Mysql4_Api_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * @see parent
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('bronto_common/api');
    }
}
