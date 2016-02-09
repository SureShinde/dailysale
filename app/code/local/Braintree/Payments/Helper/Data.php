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

class Braintree_Payments_Helper_Data extends Mage_Core_Helper_Abstract
{
    const CONFIG_PATH_KOUNT_ID                  = 'payment/braintree/kount_id';
    const CONFIG_PATH_KOUNT_ENVIRONMENT         = 'payment/braintree/kount_environment';
    const CONFIG_PATH_3DSECURE                  = 'payment/braintree/three_d_secure';
    const CONFIG_PATH_3DSECURE_ALLOWSPECIFIC    = 'payment/braintree/three_d_secure_allowspecific';
    const CONFIG_PATH_3DSECURE_SPECIFICCOUNTRY  = 'payment/braintree/three_d_secure_specificcountry';
    const CONFIG_PATH_PAYPAL_TITLE              = 'payment/braintree/paypal_merchant_name';
    const CONFIG_PATH_BRAINTREE_CC_ENABLED      = 'payment/braintree/active';
    const CONFIG_PATH_BRAINTREE_PAYPAL_ENABLED  = 'payment/braintree/paypal_active';
    const CONFIG_PATH_3DSECURE_FAIL_ACTION      = 'payment/braintree/three_d_secure_failed';
    const CONFIG_PATH_THRESHOLD_AMOUNT          = 'payment/braintree/threshold_amount';
    const CONFIG_PATH_ADVANCED_FRAUD_PROTECTION = 'payment/braintree/fraudprotection';

    protected $_today = null;

    /**
     * Finds credit card type by type name using global payments config
     * 
     * @param string $name
     * @return boolean | string
     */
    public function getCcTypeCodeByName($name)
    {
        $ccTypes = Mage::getConfig()->getNode('global/payment/cc/types')->asArray();
        foreach ($ccTypes as $code => $data) {
            if (isset($data['name']) && $data['name'] == $name) {
                return $code;
            }
        }
        return 'OT';
    }

    /**
     * Get the configured Kount ID
     *
     * @return mixed
     */
    public function getKountId()
    {
        $storeId = null;
        if (Mage::app()->getStore()->isAdmin()) {
            $storeId = Mage::getSingleton('adminhtml/session_quote')->getQuote()->getStoreId();
        }
        return $this->jsQuoteEscape(Mage::getStoreConfig(self::CONFIG_PATH_KOUNT_ID, $storeId));
    }

    /**
     * Removes Magento added transaction id suffix if applicable
     * 
     * @param string $transactionId
     * @return strung
     */
    public function clearTransactionId($transactionId)
    {
        $suffixes = array(
            '-' . Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE,
            '-' . Mage_Sales_Model_Order_Payment_Transaction::TYPE_VOID,
        );
        foreach ($suffixes as $suffix) {
            if (strpos($transactionId, $suffix) !== false) {
                $transactionId = str_replace($suffix, '', $transactionId);
            }        
        }
        return $transactionId;
    }

    /**
     * Returns today year
     * 
     * @return string
     */
    public function getTodayYear()
    {
        if (!$this->_today) {
            $this->_today = Mage::app()->getLocale()->date(Mage::getSingleton('core/date')->gmtTimestamp(), null, null);
        }
        return $this->_today->toString('Y');
    }

    /**
     * Returns today month
     * 
     * @return string
     */
    public function getTodayMonth()
    {
        if (!$this->_today) {
            $this->_today = Mage::app()->getLocale()->date(Mage::getSingleton('core/date')->gmtTimestamp(), null, null);
        }
        return $this->_today->toString('M');
    }

    /**
     * Generates md5 hash to be used as customer id
     * 
     * @param string $customerId
     * @param string $email
     * @return string
     */
    public function generateCustomerId($customerId, $email)
    {
        return md5($customerId . '-' . $email);
    }
    
    /**
     * Generates token for further use
     * 
     * @return string | boolean
     */
    public function getToken()
    {
        $customerExists = false;
        $customerSession = Mage::getSingleton('customer/session');
        $magentoCustomerId = false;
        $magentoCustomerEmail = false;
        $storeId = null;
        if ($customerSession->isLoggedIn()) {
            $magentoCustomerId = $customerSession->getCustomerId();
            $magentoCustomerEmail = $customerSession->getCustomer()->getEmail();
        } else if (Mage::app()->getStore()->isAdmin()) {
            $quote = Mage::getSingleton('adminhtml/session_quote')->getQuote();
            $magentoCustomerId = $quote->getCustomerId();
            $magentoCustomerEmail = $quote->getCustomerEmail();
            $storeId = $quote->getStoreId();
        }
        if ($magentoCustomerId && $magentoCustomerEmail) {
            $customerId = $this->generateCustomerId($magentoCustomerId, $magentoCustomerEmail);
            try {
                $customerExists = Braintree_Customer::find($customerId);
            } catch (Exception $e) {
                $customerExists = false;
            }
        }

        $params = array("merchantAccountId" => Mage::getStoreConfig('payment/braintree/merchant_account_id', $storeId));
        if ($customerExists) {
            $params['customerId'] = $customerId;
        }
        try {
            $token = Braintree_ClientToken::generate($params);
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }
        return $this->jsQuoteEscape($token);
    }

    /**
     * Composes customer firstname lastname
     * 
     * @return string
     */
    public function getCardholderName()
    {
        if (Mage::app()->getStore()->isAdmin()) {
            $session = Mage::getSingleton('adminhtml/session_quote');
        } else {
            $session = Mage::getSingleton('checkout/session');
        }
        $billingAddress = $session->getQuote()->getBillingAddress();
        $name = $this->stripTags($billingAddress->getFirstname() . ' ' . $billingAddress->getLastname());
        $name = str_replace("'", "", $name);
        return $this->jsQuoteEscape($name);
    }

    /**
     * Generates nonce for saved payment method
     * 
     * @param string $ccToken
     * @return string
     */
    public function getNonceForVaultedToken($ccToken)
    {
        try {
            $result = Braintree_PaymentMethodNonce::create($ccToken);
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::throwException(Mage::helper('braintree_payments')->__('Please try again later'));
        }
        return $this->jsQuoteEscape($result->paymentMethodNonce->nonce);
    }

    /**
     * Checks if 3D Secure should apply
     * 
     * @param boolean $doNotCheckMethod
     * @return boolean
     */
    public function is3DSecureAvailable($doNotCheckMethod = false)
    {
        $available = false;
        // Disallow 3D Secure for multishipping
        if (Mage::getSingleton('checkout/session')->getQuote()->getIsMultiShipping()) {
            return $available;
        }
        if ((Mage::getSingleton('checkout/type_onepage')->getQuote()->getPayment()->getMethod() == 
            Braintree_Payments_Model_Creditcard::PAYMENT_METHOD_CODE || $doNotCheckMethod) && 
            Mage::getStoreConfigFlag(self::CONFIG_PATH_3DSECURE)) {

            if (Mage::getStoreConfig(self::CONFIG_PATH_3DSECURE_ALLOWSPECIFIC) == 1) {
                $country = Mage::getSingleton('checkout/session')->getQuote()->getBillingAddress()->getCountryId();
                $availableCountries = explode(',', Mage::getStoreConfig(self::CONFIG_PATH_3DSECURE_SPECIFICCOUNTRY));
                if (in_array($country, $availableCountries)) {
                    $available = true;
                }
            } else {
                $available = true;
            }
        }
        return $available;
    }

    /**
     * returns input name for nonce to use in all the places
     * 
     * @return string
     */
    public function getNonceInputId()
    {
        return Braintree_Payments_Model_Creditcard::PAYMENT_METHOD_CODE . '_nonce';
    }

    /**
     * Returns PayPal override merchant name
     * 
     * @return string
     */
    public function getPayPalTitle()
    {
        return $this->jsQuoteEscape(Mage::getStoreConfig(self::CONFIG_PATH_PAYPAL_TITLE));
    }

    /**
     * Get the configured Kount ID
     *
     * @return mixed
     */
    public function getKountEnvironment()
    {
        $storeId = null;
        if (Mage::app()->getStore()->isAdmin()) {
            $storeId = Mage::getSingleton('adminhtml/session_quote')->getQuote()->getStoreId();
        }
        return Mage::getStoreConfig(self::CONFIG_PATH_KOUNT_ENVIRONMENT, $storeId);
    }

    /**
     * If Braintree credit card payment method is enabled
     * 
     * @return boolean
     */
    public function isBraintreeCreditCardEnabled()
    {
        return (Mage::getStoreConfigFlag(self::CONFIG_PATH_BRAINTREE_CC_ENABLED) && $this->areCredentialCorrect());
    }

    /**
     * If Braintree PayPal payment method is enabled
     * 
     * @return boolean
     */
    public function isBraintreePayPalEnabled()
    {
        return (Mage::getStoreConfigFlag(self::CONFIG_PATH_BRAINTREE_PAYPAL_ENABLED) && $this->areCredentialCorrect());
    }

    /**
     * Returns order grand total
     * 
     * @return decimal
     */
    public function getOrderAmount()
    {
        return Mage::getSingleton('checkout/session')->getQuote()->getGrandTotal();
    }

    /**
     * If order can be placed on 3D Secure Fail
     * 
     * @return int
     */
    public function canContinueOn3DSecureFail()
    {
        $canContinue = false;
        $configured = Mage::getStoreConfig(self::CONFIG_PATH_3DSECURE_FAIL_ACTION);
        if ($configured == Braintree_Payments_Model_Source_Failed3dsecure::CREATE_ANYWAY) {
            $canContinue = true;
        } else if ($configured == Braintree_Payments_Model_Source_Failed3dsecure::USE_THRESHOLD) {
            $threshold = (float)Mage::getStoreConfig(self::CONFIG_PATH_THRESHOLD_AMOUNT);
            if ($threshold >= $this->getOrderAmount()) {
                $canContinue = true;
            }
        }
        return (int)$canContinue;
    }

    /**
     * If Braintree Advanced Fraud Protection is enabled 
     * 
     * @return boolean 
     */
    public function isAdvancedFraudProtectionEnabled()
    {
        $storeId = null;
        if (Mage::app()->getStore()->isAdmin()) {
            $storeId = Mage::getSingleton('adminhtml/session_quote')->getQuote()->getStoreId();
        }
        return Mage::getStoreConfigFlag(self::CONFIG_PATH_ADVANCED_FRAUD_PROTECTION, $storeId);
    }

    /**
     * If Iwd_Opc Extension is enabled
     * 
     * @return boolean
     */
    public function isIwdOpcExtensionEnabled()
    {
        return (Mage::getStoreConfigFlag('opc/global/status') && 
            Mage::helper('core')->isModuleOutputEnabled('IWD_Opc'));
    }

    /**
     * If Uni_Opcheckout Extension is enabled
     * 
     * @return boolean
     */
    public function isUniOpcheckoutExtensionEnabled()
    {
        return (Mage::getStoreConfigFlag('opcheckout/general/enabled') && 
            Mage::helper('core')->isModuleOutputEnabled('Uni_Opcheckout'));
    }

    /**
     * Performs a check if Braintree credentials are correct
     */
    public function areCredentialCorrect()
    {
        return Mage::getSingleton('braintree_payments/credentials')->checkCredentials();
    }
}
