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

class Braintree_Payments_Model_System_Config_Backend_Credentials extends Mage_Core_Model_Config_Data
{
    // Fields which enable Braintree payment methods
    protected $_activeField = array(
        'active',
        'paypal_active',
    );

    /**
     * Prepare data before save
     * 
     * If field does not have value and method is enabled throw exception
     */
    protected function _beforeSave()
    {
        if (!$this->getValue() && $this->isValueChanged()) {
            $data = $this->getData();
            foreach ($this->_activeField as $field) {
                $fieldData = $data['groups']['braintree']['fields'][$field];
                if ((isset($fieldData['value']) && $fieldData['value']) || 
                    (isset($fieldData['inherit']) && $fieldData['inherit'] && 
                    ((string) Mage::getConfig()->getNode('default/payment/braintree/' . $field)))) {

                    Mage::throwException(
                        Mage::helper('braintree_payments')->__(
                            'The following fields are required to be completed to save the configuration: ' .
                            'Merchant ID, Merchant Account ID, Public Key and Private Key. No changes were saved'
                        )
                    );
                }
            }
        }
    }
}
