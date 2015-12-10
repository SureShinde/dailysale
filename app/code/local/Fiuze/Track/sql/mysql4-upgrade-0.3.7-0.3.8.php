<?php

$installer = $this;
$installer->startSetup();
$installer->getConnection()->addColumn($installer->getTable('track/track'), 'status', array(
    'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
    'nullable' => false,
    'comment' => 'Status'
));

$installer->endSetup();
?>