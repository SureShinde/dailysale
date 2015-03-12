<?php

/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer = $this;
$conn = $this->_conn;
$installer->startSetup();

$conn->addColumn($installer->getTable('urma/rma'), 'is_admin', "tinyint(1)");
$conn->addColumn($installer->getTable('urma/rma'), 'is_customer', "tinyint(1)");
$conn->addColumn($installer->getTable('urma/rma'), 'username', "varchar(40)");
$conn->addColumn($installer->getTable('urma/rma'), 'resolution_notes', "varchar(255)");
$conn->addColumn($installer->getTable('urma/rma'), 'rma_reason', "varchar(128)");
$conn->addColumn($installer->getTable('urma/rma_item'), 'item_condition', "varchar(128)");

$installer->endSetup();
