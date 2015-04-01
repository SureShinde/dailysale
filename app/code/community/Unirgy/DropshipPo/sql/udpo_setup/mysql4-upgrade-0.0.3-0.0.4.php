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

$conn->addColumn($this->getTable('sales_flat_invoice_grid'), 'udpo_id', 'int unsigned');
$conn->addKey($this->getTable('sales_flat_invoice_grid'), 'IDX_UDPO_ID', 'udpo_id');
$conn->addColumn($this->getTable('sales_flat_shipment_grid'), 'udpo_id', 'int unsigned');
$conn->addKey($this->getTable('sales_flat_shipment_grid'), 'IDX_UDPO_ID', 'udpo_id');

$conn->addColumn($this->getTable('udropship_po_grid'), 'udropship_method', 'varchar(100)');
try {
$conn->raw_query(sprintf('ALTER TABLE %s ADD INDEX %s (%s)', $this->getTable('udropship_po_grid'), 'IDX_UDROPSHIP_METHOD', 'udropship_method(32)'));
} catch (PDOException $e) {
    if (false === strpos($e->getMessage(), 'SQLSTATE[42000]: Syntax error or access violation: 1061 Duplicate key name')) throw $e;
}
$conn->addColumn($this->getTable('udropship_po_grid'), 'udropship_method_description', 'text');
try {
$conn->raw_query(sprintf('ALTER TABLE %s ADD INDEX %s (%s)', $this->getTable('udropship_po_grid'), 'IDX_UDROPSHIP_METHOD_DESCRIPTION', 'udropship_method_description(64)'));
} catch (PDOException $e) {
    if (false === strpos($e->getMessage(), 'SQLSTATE[42000]: Syntax error or access violation: 1061 Duplicate key name')) throw $e;
}

$installer->endSetup();
