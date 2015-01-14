<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->run("

CREATE TABLE `{$this->getTable('bronto_reminder/rule')}` (
    `rule_id` int(10) unsigned NOT NULL auto_increment,
    `name` varchar(255) NOT NULL default '',
    `description` text NOT NULL,
    `conditions_serialized` mediumtext NOT NULL,
    `condition_sql` mediumtext,
    `is_active` tinyint(1) unsigned NOT NULL default '0',
    `salesrule_id` int(10) unsigned default NULL,
    `schedule` varchar(255) NOT NULL DEFAULT '',
    `default_label` varchar(255) NOT NULL default '',
    `default_description` text NOT NULL,
    `active_from` datetime default NULL,
    `active_to` datetime default NULL,
    PRIMARY KEY  (`rule_id`),
    KEY `IDX_BRONTO_REMINDER_SALESRULE` (`salesrule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `{$this->getTable('bronto_reminder/website')}` (
    `rule_id` int(10) unsigned NOT NULL,
    `website_id` smallint(5) unsigned NOT NULL,
    PRIMARY KEY (`rule_id`,`website_id`),
    KEY `IDX_BRONTO_REMINDER_WEBSITE` (`website_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `{$this->getTable('bronto_reminder/message')}` (
    `rule_id` int(10) unsigned NOT NULL,
    `store_id` smallint(5) NOT NULL,
    `message_id` varchar(255) NOT NULL default '',
    `label` varchar(255) default NULL,
    `description` text default NULL,
    PRIMARY KEY (`rule_id`,`store_id`),
    KEY `IDX_BRONTO_REMINDER_MESSAGE_RULE` (`rule_id`),
    KEY `IDX_BRONTO_REMINDER_MESSAGE` (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `{$this->getTable('bronto_reminder/coupon')}` (
    `rule_id` int(10) unsigned NOT NULL,
    `coupon_id` int(10) unsigned DEFAULT NULL,
    `customer_id` int(10) unsigned NOT NULL,
    `associated_at` datetime NOT NULL,
    `emails_failed` smallint(5) unsigned NOT NULL default '0',
    `is_active` tinyint(1) unsigned NOT NULL default '1',
    PRIMARY KEY (`rule_id`,`customer_id`),
    KEY `IDX_BRONTO_REMINDER_RULE_COUPON` (`rule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `{$this->getTable('bronto_reminder/log')}` (
    `log_id` int(10) unsigned NOT NULL auto_increment,
    `rule_id` int(10) unsigned NOT NULL,
    `customer_id` int(10) unsigned NOT NULL,
    `sent_at` datetime NOT NULL,
    `bronto_delivery_id` varchar(255) NULL DEFAULT NULL,
    `bronto_message_id` varchar(255) NULL DEFAULT NULL,
    PRIMARY KEY (`log_id`),
    KEY `IDX_BRONTO_REMINDER_LOG_RULE` (`rule_id`),
    KEY `IDX_BRONTO_REMINDER_LOG_CUSTOMER` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$installer->getConnection()->addConstraint(
    'FK_BRONTO_REMINDER_RULE',
    $this->getTable('bronto_reminder/website'),
    'rule_id',
    $this->getTable('bronto_reminder/rule'),
    'rule_id'
);

$installer->getConnection()->addConstraint(
    'FK_BRONTO_REMINDER_SALESRULE',
    $this->getTable('bronto_reminder/rule'),
    'salesrule_id',
    $this->getTable('salesrule'),
    'rule_id',
    'SET NULL'
);

$installer->getConnection()->addConstraint(
    'FK_BRONTO_REMINDER_MESSAGE_RULE',
    $this->getTable('bronto_reminder/message'),
    'rule_id',
    $this->getTable('bronto_reminder/rule'),
    'rule_id'
);

$installer->getConnection()->addConstraint(
    'FK_BRONTO_REMINDER_RULE_COUPON',
    $this->getTable('bronto_reminder/coupon'),
    'rule_id',
    $this->getTable('bronto_reminder/rule'),
    'rule_id'
);

$installer->getConnection()->addConstraint(
    'FK_BRONTO_REMINDER_LOG_RULE',
    $this->getTable('bronto_reminder/log'),
    'rule_id',
    $this->getTable('bronto_reminder/rule'),
    'rule_id'
);

$installer->endSetup();
