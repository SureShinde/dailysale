<?php
class TM_NewsletterBooster_Model_Unsubscribe extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('newsletterbooster/unsubscribe');
    }
    
    public function unsubscribeExist($campaignId, $queueId, $customerId, $email)
    {
        $result = $this->getResource()->unsubscribeExist($campaignId, $queueId, $customerId, $email);
        if ($result[0] > 0) {
            return true;
        }
        
        return false;
    }
    
    public function customerExist($customerId, $email)
    {
        $result = $this->getResource()->customerExist($customerId, $email);
        if ($result[0] > 0) {
            return true;
        }
        
        return false;
    }
    
    public function deleteSubscribeRecord($campaignId, $customerId, $email)
    {
        $this->getResource()->deleteSubscribeRecord($campaignId, $customerId, $email);
    }
}