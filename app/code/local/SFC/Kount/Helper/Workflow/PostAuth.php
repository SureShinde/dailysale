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
 * Class SFC_Kount_Helper_Workflow_PostAuth
 *
 * Implement Post-Authorization fraud review workflow
 *
 */
class SFC_Kount_Helper_Workflow_PostAuth extends Mage_Core_Helper_Abstract implements SFC_Kount_Helper_Workflow_Interface
{

    /**
     * @param Mage_Sales_Model_Order_Payment $payment
     */
    public function onSalesOrderPaymentPlaceStart(Mage_Sales_Model_Order_Payment $payment)
    {   
        Mage::log('==== Before Magento Order Placement ====', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        // Grab 'cc_number' from $payment and store it in memory
        // We do this because in normal pay methods (ie built-in Authorize.Net), Magento comes back and obfuscates the card number
        // after it has been sent to processor.  So we save it so that in later, post-order, events we still have access.
        // NOTE: Storing card number in memory here is no more than has already been done, thus this action doesn't change PCI scope
        Mage::register('kount_cc_number', $payment->getData('cc_number'));
    }

    /**
     * When order fails, optionally notify Kount RIS
     *
     * @param Mage_Sales_Model_Order $order
     */
    public function onSalesOrderServiceQuoteSubmitFailure(Mage_Sales_Model_Order $order)
    {
        /** @var SFC_Kount_Helper_Ris $risHelper */
        $risHelper = Mage::helper('kount/ris');

        if (Mage::getStoreConfig('kount/workflow/notify_processor_decline') == '1') {
            // Log
            Mage::log('Order failed, notifying Kount RIS.', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

            // Build and send RIS inquiry
            $request = $risHelper->buildInquiryFromOrder($order, Mage::registry('kount_cc_number'), 'D', 'N');
            $response = $risHelper->sendRequest($request);

        }
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @throws \Exception
     */
    public function onCheckoutSubmitAllAfter(Mage_Sales_Model_Order $order)
    {
        Mage::log('==== After Magento Order Placement ====', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        /** @var SFC_Kount_Helper_Ris $risHelper */
        $risHelper = Mage::helper('kount/ris');
        /** @var SFC_Kount_Helper_Order $orderHelper */
        $orderHelper = Mage::helper('kount/order');

        // Log
        Mage::log('Implementing Post-Authorization Workflow for order:', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        Mage::log("Order Id: {$order->getIncrementId()}", Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        Mage::log("Order Store Id: {$order->getStoreId()}", Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

        // Build and send RIS inquiry
        $request = $risHelper->buildInquiryFromOrder($order, Mage::registry('kount_cc_number'));
        $response = $risHelper->sendRequest($request);

        // Check result / response
        if ($response instanceof Kount_Ris_Response) {
            if($response->getTransactionId()) {
                // Save response on order
                $orderHelper->addRisResponseFieldsToOrder($order, $response);
                $order->save();
                $order->getPayment()->save();
                // Request went through, check response
                $auto = $risHelper->parseInquiryResponse($response);
                if ($auto == SFC_Kount_Helper_Ris::RESPONSE_DECLINE) {
                    // Transaction was declined
                    // We should attempt to Refund / Void / Cancel order
                    //$orderHelper->handleDecline($order);
                }
                else if ($auto == SFC_Kount_Helper_Ris::RESPONSE_REVIEW || $auto == SFC_Kount_Helper_Ris::RESPONSE_ESCALATE) {
                    // Transaction should be flagged for review
                    $orderHelper->handleReview($order);
                }
                else {
                    // Otherwise consider transaction approved
                    // Don't alter order, let it go through as normal
                }
            } else {
                Mage::log('No transaction_id in response, skipping Update.', Zend_Log::WARN, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
            }
        }
        else {
            // RIS Request failed
            // Treat this as a decline
            $orderHelper->handleDecline($order);
        }
    }

}
