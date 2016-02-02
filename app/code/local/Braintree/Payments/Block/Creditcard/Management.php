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

class Braintree_Payments_Block_Creditcard_Management extends Mage_Core_Block_Template
{
    const TYPE_NEW_CART     = 'cart';
    const TYPE_NEW_CUSTOMER = 'customer';
    const TYPE_EDIT         = 'edit';

    protected $_braintree;

    /**
     * Internal constructor. Set template, model
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('braintree/creditcard/index.phtml');
        $this->_braintree = Mage::getModel('braintree_payments/creditcard');
    }

    /**
     * Returns credit card
     * 
     * @return Braintree_CreditCard
     */
    public function creditCard()
    {
        $result = Mage::registry('braintree_result');
        if (!empty($result)) {
            $token = ($result->success) ? $result->creditCard->token : $result->params['paymentMethodToken'];
        } else {
            $token = Mage::app()->getRequest()->getParam('token');
        }
        return $this->_braintree->storedPaymentMethod($token);
    }

    /**
     * Returns value for post param
     * 
     * @param string $index
     * @param string $default
     * @return string | null
     */
    public function getPostParam($index, $default='')
    {
        $result = Mage::registry('braintree_result');
        if (!empty($result)) {
            $indices = explode('.', $index);
            $value = $result->params;
            foreach ($indices as $key) {
                if (isset($value[$key]) && !is_array($value[$key])) {
                    return $value[$key];
                }
            }
        }
        return $default;
    }

    /**
     * If make default should be shown or not
     * 
     * @return boolean
     */
    public function canShowMakeDefault()
    {
        if ($this->getType() == self::TYPE_NEW_CART || $this->getType() == self::TYPE_EDIT) {
            return true;
        }
        return false;
    }

    /**
     * Returns page title
     * 
     * @return string
     */
    public function getTitle()
    {
        $title = '';
        if ($this->getType() == self::TYPE_EDIT) {
            $title = 'Edit Credit Card';
        } else {
            $title = 'Add Credit Card';
        }
        return $this->__($title);
    }

    /**
     * If cart is edited
     * 
     * @return boolean
     */
    public function isEditMode()
    {
        if ($this->getType() == self::TYPE_EDIT) {
            return true;
        }
        return false;
    }

    /**
     * Returns html select for country
     * 
     * @param string $name
     * @param string $id
     * @param string $default
     * @param string $title
     * @return string
     */
    public function countrySelect($name, $id, $default = '', $title = 'Country')
    {
        return Mage::app()->getLayout()->createBlock('braintree_payments/directory_data')
            ->getCountryHtmlSelect($default, $name, $id, $title);
    }

    /**
     * Returns url for edit
     * 
     * @param string $token
     * @return string
     */
    public function getEditUrl($token)
    {
        return $this->getUrl('customer/creditcard/edit', array('token' => $token));
    }

    /**
     * Returns url for delete
     * 
     * @param string $token
     * @return string
     */
    public function getDeleteUrl($token)
    {
        return $this->getUrl('customer/creditcard/delete', array('token' => $token));
    }

    /**
     * Returns url for add
     * 
     * @return string
     */
    public function getAddUrl()
    {
        return $this->getUrl('customer/creditcard/new');
    }

    /**
     * Returns url for add
     * 
     * @return string
     */
    public function getDeleteConfirmUrl()
    {
        return $this->getUrl('customer/creditcard/deleteconfirm');
    }

    /**
     * Returns url for edit form
     * 
     * @return string
     */
    public function getFormAction()
    {
        if ($this->getType() == self::TYPE_EDIT) {
            $url = $this->getUrl(
                'customer/creditcard/update', 
                array('token' => Mage::app()->getRequest()->getParam('token'))
            );
        } else {
            $url = $this->getUrl('customer/creditcard/save');
        }
        return $url;
    }

    /**
     * Returns url for edit form
     * 
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('customer/creditcard/index');
    }

    /**
     * Returns region code by name
     * 
     * @param string $region
     * @return string
     */
    public function getRegionIdByName($region)
    {
        if ($region) {
            $collection = Mage::getModel('directory/region')
                ->getCollection()
                ->addRegionNameFilter($region)
                ->removeAllFieldsFromSelect()
                ->setPageSize(1)
                ->setCurPage(1);
            if ($collection->getSize()) {
                return $collection->getFirstItem()->getId();
            }
        }
        return '';
    }

    /**
     * Retrieve credit card expire months
     *
     * @return array
     */
    public function getCcMonths()
    {
        $months = $this->getData('cc_months');
        if (is_null($months)) {
            $months[0] =  $this->__('Month');
            $months = array_merge($months, Mage::getSingleton('payment/config')->getMonths());
            $this->setData('cc_months', $months);
        }
        return $months;
    }

    /**
     * Retrieve credit card expire years
     *
     * @return array
     */
    public function getCcYears()
    {
        $years = $this->getData('cc_years');
        if (is_null($years)) {
            $years = Mage::getSingleton('payment/config')->getYears();
            $years = array(0=>$this->__('Year'))+$years;
            $this->setData('cc_years', $years);
        }
        return $years;
    }

    /**
     * If CVV for card is required
     * 
     * @return boolean
     */
    public function hasVerification()
    {
        return Mage::getStoreConfigFlag('payment/braintree/useccv');
    }

    /**
     * Gets default card data or empty strings if not applicable
     * 
     * @return Varien_Object
     */
    public function getDefaultCartData()
    {
        $params = new Varien_Object();
        if ($this->isEditMode()) {
            $creditCard     = $this->creditCard();
            $billingAddress = $creditCard->billingAddress;
            $params->setExpDate($this->escapeHtml($creditCard->expirationDate));
            $params->setIsCCDefault($creditCard->isDefault() ? true : false);
            $params->setCartholder($this->escapeHtml($creditCard->cardholderName));
            $params->setFirstName($this->escapeHtml($billingAddress->firstName));
            $params->setLastName($this->escapeHtml($billingAddress->lastName));
            $params->setCompany($this->escapeHtml($billingAddress->company));
            $params->setLocality($this->escapeHtml($billingAddress->locality));
            $params->setRegion($this->escapeHtml($billingAddress->region));
            $params->setPostalCode($this->escapeHtml($billingAddress->postalCode));
            $params->setStreetAddress($this->escapeHtml($billingAddress->streetAddress));
            $params->setExtendedAddress($this->escapeHtml($billingAddress->extendedAddress));
            $params->setCountryCodeAlpha2($this->escapeHtml($billingAddress->countryCodeAlpha2));
            $params->setRegionId($this->getRegionIdByName($billingAddress->region));
        }
        if ($params->getExpDate()) {
            list($defaultExpMonth, $defaultExpYear) = explode('/', $this->escapeHtml($params->getExpDate()));
            $params->setExpMonth($defaultExpMonth);
            $params->setExpYear($defaultExpYear);
        } else {
            $params->setExpMonth(Mage::helper('braintree_payments')->getTodayMonth());
            $params->setExpYear(Mage::helper('braintree_payments')->getTodayYear());
        }
        return $params;
    }
}
