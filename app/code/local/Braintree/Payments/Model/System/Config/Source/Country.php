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

class Braintree_Payments_Model_System_Config_Source_Country
{
    protected $_options;

    /**
     * Countries not supported by Braintree
     * List can be found in Braintree website https://support.braintreepayments.com/customer/portal/articles/1142481
     */
    protected $_excludedCountries = array(
        'MM', 'IR', 'SD', 'BY', 'CI', 'CD', 'CG', 'IQ', 'LR', 'KP', 'SL', 'SY', 'ZW', 'AL', 'BA', 'MK', 'ME', 'RS'
    );

    public function toOptionArray($isMultiselect=false)
    {
        if (!$this->_options) {
            $this->_options = Mage::getResourceModel('directory/country_collection')
                ->addFieldToFilter('country_id', array('nin' => $this->_excludedCountries))
                ->loadData()
                ->toOptionArray(false);
        }

        $options = $this->_options;
        if (!$isMultiselect) {
            array_unshift($options, array('value'=>'', 'label'=> Mage::helper('adminhtml')->__('--Please Select--')));
        }

        return $options;
    }

    /**
     * If country is in list of restricted (not supported by Braintree)
     * 
     * @param string $countryId
     * @return boolean
     */
    public function isCountryRestricted($countryId)
    {
        if (in_array($countryId, $this->_excludedCountries)) {
            return true;
        }
        return false;
    }

    /**
     * Returns list of restricted countries
     * 
     * @return array
     */
    public function getRestrictedCountries()
    {
        return $this->_excludedCountries;
    }
}
