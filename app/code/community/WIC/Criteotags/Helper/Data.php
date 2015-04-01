<?php

/**
 * Web In Color
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file WIC-LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://store.webincolor.fr/WIC-LICENSE.txt
 * 
 * @package		WIC_Criteotags
 * @copyright   Copyright (c) 2010-2014 Web In Color (http://www.webincolor.fr)
 * @author		Web In Color <contact@webincolor.fr>
 * */
class WIC_Criteotags_Helper_Data extends Mage_Core_Helper_Abstract {

    const XML_CONFIG_TAGS_ENABLE = 'criteotags/general/enable';
    const XML_CONFIG_TAGS_ACCOUNT = 'criteotags/general/account';
    const XML_CONFIG_TAGS_SITETYPE = 'criteotags/general/sitetype';
    const XML_CONFIG_TAGS_PRODUCT_TYPE = 'criteotags/general/producttype';
    
    const XML_CONFIG_EXPORT_ENABLE = 'criteotags/export_criteo/enable';
    const XML_CONFIG_EXPORT_DESCRIPTION = 'criteotags/export_criteo/description';
    const XML_CONFIG_EXPORT_NAME_TEMPLATE = 'criteotags/export_criteo/name_template';
    
    private $_logfilename = 'wic_criteo.log';        

    public function isTagsEnabled() {
        return Mage::getStoreConfigFlag(self::XML_CONFIG_TAGS_ENABLE, Mage::app()->getStore()->getStoreId());
    }

    public function getAccountId() {
        return Mage::getStoreConfig(self::XML_CONFIG_TAGS_ACCOUNT, Mage::app()->getStore()->getStoreId());
    }

    public function getSitetype() {
        return Mage::getStoreConfig(self::XML_CONFIG_TAGS_SITETYPE, Mage::app()->getStore()->getStoreId());
    }
    
    public function getProductType() {
        return Mage::getStoreConfig(self::XML_CONFIG_TAGS_PRODUCT_TYPE, Mage::app()->getStore()->getStoreId());
    }

    public function isExportEnable() {
        return Mage::getStoreConfigFlag(self::XML_CONFIG_EXPORT_DESCRIPTION, Mage::app()->getStore()->getStoreId());
    }

    public function getDescription() {
        return Mage::getStoreConfig(self::XML_CONFIG_TAGS_SITETYPE, Mage::app()->getStore()->getStoreId());
    }

    public function getNameTemplate() {
        return Mage::getStoreConfig(self::XML_CONFIG_EXPORT_NAME_TEMPLATE, Mage::app()->getStore()->getStoreId());
    }        

    public function getCustomerId() {
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customerData = Mage::getSingleton('customer/session')->getCustomer();
            return $customerData->getId();
        }
        return;
    }
    
    public function getCustomerEmail() {
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customerData = Mage::getSingleton('customer/session')->getCustomer();
            return $customerData->getEmail();
        }
        return "";
    }
    
    public function getHashedEmail() {
                        
        $processed_address = strtolower ($this->getCustomerEmail()); //conversion to lower case
	$processed_address = trim ($processed_address); //trimming 
	$processed_address = mb_convert_encoding($processed_address, "UTF-8", "ISO-8859-1");
        
        return md5($processed_address);                
        
    }
        
    
    public function log($message) {
        Mage::log($message, null, $this->_logfilename);
    }
       

}
