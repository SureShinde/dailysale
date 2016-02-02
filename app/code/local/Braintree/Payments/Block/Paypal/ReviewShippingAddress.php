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

class Braintree_Payments_Block_Paypal_ReviewShippingAddress extends Mage_Core_Block_Template
{
    protected $_address = null;
    protected $_customer = null;

    /**
     * Return Sales Quote Address model (shipping address)
     *
     * @return Mage_Sales_Model_Quote_Address
     */
    public function getAddress()
    {
        if (is_null($this->_address)) {
            $this->_address = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress();
        }

        return $this->_address;
    }

    /**
     * Get logged in customer
     *
     * @return Mage_Customer_Model_Customer
     */
    public function getCustomer()
    {
        if (is_null($this->_customer)) {
            $this->_customer = Mage::getSingleton('customer/session')->getCustomer();
        }
        return $this->_customer;
    }

    /**
     * If customer has saved addresses
     * 
     * @return int
     */
    public function customerHasAddresses()
    {
        return count($this->getCustomer()->getAddresses());
    }

    /**
     * Html select for customer addresses
     * 
     * @return string
     */
    public function getAddressesHtmlSelect()
    {
        $options = array();
        foreach ($this->getCustomer()->getAddresses() as $address) {
            $options[] = array(
                'value' => $address->getId(),
                'label' => $address->format('oneline')
            );
        }

        $addressId = $this->getAddress()->getCustomerAddressId();
        if (empty($addressId)) {
            $address = $this->getCustomer()->getPrimaryShippingAddress();
            if ($address) {
                $addressId = $address->getId();
            }
        }

        $select = $this->getLayout()->createBlock('core/html_select')
            ->setName('shipping_address_id')
            ->setId('shipping-address-select')
            ->setClass('address-select')
            ->setValue($addressId)
            ->setOptions($options);

        $select->addOption('', $this->__('New Address'));

        return $select->getHtml();
    }

    /**
     * Returns save shipping address URL
     * 
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('braintree/checkout/saveaddress');
    }

    /**
     * Html select for countries
     * 
     * @return string
     */
    public function getCountryHtmlSelect()
    {
        $countryId = $this->getAddress()->getCountryId();
        if (is_null($countryId)) {
            $countryId = Mage::helper('core')->getDefaultCountry();
        }
        $select = $this->getLayout()->createBlock('core/html_select')
            ->setName('shipping[country_id]')
            ->setId('shipping:country_id')
            ->setTitle(Mage::helper('checkout')->__('Country'))
            ->setClass('validate-select')
            ->setValue($countryId)
            ->setOptions($this->getCountryOptions());

        return $select->getHtml();
    }

    /**
     * Countries options
     * 
     * @return array
     */
    public function getCountryOptions()
    {
        $options    = false;
        $useCache   = Mage::app()->useCache('config');
        if ($useCache) {
            $cacheId    = 'DIRECTORY_COUNTRY_SELECT_STORE_' . Mage::app()->getStore()->getCode();
            $cacheTags  = array('config');
            if ($optionsCache = Mage::app()->loadCache($cacheId)) {
                $options = unserialize($optionsCache);
            }
        }

        if ($options == false) {
            $options = Mage::getSingleton('directory/country')->getResourceCollection()->loadByStore()->toOptionArray();
            if ($useCache) {
                Mage::app()->saveCache(serialize($options), $cacheId, $cacheTags);
            }
        }
        return $options;
    }

    /**
     * Address is edited or there are no address and it's required
     * 
     * @return string
     */
    public function getAction()
    {
        if (Mage::app()->getRequest()->getParam('action') == 'add') {
            return $this->__('Specify');
        } else {
            return $this->__('Edit');
        }
    }
}
