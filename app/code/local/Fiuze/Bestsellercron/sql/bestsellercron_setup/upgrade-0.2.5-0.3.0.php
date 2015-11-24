<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$table = $installer->getConnection()
    ->newTable($installer->getTable('bestsellercron/fiuze_tasks'))
    ->addColumn('fiuze_tasks_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable' => false,
        'primary' => true,
        'identity' => true,
    ), 'entity_id')
    ->addColumn('task_id',Varien_Db_Ddl_Table::TYPE_VARCHAR)
    ->addColumn('current_timestamp',Varien_Db_Ddl_Table::TYPE_INTEGER)
    ->addColumn('step_timestamp',Varien_Db_Ddl_Table::TYPE_INTEGER)
    ->setComment('Fiuze cron tasks');

$installer->getConnection()->createTable($table);

$installer->endSetup();