<?php

class Bronto_Common_Model_Mysql4_Api extends Mage_Core_Model_Mysql4_Abstract
{
    protected $_isPkAutoIncrement = false;

    /**
     * @see parent
     */
    public function _construct()
    {
        $this->_init('bronto_common/api', 'token');
    }
}
