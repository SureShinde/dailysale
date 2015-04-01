<?php

$this->startSetup();

$this->_conn->addColumn($this->getTable('udropship_vendor'), 'batch_import_inventory_ts', 'datetime');
$this->_conn->addColumn($this->getTable('udropship_vendor'), 'batch_import_orders_ts', 'datetime');

$this->_conn->addColumn($this->getTable('udropship_batch'), 'use_wildcard', 'tinyint');
$this->_conn->addColumn($this->getTable('udropship_batch'), 'per_po_rows_text', 'LONGTEXT CHARACTER SET utf8');

$this->endSetup();
