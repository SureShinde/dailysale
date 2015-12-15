<?php

$this->startSetup();

$this->_conn->addColumn($this->getTable('udropship_batch_invrow'), 'stock_qty_add', 'decimal(12,4)');

$this->endSetup();
