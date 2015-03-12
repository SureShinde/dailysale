<?php

class Unirgy_DropshipBatch_Model_Observer
{
    public function processStandard()
    {
        session_write_close();
        ignore_user_abort(true);
        set_time_limit(0);
        ob_implicit_flush();

        $batches = Mage::getModel('udbatch/batch')->getCollection();

        // dispatch scheduled batches
        $batches->loadScheduledBatches();
        $batches->addPendingPOsToExport(true)->exportOrders();
        $batches->addPendingStockPOsToExport(true)->exportStockpo();
        $batches->importOrders();
        $batches->importInventory();

        // generate new scheduled batches and clean batches history
        Mage::helper('udbatch')->generateSchedules()->cleanupSchedules();
    }

    /**
    * Possible exceptions:
    *
    * - batch.batch_status='processing': crashed during collecting rows for export
    * - batch.batch_status in ('exporting','importing'): crashed during processing distribution
    * - batch.batch_status='partial': some distributions passed, some failed
    * - batch.batch_status='error': all distributions failed
    * - dist.dist_status='pending': distribution didn't happen due to errors in other distributions
    * - dist.dist_status in ('exporting','importing'): crashed during distribution
    * - dist.dist_status='error': distribution failed
    *
    * @param mixed $observer
    */
    public function processExceptions()
    {

    }

    public function udropship_adminhtml_vendor_tabs_after($observer)
    {
        $block = $observer->getEvent()->getBlock();
        $id = $observer->getEvent()->getId();
        $v = Mage::helper('udropship')->getVendor($id);

        if (Mage::helper('udbatch')->isVendorEnabled($v)) {
            $block->addTab('batches_section', array(
                'label'     => Mage::helper('udbatch')->__('Import/Export Batches'),
                'title'     => Mage::helper('udbatch')->__('Import/Export Batches'),
                'content'   => $block->getLayout()->createBlock('udbatch/adminhtml_vendor_batch_grid', 'udropship.batch.grid')
                    ->setVendorId($id)
                    ->toHtml(),
            ));
        }
    }

    public function adminhtml_version($observer)
    {
        Mage::helper('udropship')->addAdminhtmlVersion('Unirgy_DropshipBatch');
    }

    public function udropship_order_save_after($observer)
    {
        $pos = $observer->getShipments();
        if ($pos) {
            try {
                $this->_instantPoExport($pos);
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }

    public function udpo_order_save_after($observer)
    {
        $pos = $observer->getUdpos();
        if ($pos) {
            try {
                $this->_instantPoExport($pos);
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }

    public function udpo_po_save_after($observer)
    {
        $this->_instantByStatusPoExport($observer->getPo());
    }
    public function udpo_po_status_save_after($observer)
    {
        $this->_instantByStatusPoExport($observer->getPo());
    }

    public function sales_order_shipment_save_after($observer)
    {
        if (!Mage::helper('udropship')->isUdpoActive()) $this->_instantByStatusPoExport($observer->getShipment());
    }
    public function udropship_shipment_status_save_after($observer)
    {
        if (!Mage::helper('udropship')->isUdpoActive()) $this->_instantByStatusPoExport($observer->getShipment());
    }

    protected function _instantByStatusPoExport($po)
    {
        try {
            $v = Mage::helper('udropship')->getVendor($po->getUdropshipVendor());
            $exportOnPoStatus = Mage::getStoreConfig('udropship/batch/export_on_po_status');
            if (!is_array($exportOnPoStatus)) {
                $exportOnPoStatus = explode(',', $exportOnPoStatus);
            }
            if ($v->getId() && $v->getData("batch_export_orders_method") == 'status_instant'
                && in_array($po->getUdropshipStatus(), $exportOnPoStatus)
            ) {
                $batch = Mage::helper('udbatch')->createBatch('export_orders', $v, 'processing')->save();
                $batch->addPOToExport($po)->exportOrders();
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    protected function _instantPoExport($pos)
    {
        foreach ($pos as $po) {
            $v = Mage::helper('udropship')->getVendor($po->getUdropshipVendor());
            if ($v->getId() && $v->getData("batch_export_orders_method") == 'instant') {
                $batch = Mage::helper('udbatch')->createBatch('export_orders', $v, 'processing')->save();
                $batch->addPOToExport($po)->exportOrders();
            }
        }
    }

}