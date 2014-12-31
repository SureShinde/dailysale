<?php

class TM_NewsletterBooster_Model_Subscriber extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('newsletterbooster/subscriber');
    }
    
    public function subscribeExist($campaignId, $entityId, $email)
    {
        return $this->getResource()->subscribeExist($campaignId, $entityId, $email);
    }
    
    public function getCustomerSubCampaigns($entityId)
    {
        return $this->getResource()->getCustomerSubCampaigns($entityId);   
    }
    
    public function deleteCustomerSubscribe($entityId)
    {
        return $this->getResource()->deleteCustomerSubscribe($entityId);   
    }
    
    public function deleteSubscribeRecord($campaignId, $email, $customerId)
    {
        return $this->getResource()->deleteSubscribeRecord($campaignId, $email, $customerId);   
    }
    
    public function getSubscriberId($campaignId, $email)
    {
        return $this->getResource()->getSubscriberId($campaignId, $email);   
    }

    public function getEntityForSend($count, $offset, $campaignId)
    {
        return $this->getResource()->getEntityForSend($count, $offset, $campaignId);   
    }
}