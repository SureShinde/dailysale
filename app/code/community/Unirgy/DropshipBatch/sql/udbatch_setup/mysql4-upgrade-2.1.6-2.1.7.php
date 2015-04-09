<?php

$this->startSetup();

$this->_conn->modifyColumn($this->getTable('udropship_batch_invrow'), 'product_id', 'text');

$this->endSetup();
