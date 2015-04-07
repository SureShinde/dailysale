<?php

class Unirgy_Rma_Model_ShippingInfo extends Mage_Shipping_Model_Info
{
    public function loadByHash($hash)
    {
        $data = Mage::helper('urma/shipping')->decodeTrackingHash($hash);
        if (!empty($data)) {
            $this->setData($data['key'], $data['id']);
            $this->setProtectCode($data['hash']);

            if ($this->getRmaId()>0) {
                $this->getTrackingInfoByRma();
            } elseif ($this->getRmaTrackId()>0) {
                $this->getTrackingInfoByRmaTrackId();
            } elseif ($this->getUstockpoId()>0) {
                $this->getTrackingInfoByUstockpo();
            } elseif ($this->getUstockpoTrackId()>0) {
                $this->getTrackingInfoByUstockpoTrackId();
            } elseif ($this->getOrderId()>0) {
                $this->getTrackingInfoByOrder();
            } elseif($this->getShipId()>0) {
                $this->getTrackingInfoByShip();
            } else {
                $this->getTrackingInfoByTrackId();
            }
        }
        return $this;
    }

    public function getTrackingInfoByRmaTrackId()
    {
        $track = Mage::getModel('urma/rma_track')->load($this->getRmaTrackId());
        if ($track->getId() && $this->getProtectCode() == $track->getProtectCode()) {
            $this->_trackingInfo = array(array($track->getNumberDetail()));
        }
        return $this->_trackingInfo;
    }
    public function getTrackingInfoByRma()
    {
        $shipTrack = array();
        $po = $this->_initRma();
        if ($po) {
            $increment_id = $po->getIncrementId();
            $tracks = $po->getTracksCollection();

            $trackingInfos=array();
            foreach ($tracks as $track){
                $trackingInfos[] = $track->getNumberDetail();
            }
            $shipTrack[$increment_id] = $trackingInfos;

        }
        $this->_trackingInfo = $shipTrack;
        return $this->_trackingInfo;
    }
    protected function _initRma()
    {
        $model = Mage::getModel('urma/rma');
        $po = $model->load($this->getRmaId());
        if (!$po->getEntityId() || $this->getProtectCode() != $po->getProtectCode()) {
            return false;
        }
        return $po;
    }

    public function getTrackingInfoByUstockpoTrackId()
    {
        $track = Mage::getModel('ustockpo/po_track')->load($this->getUstockpoTrackId());
        if ($track->getId() && $this->getProtectCode() == $track->getProtectCode()) {
            $this->_trackingInfo = array(array($track->getNumberDetail()));
        }
        return $this->_trackingInfo;
    }
    public function getTrackingInfoByUstockpo()
    {
        $shipTrack = array();
        $po = $this->_initUstockpo();
        if ($po) {
            $increment_id = $po->getIncrementId();
            $tracks = $po->getTracksCollection();

            $trackingInfos=array();
            foreach ($tracks as $track){
                $trackingInfos[] = $track->getNumberDetail();
            }
            $shipTrack[$increment_id] = $trackingInfos;

        }
        $this->_trackingInfo = $shipTrack;
        return $this->_trackingInfo;
    }
    protected function _initUstockpo()
    {
        $model = Mage::getModel('ustockpo/po');
        $po = $model->load($this->getUstockpoId());
        if (!$po->getEntityId() || $this->getProtectCode() != $po->getProtectCode()) {
            return false;
        }
        return $po;
    }
}
