<?php

$this->startSetup();

$this->_conn->addColumn($this->getTable('udropship_batch_invrow'), 'stock_status', 'tinyint(1)');

$this->endSetup();
