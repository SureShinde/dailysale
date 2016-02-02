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

class Braintree_Payments_Block_Directory_Data extends Mage_Directory_Block_Data
{
    /**
     * Prepares html with countries
     * 
     * @param string $defValue
     * @param string $name
     * @param string $id
     * @param string $title
     * @return string
     */
    public function getCountryHtmlSelect($defValue=null, $name='country_id', $id='country', $title='Country')
    {
        if (is_null($defValue)) {
            $defValue = $this->getCountryId();
        }
        $cacheKey = 'BRAINTREE_DIRECTORY_COUNTRY_SELECT_STORE_'.Mage::app()->getStore()->getCode();
        if (Mage::app()->useCache('config') && $cache = Mage::app()->loadCache($cacheKey)) {
            $options = unserialize($cache);
        } else {
            $options = $this->getCountryCollection()->toOptionArray(false);
        }
        $html = $this->getLayout()->createBlock('core/html_select')
            ->setName($name)
            ->setId($id)
            ->setTitle(Mage::helper('braintree_payments')->__($title))
            ->setClass('validate-select')
            ->setValue($defValue)
            ->setOptions($options)
            ->getHtml();
        return $html;
    }

    /**
     * Loads country collection
     * 
     * @return Mage_Directory_Model_Resource_Country_Collection
     */
    public function getCountryCollection()
    {
        $collection = $this->getData('country_collection');
        if (is_null($collection)) {
            $restrictedCountries = Mage::getModel('braintree_payments/system_config_source_country')
                ->getRestrictedCountries();
            $collection = Mage::getResourceModel('directory/country_collection')
                ->addFieldToFilter('country_id', array('nin' => $restrictedCountries))
                ->loadByStore();
            $this->setData('country_collection', $collection);
        }
        return $collection;
    }
}
