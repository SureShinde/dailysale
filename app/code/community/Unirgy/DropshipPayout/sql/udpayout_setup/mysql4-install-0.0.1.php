<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    Unirgy_DropshipPayout
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

$this->startSetup();

$this->run("

CREATE TABLE IF NOT EXISTS `{$this->getTable('udropship_payout')}` (
  `payout_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` int(10) unsigned NOT NULL,
  `payout_type` varchar(50) NOT NULL,
  `payout_method` varchar(50) NOT NULL,
  `payout_status` varchar(20) DEFAULT NULL,
  `orders_data` longtext not null,
  `total_orders` mediumint not null,
  `total_payout` decimal(12,4) not null,
  `created_at` datetime DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `notes` text,
  `error_info` text,
  KEY `IDX_PAYOUT_STATUS` (`payout_status`),
  KEY `IDX_CREATED_AT` (`created_at`),
  KEY `IDX_SCHEDULED_AT` (`scheduled_at`),
  PRIMARY KEY (`payout_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{$this->getTable('udropship_payout_row')}` (
  `row_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `payout_id` int(10) unsigned NOT NULL,
  `order_id` int(10) unsigned DEFAULT NULL,
  `shipment_id` int(10) unsigned DEFAULT NULL,
  `order_increment_id` varchar(50) DEFAULT NULL,
  `shipment_increment_id` varchar(50) DEFAULT NULL,
  `order_created_at` datetime DEFAULT NULL,
  `shipment_created_at` datetime DEFAULT NULL,
  `has_error` tinyint(4) DEFAULT NULL,
  `error_info` text,
  `row_json` text,
  PRIMARY KEY (`row_id`),
  UNIQUE KEY `UNQ_SHIPMENT_PAYOUT` (`shipment_id`,`payout_id`),
  KEY `FK_udropship_payout_row` (`payout_id`),
  CONSTRAINT `FK_udropship_payout_row` FOREIGN KEY (`payout_id`) REFERENCES `{$this->getTable('udropship_payout')}` (`payout_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$this->_conn->addColumn($this->getTable('udropship_vendor'), 'payout_type', 'varchar(50)');
$this->_conn->addColumn($this->getTable('udropship_vendor'), 'payout_method', 'varchar(50)');
$this->_conn->addColumn($this->getTable('udropship_vendor'), 'payout_schedule', 'varchar(50)');

$this->endSetup();
