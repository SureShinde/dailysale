<?php
class TM_NewsletterBooster_Model_Trackclick extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('newsletterbooster/trackclick');
    }
    
    public function clickExist($queueId, $customerId)
    {
        $result = $this->getResource()->clickExist($queueId, $customerId);
        if ($result[0] > 0) {
            return true;
        }
        
        return false;
    }

    public function getClicksCustomerIds($queueId)
    {
        return $this->getResource()->getClicksCustomerIds($queueId);
    }

}