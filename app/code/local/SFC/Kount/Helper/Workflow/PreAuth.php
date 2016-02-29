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
 * Class SFC_Kount_Helper_Workflow_PreAuth
 *
 * Implement Pre-Authorization fraud review workflow
 *
 */
class SFC_Kount_Helper_Workflow_PreAuth extends Mage_Core_Helper_Abstract implements SFC_Kount_Helper_Workflow_Interface
{

    /**
     * @param Mage_Sales_Model_Order_Payment $payment
     */
    public function onSalesOrderPaymentPlaceStart(Mage_Sales_Model_Order_Payment $payment)
    {
        Mage::log('==== Before Magento Order Placement ====', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        /** @var SFC_Kount_Helper_Ris $risHelper */
        $risHelper = Mage::helper('kount/ris');
        /** @var SFC_Kount_Helper_Order $orderHelper */
        $orderHelper = Mage::helper('kount/order');

        // Get order from payment
        $order = $payment->getOrder();

        // Log
        Mage::log('Implementing Pre-Authorization Workflow for order:', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        Mage::log("Order Id: {$order->getIncrementId()}", Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        Mage::log("Order Store Id: {$order->getStoreId()}", Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
            
        // Build and send RIS inquiry            
        $request = $risHelper->buildInquiryFromOrder($order, $payment->getData('cc_number'));
        $response = $risHelper->sendRequest($request);

        // Now save response in memory.  We will handle it later after order saved.
        Mage::register('kount_ris_response', $response);

        // Check result / response
        if ($response instanceof Kount_Ris_Response) {
            // Request went through, check response
            $auto = $risHelper->parseInquiryResponse($response);
            if ($auto == SFC_Kount_Helper_Ris::RESPONSE_DECLINE) {
                // Transaction was declined
                // We should throw an exception and thus block the order
                Mage::throwException(SFC_Kount_Helper_Ris::RIS_MESSAGE_REJECTED);
            }
        }
        else {
            // RIS Request failed
            // We should throw an exception and thus block the order
            Mage::throwException(SFC_Kount_Helper_Ris::RIS_MESSAGE_REJECTED);
        }
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

        // Log
        Mage::log('Order failed, sending update to Kount RIS.', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

        // Pull response from memory
        $inquiryResponse = Mage::registry('kount_ris_response');
        // Check result / response
        if ($inquiryResponse instanceof Kount_Ris_Response) {
            // Send update to RIS
            $updateRequest = $risHelper->buildUpdate($inquiryResponse->getTransactionId(), false);
            $updateResponse = $risHelper->sendRequest($updateRequest);
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

        // Pull response from memory
        $inquiryResponse = Mage::registry('kount_ris_response');
        // Check result / response
        if ($inquiryResponse instanceof Kount_Ris_Response) {
            if($inquiryResponse->getTransactionId()) {
                // Save response on order
                $orderHelper->addRisResponseFieldsToOrder($order, $inquiryResponse);
                $order->save();
                $order->getPayment()->save();
                // Log
                Mage::log('Order succeeded, sending update to Kount RIS.', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
                // Send update to RIS
                $updateRequest = $risHelper->buildUpdate($inquiryResponse->getTransactionId(), true);
                $updateResponse = $risHelper->sendRequest($updateRequest);
                // Original Inquiry Request went through, check response
                $auto = $risHelper->parseInquiryResponse($inquiryResponse);
                if ($auto == SFC_Kount_Helper_Ris::RESPONSE_REVIEW || $auto == SFC_Kount_Helper_Ris::RESPONSE_ESCALATE) {
                    // Transaction should be flagged for review
                    $orderHelper->handleReview($order);
                }
            } else {
                Mage::log('No transaction_id in response, skipping Update.', Zend_Log::WARN, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
            }
        }
    }

}
