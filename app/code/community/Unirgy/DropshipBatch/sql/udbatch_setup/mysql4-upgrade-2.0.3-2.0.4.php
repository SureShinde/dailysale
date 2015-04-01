<?php

$this->startSetup();

$this->_conn->addColumn($this->getTable('udropship_batch_row'), 'stockpo_id', 'int(10) unsigned');
$this->_conn->addColumn($this->getTable('udropship_batch_row'), 'stockpo_increment_id', 'varchar(50)');

$this->endSetup();
