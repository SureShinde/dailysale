<?php

class Unirgy_DropshipMicrosite_Block_Ga extends Mage_GoogleAnalytics_Block_Ga
{
    protected function _getPageTrackingCode($accountId)
    {
        $result = '';
        if ($this->isUdmsGaEnabled()) {
            $result .= parent::_getPageTrackingCode($this->getCurrentVendor()->getGoogleAnalyticsAccountId())."\n\n";
        }
        $result .= parent::_getPageTrackingCode($accountId);
        return $result;
    }
    public function getCurrentVendor()
    {
        return Mage::helper('umicrosite')->getCurrentVendor();
    }
    public function isUdmsGaEnabled()
    {
        return ($v = $this->getCurrentVendor()) && $v->getGoogleAnalyticsEnable() && $v->getGoogleAnalyticsAccountId();
    }
}