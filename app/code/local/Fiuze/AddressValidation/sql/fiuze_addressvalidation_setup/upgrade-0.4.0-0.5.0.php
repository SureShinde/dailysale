<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$installer->getConnection()->dropColumn($installer->getTable('sales/order'), 'dvpid');
$installer->getConnection()->dropColumn($installer->getTable('sales/quote'), 'dvpid');
$installer->run("TRUNCATE TABLE {$this->getTable('fiuze_addressvalidation/fiuze_addresses')}");
$installer->getConnection()
    ->addColumn($installer->getTable('sales/quote'),
        'dvpshippingid',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
            'comment' => 'DVP shipping ID'
        )
    );


$installer->getConnection()
    ->addColumn($installer->getTable('sales/quote'),
        'dvpbillingid',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
            'comment' => 'DVP billing ID'
        )
    );


$installer->getConnection()
    ->addColumn($installer->getTable('sales/order'),
        'dvpshippingid',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
            'comment' => 'DVP shipping ID'
        )
    );


$installer->getConnection()
    ->addColumn($installer->getTable('sales/order'),
        'dvpbillingid',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
            'comment' => 'DVP billing ID'
        )
    );


$installer->endSetup();