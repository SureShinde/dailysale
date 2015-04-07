<?php

class Unirgy_Rma_Model_Rma_Comment extends Mage_Sales_Model_Abstract
{
    protected $_eventPrefix = 'urma_rma_comment';
    protected $_eventObject = 'rma_comment';
    
    protected $_rma;

    protected function _construct()
    {
        $this->_init('urma/rma_comment');
    }

    public function setRma(Unirgy_Rma_Model_Rma $rma)
    {
        $this->_rma = $rma;
        return $this;
    }

    public function getRma()
    {
        return $this->_rma;
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
