<?php

$this->startSetup();
$conn = $this->_conn;

$table = $this->getTable('udropship_batch_dist');
$conn->dropForeignKey($table, 'FK_udropship_batch_dist');
$conn->addConstraint('FK_udropship_batch_dist', $table, 'batch_id', $this->getTable('udropship_batch'), 'batch_id', 'CASCADE', 'CASCADE');

$table = $this->getTable('udropship_batch_row');
$conn->dropForeignKey($table, 'FK_udropship_batch_row');
$conn->addConstraint('FK_udropship_batch_row', $table, 'batch_id', $this->getTable('udropship_batch'), 'batch_id', 'CASCADE', 'CASCADE');

$table = $this->getTable('udropship_batch_invrow');
$conn->dropForeignKey($table, 'FK_udropship_batch_invrow');
$conn->addConstraint('FK_udropship_batch_invrow', $table, 'batch_id', $this->getTable('udropship_batch'), 'batch_id', 'CASCADE', 'CASCADE');

$this->endSetup();