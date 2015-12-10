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
 * @package    Unirgy_Dropship
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

$this->startSetup();

$this->_conn->addColumn($this->getTable('udropship_payout_row'), 'subtotal', 'decimal(12,4)');
$this->_conn->addColumn($this->getTable('udropship_payout_row'), 'shipping', 'decimal(12,4)');
$this->_conn->addColumn($this->getTable('udropship_payout_row'), 'tax', 'decimal(12,4)');
$this->_conn->addColumn($this->getTable('udropship_payout_row'), 'handling', 'decimal(12,4)');
$this->_conn->addColumn($this->getTable('udropship_payout_row'), 'trans_fee', 'decimal(12,4)');
$this->_conn->addColumn($this->getTable('udropship_payout_row'), 'adj_amount', 'decimal(12,4)');
$this->_conn->addColumn($this->getTable('udropship_payout_row'), 'com_amount', 'decimal(12,4)');

if ($this->_conn->tableColumnExists($this->getTable('udropship_payout_row'), 'shipment_id')
    && !$this->_conn->tableColumnExists($this->getTable('udropship_payout_row'), 'po_id')
) {
$this->_conn->changeColumn($this->getTable('udropship_payout_row'), 'shipment_id', 'po_id', 'int(10) unsigned DEFAULT NULL');
}
$this->_conn->addColumn($this->getTable('udropship_payout_row'), 'po_type', 'varchar(32)');
$this->_conn->dropKey($this->getTable('udropship_payout_row'), 'UNQ_SHIPMENT_PAYOUT');
$this->_conn->addKey($this->getTable('udropship_payout_row'), 'UNQ_PO_PAYOUT', array('po_id', 'po_type', 'payout_id'), 'unique');

if ($this->_conn->tableColumnExists($this->getTable('udropship_payout_adjustment'), 'shipment_id')
    && !$this->_conn->tableColumnExists($this->getTable('udropship_payout_adjustment'), 'po_id')
) {
$this->_conn->changeColumn($this->getTable('udropship_payout_adjustment'), 'shipment_id', 'po_id', "varchar(50) NOT NULL DEFAULT ''");
}
$this->_conn->addColumn($this->getTable('udropship_payout_adjustment'), 'po_type', "varchar(32) DEFAULT 'shipment'");
$this->_conn->dropKey($this->getTable('udropship_payout_adjustment'), 'IDX_SHIPMENT_ID');
$this->_conn->addKey($this->getTable('udropship_payout_adjustment'), 'IDX_PO_ID', array('po_id', 'po_type'));

$this->_conn->addColumn($this->getTable('udropship_payout'), 'subtotal', 'decimal(12,4)');
$this->_conn->addColumn($this->getTable('udropship_payout'), 'shipping', 'decimal(12,4)');
$this->_conn->addColumn($this->getTable('udropship_payout'), 'tax', 'decimal(12,4)');
$this->_conn->addColumn($this->getTable('udropship_payout'), 'handling', 'decimal(12,4)');
$this->_conn->addColumn($this->getTable('udropship_payout'), 'trans_fee', 'decimal(12,4)');
$this->_conn->addColumn($this->getTable('udropship_payout'), 'com_amount', 'decimal(12,4)');

$this->_conn->addColumn($this->getTable('udropship_payout'), 'before_hold_status', 'varchar(20)');

$this->_conn->dropColumn($this->getTable('udropship_payout'), 'my_adjustment');
$this->_conn->dropColumn($this->getTable('udropship_payout'), 'adjustment');

$this->_conn->addColumn($this->getTable('udropship_payout'), 'po_type', 'varchar(32)');

if (Mage::helper('udropship')->isSalesFlat()) {
    $this->_conn->addColumn($this->getTable('sales_flat_shipment'), 'payout_id', 'int(10) unsigned DEFAULT NULL');
    $this->_conn->addColumn($this->getTable('sales_flat_shipment_grid'), 'payout_id', 'int(10) unsigned DEFAULT NULL');
    $this->_conn->addKey($this->getTable('sales_flat_shipment_grid'), 'IDX_UDROPSHIP_PAYOUT_ID', 'payout_id');
} else {
    $sEav = new Mage_Sales_Model_Mysql4_Setup('sales_setup');
    $sEav->addAttribute('shipment', 'payout_id', array('type' => 'int'));
}

$this->endSetup();
