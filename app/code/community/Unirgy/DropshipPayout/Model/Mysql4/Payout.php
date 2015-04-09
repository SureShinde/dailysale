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
 
class Unirgy_DropshipPayout_Model_Mysql4_Payout extends Unirgy_Dropship_Model_Mysql4_Vendor_Statement_Abstract
{
    protected function _construct()
    {
        $this->_init('udpayout/payout', 'payout_id');
    }
    
    protected function _getRowTable()
    {
        return $this->getTable('udpayout/payout_row');
    }
    protected function _getAdjustmentTable()
    {
        return $this->getTable('udpayout/payout_adjustment');
    }
    
    public function initAdjustmentsCollection($statement)
    {
        $adjCollection = Mage::getResourceModel('udpayout/payout_adjustment_collection');
        $adjCollection->addFieldToFilter('payout_id', $statement->getId());
        return $statement->setAdjustmentsCollection($adjCollection);
    }
    
    protected function _cleanRowTable($statement)
    {
        $poIds = array();
        $orders = $statement->getOrders();
        if (empty($orders)) {
            $poIds = array(false);
        } else {
            foreach ($orders as $order) {
                $poIds[] = $order['po_id'];
            }
        }
        $conn = $this->_getWriteAdapter();
        $conn->delete(
            $this->_getRowTable(), 
            $conn->quoteInto('payout_id=?', $statement->getId())
            .$conn->quoteInto(' AND (po_id not in (?)', $poIds)
            .$conn->quoteInto(' OR po_type!=? OR po_id is NULL)', $statement->getPoType())
        );
        return $this;
    }
    
    protected function _cleanAdjustmentTable($statement)
    {
        $conn = $this->_getWriteAdapter();
        $conn->delete(
            $this->_getAdjustmentTable(), 
            $conn->quoteInto('payout_id=?', $statement->getId())
            .$conn->quoteInto(' AND adjustment_id not like ?', Mage::helper('udropship')->getAdjustmentPrefix('statement:payout').'%')
            .$conn->quoteInto(' AND adjustment_id not like ?', Mage::helper('udropship')->getAdjustmentPrefix('payout').'%')
        );
        return $this;
    }
    
    protected function _beforeDelete(Mage_Core_Model_Abstract $object)
    {
        $this->_cleanPayout($object);
        return parent::_beforeDelete($object);
    }
    
    protected function _getCleanExcludePoSelect(Mage_Core_Model_Abstract $object)
    {
        $conn = $this->_getWriteAdapter();
        $excludePoSelect = $conn->select()->union(array(
            $conn->select()
                ->from(array('pr' => $this->getTable('payout_row')), array())
                ->where('pr.po_type=?', $object->getPoType())
                ->where('pr.payout_id!=?', $object->getId())
                ->columns('pr.po_id')
        ));
        $excludePoSelect->union(array(
            $conn->select()
                ->from(array('sr' => $this->getTable('udropship/vendor_statement_row')), array())
                ->where('sr.po_type=?', $object->getPoType())
                ->columns('sr.po_id')
        ));
        return $excludePoSelect;
    }
    
    protected function _cleanPayout(Mage_Core_Model_Abstract $object)
    {
        return $this->_cleanStatement($object);
    }
    
    protected function _cleanStatement(Mage_Core_Model_Abstract $object)
    {
        $conn = $this->_getWriteAdapter();
        $conn->delete(
            $this->getTable('payout_row'), 
            $conn->quoteInto('payout_id=?', $object->getId())
        );
        $excludePoSelect = $conn->select()->union(array(
            $conn->select()
                ->from(array('pr' => $this->getTable('payout_row')), array())
                ->where('pr.po_type=?', $object->getPoType())
                ->where('pr.payout_id!=?', $object->getId())
                ->columns('pr.po_id')
        ));
        $this->_changePosAttribute(array_keys($object->getOrders()), $object->getPoType(), 'payout_id', NULL, $excludePoSelect);
        parent::_cleanStatement($object);
        return $this;
    }
    
    protected function _prepareAdjustmentSave($statement, $adjustment)
    {
        $adjustment['payout_id'] = $statement->getId();
        $adjustment['statement_id'] = $statement->getStatementId();
        return parent::_prepareAdjustmentSave($statement, $adjustment);
    }
    
    protected function _prepareRowSave($statement, $row)
    {
        $row['payout_id'] = $statement->getId();
        $row['statement_id'] = $statement->getStatementId();
        return parent::_prepareRowSave($statement, $row);
    }

    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {
       
        parent::_afterSave($object);
        
        if ($object->getPayoutStatus() == Unirgy_DropshipPayout_Model_Payout::STATUS_CANCELED) {
            $this->_cleanPayout($object);
            return $this;
        }
        
        $this->_saveRows($object);
        $this->_saveAdjustments($object);
        
        if ($object->getOrders()) {
            $this->_changePosAttribute(array_keys($object->getOrders()), $object->getPoType(), 'udropship_payout_status', $object->getPayoutStatus());
            $this->_changePosAttribute(array_keys($object->getOrders()), $object->getPoType(), 'payout_id', $object->getId());
        }
        
        return $this;
    }
}
