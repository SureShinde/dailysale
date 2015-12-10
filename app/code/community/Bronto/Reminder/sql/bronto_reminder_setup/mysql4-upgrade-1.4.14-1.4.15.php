<?php
$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup|Mage_Core_Model_Mysql4_Setup */

$installer->startSetup();

/**
 * Reminder Delivery Log table
 */
try {

    $installer->run("DROP TABLE IF EXISTS `{$installer->getTable('bronto_reminder/delivery')}`;");

    $installer->run("
        CREATE TABLE `{$installer->getTable('bronto_reminder/delivery')}` (
            `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Log ID',
            `customer_id` int(10) unsigned DEFAULT NULL COMMENT 'Customer ID',
            `customer_email` varchar(255) DEFAULT NULL COMMENT 'Customer Email Address',
            `contact_id` varchar(36) DEFAULT NULL COMMENT 'Bronto Contact ID',
            `message_id` varchar(36) NOT NULL COMMENT 'Bronto Message ID',
            `message_name` varchar(64) DEFAULT NULL COMMENT 'Bronto Message Name',
            `delivery_id` varchar(36) DEFAULT NULL COMMENT 'Bronto Delivery ID',
            `sent_at` datetime DEFAULT NULL COMMENT 'Date Message Sent',
            `success` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Message Send Success',
            `error` varchar(255) DEFAULT NULL COMMENT 'Error Message',
            `fields` text COMMENT 'Fields',
            PRIMARY KEY (`log_id`),
            KEY `IDX_BRONTO_REMINDER_LOG_CUSTOMER_EMAIL` (`customer_email`),
            KEY `IDX_BRONTO_REMINDER_LOG_SENT_AT` (`sent_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Bronto Reminder Delivery Logs'
    ");

} catch (Exception $e) {
    Mage::helper('bronto_reminder')->writeError($e->getMessage());
}