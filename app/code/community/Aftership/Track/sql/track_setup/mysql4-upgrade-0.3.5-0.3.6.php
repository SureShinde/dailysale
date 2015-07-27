<?php

$installer = $this;
$installer->startSetup();
$installer->getConnection()->addColumn($installer->getTable('track/track'), 'pack_tracking', array(
    'type' => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
    'nullable' => false,
    'comment' => 'Pack tracking'
));
$installer->getConnection()->addColumn($installer->getTable('track/track'), 'error_tracking', array(
    'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
    'nullable' => false,
    'comment' => 'Error tracking'
));
$installer->endSetup();
?>