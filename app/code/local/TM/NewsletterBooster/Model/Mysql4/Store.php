<?php

class TM_NewsletterBooster_Model_Mysql4_Store extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        $this->_init('newsletterbooster/store', 'campaign_store_id');
    }
    
    public function deleteCampaignStoreIds($campaignId)
    {
        $write = $this->_getWriteAdapter();
        $write->beginTransaction();
        $write->delete($this->getTable('store'), $write->quoteInto('campaign_id=?', $campaignId));
        $write->commit();
        return $this;
    }
    
    public function lookupStoreIds($campaignId) {
        return $this->_getReadAdapter()
            ->fetchAll($this->_getReadAdapter()->select()
                ->from($this -> getTable('store'), 'store_id') -> where('campaign_id = ?', $campaignId));
    }
}