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

class Braintree_Payments_Model_Rewrite_Sales_Order_Payment extends Mage_Sales_Model_Order_Payment
{
    const REGISTRY_KEY_CANCEL = 'braintree_cancel';

    /**
     * This function is overriden because Magento will consider a transaction for voiding only if it is an authorization
     * Braintree allows voiding captures too
     *
     * Lookup an authorization transaction using parent transaction id, if set
     * @return Mage_Sales_Model_Order_Payment_Transaction|false
     */
    public function getAuthorizationTransaction()
    {
        if ($this->getMethodInstance()->getCode() == Braintree_Payments_Model_Creditcard::PAYMENT_METHOD_CODE) {
            $invoice = Mage::registry('current_invoice');
            $collection = false;
            if ($invoice && $invoice->getId()) {
                $transactionId = Mage::helper('braintree_payments')
                    ->clearTransactionId($invoice->getTransactionId());
                $collection = Mage::getModel('sales/order_payment_transaction')->getCollection()
                    ->addFieldToFilter('txn_id', array('eq' => $transactionId));
                if ($collection->getSize() < 1) {
                    $collection = false;
                }
            } else if (($order = Mage::registry('current_order')) && $order->getId() && $order->hasInvoices() ) {
                if ((boolean)Mage::registry(self::REGISTRY_KEY_CANCEL)) {
                    $collection = Mage::getModel('sales/order_payment_transaction')->getCollection()
                        ->addFieldToFilter('payment_id', array('eq' => $this->getId()))
                        ->addFieldToFilter(
                            'txn_type', 
                            array('eq' => Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE)
                        );
                    if ($collection->getSize() >= 1) {
                        return false;
                    }
                } else {
                    return false;
                }
            }
            if (!$collection) {
                $collection = Mage::getModel('sales/order_payment_transaction')->getCollection()
                    ->setOrderFilter($this->getOrder())
                    ->addPaymentIdFilter($this->getId())
                    ->setOrder('created_at', Varien_Data_Collection::SORT_ORDER_DESC)
                    ->setOrder('transaction_id', Varien_Data_Collection::SORT_ORDER_DESC);
            }
            foreach ($collection as $txn) {
                $txn->setOrderPaymentObject($this);
                $this->_transactionsLookup[$txn->getTxnId()] = $txn;
                $txn->setParentId($txn->getId());
                return $txn;
            }
        } else {
            return parent::getAuthorizationTransaction();
        }
    }
    /**
     * Order cancellation hook for payment method instance
     * Adds void transaction if needed
     * @return Mage_Sales_Model_Order_Payment
     */
    public function cancel()
    {
        if ($this->getMethodInstance()->getCode() == Braintree_Payments_Model_Creditcard::PAYMENT_METHOD_CODE) {
            Mage::register(self::REGISTRY_KEY_CANCEL, true);
        }
        return parent::cancel();
    }    
}
