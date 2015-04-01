<?php

class Unirgy_DropshipMicrosite_Model_Url extends Mage_Core_Model_Url
{
    public function getUrl($routePath = null, $routeParams = null)
    {
        $forceVendorUrl = !empty($routeParams['_current']);
        if ($forceVendorUrl) {
            Mage::app()->getStore()->useVendorUrl(true);
        }
        $url = parent::getUrl($routePath, $routeParams);
        if ($forceVendorUrl) {
            Mage::app()->getStore()->resetUseVendorUrl();
        }
        return $url;
    }
}