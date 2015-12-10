<?php

$installer = $this;

$installer->startSetup();

try {
    $installer->run("
        DROP TABLE IF EXISTS `{$installer->getTable('bronto_emailcapture/queue')}`;

        CREATE TABLE `{$installer->getTable('bronto_emailcapture/queue')}` (
          `queue_id` varchar(50) NOT NULL PRIMARY KEY COMMENT 'Tracking ID and Store ID',
          `email_address` varchar(255) NOT NULL COMMENT 'Email Address',
          `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Updated At'
        );
    ");

} catch (Exception $e) {
    throw new RuntimeException('Failed Creating Emailcapture Queue Table: ' . $e->getMessage());
}

$installer->endSetup();