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
        ADD KEY `IDX_BRONTO_STORE_ID` (`store_id`);
    ");
} catch (Exception $e) {
    Mage::helper('bronto_email')->writeError($e->getMessage());
}

$installer->endSetup();
