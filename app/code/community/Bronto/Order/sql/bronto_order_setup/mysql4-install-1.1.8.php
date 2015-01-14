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
        DROP TABLE IF EXISTS `{$this->getTable('bronto_order_queue')}`;

        CREATE TABLE `{$this->getTable('bronto_order_queue')}` (
            `queue_id` int(10) NOT NULL AUTO_INCREMENT,
            `order_id` int(10) unsigned NOT NULL COMMENT 'Order Entity Id',
            `quote_id` int(11) unsigned NOT NULL COMMENT 'Quote Id',
            `store_id` smallint(5) unsigned NOT NULL COMMENT 'Store Id',
            `bronto_tid` varchar(255) DEFAULT NULL COMMENT 'Bronto Tid',
            `bronto_imported` datetime DEFAULT NULL COMMENT 'Bronto Imported',
            `created_at` timestamp NULL DEFAULT NULL COMMENT 'Created At',
            `updated_at` timestamp NULL DEFAULT NULL COMMENT 'Updated At',
            `bronto_suppressed` varchar(255) DEFAULT NULL,
            PRIMARY KEY (`order_id`,`store_id`,`quote_id`),
            KEY `IDX_BRONTO_ORDER_QUEUE_QUEUE_ID` (`queue_id`),
            KEY `IDX_BRONTO_ORDER_QUEUE_STORE_ID` (`store_id`),
            KEY `IDX_BRONTO_ORDER_QUEUE_QUOTE_ID` (`quote_id`),
            KEY `IDX_BRONTO_ORDER_QUEUE_BRONTO_IMPORTED` (`bronto_imported`),
            KEY `IDX_BRONTO_ORDER_QUEUE_CREATED_AT` (`created_at`),
            KEY `IDX_BRONTO_ORDER_QUEUE_UPDATED_AT` (`updated_at`),
            CONSTRAINT `FK_BRONTO_ORDER_QUEUE_STORE_ID_CORE_STORE_STORE_ID` FOREIGN KEY (`store_id`) 
            REFERENCES {$this->getTable('core_store')} (`store_id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Bronto Order Import Queue'

    ");
} catch (Exception $e) {
    throw new RuntimeException('Failed Creating Order Queue Table: ' . $e->getMessage());
}

try {
    // Populate New Table
    $installer->run("
        INSERT IGNORE INTO `{$this->getTable('bronto_order_queue')}` 
          (`order_id`, `quote_id`, `store_id`, `bronto_tid`, `bronto_imported`, `created_at`, `updated_at`)
          SELECT `entity_id`, `quote_id`, `store_id`, NULL, NULL, `created_at`, `updated_at`
          FROM `{$this->getTable('sales/order')}`;
    ");
} catch (Exception $e) {
    throw new RuntimeException('Failed Populating Order Queue Table: ' . $e->getMessage());
}

$installer->endSetup();