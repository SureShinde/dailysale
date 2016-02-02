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

class Braintree_Payments_Model_Creditcard extends Braintree_Payments_Model_Paymentmethod
{
    const PAYMENT_METHOD_CODE       = 'braintree';
    const CACHE_KEY_CREDIT_CARDS    = 'braintree_cc';
    const REGISTER_NAME             = 'braintree_save_card_token';
    const RISK_DATA_NOT_EVALUATED   = 'Not Evaluated';
    const RISK_DATA_APPROVE         = 'Approve';
    const RISK_DATA_REVIEW          = 'Review';
    const RISK_DATA_DECLINE         = 'Decline';

    protected $_formBlockType       = 'braintree_payments/creditcard_form';
    protected $_infoBlockType       = 'braintree_payments/creditcard_info';

    protected $_code                    = 'braintree';
    protected $_isGateway               = true;
    protected $_canAuthorize            = true;
    protected $_canCapture              = true;
    protected $_canCapturePartial       = true;
    protected $_canRefund               = true;
    protected $_canVoid                 = true;
    protected $_canUseInternal          = true;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = true;
    protected $_canSaveCc               = false;
    protected $_canRefundInvoicePartial = true;
    protected $_merchantAccountId       = '';
    protected $_useVault                = false;
    protected $_canReviewPayment        = true;

    /**
     * Assign corresponding data
     * 
     * @return Braintree_Payments_Model_Paymentmethod
     */
    public function assignData($data)
    {
        parent::assignData($data);
        $infoInstance = $this->getInfoInstance();
        $infoInstance->setCcLast4($data->getCcLast4());
        $additionalData = array();
        $nonce = '';
        if ($data->getCcToken()) {
            $nonce = Mage::helper('braintree_payments')->getNonceForVaultedToken($data->getCcToken());
            $additionalData['ccToken'] = $data->getCcToken();
        } else if ($data->getNonce() && $infoInstance->getAdditionalInformation('nonceLocked') != true) {
            $nonce = $data->getNonce();
        } else if (Mage::app()->getRequest()->getParam('payment_method_nonce')) {
            $nonce = Mage::app()->getRequest()->getParam('payment_method_nonce');
        }
        $additionalData['nonce'] = $nonce;
        if (Mage::app()->getRequest()->getParam('device_data')) {
            $additionalData['deviceData'] = Mage::app()->getRequest()->getParam('device_data');
        }
        if ($data->hasLiabilityShifted() || $data->hasLiabilityShiftPossible()) {
            $additionalData['liabilityShifted'] = $data->getLiabilityShifted();
            $additionalData['liabilityShiftPossible'] = $data->getLiabilityShiftPossible();
            $additionalData['threeDSecure'] = true;
            if ($infoInstance->getAdditionalInformation('nonceLocked') != true) {
                $additionalData['nonce'] = $data->getNonce();
                $additionalData['nonceLocked'] = true;
            } else {
                $additionalData['nonceLocked'] = false;
                $additionalData['nonce'] = $infoInstance->getAdditionalInformation('nonce');
            }
        } else {
            $additionalData['threeDSecure'] = false;
        }
        if ($data->getStoreInVault()) {
            $additionalData['storeInVault'] = $data->getStoreInVault();
        }
        $infoInstance->setAdditionalInformation($additionalData);
        return $this;
    }

    /**
     * Validate data
     * 
     * @return Braintree_Payments_Model_Paymentmethod
     */
    public function validate()
    {
        $info = $this->getInfoInstance();
        if ($info instanceof Mage_Sales_Model_Order_Payment) {
            $billingCountry = $info->getOrder()->getBillingAddress()->getCountryId();
        } else {
            $billingCountry = $info->getQuote()->getBillingAddress()->getCountryId();
        }

        if (!$this->canUseForCountry($billingCountry)) {
            Mage::throwException(
                Mage::helper('braintree_payments')->__('Selected payment type is not allowed for billing country.')
            );
        }

        $ccType = false;
        if ($info->getCcType()) {
            $ccType = $info->getCcType();
        } else {
            $token = $info->getAdditionalInformation('ccToken');
            if ($token) {
                $ccType = false;
                $useCache = $this->getConfigData('usecache');
                $cachedValues = $useCache ? Mage::app()->loadCache(self::CACHE_KEY_CREDIT_CARDS) : false;
                if ($cachedValues) {
                    try {
                        $data = unserialize($cachedValues);
                    } catch (Exception $e) {
                        $data = false;
                    }
                    if ($data && array_key_exists($token, $data)) {
                        $ccType = $data[$token];
                    }
                }
                if (!$ccType) {
                    try {
                        $creditCard = Braintree_PaymentMethod::find($token);
                        $this->_debug($token);
                        $this->_debug($creditCard);
                        $ccType = Mage::helper('braintree_payments')->getCcTypeCodeByName($creditCard->cardType);
                        if ($cachedValues && $data) {
                            $data = array_merge($data, array($token => $ccType));
                        } else {
                            $data = array($token => $ccType);
                        }
                        if ($useCache) {
                            Mage::app()->saveCache(serialize($data), self::CACHE_KEY_CREDIT_CARDS);
                        }
                    } catch (Exception $e) {
                        Mage::logException($e);
                    }
                }
            }
        }

        if ($ccType) {
            $error = $this->_canUseCcTypeForCountry($billingCountry, $ccType);
            if ($error) {
                Mage::throwException($error);
            }
        }
        
        return $this;
    }

    /**
     * Array of customer credit cards
     * 
     * @return array
     */
    public function currentCustomerStoredCards ()
    {
        if ($this->useVault() && Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customerId = Mage::helper('braintree_payments')->generateCustomerId(
                Mage::getSingleton('customer/session')->getCustomerId(),
                Mage::getSingleton('customer/session')->getCustomer()->getEmail()
            );
            try {
                $ret = Braintree_Customer::find($customerId)->creditCards;
                $this->_debug($customerId);
                $this->_debug($ret);
                return $ret;
            } catch (Braintree_Exception $e) {
                return array();
            }
        }
        return array();
    }

    /**
     * Deletes customer
     * 
     * @param int $customerID
     */
    public function deleteCustomer($customerID)
    {
        try {
            Braintree_Customer::delete($customerID);
        } catch (Braintree_Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * If credit card type can be used for billing country
     * 
     * @param string $country
     * @param string $ccType
     */
    protected function _canUseCcTypeForCountry($country, $ccType)
    {
        try {
            $countriesCardTypes = unserialize($this->getConfigData('countrycreditcard'));
        } catch (Exception $e) {
            $countriesCardTypes = false;
        }
        $countryFound = false;
        if ($countriesCardTypes) {
            if (array_key_exists($country, $countriesCardTypes)) {
                if (!in_array($ccType, $countriesCardTypes[$country])) {
                    return Mage::helper('braintree_payments')
                        ->__('Credit card type is not allowed for your country.');
                }
                $countryFound = true;
            }
        }
        if (!$countryFound) {
            $availableTypes = explode(',', $this->getConfigData('cctypes'));
            if (!in_array($ccType, $availableTypes)) {
                return Mage::helper('braintree_payments')
                    ->__('Credit card type is not allowed for this payment method.');
            }
        }
        return false;
    }

    /**
     * Check whether payment method is applicable to quote
     * Purposed to allow use in controllers some logic that was implemented in blocks only before
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param int|null $checksBitMask
     * @return bool
     */
    public function isApplicableToQuote($quote, $checksBitMask)
    {
        if (!Mage::helper('braintree_payments')->areCredentialCorrect()) {
            return false;
        }
        if (parent::isApplicableToQuote($quote, $checksBitMask)) {
            $availableCcTypes = $this->getApplicableCardTypes($quote->getBillingAddress()->getCountryId());
            if (!$availableCcTypes) {
                return false;
            }
        } else {
            return false;
        }
        return true;
    }

    /**
     * If there are any card types for country
     * 
     * @param string $country
     * @return array
     */
    public function getApplicableCardTypes($country)
    {
        try {
            $countriesCardTypes = unserialize($this->getConfigData('countrycreditcard'));
        } catch (Exception $e) {
            $countriesCardTypes = false;
        }
        if ($countriesCardTypes && array_key_exists($country, $countriesCardTypes)) {
            $allowedTypes = $countriesCardTypes[$country];
        } else {
            $allowedTypes = explode(',', $this->getConfigData('cctypes'));
        }
        return $allowedTypes;
    }

    /**
     * Saves Credit Card and customer (if new) in vault
     * 
     * @throws Mage_Core_Exception
     * @return boolean
     */
    public function saveInVault($postData, $token = false)
    {
        $post = $this->_protectArray($postData);
        $customerId = Mage::getSingleton('customer/session')->getCustomerId();
        if (!$customerId) {
            Mage::throwException(Mage::helper('braintree_payments')->__('Invalid Customer ID provided'));
        }
        $customerId = Mage::helper('braintree_payments')->generateCustomerId(
            $customerId,
            Mage::getSingleton('customer/session')->getCustomer()->getEmail()
        );
        $nonce = isset($post['nonce']) ? $post['nonce'] : '';
        if (!$this->_validateCustomerAddressData($post)) {
            Mage::throwException(Mage::helper('braintree_payments')->__('Invalid Address Data provided'));
        }
        $request = array(
            'billingAddress'    => array(
                'firstName'         => $post['credit_card']['billing_address']['first_name'],
                'lastName'          => $post['credit_card']['billing_address']['last_name'],
                'streetAddress'     => $post['credit_card']['billing_address']['street_address'],
                'locality'          => $post['credit_card']['billing_address']['locality'],
                'postalCode'        => $post['credit_card']['billing_address']['postal_code'],
                'countryCodeAlpha2' => $post['credit_card']['billing_address']['country_code_alpha2'],
            ),
        );
        if (isset($post['credit_card']['billing_address']['extended_address']) 
            && $post['credit_card']['billing_address']['extended_address']) {

            $request['billingAddress']['extendedAddress'] = $post['credit_card']['billing_address']['extended_address'];
        }
        if (isset($post['credit_card']['billing_address']['region']) 
            && $post['credit_card']['billing_address']['region']) {

            $request['billingAddress']['region'] = $post['credit_card']['billing_address']['region'];
        }
        if (isset($post['credit_card']['billing_address']['company'])
            && $post['credit_card']['billing_address']['company']) {
            $request['billingAddress']['company'] = $post['credit_card']['billing_address']['company'];
        }

        if ($token) {
            // update card
            $request['billingAddress']['options'] = array('updateExisting' => true);
            $extendedRequest = array(
                'creditCard'   => array(
                    'paymentMethodNonce'    => $nonce,
                    'billingAddress'        => $request['billingAddress'],
                    'options'               => array(
                        'updateExistingToken'   => $token
                    )
                )
            );
            if (isset($post['credit_card']['options']['make_default']) 
                && $post['credit_card']['options']['make_default']) {

                $extendedRequest['creditCard']['options']['makeDefault'] = true;
            }
            $this->_debug($token);
            $this->_debug($extendedRequest);
            $result = Braintree_Customer::update($customerId, $extendedRequest);
            $this->_debug($result);
        } else {
            if (!$this->_allowDuplicateCards()) {
                $request['options'] = array('failOnDuplicatePaymentMethod' => true);
            }
            if ($this->exists($customerId)) {
                // add new card for existing customer
                $request['customerId'] = $customerId;
                $request['paymentMethodNonce'] = $nonce;
                $this->_debug($request);
                $result = Braintree_PaymentMethod::create($request);
                $this->_debug($result);
            } else {
                // add new card and new customer
                $extendedRequest = array(
                    'id'                    => $customerId,
                    'firstName'             => $post['credit_card']['billing_address']['first_name'],
                    'lastName'              => $post['credit_card']['billing_address']['last_name'],
                    'email'                 => Mage::getSingleton('customer/session')->getCustomer()->getEmail(),
                    'paymentMethodNonce'    => $nonce,
                    'creditCard'            => $request,
                );
                if (isset($post['credit_card']['billing_address']['company']) 
                    && $post['credit_card']['billing_address']['company']) {

                    $extendedRequest['company'] = $post['credit_card']['billing_address']['company'];
                }
                $this->_debug($extendedRequest);
                $result = Braintree_Customer::create($extendedRequest);
                $this->_debug($result);
            }
        }
        if (!$result->success) {
            Mage::throwException(Mage::helper('braintree_payments/error')->parseBraintreeError($result));
        }
        return true;
    }

    /**
     * Validate if all required address data entered
     * 
     * @param array $customerData
     * @return boolean
     */
    protected function _validateCustomerAddressData($customerData)
    {
        if (isset($customerData['credit_card']) &&
            isset($customerData['credit_card']['billing_address']) &&
            isset($customerData['credit_card']['billing_address']['first_name']) &&
            $customerData['credit_card']['billing_address']['first_name'] &&
            isset($customerData['credit_card']['billing_address']['last_name']) &&
            $customerData['credit_card']['billing_address']['last_name'] &&
            isset($customerData['credit_card']['billing_address']['street_address'])
            && $customerData['credit_card']['billing_address']['street_address'] &&
            isset($customerData['credit_card']['billing_address']['locality'])
            && $customerData['credit_card']['billing_address']['locality'] &&
            isset($customerData['credit_card']['billing_address']['postal_code']) &&
            $customerData['credit_card']['billing_address']['postal_code'] &&
            isset($customerData['credit_card']['billing_address']['country_code_alpha2']) &&
            $customerData['credit_card']['billing_address']['country_code_alpha2']) {
            
            return true;
        }
        return false;
    }

    /**
     * Stripes tags in array
     * 
     * @param array $data
     */
    protected function _protectArray($data)
    {
        $callableFunction = function (&$param) {
            $param = Mage::helper('core')->stripTags($param);
        }; 
        array_walk_recursive($data, $callableFunction);
        return $data;
    }

    /**
     * Accepts payment anyway, because there are no adittional features to re-check payment on Braintree side
     *
     * @param Mage_Payment_Model_Info $payment
     * @return bool
     */
    public function acceptPayment(Mage_Payment_Model_Info $payment)
    {
        return true;
    }

    /**
     * Deny payment. Void authorization
     *
     * @param Mage_Payment_Model_Info $payment
     * @return bool
     */
    public function denyPayment(Mage_Payment_Model_Info $payment)
    {
        try{
            $this->void($payment);
        } catch (Exception $e) {
            $message = $e->getMessage();
            if ($e->getCode() == Braintree_Payments_Model_Paymentmethod::VOID_ALREADY_SETTLED_EXCEPTION) {
                $message = Mage::helper('braintree_payments')->__(
                    'Transaction is already settled and can not be voided.' .
                    ' Accept payment and create creditmemo for refund'
                );
            }
            Mage::throwException($message);
        }
        return true;
    }

    /**
     * In case of multishipping use previously saved token
     * 
     * @param string $token
     * @return string
     */
    protected function _getMethodSpecificAuthorizeTransactionToken($token)
    {
        if (Mage::registry(self::REGISTER_NAME)) {
            $token = Mage::registry(self::REGISTER_NAME);
        }
        return $token;
    }

    /**
     * Add method specific authorize transaction params:
     * fraud protection, 3D secure, save in vault, multishipping extra data
     * 
     * @param Varien_Object $payment $payment
     * @return array
     */
    protected function _addMethodSpecificAuthorizeTransactionParams($payment)
    {
        $transactionParams = array();

        // Save in vault and multishipping
        $order = $payment->getOrder();
        if ($order->getCustomerId() && $this->useVault()) {
            if ($payment->getAdditionalInformation('storeInVault') == true) {
                // to avoid card save several times during multishipping
                if (!Mage::registry(self::REGISTER_NAME)) {
                    $transactionParams['options']['storeInVaultOnSuccess'] = true;
                }
            } else if ($payment->getIsMultishipping() && !Mage::registry(self::REGISTER_NAME) 
                && !$payment->getAdditionalInformation('ccToken')) {

                $transactionParams['options']['storeInVaultOnSuccess'] = true;
                Mage::getSingleton('checkout/session')->setBraintreeDeleteCard(true);
            } else {
                $transactionParams['options']['storeInVault'] = false;
            }
        } else if ($payment->getIsMultishipping() && !Mage::registry(self::REGISTER_NAME)) {
            $transactionParams['options']['storeInVaultOnSuccess'] = true;
            Mage::getSingleton('checkout/session')->setBraintreeDeleteCard(true);                
        } else {
            $transactionParams['options']['storeInVault'] = false;
        }

        // Advanced fraud protection
        if ($this->getConfigData('fraudprotection') && $payment->getAdditionalInformation('deviceData')) {
            $transactionParams['deviceData'] = $payment->getAdditionalInformation('deviceData');
        }

        // 3D Secure
        if ($payment->getAdditionalInformation('threeDSecure') === true) {
            if ($payment->getAdditionalInformation('liabilityShiftPossible') == true &&
                $payment->getAdditionalInformation('liabilityShifted') == true ) {

                $value = true;
            } else {
                $value = false;
            }
            $transactionParams['options']['three_d_secure'] = array('required' => $value);
        }

        return $transactionParams;
    }

    /**
     * To make additional manipulations on transaction error. Unregister multishipping token
     */
    protected function _processMethodSpecificAuthorizeTransactionError()
    {
        Mage::unregister(self::REGISTER_NAME);
    }

    /**
     * Makes method specific manipulations after authorize/clone transaction success
     * 
     * @param Varien_Object $payment $payment
     * @param array $result
     */
    protected function _processMethodSpecificTransactionSuccess($payment, $result)
    {
        // Saving token if applicable, additional manipulations for multishipping
        if (isset($result->transaction->creditCard['token']) && $result->transaction->creditCard['token']) {
            $token = $result->transaction->creditCard['token'];
            $payment->setTransactionAdditionalInfo('token', $token);
            
            if ($payment->getIsMultishipping()) {
                if (!Mage::registry(self::REGISTER_NAME)) {
                    Mage::register(self::REGISTER_NAME, $token);
                }
                if (Mage::getSingleton('checkout/session')->getBraintreeDeleteCard() === true) {
                    Mage::getSingleton('checkout/session')->setBraintreeDeleteCard($token);
                }
            }
        }

        // Advanced fraud protection data
        if (isset($result->transaction->riskData) && $this->getConfigData('fraudprotection')) {
            $decision = $result->transaction->riskData->decision;
            $helper = Mage::helper('braintree_payments');
            if ($decision == self::RISK_DATA_NOT_EVALUATED || $decision == self::RISK_DATA_REVIEW) {
                $payment->setIsTransactionPending(true);
                $payment->setIsFraudDetected(true);
            } else if ($decision == self::RISK_DATA_DECLINE) {
                Braintree_Transaction::void($result->transaction->id);
                throw new Mage_Payment_Model_Info_Exception($helper->__('Try another card'));
            }
            $system = $this->getConfigData('kount_id') ?
                'Kount' : 
                $helper->__('Braintree Advanced Fraud Protection Tool');
            $id = '';
            if ($result->transaction->riskData->id) {
                $id = $helper->__(', ID is "%s"', $result->transaction->riskData->id);
            }
            $comment = $helper->__('Transaction was evaluated with %s: decision is "%s"', $system, $decision) . $id;
            $payment->getOrder()->addStatusHistoryComment($comment);
        }
    }

    /**
     * Returns method specific extra transaction information, to be logged as part of the order payment
     * 
     * @param array $transaction
     * @param Varien_Object $payment
     * @return array
     */
    protected function _getMethodSpecificExtraTransactionInformation($transaction, $payment)
    {
        $data = array();

        // 3D Secure
        $liabilityShiftStatus = '';
        if ($payment->getAdditionalInformation('threeDSecure') === true) {
            if ($payment->getAdditionalInformation('liabilityShiftPossible') == true) {
                if ($payment->getAdditionalInformation('liabilityShifted') == true) {
                    $liabilityShiftStatus = Mage::helper('braintree_payments')->__('Liability Shifted');
                } else {
                    $liabilityShiftStatus = Mage::helper('braintree_payments')->__('Failed Authentication');
                }
            } else {
                $liabilityShiftStatus = Mage::helper('braintree_payments')->__('Ineligible for 3D Secure');
            }
        } else {
            $liabilityShiftStatus = Mage::helper('braintree_payments')->__('Not applicable');
        }
        $data['liabilityShiftStatus'] = $liabilityShiftStatus;

        // Kount Risk data
        if (isset($transaction->riskData) && $transaction->riskData) {
            if ($transaction->riskData->decision) {
                $data['riskDecision'] = $transaction->riskData->decision;
            }
            if ($transaction->riskData->id) {
                $data['riskTransactionId'] = $transaction->riskData->id;
            }
        }

        return $data;
    }

    /**
     * Updates customer ID
     * 
     * @param string $customerId
     * @param string $newId
     */
    public function updateCustomerId($customerId, $newId)
    {
        if ($this->exists($customerId)) {
            $this->_debug("Updating customer ID $customerId to $newId");
            try {
                $result = Braintree_Customer::update($customerId, array('id' => $newId));
            } catch (Exception $e) {
                Mage::logException($e);
                $result = 'Update failed';
            }
            $this->_debug($result);
        }
    }
}
