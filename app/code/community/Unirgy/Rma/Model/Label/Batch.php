<?php

class Unirgy_Rma_Model_Label_Batch extends Unirgy_Dropship_Model_Label_Batch
{
    public function processRmas($rmas, $trackData = array(), $flags = array())
    {
        if (!$this->getBatchId()) {
            $this->setCreatedAt(now());
            $this->setVendorId($this->getVendor()->getId());
            $this->setLabelType($this->getVendor()->getLabelType());
            $this->save();
        }

        $labelModels = array();

        $success = 0;
        $fromOrderId = null;
        $toOrderId = null;

        if (isset($trackData['weight']) && is_array($trackData['weight'])) {
            unset($trackData['weight']['$ROW']);
            $pkgLength = $pkgHeight = $pkgWidth = $pkgValue = $pkgWeight = array();
            $pcIdx=1; foreach ($trackData['weight'] as $wIdx=>$w) {
                $pkgLength[$pcIdx] = @$trackData['length'][$wIdx];
                $pkgHeight[$pcIdx] = @$trackData['height'][$wIdx];
                $pkgWidth[$pcIdx]  = @$trackData['width'][$wIdx];
                $pkgValue[$pcIdx]  = @$trackData['value'][$wIdx];
                $pkgWeight[$pcIdx] = @$trackData['weight'][$wIdx];
                $pcIdx++;
            }
            $totalWeight = array_sum($pkgWeight);
            $trackData['package_count'] = count($pkgWeight);
        }

        $transactionSave = true;
        foreach ($rmas as $rma) {
            $storeId = $rma->getOrder()->getStoreId();

            Mage::helper('urma')->beforeRmaLabel($this->getVendor(), $rma);

            $sItemIds = array();
            foreach ($rma->getAllItems() as $sItem) {
                $sItemIds[$sItem->getId()] = array('item' => $sItem);
            }

            try {
                $method = explode('_', $rma->getUdropshipMethod(), 2);
                $carrierCode = !empty($method[0]) ? $method[0] : $this->getVendor()->getCarrierCode();

                $packageCount = $carrierCode == 'fedex' && isset($trackData['package_count']) && is_numeric($trackData['package_count'])
                    ? $trackData['package_count']
                    : 1;

                if (empty($labelModels[$carrierCode])) {
                    $labelModels[$carrierCode] = Mage::helper('udropship')->getLabelCarrierInstance($carrierCode)
                        ->setBatch($this)
                        ->setVendor($this->getVendor());
                }
                $labelModels[$carrierCode]->setUdropshipPackageCount($packageCount);

                $mpsRequests = array();
                if (Mage::helper('udropship')->isUdpoMpsAvailable($carrierCode)) {
                    unset($trackData['weight']);
                    unset($trackData['value']);
                    unset($trackData['package_count']);
                    foreach ($rma->getAllItems() as $sItem) {
                        if ($sItem->getOrderItem()->getUdpompsShiptype() == Unirgy_DropshipPoMps_Model_Source::SHIPTYPE_ROW_SEPARATE) {
                            $mpsRequests[] = array(
                                'items' => array($sItem->getId() => array('item' => $sItem))
                            );
                            unset($sItemIds[$sItem->getId()]);
                        } elseif ($sItem->getOrderItem()->getUdpompsShiptype() == Unirgy_DropshipPoMps_Model_Source::SHIPTYPE_ITEM_SEPARATE) {
                            for ($i=1; $i<=$sItem->getQty(); $i++) {
                                $splitWeight = $sItem->getOrderItem()->getUdpompsSplitWeight();
                                if (!is_array($splitWeight)) {
                                    $splitWeight = Mage::helper('core')->jsonDecode($splitWeight);
                                }
                                if (!empty($splitWeight) && is_array($splitWeight)) {
                                    foreach ($splitWeight as $_sWeight) {
                                        $mpsRequests[] = array(
                                            'items' => array($sItem->getId() => array(
                                                'item' => $sItem,
                                                'qty' => 1,
                                                'weight' => !empty($_sWeight['weight']) ? $_sWeight['weight'] : $sItem->getWeight()
                                            )),
                                        );
                                    }
                                } else {
                                    $mpsRequests[] = array(
                                        'items' => array($sItem->getId() => array('item' => $sItem)),
                                    );
                                }
                            }
                            unset($sItemIds[$sItem->getId()]);
                        }
                    }
                    if (!empty($sItemIds)) {
                        $mpsRequests[] = array(
                            'items' => $sItemIds
                        );
                    }
                }
                if (empty($mpsRequests)) {
                    $sItemIds = array();
                    foreach ($rma->getAllItems() as $sItem) {
                        $sItemIds[$sItem->getId()] = array('item' => $sItem);
                    }
                    for ($pcIdx=1; $pcIdx<=$packageCount; $pcIdx++) {
                        $mpsRequests[] = array(
                            'items' => $sItemIds
                        );
                    }
                }

                $labelModels[$carrierCode]->setUdropshipPackageCount(count($mpsRequests));

                $newTracks = array();

                for ($pcIdx=1; $pcIdx<=count($mpsRequests); $pcIdx++) {

                    $labelModels[$carrierCode]->setMpsRequest($mpsRequests[$pcIdx-1]);

                    $track = Mage::getModel('urma/rma_track')
                        ->setBatchId($this->getBatchId());
                    if (!empty($trackData)) {
                        if (isset($pkgWeight)) {
                            $trackData['total_weight'] = $totalWeight;
                            $trackData['length'] = $pkgLength[$pcIdx];
                            $trackData['height'] = $pkgHeight[$pcIdx];
                            $trackData['width']  = $pkgWidth[$pcIdx];
                            $trackData['value']  = $pkgValue[$pcIdx];
                            $trackData['weight'] = $pkgWeight[$pcIdx];
                        }
                        $track->addData($trackData);
                    }
                    $rma->addTrack($track);

                    $labelModels[$carrierCode]->setUdropshipPackageIdx($pcIdx);
                    $labelModels[$carrierCode]->requestRma($track);

                    $newTracks[] = $track;

                    $success++;

                }
                $labelModels[$carrierCode]->unsUdropshipPackageIdx();
                $labelModels[$carrierCode]->unsUdropshipPackageCount();
                $labelModels[$carrierCode]->unsUdropshipMasterTrackingId();
            } catch (Exception $e) {
                Mage::dispatchEvent('udropship_rma_label_request_failed', array('rma'=>$rma, 'error'=>$e->getMessage()));
                $this->addError($e->getMessage().' - %s order(s)');
                continue;
            }

            $orderId = $rma->getOrder() ? $rma->getOrder()->getIncrementId() : $rma->getOrderIncrementId();
            if (is_null($fromOrderId)) {
                $fromOrderId = $orderId;
                $toOrderId = $orderId;
            } else {
                $fromOrderId = min($fromOrderId, $orderId);
                $toOrderId = max($toOrderId, $orderId);
            }

            Mage::helper('urma')->afterRmaLabel($this->getVendor(), $rma);

        }
#exit;
        $this->setTitle('Orders IDs: '.$fromOrderId.' - '.$toOrderId);
        $this->setShipmentCnt($this->getShipmentCnt()+$success);

        if (!empty($track)) {
            $this->setLastTrack($track);
        }

        if (!$this->getShipmentCnt()) {
            $this->delete();
        } else {
            $this->save();
        }

        return $this;
    }

    public function renderRmas($rmas)
    {
        $tracks = array();
        foreach ($rmas as $rma) {
            foreach ($rma->getAllTracks() as $track) {
                $tracks[] = $track;
            }
        }
        $this->setTracks($tracks);
        $this->setVendorId($this->getVendor()->getId());
        $this->setLabelType($this->getVendor()->getLabelType());
        return $this;
    }

}
