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

class Braintree_Payments_Block_Adminhtml_Form_Field_Countries extends Mage_Core_Block_Html_Select
{
    /**
     * Countries cache
     *
     * @var array
     */
    protected $_countries;

    /**
     * Returns countries array
     * 
     * @return array
     */
    protected function _getCountries()
    {
        if (!$this->_countries) {
            $restrictedCountries = Mage::getModel('braintree_payments/system_config_source_country')
                ->getRestrictedCountries();
            $this->_countries = Mage::getResourceModel('directory/country_collection')
                ->addFieldToFilter('country_id', array('nin' => $restrictedCountries))
                ->loadData()
                ->toOptionArray(false);
        }
        return $this->_countries;
    }
    
    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            foreach ($this->_getCountries() as $country) {
                if (isset($country['value']) && $country['value'] && isset($country['label']) && $country['label']) {
                    $this->addOption($country['value'], $country['label']);
                }
            }
        }
        return parent::_toHtml();
    }

    /**
     * Sets name for input element
     * 
     * @param string $value
     * @return Braintree_Payments_Block_Adminhtml_Form_Field_Countries
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }
}
