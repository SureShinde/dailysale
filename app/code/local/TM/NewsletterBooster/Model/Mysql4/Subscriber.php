<?php

class TM_NewsletterBooster_Model_Mysql4_Subscriber extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        $this->_init('newsletterbooster/subscriber', 'subscriber_id');
    }
    
    public function subscribeExist($campaignId, $entityId, $email)
    {
        if (null == $entityId) {
            $select = $this->_getReadAdapter()->select()
                ->from($this->getTable('subscriber'))
                ->where('campaign_id = ?', $campaignId)
                ->where('email = ?', $email);
        } else {
            $select = $this->_getReadAdapter()->select()
                ->from($this->getTable('subscriber'))
                ->where('campaign_id = ?', $campaignId)
                ->where('entity_id = ?', $entityId)
                ->where('email = ?', $email);            
        }
            
        if (count($this->_getReadAdapter()->fetchAll($select)) > 0) {
            return true;
        }
        return false;
    }
    
    public function getCustomerSubCampaigns($entityId)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('subscriber'),'campaign_id')
            ->where('entity_id = ?', $entityId);

        return $this->_getReadAdapter()->fetchCol($select);
    }
    
    public function getSubscriberId($campaignId, $email)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('subscriber'),'subscriber_id')
            ->where('campaign_id = ?', $campaignId)
            ->where('email = ?', $email);

        return $this->_getReadAdapter()->fetchCol($select);
    }

    public function getEntityForSend($count, $offset, $campaignId)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('subscriber'))
            ->where('campaign_id = ?', $campaignId)
            ->limit($count, $offset);

        return $this->_getReadAdapter()->fetchAll($select);
    }
    
    public function deleteCustomerSubscribe($entityId)
    {
        $write = $this->_getWriteAdapter();
        $write->beginTransaction();
        $write->delete($this->getTable('newsletterbooster/subscriber'), $write->quoteInto('entity_id=?', $entityId));
        $write->commit();
        
        return $this;
    }
    
    public function deleteSubscribeRecord($campaignId, $email, $customerId)
    {
        $subId = $this->getSubId($campaignId, $email, $customerId);
        $write = $this->_getWriteAdapter();
        $write->beginTransaction();
        $write->delete($this->getTable('newsletterbooster/subscriber'), $write->quoteInto('subscriber_id=?', $subId));
        $write->commit();
        
        return $this;
    }
    
    public function getSubId($campaignId, $email, $customerId)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('subscriber'),'subscriber_id')
            ->where('campaign_id = ?', $campaignId)
            ->where('email = ?', $email);
        if (null !== $customerId) {
            $select->where('entity_id = ?', $customerId);
        }
        $result = $this->_getReadAdapter()->fetchCol($select);
        
        return $result[0];
    }

    public function getItemsToProcess($count = 1, $step = 0) {
        $connection = $this -> _getReadAdapter();

        $labelSelect = $connection->select()->from(
            array('cp' => $this -> getTable('newsletter/subscriber')))
            ->where('subscriber_status = 1')
            ->where('customer_id = 0')
            ->order('subscriber_id')->limit($count, $count * $step);

        $result = $connection -> fetchAll($labelSelect);

        return $result;
    }
}