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

class Braintree_Payments_Block_Creditcard_Form extends Mage_Payment_Block_Form_Cc
{
    /**
     * Internal constructor. Set template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('braintree/creditcard/form.phtml');
    }

    /**
     * Set quote and payment
     * 
     * @return Braintree_Payments_Block_Form
     */
    public function setMethodInfo()
    {
        $payment = Mage::getSingleton('checkout/session')
            ->getQuote()
            ->getPayment();
        $this->setMethod($payment->getMethodInstance());

        return $this;
    }

    /**
     * Returns applicable stored cards
     * 
     * @return array
     */
    public function getStoredCards()
    {
        $model = Mage::getModel('braintree_payments/creditcard');
        $storedCards = $model->currentCustomerStoredCards();
        $country = Mage::getSingleton('checkout/session')->getQuote()->getBillingAddress()->getCountryId();
        $cardTypes = $model->getApplicableCardTypes($country);
        $applicableCards = array();
        foreach ($storedCards as $card) {
            if (in_array(Mage::helper('braintree_payments')->getCcTypeCodeByName($card->cardType), $cardTypes)) {
                $applicableCards[] = $card;
            }
        }
        return $applicableCards;
    }

    /**
     * Returns the credit cart types that we have images for. The only one that should be missing is "Other"
     *
     * @return array
     */
    public function getCardTypesWithImages()
    {
        return array('VI', 'DI', 'MC', 'AE', 'JCB');
    }

    /**
     * Retrieve availables credit card types
     *
     * @return array
     */
    public function getCcAvailableTypes()
    {
        if (Mage::app()->getStore()->isAdmin()) {
            $country = Mage::getSingleton('adminhtml/session_quote')->getQuote()->getBillingAddress()->getCountryId();
        } else {
            $country = Mage::getSingleton('checkout/session')->getQuote()->getBillingAddress()->getCountryId();
        }
        $applicableTypes = Mage::getModel('braintree_payments/creditcard')->getApplicableCardTypes($country);
        $types = $this->_getConfig()->getCcTypes();
        foreach ($types as $code => $name) {
            if (!in_array($code, $applicableTypes)) {
                unset($types[$code]);
            }
        }
        return $types;
    }

    /**
     * If card can be saved for further use
     * 
     * @return boolean
     */
    public function canSaveCard()
    {
        if (Mage::getModel('braintree_payments/creditcard')->useVault() && 
            (Mage::getSingleton('customer/session')->isLoggedIn() || 
            Mage::getSingleton('checkout/type_onepage')->getCheckoutMethod() 
                == Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER)) {
            
            return true;
        }
        return false;
    }

    /**
     * Returns the credit cart types that we have 3D Secure logo for. Applicable to Visa, Mastercard and JCB
     *
     * @return array
     */
    public function getCardTypesWith3DSecureImages()
    {
        return array('VI', 'MC', 'JCB');
    }
}
