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

class Braintree_Payments_Model_Paypal extends Braintree_Payments_Model_Paymentmethod
{
    const PAYMENT_METHOD_CODE       = 'braintree_paypal';

    protected $_formBlockType       = 'braintree_payments/paypal_form';
    protected $_infoBlockType       = 'braintree_payments/paypal_info';

    protected $_code                    = 'braintree_paypal';
    protected $_isGateway               = true;
    protected $_canAuthorize            = true;
    protected $_canCapture              = true;
    protected $_canCapturePartial       = false;
    protected $_canRefund               = true;
    protected $_canVoid                 = true;
    protected $_canUseInternal          = false;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = false;
    protected $_canSaveCc               = false;
    protected $_canRefundInvoicePartial = true;
    protected $_merchantAccountId       = '';
    protected $_useVault                = false;

    protected $_specificConfigFields = array(
        'active',
        'title',
        'sort_order',
        'payment_action',
        'order_status',
        'allowspecific',
        'specificcountry',
        'debug'
    );

    protected $_allowedCurrencies = array("USD", "EUR", "GBP", "CAD", "AUD", "DKK", "NOK", "PLN", "SEK", "CHF", "TRY");

    protected $_countriesWithCustomRegionProcessing = array('US');

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
        if (in_array($field, $this->_specificConfigFields)) {
            $prefix = 'paypal_';
        } else {
            $prefix = '';
        }
        $path = 'payment/' . Braintree_Payments_Model_Creditcard::PAYMENT_METHOD_CODE . '/' . $prefix . $field;
        return Mage::getStoreConfig($path, $storeId);
    }

    /**
     * Validate payment method information object
     *
     * @param   Mage_Payment_Model_Info $info
     * @return  Mage_Payment_Model_Abstract
     */
    public function validate()
    {
        // Migrated from parent-parent model
        $paymentInfo = $this->getInfoInstance();
        if ($paymentInfo instanceof Mage_Sales_Model_Order_Payment) {
            $billingCountry = $paymentInfo->getOrder()->getBillingAddress()->getCountryId();
        } else {
            $billingCountry = $paymentInfo->getQuote()->getBillingAddress()->getCountryId();
        }
        if (!$this->canUseForCountry($billingCountry)) {
            Mage::throwException(
                Mage::helper('braintree_payments')->__('Selected payment type is not allowed for billing country.')
            );
        }
        if (!$this->isCurrencyAllowed()) {
            Mage::throwException(
                Mage::helper('braintree_payments')->__('Selected payment type is not allowed for store currency.')
            );
        }
        return $this;
    }

    /**
     * Assign corresponding data
     * 
     * @return Braintree_Payments_Model_Paymentmethod
     */
    public function assignData($data)
    {
        parent::assignData($data);
        $infoInstance = $this->getInfoInstance();
        $infoInstance->setAdditionalInformation(array('nonce' => $data->getNonce()));
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
        $braintreeAddress = parent::_toBraintreeAddress($address);
        if ($braintreeAddress && in_array($address->getCountry(), $this->_countriesWithCustomRegionProcessing)) {
            $collection = Mage::getResourceModel('directory/region_collection')
                ->addCountryFilter($address->getCountry())
                ->setPageSize(1)
                ->setCurPage(1)
                ->addFieldToFilter('main_table.region_id', $address->getRegionId())
                ->removeAllFieldsFromSelect()
                ->addFieldToSelect('code');
            if ($collection->getSize()) {
                $braintreeAddress['region'] = $collection->getFirstItem()->getCode();
            }
        }
        return $braintreeAddress;
    }

    /**
     * Check whether payment method is applicable to quote
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param int|null $checksBitMask
     * @return bool
     */
    public function isApplicableToQuote($quote, $checksBitMask)
    {
        if (!parent::isApplicableToQuote($quote, $checksBitMask)) {
            return false;
        }
        if (!Mage::helper('braintree_payments')->areCredentialCorrect()) {
            return false;
        }
        return true;
    }

    /**
     * If store currency is allowed
     * 
     * @return boolean
     */
    public function isCurrencyAllowed()
    {
        if (!in_array(Mage::getStoreConfig('currency/options/base'), $this->_allowedCurrencies) ) {
            return false;
        }
        return true;
    }

    /**
     * Makes method specific manipulations after authorize/clone transaction success
     * 
     * @param Varien_Object $payment $payment
     * @param array $result
     */
    protected function _processMethodSpecificTransactionSuccess($payment, $result)
    {
        if (isset($result->transaction->paypal['token']) && $result->transaction->paypal['token']) {
            $token = $result->transaction->paypal['token'];
            $payment->setTransactionAdditionalInfo('token', $token);
        }
    }
}
