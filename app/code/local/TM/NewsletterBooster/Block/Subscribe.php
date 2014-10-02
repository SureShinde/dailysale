<?php
class TM_NewsletterBooster_Block_Subscribe extends Mage_Core_Block_Template
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('newsletterbooster/subscribe.phtml');
    }
    
    public function getCampaignsForSubscribe()
    {
        $campaign = Mage::getModel('newsletterbooster/campaign');
        
        return $campaign->getFrontendCampaigns();
    }
    
    public function getFormActionUrl()
    {
        return Mage::getUrl('newsletterbooster/subscribe/index');
    }
}
