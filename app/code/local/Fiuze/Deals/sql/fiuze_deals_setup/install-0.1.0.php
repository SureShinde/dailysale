<?php
/**
 * @category   Fiuze
 * @package    Fiuze_Deals
 * @author     Alena Tsareva <alena.tsareva@webinse.com>
 */
$installer = $this;
$installer->startSetup();

$table = $installer->getConnection()
    ->newTable($installer->getTable('fiuze_deals/deals'))
    ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable' => false,
        'primary' => true,
        'identity' => true,
    ), 'entity_id')
    ->addColumn('product_id', Varien_Db_Ddl_Table::TYPE_INTEGER)
    ->addColumn('product_name', Varien_Db_Ddl_Table::TYPE_VARCHAR)
    ->addColumn('price', Varien_Db_Ddl_Table::TYPE_FLOAT)
    ->addColumn('qty', Varien_Db_Ddl_Table::TYPE_INTEGER)
    ->addColumn('category_id', Varien_Db_Ddl_Table::TYPE_INTEGER)
    ->addColumn('start_time', Varien_Db_Ddl_Table::TYPE_DATETIME)
    ->addColumn('active', Varien_Db_Ddl_Table::TYPE_BOOLEAN)

    ->addIndex($installer->getIdxName('fiuze_deals/deals', array('product_id')), array('product_id'))
    ->addForeignKey($installer->getFkName('fiuze_deals/deals', 'product_id', 'catalog/product', 'entity_id'),
        'product_id',
        $installer->getTable('catalog/product'), 'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)

    ->addIndex($installer->getIdxName('fiuze_deals/deals', array('category_id')), array('category_id'))
    ->addForeignKey($installer->getFkName('fiuze_deals/deals', 'category_id', 'catalog/category', 'entity_id'),
        'category_id',
        $installer->getTable('catalog/category'), 'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)

    ->setComment('Deals Rotation Table');

$installer->getConnection()->createTable($table);

$installer->endSetup();