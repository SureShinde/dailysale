<?php

class TM_NewsletterBooster_Model_Mysql4_Send extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        $this->_init('newsletterbooster/send', 'queue_send_id');
    }
    
    public function getCustomerSentCount($id)
    {
        return $this->_getReadAdapter()->fetchCol($this->_getReadAdapter()->select()
            ->from($this->getTable('newsletterbooster/send'), array('total' => 'COUNT(queue_id)'))
            ->where('queue_id = ?', $id)
        );
    }
}