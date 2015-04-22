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
 * @package    Unirgy_Dropship
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

$this->startSetup();

$this->run("

CREATE TABLE IF NOT EXISTS `{$this->getTable('udropship_batch')}` (
  `batch_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` int(10) unsigned NOT NULL,
  `batch_type` varchar(50) NOT NULL,
  `batch_status` varchar(20) DEFAULT NULL,
  `rows_text` longtext,
  `num_rows` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `notes` text,
  `error_info` text,
  PRIMARY KEY (`batch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{$this->getTable('udropship_batch_dist')}` (
  `dist_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `batch_id` int(10) unsigned NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `dist_status` varchar(20) DEFAULT NULL,
  `error_info` text,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`dist_id`),
  KEY `FK_udropship_batch_dist` (`batch_id`),
  KEY `dist_status` (`dist_status`),
  CONSTRAINT `FK_udropship_batch_dist` FOREIGN KEY (`batch_id`) REFERENCES `{$this->getTable('udropship_batch')}` (`batch_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{$this->getTable('udropship_batch_row')}` (
  `row_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `batch_id` int(10) unsigned NOT NULL,
  `order_id` int(10) unsigned DEFAULT NULL,
  `po_id` int(10) unsigned DEFAULT NULL,
  `item_id` int(10) unsigned DEFAULT NULL,
  `track_id` int(10) unsigned DEFAULT NULL,
  `order_increment_id` varchar(50) DEFAULT NULL,
  `po_increment_id` varchar(50) DEFAULT NULL,
  `item_sku` varchar(50) DEFAULT NULL,
  `tracking_id` varchar(50) DEFAULT NULL,
  `has_error` tinyint(4) DEFAULT NULL,
  `error_info` text,
  `row_json` text,
  PRIMARY KEY (`row_id`),
  KEY `FK_udropship_batch_row` (`batch_id`),
  CONSTRAINT `FK_udropship_batch_row` FOREIGN KEY (`batch_id`) REFERENCES `{$this->getTable('udropship_batch')}` (`batch_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$this->_conn->addColumn($this->getTable('udropship_vendor'), 'batch_export_orders_method', 'varchar(20)');
$this->_conn->addColumn($this->getTable('udropship_vendor'), 'batch_export_orders_schedule', 'varchar(50)');
$this->_conn->addColumn($this->getTable('udropship_vendor'), 'batch_import_orders_method', 'varchar(20)');
$this->_conn->addColumn($this->getTable('udropship_vendor'), 'batch_import_orders_schedule', 'varchar(50)');

$eav = new Mage_Sales_Model_Mysql4_Setup('sales_setup');
$eav->addAttribute('shipment', 'udropship_batch_status', array('type' => 'varchar'));

$this->endSetup();