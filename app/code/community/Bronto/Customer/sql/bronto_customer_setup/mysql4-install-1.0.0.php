<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Mysql4_Setup */

$installer->startSetup();

//
// Customer Attributes
$installer->addAttribute('customer', 'bronto_imported', array(
    'type'     => 'datetime',
    'required' => false,
));

// try {
//     $installer->getConnection()->addKey(
//         $installer->getTable('sales/order'), 'IDX_BRONTO_IMPORTED', 'bronto_imported'
//     );
// } catch (Exception $e) {
//     // Already exists...
// }

$installer->endSetup();

// Mark installation date
$config = Mage::getConfig();
$config->saveConfig(Bronto_Customer_Helper_Data::XML_PATH_INSTALL_DATE, time());
