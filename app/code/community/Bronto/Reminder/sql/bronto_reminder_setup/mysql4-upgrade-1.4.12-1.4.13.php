<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

try {
    $installer->run("
        ALTER TABLE `{$installer->getTable('bronto_reminder/rule')}`
            ADD COLUMN `send_to` enum('user', 'guest', 'both') DEFAULT 'both';
    ");
} catch (Exception $e) {
    throw new RuntimeException('Failed Modifying Table: ' . $e->getMessage());
}

$installer->endSetup();