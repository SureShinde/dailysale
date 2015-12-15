<?php

$installer = $this;
$installer->startSetup();

/**
 * Create table 'track/schedule'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('track/schedule'))
    ->addColumn('schedule_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ), 'Schedule Id')
    ->addColumn('order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable'  => false,
        'default'   => '0',
    ), 'Order Id')
    ->addColumn('job_code', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable'  => false,
        'default'   => '0',
    ), 'Job Code')
    ->addColumn('cron_expr', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable'  => false,
        'default'   => '0',
    ), 'Cron Expr')
    ->addColumn('status', Varien_Db_Ddl_Table::TYPE_TEXT, 7, array(
        'nullable'  => false,
        'default'   => 'pending',
    ), 'Status')
    ->addColumn('messages', Varien_Db_Ddl_Table::TYPE_TEXT, '64k', array(
    ), 'Messages')
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'nullable'  => false,
    ), 'Created At')
    ->addColumn('scheduled_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'nullable'  => true,
    ), 'Scheduled At')
    ->addColumn('executed_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'nullable'  => true,
    ), 'Executed At')
    ->addColumn('finished_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'nullable'  => true,
    ), 'Finished At')
    ->addIndex($installer->getIdxName('track/schedule', array('job_code')),
        array('job_code'))
    ->addIndex($installer->getIdxName('track/schedule', array('scheduled_at', 'status')),
        array('scheduled_at', 'status'))
    ->setComment('Cron Schedule');
$installer->getConnection()->createTable($table);

$installer->endSetup();