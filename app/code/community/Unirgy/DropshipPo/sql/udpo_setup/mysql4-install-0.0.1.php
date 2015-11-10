<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    Unirgy_DropshipPo
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

$hlp = Mage::helper('udropship');
if (!$hlp->hasMageFeature('sales_flat')) Mage::throwException(Mage::helper('udropship')->__('Unirgy_DropshipPo module does not support this version of magento'));
if (!$hlp->isUdpoActive()) return false;

/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer = $this;
$conn = $this->_conn;
$installer->startSetup();
$installer->run("

/* Purchase Order */

-- DROP TABLE IF EXISTS `{$installer->getTable('udpo/po')}`;

CREATE TABLE IF NOT EXISTS `{$installer->getTable('udpo/po')}` (
    `entity_id` int(10) unsigned NOT NULL auto_increment,
    `store_id` smallint(5) unsigned default NULL,
    `total_weight` decimal(12,4) default NULL,
    `total_qty` decimal(12,4) default NULL,
    `email_sent` tinyint(1) unsigned default NULL,
    `order_id` int(10) unsigned NOT NULL,
    `customer_id` int(10) default NULL,
    `shipping_address_id` int(10) default NULL,
    `billing_address_id` int(10) default NULL,
    `increment_id` varchar(50) default NULL,
    `created_at` datetime default NULL,
    `updated_at` datetime default NULL,
    `udropship_vendor` int(11) DEFAULT NULL,
    `udropship_status` int(11) DEFAULT NULL,
    `base_total_value` decimal(12,4) DEFAULT NULL,
    `total_value` decimal(12,4) DEFAULT NULL,
    `base_shipping_amount` decimal(12,4) DEFAULT NULL,
    `shipping_amount` decimal(12,4) DEFAULT NULL,
    `udropship_available_at` datetime DEFAULT NULL,
    `udropship_method` varchar(100) DEFAULT NULL,
    `udropship_method_description` text,
    `base_tax_amount` decimal(12,4) DEFAULT NULL,
    `total_cost` decimal(12,4) DEFAULT NULL,
    `transaction_fee` decimal(12,4) DEFAULT NULL,
    `commission_percent` decimal(12,4) DEFAULT NULL,
    `handling_fee` decimal(12,4) DEFAULT NULL,
    `udropship_shipcheck` varchar(5) DEFAULT NULL,
    `udropship_vendor_order_id` varchar(30) DEFAULT NULL,
    `udropship_batch_status` varchar(20) DEFAULT NULL,
    PRIMARY KEY (`entity_id`),
    KEY `IDX_STORE_ID` (`store_id`),
    KEY `IDX_TOTAL_QTY` (`total_qty`),
    KEY `IDX_INCREMENT_ID` (`increment_id`),
    KEY `IDX_ORDER_ID` (`order_id`),
    KEY `IDX_UDROPSHIP_VENDOR` (`udropship_vendor`),
    KEY `IDX_UDROPSHIP_STATUS` (`udropship_status`),
    KEY `IDX_UDROPSHIP_SHIPCHECK` (`udropship_shipcheck`),
    KEY `IDX_UDROPSHIP_BATCH_STATUS` (`udropship_batch_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* Purchase Order Grid */

-- DROP TABLE IF EXISTS `{$installer->getTable('udpo/po_grid')}`;
CREATE TABLE IF NOT EXISTS `{$installer->getTable('udpo/po_grid')}` (
    `entity_id` int(10) unsigned NOT NULL auto_increment,
    `store_id` smallint(5) unsigned default NULL,
    `total_qty` decimal(12,4) default NULL,
    `order_id` int(10) unsigned NOT NULL,
    `increment_id` varchar(50) default NULL,
    `order_increment_id` varchar(50) default NULL,
    `created_at` datetime default NULL,
    `order_created_at` datetime default NULL,
    `shipping_name` varchar(255) default NULL,
    `udropship_vendor` int(11) DEFAULT NULL,
    `udropship_status` int(11) DEFAULT NULL,
    PRIMARY KEY (`entity_id`),
    KEY `IDX_STORE_ID` (`store_id`),
    KEY `IDX_TOTAL_QTY` (`total_qty`),
    KEY `IDX_ORDER_ID` (`order_id`),
    KEY `IDX_INCREMENT_ID` (`increment_id`),
    KEY `IDX_ORDER_INCREMENT_ID` (`order_increment_id`),
    KEY `IDX_CREATED_AT` (`created_at`),
    KEY `IDX_ORDER_CREATED_AT` (`order_created_at`),
    KEY `IDX_SHIPPING_NAME` (`shipping_name`),
    KEY `IDX_UDROPSHIP_VENDOR` (`udropship_vendor`),
    KEY `IDX_UDROPSHIP_STATUS` (`udropship_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* Purchase Order Items */

-- DROP TABLE IF EXISTS `{$installer->getTable('udpo/po_item')}`;
CREATE TABLE IF NOT EXISTS `{$installer->getTable('udpo/po_item')}` (
    `entity_id` int(10) unsigned NOT NULL auto_increment,
    `parent_id` int(10) unsigned NOT NULL,
    `row_total` decimal(12,4) default NULL,
    `price` decimal(12,4) default NULL,
    `weight` decimal(12,4) default NULL,
    `qty` decimal(12,4) default NULL,
    `qty_shipped` decimal(12,4) default NULL,
    `product_id` int(10) default NULL,
    `order_item_id` int(10) default NULL,
    `additional_data` text,
    `description` text,
    `name` varchar(255) default NULL,
    `sku` varchar(255) default NULL,
    `commission_percent` decimal(12,4),
    `transaction_fee` decimal(12,4),
    PRIMARY KEY (`entity_id`),
    KEY `IDX_PARENT_ID` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* Purchase Order Comment */

-- DROP TABLE IF EXISTS `{$installer->getTable('udpo/po_comment')}`;
CREATE TABLE IF NOT EXISTS `{$installer->getTable('udpo/po_comment')}` (
    `entity_id` int(10) unsigned NOT NULL auto_increment,
    `parent_id` int(10) unsigned NOT NULL,
    `is_customer_notified` int(10) default NULL,
    `comment` text,
    `created_at` datetime default NULL,
    PRIMARY KEY (`entity_id`),
    KEY `IDX_CREATED_AT` (`created_at`),
    KEY `IDX_PARENT_ID` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$conn->addColumn($this->getTable('sales_flat_order_item'), 'locked_do_udpo', "tinyint(1) unsigned default NULL");
$conn->addColumn($this->getTable('sales_flat_order_item'), 'qty_udpo', "decimal(12,4) default '0.0000'");

$conn->addColumn($this->getTable('sales_flat_shipment'), 'udpo_id', 'int unsigned');
$conn->addColumn($this->getTable('sales_flat_shipment'), 'udpo_increment_id', 'varchar(50)');
$conn->addColumn($this->getTable('sales_flat_shipment_item'), 'udpo_item_id', 'int unsigned');

$constraints = array(
    'sales_flat_shipment' => array(
        'parent' => array('udpo_id', 'udropship_po', 'entity_id'),
    ),
    'sales_flat_shipment_item' => array(
        'parent' => array('udpo_item_id', 'udropship_po_item', 'entity_id'),
    ),
    'udropship_po' => array(
        'parent' => array('order_id', 'sales_flat_order', 'entity_id'),
        'store' => array('store_id', 'core_store', 'store_id', 'set null')
    ),
    'udropship_po_grid' => array(
        'parent' => array('entity_id', 'udropship_po', 'entity_id'),
        'store' => array('store_id', 'core_store', 'store_id', 'set null')
    ),
    'udropship_po_item' => array(
        'parent' => array('parent_id', 'udropship_po', 'entity_id'),
    ),
    'udropship_po_comment' => array(
        'parent' => array('parent_id', 'udropship_po', 'entity_id'),
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

$installer->addEntityType('udpo_po', array(
    'entity_model'          =>'udpo/po',
    'table'                 =>'udpo/po',
    'increment_model'       =>'eav/entity_increment_numeric',
    'increment_per_store'   =>true
));

$installer->endSetup();
