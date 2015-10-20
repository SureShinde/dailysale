<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 27.07.15
 * Time: 18:05
 */ 
class Fiuze_AddressValidation_Model_Resource_Addresses_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{

    protected function _construct()
    {
        $this->_init('fiuze_addressvalidation/addresses');
    }

}