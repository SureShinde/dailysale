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

$this->_conn->addColumn($this->getTable('udropship_payout'), 'adjustment', 'decimal(12,4)');
$this->_conn->addColumn($this->getTable('udropship_payout'), 'date_from', 'datetime');
$this->_conn->addColumn($this->getTable('udropship_payout'), 'date_to', 'datetime');

$this->endSetup();