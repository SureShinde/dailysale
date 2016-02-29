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

class SFC_Kount_Helper_Order extends Mage_Core_Helper_Abstract
{

    /**
     * Order statuses
     */
    const ORDER_STATUS_KOUNT_REVIEW = 'review_kount';
    const ORDER_STATUS_KOUNT_REVIEW_LABEL = 'Review';
    const ORDER_STATUS_KOUNT_DECLINE = 'decline_kount';
    const ORDER_STATUS_KOUNT_DECLINE_LABEL = 'Decline';

    /**
     * @param Mage_Sales_Model_Order $order
     * @throws \Exception
     */
    public function addRisResponseFieldsToOrder(Mage_Sales_Model_Order $order, Kount_Ris_Response $response)
    {
        /** @var SFC_Kount_Helper_Ris $risHelper */
        $risHelper = Mage::helper('kount/ris');

        // Get payment
        $payment = $order->getPayment();

        // Build rules string
        $rules = $risHelper->getRulesTriggeredString($response);

        // Add standard fields to order
        $order->setData('kount_ris_score', $response->getScore());
        $order->setData('kount_ris_response', $response->getAuto());
        $order->setData('kount_ris_rule', $rules);
        $order->setData('kount_ris_description', $response->getReasonCode());

        // Add additional info
        $payment->setAdditionalInformation(
            'ris_additional_info',
            json_encode(
                array(
                    'TRAN' => $response->getTransactionId(),
                    'GEOX' => $response->getGeox(),
                    'DVCC' => $response->getCountry(),
                    'KAPT' => $response->getKaptcha(),
                    'CARDS' => $response->getCards(),
                    'EMAILS' => $response->getEmails(),
                    'DEVICES' => $response->getDevices(),
                ))
        );

        Mage::log('Saving RIS Response to order: ', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        Mage::log(' === RIS Response Data === ', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        Mage::log('Response: ' . $order->getData('kount_ris_response'), Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        Mage::log('Score: ' . $order->getData('kount_ris_score'), Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        Mage::log('Rules: ' . $rules, Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        Mage::log('Description: ' . $order->getData('kount_ris_description'), Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        Mage::log('Additional Info:', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        Mage::log(json_decode($payment->getAdditionalInformation('ris_additional_info'), true), Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        Mage::log(' ========================= ', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

    }

    /**
     * Handle order for a Decline response from Kount RIS
     *
     * @param Mage_Sales_Model_Order $order
     */
    public function handleDecline(Mage_Sales_Model_Order $order)
    {
        // Should we attempt credit / refund, cancel or nothing
        if (Mage::getStoreConfig('kount/workflow/decline_action') == SFC_Kount_Model_Source_DeclineAction::ACTION_CANCEL) {
            // Attempt cancel and void order
            // Assume Payment Method is in "authorize only" mode
            if($this->cancelOrder($order)) {
                $order->addStatusHistoryComment('Order cancelled / voided due to Kount RIS Decline.');
                $order->save();
            }
            else {
                $order->addStatusHistoryComment('Failed to cancel order.  Cancel attempt due to Kount RIS Decline.');
                $order->save();
            }
        }
        else if (Mage::getStoreConfig('kount/workflow/decline_action') == SFC_Kount_Model_Source_DeclineAction::ACTION_REFUND) {
            // Attempt refund
            if($this->refundOrder($order)) {
                $order->addStatusHistoryComment('Order refunded due to Kount RIS Decline.');
                $order->save();
            }
            else {
                $order->addStatusHistoryComment('Failed to refund order.  Refund attempt due to Kount RIS Decline.');
                $order->save();
            }
        }
        else {
            // Default to setting HOLD / Decline
            // Just set order to Decline status / state
            $this->setOrderToKountDecline($order);
        }
    }

    /**
     * Handle order in case of a Review response from Kount RIS
     *
     * @param Mage_Sales_Model_Order $order
     */
    public function handleReview(Mage_Sales_Model_Order $order)
    {
        // Just set order to Review status / state
        $this->setOrderToKountReview($order);
    }

    /**
     * Attempt to issue credit memo and online refund for Magento order, where possible
     *
     * NOTE: This method is based on copying actions which occur when credit memo / online refund issued in Admin panel
     *
     * @param Mage_Sales_Model_Order $order
     * @return bool
     */
    public function refundOrder(Mage_Sales_Model_Order $order)
    {
        try {
            // Check if order will allow us to create 
            if (!$order->canCreditmemo()) {
                // Error, can't create credit memo for this order
                Mage::log('Cant create credit memo for order: ' . $order->getIncrementId(), Zend_Log::ERR,
                    SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

                return false;
            }
            // Get invoices from order
            if (!$order->hasInvoices()) {
                // Order has no invoice to credit memo
                Mage::log('No invoices found for order: ' . $order->getIncrementId(), Zend_Log::ERR,
                    SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

                return false;
            }

            // Iterate invoice & attempt to credit memo & refund each
            $invoiceCollection = $order->getInvoiceCollection();
            /** @var Mage_Sales_Model_Order_Invoice $curInvoice */
            foreach ($invoiceCollection as $curInvoice) {
                // Log
                Mage::log(
                    'Issuing refund / credit memo for invoice: ' . $curInvoice->getIncrementId(),
                    Zend_Log::INFO,
                    SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

                // Use sales/service_order to prepare memo for all items on invoice
                /** @var Mage_Sales_Model_Service_Order $service */
                $service = Mage::getModel('sales/service_order', $order);
                /** @var Mage_Sales_Model_Order_Creditmemo $curCreditmemo */
                $curCreditmemo = $service->prepareInvoiceCreditmemo($curInvoice);

                // Set refund requested flag on credit memo
                $curCreditmemo->setData('refund_requested', true);

                // Register credit memo
                $curCreditmemo->register();

                // Set email customer flag
                $curCreditmemo->setEmailSent(true);
                $curCreditmemo->getOrder()->setCustomerNoteNotify(true);

                // Save the credit memo
                // Save creditmemo and related order, invoice in one transaction
                /** @var Mage_Core_Model_Resource_Transaction $transactionSave */
                $transactionSave = Mage::getModel('core/resource_transaction');
                $transactionSave
                    ->addObject($curCreditmemo)
                    ->addObject($curCreditmemo->getOrder())
                    ->addObject($curCreditmemo->getData('invoice'));
                $transactionSave->save();

                // Send customer email
                $comment = 'Kount Decline';
                $curCreditmemo->sendEmail(true, $comment);

            }

            // Return successfully
            return true;

        }
        catch (\Exception $e) {
            // Log details of this exception
            Mage::log($e->getMessage(), Zend_Log::ERR, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
            
            Mage::log('Unable to refund Magento order.', Zend_Log::ERR, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
            
            if($this->cancelOrder($order) == false) {
                $this->setOrderToKountDecline($order);
            }
            // return false
            return false;
        }

    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return bool
     * @throws \Exception
     */
    public function cancelOrder(Mage_Sales_Model_Order $order)
    {
        /** @var SFC_Kount_Helper_Data $helper */
        $helper = Mage::helper('kount');

        Mage::log('Attempting to cancel Magento order.', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        // Check canCancel
        if ($order->canCancel()) {
            // Cancel & save order
            $order->cancel();
            $order->save();

            return true;
        }
        else {
            // Not able to cancel this order
            Mage::log('Unable to cancel Magento order.', Zend_Log::ERR, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

            return false;
        }
    }

    /**
     * Attempt to void the order payment
     *
     * @param Mage_Sales_Model_Order $order
     * @return bool
     */
    public function voidOrder(Mage_Sales_Model_Order $order)
    {
        try {
            $order->getPayment()->void(
                new Varien_Object() // workaround for backwards compatibility
            );
            $order->save();
            Mage::log('The payment has been voided.', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

            return true;
        }
        catch (Exception $e) {
            Mage::log('Failed to void the payment.', Zend_Log::ERR, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
            Mage::logException($e);

            return false;
        }
    }


    /**
     * Put order on hold / at Kount Review status
     *
     * @param Mage_Sales_Model_Order $order Order to operate on
     */
    public function setOrderToKountReview($order)
    {
        // Log
        Mage::log('Setting order to Kount Review status / state', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

        // Save order state & status before we start
        // Save prior order state & status
        $order->setHoldBeforeState($order->getState());
        $order->setHoldBeforeStatus($order->getStatus());

        // Get appropriate order status
        //$orderStatus = Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW;
        $orderStatus = self::ORDER_STATUS_KOUNT_REVIEW;

        // Set state & status on Magento order
        $order->setState(
            Mage_Sales_Model_Order::STATE_HOLDED,
            $orderStatus,
            SFC_Kount_Helper_Ris::RIS_MESSAGE_ORDERREVIEW,
            false
        );
        $order->save();
    }

    /**
     * Put order to Kount Decline status
     *
     * @param Mage_Sales_Model_Order $order Order to operate on
     */
    public function setOrderToKountDecline($order)
    {
        // Log
        Mage::log('Setting order to Kount Decline status / state', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

        // Save order state & status before we start
        // Save prior order state & status
        $order->setHoldBeforeState($order->getState());
        $order->setHoldBeforeStatus($order->getStatus());

        // Get appropriate order status
        //$orderStatus = Mage_Sales_Model_Order::STATE_HOLDED;
        $orderStatus = self::ORDER_STATUS_KOUNT_DECLINE;

        // Set state & status on Magento order
        $order->setState(
            Mage_Sales_Model_Order::STATE_HOLDED,
            $orderStatus,
            SFC_Kount_Helper_Ris::RIS_MESSAGE_ORDERDECLINE,
            false
        );
        $order->save();
    }

    /**
     * Restore order status from before hold
     *
     * @param Mage_Sales_Model_Order $order Order to operate on
     */
    public function restorePreHoldOrderStatus($order)
    {
        // Move order from Hold to previous status
        $order->setData('state', $order->getHoldBeforeState());
        
        $order->setStatus($order->getHoldBeforeStatus());
        $history = $order->addStatusHistoryComment('', false);
        $history->setIsCustomerNotified(0);
        
        $order->setHoldBeforeState(null);
        $order->setHoldBeforeStatus(null);
        $order->save();
    }
    
}
