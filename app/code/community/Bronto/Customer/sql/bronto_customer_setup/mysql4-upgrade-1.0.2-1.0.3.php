<?php
/**
 * fall back to create table if existing modules already exists to support upgrade
 */

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup|Mage_Core_Model_Mysql4_Setup */

$installer->startSetup();

try {
    // Update Table
    $installer->run("
        ALTER IGNORE TABLE `{$this->getTable('bronto_customer_queue')}`
        DROP PRIMARY KEY, ADD PRIMARY KEY(`customer_id`, `store_id`),
        ADD KEY `IDX_BRONTO_CUSTOMER_QUEUE_QUEUE_ID` (`queue_id`);
    ");
} catch (Exception $e) {
    throw new RuntimeException('Failed Updating Keys for Table: ' . $e->getMessage());
}

try {
    // Update Table
    $installer->run("
        ALTER TABLE `{$this->getTable('bronto_customer_queue')}`
        ADD COLUMN `bronto_suppressed` VARCHAR(255) NULL DEFAULT NULL;
    ");
} catch (Exception $e) {
    throw new RuntimeException('Failed Modifying Table: ' . $e->getMessage());
}

$installer->endSetup();
