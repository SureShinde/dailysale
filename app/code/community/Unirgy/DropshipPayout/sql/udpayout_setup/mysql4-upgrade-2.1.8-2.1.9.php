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
 * @package    Unirgy_DropshipPayout
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

$this->startSetup();

$this->_conn->addColumn($this->getTable('udropship_payout'), 'is_online', 'tinyint(1)');
$this->_conn->addColumn($this->getTable('udropship_payout'), 'sender_transaction_id', 'varchar(255)');
$this->_conn->addColumn($this->getTable('udropship_payout'), 'transaction_status', 'varchar(255)');
$this->_conn->addColumn($this->getTable('udropship_payout'), 'sender_transaction_status', 'varchar(255)');

$this->endSetup();