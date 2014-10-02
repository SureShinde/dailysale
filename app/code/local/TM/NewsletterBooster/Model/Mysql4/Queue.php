<?php

class TM_NewsletterBooster_Model_Mysql4_Queue extends Mage_Newsletter_Model_Resource_Queue
{
    protected function _construct()
    {
        $this->_init('newsletterbooster/queue', 'queue_id');
    }

    public function getTotalQueueCount($store, $campaignId)
    {
        if (null !== $campaignId) {
            $select = $this->_getReadAdapter()->select()
                ->from($this->getMainTable())
                ->where('campaign_id = ?', $campaignId);
        } else {
            $select = $this->_getReadAdapter()->select()
                ->from($this->getMainTable());
        }

        $count = 0;
        foreach ($this->_getReadAdapter()->fetchAll($select) as $row) {
            if (null === $row['queue_finish_at']) {
                continue;
            }

            if (null === $store) {
                $count++;
            } else {
                if ($this->checkCampaignStore($row['campaign_id'], $store)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    public function getOpensCount($queueId)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('newsletterbooster/trackopen', 'open_id'))
            ->where('queue_id = ?', $queueId);
        $result = $this->_getReadAdapter()->fetchCol($select);

        return count($result);
    }

    public function getCampaignQueueIds($campaignId)
    {
        $queueSelect = $this->_getReadAdapter()->select()
            ->from($this->getTable('newsletterbooster/queue'),
                array('queue_id'))
            ->where('campaign_id = ?', $campaignId);

        $queueIds = $this->_getReadAdapter()->fetchCol($queueSelect);

        return $queueIds;
    }

    public function getCampaignOpensCount($campaignId)
    {
        $queueIds = $this->getCampaignQueueIds($campaignId);

        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('newsletterbooster/trackopen', 'open_id'))
            ->where('queue_id IN (?)', $queueIds);
        $result = $this->_getReadAdapter()->fetchCol($select);

        return count($result);
    }

    public function getCampaignClicksCount($campaignId)
    {
        $queueIds = $this->getCampaignQueueIds($campaignId);

        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('newsletterbooster/trackclick', 'click_id'))
            ->where('queue_id IN (?)', $queueIds);
        $result = $this->_getReadAdapter()->fetchCol($select);

        return count($result);
    }

    public function getCampaignUnsubscribeCount($campaignId)
    {
        $queueIds = $this->getCampaignQueueIds($campaignId);

        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('newsletterbooster/unsubscribe', 'id'))
            ->where('queue_id IN (?)', $queueIds);
        $result = $this->_getReadAdapter()->fetchCol($select);

        return count($result);
    }

    public function getClicksCount($queueId)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('newsletterbooster/trackclick', 'click_id'))
            ->where('queue_id = ?', $queueId);
        $result = $this->_getReadAdapter()->fetchCol($select);

        return count($result);
    }

    public function getUnsubscribeCount($queueId)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('newsletterbooster/unsubscribe', 'id'))
            ->where('queue_id = ?', $queueId);
        $result = $this->_getReadAdapter()->fetchCol($select);

        return count($result);
    }

    public function checkCampaignStore($campaignId, $storeId)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('newsletterbooster/store'))
            ->where('campaign_id = ?', $campaignId)
            ->where('store_id = ?', $storeId);

        if (count($this->_getReadAdapter()->fetchAll($select)) > 0) {
            return true;
        }
        return false;
    }

    public function getCountryOpensData($queueId)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('newsletterbooster/trackclick'),
                array('country_name', 'COUNT(click_id)'))
            ->where('queue_id = ?', $queueId)
            ->where('country_name IS NOT NULL')
            ->group('country_name')
            ;

        $result = $this->_getReadAdapter()->fetchPairs($select);

        return $result;
    }

    public function getCampaignCountryOpensData($campaignId)
    {
        $queueSelect = $this->_getReadAdapter()->select()
            ->from($this->getTable('newsletterbooster/queue'),
                array('queue_id'))
            ->where('campaign_id = ?', $campaignId);
        $queueIds = $this->_getReadAdapter()->fetchCol($queueSelect);

        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('newsletterbooster/trackclick'),
                array('country_name', 'COUNT(click_id)'))
            ->where('queue_id IN (?)', $queueIds)
            ->where('country_name IS NOT NULL')
            ->group('country_name')
            ;

        $result = $this->_getReadAdapter()->fetchPairs($select);

        return $result;
    }

    public function getRegionOpensData($queueId)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('newsletterbooster/trackclick'),
                array('country_code', 'city', 'opens' => 'COUNT(click_id)'))
            ->where('queue_id = ?', $queueId)
            ->where('country_code IS NOT NULL')
            ->where('city IS NOT NULL')
            ->group(array('country_code','city'))
            ;

        $result = $this->_getReadAdapter()->fetchAll($select);

        return $result;
    }

    public function getCampaignRegionOpensData($campaignId)
    {
        $queueIds = $this->getCampaignQueueIds($campaignId);

        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('newsletterbooster/trackclick'),
                array('country_code', 'city', 'opens' => 'COUNT(click_id)'))
            ->where('queue_id IN (?)', $queueIds)
            ->where('country_code IS NOT NULL')
            ->where('city IS NOT NULL')
            ->group(array('country_code','city'))
            ;

        $result = $this->_getReadAdapter()->fetchAll($select);

        return $result;
    }

    public function getCampaignRecipientsCount($campaignId)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('newsletterbooster/queue'),
                array('resipients' => 'SUM(recipients)'))
            ->where('campaign_id = ?', $campaignId)
            ;

        $result = $this->_getReadAdapter()->fetchCol($select);

        return $result[0];
    }

    public function getCampaignQueueProcessed($campaignId)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('newsletterbooster/queue'),
                array('resipients' => 'SUM(processed)'))
            ->where('campaign_id = ?', $campaignId)
            ;

        $result = $this->_getReadAdapter()->fetchCol($select);

        return $result[0];
    }

    public function getCampaignQueueErrors($campaignId)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('newsletterbooster/queue'),
                array('resipients' => 'SUM(errors)'))
            ->where('campaign_id = ?', $campaignId)
            ;

        $result = $this->_getReadAdapter()->fetchCol($select);

        return $result[0];
    }

    public function getSendedQueue($campaignId)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('newsletterbooster/queue'),
                array('queue' => 'COUNT(queue_id)'))
            ->where('campaign_id = ?', $campaignId)
            ;

        $result = $this->_getReadAdapter()->fetchCol($select);

        return $result[0];
    }
}
