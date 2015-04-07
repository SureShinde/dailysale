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
 * @package    Unirgy_DropshipBatch
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

class Unirgy_DropshipBatch_Model_Mysql4_Batch_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected $_batches = array();

    protected function _construct()
    {
        $this->_init('udbatch/batch');
        parent::_construct();
    }

    public function resetBatches()
    {
        $this->_batches = array();
    }

    public function loadScheduledBatches()
    {
        $hlp = Mage::helper('udropship');
        $hlpb = Mage::helper('udbatch');

        // find all scheduled batches scheduled for earlier than now, sorted by schedule time
        $this->addFieldToFilter('batch_status', 'scheduled')
            ->addFieldToFilter('scheduled_at', array('datetime'=>true, 'to'=>now()));
        $this->getSelect()->order('scheduled_at');

        // preprocess batches and set correct statuses
        foreach ($this->getItems() as $b) {
            $this->addBatch($b, true);
        }

        foreach($this->getItems() as $b) {
            if ($b->getBatchStatus()!='processing') {
                $this->removeBatch($b);
            }
        }

        return $this;
    }

    public function addBatch($batch, $validate=false)
    {
        $batch->setBatchStatus('processing')->save();
        $type = $batch->getBatchType();
        $vId = $batch->getVendorId();
        if ($validate) {
            // if vendors are not configured to be scheduled anymore, mark as canceled
            if (!Mage::helper('udbatch')->isVendorEnabled(Mage::helper('udropship')->getVendor($vId), $type, true)) {
                $batch->setBatchStatus('canceled')->save();
                return $this;
            }
            // if multiple batches for the same vendor/type exist, mark older batches as missed
            elseif (!empty($this->_batches[$type][$vId])) {
                #$this->_batches[$type][$vId]->setBatchStatus('missed')->save();
                $this->_batches[$type][$vId]->delete();
            }
        }
        $this->_batches[$type][$vId] = $batch;
        return $this;
    }

    public function removeBatch($batch)
    {
        $this->removeItemByKey($batch->getId());
        return $this;
    }

    public function getBatchesByType($type)
    {
        return !empty($this->_batches[$type]) ? $this->_batches[$type] : array();
    }

    public function addPendingPOsToExport($vendorIds=null)
    {
        if ($vendorIds===true) {
            if (empty($this->_batches['export_orders'])) {
                return $this;
            }
            $vendorIds = array_keys($this->_batches['export_orders']);
        }
        $pos = Mage::getModel('udropship/po')->getCollection()
            ->addPendingBatchStatusVendorFilter($vendorIds);
        if (!is_null($vendorIds)) {
            $pos->addAttributeToFilter('udropship_vendor', array('in'=>(array)$vendorIds));
        }

        foreach ($pos as $po) {
            $this->addPOToExport($po);
        }

        return $this;
    }

    public function addPendingStockPOsToExport($vendorIds=null)
    {
        if ($vendorIds===true) {
            if (empty($this->_batches['export_stockpo'])) {
                return $this;
            }
            $vendorIds = array_keys($this->_batches['export_stockpo']);
        }

        $stockPoIds = Mage::getModel('ustockpo/po')->getCollection()
            ->addAttributeToFilter('ustock_vendor', array('in'=>(array)$vendorIds))
            ->addPendingBatchStatusFilter()
            ->getAllIds();

        $pos = array();

        if (!empty($stockPoIds)) {
            $pos = Mage::getModel('udpo/po')->getCollection()
                ->addAttributeToFilter('ustockpo_id', array('in'=>$stockPoIds))
                ->addPendingStockpoBatchStatusFilter()
                ->addOrders()
                ->addStockPos();
        }
        foreach ($pos as $po) {
            $this->addStockPOToExport($po);
        }

        return $this;
    }

    public function addPOToExport($po)
    {
        $vId = $po->getUdropshipVendor();
        if (empty($this->_batches['export_orders'][$vId])) {
            $batch = false;
            foreach ($this->getItems() as $item) {
                if ($item->getVendorId()==$vId
                    && $item->getBatchType()=='export_orders'
                    && $item->getBatchStatus()=='processing'
                ) {
                    $batch = $item;
                    break;
                }
            }
            if (!$batch) {
                $batch = Mage::getModel('udbatch/batch')->setVendorId($vId);
                $this->addItem($batch);
            }
            $this->_batches['export_orders'][$vId] = $batch;
        } else {
            $batch = $this->_batches['export_orders'][$vId];
        }
        $batch->addPOToExport($po);
        return $this;
    }

    public function addStockPOToExport($po)
    {
        $vId = $po->getUstockVendor();
        if (empty($this->_batches['export_stockpo'][$vId])) {
            $batch = false;
            foreach ($this->getItems() as $item) {
                if ($item->getVendorId()==$vId
                    && $item->getBatchType()=='export_stockpo'
                    && $item->getBatchStatus()=='processing'
                ) {
                    $batch = $item;
                    break;
                }
            }
            if (!$batch) {
                $batch = Mage::getModel('udbatch/batch')->setVendorId($vId);
                $this->addItem($batch);
            }
            $this->_batches['export_stockpo'][$vId] = $batch;
        } else {
            $batch = $this->_batches['export_stockpo'][$vId];
        }
        $batch->addStockPOToExport($po);
        return $this;
    }

    public function exportOrders()
    {
        if (empty($this->_batches['export_orders'])) {
            return $this;
        }
        foreach ($this->_batches['export_orders'] as $batch) {
            $batch->exportOrders();
        }
        return $this;
    }
    
    public function exportStockpo()
    {
        if (empty($this->_batches['export_stockpo'])) {
            return $this;
        }
        foreach ($this->_batches['export_stockpo'] as $batch) {
            $batch->exportStockpo();
        }
        return $this;
    }

    public function importOrders()
    {
        if (empty($this->_batches['import_orders'])) {
            return $this;
        }
        foreach ($this->_batches['import_orders'] as $batch) {
            $batch->importOrders();
        }
        return $this;
    }
    
    public function importStockpo()
    {
        if (empty($this->_batches['import_stockpo'])) {
            return $this;
        }
        foreach ($this->_batches['import_stockpo'] as $batch) {
            $batch->importStockpo();
        }
        return $this;
    }
    
	public function importInventory()
    {
        if (empty($this->_batches['import_inventory'])) {
            return $this;
        }
        foreach ($this->_batches['import_inventory'] as $batch) {
            $batch->importInventory();
        }
        return $this;
    }

}