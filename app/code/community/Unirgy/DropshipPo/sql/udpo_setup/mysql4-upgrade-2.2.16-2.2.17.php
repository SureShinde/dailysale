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

/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer = $this;
$conn = $this->_conn;
$installer->startSetup();

$conn->addColumn($installer->getTable('sales_flat_order'), 'udpo_amount_fields', 'tinyint(1)');
$conn->addColumn($installer->getTable('udpo/po'), 'base_hidden_tax_amount', 'decimal(12,4)');

$conn->addColumn($installer->getTable('udpo/po_item'), 'base_tax_amount', 'decimal(12,4)');
$conn->addColumn($installer->getTable('udpo/po_item'), 'base_hidden_tax_amount', 'decimal(12,4)');
$conn->addColumn($installer->getTable('udpo/po_item'), 'base_discount_amount', 'decimal(12,4)');
$conn->addColumn($installer->getTable('udpo/po_item'), 'base_row_total', 'decimal(12,4)');
$conn->addColumn($installer->getTable('udpo/po_item'), 'row_total', 'decimal(12,4)');

$conn->addColumn($installer->getTable('sales_flat_order_item'), 'udpo_base_tax_amount', 'decimal(12,4)');
$conn->addColumn($installer->getTable('sales_flat_order_item'), 'udpo_base_hidden_tax_amount', 'decimal(12,4)');
$conn->addColumn($installer->getTable('sales_flat_order_item'), 'udpo_base_discount_amount', 'decimal(12,4)');
$conn->addColumn($installer->getTable('sales_flat_order_item'), 'udpo_base_row_total', 'decimal(12,4)');
$conn->addColumn($installer->getTable('sales_flat_order_item'), 'udpo_row_total', 'decimal(12,4)');


$installer->endSetup();
