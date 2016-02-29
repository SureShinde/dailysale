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

class SFC_Kount_PaypalstandardController extends Mage_Core_Controller_Front_Action
{

    /**
     * Index action
     */
    public function indexAction()
    {
        // Nothing to do here
        // Log
        Mage::log('==== PayPal Standard Controller indexAction ====', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
    }

    /**
     * When a customer chooses Paypal on Checkout/Payment page
     *
     */
    public function redirectAction()
    {
        try {
            /** @var SFC_Kount_Helper_Ris $risHelper */
            $risHelper = Mage::helper('kount/ris');
            /** @var SFC_Kount_Helper_Order $orderHelper */
            $orderHelper = Mage::helper('kount/order');

            // Log
            Mage::log('==== PayPal Standard Controller redirectAction ====', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

            // Session
            $session = Mage::getSingleton('checkout/session');

            // Helper
            /** @var SFC_Kount_Helper_Data $helper */
            $helper = Mage::helper('kount');

            // Is Kount enabled and configured           
            if (Mage::getStoreConfig('kount/account/enabled') && Mage::getStoreConfig('kount/workflow/workflow_mode') == SFC_Kount_Model_Source_WorkflowMode::MODE_PRE_AUTH && $this->isEnabled()) {

                // -- Get quote
                $iQuoteId = $session->getQuoteId();
                $oQuote = (($iQuoteId) ? Mage::getModel('sales/quote')->load($iQuoteId) : null);
                if (empty($oQuote)) {
                    throw new Exception('Invalid quote passed to controller.');
                }

                // -- Get order
                $iOrderId = $session->getLastOrderId();
                $order = (($iOrderId) ? Mage::getModel('sales/order')->load($iOrderId) : null);
                if (!$order instanceof Mage_Sales_Model_Order) {
                    throw new Exception('Invalid order passed to controller.');
                }

                // -- Get payment
                $oPayment = $order->getPayment();
                if (empty($oPayment)) {
                    throw new Exception('Invalid payment passed to controller.');
                }
                
                $customer = Mage::getSingleton('customer/session')->getCustomer();
                $order->setData('customer', $customer);

                // Build and send RIS inquiry
                $request = $risHelper->buildInquiryFromOrder($order);
                $response = $risHelper->sendRequest($request);
                
                Mage::getSingleton('core/session')->setRisTransId($response->getTransactionId());

                // Check result / response
                if ($response instanceof Kount_Ris_Response) {
                    // Save response on order
                    $orderHelper->addRisResponseFieldsToOrder($order, $response);
                    $order->save();
                    $order->getPayment()->save();
                    // Request went through, check response
                    $auto = $risHelper->parseInquiryResponse($response);
                    if ($auto == SFC_Kount_Helper_Ris::RESPONSE_DECLINE) {
                        // Transaction was declined
                        // Mage::throwException(SFC_Kount_Helper_Ris::RIS_MESSAGE_REJECTED);
                    }
                    else {
                        if ($auto == SFC_Kount_Helper_Ris::RESPONSE_REVIEW) {
                            // Transaction should be flagged for review
                            $orderHelper->handleReview($order);
                        }
                        else {
                            // Otherwise consider transaction approved
                            // Don't alter order, let it go through as normal
                        }
                    }
                }
                else {
                    // RIS Request failed
                    Mage::throwException('No RIS information for this order. Might not have payment method supported or enabled for Kount extension.');
                }
            }

            // Set body
            $this->getResponse()->setBody($this->getLayout()->createBlock('paypal/standard_redirect')->toHtml());

            return;
        }
        catch (\Exception $e) {

            // -- Log
            Mage::getSingleton('checkout/session')->addError($this->__('Unable to process Express Checkout approval.'));
            Mage::log($e->getMessage(), Zend_Log::ERR, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        }

        // Redirect to cart
        $this->_redirect('checkout/cart');
    }

    /**
     * When a customer cancel payment from paypal.
     */
    public function cancelAction()
    {
        try {
            // Log
            Mage::log('==== PayPal Standard Controller cancelAction ====', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

            // Session
            $session = Mage::getSingleton('checkout/session');
            $session->setQuoteId($session->getPaypalStandardQuoteId(true));

            // Cancel order
            if ($session->getLastRealOrderId()) {
                $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
                if ($order->getId()) {
                    $order->cancel()->save();
                }
            }

            // Helper
            /** @var SFC_Kount_Helper_Data $helper */
            $helper = Mage::helper('kount');
            $risHelper = Mage::helper('kount/ris');
            $orderHelper = Mage::helper('kount/order');

            // Is Kount enabled and configured
            if (Mage::getStoreConfig('kount/account/enabled') && $helper->validateConfig() && $this->isEnabled()) {

                // Send RIS Update
                $transactionId = Mage::getSingleton('core/session')->getRisTransId();
                if(Mage::getStoreConfig('kount/workflow/workflow_mode') == SFC_Kount_Model_Source_WorkflowMode::MODE_PRE_AUTH) {
                    $updateRequest = $risHelper->buildUpdate($transactionId, false);
                    $updateResponse = $risHelper->sendRequest($updateRequest);
                } else {
                    // Build and send RIS inquiry
                    $request = $risHelper->buildInquiryFromOrder($order);
                    $response = $risHelper->sendRequest($request);
                    
                    Mage::getSingleton('core/session')->setRisTransId($response->getTransactionId());
    
                    // Check result / response
                    if ($response instanceof Kount_Ris_Response) {
                        // Save response on order
                        $orderHelper->addRisResponseFieldsToOrder($order, $response);
                        $order->save();
                        $order->getPayment()->save();
                        // Request went through, check response
                        $auto = $risHelper->parseInquiryResponse($response);
                        if ($auto == SFC_Kount_Helper_Ris::RESPONSE_DECLINE) {
                            // Transaction was declined
                            Mage::throwException(SFC_Kount_Helper_Ris::RIS_MESSAGE_REJECTED);
                        }
                        else {
                            if ($auto == SFC_Kount_Helper_Ris::RESPONSE_REVIEW) {
                                // Transaction should be flagged for review
                                $orderHelper->handleReview($order);
                            }
                            else {
                                // Otherwise consider transaction approved
                                // Don't alter order, let it go through as normal
                            }
                        }
                    }
                    else {
                        // RIS Request failed
                        Mage::throwException('No RIS information for this order. Might not have payment method supported or enabled for Kount extension.');
                    }                    
                }
            }
        }
        catch (Mage_Core_Exception $e) {
            Mage::getSingleton('checkout/session')->addError($e->getMessage());
            Mage::log($e->getMessage(), Zend_Log::ERR, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        }
        catch (Exception $e) {
            Mage::getSingleton('checkout/session')->addError($this->__('Unable to cancel Express Checkout.'));
            Mage::log($e->getMessage(), Zend_Log::ERR, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        }

        // Redirect
        $this->_redirect('checkout/cart');
    }

    /**
     * when paypal returns
     * The order information at this point is in POST
     * variables.  However, you don't want to "process" the order until you
     * get validation from the IPN.
     */
    public function successAction()
    {
        /** @var SFC_Kount_Helper_Ris $risHelper */
        $risHelper = Mage::helper('kount/ris');
        $orderHelper = Mage::helper('kount/order');

        // Log
        Mage::log('==== PayPal Standard Controller successAction ====', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

        // Is Kount enabled and configured
        if (Mage::getStoreConfig('kount/account/enabled') && $this->isEnabled()) {            
            if(Mage::getStoreConfig('kount/workflow/workflow_mode') == SFC_Kount_Model_Source_WorkflowMode::MODE_PRE_AUTH) {
                $transactionId = Mage::getSingleton('core/session')->getRisTransId();
                // Send update to RIS
                $updateRequest = $risHelper->buildUpdate($transactionId, true);
                $updateResponse = $risHelper->sendRequest($updateRequest);
            } else {
                $session = Mage::getSingleton('checkout/session');
                $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
                
                // Build and send RIS inquiry
                $request = $risHelper->buildInquiryFromOrder($order);
                $response = $risHelper->sendRequest($request);
                
                Mage::getSingleton('core/session')->setRisTransId($response->getTransactionId());

                // Check result / response
                if ($response instanceof Kount_Ris_Response) {
                    // Save response on order
                    $orderHelper->addRisResponseFieldsToOrder($order, $response);
                    $order->save();
                    $order->getPayment()->save();
                    // Request went through, check response
                    $auto = $risHelper->parseInquiryResponse($response);
                    if ($auto == SFC_Kount_Helper_Ris::RESPONSE_DECLINE) {
                        // Transaction was declined
                        // Mage::throwException(SFC_Kount_Helper_Ris::RIS_MESSAGE_REJECTED);
                    }
                    else {
                        if ($auto == SFC_Kount_Helper_Ris::RESPONSE_REVIEW) {
                            // Transaction should be flagged for review
                            $orderHelper->handleReview($order);
                        }
                        else {
                            // Otherwise consider transaction approved
                            // Don't alter order, let it go through as normal
                        }
                    }
                }
            }            
        }

        // Session
        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getPaypalStandardQuoteId(true));

        // Save
        Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
        $this->_redirect('checkout/onepage/success', array('_secure' => true));
    }
    
    protected function isEnabled()
    {
        if(Mage::helper('kount/paymentMethod')->methodIsDisabledForKount('paypal_standard') == false) {
            return true;
        } else {
            Mage::log('Paypal Standard is not enabled for Kount.', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
            return false;
        }
    }

}
