<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$table = $installer->getConnection()
    ->newTable($installer->getTable('bestsellercron/fiuze_task_logs'))
    ->addColumn('fiuze_task_logs_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable' => false,
        'primary' => true,
        'identity' => true,
    ), 'entity_id')
    ->addColumn('date',Varien_Db_Ddl_Table::TYPE_VARCHAR)
    ->addColumn('time',Varien_Db_Ddl_Table::TYPE_VARCHAR)
    ->addColumn('type',Varien_Db_Ddl_Table::TYPE_VARCHAR)
    ->addColumn('internal_id',Varien_Db_Ddl_Table::TYPE_VARCHAR)
    ->setComment('Fiuze tasks logs');

$installer->getConnection()->createTable($table);

$installer->endSetup();