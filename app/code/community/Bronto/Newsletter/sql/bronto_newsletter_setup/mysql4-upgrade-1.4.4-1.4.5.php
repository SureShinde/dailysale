<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

try {
    $installer->run("
        ALTER TABLE `{$this->getTable('bronto_newsletter_queue')}` 
            ADD COLUMN `bronto_suppressed` varchar(255) DEFAULT NULL;
    ");

} catch (Exception $e) {
    throw new RuntimeException('Error altering table');
}

try {
    // Populate New Table
    $installer->run("
        INSERT IGNORE INTO `{$this->getTable('bronto_newsletter_queue')}` 
        (
          SELECT 
            NULL, 
            `newsletter`.`subscriber_id`, 
            `newsletter`.`store_id`, 
            IF(`newsletter`.`subscriber_status` = 1, 'active', IF(`newsletter`.`subscriber_status` = 2, 'transactional', 'unsub')),
            'html', 
            'api', 
            0, 
            `newsletter`.`subscriber_email`,
            null
          FROM `{$this->getTable('newsletter_subscriber')}` `newsletter` 
          WHERE NOT EXISTS(
            SELECT 1 FROM `{$this->getTable('bronto_newsletter_queue')}` `queue` WHERE 
                `queue`.`subscriber_id`=`newsletter`.`subscriber_id` OR
                `queue`.`subscriber_email`=`newsletter`.`subscriber_email`
        ));
    ");
} catch (Exception $e) {
    throw new RuntimeException('Failed Populating Newsletter Queue Table: ' . $e->getMessage());
}

$installer->endSetup();