<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$table = $installer->getConnection()
    ->newTable($installer->getTable('fiuze_addressvalidation/fiuze_addresses'))
    ->addColumn('fiuze_addresses_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable' => false,
        'primary' => true,
        'identity' => true,
    ), 'entity_id')
    ->addColumn('address', Varien_Db_Ddl_Table::TYPE_VARCHAR)
    ->addColumn('city', Varien_Db_Ddl_Table::TYPE_VARCHAR)
    ->addColumn('state', Varien_Db_Ddl_Table::TYPE_VARCHAR)
    ->addColumn('postalcode', Varien_Db_Ddl_Table::TYPE_INTEGER)
    ->addColumn('status', Varien_Db_Ddl_Table::TYPE_BOOLEAN)


    ->setComment('Fiuze_addresses_for_validation');

$installer->getConnection()->createTable($table);

$installer->endSetup();