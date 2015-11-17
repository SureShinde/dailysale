<?php

class Unirgy_Rma_Model_Rma_Track extends Mage_Sales_Model_Abstract
{
    const CUSTOM_CARRIER_CODE   = 'custom';
    protected $_rma = null;

    protected $_eventPrefix = 'urma_rma_track';
    protected $_eventObject = 'track';

    function _construct()
    {
        $this->_init('urma/rma_track');
    }
    public function setRma(Unirgy_Rma_Model_Rma $rma)
    {
        $this->_rma = $rma;
        return $this;
    }

    public function getShipment()
    {
        return $this->getRma();
    }

    public function getRma()
    {
        if (!($this->_rma instanceof Unirgy_Rma_Model_Rma)) {
            $this->_rma = Mage::getModel('urma/rma')->load($this->getParentId());
        }

        return $this->_rma;
    }

    public function isCustom()
    {
        return $this->getCarrierCode() == self::CUSTOM_CARRIER_CODE;
    }

    protected function _initOldFieldsMap()
    {
        $this->_oldFieldsMap = array(
            'number' => 'track_number'
        );
    }

    public function getNumber()
    {
        return $this->getData('track_number') ? $this->getData('track_number') : $this->getData('number');
    }

    public function getProtectCode()
    {
        return (string)$this->getRma()->getProtectCode();
    }
    
    public function getNumberDetail()
    {
        $carrierInstance = Mage::getSingleton('shipping/config')->getCarrierInstance($this->getCarrierCode());
        if (!$carrierInstance) {
            $custom['title'] = $this->getTitle();
            $custom['number'] = $this->getNumber();
            return $custom;
        } else {
            $carrierInstance->setStore($this->getStore());
        }

        if (!$trackingInfo = $carrierInstance->getTrackingInfo($this->getNumber())) {
            return Mage::helper('udropship')->__('No detail for number "%s"', $this->getNumber());
        }

        return $trackingInfo;
    }
    
    public function getStore()
    {
        if ($this->getRma()) {
            return $this->getRma()->getStore();
        }
        return Mage::app()->getStore();
    }

    protected function _beforeSave()
    {
        parent::_beforeSave();

        if (!$this->getParentId() && $this->getRma()) {
            $this->setParentId($this->getRma()->getId());
        }

        return $this;
    }
}
