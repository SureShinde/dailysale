<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

try {
    $installer->run("

    ALTER TABLE `{$this->getTable('bronto_email_log')}`
    ADD COLUMN `message_name`  varchar(64) NULL DEFAULT NULL AFTER `message_id`,
    ADD COLUMN `fields`  text NULL AFTER `error`,
    ADD INDEX `IDX_BRONTO_EMAIL_LOG_SENT_AT` (`sent_at`) ;

    ");
} catch (Exception $e) {
    //
}

$installer->endSetup();