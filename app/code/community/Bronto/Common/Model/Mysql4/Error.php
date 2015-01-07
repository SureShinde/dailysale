<?php

class Bronto_Common_Model_Mysql4_Error extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * @see parent
     */
    public function _construct()
    {
        $this->_init('bronto_common/error', 'error_id');
    }
}
