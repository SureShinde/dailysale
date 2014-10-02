<?php
class TM_NewsletterBooster_Model_Send extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('newsletterbooster/send');
    }
    
    public function getCustomerSentCount($queueId)
    {
        $result = $this->getResource()->getCustomerSentCount($queueId);
        
        return $result[0];
    }
}