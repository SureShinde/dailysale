<?php
class TM_NewsletterBooster_Model_Store extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('newsletterbooster/store');
    }
    
    public function getStoreIds($id)
    {
        return $this->_getResource()->lookupStoreIds($id);
    }
    
    public function deleteCampaignStoreIds($campaignId)
    {
        $this->getResource()->deleteCampaignStoreIds($campaignId);
    }
}