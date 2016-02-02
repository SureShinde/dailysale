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

class Braintree_Payments_Helper_Createorder extends Mage_Core_Helper_Abstract
{
    const CONFIG_PATH_VAULT         = 'payment/braintree/use_vault';
    const CONFIG_PATH_MERCHANT_ID   = 'payment/braintree/merchant_id';

    /**
     * Returns customer credit cards if applicable
     * 
     * @return Braintree_Customer | boolean
     */
    public function getCustomerCards()
    {
        $session = Mage::getSingleton('adminhtml/session_quote');
        $applicableCards = array();
        if (Mage::getStoreConfig(self::CONFIG_PATH_VAULT, $session->getStoreId())) {
            $storedCards = false;
            if ($session->getCustomerId()) {
                $customerId = Mage::helper('braintree_payments')->generateCustomerId(
                    $session->getCustomerId(), $session->getQuote()->getCustomerEmail()
                );
                try {
                    $storedCards = Braintree_Customer::find($customerId)->creditCards;
                } catch (Braintree_Exception $e) {
                    Mage::logException($e);
                }
            }
            if ($storedCards) {
                $country = $session->getQuote()->getBillingAddress()->getCountryId();
                $types = Mage::getModel('braintree_payments/creditcard')->getApplicableCardTypes($country);
                $applicableCards = array();
                foreach ($storedCards as $card) {
                    if (in_array(Mage::helper('braintree_payments')->getCcTypeCodeByName($card->cardType), $types)) {
                        $applicableCards[] = $card;
                    }
                }
                
            }
        }
        return $applicableCards;
    }
    
    /**
     * Returns merchant id
     * 
     * @return string
     */
    public function getMerchantId()
    {
        return $this->jsQuoteEscape(
            Mage::getStoreConfig(
                self::CONFIG_PATH_MERCHANT_ID,
                Mage::getSingleton('adminhtml/session_quote')->getStoreId()
            )
        );
    }
}
