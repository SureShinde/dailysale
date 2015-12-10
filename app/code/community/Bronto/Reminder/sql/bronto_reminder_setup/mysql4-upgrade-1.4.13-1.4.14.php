<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

try {
    $installer->run("
        ALTER TABLE `{$installer->getTable('bronto_reminder/message')}`
            ADD COLUMN `send_type` varchar(20) NOT NULL DEFAULT 'transactional';
    ");
} catch (Exception $e) {
    throw new RuntimeException('Failed Modifying Table: ' . $e->getMessage());
}

$installer->endSetup();