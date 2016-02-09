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

class Braintree_Payments_Block_Paypal_Form extends Mage_Payment_Block_Form
{
    const CONFIG_PATH_PAYPAL_VAULT_ENABLED      = 'payment/braintree/paypal_use_vault';

    /**
     * Internal constructor. Set template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('braintree/paypal/form.phtml');
    }

    /**
     * Returns saved customer Paypal accounts
     * 
     * @param boolean $admin
     * @return array
     */
    public function getPayPalAccounts($admin = false)
    {
        return Mage::getModel('braintree_payments/paypal')->currentCustomerPaypalAccounts($admin);
    }

    /**
     * If vault is enabled
     * 
     * @return boolean
     */
    public function canSaveAccount()
    {
        return (Mage::getStoreConfigFlag(self::CONFIG_PATH_PAYPAL_VAULT_ENABLED) && 
            (Mage::getSingleton('customer/session')->isLoggedIn() || 
            Mage::getSingleton('checkout/type_onepage')->getCheckoutMethod() 
                == Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER));
    }
}
