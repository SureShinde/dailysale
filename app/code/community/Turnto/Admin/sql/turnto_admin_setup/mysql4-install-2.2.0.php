<?php
 
$installer = $this;
 
$installer->startSetup();
 
$table = $installer->getConnection()
    ->newTable($installer->getTable('turnto_products_ratings'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Id')
    ->addColumn('product_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable'  => true,
        ), 'Product Id')
    ->addColumn('sku', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
        'nullable'  => false,
        ), 'Sku')
    ->addColumn('review_count', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable'  => false,
        'default'   => 0, 
        ), 'Review Count')
    ->addColumn('rating', Varien_Db_Ddl_Table::TYPE_DECIMAL, null, array(
        'nullable'  => false,
        'precision' => 2,
        'scale' => 1,
        'default'   => 0, 
        ), 'Rating')
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'nullable'  => true,
        'default'   => null, 
        ), 'Created At')
    ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'nullable'  => true,
        'default'   => null, 
        ), 'Updated At');


$installer->getConnection()->createTable($table);


$installer->endSetup();