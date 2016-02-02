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

abstract class Braintree_Payments_Model_Paymentmethod extends Mage_Payment_Model_Method_Cc
{
    const CHANNEL_NAME                      = 'RocketWeb_SI_Magento%s_V2';
    const VOID_ALREADY_SETTLED_EXCEPTION    = '10000';

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        if ($this->getConfigData('active') == 1) {
            $this->_initEnvironment(null);
        }
    }

    /**
     * Authorizes specified amount
     * 
     * @param Varien_Object $payment
     * @param decimal $amount
     */
    public function authorize (Varien_Object $payment, $amount)
    {
        $this->_authorize($payment, $amount, false);
    }

    /**
     * Authorizes specified amount
     * 
     * @param Varien_Object $payment
     * @param decimal $amount
     * @param boolean $capture
     * @return Braintree_Payments_Model_Paymentmethod
     */
    protected function _authorize (Varien_Object $payment, $amount, $capture, $token = false)
    {
        try {
            $order = $payment->getOrder();
            $orderId = $order->getIncrementId();
            $billing = $order->getBillingAddress();
            $shipping = $order->getShippingAddress();
            $transactionParams = array(
                'channel'   => $this->_getChannel(),
                'orderId'   => $orderId,
                'amount'    => $amount,
                'customer'  => array(
                    'firstName' => $billing->getFirstname(),
                    'lastName'  => $billing->getLastname(),
                    'company'   => $billing->getCompany(),
                    'phone'     => $billing->getTelephone(),
                    'fax'       => $billing->getFax(),
                    'email'     => $order->getCustomerEmail(),
                )
            );

            $customerId = Mage::helper('braintree_payments')
                ->generateCustomerId($order->getCustomerId(), $order->getCustomerEmail());
            
            if ($order->getCustomerId() && $this->exists($customerId)) {
                $transactionParams['customerId'] = $customerId;
                unset($transactionParams['customer']);
            } else {
                $transactionParams['customer']['id'] = $customerId;
            }

            if ($capture) {
                $transactionParams['options']['submitForSettlement'] = true;
            }

            if ($this->_merchantAccountId) {
                $transactionParams['merchantAccountId'] = $this->_merchantAccountId;
            }

            $token = $this->_getMethodSpecificAuthorizeTransactionToken($token);

            if ($token) {
                $nonce = Mage::helper('braintree_payments')->getNonceForVaultedToken($token);
                $transactionParams['customerId'] = $customerId;
            } else {
                $transactionParams['billing']  = $this->_toBraintreeAddress($billing);
                $transactionParams['shipping'] = $this->_toBraintreeAddress($shipping);
                $transactionParams['options']['addBillingAddressToPaymentMethod']  = true;
                $nonce = $payment->getAdditionalInformation('nonce');
            }
            
            $transactionParams['paymentMethodNonce'] = $nonce;

            $transactionParams = array_merge_recursive(
                $transactionParams,
                $this->_addMethodSpecificAuthorizeTransactionParams($payment)
            );

            if (isset($transactionParams['options']['storeInVault']) && 
                !$transactionParams['options']['storeInVault']) {

                $transactionParams['options']['addBillingAddressToPaymentMethod']  = false;
            }

            $this->_debug($transactionParams);
            try {
                $result = Braintree_Transaction::sale($transactionParams);
                $this->_debug($result);
            } catch (Exception $e) {
                Mage::logException($e);
                Mage::throwException(Mage::helper('braintree_payments')->__('Please try again later'));
            }
            if ($result->success) {
                $this->setStore($payment->getOrder()->getStoreId());
                $payment = $this->_processSuccessResult($payment, $result, $amount);
            } else {
                Mage::throwException(Mage::helper('braintree_payments/error')->parseBraintreeError($result));
            }
        } catch (Exception $e) {
            $this->_processMethodSpecificAuthorizeTransactionError();
            throw new Mage_Payment_Model_Info_Exception($e->getMessage());
        }
        return $this;
    }

    /**
     * Returns extra transaction information, to be logged as part of the order payment
     *
     * @param $transaction
     * @param $payment
     * @return array
     */
    protected function _getExtraTransactionInformation($transaction, $payment)
    {
        $data = array();
        $loggedFields = array(
            'avsErrorResponseCode',
            'avsPostalCodeResponseCode',
            'avsStreetAddressResponseCode',
            'cvvResponseCode',
            'gatewayRejectionReason',
            'processorAuthorizationCode',
            'processorResponseCode',
            'processorResponseText'
        );
        foreach ($loggedFields as $loggedField) {
            if (!empty($transaction->{$loggedField})) {
                $data[$loggedField] = $transaction->{$loggedField};
            }
        }

        $data = array_merge($data, $this->_getMethodSpecificExtraTransactionInformation($transaction, $payment));

        return $data;
    }

    /**
     * Captures specified amount
     * 
     * @param Varien_Object $payment
     * @param decimal $amount
     * @return Braintree_Payments_Model_Paymentmethod
     */
    public function capture(Varien_Object $payment, $amount)
    {
        try {
            if ($payment->getCcTransId()) {
                $collection = Mage::getModel('sales/order_payment_transaction')
                    ->getCollection()
                    ->addFieldToFilter('payment_id', $payment->getId())
                    ->addFieldToFilter('txn_type', Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
                if ($collection->getSize() > 0) {
                    $collection = Mage::getModel('sales/order_payment_transaction')
                        ->getCollection()
                        ->addPaymentIdFilter($payment->getId())
                        ->addTxnTypeFilter(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH)
                        ->setOrder('created_at', Varien_Data_Collection::SORT_ORDER_DESC)
                        ->setOrder('transaction_id', Varien_Data_Collection::SORT_ORDER_DESC)
                        ->setPageSize(1)
                        ->setCurPage(1);
                    $authTransaction = $collection->getFirstItem();
                    if (!$authTransaction->getId()) {
                        Mage::throwException(Mage::helper('braintree_payments')->__('Please try again later'));
                    }
                    if (($token = $authTransaction->getAdditionalInformation('token'))) {
                        //order was placed using saved card or card was saved during checkout token
                        $found = true;
                        try {
                            Braintree_PaymentMethod::find($token);
                        } catch (Exception $e) {
                            $found = false;
                        }
                        if ($found) {
                            $this->_initEnvironment($payment->getOrder()->getStoreId());
                            $this->_authorize($payment, $amount, true, $token);
                        } else {
                            // case if payment token is no more applicable. attempt to clone transaction
                            $result = $this->_cloneTransaction($amount, $authTransaction->getTxnId());
                            if ($result && $result->success) {
                                $payment = $this->_processSuccessResult($payment, $result, $amount);
                            } else if ($result === false) {
                                Mage::throwException(Mage::helper('braintree_payments')->__('Please try again later'));
                            } else {
                                Mage::throwException(
                                    Mage::helper('braintree_payments/error')->parseBraintreeError($result)
                                );
                            }
                        }
                    } else {
                        // order was placed without saved card and card wasn't saved during checkout
                        $result = $this->_cloneTransaction($amount, $authTransaction->getTxnId());
                        if ($result && $result->success) {
                            $payment = $this->_processSuccessResult($payment, $result, $amount);
                        } else if ($result === false) {
                            Mage::throwException(Mage::helper('braintree_payments')->__('Please try again later'));
                        } else {
                            Mage::throwException(
                                Mage::helper('braintree_payments/error')->parseBraintreeError($result)
                            );
                        }
                    }
                } else {
                    $result = Braintree_Transaction::submitForSettlement($payment->getCcTransId(), $amount);
                    $this->_debug($payment->getCcTransId().' - '.$amount);
                    $this->_debug($result);
                    if ($result->success) {
                        $payment->setIsTransactionClosed(0)
                            ->setAmountPaid($result->transaction->amount)
                            ->setShouldCloseParentTransaction(false);
                    } else {
                        Mage::throwException(Mage::helper('braintree_payments/error')->parseBraintreeError($result));
                    }
                }
            } else {
                $this->_authorize($payment, $amount, true);
            }
        } catch (Exception $e) {
            Mage::throwException(
                Mage::helper('braintree_payments')->__('There was an error capturing the transaction.') .
                ' ' . $e->getMessage()
            );
        }
        return $this;
    }

    /**
     * Refunds specified amount
     * 
     * @param Varien_Object $payment
     * @param decimal $amount
     * @return Braintree_Payments_Model_Paymentmethod
     */
    public function refund(Varien_Object $payment, $amount)
    {
        $transactionId = Mage::helper('braintree_payments')->clearTransactionId($payment->getRefundTransactionId());
        try {
            $transaction = Braintree_Transaction::find($transactionId);
            $this->_debug($payment->getCcTransId());
            $this->_debug($transaction);
            if ($transaction->status === Braintree_Transaction::SUBMITTED_FOR_SETTLEMENT) {
                if ($transaction->amount != $amount ) {
                    Mage::throwException(
                        Mage::helper('braintree_payments')->__(
                            'This refund is for a partial amount but the Transaction has not settled. ' .
                            'Please wait 24 hours before trying to issue a partial refund.'
                        )
                    );
                } else {
                    Mage::throwException(
                        Mage::helper('braintree_payments')->__(
                            'The Transaction has not settled. ' .
                            'Please wait 24 hours before trying to issue a refund or use Void option.'
                        )
                    );
                }
            }

            if ($transaction->status === Braintree_Transaction::SETTLED || 
                $transaction->status === Braintree_Transaction::SETTLING) {

                $result = Braintree_Transaction::refund($transactionId, $amount);
            } else {
                $result = Braintree_Transaction::void($transactionId);
            }

            $this->_debug($result);
            if ($result->success) {
                $payment->setIsTransactionClosed(1);
            } else {
                Mage::throwException(Mage::helper('braintree_payments/error')->parseBraintreeError($result));
            }
        } catch (Exception $e) {
            Mage::throwException(
                Mage::helper('braintree_payments')->__('There was an error refunding the transaction.') . ' '
                . $e->getMessage()
            );
        }
        return $this;
    }

    /**
     * Voids transaction
     * 
     * @param Varien_Object $payment
     * @return Braintree_Payments_Model_Paymentmethod
     */
    public function void(Varien_Object $payment)
    {
        $transactionIds = array();
        $invoice = Mage::registry('current_invoice');
        $message = false;
        if ($invoice && $invoice->getId() && $invoice->getTransactionId()) {
            $transactionIds[] = Mage::helper('braintree_payments')->clearTransactionId($invoice->getTransactionId());
            
        } else {
            $collection = Mage::getModel('sales/order_payment_transaction')
                ->getCollection()
                ->addFieldToSelect('txn_id')
                ->addOrderIdFilter($payment->getOrder()->getId())
                ->addTxnTypeFilter(
                    array(
                        Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, 
                        Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE
                    )
                );
            $fetchedIds = $collection->getColumnValues('txn_id');
            foreach ($fetchedIds as $transactionId) {
                $txnId = Mage::helper('braintree_payments')->clearTransactionId($transactionId);
                if (!in_array($txnId, $transactionIds)) {
                    $transactionIds[] = $txnId;
                }
            }
        }
        foreach ($transactionIds as $transactionId) {
            $transaction = Braintree_Transaction::find($transactionId);
            if ($transaction->status !== Braintree_Transaction::SUBMITTED_FOR_SETTLEMENT && 
                $transaction->status !== Braintree_Transaction::AUTHORIZED) {
                $message = Mage::helper('braintree_payments')
                        ->__('Some transactions are already settled or voided and cannot be voided.');
                throw new Mage_Core_Exception($message, self::VOID_ALREADY_SETTLED_EXCEPTION);
            }
            if ($transaction->status === Braintree_Transaction::SUBMITTED_FOR_SETTLEMENT) {
                $message = Mage::helper('braintree_payments')->__('Voided capture.');
            }
        }
        $errors = '';
        foreach ($transactionIds as $transactionId) {

            $this->_debug('void-' . $transactionId);
            $result = Braintree_Transaction::void($transactionId);
            $this->_debug($result);
            if (!$result->success) {
                $errors .= ' ' . Mage::helper('braintree_payments/error')->parseBraintreeError($result);
            } else if ($message) {
                $payment->setMessage($message);
            }
        }
        if ($errors) {
            Mage::throwException(
                Mage::helper('braintree_payments')->__('There was an error voiding the transaction.'). $errors
            );
            
        } else {
            $match = true;
            foreach ($transactionIds as $transactionId) {
                $collection = Mage::getModel('sales/order_payment_transaction')
                    ->getCollection()
                    ->addFieldToFilter('parent_txn_id', array('eq' => $transactionId))
                    ->addFieldToFilter('txn_type', Mage_Sales_Model_Order_Payment_Transaction::TYPE_VOID);
                if ($collection->getSize() < 1) {
                    $match = false;
                }
            }
            if ($match) {
                $payment->setIsTransactionClosed(1);
            }
        }
        return $this;
    }

    /**
     * Convert magento address to array for braintree
     * 
     * @param Mage_Customer_Model_Address $address
     * @return array
     */
    protected function _toBraintreeAddress($address)
    {
        if ($address) {
            return array(
                'firstName'         => $address->getFirstname(),
                'lastName'          => $address->getLastname(),
                'company'           => $address->getCompany(),
                'streetAddress'     => $address->getStreet(1),
                'extendedAddress'   => $address->getStreet(2),
                'locality'          => $address->getCity(),
                'region'            => $address->getRegion(),
                'postalCode'        => $address->getPostcode(),
                'countryCodeAlpha2' => $address->getCountry(), // alpha2 is the default in magento
            );
        } else {
            return array();
        }
    }

    /**
     * If vault can be used
     * 
     * @return boolean
     */
    public function useVault()
    {
        return $this->_useVault;
    }

    /**
     * Voids transaction on cancel action
     * 
     * @param Varien_Object $payment
     * @return Braintree_Payments_Model_Paymentmethod
     */
    public function cancel(Varien_Object $payment)
    {
        try{
            $this->void($payment);
        } catch (Exception $e) {
            Mage::logException($e);
        }
        return $this;
    }

    /**
     * Format param "channel" for transaction
     * 
     * @return string
     */
    protected function _getChannel()
    {
        $edition = 'CE';
        if (Mage::getEdition() == Mage::EDITION_ENTERPRISE) {
            $edition = 'EE';
        }
        return sprintf(self::CHANNEL_NAME, $edition);
    }

    /**
     * Retrieve information from payment configuration
     *
     * @param string $field
     * @param int|string|null|Mage_Core_Model_Store $storeId
     *
     * @return mixed
     */
    public function getConfigData($field, $storeId = null)
    {
        if (null === $storeId) {
            if (Mage::app()->getStore()->isAdmin()) {
                $storeId = Mage::getSingleton('adminhtml/session_quote')->getStoreId();
            } else {
                $storeId = $this->getStore();
            }
        }
        $path = 'payment/'.$this->getCode().'/'.$field;
        return Mage::getStoreConfig($path, $storeId);
    }

    /**
     * If duplicate credit cards are allowed
     * 
     * @return boolean
     */
    protected function _allowDuplicateCards()
    {
        return $this->_allowDuplicates;
    }

    /**
     * Clones existing transaction
     * 
     * @param decimal $amount
     * @param string $transactionId
     */
    protected function _cloneTransaction($amount, $transactionId)
    {
        $this->_debug('clone-' . $transactionId . ' amount=' . $amount);
        try {
            $result = Braintree_Transaction::cloneTransaction(
                $transactionId,
                array(
                    'amount'    => $amount,
                    'options'   => array('submitForSettlement' => true)
                )
            );
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }
        $this->_debug($result);
        return $result;
    }

    /**
     * Initializes environment
     * 
     * @param int $storeId
     */
    protected function _initEnvironment($storeId)
    {
        // For compatibility with old extension versions where "development" was available
        if ($this->getConfigData('environment', $storeId) == 
            Braintree_Payments_Model_Source_Environment::ENVIRONMENT_PRODUCTION) {
            
            Braintree_Configuration::environment(Braintree_Payments_Model_Source_Environment::ENVIRONMENT_PRODUCTION);
        } else {
            Braintree_Configuration::environment(Braintree_Payments_Model_Source_Environment::ENVIRONMENT_SANDBOX);
        }
        Braintree_Configuration::merchantId($this->getConfigData('merchant_id', $storeId));
        Braintree_Configuration::publicKey($this->getConfigData('public_key', $storeId));
        Braintree_Configuration::privateKey($this->getConfigData('private_key', $storeId));
        $this->_merchantAccountId = $this->getConfigData('merchant_account_id', $storeId);
        $this->_useVault = $this->getConfigData('use_vault', $storeId);
        $this->_allowDuplicates = $this->getConfigData('duplicate_card', $storeId);        
    }

    /**
     * Processes successful authorize/clone result
     * 
     * @param Varien_Object $payment
     * @param Braintree_Result_Successful $result
     * @param decimal amount
     * @return Varien_Object
     */
    protected function _processSuccessResult($payment, $result, $amount)
    {
        $payment->setStatus(self::STATUS_APPROVED)
            ->setCcTransId($result->transaction->id)
            ->setLastTransId($result->transaction->id)
            ->setTransactionId($result->transaction->id)
            ->setIsTransactionClosed(0)
            ->setCcLast4($result->transaction->creditCardDetails->last4)
            ->setAdditionalInformation($this->_getExtraTransactionInformation($result->transaction, $payment))
            ->setAmount($amount)
            ->setShouldCloseParentTransaction(false);

        $this->_processMethodSpecificTransactionSuccess($payment, $result);

        return $payment;
    }

    /**
     * To check billing country is allowed for the payment method
     *
     * @return bool
     */
    public function canUseForCountry($country)
    {
        // For specific country, the flag will be set up as 1
        if ($this->getConfigData('allowspecific') == 1) {
            $availableCountries = explode(',', $this->getConfigData('specificcountry'));
            if (!in_array($country, $availableCountries)) {
                return false;
            }
        } else if (Mage::getModel('braintree_payments/system_config_source_country')->isCountryRestricted($country)) {
            return false;
        }
        return true;
    }

    /**
     * If customer exists in Braintree
     * 
     * @param int $customerId
     * @return boolean
     */
    public function exists($customerId)
    {
        try {
            Braintree_Customer::find($customerId);
        } catch (Braintree_Exception $e) {
            return false;
        }
        return true;        
    }

    /**
     * Delete card or PayPal account by token
     * 
     * @param string $token
     * @return Braintree_CreditCard | boolean
     */
    public function deletePaymentMethod($token)
    {
        try {
            $ret = Braintree_PaymentMethod::delete($token);
            $this->_debug($token);
            $this->_debug($ret);
            return $ret;
        } catch (Braintree_Exception $e) {
            Mage::logException($e);
            return false;
        }
    }

    /**
     * Returns stored payment method by token
     * 
     * @return Braintree_PaymentMethod | null
     */
    public function storedPaymentMethod($token)
    {
        try {
            $ret = Braintree_PaymentMethod::find($token);
            $this->_debug($token);
            $this->_debug($ret);
            return $ret;
        } catch (Braintree_Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Add method specific authorize transaction params
     * 
     * @param Varien_Object $payment $payment
     * @return array
     */
    protected function _addMethodSpecificAuthorizeTransactionParams($payment)
    {
        return array();
    }

    /**
     * Make additional manipulations with payment method token before authorize transaction execution
     * 
     * @param string $token
     * @return string
     */
    protected function _getMethodSpecificAuthorizeTransactionToken($token)
    {
        return $token;
    }

    /**
     * To make additional manipulations on transaction error
     */
    protected function _processMethodSpecificAuthorizeTransactionError()
    {
    }

    /**
     * Makes method specific manipulations after authorize/clone transaction success
     * 
     * @param Varien_Object $payment $payment
     * @param array $result
     */
    protected function _processMethodSpecificTransactionSuccess($payment, $result)
    {
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
        return array();
    }
}
