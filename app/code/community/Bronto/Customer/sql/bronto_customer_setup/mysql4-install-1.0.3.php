<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Mysql4_Setup */

$installer->startSetup();

try {
    // Create New Table
    $installer->run("
        DROP TABLE IF EXISTS `{$this->getTable('bronto_customer_queue')}`;
            
        CREATE TABLE  IF NOT EXISTS `{$this->getTable('bronto_customer_queue')}` (
          `queue_id` int(10) NOT NULL AUTO_INCREMENT,
          `customer_id` int(10) unsigned NOT NULL COMMENT 'Customer Entity Id',
          `store_id` smallint(5) unsigned NOT NULL COMMENT 'Store Id',
          `bronto_imported` datetime DEFAULT NULL COMMENT 'Bronto Imported',
          `created_at` timestamp NULL DEFAULT NULL COMMENT 'Created At',
          `updated_at` timestamp NULL DEFAULT NULL COMMENT 'Updated At',
          `bronto_suppressed` varchar(255) DEFAULT NULL,
          PRIMARY KEY (`customer_id`,`store_id`),
          KEY `IDX_BRONTO_CUSTOMER_QUEUE_QUEUE_ID` (`queue_id`),
          KEY `IDX_BRONTO_CUSTOMER_QUEUE_STORE_ID` (`store_id`),
          KEY `IDX_BRONTO_CUSTOMER_QUEUE_BRONTO_IMPORTED` (`bronto_imported`),
          KEY `IDX_BRONTO_CUSTOMER_QUEUE_CREATED_AT` (`created_at`),
          KEY `IDX_BRONTO_CUSTOMER_QUEUE_UPDATED_AT` (`updated_at`),
          CONSTRAINT `FK_BRONTO_CUSTOMER_QUEUE_STORE_ID_CORE_STORE_STORE_ID` FOREIGN KEY (`store_id`) 
          REFERENCES `{$this->getTable('core_store')}` (`store_id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Bronto Customer Import Queue';
    ");
} catch (Exception $e) {
    throw new RuntimeException('Failed Creating Customer Queue Table: ' . $e->getMessage());
}

try {
    // Populate New Table
    $installer->run("
        INSERT IGNORE INTO `{$this->getTable('bronto_customer_queue')}` 
          (`customer_id`, `store_id`, `bronto_imported`, `created_at`, `updated_at`)
        SELECT `ce`.`entity_id`, `ce`.`store_id`, null, `ce`.`created_at`, `ce`.`updated_at`
          FROM `{$this->getTable('customer_entity')}` `ce`          
          WHERE `ce`.`is_active` = 1;
    ");
} catch (Exception $e) {
    throw new RuntimeException('Failed Populating Customer Queue Table: ' . $e->getMessage());
}

$installer->endSetup();
