<?php

class Bronto_Reminder_Model_Resource_Setup extends Bronto_Common_Model_Resource_Abstract
{
    /**
     * @see parent
     */
    protected function _module()
    {
        return 'bronto_reminder';
    }

    /**
     * @see parent
     */
    protected function _tables()
    {
        return array(
        'rule' => "
        CREATE TABLE `{table}` (
            `rule_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL DEFAULT '',
            `description` text NOT NULL,
            `conditions_serialized` mediumtext NOT NULL,
            `condition_sql` mediumtext,
            `is_active` tinyint(1) unsigned NOT NULL DEFAULT '0',
            `salesrule_id` int(10) unsigned DEFAULT NULL,
            `product_recommendation_id` int(11) unsigned DEFAULT NULL,
            `schedule` varchar(255) NOT NULL DEFAULT '',
            `default_label` varchar(255) NOT NULL DEFAULT '',
            `default_description` text NOT NULL,
            `active_from` datetime DEFAULT NULL,
            `active_to` datetime DEFAULT NULL,
            `send_to` enum('user', 'guest', 'both') DEFAULT 'both',
            `send_limit` int(10) DEFAULT 1,
            PRIMARY KEY (`rule_id`),
            KEY `IDX_BRONTO_REMINDER_SALESRULE` (`salesrule_id`),
            CONSTRAINT `FK_BRONTO_REMINDER_SALESRULE` FOREIGN KEY (`salesrule_id`)
            REFERENCES `{$this->getTable('salesrule')}` (`rule_id`) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
        'website' => "
        CREATE TABLE `{table}` (
          `rule_id` int(10) unsigned NOT NULL,
          `website_id` smallint(5) unsigned NOT NULL,
          PRIMARY KEY (`rule_id`,`website_id`),
          KEY `IDX_BRONTO_REMINDER_WEBSITE` (`website_id`),
          CONSTRAINT `FK_BRONTO_REMINDER_RULE` FOREIGN KEY (`rule_id`)
          REFERENCES `{$this->getTable('bronto_reminder/rule')}` (`rule_id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
        'message' => "
        CREATE TABLE `{table}` (
          `rule_id` int(10) unsigned NOT NULL,
          `store_id` smallint(5) NOT NULL,
          `message_id` varchar(255) NOT NULL DEFAULT '',
          `send_type` varchar(20) NOT NULL DEFAULT 'transactional',
          `send_flags` int(3) unsigned DEFAULT NULL,
          `label` varchar(255) DEFAULT NULL,
          `description` text,
          PRIMARY KEY (`rule_id`,`store_id`),
          KEY `IDX_BRONTO_REMINDER_MESSAGE_RULE` (`rule_id`),
          KEY `IDX_BRONTO_REMINDER_MESSAGE` (`message_id`),
          CONSTRAINT `FK_BRONTO_REMINDER_MESSAGE_RULE` FOREIGN KEY (`rule_id`)
          REFERENCES `{$this->getTable('bronto_reminder/rule')}` (`rule_id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
        'coupon' => "
        CREATE TABLE `{table}` (
          `rule_id` int(10) unsigned NOT NULL,
          `coupon_id` int(10) unsigned DEFAULT NULL,
          `product_recommendation_id` int(11) unsigned DEFAULT NULL,
          `unique_id` varchar(255) NOT NULL,
          `store_id` int(10) unsigned NOT NULL,
          `customer_id` int(10) unsigned NOT NULL,
          `customer_email` varchar(255) CHARACTER SET utf8 NOT NULL,
          `quote_id` int(10) unsigned NOT NULL DEFAULT '0',
          `wishlist_id` int(10) unsigned NOT NULL DEFAULT '0',
          `associated_at` datetime NOT NULL,
          `emails_failed` smallint(5) unsigned NOT NULL DEFAULT '0',
          `is_active` tinyint(1) unsigned NOT NULL DEFAULT '1',
          PRIMARY KEY (`rule_id`,`unique_id`,`store_id`,`customer_email`),
          KEY `IDX_BRONTO_REMINDER_RULE_COUPON` (`rule_id`),
          CONSTRAINT `FK_BRONTO_REMINDER_RULE_COUPON` FOREIGN KEY (`rule_id`)
          REFERENCES `{$this->getTable('bronto_reminder/rule')}` (`rule_id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
        'log' => "
        CREATE TABLE `{table}` (
            `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `rule_id` int(10) unsigned NOT NULL,
            `unique_id` varchar(255) NOT NULL,
            `sent_at` datetime NOT NULL,
            `bronto_delivery_id` varchar(255) DEFAULT NULL,
            `bronto_message_id` varchar(255) DEFAULT NULL,
            PRIMARY KEY (`log_id`),
            KEY `IDX_BRONTO_REMINDER_LOG_RULE` (`rule_id`),
            CONSTRAINT `FK_BRONTO_REMINDER_LOG_RULE` FOREIGN KEY (`rule_id`)
            REFERENCES `{$this->getTable('bronto_reminder/rule')}` (`rule_id`) ON DELETE CASCADE ON UPDATE CASCADE
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
        'delivery' => "
        CREATE TABLE `{table}` (
            `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Log ID',
            `customer_id` int(10) unsigned DEFAULT NULL COMMENT 'Customer ID',
            `customer_email` varchar(255) DEFAULT NULL COMMENT 'Customer Email Address',
            `contact_id` varchar(36) DEFAULT NULL COMMENT 'Bronto Contact ID',
            `message_id` varchar(36) NOT NULL COMMENT 'Bronto Message ID',
            `message_name` varchar(64) DEFAULT NULL COMMENT 'Bronto Message Name',
            `delivery_id` varchar(36) DEFAULT NULL COMMENT 'Bronto Delivery ID',
            `sent_at` datetime DEFAULT NULL COMMENT 'Date Message Sent',
            `success` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Message Send Success',
            `error` varchar(255) DEFAULT NULL COMMENT 'Error Message',
            `fields` text COMMENT 'Fields',
            PRIMARY KEY (`log_id`),
            KEY `IDX_BRONTO_REMINDER_LOG_CUSTOMER_EMAIL` (`customer_email`),
            KEY `IDX_BRONTO_REMINDER_LOG_SENT_AT` (`sent_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Bronto Reminder Delivery Logs';
        "
        );
    }

    /**
     * @see parent
     */
    protected function _updates()
    {
        return array(
            '1.4.17' => array(
                'rule' => array(
                    'sql' => 'ALTER TABLE {table} ADD COLUMN `product_recommendation_id` int(11) unsigned DEFAULT NULL AFTER `salesrule_id`;'
                ),
                'coupon' => array(
                    'sql' => 'ALTER TABLE {table} ADD COLUMN `product_recommendation_id` int(11) unsigned DEFAULT NULL AFTER `coupon_id`;'
                )
            ),
            '1.4.18' => array(
                'message' => array(
                    'sql' =>
                    "ALTER TABLE {table} ADD COLUMN `send_flags` int(3) unsigned DEFAULT NULL AFTER `send_type`;"
                )
            )
        );
    }
}
