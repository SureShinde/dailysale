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

class SFC_Kount_Helper_Ris extends Mage_Core_Helper_Abstract
{

    /**
     * Ris Response codes
     */
    const RESPONSE_DECLINE  = 'D';
    const RESPONSE_REVIEW   = 'R';
    const RESPONSE_ESCALATE = 'E';
    const RESPONSE_APPROVE  = 'A';

    /**
     * Pay types
     */
    const RIS_PAYTYPE_AUTH = 'authorizenet';
    const RIS_PAYTYPE_AUTHDP = 'authorizenet_directpost';
    const RIS_PAYTYPE_AUTHSFCCIM = 'authnettoken';
    const RIS_PAYTYPE_AUTHSFCCIMCORE = 'sfc_cim_core';
    const RIS_PAYTYPE_BRAINTREE = 'braintree';
    const RIS_PAYTYPE_PPEXPRESS = 'paypal_express';
    const RIS_PAYTYPE_PPUKEXPRESS = 'paypaluk_express';
    const RIS_PAYTYPE_PPDIRECT = 'paypal_direct';
    const RIS_PAYTYPE_PPSTANDARD = 'paypal_standard';
    const RIS_PAYTYPE_CYBERSOURCE = 'cybersource_soap';
    const RIS_PAYTYPE_CYBERSOURCE_SFC = 'sfc_cybersource';
    const RIS_PAYTYPE_PAYFLOWPRO = 'verisign';

    /**
     * Messages
     */
    const RIS_MESSAGE_REJECTED = 'Payment authorization rejection from the processor.';
    const RIS_MESSAGE_ORDERREVIEW = 'Order in review from Kount.';
    const RIS_MESSAGE_ORDERDECLINE = 'Order declined from Kount.';

    /**
     * @param Mage_Sales_Model_Order $order
     * @param string $mack
     * @param string $auth
     * @param null $fullCardNumber
     * @return Kount_Ris_Request_Inquiry
     */
    public function buildInquiryFromOrder(Mage_Sales_Model_Order $order, $fullCardNumber = null, $auth = 'A', $mack = 'Y')
    {
        // Log
        Mage::log('==== Building RIS Inquiry ====', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

        // Build request and add all relevant info
        $request = new Kount_Ris_Request_Inquiry();
        $this->addVersionInfoToRequest($request);
        $this->addSessionToRequest($request);
        $this->addOrderInfoToRequest($request, $order);
        // Additional info
        $request->setMack($mack);            
        $request->setAuth($auth);
        
        // Set payment info
        /** @var SFC_Kount_Helper_PaymentMethod $paymentMethodHelper */
        $paymentMethodHelper = Mage::helper('kount/paymentMethod');
        $paymentMethodHelper->setPaymentDataOnRequest($request, $order->getPayment(), $fullCardNumber);

        return $request;
    }

    /**
     * @param string $risTransactionId
     * @param boolean $processorAuthorized
     * @return Kount_Ris_Request_Update
     */
    public function buildUpdate($risTransactionId, $processorAuthorized)
    {
        // Ris update request
        $request = new Kount_Ris_Request_Update();

        // Add session id
        $this->addSessionToRequest($request);
        // Transaction Id
        $request->setTransactionId($risTransactionId);
        // Transaction info
        $request->setMack(($processorAuthorized ? 'Y' : 'N'));
        
        if(Mage::getStoreConfig('kount/workflow/workflow_mode') == SFC_Kount_Model_Source_WorkflowMode::MODE_POST_AUTH)
            $auth = self::RESPONSE_APPROVE;
        else
            $auth = ($processorAuthorized ? 'A' : 'D');
            
        $request->setAuth($auth);

        return $request;
    }

    /**
     * @param Kount_Ris_Request $request
     * @return bool|Kount_Ris_Response
     */
    public function sendRequest(Kount_Ris_Request $request)
    {
        /** @var SFC_Kount_Helper_Data $helper */
        $helper = Mage::helper('kount');

        try {
            // Log
            Mage::log('Kount extension version: ' . $helper->getExtensionVersion(), Zend_Log::INFO,
                SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
            Mage::log('Store Id: ' . Mage::app()->getStore()->getId(), Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
            Mage::log('Merchant Id: ' . Mage::getStoreConfig('kount/account/merchantnum'), Zend_Log::INFO,
                SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
            Mage::log('Website Id: ' . Mage::getStoreConfig('kount/account/website'), Zend_Log::INFO,
                SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

            // Send the request and get response
            $response = $request->getResponse();
            if (!$response) {
                Mage::throwException('Invalid response from Kount RIS.');
            }
            // Log errors
            $errors = $response->getErrors();
            foreach ($errors as $error) {
                Mage::log('RIS returned error: ' . $error, Zend_Log::ERR, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
            }
            // Log warnings
            $warnings = $response->getWarnings();
            foreach ($warnings as $warning) {
                Mage::log('RIS returned warning: ' . $warning, Zend_Log::WARN, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
            }
            // Check error code
            if ($response->getErrorCode() !== null) {
                Mage::log('RIS returned error code: ' . $response->getErrorCode(), Zend_Log::WARN, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
                Mage::log('Continuing to process order without Kount.', Zend_Log::WARN, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
            }

            // If we got here, request was successful, return the response
            return $response;
        }
        catch (Kount_Ris_ValidationException $e) {
            // Log
            Mage::log('Exception while validating RIS request: ' . $e->getMessage(), Zend_Log::ERR, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
            Mage::log('Continuing to process order without Kount.', Zend_Log::WARN, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
            return new Kount_Ris_Response();
        }
        catch (Kount_Ris_Exception $e) {
            // Log
            Mage::log('Exception while making RIS request: ' . $e->getMessage(), Zend_Log::ERR, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

            // This request failed if there was an exception
            return false;
        }
        catch (\Exception $e) {
            // Log
            Mage::log(get_class($e), 7, 'kount.log');
            Mage::log('Exception while making RIS request: ' . $e->getMessage(), Zend_Log::ERR, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

            // This request failed if there was an exception
            return false;
        }
    }

    /**
     * @param Kount_Ris_Response $response
     * @return null|string
     */
    public function parseInquiryResponse(Kount_Ris_Response $response)
    {
        $auto = null;
        $score = null;
        if ($response->getErrorCount() == 0) {
            $auto = $response->getAuto();
            $score = $response->getScore();

            // Log
            Mage::log("Ris Response: {$auto}", Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
            Mage::log("Ris Score: {$score}", Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        }

        return $auto;
    }

    /**
     * @param Kount_Ris_Response $response
     * @return string
     */
    public function getRulesTriggeredString(Kount_Ris_Response $response)
    {
        $rules = '';
        if ($response->getNumberRulesTriggered() > 0) {
            foreach ($response->getRulesTriggered() as $curRuleId => $curRuleDesc) {
                $rules .= 'Rule ID ' . $curRuleId . ': ' . $curRuleDesc . "\n";
            }
        }
        else {
            $rules = 'No Rules';
        }

        return $rules;
    }

    /**
     * @param Kount_Ris_Request_Inquiry $request
     */
    protected function addVersionInfoToRequest(Kount_Ris_Request_Inquiry $request)
    {
        // Get helper
        /** @var SFC_Kount_Helper_Data $helper */
        $helper = Mage::helper('kount');

        // Get Version info
        $version = Mage::getVersionInfo();
        // Get Edition
        if (method_exists('Mage', 'getEdition')) {
            $magentoEdition = Mage::getEdition();
            // Build platform string from Magento version info
            switch ($magentoEdition) {
                case Mage::EDITION_COMMUNITY:
                    $platformString = 'CE';
                    break;
                case Mage::EDITION_ENTERPRISE:
                    $platformString = 'EE';
                    break;
                case Mage::EDITION_PROFESSIONAL:
                    $platformString = 'PE';
                    break;
                default:
                    $platformString = '??';
                    break;
            }
        }
        else {
            $platformString = '??';
        }

        // Add version to platform string
        $platformString .= $version['major'] . '.' . $version['minor'];

        // Add platform string to RIS request
        $request->setUserDefinedField('PLATFORM', $platformString);
        // Add extension version info to request
        $request->setUserDefinedField('EXT', $helper->getExtensionVersion());
    }

    /**
     * @param Kount_Ris_Request $request
     */
    protected function addSessionToRequest(Kount_Ris_Request $request)
    {
        /** @var SFC_Kount_Model_Session $kountSession */
        $kountSession = Mage::getSingleton('kount/session');

        Mage::log("Setting Kount session ID on request: " . $kountSession->getKountSessionId(), Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        $request->setSessionId($kountSession->getKountSessionId());
    }

    /**
     * @param Kount_Ris_Request_Inquiry $request
     * @param Mage_Sales_Model_Order $order
     * @throws Kount_Ris_IllegalArgumentException
     */
    protected function addOrderInfoToRequest(Kount_Ris_Request_Inquiry $request, Mage_Sales_Model_Order $order)
    {
        /** @var SFC_Kount_Helper_Data $helper */
        $helper = Mage::helper('kount');
        /** @var Mage_Directory_Helper_Data $directoryHelper */
        $directoryHelper = Mage::helper('directory');
        /** @var Mage_Core_Helper_Http $httpHelper */
        $httpHelper = Mage::helper('core/http');

        // Order info
        $baseCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();
        Mage::log("Base Currency: {$baseCurrencyCode}", Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        if ($baseCurrencyCode == 'USD') {
            $baseGrandTotal = round($order->getBaseGrandTotal() * 100);
            $request->setTotal($baseGrandTotal);
            Mage::log("USD Base Grand Total: {$baseGrandTotal}", Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        }
        else {
            $convertedGrandTotal =
                (round($directoryHelper->currencyConvert($order->getBaseGrandTotal(), $baseCurrencyCode, 'USD') * 100));
            $request->setTotal($convertedGrandTotal);
            Mage::log("Grand Total Converted to USD: {$convertedGrandTotal}", Zend_Log::INFO,
                SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        }
        $request->setCurrency('USD');
        $request->setOrderNumber($order->getIncrementId());

        // Map other order fields to UDF fields
        // Map shipping method and carrier
        $shippingFields = explode('_', $order->getShippingMethod());
        if (isset($shippingFields[0])) {
            $request->setUserDefinedField('CARRIER', $shippingFields[0]);
        }
        if (isset($shippingFields[1])) {
            $request->setUserDefinedField('METHOD', $shippingFields[1]);
        }
        // Map coupon code
        if (strlen($order->getCouponCode())) {
            $request->setUserDefinedField('COUPON_CODE', $order->getCouponCode());
        }
        // Map account name
        if (strlen($order->getCustomerName())) {
            $request->setUserDefinedField('ACCOUNT_NAME', $order->getCustomerName());
        }

        // Customer info
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = $order->getData('customer');
        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();
        $sName = $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname();
        $request->setName($sName);
        $request->setEmail($order->getCustomerEmail());
        $request->setUserAgent($httpHelper->getHttpUserAgent());
        if($customer && $customer->getEntityId()){
            $request->setUnique($customer->getEntityId());
            $request->setEpoch(strtotime($customer->getData('created_at')));
        } else {
            $request->setEpoch(time());
        }

        // Billing Info
        if (!empty($billingAddress)) {
            $request->setBillingAddress(
                $billingAddress->getStreet(1),
                ($billingAddress->getStreet(2) ? $billingAddress->getStreet(2) : ''),
                $billingAddress->getCity(),
                $billingAddress->getRegion(),
                $billingAddress->getPostcode(),
                $billingAddress->getCountry());
            $request->setBillingPhoneNumber($billingAddress->getTelephone());
        }

        // Shipping info
        if (!empty($shippingAddress)) {
            $request->setShippingName($shippingAddress->getFirstname() . ' ' . $shippingAddress->getLastname());
            $request->setShippingAddress(
                $shippingAddress->getStreet(1), ($shippingAddress->getStreet(2) ? $shippingAddress->getStreet(2) : ''),
                $shippingAddress->getCity(),
                $shippingAddress->getRegion(),
                $shippingAddress->getPostcode(),
                $shippingAddress->getCountry());
            $request->setShippingPhoneNumber($shippingAddress->getTelephone());;
            $request->setShippingEmail($order->getCustomerEmail());
        }

        // Cart
        $cart = array();
        /** @var Mage_Sales_Model_Order_Item $item */
        foreach ($order->getAllVisibleItems() as $item) {
            $cart[] = new Kount_Ris_Data_CartItem(
                $item->getSku(),
                $item->getName(),
                ($item->getDescription() ? $item->getDescription() : ''),
                round($item->getQtyOrdered()),
                ($baseCurrencyCode == 'USD' ? round($item->getBasePrice() * 100) :
                    round($directoryHelper->currencyConvert($item->getBasePrice(), $baseCurrencyCode, 'USD') * 100)));
        }
        $request->setCart($cart);

        // Ip Address
        if (Mage::app()->getStore()->isAdmin()) {
            $request->setIpAddress('10.0.0.1');
        }
        else {
            $ipAddress = ($order->getXForwardedFor() ? $order->getXForwardedFor() : $order->getRemoteIp());
            if (strpos($ipAddress, ",")) {
                $ipAddress = explode(",", $ipAddress);
                $ipAddress = array_shift($ipAddress);
            }
            if ($helper->checkIPAddress($ipAddress)) {
                $request->setIpAddress('10.0.0.1');
            }
            else {
                $request->setIpAddress($ipAddress);
            }
        }

        // Website Id
        $request->setWebsite(Mage::getStoreConfig('kount/account/website'));
    }

}
