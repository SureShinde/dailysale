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

class Braintree_Payments_Model_Credentials
{
    protected $_result = null;

    /**
     * Check if Braintree credentials are correct by sending request to get client
     * 
     * @return boolean
     */
    public function checkCredentials()
    {
        if (is_null($this->_result)) {
            try {
                $result = Mage::helper('braintree_payments')->getToken();
            } catch (Exception $e) {
                $result = false;
            }
            $this->_result = ((boolean) $result) && $this->_checkMerchantAccountId();
        }
        return $this->_result;
    }

    /**
     * Check if specified Merchant Account Id is correct
     * 
     * @return boolean
     */
    protected function _checkMerchantAccountId()
    {
        $storeId = null;
        if (Mage::app()->getStore()->isAdmin()) {
            $quote = Mage::getSingleton('adminhtml/session_quote')->getQuote();
            $storeId = $quote->getStoreId();
        }
        $merchantAccountId = Mage::getStoreConfig('payment/braintree/merchant_account_id', $storeId);
        try {
            Braintree_MerchantAccount::find($merchantAccountId);
            $result = true;
        } catch (Exception $e) {
            $result = false;
        }
        return $result;
    }
}
