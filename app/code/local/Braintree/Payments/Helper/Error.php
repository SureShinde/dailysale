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

class Braintree_Payments_Helper_Error extends Mage_Core_Helper_Abstract
{
    const STATUS_GATEWAY_REJECTED   = 'gateway_rejected';
    const STATUS_PROCESSOR_DECLINED = 'processor_declined';
    const CLONE_ERROR_CODE          = '91542';

    protected $_codesMessages = array(
        2000 => 'Contact your bank or try another card',
        2001 => 'Contact your bank or try another card',
        2002 => 'Contact your bank or try another card',
        2003 => 'Contact your bank or try another card',
        2004 => 'Check card details or try another card',
        2005 => 'Check card details or try another card',
        2006 => 'Check card details or try another card',
        2007 => 'Check card details or try another card',
        2008 => 'Check card details or try another card',
        2009 => 'Try another card',
        2010 => 'Check card details or try another card',
        2011 => 'Voice Authorization Required',
        2012 => 'Contact your bank or try another card',
        2013 => 'Contact your bank or try another card',
        2014 => 'Contact your bank or try another card',
        2015 => 'Contact your bank or try another card',
        2016 => 'Duplicate transaction',
        2017 => 'Contact your bank or try another card',
        2018 => 'Contact your bank or try another card',
        2019 => 'Contact your bank or try another card',
        2020 => 'Contact your bank or try another card',
        2021 => 'Contact your bank or try another card',
        2022 => 'Contact your bank or try another card',
        2023 => 'Try another card',
        2024 => 'Try another card',
        2025 => 'Try again later',
        2026 => 'Try again later',
        2027 => 'Try again later',
        2028 => 'Try again later',
        2029 => 'Try again later',
        2030 => 'Try again later',
        2031 => 'Try another card',
        2032 => 'Try another card',
        2033 => 'Try another card',
        2034 => 'Try another card',
        2035 => 'Try another card',
        2036 => 'Try another card',
        2037 => 'Try another card',
        2038 => 'Contact your bank or try another card',
        2039 => 'Try another card',
        2040 => 'Try another card',
        2041 => 'Contact your bank or try another card',
        2043 => 'Contact your bank or try another card',
        2044 => 'Contact your bank or try another card',
        2045 => 'Try again later',
        2046 => 'Contact your bank or try another card',
        2047 => 'Try another card',
        2048 => 'Try again later',
        2049 => 'Try again later',
        2050 => 'Try again later',
        2051 => 'Check card details or try another card',
        2052 => 'Try again later',
        2053 => 'Try another card',
        2054 => 'Processor decline',
        2055 => 'Processor decline',
        2056 => 'Processor decline',
        2057 => 'Contact your bank or try another card',
        2058 => 'Try another card',
        2059 => 'Contact your bank or try another card',
        2060 => 'Contact your bank or try another card',
        2061 => 'Processor decline',
        2062 => 'Processor decline',
        2063 => 'Payment method not supported. Please choose a different payment method',
        2068 => 'Please choose a different payment method',
        2071 => 'PayPal account invalid, please contact PayPal support',
        2072 => 'Email incorrectly formatted, please re-enter and try again. If problem continues contact PayPal support',
        2074 => 'Please contact PayPal Support',
        2075 => 'Please contact PayPal Support',
        2076 => 'Please choose a different payment method',
        2077 => 'Please choose a different payment method',
        2078 => 'Please choose a different payment method',
        2079 => 'PayPal not supported at this time. Please choose a different payment method',
        2080 => 'Invalid PayPal credentials, please re-enter and try again. If issue persists please contact PayPal Support',
        2081 => 'Please choose a different payment method',
        3000 => 'Processor network error',
    );

    /**
     * Parses unsuccessful result into message 
     * 
     * @param Braintree_Result_Error $result
     * @return string
     */
    public function parseBraintreeError($result)
    {
        $message = '';
        if (isset($result->transaction) && $result->transaction && $result->transaction->status) {
            if ($result->transaction->status == self::STATUS_GATEWAY_REJECTED) {
                $message = $this->__('Transaction declined by gateway: Check card details or try another card');
            } else if ($result->transaction->status == self::STATUS_PROCESSOR_DECLINED) {
                if (isset($result->transaction->processorResponseCode) && $result->transaction->processorResponseCode) {
                    $code = $result->transaction->processorResponseCode;
                    if (array_key_exists($code, $this->_codesMessages)) {
                        $message = $this->__('Transaction declined: ') . $this->__($this->_codesMessages[$code]);
                    }
                }
            } else if ($result->transaction->gatewayRejectionReason == Braintree_Transaction::THREE_D_SECURE) {
                $message = $this->__('Please try another credit card');
            }
        }
        if (!$message) {
            $errors = explode("\n", $result->message);
            foreach ($errors as $error) {
                $message .= ' ' . $this->__($error);
            }
        }
        return trim($message);
    }
}
