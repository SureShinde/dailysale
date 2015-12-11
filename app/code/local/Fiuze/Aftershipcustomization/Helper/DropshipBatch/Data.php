<?php

class Fiuze_Aftershipcustomization_Helper_DropshipBatch_Data extends Unirgy_DropshipBatch_Helper_Data {
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
                Mage::throwException($_poHlp->__("Track number " . $currentTrackingNumber . " has already been uploaded or is associated with another order."));
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
}