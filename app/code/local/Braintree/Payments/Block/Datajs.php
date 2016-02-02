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

class Braintree_Payments_Block_Datajs extends Mage_Core_Block_Template
{
    const JS_SRC_CONFIG_PATH        = 'payment/braintree/data_js';
    const MERCHANT_ID_CONFIG_PATH   = 'payment/braintree/merchant_id';
    
    /**
     * Returns data.js script source from store config
     * 
     * @return string
     */
    public function getJsSrc()
    {
        return Mage::getStoreConfig(self::JS_SRC_CONFIG_PATH);
    }
    
    /**
     * Returns merchant_id from store config
     * 
     * @return string
     */
    public function getMerchantId()
    {
        return $this->jsQuoteEscape(Mage::getStoreConfig(self::MERCHANT_ID_CONFIG_PATH));
    }

    /**
     * If advanced fraud protection is disabled do not setup JavaScript
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!Mage::helper('braintree_payments')->isAdvancedFraudProtectionEnabled()) {
            return '';
        }
        return parent::_toHtml();
    }
}
