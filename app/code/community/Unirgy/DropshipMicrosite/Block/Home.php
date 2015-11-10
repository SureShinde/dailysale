<?php

class Unirgy_DropshipMicrosite_Block_Home extends Mage_Core_Block_Template
{
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if ($breadcrumbsBlock = $this->getLayout()->getBlock('breadcrumbs')) {
            $breadcrumbsBlock->addCrumb('home', array(
                'label'=>Mage::helper('udropship')->__('Home'),
                'title'=>Mage::helper('udropship')->__('Go to Home Page'),
                'link'=>Mage::getBaseUrl()
            ));
            if ($vendor = Mage::helper('umicrosite')->getCurrentVendor()) {
                $breadcrumbsBlock->addCrumb('vendormicrosite', array(
                    'label'=>$vendor->getVendorName(),
                    'title'=>Mage::helper('umicrosite')->getLandingPageTitle($vendor),
                ));
            }
        }
    }
}