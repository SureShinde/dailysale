<?php

$this->startSetup();

$this->_conn->addColumn($this->getTable('udropship_batch'), 'adapter_type', 'varchar(100)');

$this->endSetup();
