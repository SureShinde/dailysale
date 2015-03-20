<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

try {
    $installer->run("

    ALTER TABLE `{$installer->getTable('core/email_template')}`
    ADD COLUMN `bronto_message_id` char(36) NOT NULL default '';

    ");
} catch (Exception $e) {
    //
}

try {
    $installer->run("

    ALTER TABLE `{$installer->getTable('core/email_template')}`
    ADD COLUMN `bronto_message_name` varchar(255) NOT NULL default '';

    ");
} catch (Exception $e) {
    //
}

$installer->endSetup();