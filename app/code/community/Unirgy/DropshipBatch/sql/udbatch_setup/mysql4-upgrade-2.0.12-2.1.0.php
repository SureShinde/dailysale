<?php

$this->startSetup();

$this->_conn->addColumn($this->getTable('udropship_batch_invrow'), 'vendor_title', 'varchar(255)');
$this->_conn->addColumn($this->getTable('udropship_batch_invrow'), 'vendor_price', 'decimal(12,4)');
$this->_conn->addColumn($this->getTable('udropship_batch_invrow'), 'shipping_price', 'decimal(12,4)');
$this->_conn->addColumn($this->getTable('udropship_batch_invrow'), 'status', 'tinyint(1)');
$this->_conn->addColumn($this->getTable('udropship_batch_invrow'), 'state', 'varchar(32)');
$this->_conn->addColumn($this->getTable('udropship_batch_invrow'), 'avail_state', 'varchar(32)');
$this->_conn->addColumn($this->getTable('udropship_batch_invrow'), 'avail_date', 'datetime');
$this->_conn->addColumn($this->getTable('udropship_batch_invrow'), 'special_price', 'decimal(12,4)');
$this->_conn->addColumn($this->getTable('udropship_batch_invrow'), 'special_from_date', 'datetime');
$this->_conn->addColumn($this->getTable('udropship_batch_invrow'), 'special_to_date', 'datetime');

$this->endSetup();
