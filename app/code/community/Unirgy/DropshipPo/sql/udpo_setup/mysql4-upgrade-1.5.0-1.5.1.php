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

$this->_conn->addColumn($this->getTable('udpo/po'), 'statement_date', 'datetime');
$this->_conn->addColumn($this->getTable('udpo/po_grid'), 'statement_date', 'datetime');
$this->_conn->addKey($this->getTable('udpo/po_grid'), 'IDX_UDROPSHIP_STATEMENT_DATE', 'statement_date');

$vendors = Mage::getResourceModel('udropship/vendor_collection');
foreach ($vendors as $vendor) {
    $vendor->afterLoad();
    if ('po' == $vendor->getStatementPoType()) {
        $stPoStatuses = $vendor->getStatementPoStatus();
        if (!is_array($stPoStatuses)) {
            $stPoStatuses = explode(',', $stPoStatuses);
        }
        $sdInsSelect = sprintf("INSERT INTO %s (entity_id,statement_date) %s ON DUPLICATE KEY UPDATE statement_date=values(statement_date)",
            $this->getTable('udpo/po'),
            $this->_conn->select()
                ->from(array('st' => $this->getTable('udpo/po')), array())
                ->where('st.udropship_vendor=?', $vendor->getId())
                ->where('st.udropship_status in (?)', $stPoStatuses)
                ->columns(array('entity_id', 'statement_date' => 'st.created_at'))
        );
        $this->_conn->query($sdInsSelect);
        $sdInsSelect = sprintf("INSERT INTO %s (entity_id,statement_date) %s ON DUPLICATE KEY UPDATE statement_date=values(statement_date)",
            $this->getTable('udpo/po_grid'),
            $this->_conn->select()
                ->from(array('st' => $this->getTable('udpo/po_grid')), array())
                ->where('st.udropship_vendor=?', $vendor->getId())
                ->where('st.udropship_status in (?)', $stPoStatuses)
                ->columns(array('entity_id', 'statement_date' => 'st.created_at'))
        );
        $this->_conn->query($sdInsSelect);
    }
}

$this->endSetup();
