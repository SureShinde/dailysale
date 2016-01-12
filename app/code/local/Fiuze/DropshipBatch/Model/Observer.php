<?php
/**
 * DropshipBatch
 *
 * @author      Fiuze Team
 * @category    Fiuze
 * @package     DropshipBatch
 * @copyright   Copyright (c) 2016 Fiuze
 */
class Fiuze_DropshipBatch_Model_Observer extends Unirgy_DropshipBatch_Model_Observer
{
    /**
     * override method to increase memory limit
     */
    public function processStandard()
    {
        session_write_close();
        ignore_user_abort(true);
        set_time_limit(0);
        ini_set('memory_limit','8192M');
        ob_implicit_flush();

        /** @var $batches Unirgy_DropshipBatch_Model_Mysql4_Batch_Collection */
        $batches = Mage::getModel('udbatch/batch')->getCollection();

        // dispatch scheduled batches
        $batches->loadScheduledBatches();
        $batches->addPendingPOsToExport(true)->exportOrders();
        $batches->addPendingStockPOsToExport(true)->exportStockpo();
        $batches->importOrders();
        $batches->importInventory();

        // generate new scheduled batches and clean batches history
        /** @var $helper Unirgy_DropshipBatch_Helper_Data */
        $helper = Mage::helper('udbatch');
        $helper->generateSchedules()->cleanupSchedules();
    }
}
