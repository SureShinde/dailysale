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

class Braintree_Payments_Block_Paypal_Info extends Mage_Payment_Block_Info
{
    /**
     * Prepare information specific to current payment method
     * 
     * @param null | array $transport
     * @return Varien_Object
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        $transport = parent::_prepareSpecificInformation($transport);
        $data = array();
        $info = $this->getInfo();
        if (Mage::app()->getStore()->isAdmin() && $info->getAdditionalInformation()) {
            foreach ($info->getAdditionalInformation() as $field => $value) {
                $beautifiedFieldName = ucwords(trim(preg_replace('/(?<=\\w)(?=[A-Z])/', " $1", $field)));
                $data[Mage::helper('braintree_payments')->__($beautifiedFieldName)] = $value;
            }
        }
        return $transport->setData(array_merge($data, $transport->getData()));
    }
}
