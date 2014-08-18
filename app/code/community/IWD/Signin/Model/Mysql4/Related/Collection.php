<?php
class IWD_Signin_Model_Mysql4_Related_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract{
    /**
     * Resource initialization.
     *
     * @return void
     */
    protected function _construct(){
        $this->_init('signin/related');
    }
}
