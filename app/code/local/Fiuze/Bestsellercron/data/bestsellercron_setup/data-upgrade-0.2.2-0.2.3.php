<?php
$installer = $this;
$installer->startSetup();
$store = Mage::app()->getStore();

//canged  bestseller category
try{
    $bestSellerCategoryId = Mage::getStoreConfig(Fiuze_Bestsellercron_Model_Bestsellers::XML_PATH_BESTSELLER_CATEGORY);
    $currentCategory = Mage::getModel('catalog/category')->load($bestSellerCategoryId);
    $currentCategory->setIncludeInMenu(0);
    $currentCategory->save();
}catch (Exception $ex){
    Mage::throwException($ex);
}
Mage::app()->getCacheInstance()->cleanType('config');

$installer->endSetup();

