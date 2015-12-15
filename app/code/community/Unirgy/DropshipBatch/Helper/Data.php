<?php
require_once Mage::getBaseDir('lib').DS.'SweetTooth/pest/vendor/autoload.php';

class Unirgy_DropshipBatch_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_batch;

    public function isVendorEnabled($vendor, $batchType=true, $scheduled=false)
    {
        if ($batchType===true) {
            return $this->isVendorEnabled($vendor, 'export_orders', $scheduled)
                && $this->isVendorEnabled($vendor, 'import_orders', $scheduled)
                && $this->isVendorEnabled($vendor, 'export_stockpo', $scheduled)
                && $this->isVendorEnabled($vendor, 'import_stockpo', $scheduled)
                && $this->isVendorEnabled($vendor, 'import_inventory', $scheduled);
        }
        if (!$scheduled) {
            return $vendor->getData("batch_{$batchType}_method");
        } else {
            return $vendor->getData("batch_{$batchType}_method")=='auto'
                && (!$scheduled || $vendor->getData("batch_{$batchType}_schedule"));
        }
    }

    public function createBatch($type, $vendor, $status='pending')
    {
        if (!$vendor instanceof Unirgy_Dropship_Model_Vendor) {
            $vendor = Mage::helper('udropship')->getVendor($vendor);
        }
        $batch = Mage::getModel('udbatch/batch')->addData(array(
            'batch_type' => $type,
            'batch_status' => $status,
            'vendor_id' => $vendor->getId(),
            'use_custom_template' => $this->_useCustomTemplate,
            'is_all_vendors_import' => $this->_isAllVendorsImport,
        ));
        return $batch;
    }

    public function importVendorOrdersSFA($vendor, $filename=null)
    {
        return $this->_importVendorOrders($vendor, $filename, true);
    }
    public function importVendorOrders($vendor, $filename=null)
    {
        return $this->_importVendorOrders($vendor, $filename, false);
    }

    protected function _importVendorOrders($vendor, $filename=null, $skipFileActions=false)
    {
        $batch = $this->createBatch('import_orders', $vendor, 'processing')->save();
        $batch->setSkipFileactionsFlag($skipFileActions);
        $batch->generateDists('import_orders', $filename);
        $this->_batch = $batch;
        $batch->importOrders()->save();
        return $batch;
    }

    public function importVendorStockpoSFA($vendor, $filename=null)
    {
        return $this->_importVendorStockpo($vendor, $filename, true);
    }
    public function importVendorStockpo($vendor, $filename=null)
    {
        return $this->_importVendorStockpo($vendor, $filename, false);
    }

    protected function _importVendorStockpo($vendor, $filename=null, $skipFileActions=false)
    {
        $batch = $this->createBatch('import_stockpo', $vendor, 'processing')->save();
        $batch->setSkipFileactionsFlag($skipFileActions);
        $batch->generateDists('import_stockpo', $filename);
        $this->_batch = $batch;
        $batch->importStockpo()->save();
        return $batch;
    }
    
	public function importVendorInventorySFA($vendor, $filename=null)
    {
        return $this->_importVendorInventory($vendor, $filename, true);
    }
    public function importVendorInventory($vendor, $filename=null)
    {
        return $this->_importVendorInventory($vendor, $filename, false);
    }

    protected function _importVendorInventory($vendor, $filename=null, $skipFileActions=false)
    {
        $batch = $this->createBatch('import_inventory', $vendor, 'processing')
            ->setManualFlag(true)
            ->save();
        $batch->setSkipFileactionsFlag($skipFileActions);
        $batch->generateDists('import_inventory', $filename);
        $this->_batch = $batch;
        $batch->importInventory()->save();
        return $batch;
    }
    
    public function getBatch()
    {
    	return $this->_batch;
    }

    /**
    * Export POs to file
    *
    * @param mixed $vendor
    * @param mixed $filename
    * @param mixed $condCb callback to set conditions on $pos collection, pending POs if null
    */
    public function exportVendorOrders($vendor, $filename=null, $condCb=null)
    {
        if (!$vendor instanceof Unirgy_Dropship_Model_Vendor) {
            $vendor = Mage::helper('udropship')->getVendor($vendor);
        }
        $pos = Mage::getModel('udropship/po')->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('udropship_vendor', $vendor->getId());
        if (!$condCb) {
            $pos->addPendingBatchStatusVendorFilter($vendor);
        } else {
            call_user_func($condCb, $pos, $vendor);
        }
        $batch = $this->createBatch('export_orders', $vendor, 'processing')
            ->save()
            ->generateDists('export_orders', $filename);

        $this->_batch = $batch;

        foreach ($pos as $po) {
            $batch->addPOToExport($po);
        }
        $batch->exportOrders()->save();
        return $batch;
    }

    public function exportVendorStockpo($vendor, $filename=null, $condCb=null)
    {
        if (!$vendor instanceof Unirgy_Dropship_Model_Vendor) {
            $vendor = Mage::helper('udropship')->getVendor($vendor);
        }
        $pos = Mage::getModel('udropship/po')->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('ustock_vendor', $vendor->getId());
        if (!$condCb) {
            $pos->addPendingStockpoBatchStatusFilter();
        } else {
            call_user_func($condCb, $pos, $vendor);
        }
        $batch = $this->createBatch('export_stockpo', $vendor, 'processing')
            ->save()
            ->generateDists('export_stockpo', $filename);

        $this->_batch = $batch;

        $stockPos = array();
        $pos->addOrders()->addStockPos();
        foreach ($pos as $po) {
            $stockPoKey = $po->getUstockpoId().'-'.$po->getUstockVendor();
            if (empty($stockPos[$stockPoKey])) {
                if ($po->getUstockpoId()) {
                    $stockPos[$stockPoKey] = $po->getStockPo();
                } else {
                    $stockPos[$stockPoKey] = Mage::helper('ustockpo')->udpoToStockpo($po);
                }
            }
            $stockPos[$stockPoKey]->addUdpo($po);
        }
        foreach ($stockPos as $stockPo) {
            if (!$stockPo->getId()) $stockPo->save();
        }
        foreach ($pos as $po) {
            $this->addStockPOToExport($po);
        }
        $batch->exportStockpo()->save();
        return $batch;
    }

    protected $_isAllVendorsImport = false;
    public function isAllVendorsImport($isAllVendorsImport)
    {
        $this->_isAllVendorsImport = $isAllVendorsImport;
        return $this;
    }
    protected $_useCustomTemplate = '';
    public function useCustomTemplate($useCustomTemplate)
    {
        $this->_useCustomTemplate = $useCustomTemplate;
        return $this;
    }
    public function processPost()
    {
        $r = Mage::app()->getRequest();
        $allowAllVendors = Mage::getStoreConfigFlag('udropship/batch/allow_all_vendors_import');
        $vendor = Mage::helper('udropship')->getVendor($r->getParam('vendor_id'));
        $isAllVendors = !$vendor->getId();
        if ($isAllVendors && !$allowAllVendors) {
            Mage::throwException(Mage::helper('udropship')->__('Invalid vendor'));
        }
        if ($isAllVendors && !$this->_useCustomTemplate) {
            Mage::throwException(Mage::helper('udropship')->__('Please select "Use Template"'));
        }
        $notes = $r->getParam('batch_notes');
        $errors = false;
        switch ($r->getParam('batch_type')) {
        case 'import_orders':
            if (!empty($_FILES['import_orders_upload']['tmp_name'])) {
                $filename = Mage::getConfig()->getVarDir('udbatch') . '/' . $_FILES['import_orders_upload']['name'];
                @move_uploaded_file($_FILES['import_orders_upload']['tmp_name'], $filename);
                try {
                    $csv = new Varien_File_Csv();
                    $csv->setDelimiter(";");
                    $trackingNumbersTmp = $csv->getData($filename);
                } catch (Exception $ex) {

                }
                $trackingNumbersContent = array();
                foreach ($trackingNumbersTmp as $item) {
                    $trackingNumbersContent[] = implode(';', $item);
                }
                $this->trackigForImport($trackingNumbersContent);
                Mage::getSingleton('core/session')->setData('tracking_numbers_content',$trackingNumbersContent);
            }
            //track from textarea
            if ($r->getParam('import_orders_textarea')) {
                $content = trim($r->getParam('import_orders_textarea'));
                $trackingNumbersContent = preg_split("/[\s,]+/", $content);
                $this->trackigForImport($trackingNumbersContent);
                Mage::getSingleton('core/session')->setData('tracking_numbers_content',$trackingNumbersContent);
            }
            //
            if ($r->getParam('import_orders_locations')) {
                try {
                    $this->importVendorOrders($vendor, $r->getParam('import_orders_locations'));
                    $this->_batch->setStatus('success');
                } catch (Exception $e) {
                    if ($this->_batch) {
                        $this->_batch->setBatchStatus('error')->setErrorInfo($e->getMessage());
                    }
                    $errors = true;
                }
                if ($this->_batch) {
                    $this->_batch->setNotes($notes)->save();
                }
            }
            if ($r->getParam('import_orders_default') && !$isAllVendors) {
                try {
                    $this->importVendorOrders($vendor);
                    $this->_batch->setStatus('success');
                } catch (Exception $e) {
                    if ($this->_batch) {
                        $this->_batch->setBatchStatus('error')->setErrorInfo($e->getMessage());
                    }
                    $errors = true;
                }
                if ($this->_batch) {
                    $this->_batch->setNotes($notes)->save();
                }
            }

            if ($errors) {
                Mage::throwException(Mage::helper('udropship')->__('Errors during importing, please see individual batches for details'));
            }
            break;
        //end
        case 'export_orders':
            $batch = $this->createBatch('export_orders', $vendor, 'processing')
                ->save();

            if ($r->getParam('export_orders_default')) {
                $batch->generateDists('export_orders');
            }
            if ($r->getParam('export_orders_locations')) {
                $batch->generateDists('export_orders', $r->getParam('export_orders_locations'), true);
            }
            if ($r->getParam('export_orders_download')) {
                if ($r->getParam('export_orders_download_filename')) {
                    $filename = $r->getParam('export_orders_download_filename');
                } else {
                    $filename = 'export_orders-'.date('YmdHis').'.txt';
                }
                $filename = Mage::getConfig()->getVarDir('udbatch').'/'.$filename;
                $batch->generateDists('export_orders', $filename, true);
            }

            $this->_batch = $batch;

            $pos = Mage::getModel('udropship/po')->getCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('udropship_vendor', $vendor->getId())
                ->addPendingBatchStatusVendorFilter($vendor)
                ->addOrders();

            foreach ($pos as $po) {
                $batch->addPOToExport($po);
            }

            $batch->exportOrders()->save();
/*
            if ($r->getParam('export_orders_download')) {
                Mage::helper('udropship')->sendDownload(basename($filename), file_get_contents($filename), 'text/plain');
            }
*/
            break;

        case 'import_stockpo':
            if (!empty($_FILES['import_stockpo_upload']['tmp_name'])) {
                $filename = Mage::getConfig()->getVarDir('udbatch').'/'.$_FILES['import_stockpo_upload']['name'];
                @move_uploaded_file($_FILES['import_stockpo_upload']['tmp_name'], $filename);
                try {
                    $this->importVendorStockpoSFA($vendor, $filename);
                    $this->_batch->setStatus('success');
                } catch (Exception $e) {
                    if ($this->_batch) {
                        $this->_batch->setBatchStatus('error')->setErrorInfo($e->getMessage());
                    }
                    $errors = true;
                }
                if ($this->_batch) {
                    $this->_batch->setNotes($notes)->save();
                }
                $this->_batch->setSkipFileactionsFlag(false);
            }
            if ($r->getParam('import_stockpo_textarea')) {
                $filename = Mage::getConfig()->getVarDir('udbatch').'/import_stockpo-'.date('YmdHis').'.txt';
                @file_put_contents($filename, $r->getParam('import_stockpo_textarea'));
                try {
                    $this->importVendorStockpoSFA($vendor, $filename);
                    $this->_batch->setStatus('success');
                } catch (Exception $e) {
                    if ($this->_batch) {
                        $this->_batch->setBatchStatus('error')->setErrorInfo($e->getMessage());
                    }
                    $errors = true;
                }
                if ($this->_batch) {
                    $this->_batch->setNotes($notes)->save();
                }
                $this->_batch->setSkipFileactionsFlag(false);
            }
            if ($r->getParam('import_stockpo_locations')) {
                try {
                    $this->importVendorStockpo($vendor, $r->getParam('import_stockpo_locations'));
                    $this->_batch->setStatus('success');
                } catch (Exception $e) {
                    if ($this->_batch) {
                        $this->_batch->setBatchStatus('error')->setErrorInfo($e->getMessage());
                    }
                    $errors = true;
                }
                if ($this->_batch) {
                    $this->_batch->setNotes($notes)->save();
                }
            }
            if ($r->getParam('import_stockpo_default')) {
                try {
                    $this->importVendorStockpo($vendor);
                    $this->_batch->setStatus('success');
                } catch (Exception $e) {
                    if ($this->_batch) {
                        $this->_batch->setBatchStatus('error')->setErrorInfo($e->getMessage());
                    }
                    $errors = true;
                }
                if ($this->_batch) {
                    $this->_batch->setNotes($notes)->save();
                }
            }

            if ($errors) {
                Mage::throwException(Mage::helper('udropship')->__('Errors during importing, please see individual batches for details'));
            }
            break;

        case 'export_stockpo':
            $batch = $this->createBatch('export_stockpo', $vendor, 'processing')
                ->save();

            if ($r->getParam('export_stockpo_default')) {
                $batch->generateDists('export_stockpo');
            }
            if ($r->getParam('export_stockpo_locations')) {
                $batch->generateDists('export_stockpo', $r->getParam('export_stockpo_locations'), true);
            }
            if ($r->getParam('export_stockpo_download')) {
                if ($r->getParam('export_stockpo_download_filename')) {
                    $filename = $r->getParam('export_stockpo_download_filename');
                } else {
                    $filename = 'export_stockpo-'.date('YmdHis').'.txt';
                }
                $filename = Mage::getConfig()->getVarDir('udbatch').'/'.$filename;
                $batch->generateDists('export_stockpo', $filename, true);
            }

            $this->_batch = $batch;

            $stockPoIds = Mage::getModel('ustockpo/po')->getCollection()
                ->addAttributeToFilter('ustock_vendor', $vendor->getId())
                ->addPendingBatchStatusVendorFilter($vendor)
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
                $batch->addStockPOToExport($po);
            }


            $batch->exportStockpo()->save();
/*
            if ($r->getParam('export_stockpo_download')) {
                Mage::helper('udropship')->sendDownload(basename($filename), file_get_contents($filename), 'text/plain');
            }
*/
            break;


            
        case 'import_inventory':
            if (!empty($_FILES['import_inventory_upload']['tmp_name'])) {
                $filename = Mage::getConfig()->getVarDir('udbatch').'/'.$_FILES['import_inventory_upload']['name'];
                @move_uploaded_file($_FILES['import_inventory_upload']['tmp_name'], $filename);
                try {
                    $this->importVendorInventorySFA($vendor, $filename);
                    $this->_batch->setStatus('success');
                } catch (Exception $e) {
                    if ($this->_batch) {
                        $this->_batch->setBatchStatus('error')->setErrorInfo($e->getMessage());
                    }
                    $errors = true;
                }
                if ($this->_batch) {
                    $this->_batch->setNotes($notes)->save();
                }
                $this->_batch->setSkipFileactionsFlag(false);
            }
            if ($r->getParam('import_inventory_textarea')) {
                $filename = Mage::getConfig()->getVarDir('udbatch').'/import_inventory-'.date('YmdHis').'.txt';
                @file_put_contents($filename, $r->getParam('import_inventory_textarea'));
                try {
                    $this->importVendorInventorySFA($vendor, $filename);
                    $this->_batch->setStatus('success');
                } catch (Exception $e) {
                    if ($this->_batch) {
                        $this->_batch->setBatchStatus('error')->setErrorInfo($e->getMessage());
                    }
                    $errors = true;
                }
                if ($this->_batch) {
                    $this->_batch->setNotes($notes)->save();
                }
                $this->_batch->setSkipFileactionsFlag(false);
            }
            if ($r->getParam('import_inventory_locations')) {
                try {
                    $this->importVendorInventory($vendor, $r->getParam('import_inventory_locations'));
                    $this->_batch->setStatus('success');
                } catch (Exception $e) {
                    if ($this->_batch) {
                        $this->_batch->setBatchStatus('error')->setErrorInfo($e->getMessage());
                    }
                    $errors = true;
                }
                if ($this->_batch) {
                    $this->_batch->setNotes($notes)->save();
                }
            }
            if ($r->getParam('import_inventory_default')) {
                try {
                    $this->importVendorInventory($vendor);
                    $this->_batch->setStatus('success');
                } catch (Exception $e) {
                    if ($this->_batch) {
                        $this->_batch->setBatchStatus('error')->setErrorInfo($e->getMessage());
                    }
                    $errors = true;
                }
                if ($this->_batch) {
                    $this->_batch->setNotes($notes)->save();
                }
            }

            if ($errors) {
                Mage::throwException(Mage::helper('udropship')->__('Errors during importing, please see individual batches for details'));
            }
            break;


        default:
            Mage::throwException(Mage::helper('udropship')->__('Invalid batch type'));
        }
    }

    public function getManualImportTemplates($store=null)
    {
        $importTpls = Mage::getStoreConfig('udropship/batch/manual_import_templates', $store);
        $importTpls = Mage::helper('udropship')->unserialize($importTpls);
        return $importTpls;
    }

    public function getManualImportTemplateTitles($store=null)
    {
        $importTpls = $this->getManualImportTemplates($store);
        $_importTpls = array();
        if (is_array($importTpls)) {
            foreach ($importTpls as $imtpl) {
                $_importTpls[] = @$imtpl['title'];
            }
        }
        return array_unique(array_filter($_importTpls));
    }

    public function getManualImportTemplate($title, $store=null)
    {
        $tpl = false;
        $importTpls = $this->getManualImportTemplates($store);
        if (is_array($importTpls)) {
            foreach ($importTpls as $imtpl) {
                if ($title == @$imtpl['title']) {
                    $tpl = $imtpl['template'];
                    break;
                }
            }
        }
        return $tpl;
    }

    public function getManualExportTemplates($store=null)
    {
        $exportTpls = Mage::getStoreConfig('udropship/batch/manual_export_templates', $store);
        $exportTpls = Mage::helper('udropship')->unserialize($exportTpls);
        return $exportTpls;
    }

    public function getManualExportTemplateTitles($store=null)
    {
        $exportTpls = $this->getManualExportTemplates($store);
        $_exportTpls = array();
        if (is_array($exportTpls)) {
            foreach ($exportTpls as $extpl) {
                $_exportTpls[] = @$extpl['title'];
            }
        }
        return array_unique(array_filter($_exportTpls));
    }

    public function getManualExportTemplate($title, $field, $store=null)
    {
        $tpl = false;
        $exportTpls = $this->getManualExportTemplates($store);
        if (is_array($exportTpls)) {
            foreach ($exportTpls as $extpl) {
                if ($title == @$extpl['title']) {
                    $tpl = $extpl[$field];
                    break;
                }
            }
        }
        return $tpl;
    }

    public function retryDists($distIds)
    {
        $dists = Mage::getModel('udbatch/batch_dist')->getCollection()
            ->addFieldToFilter('dist_id', array('in'=>$distIds));
        $batchIds = array();
        foreach ($dists as $dist) {
            $batchIds[$dist->getBatchId()][] = $dist->getId();
        }
        $batches = Mage::getModel('udbatch/batch')->getCollection()
            ->addFieldToFilter('batch_id', array('in'=>array_keys($batchIds)));
        foreach ($batches as $batch) {
            $batch->retry($batchIds[$batch->getId()]);
        }
        return $this;
    }


    public function generateSchedules()
    {
        Mage::helper('udbatch/protected')->generateSchedules();
        return $this;
    }

    public function cleanupSchedules()
    {
        return $this;

        // check if history cleanup is needed
        $lastCleanup = Mage::app()->loadCache(self::CACHE_KEY_LAST_HISTORY_CLEANUP_AT);
        if ($lastCleanup > time() - Mage::getStoreConfig(self::XML_PATH_HISTORY_CLEANUP_EVERY)*60) {
            return $this;
        }

        $history = Mage::getModel('udbatch/batch')->getCollection()
            ->addFieldToFilter('batch_status', array('in'=>array(
                Mage_Cron_Model_Schedule::STATUS_SUCCESS,
                Mage_Cron_Model_Schedule::STATUS_MISSED,
                Mage_Cron_Model_Schedule::STATUS_ERROR,
            )))->load();

        $historyLifetimes = array(
            Mage_Cron_Model_Schedule::STATUS_SUCCESS => Mage::getStoreConfig(self::XML_PATH_HISTORY_SUCCESS)*60,
            Mage_Cron_Model_Schedule::STATUS_MISSED => Mage::getStoreConfig(self::XML_PATH_HISTORY_FAILURE)*60,
            Mage_Cron_Model_Schedule::STATUS_ERROR => Mage::getStoreConfig(self::XML_PATH_HISTORY_FAILURE)*60,
        );

        $now = time();
        foreach ($history->getIterator() as $record) {
            if (strtotime($record->getExecutedAt()) < $now-$historyLifetimes[$record->getStatus()]) {
                $record->delete();
            }
        }

        // save time history cleanup was ran with no expiration
        Mage::app()->saveCache(time(), self::CACHE_KEY_LAST_HISTORY_CLEANUP_AT, array('crontab'), null);

        return $this;
    }

    /**
     * @param $trackingNumbersContent array['order','tracking']
     * @throws Mage_Core_Exception
     */
    public function trackigForImport($trackingNumbersContent){
        $_poHlp = Mage::helper('udpo');
        $_udpos = Mage::helper('core')->decorateArray($_poHlp->getVendorPoCollection(true), '');

        foreach ($trackingNumbersContent as $trackingOrder) {
            $currentRow = preg_split("/;/", $trackingOrder);
            if(count($currentRow) != 2){
                Mage::throwException($_poHlp->__("Incorrect input format"));
            }
            $currentTrackingNumber = $currentRow[1];
            //validation of tracknumbers
            $api_key = Mage::app()->getWebsite(0)->getConfig('aftership_options/messages/api_key');
            $courier = new AfterShip\Couriers($api_key);
            $response = $courier->detect($currentTrackingNumber);
            $trackings = new AfterShip\Trackings($api_key);
            $response = $trackings->get($response['data']['couriers'][0]['slug'], $currentTrackingNumber, array('title', 'order_id'));
            if ($response['meta']['code'] != 4004) {
                Mage::throwException($_poHlp->__("Track number " . $currentTrackingNumber . " are already tracked."));
            }
        }
        foreach ($trackingNumbersContent as $trackingOrder) {
            $currentRow = preg_split("/;/", $trackingOrder);

            $currentOrderId = $currentRow[0];
            $currentTrackingNumber = $currentRow[1];
            foreach ($_udpos as $keyPo => $item) {
                $orderId = $item->getOrderIncrementId();
                if ($orderId == $currentOrderId) {
                    //$_session = Mage::getSingleton('udropship/session');
                    //$_vendor = $_session->getVendor();
                    $hlp = Mage::helper('udropship');
                    $udpoHlp = Mage::helper('udpo');
                    $po = Mage::getModel('udpo/po')->load($keyPo);
                    $vendor = $hlp->getVendor($po->getUdropshipVendor());

                    $carrierInstances = Mage::getSingleton('shipping/config')->getAllCarriers();
                    $carriers['custom'] = Mage::helper('sales')->__('Custom Value');
                    foreach ($carrierInstances as $code => $carrier) {
                        if ($carrier->isTrackingAvailable()) {
                            $carriers[$code] = $carrier->getConfigData('title');
                        }
                    }
                    $method = explode('_', $po->getUdropshipMethod(), 2);
                    $api_key = Mage::app()->getWebsite(0)->getConfig('aftership_options/messages/api_key');
                    $courier = new AfterShip\Couriers($api_key);
                    $track_carrier = $courier->detect($currentTrackingNumber);
                    for($i=0;$i<$track_carrier['data']['total'];$i++){
                        if (array_key_exists($track_carrier['data']['couriers'][$i]['slug'], $carriers)) {
                            $carrier = $track_carrier['data']['couriers'][$i]['slug'];
                            $title = $track_carrier['data']['couriers'][$i]['name'];
                            $trackingNumber = trim($currentTrackingNumber);
                            if($this->asTrackNumber($trackingNumber, $orderId)){
                                $track = Mage::getModel('sales/order_shipment_track')
                                    ->setNumber($trackingNumber)
                                    ->setCarrierCode($carrier)
                                    ->setTitle($title)
                                    ->setUdropshipStatus(Unirgy_Dropship_Model_Source::TRACK_STATUS_READY);
                                $trackItems = Mage::getModel('track/track')
                                    ->getCollection()
                                    ->addFieldToFilter('tracking_number', array('eq' => $trackingNumber))
                                    ->addFieldToFilter('order_id', array('eq' => $orderId))
                                    ->getItems();
                                $trackItem = reset($trackItems);
                                if($trackItem){
                                    $trackItem->setErrorTracking('Tracking already exists.');
                                    $trackItem->save();
                                }
                                $shipment = $po;
                                if ($po instanceof Unirgy_DropshipPo_Model_Po) {
                                    $_shipment = false;
                                    foreach ($po->getShipmentsCollection() as $_s) {
                                        if ($_s->getUdropshipStatus() == Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_CANCELED) {
                                            continue;
                                        }
                                        $_shipment = $_s;
                                    }
                                    if (!$_shipment) {
                                        $shipment = Mage::helper('udpo')->createShipmentFromPo($po);
                                    } else {
                                        $shipment = $_shipment;
                                    }
                                }
                                if (empty($shipment)) Mage::throwException('cannot find/initialize shipment record');
                                $shipment->addTrack($track);
                                if ($track->getData('__update_date')) {
                                    $shipment->setCreatedAt($track->getCreatedAt());
                                }
                                Mage::helper('udropship')->addShipmentComment(
                                    $shipment,
                                    Mage::helper('udbatch')->__('Tracking ID %s was added', $track->getNumber())
                                );
                                Mage::helper('udropship')->processTrackStatus($track, true, true);
                                //$shipment->setData('__dummy', 1)->save();
                            }else{//remove track number
                                $tracks = Mage::getModel('sales/order_shipment_track')
                                    ->getCollection()
                                    ->addFieldToFilter('track_number', array('eq' => $trackingNumber))
                                    ->addFieldToFilter('carrier_code', array('eq' => $carrier))
                                    ->getItems();
                                foreach($tracks as $item){
                                    $item->delete();
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function asTrackNumber($trackingNumber, $orderId){
        $api_key = Mage::app()->getWebsite(0)->getConfig('aftership_options/messages/api_key');
        $courier = new AfterShip\Couriers($api_key);
        $response = $courier->detect($trackingNumber);
        $data = $response['data'];
        $data['total']? $result = true: $result = false;
        if(!$result){
            $track = Mage::getModel('track/track');
            $track->setTrackingNumber($trackingNumber);
            $track->setOrderId($orderId);
            $track->setErrorTracking('Not valid.');
            $track->save();
        }
        return $result;
    }
}