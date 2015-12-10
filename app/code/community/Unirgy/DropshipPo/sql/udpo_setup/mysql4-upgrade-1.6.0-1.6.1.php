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

$this->startSetup();

$conn = $this->_conn;

if ($hlp->isUdpayoutActive()) {
    $searchSelect = $conn->select()
        ->from(array('po' => $this->getTable('udpo/po')), array('entity_id'))
        ->joinLeft(array('vs' => $this->getTable('udpayout/payout')), 'vs.payout_id=po.payout_id', array())
        ->where("po.payout_id is not null and po.payout_id!='' and vs.payout_id is null");

    $updateSelect = $conn->select()
        ->join(array('_orp' => $searchSelect), '_orp.entity_id=_po.entity_id', array())
        ->columns(array('payout_id' => new Zend_Db_Expr('NULL')));

    $updateSql = $updateSelect->crossUpdateFromSelect(array('_po' => $this->getTable('udpo/po')));
    //print $updateSql."\n\n\n";
    $conn->raw_query($updateSql);

    $updateSql = $updateSelect->crossUpdateFromSelect(array('_po' => $this->getTable('udpo/po_grid')));
    //print $updateSql."\n\n\n";
    $conn->raw_query($updateSql);
}

$this->endSetup();