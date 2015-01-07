<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Mysql4_Setup */

$installer->startSetup();
$connection = $installer->getConnection();

//
// Subscriber Attributes
$connection->addColumn(
    $installer->getTable('newsletter/subscriber'), 'bronto_imported', 'datetime NULL default NULL'
);

try {
    $installer->getConnection()->addKey(
        $installer->getTable('newsletter/subscriber'), 'IDX_BRONTO_IMPORTED', 'bronto_imported'
    );
} catch (Exception $e) {
    throw new RuntimeException('Table Already Exists');
}

$installer->endSetup();

// Mark installation date
$config = Mage::getConfig();
$config->saveConfig(Bronto_Newsletter_Helper_Data::XML_PATH_INSTALL_DATE, time());
