<?php

class TM_NewsletterBooster_Block_Adminhtml_Dashboard_Campaign extends Mage_Adminhtml_Block_Dashboard_Bar
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('newsletterbooster/dashboard/campaign_total.phtml');
    }

    protected function _prepareLayout()
    {
        $storeId = $this->getRequest()->getParam('store');
        $campaignId = $this->getRequest()->getParam('campaign');
        
        $campaignModel = Mage::getModel('newsletterbooster/campaign');
        $queueModel = Mage::getModel('newsletterbooster/queue'); 

        $totalCampaign = $campaignModel->getTotalCampaignCount($storeId);
        $totalQueue = $queueModel->getTotalQueueCount($storeId, $campaignId);

        $this->addTotal(Mage::helper('newsletterbooster')->__('Active campaigns'), $totalCampaign, true);
        $this->addTotal(Mage::helper('newsletterbooster')->__('Campaigns Sens'), $totalQueue, true);
    }
}
