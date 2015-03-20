<?php

$installer = $this;
/* @var $installer Bronto_News_Model_Resource_Setup */

$installer->startSetup();

try {
    $installer->run("
	CREATE TABLE `{$installer->getTable('bronto_news_item')}` (
		`item_id` int(10) unsigned NOT NULL auto_increment,
		`link` varchar(255) NOT NULL,
		`title` varchar(255) NOT NULL,
		`description` text NOT NULL,
		`pub_date` datetime NOT NULL,
		`type` varchar(20) NOT NULL,
		`notification_id` int(10) unsigned NULL,
		PRIMARY KEY (`item_id`),
		KEY `IDX_BRONTO_NEWS_ITEM_LINK` (`link`),
		KEY `IDX_BRONTO_NEWS_ITEM_TYPE` (`type`),
		KEY `IDX_BRONTO_NEWS_ITEM_TITLE` (`title`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	");
} catch (Exception $e) {
    Mage::helper('bronto_news')->writeError($e->getMessage());
    Mage::helper('bronto_news')->writeError($e->getTraceAsString());
}

$installer->loadInitialSettings();

$installer->createInitialItems();

$installer->endSetup();
