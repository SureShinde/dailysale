<?php

/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer = $this;
$conn = $this->_conn;
$installer->startSetup();

$conn->addColumn($this->getTable('urma/rma'), 'shipment_id', "int(10) unsigned");
$conn->addColumn($this->getTable('urma/rma'), 'shipment_increment_id', "varchar(50)");

$installer->endSetup();
