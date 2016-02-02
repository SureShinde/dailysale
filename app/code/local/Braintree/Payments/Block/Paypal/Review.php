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

class Braintree_Payments_Block_Paypal_Review extends Mage_Core_Block_Template
{
    /**
     * @var Mage_Sales_Model_Quote
     */
    protected $_quote;

    /**
     * Currently selected shipping rate
     *
     * @var Mage_Sales_Model_Quote_Address_Rate
     */
    protected $_currentShippingRate = null;

    /**
     * Quote object setter
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return Mage_Paypal_Block_Express_Review
     */
    public function setQuote(Mage_Sales_Model_Quote $quote)
    {
        $this->_quote = $quote;
        return $this;
    }

    /**
     * Return quote billing address
     *
     * @return Mage_Sales_Model_Quote_Address
     */
    public function getBillingAddress()
    {
        return $this->_quote->getBillingAddress();
    }

    /**
     * Return quote shipping address
     *
     * @return Mage_Sales_Model_Quote_Address
     */
    public function getShippingAddress()
    {
        if ($this->_quote->getIsVirtual()) {
            return false;
        }
        return $this->_quote->getShippingAddress();
    }

    /**
     * Get HTML output for specified address
     *
     * @param Mage_Sales_Model_Quote_Address
     * @return string
     */
    public function renderAddress($address)
    {
        return $address->getFormated(true);
    }

    /**
     * Return carrier name from config, base on carrier code
     *
     * @param $carrierCode string
     * @return string
     */
    public function getCarrierName($carrierCode)
    {
        if ($name = Mage::getStoreConfig("carriers/{$carrierCode}/title")) {
            return $name;
        }
        return $carrierCode;
    }

    /**
     * Get either shipping rate code or empty value on error
     *
     * @param Varien_Object $rate
     * @return string
     */
    public function renderShippingRateValue(Varien_Object $rate)
    {
        if ($rate->getErrorMessage()) {
            return '';
        }
        return $rate->getCode();
    }

    /**
     * Get shipping rate code title and its price or error message
     *
     * @param Varien_Object $rate
     * @param string $format
     * @param string $inclTaxFormat
     * @return string
     */
    public function renderShippingRateOption($rate, $format = '%s - %s%s', $inclTaxFormat = ' (%s %s)')
    {
        $renderedInclTax = '';
        if ($rate->getErrorMessage()) {
            $price = $rate->getErrorMessage();
        } else {
            $price = $this->_getShippingPrice(
                $rate->getPrice(),
                $this->helper('tax')->displayShippingPriceIncludingTax()
            );

            $incl = $this->_getShippingPrice($rate->getPrice(), true);
            if (($incl != $price) && $this->helper('tax')->displayShippingBothPrices()) {
                $renderedInclTax = sprintf($inclTaxFormat, Mage::helper('tax')->__('Incl. Tax'), $incl);
            }
        }
        return sprintf($format, $this->escapeHtml($rate->getMethodTitle()), $price, $renderedInclTax);
    }

    /**
     * Getter for current shipping rate
     *
     * @return Mage_Sales_Model_Quote_Address_Rate
     */
    public function getCurrentShippingRate()
    {
        return $this->_currentShippingRate;
    }

    /**
     * Return formatted shipping price
     *
     * @param float $price
     * @param bool $isInclTax
     *
     * @return bool
     */
    protected function _getShippingPrice($price, $isInclTax)
    {
        return $this->_formatPrice($this->helper('tax')->getShippingPrice($price, $isInclTax, $this->_address));
    }

    /**
     * Format price base on store convert price method
     *
     * @param float $price
     * @return string
     */
    protected function _formatPrice($price)
    {
        return $this->_quote->getStore()->convertPrice($price, true);
    }

    /**
     * Retrieve payment method and assign additional template values
     *
     * @return Mage_Paypal_Block_Express_Review
     */
    protected function _beforeToHtml()
    {
        $methodInstance = $this->_quote->getPayment()->getMethodInstance();
        $this->setPaymentMethodTitle($methodInstance->getTitle());

        $this->setShippingRateRequired(true);
        if ($this->_quote->getIsVirtual()) {
            $this->setShippingRateRequired(false);
        } else {
            // prepare shipping rates
            $this->_address = $this->_quote->getShippingAddress();
            $groups = $this->_address->getGroupedAllShippingRates();
            if ($groups && $this->_address) {
                $this->setShippingRateGroups($groups);
                // determine current selected code & name
                foreach ($groups as $code => $rates) {
                    foreach ($rates as $rate) {
                        if ($this->_address->getShippingMethod() == $rate->getCode()) {
                            $this->_currentShippingRate = $rate;
                            break(2);
                        }
                    }
                }
            }
        }

        return parent::_beforeToHtml();
    }

    /**
     * Returns place order URL
     * 
     * @return string
     */
    public function getPlaceOrderUrl()
    {
        return $this->getUrl('braintree/checkout/placeorder');
    }

    /**
     * Returns shipping method update URL
     * 
     * @return string
     */
    public function getShippingMethodSubmitUrl()
    {
        return $this->getUrl('braintree/checkout/shippingmethod');
    }

    /**
     * Returns edit shipping address URL
     * 
     * @return string
     */
    public function getEditUrl()
    {
        return $this->getUrl('braintree/checkout/editaddress', array('action' => 'edit'));
    }
}
