<?php

class Unirgy_DropshipMicrosite_Block_PageTopmenu extends Mage_Page_Block_Html_Topmenu
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
