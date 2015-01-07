<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

try {
    $installer->run("
        ALTER IGNORE TABLE `{$this->getTable('bronto_newsletter_queue')}`
            DROP PRIMARY KEY, ADD PRIMARY KEY(`subscriber_id`, `store`),
            ADD KEY `IDX_BRONTO_NEWSLETTER_QUEUE_QUEUE_ID` (`queue_id`);
    ");

} catch (Exception $e) {
    throw new RuntimeException('Failed Updating Keys for Table: ' . $e->getMessage());
}

try {
    $installer->run("
        ALTER TABLE `{$this->getTable('bronto_newsletter_queue')}`
            ADD COLUMN `created_at` timestamp NULL DEFAULT NULL COMMENT 'Created At',
            ADD COLUMN `updated_at` timestamp NULL DEFAULT NULL COMMENT 'Updated At';
    ");

} catch (Exception $e) {
    throw new RuntimeException('Failed Modifying Table: ' . $e->getMessage());
}

$installer->endSetup();
