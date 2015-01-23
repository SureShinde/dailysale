<?php
/**
 * fall back to create table if existing modules already exists to support upgrade
 */

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

try {
    $installer->run("
        ALTER TABLE `{$this->getTable('bronto_newsletter_queue')}` 
            CHANGE COLUMN `messagePreference` `message_preference` VARCHAR(16) CHARACTER SET 'utf8' NOT NULL;
    ");

} catch (Exception $e) {
    throw new RuntimeException('Error altering table');
}

$installer->endSetup();
