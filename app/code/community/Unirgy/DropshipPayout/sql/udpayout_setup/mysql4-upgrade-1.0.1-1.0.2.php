<?php

$this->startSetup();

$conn = $this->_conn;

$table = $this->getTable('udropship_payout_row');
$conn->dropForeignKey($table, 'FK_udropship_payout_row');
$conn->addConstraint('FK_udropship_payout_row', $table, 'payout_id', $this->getTable('udropship_payout'), 'payout_id', 'CASCADE', 'CASCADE');

$this->endSetup();