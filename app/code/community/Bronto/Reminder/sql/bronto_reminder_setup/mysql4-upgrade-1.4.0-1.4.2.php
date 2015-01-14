<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

try {
    $installer->run("

    ALTER TABLE `{$installer->getTable('bronto_reminder/log')}`
    ADD COLUMN `bronto_delivery_id` varchar(255) NULL DEFAULT NULL,
    ADD COLUMN `bronto_message_id` varchar(255) NULL DEFAULT NULL;

    ");
} catch (Exception $e) {
    //
}

$installer->endSetup();