<?php

class Fiuze_Notifylowstock_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_NOTIFYLOWSTOCK_CORE_MODULE = 'notifylowstock/core/module_enabled';
    const XML_PATH_NOTIFYLOWSTOCK_CORE_SCHEDULER = 'notifylowstock/core/scheduler';
    const XML_PATH_TEMPLATE_EMAIL = 'notifylowstock/core/custom_template';
    const XML_PATH_IDENTITY_EMAIL = 'notifylowstock/core/custom_identity';
    const XML_PATH_CATEGORY_ID = 'notifylowstock/core/category';
    const XML_PATH_NOTIFYLOWSTOCK_QUANTITY = 'notifylowstock/core/quantity';
    const XML_PATH_NOTIFYLOWSTOCK_EMAIL = 'notifylowstock/core/email_address_list';

    public function getModuleEnabled()
    {
        $storeId = Mage::app()->getStore()->getId();
        return (int)Mage::getStoreConfig(self::XML_PATH_NOTIFYLOWSTOCK_CORE_MODULE, $storeId);
    }

    public function getSchedulerCron()
    {
        $storeId = Mage::app()->getStore()->getId();
        return (int)Mage::getStoreConfig(self::XML_PATH_NOTIFYLOWSTOCK_CORE_SCHEDULER, $storeId);
    }

    public function getTemplateEmail()
    {
        $storeId = Mage::app()->getStore()->getId();
        return Mage::getStoreConfig(self::XML_PATH_TEMPLATE_EMAIL, $storeId);
    }

    public function getIdentityEmail()
    {
        $storeId = Mage::app()->getStore()->getId();
        return Mage::getStoreConfig(self::XML_PATH_IDENTITY_EMAIL, $storeId);
    }

    public function getCategoryId()
    {
        $storeId = Mage::app()->getStore()->getId();
        return Mage::getStoreConfig(self::XML_PATH_CATEGORY_ID, $storeId);
    }

    public function getQuantity()
    {
        $storeId = Mage::app()->getStore()->getId();
        return Mage::getStoreConfig(self::XML_PATH_NOTIFYLOWSTOCK_QUANTITY, $storeId);
    }

    public function getEmailArray()
    {
        $storeId = Mage::app()->getStore()->getId();
        $email = Mage::getStoreConfig(self::XML_PATH_NOTIFYLOWSTOCK_EMAIL, $storeId);
        $result = $categoryIds = explode(',', $email);

       array_walk($result, function(&$value){
            $value = trim($value);
        });

        return $result;
    }

}