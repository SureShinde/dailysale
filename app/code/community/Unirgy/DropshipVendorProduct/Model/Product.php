<?php

class Unirgy_DropshipVendorProduct_Model_Product extends Mage_Catalog_Model_Product
{
    protected function _construct()
    {
        $this->_init('udprod/product');
    }
    public function resetTypeInstance()
    {
        $this->_typeInstanceSingleton = null;
        $this->_typeInstance = null;
        return $this;
    }
    protected function _beforeSave()
    {
        $ufName = $this->formatUrlKey($this->getName());
        if (!trim($ufName)) {
            Mage::throwException(Mage::helper('udropship')->__('Product name is invalid'));
        }
        return parent::_beforeSave();
    }
    public function uclearOptions()
    {
        $this->getOptionInstance()->unsetOptions();
        $this->_options = array();
        return $this;
    }
}