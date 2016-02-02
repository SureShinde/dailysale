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

class Braintree_Payments_Model_Source_Failed3dsecure
{
    const CREATE_ANYWAY = 1;
    const ASK_ANOTHER   = 2;
    const USE_THRESHOLD = 3;
    
    public function toOptionArray()
    {
        $helper = Mage::helper('braintree_payments');
        return array(
            array('value' => self::CREATE_ANYWAY,   'label' => $helper->__('Create transaction anyway')),
            array('value' => self::ASK_ANOTHER,     'label' => $helper->__('Ask for another form of payment')),
            array('value' => self::USE_THRESHOLD,   'label' => $helper->__('Use Threshold Amount')),
        );
    }
}
