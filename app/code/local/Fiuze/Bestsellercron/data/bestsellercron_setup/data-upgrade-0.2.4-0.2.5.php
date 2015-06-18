<?php
$installer = $this;
$installer->startSetup();
$store = Mage::app()->getStore();

$categoryId = Mage::getStoreConfig(Fiuze_Bestsellercron_Model_Bestsellers::XML_PATH_BESTSELLER_CATEGORY);
$rowId =  Mage::getStoreConfig(Fiuze_Bestsellercron_Model_Bestsellers::XML_PATH_BESTSELLER_ROWID);
$value = 'a:1:{s:18:"'.$rowId.'";a:8:{s:8:"checkbox";s:7:"checked";s:8:"criteria";s:3:"qty";s:11:"time_period";a:3:{i:0;s:2:"00";i:1;s:2:"00";i:2;s:2:"00";}s:11:"days_period";s:1:"1";s:7:"history";s:0:"";s:13:"task_schedule";s:11:"*/2 * * * *";s:18:"number_of_products";a:2:{s:14:"count_products";s:2:"20";s:8:"checkbox";s:0:"";}s:8:"category";s:2:"'.$categoryId.'";}}';

$config =Mage::getModel('core/config_data')
    ->load(Fiuze_Bestsellercron_Model_Bestsellers::XML_PATH_GENERAL, 'path')
    ->setValue($value)
    ->setPath(Fiuze_Bestsellercron_Model_Bestsellers::XML_PATH_GENERAL)->cleanModelCache()
    ->save();
$store->setConfig(Fiuze_Bestsellercron_Model_Bestsellers::XML_PATH_GENERAL, $value);

Mage::app()->getCacheInstance()->cleanType('config');

$installer->endSetup();

