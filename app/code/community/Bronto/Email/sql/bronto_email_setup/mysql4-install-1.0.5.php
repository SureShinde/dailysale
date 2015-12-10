<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->run("

CREATE TABLE `{$this->getTable('bronto_email_log')}` (
    `log_id`  int(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
    `customer_id`  int(10) UNSIGNED NULL DEFAULT NULL ,
    `customer_email`  varchar(255) NULL DEFAULT NULL ,
    `contact_id`  char(36) NULL DEFAULT NULL ,
    `message_id`  char(36) NOT NULL ,
    `delivery_id`  char(36) NULL DEFAULT NULL ,
    `sent_at`  datetime NULL DEFAULT NULL ,
    `success`  tinyint(1) UNSIGNED NOT NULL DEFAULT 0 ,
    `error`  varchar(255) NULL DEFAULT NULL ,
    PRIMARY KEY (`log_id`),
    INDEX `IDX_BRONTO_EMAIL_LOG_CUSTOMER_EMAIL` (`customer_email`)
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