<?php

$this->startSetup();

$this->_conn->addColumn($this->getTable('udropship_batch'), 'use_custom_template', 'varchar(255)');

$this->endSetup();
