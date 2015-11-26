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
 * @package    Unirgy_DropshipMicrosite
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

$this->startSetup();

$conn = $this->_conn;
$conn->addColumn($this->getTable('udropship_vendor'), 'update_store_base_url', 'tinyint(1) not null default -1');
$conn->addColumn($this->getTable('udropship_vendor_registration'), 'update_store_base_url', 'tinyint(1) not null default -1');

$this->endSetup();