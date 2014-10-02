<?php
class TM_NewsletterBooster_Model_Trackopen extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('newsletterbooster/trackopen');
    }
    
    public function openExist($queueId, $customerId)
    {
        $result = $this->getResource()->openExist($queueId, $customerId);
        if ($result[0] > 0) {
            return true;
        }
        
        return false;
    }

    public function getOpensCustomerIds($queueId)
    {
        return $this->getResource()->getOpensCustomerIds($queueId);
    }
}