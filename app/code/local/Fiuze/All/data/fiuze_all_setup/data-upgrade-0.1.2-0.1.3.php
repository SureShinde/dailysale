<?php
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer = $this;

$installer->startSetup();
try{
    $pathVar = Mage::getBaseDir('var');
    mkdir($pathVar.DS.'orders'.DS.'new', 0777, true);
    mkdir($pathVar.DS.'orders'.DS.'imported', 0777, true);
    mkdir($pathVar.DS.'orders'.DS.'exported', 0777, true);
    mkdir($pathVar.DS.'orders'.DS.'complete', 0777, true);
}catch (Exception $ex){
    Mage::logException($ex);
}

$installer->endSetup();
Mage::getConfig()->cleanCache();