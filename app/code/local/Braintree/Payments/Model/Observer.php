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

class Braintree_Payments_Model_Observer
{
    const CONFIG_PATH_CAPTURE_ACTION    = 'payment/braintree/capture_action';
    const CONFIG_PATH_PAYMENT_ACTION    = 'payment/braintree/payment_action';
    
    /**
     * If it's configured to capture on shipment - do this
     * 
     * @param Varien_Event_Observer $observer
     * @return Braintree_Payments_Model_Observer
     */
    public function processBraintreePayment(Varien_Event_Observer $observer)
    {
        $shipment = $observer->getEvent()->getShipment();
        $order = $shipment->getOrder();
        if ($order->getPayment()->getMethod() == Braintree_Payments_Model_Creditcard::PAYMENT_METHOD_CODE 
            && $order->canInvoice() && $this->_shouldInvoice()) {

            $qtys = array(); 
            foreach ($shipment->getAllItems() as $shipmentItem) {
                $qtys[$shipmentItem->getOrderItem()->getId()] = $shipmentItem->getQty();
            }
            foreach ($order->getAllItems() as $orderItem) {
                if (!array_key_exists($orderItem->getId(), $qtys)) {
                    $qtys[$orderItem->getId()] = 0;
                }
            }
            $invoice = $order->prepareInvoice($qtys);
            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
            $invoice->register();
            Mage::getModel('core/resource_transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder())
                ->save();
        }
        return $this;
    }

    /**
     * If it's configured to capture on each shipment
     * 
     * @return boolean
     */
    protected function _shouldInvoice()
    {
        return ((Mage::getStoreConfig(self::CONFIG_PATH_PAYMENT_ACTION) == 
            Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE) && 
            (Mage::getStoreConfig(self::CONFIG_PATH_CAPTURE_ACTION) == 
            Braintree_Payments_Model_Source_CaptureAction::CAPTURE_ON_SHIPMENT));
    }

    /**
     * Delete Braintree customer when Magento customer is deleted
     * 
     * @param Varien_Event_Observer $observer
     * @return Braintree_Payments_Model_Observer
     */
    public function deleteBraintreeCustomer(Varien_Event_Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        $braintree = Mage::getModel('braintree_payments/creditcard');
        $customerId = Mage::helper('braintree_payments')->generateCustomerId($customer->getId(), $customer->getEmail());
        if ($braintree->exists($customerId)) {
            $braintree->deleteCustomer($customerId);
        }
        return $this;
    }

    /**
     * Adds flag to payment that multishipping checkout is in use
     * 
     * @param Varien_Event_Observer $observer
     * @return Braintree_Payments_Model_Observer
     */
    public function processBraintreeMultishipping(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if ($order->getPayment()->getMethod() == Braintree_Payments_Model_Creditcard::PAYMENT_METHOD_CODE) {
            $order->getPayment()->setIsMultishipping(true);
        }
        return $this;
    }

    /**
     * Deletes saved card after multishipping checkout if customer doesn't want to save it
     * 
     * @param Varien_Event_Observer $observer
     * @return Braintree_Payments_Model_Observer
     */
    public function deleteCardBraintreeMultishipping(Varien_Event_Observer $observer)
    {
        $token = Mage::getSingleton('checkout/session')->getBraintreeDeleteCard();
        if ($token) {
            Mage::getModel('braintree_payments/creditcard')->deletePaymentMethod($token);
        }
        return $this;
    }

    /**
     * Check if admin notification regarding double shortcuts should be added
     * 
     * @param Varien_Event_Observer $observer
     */
    public function addShortcutsNotification(Varien_Event_Observer $observer)
    {
        if (Mage::getStoreConfigFlag('payment/braintree/active') && 
            Mage::getStoreConfigFlag('payment/paypal_express/active')) {

            $helper = Mage::helper('braintree_payments');
            $title = $helper->__('You currently have two similar PayPal integrations enabled');
            $description = $helper->__(
                'You currently have PayPal Express Checkout enabled through your native Magento integration.' . 
                'To get the benefits of a single report of all payments within Braintree’s admin panel, ' .
                'enable PayPal Express Checkout in this Braintree extension and disable it in your native integration'
            );
            Mage::getModel('adminnotification/inbox')->addMajor(
                $title,
                $description,
                '',
                true
            );
        }
    }

    /**
     * Check if braintree methods are available. For compatibility with old Magento versions
     * 
     * @param Varien_Event_Observer $observer
     */
    public function checkBraintreeMethodsAvailability(Varien_Event_Observer $observer)
    {
        $result = $observer->getEvent()->getResult();
        if (!$result->isAvailable) {
            return;
        }
        $method = $observer->getEvent()->getMethodInstance();
        if ($method->getCode() == Braintree_Payments_Model_Paypal::PAYMENT_METHOD_CODE) {
            if (!Mage::getModel('braintree_payments/paypal')->isCurrencyAllowed()) {
                $result->isAvailable = false;
            } else {
                if (!Mage::helper('braintree_payments')->areCredentialCorrect()) {
                    $result->isAvailable = false;
                }
            }
        } else if ($method->getCode() == Braintree_Payments_Model_Creditcard::PAYMENT_METHOD_CODE) {
            if (!Mage::helper('braintree_payments')->areCredentialCorrect()) {
                $result->isAvailable = false;
            }
        }
    }

    /**
     * Delete Braintree customer when Magento customer is deleted
     * 
     * @param Varien_Event_Observer $observer
     * @return Braintree_Payments_Model_Observer
     */
    public function updateBraintreeCustomerId(Varien_Event_Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        $oldEmail = $customer->getOrigData('email');
        $newEmail = $customer->getEmail();
        if ($oldEmail && ($newEmail != $oldEmail)) {
            $helper = Mage::helper('braintree_payments');
            $customerId = $helper->generateCustomerId($customer->getId(), $oldEmail);
            $newId = $helper->generateCustomerId($customer->getId(), $newEmail);
            Mage::getModel('braintree_payments/creditcard')->updateCustomerId($customerId, $newId);
        }
        return $this;
    }
}
