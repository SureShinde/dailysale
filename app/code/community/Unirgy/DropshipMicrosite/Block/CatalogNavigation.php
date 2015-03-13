<?php

class Unirgy_DropshipMicrosite_Block_CatalogNavigation extends Mage_Catalog_Block_Navigation
{
    protected $_vendor;
    public function getVendor()
    {
        return $this->_vendor ? $this->_vendor : Mage::helper('umicrosite')->getCurrentVendor();
    }
    public function getCacheKeyInfo()
    {
        $cacheId = parent::getCacheKeyInfo();
        if ($this->getVendor()) {
            $cacheId['udropship_vendor'] = $this->getVendor()->getId();
        }
        return $cacheId;
    }
}
