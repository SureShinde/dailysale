<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$connection = $installer->getConnection();
$installer->startSetup();

$installer->getConnection()
    ->addColumn($installer->getTable('fiuze_addressvalidation/fiuze_addresses'),
        'real_address',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
            'comment' => 'Real address'
        )
    );
$installer->getConnection()
    ->addColumn($installer->getTable('fiuze_addressvalidation/fiuze_addresses'),
        'real_city',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
            'comment' => 'Real City'
        )
    );
$installer->getConnection()
    ->addColumn($installer->getTable('fiuze_addressvalidation/fiuze_addresses'),
        'real_state',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
            'comment' => 'Real State'
        )
    );
$installer->getConnection()
    ->addColumn($installer->getTable('fiuze_addressvalidation/fiuze_addresses'),
        'real_postalcode',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
            'comment' => 'Real Postal code'
        )
    );

$installer->endSetup();