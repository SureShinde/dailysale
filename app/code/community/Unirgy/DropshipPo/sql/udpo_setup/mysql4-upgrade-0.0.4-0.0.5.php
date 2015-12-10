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

$hlp = Mage::helper('udropship');
if (!$hlp->hasMageFeature('sales_flat')) Mage::throwException($hlp->__('Unirgy_DropshipPo module does not support this version of magento'));
if (!$hlp->isUdpoActive()) return false;

$this->startSetup();

$this->_conn->addColumn($this->getTable('udpo/po'), 'statement_id', 'varchar(30) DEFAULT NULL');
$this->_conn->addColumn($this->getTable('udpo/po_grid'), 'statement_id', 'varchar(30) DEFAULT NULL');
$this->_conn->addKey($this->getTable('udpo/po_grid'), 'IDX_UDROPSHIP_STATEMENT_ID', 'statement_id');

$this->_conn->addColumn($this->getTable('udpo/po'), 'udropship_payout_status', 'varchar(50) DEFAULT NULL');
$this->_conn->addColumn($this->getTable('udpo/po_grid'), 'udropship_payout_status', 'varchar(50) DEFAULT NULL');
$this->_conn->addKey($this->getTable('udpo/po_grid'), 'IDX_UDROPSHIP_PAYOUT_STATUS', 'udropship_payout_status');

$this->_conn->addColumn($this->getTable('udpo/po'), 'payout_id', 'int(10) unsigned DEFAULT NULL');
$this->_conn->addColumn($this->getTable('udpo/po_grid'), 'payout_id', 'int(10) unsigned DEFAULT NULL');
$this->_conn->addKey($this->getTable('udpo/po_grid'), 'IDX_UDROPSHIP_PAYOUT_ID', 'payout_id');

$this->endSetup();
