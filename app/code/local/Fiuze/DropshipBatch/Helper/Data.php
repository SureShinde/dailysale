<?php

/**
 * @author Fiuze Team
 * @category Fiuze
 * @package Fiuze_DropshipBatch
 * @copyright Copyright (c) 2016 Fiuze
 */
class Fiuze_DropshipBatch_Helper_Data extends Unirgy_DropshipBatch_Helper_Data
{
    public function processPost()
    {
        $r = Mage::app()->getRequest();
        $vendor = Mage::helper('udropship')->getVendor($r->getParam('vendor_id'));
        if (!$vendor) {
            Mage::throwException($this->__('Invalid vendor'));
        }
        $notes = $r->getParam('batch_notes');
        $errors = false;

        switch ($r->getParam('batch_type')) {
            case 'import_orders':
                $importContent = $r->getParam('import_orders_textarea');
                if (!empty($_FILES['import_orders_upload']['tmp_name']) && !empty($importContent)) {
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
                }
                if ($r->getParam('import_orders_textarea')) {
                    $content = trim($r->getParam('import_orders_textarea'));
                    $trackingNumbersContent = preg_split("/[\s,]+/", $content);
                    $this->trackigForImport($trackingNumbersContent);
                    Mage::getSingleton('core/session')->setData('tracking_numbers_content',$trackingNumbersContent);
                }
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
                if ($r->getParam('import_orders_default')) {
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
                    Mage::throwException($this->__('Errors during importing, please see individual batches for details'));
                }
                if(empty($importContent)){
                    Mage::throwException($this->__('Error, empty Import Content field'));
                }
                break;

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
                    Mage::throwException($this->__('Errors during importing, please see individual batches for details'));
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
                    Mage::throwException($this->__('Errors during importing, please see individual batches for details'));
                }
                break;

            default:
                Mage::throwException($this->__('Invalid batch type'));
        }
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
                Mage::throwException($_poHlp->__("Incorrectly input format"));
            }
            $currentOrderId = $currentRow[0];
            $currentTrackingNumber = $currentRow[1];

            foreach ($_udpos as $keyPo => $item) {
                $orderId = $item->getOrderIncrementId();
                if ($orderId == $currentOrderId) {
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
                    if (array_key_exists($vendor->getCarrierCode(), $carriers)) {
                        $carrier = $vendor->getCarrierCode();
                        $title = $carriers[$carrier];
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
                        }else{
                            //remove track number
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

    public function asTrackNumber($trackingNumber, $orderId){
        $api_key = Mage::app()->getWebsite(0)->getConfig('aftership_options/messages/api_key');
        $courier = new AfterShip\Couriers($api_key);
        $response = $courier->detect($trackingNumber);
        $data = $response['data'];
        $data['total']? $result = true: $result = false;
        if (!$result) {
            $track = Mage::getModel('track/track');
            $track->setTrackingNumber($trackingNumber);
            $track->setOrderId($orderId);
            $track->setErrorTracking('Not valid.');
            $track->save();
        }

        return $result;
    }
}