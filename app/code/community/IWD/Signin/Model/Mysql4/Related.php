<?php
class IWD_Signin_Model_Mysql4_Related extends Mage_Core_Model_Mysql4_Abstract{
	
    public function _construct(){
        $this->_init('signin/related', 'entity_id');
    }
    

}