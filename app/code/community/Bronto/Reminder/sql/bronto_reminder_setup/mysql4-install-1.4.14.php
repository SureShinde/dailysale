<?php
$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup|Mage_Core_Model_Mysql4_Setup */

$installer->startSetup();

try {
    // Create New Reminder Rule Table
    $installer->run("
        DROP TABLE IF EXISTS `{$installer->getTable('bronto_reminder/rule')}`;

        CREATE TABLE `{$installer->getTable('bronto_reminder/rule')}` (
            `rule_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL DEFAULT '',
            `description` text NOT NULL,
            `conditions_serialized` mediumtext NOT NULL,
            `condition_sql` mediumtext,
            `is_active` tinyint(1) unsigned NOT NULL DEFAULT '0',
            `salesrule_id` int(10) unsigned DEFAULT NULL,
            `schedule` varchar(255) NOT NULL DEFAULT '',
            `default_label` varchar(255) NOT NULL DEFAULT '',
            `default_description` text NOT NULL,
            `active_from` datetime DEFAULT NULL,
            `active_to` datetime DEFAULT NULL,
            `send_to` enum('user', 'guest', 'both') DEFAULT 'both',
            PRIMARY KEY (`rule_id`),
            KEY `IDX_BRONTO_REMINDER_SALESRULE` (`salesrule_id`),
            CONSTRAINT `FK_BRONTO_REMINDER_SALESRULE` FOREIGN KEY (`salesrule_id`)
            REFERENCES `{$this->getTable('salesrule')}` (`rule_id`) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ");
} catch (Exception $e) {
    throw new RuntimeException('Failed Creating Reminder Rule Table: ' . $e->getMessage());
}

try {
    // Create New Reminder Rule Website Table
    $installer->run("
        DROP TABLE IF EXISTS `{$installer->getTable('bronto_reminder/website')}`;

        CREATE TABLE `{$installer->getTable('bronto_reminder/website')}` (
          `rule_id` int(10) unsigned NOT NULL,
          `website_id` smallint(5) unsigned NOT NULL,
          PRIMARY KEY (`rule_id`,`website_id`),
          KEY `IDX_BRONTO_REMINDER_WEBSITE` (`website_id`),
          CONSTRAINT `FK_BRONTO_REMINDER_RULE` FOREIGN KEY (`rule_id`)
          REFERENCES `{$installer->getTable('bronto_reminder/rule')}` (`rule_id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ");
} catch (Exception $e) {
    throw new RuntimeException('Failed Creating Reminder Rule Website Table: ' . $e->getMessage());
}

try {
    // Create New Reminder Message Table
    $installer->run("
        DROP TABLE IF EXISTS `{$installer->getTable('bronto_reminder/message')}`;

        CREATE TABLE `{$installer->getTable('bronto_reminder/message')}` (
          `rule_id` int(10) unsigned NOT NULL,
          `store_id` smallint(5) NOT NULL,
          `message_id` varchar(255) NOT NULL DEFAULT '',
          `send_type` varchar(20) NOT NULL DEFAULT 'transactional',
          `label` varchar(255) DEFAULT NULL,
          `description` text,
          PRIMARY KEY (`rule_id`,`store_id`),
          KEY `IDX_BRONTO_REMINDER_MESSAGE_RULE` (`rule_id`),
          KEY `IDX_BRONTO_REMINDER_MESSAGE` (`message_id`),
          CONSTRAINT `FK_BRONTO_REMINDER_MESSAGE_RULE` FOREIGN KEY (`rule_id`)
          REFERENCES `{$installer->getTable('bronto_reminder/rule')}` (`rule_id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ");
} catch (Exception $e) {
    throw new RuntimeException('Failed Creating Reminder Message Table: ' . $e->getMessage());
}

try {
    // Create New Reminder Rule Coupon Table
    $installer->run("
        DROP TABLE IF EXISTS `{$installer->getTable('bronto_reminder/coupon')}`;

        CREATE TABLE `{$installer->getTable('bronto_reminder/coupon')}` (
          `rule_id` int(10) unsigned NOT NULL,
          `coupon_id` int(10) unsigned DEFAULT NULL,
          `unique_id` varchar(20) NOT NULL,
          `store_id` int(10) unsigned NOT NULL,
          `customer_id` int(10) unsigned NOT NULL,
          `visitor_id` int(10) unsigned NOT NULL DEFAULT '0',
          `quote_id` int(10) unsigned NOT NULL DEFAULT '0',
          `wishlist_id` int(10) unsigned NOT NULL DEFAULT '0',
          `associated_at` datetime NOT NULL,
          `emails_failed` smallint(5) unsigned NOT NULL DEFAULT '0',
          `is_active` tinyint(1) unsigned NOT NULL DEFAULT '1',
          PRIMARY KEY (`rule_id`,`unique_id`,`store_id`,`customer_id`,`visitor_id`,`quote_id`,`wishlist_id`),
          KEY `IDX_BRONTO_REMINDER_RULE_COUPON` (`rule_id`),
          CONSTRAINT `FK_BRONTO_REMINDER_RULE_COUPON` FOREIGN KEY (`rule_id`)
          REFERENCES `{$installer->getTable('bronto_reminder/rule')}` (`rule_id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    ");
} catch (Exception $e) {
    throw new RuntimeException('Failed Creating Reminder Rule Coupon Table: ' . $e->getMessage());
}

try {
    // Create New Reminder Rule Website Table
    $installer->run("
        DROP TABLE IF EXISTS `{$installer->getTable('bronto_reminder/log')}`;

        CREATE TABLE `{$installer->getTable('bronto_reminder/log')}` (
            `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `rule_id` int(10) unsigned NOT NULL,
            `unique_id` varchar(20) NOT NULL,
            `sent_at` datetime NOT NULL,
            `bronto_delivery_id` varchar(255) DEFAULT NULL,
            `bronto_message_id` varchar(255) DEFAULT NULL,
            PRIMARY KEY (`log_id`),
            KEY `IDX_BRONTO_REMINDER_LOG_RULE` (`rule_id`),
            CONSTRAINT `FK_BRONTO_REMINDER_LOG_RULE` FOREIGN KEY (`rule_id`)
            REFERENCES `{$installer->getTable('bronto_reminder/rule')}` (`rule_id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    ");
} catch (Exception $e) {
    throw new RuntimeException('Failed Creating Reminder Rule Log Table: ' . $e->getMessage());
}

$installer->endSetup();
