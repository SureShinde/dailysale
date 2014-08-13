<?php
class IWD_Signin_Model_Related extends Mage_Core_Model_Abstract{


    public function _construct(){
        parent::_construct();
        $this->_init('signin/related');
    }

}