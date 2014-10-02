<?php

class TM_NewsletterBooster_Model_Mysql4_Trackopen extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        $this->_init('newsletterbooster/trackopen', 'open_id');
    }
    
    public function openExist($queueId, $customerId)
    {
        return $this->_getReadAdapter()->fetchCol($this->_getReadAdapter()->select()
            ->from($this->getTable('newsletterbooster/trackopen'), array('total' => 'COUNT(open_id)'))
            ->where('queue_id = ?', $queueId)
            ->where('entity_id = ?', $customerId)
        );
    }

    public function getOpensCustomerIds($queueId)
    {
        return $this->_getReadAdapter()->fetchCol($this->_getReadAdapter()->select()
                ->from($this->getTable('newsletterbooster/trackopen'), array('total' => 'entity_id'))
                ->where('queue_id = ?', $queueId)
        );
    }
}