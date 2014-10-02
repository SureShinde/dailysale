<?php

class TM_NewsletterBooster_Model_Mysql4_Unsubscribe extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        $this->_init('newsletterbooster/unsubscribe', 'id');
    }
    
    public function unsubscribeExist($campaignId, $queueId, $customerId, $email)
    {
        if (null == $customerId) {
            return $this->_getReadAdapter()->fetchCol($this->_getReadAdapter()->select()
                ->from($this->getTable('newsletterbooster/unsubscribe'), array('total' => 'COUNT(id)'))
                ->where('campaign_id = ?', $campaignId)
                ->where('email = ?', $email)
            );
        } else {
            return $this->_getReadAdapter()->fetchCol($this->_getReadAdapter()->select()
                ->from($this->getTable('newsletterbooster/unsubscribe'), array('total' => 'COUNT(id)'))
                ->where('campaign_id = ?', $campaignId)
                ->where('entity_id = ?', $customerId)
            );
        }
        
    }
    
    public function customerExist($customerId, $email)
    {
        if (null === $customerId) {
            return true;
        } else {
            return $this->_getReadAdapter()->fetchCol($this->_getReadAdapter()->select()
                ->from($this->getTable('customer/entity'), array('total' => 'COUNT(entity_id)'))
                ->where('entity_id = ?', $customerId)
                ->where('email = ?', $email)
            );
        }
    }
}