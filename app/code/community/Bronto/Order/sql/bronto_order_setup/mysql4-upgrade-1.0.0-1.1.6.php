<?php
/**
 * fall back to create table if existing modules already exists to support upgrade
 */

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup|Mage_Core_Model_Mysql4_Setup */

$installer->startSetup();

try {
    // Create New Table
    $installer->run("
        CREATE TABLE IF NOT EXISTS `{$this->getTable('bronto_order_queue')}` (
          `order_id` int(10) unsigned NOT NULL COMMENT 'Order Entity Id',
          `quote_id` int(11) DEFAULT NULL COMMENT 'Quote Id',
          `store_id` smallint(5) unsigned DEFAULT NULL COMMENT 'Store Id',
          `bronto_tid` varchar(255) DEFAULT NULL COMMENT 'Bronto Tid',
          `bronto_imported` datetime DEFAULT NULL COMMENT 'Bronto Imported',
          `created_at` timestamp NULL DEFAULT NULL COMMENT 'Created At',
          `updated_at` timestamp NULL DEFAULT NULL COMMENT 'Updated At',
          PRIMARY KEY (`order_id`),
          KEY `IDX_BRONTO_ORDER_QUEUE_STORE_ID` (`store_id`),
          KEY `IDX_BRONTO_ORDER_QUEUE_QUOTE_ID` (`quote_id`),
          KEY `IDX_BRONTO_ORDER_QUEUE_BRONTO_IMPORTED` (`bronto_imported`),
          KEY `IDX_BRONTO_ORDER_QUEUE_CREATED_AT` (`created_at`),
          KEY `IDX_BRONTO_ORDER_QUEUE_UPDATED_AT` (`updated_at`),
          CONSTRAINT `FK_BRONTO_ORDER_QUEUE_STORE_ID_CORE_STORE_STORE_ID` FOREIGN KEY (`store_id`) REFERENCES {$this->getTable('core_store')} (`store_id`) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Bronto Order Import Queue';
    ");

    // Populate New Table
    $installer->run("
        INSERT IGNORE INTO `{$this->getTable('bronto_order_queue')}` 
          (`order_id`, `quote_id`, `store_id`, `bronto_tid`, `bronto_imported`, `created_at`, `updated_at`)
          SELECT `entity_id`, `quote_id`, `store_id`, `bronto_tid`, `bronto_imported`, `created_at`, `updated_at`
          FROM `{$this->getTable('sales/order')}`;
    ");

    // Remove Quote and Order Attributes that were added on install
    $installer->getConnection()->dropKey(
        $installer->getTable('sales/order'), 'IDX_BRONTO_IMPORTED'
    );
    $installer->removeAttribute('quote', 'bronto_tid');
    $installer->removeAttribute('order', 'bronto_tid');
    $installer->removeAttribute('order', 'bronto_imported');
} catch (Exception $e) {
    throw new RuntimeException('Failed Creating and Populating Table: ' . $e->getMessage());
}

$installer->endSetup();
