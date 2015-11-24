<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$connection = $installer->getConnection();
$installer->startSetup();


$installer->getConnection()
    ->addColumn($installer->getTable('fiuze_addressvalidation/fiuze_addresses'),
        'desccode',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
            'comment' => 'Description Code'
        )
    );

$installer->getConnection()->dropColumn($installer->getTable('fiuze_addressvalidation/fiuze_addresses'), 'postalcode');

$installer->getConnection()
    ->addColumn($installer->getTable('fiuze_addressvalidation/fiuze_addresses'),
        'postalcode',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
            'comment' => 'Postal Code'
        )
    );

$installer->endSetup();