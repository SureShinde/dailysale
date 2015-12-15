<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

/**
 * Create new table to hold bronto templates
 */
try {
    $installer->run("DROP TABLE IF EXISTS `{$installer->getTable('bronto_email/message')}`;");

    $installer->run("
        CREATE TABLE `{$installer->getTable('bronto_email/message')}` (
            `core_template_id` int(10) unsigned NOT NULL COMMENT 'Magento Template ID',
            `template_send_type` varchar(255) NOT NULL DEFAULT 'magento' COMMENT 'Type of message to send as',
            `orig_template_text` text COMMENT 'Original Template Text',
            `bronto_message_id` varchar(36) DEFAULT NULL COMMENT 'Bronto Message ID',
            `bronto_message_name` varchar(255) DEFAULT NULL COMMENT 'Bronto Message Name',
            `bronto_message_approved` smallint(5) unsigned NOT NULL DEFAULT '1' COMMENT 'Bronto Message Transactional Approval Status',
            `store_id` int(11) NOT NULL DEFAULT '1' COMMENT 'Store ID for Template',
            `sales_rule` int(10) NULL COMMENT 'Sales Rule for Coupon Codes',
            PRIMARY KEY (`core_template_id`),
            KEY `IDX_BRONTO_STORE_ID` (`store_id`),
            CONSTRAINT `FK_BRONTO_EMAIL_TEMPLATE_ID_CORE_EMAIL_TEMPLATE_ID` FOREIGN KEY (`core_template_id`)
            REFERENCES `{$installer->getTable('core/email_template')}` (`template_id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Bronto Email Template Attributes'
    ");

} catch (Exception $e) {
    Mage::helper('bronto_email')->writeError($e->getMessage());
}

/**
 * Email Log table
 */
try {

    $installer->run("DROP TABLE IF EXISTS `{$installer->getTable('bronto_email/log')}`;");

    $installer->run("
        CREATE TABLE `{$installer->getTable('bronto_email/log')}` (
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
            KEY `IDX_BRONTO_EMAIL_LOG_CUSTOMER_EMAIL` (`customer_email`),
            KEY `IDX_BRONTO_EMAIL_LOG_SENT_AT` (`sent_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Bronto Email Delivery Logs'
    ");

} catch (Exception $e) {
    Mage::helper('bronto_email')->writeError($e->getMessage());
}

$installer->endSetup();
