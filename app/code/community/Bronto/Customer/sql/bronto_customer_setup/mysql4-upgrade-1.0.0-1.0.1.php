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
        CREATE TABLE IF NOT EXISTS `{$this->getTable('bronto_customer_queue')}` (
          `customer_id` int(10) unsigned NOT NULL COMMENT 'Customer Entity Id',
          `store_id` smallint(5) unsigned DEFAULT NULL COMMENT 'Store Id',
          `bronto_imported` datetime DEFAULT NULL COMMENT 'Bronto Imported',
          `created_at` timestamp NULL DEFAULT NULL COMMENT 'Created At',
          `updated_at` timestamp NULL DEFAULT NULL COMMENT 'Updated At',
          PRIMARY KEY (`customer_id`),
          KEY `IDX_BRONTO_CUSTOMER_QUEUE_STORE_ID` (`store_id`),
          KEY `IDX_BRONTO_CUSTOMER_QUEUE_BRONTO_IMPORTED` (`bronto_imported`),
          KEY `IDX_BRONTO_CUSTOMER_QUEUE_CREATED_AT` (`created_at`),
          KEY `IDX_BRONTO_CUSTOMER_QUEUE_UPDATED_AT` (`updated_at`),
          CONSTRAINT `FK_BRONTO_CUSTOMER_QUEUE_STORE_ID_CORE_STORE_STORE_ID` FOREIGN KEY (`store_id`) REFERENCES `{$this->getTable('core_store')}` (`store_id`) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Bronto Customer Import Queue';
    ");

    // Populate New Table
    $installer->run("
        INSERT IGNORE INTO `{$this->getTable('bronto_customer_queue')}` 
          (`customer_id`, `store_id`, `bronto_imported`, `created_at`, `updated_at`)
        SELECT `ce`.`entity_id`, `ce`.`store_id`, `eav`.`value`, `ce`.`created_at`, `ce`.`updated_at`
          FROM `{$this->getTable('customer_entity')}` `ce`
          LEFT JOIN `{$this->getTable('eav_attribute')}` `ea` ON `ea`.`attribute_code` = 'bronto_imported'
          LEFT JOIN `{$this->getTable('customer_entity_datetime')}` `eav` ON `eav`.`attribute_id` = `ea`.`attribute_id` AND `eav`.`entity_id` = `ce`.`entity_id`
          WHERE `ce`.`is_active` = 1;
    ");

    // Remove Customer Attribute that was added on install
    $installer->removeAttribute('customer', 'bronto_imported');
} catch (Exception $e) {
    throw new RuntimeException('Failed Creating and Populating Table: ' . $e->getMessage());
}

$installer->endSetup();
