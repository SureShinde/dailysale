<?php

class TM_SegmentationSuite_Model_Index extends Mage_Catalog_Model_Abstract
{
    protected function _construct()
    {
        parent::_construct();
        $this->_init('segmentationsuite/index');
    }
    
    public function deleteDisableIndex($rulesId)
    {
        $this->getResource()->deleteIndexs($rulesId);
    }
    
    public function getSegmentCustomerIds($segmentId)
    {
        return $this->getResource()->getSegmentCustomerIds($segmentId);
    }
    
    public function getCustomerCount($segmentId)
    {
        $result = $this->getResource()->getCustomerCount($segmentId);
        
        return $result[0];
    }
    
    public function getRecipientsCount($segmentId, $campaignId, $queueId, $guest = false)
    {
        return $this->getResource()->getRecipientsCount($segmentId, $campaignId, $queueId, $guest);
    }
    
    public function getEmailsToSend($segmentId, $queueId, $count, $offset, $campaignId)
    {
        return $this->getResource()->getEmailsToSend($segmentId, $queueId, $count, $offset, $campaignId);
    }
}
