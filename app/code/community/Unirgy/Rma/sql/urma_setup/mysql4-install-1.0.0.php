<?php

/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer = $this;
$conn = $this->_conn;
$installer->startSetup();

$installer->run("

/* RMA */

-- DROP TABLE IF EXISTS `{$installer->getTable('urma/rma')}`;
CREATE TABLE IF NOT EXISTS `{$installer->getTable('urma/rma')}` LIKE `{$installer->getTable('sales/shipment')}`;

/* RMA Grid */

-- DROP TABLE IF EXISTS `{$installer->getTable('urma/rma_grid')}`;
CREATE TABLE IF NOT EXISTS `{$installer->getTable('urma/rma_grid')}` LIKE `{$installer->getTable('sales/shipment_grid')}`;

/* RMA Items */

-- DROP TABLE IF EXISTS `{$installer->getTable('urma/rma_item')}`;
CREATE TABLE IF NOT EXISTS `{$installer->getTable('urma/rma_item')}` LIKE `{$installer->getTable('sales/shipment_item')}`;

/* RMA Comment */

-- DROP TABLE IF EXISTS `{$installer->getTable('urma/rma_comment')}`;
CREATE TABLE IF NOT EXISTS `{$installer->getTable('urma/rma_comment')}` LIKE `{$installer->getTable('sales/shipment_comment')}`;

/* RMA Track */

-- DROP TABLE IF EXISTS `{$installer->getTable('urma/rma_track')}`;
CREATE TABLE IF NOT EXISTS `{$installer->getTable('urma/rma_track')}` LIKE `{$installer->getTable('sales/shipment_track')}`;


");

$conn->addColumn($installer->getTable('urma/rma'), 'rma_status', "varchar(128) DEFAULT 'pending'");
$conn->addColumn($installer->getTable('urma/rma_comment'), 'rma_status', "varchar(128) DEFAULT 'pending'");

$constraints = array(
    'urma_rma' => array(
        'parent' => array('order_id', 'sales_flat_order', 'entity_id'),
        'store' => array('store_id', 'core_store', 'store_id', 'set null')
    ),
    'urma_rma_grid' => array(
        'parent' => array('entity_id', 'urma_rma', 'entity_id'),
        'store' => array('store_id', 'core_store', 'store_id', 'set null')
    ),
    'urma_rma_item' => array(
        'parent' => array('parent_id', 'urma_rma', 'entity_id'),
    ),
    'urma_rma_comment' => array(
        'parent' => array('parent_id', 'urma_rma', 'entity_id'),
    ),
    'urma_rma_track' => array(
        'parent' => array('parent_id', 'urma_rma', 'entity_id'),
    ),
);

foreach ($constraints as $table => $list) {
    foreach ($list as $code => $constraint) {
        $constraint[1] = $installer->getTable($constraint[1]);
        array_unshift($constraint, $installer->getTable($table));
        array_unshift($constraint, strtoupper($table . '_' . $code));

        call_user_func_array(array($installer->getConnection(), 'addConstraint'), $constraint);
    }
}

$installer->addEntityType('urma_rma', array(
    'entity_model'          =>'urma/rma',
    'table'                 =>'urma/rma',
    'increment_model'       =>'eav/entity_increment_numeric',
    'increment_per_store'   =>true
));

$installer->endSetup();
