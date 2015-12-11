<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 27.07.15
 * Time: 18:05
 */ 
class Fiuze_AddressValidation_Model_Resource_Addresses extends Mage_Core_Model_Resource_Db_Abstract
{

    protected function _construct()
    {
        $this->_init('fiuze_addressvalidation/fiuze_addresses', 'fiuze_addresses_id');
    }

}