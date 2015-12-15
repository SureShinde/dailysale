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

class Unirgy_DropshipPo_Model_Mysql4_Po_Collection extends Mage_Sales_Model_Mysql4_Order_Collection_Abstract
{
    protected $_eventPrefix = 'udpo_po_collection';
    protected $_eventObject = 'po_collection';
    protected $_orderField = 'order_id';

    protected function _construct()
    {
        $this->_init('udpo/po');
    }

    public function afterLoad()
    {
        $this->_afterLoad();
        return $this;
    }
    protected function _afterLoad()
    {
        $this->walk('afterLoad');
        if (($stockPo = $this->getStockPo())) {
            foreach ($this->getItems() as $item) {
                $item->setStockPo($stockPo);
            }
        }
    }

    public function addPendingBatchStatusFilter()
    {
        return $this->_addPendingBatchStatusFilter();
    }
    public function addPendingBatchStatusVendorFilter($vendor)
    {
        return $this->_addPendingBatchStatusFilter($vendor);
    }
    protected function _addPendingBatchStatusFilter($vendor=null)
    {
        if (is_array($vendor)) {
            $exportOnPoStatusAll = array();
            foreach ($vendor as $vId) {
                $exportOnPoStatus = array();
                if ($vId && ($v = Mage::helper('udropship')->getVendor($vId)) && $v->getId()) {
                    $exportOnPoStatus = $v->getData('batch_export_orders_export_on_po_status');
                }
                if (in_array('999', $exportOnPoStatus) || empty($exportOnPoStatus)) {
                    $exportOnPoStatus = Mage::getStoreConfig('udropship/batch/export_on_po_status');
                    if (!is_array($exportOnPoStatus)) {
                        $exportOnPoStatus = explode(',', $exportOnPoStatus);
                    }
                }
                $exportOnPoStatusAll[(int)$vId] = $this->getSelect()->getAdapter()->quoteInto('main_table.udropship_status in (?)', $exportOnPoStatus);
            }
            $this->getSelect()->where(
                Mage::helper('udropship/catalog')->getCaseSql('main_table.udropship_vendor', $exportOnPoStatusAll)
            );
        } else {
            $exportOnPoStatus = array();
            if ($vendor && ($vendor = Mage::helper('udropship')->getVendor($vendor)) && $vendor->getId()) {
                $exportOnPoStatus = $vendor->getData('batch_export_orders_export_on_po_status');
            }
            if (in_array('999', $exportOnPoStatus) || empty($exportOnPoStatus)) {
                $exportOnPoStatus = Mage::getStoreConfig('udropship/batch/export_on_po_status');
                if (!is_array($exportOnPoStatus)) {
                    $exportOnPoStatus = explode(',', $exportOnPoStatus);
                }
            }
            $this->getSelect()->where("main_table.udropship_status in (?)", $exportOnPoStatus);
        }
        return $this;
    }
    public function addPendingStockpoBatchStatusFilter()
    {
    	$exportOnPoStatus = Mage::getStoreConfig('udropship/batch/export_on_stockpo_status');
    	if (!is_array($exportOnPoStatus)) {
    		$exportOnPoStatus = explode(',', $exportOnPoStatus);
    	}
        $this->getSelect()->where("main_table.udropship_status in (?)", $exportOnPoStatus);
        return $this;
    }

    public function addPendingStockpoFilter()
    {
    	$exportOnPoStatus = Mage::getStoreConfig('udropship/stockpo/generate_on_po_status');
    	if (!is_array($exportOnPoStatus)) {
    		$exportOnPoStatus = explode(',', $exportOnPoStatus);
    	}
        $this->getSelect()->where("main_table.udropship_status in (?)", $exportOnPoStatus);
        $this->getSelect()->where("ustockpo_id is null");
        return $this;
    }

    protected $_orderJoined=false;
    protected function _joinOrderTable()
    {
        if (!$this->_orderJoined) {
            $this->getSelect()->join(
                array('order_table'=>$this->getTable('sales/order')),
                'order_table.entity_id=main_table.order_id',
                array()
            );
            $this->_orderJoined = true;
        }
        return $this;
    }

    public function addOrderDateFilter($dateFilter)
    {
        $this->_joinOrderTable();
        $this->addFieldToFilter('order_table.created_at', $dateFilter);
        return $this;
    }


    public function addOrders()
    {
        if (!Mage::helper('udropship')->isSalesFlat()) {
            $this->addAttributeToSelect('*', 'inner');
        }

        $orderIds = array();
        foreach ($this as $po) {
            if ($po->getOrderId()) {
                $orderIds[$po->getOrderId()] = 1;
            }
        }

        if ($orderIds) {
            $orders = Mage::getModel('sales/order')->getCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('entity_id', array('in'=>array_keys($orderIds)));
            foreach ($this as $po) {
                $po->setOrder($orders->getItemById($po->getOrderId()));
            }
        }
        return $this;
    }

    public function addStockPos()
    {
        $this->addAttributeToSelect('*', 'inner');

        $stockPoIds = array();
        foreach ($this as $po) {
            if ($po->getUstockpoId()) {
                $stockPoIds[$po->getUstockpoId()] = 1;
            }
        }

        if ($stockPoIds) {
            $stockPos = Mage::getModel('ustockpo/po')->getCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('entity_id', array('in'=>array_keys($stockPoIds)));
            foreach ($this as $po) {
                $po->setStockPo($stockPos->getItemById($po->getUstockpoId()));
            }
        }
        return $this;
    }

    protected $_stockPo;
    public function setStockPo($stockPo)
    {
        $this->_stockPo = $stockPo;
        return $this;
    }
    public function getStockPo()
    {
        return $this->_stockPo;
    }

    public function setStockPoFilter($stockPo)
    {
        if ($stockPo instanceof Unirgy_DropshipStockPo_Model_Po) {
            $this->setStockPo($stockPo);
            $stockPoId = $stockPo->getId();
            if ($stockPoId) {
                $this->addFieldToFilter('ustockpo_id', $stockPoId);
            } else {
                $this->_totalRecords = 0;
                $this->_setIsLoaded(true);
            }
        } else {
            $this->addFieldToFilter('ustockpo_id', $stockPo);
        }
        return $this;
    }
}
