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
if (!$hlp->hasMageFeature('sales_flat')) Mage::throwException($hlp->__('Unirgy_DropshipPo module does not support this version of magento'));
if (!$hlp->isUdpoActive()) return false;

/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer = $this;
$conn = $this->_conn;
$installer->startSetup();

$conn->addColumn($this->getTable('udropship_po_item'), 'base_cost', 'decimal(12,4)');
$conn->addColumn($this->getTable('udropship_po_item'), 'qty_invoiced', 'decimal(12,4)');
$conn->addColumn($this->getTable('udropship_po_item'), 'qty_canceled', 'decimal(12,4)');
$conn->addColumn($this->getTable('sales_flat_shipment_item'), 'qty_canceled', 'decimal(12,4)');

$conn->addColumn($this->getTable('udropship_po_grid'), 'total_cost', 'decimal(12,4)');
$conn->addKey($this->getTable('udropship_po_grid'), 'IDX_TOTAL_COST', 'total_cost');
$conn->addColumn($this->getTable('udropship_po_grid'), 'shipping_amount', 'decimal(12,4)');
$conn->addKey($this->getTable('udropship_po_grid'), 'IDX_SHIPPING_AMOUNT', 'shipping_amount');

$conn->addColumn($this->getTable('udropship_po_comment'), 'is_visible_to_vendor', 'tinyint');
$conn->addColumn($this->getTable('udropship_po_comment'), 'udropship_status', 'varchar(64)');

if ($conn->tableColumnExists($this->getTable('udropship_po_comment'), 'is_customer_notified')
    && !$conn->tableColumnExists($this->getTable('udropship_po_comment'), 'is_vendor_notified')
) {
$conn->changeColumn($this->getTable('udropship_po_comment'), 'is_customer_notified', 'is_vendor_notified', 'tinyint');
}

$conn->addColumn($this->getTable('sales_flat_invoice'), 'udpo_id', 'int unsigned');
$conn->addColumn($this->getTable('sales_flat_invoice_item'), 'udpo_item_id', 'int unsigned');

$installer->endSetup();
