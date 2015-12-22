<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$installer->getConnection()
    ->addColumn($installer->getTable('bestsellercron/fiuze_task_logs'),'cronname', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'comment'   => 'Cron Name'
    ));
$installer->getConnection()
    ->addColumn($installer->getTable('bestsellercron/fiuze_tasks'),'cronname', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'comment'   => 'Cron Name'
    ));
$installer->endSetup();