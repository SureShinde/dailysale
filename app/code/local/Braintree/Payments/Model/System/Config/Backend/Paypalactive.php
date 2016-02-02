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

class Braintree_Payments_Model_System_Config_Backend_Paypalactive 
    extends Braintree_Payments_Model_System_Config_Backend_Active
{
    /**
     * Prepare data before save
     * 
     * Save the same data to 'payment/braintree_paypal/active' config path
     * @return Braintree_Payments_Model_System_Config_Backend_Paypalactive
     */
    protected function _beforeSave()
    {
        parent::_beforeSave();
        if ($this->isValueChanged()) {
            if ($this->getWebsiteCode()) {
                $scope = 'websites';
            } else {
                $scope = 'default';
            }
            Mage::getModel('core/config')->saveConfig(
                'payment/braintree_paypal/active', $this->getValue(), $scope, $this->getScopeId()
            );
        }
        return $this;
    }
}
