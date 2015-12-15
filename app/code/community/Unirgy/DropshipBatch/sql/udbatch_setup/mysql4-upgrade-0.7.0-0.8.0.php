<?php

$this->startSetup();

if (Mage::helper('udropship')->isSalesFlat()) {
    $this->_conn->addColumn($this->getTable('sales_flat_shipment'), 'udropship_batch_status', 'varchar(20)');
    $this->_conn->addKey($this->getTable('sales_flat_shipment'), 'IDX_udropship_batch_status', array('udropship_batch_status'));
}

$this->endSetup();