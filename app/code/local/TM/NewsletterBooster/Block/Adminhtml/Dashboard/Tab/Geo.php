<?php

class TM_NewsletterBooster_Block_Adminhtml_Dashboard_Tab_Geo
    extends Mage_Adminhtml_Block_Template
{
    protected function _construct()
    {
        parent::_construct();
        $this->setCampaign($this->getRequest()->getParam('campaign'));
        $this->setTemplate('newsletterbooster/dashboard/charts/geo.phtml');
    }

    public function CampaignSelected()
    {
        if ($this->getRequest()->getParam('campaign')) {
            return true;
        }
        return false;
    }

    public function getCountryData()
    {
        $queueModel = Mage::getModel('newsletterbooster/queue');

        $country = $queueModel->getCampaignCountryOpensData($this->getCampaign());

        return $country;
    }

    public function getRegionData()
    {
        $queueModel = Mage::getModel('newsletterbooster/queue');
        $city = $queueModel->getCampaignRegionOpensData($this->getCampaign());

        return $city;
    }
}
