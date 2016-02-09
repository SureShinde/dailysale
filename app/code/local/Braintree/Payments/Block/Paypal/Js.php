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

class Braintree_Payments_Block_Paypal_Js extends Mage_Core_Block_Template
{
    protected $_model = false;

    protected $_allowedLocales = array(
        "en_au",
        "de_at",
        "en_be",
        "en_ca",
        "da_dk",
        "fr_fr",
        "de_de",
        "en_gb",
        "it_it",
        "nl_nl",
        "no_no",
        "pl_pl",
        "es_es",
        "sv_se",
        "en_ch",
        "tr_tr",
        "en_us",
    );

    /**
     * Returns quote currency code
     * 
     * @return string
     */
    public function getCurrencyCode()
    {
        return Mage::getSingleton('checkout/session')->getQuote()->getBaseCurrencyCode();
    }

    /**
     * Returns store locale code
     * 
     * @return string
     */
    public function getLocale()
    {
        $currentLocale = strtolower(Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE));
        if (in_array($currentLocale, $this->_allowedLocales)) {
            return $currentLocale;
        }
        return Mage_Core_Model_Locale::DEFAULT_LOCALE;
    }

    /**
     * If Braintree PayPal payment method is used during checkout
     * 
     * @return boolean
     */
    public function isBraintreePayPalMethod()
    {
        return Mage::getSingleton('checkout/session')->getQuote()->getPayment()->getMethod() 
            == Braintree_Payments_Model_Paypal::PAYMENT_METHOD_CODE;
    }

    /**
     * Internal constructor, that is called from real constructor
     *
     */
    protected function _construct()
    {
        parent::_construct();
        // Model has to be initialized to get token from Braintree
        $this->_getModel();
    }

    /**
     * Initializes payment method model for further use
     * 
     * @return Braintree_Payments_Model_Paypal
     */
    protected function _getModel()
    {
        if (!$this->_model) {
            $this->_model = Mage::getModel('braintree_payments/paypal');
        }
        return $this->_model;
    }

    /**
     * Returns PayPal Shortcut order review page URL
     * 
     * return string
     */
    public function getReviewUrl()
    {
        return $this->getUrl('braintree/checkout/review'); 
    }

    /**
     * If PayPal payment method can be used
     * 
     * @return boolean
     */
    public function isApplicable()
    {
        $model = $this->_getModel();
        if (($model->getConfigData('active') == 1) && $this->_isShortcutEnabled()
            && $model->isCurrencyAllowed() && Mage::helper('braintree_payments')->areCredentialCorrect()) {

            return true;
        }
        return false;
    }

    /**
     * If shortcut is enabled in config
     * 
     * @return boolean
     */
    protected function _isShortcutEnabled()
    {
        $storeConfigPath = '';
        if (in_array($this->getShortcutType(), array('top', 'bottom', 'minicart'))) {
            $storeConfigPath = 'shortcut_shopping_cart';
        }
        return $storeConfigPath ? $this->_getModel()->getConfigData($storeConfigPath) : false;
    }

    /**
     * If it is allowed to add/select shipping address on PayPal side
     * 
     * @return integer
     */
    public function isShippingAddressSelectionAllowed()
    {
        return (int)$this->_getModel()->getConfigData('shortcut_shipping_address');
    }
}
