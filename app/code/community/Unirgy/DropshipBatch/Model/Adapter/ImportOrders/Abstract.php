<?php

abstract class Unirgy_DropshipBatch_Model_Adapter_ImportOrders_Abstract extends Varien_Object
{
    abstract public function init();
    abstract public function parse($content);
    abstract public function process($rows);
    abstract public function getImportFields();

    public function import($content)
    {
        $this->init();
        $rows = $this->parse($content);
        $this->process($rows);
        return $this;
    }
    
    public function getVendor()
    {
        return $this->getBatch()->getVendor();
    }

    public function getVendorId()
    {
        return $this->getVendor()->getId();
    }
}