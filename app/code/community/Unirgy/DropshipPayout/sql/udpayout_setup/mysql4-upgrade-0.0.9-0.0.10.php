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

$this->_conn->addColumn($this->getTable('udropship_payout'), 'paypal_unique_id', 'varchar(30)');
$this->_conn->addColumn($this->getTable('udropship_payout'), 'transaction_fee', 'decimal(12,4)');

if ($this->_conn->tableColumnExists($this->getTable('udropship_payout'), 'correlation_id')
    && !$this->_conn->tableColumnExists($this->getTable('udropship_payout'), 'paypal_correlation_id')
) {
$this->_conn->changeColumn($this->getTable('udropship_payout'), 'correlation_id', 'paypal_correlation_id', 'varchar(64)');
}

$this->endSetup();
