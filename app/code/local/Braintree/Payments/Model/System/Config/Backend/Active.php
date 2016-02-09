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

class Braintree_Payments_Model_System_Config_Backend_Active extends Mage_Core_Model_Config_Data
{
    // Fields required to enter
    protected $_credentialField = array(
        'merchant_id',
        'merchant_account_id',
        'public_key',
        'private_key'
    );

    /**
     * Prepare data before save
     * 
     * If not all the credential fields are set up dissalow to enable payment method
     * @return Braintree_Payments_Model_System_Config_Backend_Active
     */
    protected function _beforeSave()
    {
        if ($this->getValue() == 1) {
            $data = $this->getData();
            foreach ($this->_credentialField as $field) {
                $fieldData = $data['groups']['braintree']['fields'][$field];
                if ((isset($fieldData['value']) && !$fieldData['value']) || 
                    (isset($fieldData['inherit']) && $fieldData['inherit'] && 
                    !((string) Mage::getConfig()->getNode('default/payment/braintree/' . $field)))) {

                    Mage::throwException(
                        Mage::helper('braintree_payments')->__(
                            'To enable Braintee Payment please complete the required information for ' . 
                            'Merchant ID, Merchant Account ID, Public Key and Private Key'
                        )
                    );
                }
            }
        }
        return parent::_beforeSave();
    }
}
