<?php

$installer = $this;
/* @var $installer Bronto_Order_Model_Mysql4_Setup */

$installer->startSetup();

//
// Quote Attributes
$installer->addAttribute('quote', 'bronto_tid', array(
    'type'     => 'varchar',
    'required' => false,
));

//
// Order Attributes
$installer->addAttribute('order', 'bronto_tid', array(
    'type'     => 'varchar',
    'required' => false,
));

$installer->addAttribute('order', 'bronto_imported', array(
    'type'     => 'datetime',
    'required' => false,
));

try {
    $installer->getConnection()->addKey(
        $installer->getTable('sales/order'), 'IDX_BRONTO_IMPORTED', 'bronto_imported'
    );
} catch (Exception $e) {
    // Already exists...
}

$installer->endSetup();

// Mark installation date
$config = Mage::getConfig();
$config->saveConfig(Bronto_Order_Helper_Data::XML_PATH_INSTALL_DATE, time());
