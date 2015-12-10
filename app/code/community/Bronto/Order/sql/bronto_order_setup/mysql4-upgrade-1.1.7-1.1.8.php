<?php
/**
 * fall back to create table if existing modules already exists to support upgrade
 */

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup|Mage_Core_Model_Mysql4_Setup */

$installer->startSetup();

try {
    // Update Table Keys
    $installer->run("
        ALTER IGNORE TABLE `{$this->getTable('bronto_order_queue')}`
          DROP PRIMARY KEY, ADD PRIMARY KEY (`order_id`,`store_id`,`quote_id`),
          ADD KEY `IDX_BRONTO_ORDER_QUEUE_QUEUE_ID` (`queue_id`);
    ");
} catch (Exception $e) {
    throw new RuntimeException('Failed Updating Keys for Table: ' . $e->getMessage());
}

try {
    // Update Table
    $installer->run("
        ALTER TABLE `{$this->getTable('bronto_order_queue')}`
          ADD COLUMN `bronto_suppressed` VARCHAR(255) NULL DEFAULT NULL;
    ");
} catch (Exception $e) {
    throw new RuntimeException('Failed Modifying Table: ' . $e->getMessage());
}

try {
    $installer->run("
      UPDATE `{$this->getTable('core/config_data')}`
      SET `path` = 'bronto_order/import/description'
      WHERE `path` = 'bronto_order/settings/description_attribute';
    ");
} catch (Exception $e) {
    throw new RuntimeException('Failed migrating settings: ' . $e->getMessage());
}

$installer->endSetup();
