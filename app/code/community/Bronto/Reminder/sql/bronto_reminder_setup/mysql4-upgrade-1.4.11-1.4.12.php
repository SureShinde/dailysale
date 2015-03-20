<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

try {
    $installer->run("
    TRUNCATE `{$installer->getTable('bronto_reminder/coupon')}`;
    TRUNCATE `{$installer->getTable('bronto_reminder/log')}`;
    TRUNCATE `{$installer->getTable('bronto_reminder/website')}`;
    TRUNCATE `{$installer->getTable('bronto_reminder/message')}`;
    DELETE FROM `{$installer->getTable('bronto_reminder/rule')}`;

    ALTER TABLE `{$installer->getTable('bronto_reminder/coupon')}`
        ADD COLUMN `unique_id` VARCHAR(20) NOT NULL AFTER `coupon_id`,
        ADD COLUMN `store_id` INT(10) UNSIGNED NOT NULL AFTER `unique_id`,
        ADD COLUMN `visitor_id` int(10) unsigned NOT NULL DEFAULT '0' AFTER `customer_id`,
        ADD COLUMN `quote_id` int(10) unsigned NOT NULL DEFAULT '0' AFTER `visitor_id`,
        ADD COLUMN `wishlist_id` int(10) unsigned NOT NULL DEFAULT '0' AFTER `quote_id`,
        DROP PRIMARY KEY,
        ADD PRIMARY KEY (`rule_id`,`unique_id`,`store_id`,`customer_id`,`visitor_id`,`quote_id`,`wishlist_id`);
        
    ALTER TABLE `{$installer->getTable('bronto_reminder/log')}` 
        DROP COLUMN `customer_id` , 
        ADD COLUMN `unique_id` VARCHAR(20) NOT NULL  AFTER `rule_id`, 
        DROP INDEX `IDX_BRONTO_REMINDER_LOG_CUSTOMER`;
    ");
} catch (Exception $e) {
    throw new RuntimeException('Failed Modifying Table: ' . $e->getMessage());
}

$installer->endSetup();