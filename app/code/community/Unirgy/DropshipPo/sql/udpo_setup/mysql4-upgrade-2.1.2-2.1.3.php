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

$this->_conn->addColumn($this->getTable('udpo/po_grid'), 'base_shipping_amount', 'decimal(12,4)');
$this->_conn->addKey($this->getTable('udpo/po_grid'), 'IDX_BASE_SHIPPING_AMOUNT', 'base_shipping_amount');

$select = $this->getConnection()->select();
$select->join(
    array('po'=>$this->getTable('udpo/po')),
    'po_grid.entity_id = po.entity_id',
    array('base_shipping_amount' => 'base_shipping_amount')
);
$this->getConnection()->query(
    $select->crossUpdateFromSelect(
        array('po_grid' => $this->getTable('udpo/po_grid'))
    )
);

$this->endSetup();