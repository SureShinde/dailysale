<?php

class TM_NewsletterBooster_Model_Mysql4_Queue_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected $_addSubscribersFlag   = false;
    
    protected function _construct()
    {
        $this->_map['fields']['queue_id'] = 'main_table.queue_id';
        $this->_init('newsletterbooster/queue');
    }
    
    public function addOnlyForSendingFilter()
    {
        $this->getSelect()
            ->where('main_table.queue_status in (?)', array(TM_NewsletterBooster_Model_Queue::STATUS_SENDING,
                                                            TM_NewsletterBooster_Model_Queue::STATUS_NEVER))
            ->where('main_table.queue_start_at < ?', Mage::getSingleton('core/date')->gmtdate())
            ->where('main_table.queue_start_at IS NOT NULL');

        return $this;
    }
    
    public function addStoresToSelect()
    {
        $this->getSelect()
            ->join(
                array('nbcs' => $this->getTable('newsletterbooster/store')), 
                'main_table.campaign_id = nbcs.campaign_id', 'nbcs.store_id')
            ->join(
                array('nbq' => $this->getTable('newsletterbooster/queue')), 
                'main_table.campaign_id = nbq.campaign_id', array('queue_count'=>'COUNT(nbq.queue_id)'))
        ;
                
        return $this;
    }
    
    protected function _afterLoad()
    {
        parent::_afterLoad();
        $segmentModel = Mage::getModel('segmentationsuite/index');
        $sendModel = Mage::getModel('newsletterbooster/send');
        foreach ($this->_items as $item) {
            $unserialized = unserialize($item->getCampaignSerialize());
            $customerTotal = $segmentModel->getCustomerCount($unserialized['tm_segment']);
            $customerSent = $sendModel->getCustomerSentCount($item->getQueueId());
            $item->addData(array('customer_sent' => $customerSent));
            $item->addData(array('customer_total' => $customerTotal));
            $item->addData($unserialized);
        }
        return $this;
    }
}
