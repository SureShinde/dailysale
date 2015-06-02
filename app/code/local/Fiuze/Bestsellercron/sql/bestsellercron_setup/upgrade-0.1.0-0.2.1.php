<?php

$installer = $this;
$installer->startSetup();

$config = new Mage_Core_Model_Config();
$var = $config->getPathVars(Fiuze_Bestsellercron_Model_Bestsellers::XML_PATH_GENERAL);
//$config->saveConfig('stocklist/number', $value, 'default', 0);
Mage::app()->getCacheInstance()->cleanType('config');

die();

$installer->endSetup();

