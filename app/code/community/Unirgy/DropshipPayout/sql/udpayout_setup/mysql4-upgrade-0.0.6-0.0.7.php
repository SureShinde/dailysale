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

if ($this->_conn->tableColumnExists($this->getTable('udropship_payout_row'), 'shipment_increment_id')
    && !$this->_conn->tableColumnExists($this->getTable('udropship_payout_row'), 'po_increment_id')
) {
$this->_conn->changeColumn($this->getTable('udropship_payout_row'), 'shipment_increment_id', 'po_increment_id', 'varchar(50) DEFAULT NULL');
}
if ($this->_conn->tableColumnExists($this->getTable('udropship_payout_row'), 'shipment_created_at')
    && !$this->_conn->tableColumnExists($this->getTable('udropship_payout_row'), 'po_created_at')
) {
$this->_conn->changeColumn($this->getTable('udropship_payout_row'), 'shipment_created_at', 'po_created_at', 'datetime DEFAULT NULL');
}

$this->endSetup();
