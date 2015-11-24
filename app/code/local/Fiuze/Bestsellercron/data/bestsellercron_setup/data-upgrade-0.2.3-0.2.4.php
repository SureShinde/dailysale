<?php
$installer = $this;
$installer->startSetup();
$store = Mage::app()->getStore();

try{
    $bestSellerCategoryId = Mage::getStoreConfig(Fiuze_Bestsellercron_Model_Bestsellers::XML_PATH_BESTSELLER_CATEGORY);
    $currentCategory = Mage::getModel('catalog/category')->load($bestSellerCategoryId);
    $currentCategory->setName('Bestseller');
    $currentCategory->save();
}catch (Exception $ex){
    Mage::throwException($ex);
}
Mage::app()->getCacheInstance()->cleanType('config');

$installer->endSetup();

