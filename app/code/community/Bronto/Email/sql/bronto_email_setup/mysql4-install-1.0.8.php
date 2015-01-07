<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->run("

CREATE TABLE `{$this->getTable('bronto_email_log')}` (
  `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `contact_id` char(36) DEFAULT NULL,
  `message_id` char(36) NOT NULL,
  `message_name` varchar(64) DEFAULT NULL,
  `delivery_id` char(36) DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `success` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `error` varchar(255) DEFAULT NULL,
  `fields` text,
  PRIMARY KEY (`log_id`),
  KEY `IDX_BRONTO_EMAIL_LOG_CUSTOMER_EMAIL` (`customer_email`),
  KEY `IDX_BRONTO_EMAIL_LOG_SENT_AT` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

try {
    $installer->run("

    ALTER TABLE `{$installer->getTable('core/email_template')}`
    ADD COLUMN `bronto_message_id` char(36) NULL DEFAULT NULL;

    ");
} catch (Exception $e) {
    //
}

try {
    $installer->run("

    ALTER TABLE `{$installer->getTable('core/email_template')}`
    ADD COLUMN `bronto_message_name` varchar(255) NULL DEFAULT NULL;

    ");
} catch (Exception $e) {
    //
}

try {
    $installer->run("

    ALTER TABLE `{$installer->getTable('core/email_template')}`
    ADD COLUMN `bronto_message_approved` tinyint(1) UNSIGNED NOT NULL DEFAULT 1;

    ");
} catch (Exception $e) {
    //
}

$installer->endSetup();

  