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
            ADD COLUMN `queue_id` INT(11) NOT NULL AUTO_INCREMENT  FIRST , 
            CHANGE COLUMN `subscriber_id` `subscriber_id` INT(11) NOT NULL  AFTER `queue_id` , 
            CHANGE COLUMN `store` `store` TINYINT(4) NOT NULL  AFTER `subscriber_id`, 
            DROP PRIMARY KEY, 
            ADD PRIMARY KEY (`queue_id`, `subscriber_id`, `store`);
    ");

} catch (Exception $e) {
    throw new RuntimeException('Error altering table');
}

$installer->endSetup();