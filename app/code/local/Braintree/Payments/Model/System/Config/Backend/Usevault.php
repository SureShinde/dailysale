<?php
/**
* Braintree Payments Extension
*
* This source file is subject to the Braintree Payment System Agreement (https://www.braintreepayments.com/legal)
*
* DISCLAIMER
* This file will not be supported if it is modified.
*
* @copyright   Copyright (c) 2015 Braintree. (https://www.braintreepayments.com/)
*/

class Braintree_Payments_Model_System_Config_Backend_Usevault extends Mage_Core_Model_Config_Data
{
    protected $_enabledFieldName = 'active';
    /**
     * Prepare data before save
     * If payment method is disabled, vault also have to be disabled
     */
    protected function _beforeSave()
    {
        $data = $this->getData();
        if (isset($data['groups']['braintree']['fields'][$this->_enabledFieldName]['value']) && 
            !$data['groups']['braintree']['fields'][$this->_enabledFieldName]['value']) {
            
            $this->setValue(0);
        }
    }
}
