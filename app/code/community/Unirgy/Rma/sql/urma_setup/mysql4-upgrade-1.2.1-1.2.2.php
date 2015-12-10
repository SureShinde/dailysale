<?php

/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer = $this;
$conn = $this->_conn;
$installer->startSetup();

$conn->addColumn($this->getTable('sales_flat_order_item'), 'qty_urma', "decimal(12,4) default '0.0000'");

$installer->endSetup();
