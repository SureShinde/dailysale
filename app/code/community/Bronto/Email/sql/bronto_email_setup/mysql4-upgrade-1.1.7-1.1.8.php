<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

/**
 * Create new table to hold bronto templates
 */
try {
    $installer->run("
        ALTER TABLE `{$installer->getTable('bronto_email/message')}`
        ADD COLUMN `sales_rule` int(10) NULL;
    ");
} catch (Exception $e) {
    Mage::helper('bronto_email')->writeError($e->getMessage());
}

$installer->endSetup();
