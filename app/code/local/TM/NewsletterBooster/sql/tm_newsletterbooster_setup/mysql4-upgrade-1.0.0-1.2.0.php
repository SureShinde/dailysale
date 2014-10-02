<?php
$installer = $this;

$installer->startSetup();

$installer->run("

CREATE TABLE IF NOT EXISTS {$this->getTable('newsletterbooster/subscriber')} (
    `subscriber_id` int(11) NOT NULL AUTO_INCREMENT,
    `campaign_id` int(11) NOT NULL,
    `entity_id` int(10) unsigned NOT NULL,
    `create_at` timestamp NULL DEFAULT NULL,
    `is_guest` TINYINT(1) NOT NULL DEFAULT '0',
    `firstname` varchar(60) NULL default NULL,
    `lastname` varchar(60) NULL default NULL,
    `email` varchar(60) NULL default NULL,
    `store` int(10) unsigned NOT NULL,
    PRIMARY KEY (`subscriber_id`),
    KEY `campaign_id` (`campaign_id`),
    KEY `entity_id` (`entity_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE {$this->getTable('newsletterbooster/subscriber')}
    ADD CONSTRAINT `tm_newsletterbooster_campaign_subscriber_1`
    FOREIGN KEY (`campaign_id`)
    REFERENCES {$this->getTable('newsletterbooster/campaign')} (`campaign_id`)
    ON DELETE CASCADE ON UPDATE CASCADE;

");
$installer->getConnection()->addColumn(
    $installer->getTable('newsletterbooster/campaign'), 
    'in_frontend', 
    'TINYINT(1)  NOT NULL default 0'
);
$installer->getConnection()->addColumn(
    $installer->getTable('newsletterbooster/campaign'), 
    'google_content', 
    'VARCHAR(80) DEFAULT NULL AFTER `google_title`'
);
$installer->getConnection()->addColumn(
    $installer->getTable('newsletterbooster/campaign'), 
    'description', 
    'TEXT DEFAULT NULL'
);

$installer->getConnection()->addColumn(
    $installer->getTable('newsletterbooster/campaign'), 
    'description', 
    'TEXT DEFAULT NULL'
);

$installer->getConnection()->addColumn(
    $installer->getTable('newsletterbooster/unsubscribe'), 
    'entity_id', 
    'INT(10) DEFAULT NULL'
);

$installer->getConnection()->addColumn(
    $installer->getTable('newsletterbooster/unsubscribe'), 
    'email', 
    'varchar(40) NULL default NULL'
);

$installer->getConnection()->addColumn(
    $installer->getTable('newsletterbooster/unsubscribe'), 
    'firstname', 
    'varchar(60) NULL default NULL'
);

$installer->getConnection()->addColumn(
    $installer->getTable('newsletterbooster/unsubscribe'), 
    'lastname', 
    'varchar(60) NULL default NULL'
);

$installer->getConnection()->addColumn(
    $installer->getTable('newsletterbooster/unsubscribe'), 
    'is_guest', 
    'TINYINT(1)  NOT NULL default 0'
);
$installer->getConnection()->addColumn(
    $installer->getTable('newsletterbooster/queue'), 
    'guest', 
    'int(11) NOT NULL default 0'
);

$campaignTable = $installer->getTable('newsletterbooster/campaign');
$installer->getConnection()->modifyColumn($campaignTable, 'tm_segment', 'varchar(40) NULL default NULL');
$installer->endSetup();