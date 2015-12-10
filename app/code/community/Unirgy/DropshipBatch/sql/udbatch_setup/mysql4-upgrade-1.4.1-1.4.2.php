<?php

$this->startSetup();

$this->run("

CREATE TABLE IF NOT EXISTS `{$this->getTable('udropship_batch_invrow')}` (
  `row_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `batch_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned DEFAULT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `vendor_sku` varchar(50) DEFAULT NULL,
  `vendor_cost` decimal(12,4) DEFAULT NULL,
  `stock_qty` decimal(12,4) DEFAULT NULL,
  `has_error` tinyint(4) DEFAULT NULL,
  `error_info` text,
  `row_json` text,
  PRIMARY KEY (`row_id`),
  KEY `FK_udropship_batch_invrow` (`batch_id`),
  CONSTRAINT `FK_udropship_batch_invrow` FOREIGN KEY (`batch_id`) REFERENCES `{$this->getTable('udropship_batch')}` (`batch_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$this->_conn->addColumn($this->getTable('udropship_vendor'), 'batch_import_inventory_method', 'varchar(20)');
$this->_conn->addColumn($this->getTable('udropship_vendor'), 'batch_import_inventory_schedule', 'varchar(50)');

$this->endSetup();
