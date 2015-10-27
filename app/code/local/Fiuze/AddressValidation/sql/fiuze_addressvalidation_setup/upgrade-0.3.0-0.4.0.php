<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$connection = $installer->getConnection();
$installer->startSetup();

$installer->getConnection()->dropColumn($installer->getTable('fiuze_addressvalidation/fiuze_addresses'), 'desccode');

$installer->getConnection()
    ->addColumn($installer->getTable('fiuze_addressvalidation/fiuze_addresses'),
        'dvpcode',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
            'comment' => 'DVP Note'
        )
    );

$installer->getConnection()
    ->addColumn($installer->getTable('fiuze_addressvalidation/fiuze_addresses'),
        'dvpnote',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
            'comment' => 'DVP Code'
        )
    );

$installer->getConnection()
    ->addColumn($installer->getTable('sales/order'),
        'dvpid',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
            'comment' => 'DVP address ID'
        )
    );
$installer->getConnection()
    ->addColumn($installer->getTable('sales/quote'),
        'dvpid',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
            'comment' => 'DVP address ID'
        )
    );


$installer->endSetup();