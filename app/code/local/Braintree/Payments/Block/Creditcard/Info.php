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

class Braintree_Payments_Block_Creditcard_Info extends Mage_Payment_Block_Info
{
    /**
     * Return credit cart type
     * 
     * @return string
     */
    protected function getCcTypeName()
    {
        $types = Mage::getSingleton('payment/config')->getCcTypes();
        $ccType = $this->getInfo()->getCcType();
        if (isset($types[$ccType])) {
            return $types[$ccType];
        } else {
            return Mage::helper('braintree_payments')->__('Stored Card');
        }
    }

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
        if ($ccType = $this->getCcTypeName()) {
            $data[Mage::helper('braintree_payments')->__('Credit Card Type')] = $ccType;
        }
        if ($info->getCcLast4()) {
            $data[Mage::helper('braintree_payments')->__('Credit Card Number')] = 
                sprintf('xxxx-%s', $info->getCcLast4());
        }
        if (Mage::app()->getStore()->isAdmin() && $info->getAdditionalInformation()) {
            foreach ($info->getAdditionalInformation() as $field => $value) {
                $beautifiedFieldName = ucwords(trim(preg_replace('/(?<=\\w)(?=[A-Z])/', " $1", $field)));
                $data[Mage::helper('braintree_payments')->__($beautifiedFieldName)] = $value;
            }
        }
        return $transport->setData(array_merge($data, $transport->getData()));
    }
}
