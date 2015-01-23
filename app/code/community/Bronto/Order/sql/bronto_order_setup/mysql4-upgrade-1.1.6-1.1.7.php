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
        ALTER TABLE `{$this->getTable('bronto_order_queue')}` DROP FOREIGN KEY `FK_BRONTO_ORDER_QUEUE_STORE_ID_CORE_STORE_STORE_ID`;
        ALTER TABLE `{$this->getTable('bronto_order_queue')}` ADD COLUMN `queue_id` INT(10) NOT NULL AUTO_INCREMENT FIRST, 
        CHANGE COLUMN `quote_id` `quote_id` INT(11) UNSIGNED NOT NULL COMMENT 'Quote Id', 
        CHANGE COLUMN `store_id` `store_id` SMALLINT(5) UNSIGNED NOT NULL COMMENT 'Store Id', 
          ADD CONSTRAINT `FK_BRONTO_ORDER_QUEUE_STORE_ID_CORE_STORE_STORE_ID`
          FOREIGN KEY (`store_id`)
          REFERENCES `{$this->getTable('core_store')}` (`store_id`)
          ON DELETE CASCADE
          ON UPDATE CASCADE
        , DROP PRIMARY KEY 
        , ADD PRIMARY KEY (`queue_id`, `order_id`, `store_id`, `quote_id`);
    ");
} catch (Exception $e) {
    throw new RuntimeException('Failed Modifying Table ' . $e->getMessage());
}

$installer->endSetup();
