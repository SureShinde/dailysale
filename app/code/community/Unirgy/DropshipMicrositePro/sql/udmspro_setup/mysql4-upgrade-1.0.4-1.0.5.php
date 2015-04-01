<?php

/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer = $this;
$conn = $this->_conn;
$installer->startSetup();

$conn->addColumn($this->getTable('udropship/vendor'), 'confirmation_sent', 'tinyint(1)');
$conn->addColumn($this->getTable('udropship/vendor'), 'reject_reason', 'text');

$conn->query("update {$this->getTable('udropship/vendor')} set confirmation_sent=1");

$installer->endSetup();
