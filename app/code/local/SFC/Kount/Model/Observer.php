<?php
// @codingStandardsIgnoreStart
/**
 * StoreFront Consulting Kount Magento Extension
 *
 * PHP version 5
 *
 * @category  SFC
 * @package   SFC_Kount
 * @copyright 2009-2015 StoreFront Consulting, Inc. All Rights Reserved.
 *
 */
// @codingStandardsIgnoreEnd

/**
 * Class SFC_Kount_Model_Observer
 *
 * Catch Magento events and distribute them as appropriate for pre or post authorization workflow.
 */
class SFC_Kount_Model_Observer
{

    /**
     * Order payment placed start
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function onSalesOrderPaymentPlaceStart($observer)
    {
        if(Mage::app()->getStore()->isAdmin()) {
            // Increment Kount Session ID for admin orders
            $session = Mage::getSingleton('kount/session');
            $session->incrementKountSessionId();
        }    
        
        // Log
        Mage::log('==== sales_order_payment_place_start Callback ====' . "\n" . '==== Place Order ====', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment = $observer->getEvent()->getData('payment');

        // Skip paypal standard
        if ($payment->getMethodInstance()->getCode() == 'paypal_standard')
            return;

        // Use Kount for this transaction?
        if ($this->useKountForThisTransaction($payment->getMethod(), $payment->getOrder()->getStoreId())) {
            if($workflowHelper = $this->workflowHelper())
                $workflowHelper->onSalesOrderPaymentPlaceStart($payment);
        }

        return $this;
    }
    
    public function onCheckoutSuccessAction($observer)
    {
        Mage::log('==== Checkout Success ====', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        $orderIds = $observer->getEvent()->getData('order_ids');
        foreach($orderIds as $orderId) {
            Mage::log('Order ID '. $orderId, Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
            
            $order = Mage::getModel('sales/order')->load($orderId);
            if($order->getData('kount_ris_response') == SFC_Kount_Helper_Ris::RESPONSE_DECLINE) {
                Mage::log('Handle Decline', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
                Mage::helper('kount/order')->handleDecline($order);
            }
        }
    }
    
    /**
     * Order payment placed end
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function onSalesOrderPaymentPlaceEnd($observer)
    {
        // Log
        Mage::log('==== sales_order_payment_place_end Callback ====' . "\n" . '==== Done Placing Order ====', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment = $observer->getEvent()->getData('payment');
            
        // force post-auth for paypal express
        if ($payment->getMethodInstance()->getCode() == 'paypal_express') {
            if($workflowHelper = $this->workflowHelper())
                $workflowHelper->onCheckoutSubmitAllAfter($payment->getOrder());
        }

        return $this;
    }
    

    /**
     * Order failure
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function onSalesOrderServiceQuoteSubmitFailure($observer)
    {
        // Log
        Mage::log('==== sales_model_service_quote_submit_failure Callback ====' . "\n" . '==== Transaction Failed with Gateway ====', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

        // Get order from observer
        $order = $observer->getEvent()->getData('order');

        // Skip paypal standard
        if ($order->getPayment()->getMethodInstance()->getCode() == 'paypal_standard')
            return;

        // Use Kount for this transaction?
        if ($this->useKountForThisTransaction($order->getPayment()->getMethod(), $order->getStoreId())) {
            if($workflowHelper = $this->workflowHelper())
                $workflowHelper->onSalesOrderServiceQuoteSubmitFailure($order);
        }

        return $this;
    }

    /**
     * Order payment placed start
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function onCheckoutSubmitAllAfter($observer)
    {
        // Log
        Mage::log('==== checkout_submit_all_after Callback ====' . "\n" . '==== Attempting Transaction with Gateway ====', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

        // Get data from event
        $orders = array();
        if ($observer->getEvent()->hasData('orders')) {
            $orders = $observer->getEvent()->getData('orders');
        }
        else if ($observer->getEvent()->hasData('order')) {
            $orders[] = $observer->getEvent()->getData('order');
        }
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = $observer->getEvent()->getData('quote');

        /** @var Mage_Sales_Model_Order $order */
        foreach ($orders as $order) {
            // Skip paypal standard
            if ($order->getPayment()->getMethodInstance()->getCode() == 'paypal_standard')
                continue;
                
            // Use Kount for this transaction?
            if ($this->useKountForThisTransaction($order->getPayment()->getMethod(), $order->getStoreId())) {
                if($workflowHelper = $this->workflowHelper())
                    $workflowHelper->onCheckoutSubmitAllAfter($order);
            }
        }

        return $this;
    }

    protected function useKountForThisTransaction($paymentMethodCode, $storeId = null)
    {
        /** @var SFC_Kount_Helper_Data $helper */
        $helper = Mage::helper('kount');
        /** @var SFC_Kount_Helper_PaymentMethod $paymentMethodHelper */
        $paymentMethodHelper = Mage::helper('kount/paymentMethod');

        // Kount enabled for this store?
        // Should we skip processing because this is the admin panel?
        if (Mage::getStoreConfig('kount/account/enabled', $storeId) == '1' && !$helper->shouldSkipAdminProcessing()) {
            if (!$paymentMethodHelper->methodIsDisabledForKount($paymentMethodCode, $storeId)) {
                return true;
            }
            else {
                Mage::log('Kount disabled for payment method: ' . $paymentMethodCode, Zend_Log::INFO,
                    SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
            }
        }

        return false;
    }

    /**
     * @return SFC_Kount_Helper_Workflow_Interface|null
     */
    protected function workflowHelper()
    {
        // Kount enabled for this store?
        if (Mage::getStoreConfig('kount/account/enabled') == '1') {
            // Check workflow
            switch (Mage::getStoreConfig('kount/workflow/workflow_mode')) {
                case SFC_Kount_Model_Source_WorkflowMode::MODE_PRE_AUTH:
                    return Mage::helper('kount/workflow_preAuth');

                case SFC_Kount_Model_Source_WorkflowMode::MODE_POST_AUTH:
                    return Mage::helper('kount/workflow_postAuth');
            }
        }

        // Otherwise, return null
        return null;
    }

}
