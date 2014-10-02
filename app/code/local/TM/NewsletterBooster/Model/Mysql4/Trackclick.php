<?php

class TM_NewsletterBooster_Model_Mysql4_Trackclick extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        $this->_init('newsletterbooster/trackclick', 'click_id');
    }
    
    public function clickExist($queueId, $customerId)
    {
        return $this->_getReadAdapter()->fetchCol($this->_getReadAdapter()->select()
            ->from($this->getTable('newsletterbooster/trackclick'), array('total' => 'COUNT(click_id)'))
            ->where('queue_id = ?', $queueId)
            ->where('entity_id = ?', $customerId)
        );
    }

    public function getClicksCustomerIds($queueId)
    {
        return $this->_getReadAdapter()->fetchCol($this->_getReadAdapter()->select()
                ->from($this->getTable('newsletterbooster/trackclick'), array('total' => 'entity_id'))
                ->where('queue_id = ?', $queueId)
        );
    }
}