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

class Braintree_Payments_Block_Creditcard_Threedsecure extends Mage_Core_Block_Template
{
    /**
     * Returns nonce if applicable
     * 
     * return string
     */
    public function getNonce()
    {
        return Mage::getSingleton('checkout/session')->getQuote()->getPayment()->getAdditionalInformation('nonce');
    }
}
