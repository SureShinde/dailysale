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
            PRIMARY KEY (`core_template_id`),
            CONSTRAINT `FK_BRONTO_EMAIL_TEMPLATE_ID_CORE_EMAIL_TEMPLATE_ID` FOREIGN KEY (`core_template_id`) 
            REFERENCES `{$installer->getTable('core/email_template')}` (`template_id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Bronto Email Template Attributes'
    ");
} catch (Exception $e) {
    Mage::helper('bronto_email')->writeError($e->getMessage());
}

/**
 * Move bronto email template attributes to new bronto table
 */
try {
    $installer->run("
        INSERT IGNORE INTO `{$this->getTable('bronto_email/message')}`
            SELECT `template_id`, 'magento', `template_text`, `bronto_message_id`, `bronto_message_name`, `bronto_message_approved`, `store_id`
            FROM `{$installer->getTable('core/email_template')}`;
    ");

    /**
     * Remove columns that were added to core/email_template table
     * within same try/catch so we don't remove columns if insert fails
     */
    $installer->run("
        ALTER TABLE `{$installer->getTable('core/email_template')}` DROP `bronto_message_id`;
        ALTER TABLE `{$installer->getTable('core/email_template')}` DROP `bronto_message_name`;
        ALTER TABLE `{$installer->getTable('core/email_template')}` DROP `bronto_message_approved`;
        ALTER TABLE `{$installer->getTable('core/email_template')}` DROP `store_id`;
    ");

    $installer->run("
        UPDATE `{$installer->getTable('bronto_email/message')}`
            SET `template_send_type` = 'transactional';
    ");
} catch (Exception $e) {
    Mage::helper('bronto_email')->writeError($e->getMessage());
}

$installer->endSetup();

$installer->setDefaultSending();
