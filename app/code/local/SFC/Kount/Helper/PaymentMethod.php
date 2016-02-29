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
 * Class SFC_Kount_Helper_PaymentMethod
 *
 * Abstraction layer to handle various Magento payment method modules / extensions / systems
 *
 */
class SFC_Kount_Helper_PaymentMethod extends Mage_Core_Helper_Abstract
{

    /**
     * @param Kount_Ris_Request $request
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return $this
     */
    public function setPaymentDataOnRequest(Kount_Ris_Request $request, Mage_Sales_Model_Order_Payment $payment, $fullCardNumber = null)
    {
        // Get payment method code
        $methodCode = $payment->getData('method');

        $basicMethods = array(
            'authorizenet',         // Built-in Authorize.Net
            'cybersource_soap',     // Magento Core Team Cybersource Extension
            'paypal_direct',        // PayPal Payments Pro
            'verisign',             // PayPal PayFlow Pro
        );
        $silentPostMethods = array(
            'braintree',                // Braintree Ext
            'authorizenet_directpost',  // Built-In Authorize.Net Direct POST
        );
        $sfcTokenizedMethods = array(
            'subscribe_pro',
            'authnettoken',
            'sfc_cim_core',
            'sfc_cybersource',
        );
        $payPalMethods = array(
            'paypal_express',
            'paypaluk_express',
            'paypal_standard',
        );

        // Handle basic methods that have CC # in 'cc_number' field and nothing more
        if (in_array($methodCode, $basicMethods)) {
            if (strlen($fullCardNumber)) {
                $cardNumber = $fullCardNumber;
            }
            else {
                $cardNumber = $payment->getData('cc_number');
            }
            if ($this->validateCcNum($cardNumber)) {
                $request->setCardPayment($cardNumber);
            }
            else {
                $request->setGiftCardPayment($cardNumber);
            }
            $request->setPaymentTokenLast4($payment->getData('cc_last4'));
        }
        // Handle silent POST methods
        else if (in_array($methodCode, $silentPostMethods)) {
            if($methodCode == SFC_Kount_Helper_Ris::RIS_PAYTYPE_BRAINTREE){
                $postData = Mage::app()->getRequest()->getParams();
                Mage::log($postData);
                if(!empty($postData['payment']['cc_token']))
                    $cardNumber = $postData['payment']['cc_token'];
                else if(!empty($postData['payment']['cc_number']))
                    $cardNumber = $postData['payment']['cc_number'];
                else if(!empty($postData['payment']['cc_last4']))
                    $cardNumber = $postData['payment']['cc_last4'];
                
                $request->setGiftCardPayment($cardNumber);
            } else {
                // For these methods, there is no chance to get the actual credit card number
                $request->setGiftCardPayment($payment->getData('cc_last4'));
            }
            $request->setPaymentTokenLast4($payment->getData('cc_last4'));
        }
        // Handle StoreFront Consulting Tokenized Payment Methods
        // Handle Subscribe Pro Vault
        else if ($this->methodCodeStartsWithOneOf($methodCode, $sfcTokenizedMethods)) {
            if (strlen($fullCardNumber)) {
                $cardNumber = $fullCardNumber;
            }
            else {
                $cardNumber = $payment->getData('cc_number');
            }
            if ($this->validateCcNum($cardNumber)) {
                $request->setCardPayment($cardNumber);
            }
            else {
                $request->setGiftCardPayment($cardNumber);
            }
            $request->setPaymentTokenLast4($payment->getData('cc_last4'));
        }
        // Handle PayPal methods (the ones where you pay with a PayPal account, not where they act like CC gateway)
        else if (in_array($methodCode, $payPalMethods)) {
            if ($methodCode == 'paypal_express' || $methodCode == 'paypaluk_express') {
                $sPayId =
                    $payment->getAdditionalInformation(Mage_Paypal_Model_Express_Checkout::PAYMENT_INFO_TRANSPORT_PAYER_ID);
                if (empty($sPayId)) {
                    Mage::throwException('Invalid Payer Id for PayPal payment.');
                }
                $request->setPayPalPayment($sPayId);
            }
            else if ($methodCode == 'paypal_standard') {
                $request->setNoPayment();
            }
        }
        else {
            // Attempt to handle any arbitrary payment method
            if (strlen($fullCardNumber)) {
                $cardNumber = $fullCardNumber;
            }
            else {
                $cardNumber = $payment->getData('cc_number');
            }
            if (strlen($cardNumber)) {
                if ($this->validateCcNum($cardNumber)) {
                    $request->setCardPayment($cardNumber);
                }
                else {
                    $request->setGiftCardPayment($cardNumber);
                }
                $request->setPaymentTokenLast4($payment->getData('cc_last4'));
            } else {
                $request->setNoPayment();
            }
        }

        // Get AVS and CVV responses from standard fields in $payment
        $request->setUserDefinedField('AVS', $payment->getCcAvsStatus());
        $request->setUserDefinedField('CVV', $payment->getCcCidStatus());

        return $this;
    }

    /**
     * Check if payment method code is disabled for use with Kount
     *
     * @param string $methodCode
     * @return bool
     */
    public function methodIsDisabledForKount($methodCode, $storeId = null)
    {
        
        Mage::log('Current Payment Method is '.$methodCode.', checking Config.', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        $specificConfigMethods = array(
            'authorizenet',
            'authorizenet_directpost',
            'paypal_express',
            'paypaluk_express',
            'paypal_standard',
            'paypal_direct',        // PayPal Payments Pro
            'verisign',             // PayPal PayFlow Pro
        );
        // Check arbitrary list of disabled methods
        $disabledMethods = Mage::getStoreConfig('kount/paymentmethods/disable_methods', $storeId);
        $disabledMethods = explode(',', $disabledMethods);
        $disabledMethodCodes = array();
        foreach ($disabledMethods as $disabledMethod) {
            if (strlen(trim($disabledMethod))) {
                $disabledMethodCodes[] = trim($disabledMethod);
            }
        }
        foreach ($disabledMethodCodes as $disabledMethod) {
            if ($disabledMethod == substr($methodCode, 0, strlen($disabledMethod))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $methodCode
     * @param array $prefixes
     * @return bool
     */
    protected function methodCodeStartsWithOneOf($methodCode, array $prefixes)
    {
        foreach ($prefixes as $prefix) {
            if ($prefix == substr($methodCode, 0, strlen($prefix))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate credit card number
     *
     * This method is based on Mage_Payment_Model_Method_Cc::validateCcNum
     *
     * @param   string $ccNumber
     * @return  bool
     */
    protected function validateCcNum($ccNumber)
    {
    	$cardNumber = strrev($ccNumber);
    	$numSum = 0;
    	
    	if(strlen($ccNumber) < 13) 
    	    return false;
    
    	for ($i=0; $i<strlen($cardNumber); $i++) {
    		$currentNum = substr($cardNumber, $i, 1);
    		
            if(is_numeric($currentNum) == false)
                return false;
            
    		/**
    		 * Double every second digit
    		 */
    		if ($i % 2 == 1) {
    			$currentNum *= 2;
    		}
    
    		/**
    		 * Add digits of 2-digit numbers together
    		 */
    		if ($currentNum > 9) {
    			$firstNum = $currentNum % 10;
    			$secondNum = ($currentNum - $firstNum) / 10;
    			$currentNum = $firstNum + $secondNum;
    		}
    
    		$numSum += $currentNum;
    	}
    
    	/**
    	 * If the total has no remainder it's OK
    	 */
    	return ($numSum % 10 == 0);
    }

}
