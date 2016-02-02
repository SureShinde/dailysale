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

class Braintree_Payments_Model_Source_CaptureAction
{
    const CAPTURE_ON_INVOICE    = 'invoice';
    const CAPTURE_ON_SHIPMENT   = 'shipment';

    /**
     * Possible actions to capture
     * 
     * @return array
     */
    public function toOptionArray()
    {
        $helper = Mage::helper('braintree_payments');
        return array(
            array(
                'value' => self::CAPTURE_ON_INVOICE,
                'label' => $helper->__('Invoice')
            ),
            array(
                'value' => self::CAPTURE_ON_SHIPMENT,
                'label' => $helper->__('Shipment')
            ),            
        );
    }
}
