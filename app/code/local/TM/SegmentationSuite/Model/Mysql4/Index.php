<?php

class TM_SegmentationSuite_Model_Mysql4_Index extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('segmentationsuite/index', 'id');
    }

    public function deleteIndexs($ruleId)
    {
        $write = $this->_getWriteAdapter();
        $write->beginTransaction();
        $write->delete(
            $this->getTable('segmentationsuite/index'),
            $write->quoteInto('segment_id=?', $ruleId)
        );
        $write->commit();
        return $this;
    }

    public function getSegmentCustomerIds($id)
    {
        return $this->_getReadAdapter()->fetchCol($this->_getReadAdapter()->select()
            ->from($this->getTable('segmentationsuite/index'), 'entity_id')
            ->where('segment_id = ?', $id)
        );
    }

    public function getCustomerCount($id)
    {
        return $this->_getReadAdapter()->fetchCol($this->_getReadAdapter()->select()
            ->from($this->getTable('segmentationsuite/index'), array('total' => 'COUNT(entity_id)'))
            ->where('segment_id = ?', $id)
        );
    }

    public function getRecipientsCount($segmentId, $campaignId, $queueId, $guest)
    {
        if (is_array($this->getUnsubscribeCustomers($campaignId, $queueId))) {
            $unsubscripers = array_values($this->getUnsubscribeCustomers($campaignId, $queueId));
        } else {
            $unsubscripers = null;
        }

        $result = $this->_getReadAdapter()->fetchCol($this->_getReadAdapter()->select()
            ->from($this->getTable('segmentationsuite/index'), array('total' => 'COUNT(entity_id)'))
            ->where('segment_id = ?', $segmentId));

        if (null == $unsubscripers) {
            $unsubscripers = 0;
        } else {
            $unsubscripers = count($unsubscripers);
        }
        if (!$guest) {
            $subscribers = 0;
        } else {
            $subscribers = $this->getSubscribedCount($campaignId);
        }

        return $result[0] - $unsubscripers + $subscribers;

    }

    public function getSubscribedCount($campaignId)
    {
        $getSubscriberIds = $this->_getReadAdapter()->select()
            ->from(array('ssi' => $this->getTable('newsletterbooster/subscriber')), 'subscriber_id')
            ->where('ssi.campaign_id = ?', $campaignId);

        $result = $this->_getReadAdapter()->fetchCol($getSubscriberIds);

        return count($result);
    }

    public function getEmailsToSend($segmentId, $queueId, $count, $offset, $campaignId)
    {
        if (is_array($this->getUnsubscribeCustomers($campaignId, $queueId))) {
            $unsubscribers = array_values($this->getUnsubscribeCustomers($campaignId, $queueId));
        } else {
            $unsubscribers = null;
        }

        $res = array();
        if (null !== $unsubscribers) {
            $res = array();
            foreach ($unsubscribers as $value) {
                $res[] = $value;
            }
        }

        $getCustomerIds = $this->_getReadAdapter()->select()
            ->from(array('ssi' => $this->getTable('segmentationsuite/index')), 'entity_id')
            ->where('ssi.segment_id in (?)', $segmentId)
            ->order('id')
            ->limit($count, $offset);

        if (count($res) > 0) {
            $getCustomerIds->where('ssi.entity_id not in(?)', $res);
        }

        $customerIds = $this->_getReadAdapter()->fetchCol($getCustomerIds);

        return $customerIds;
    }

    public function getUnsubscribeCustomers($campaignId, $queueId)
    {
        $getQueueStartDate = $this->_getReadAdapter()->select()
            ->from(array('nbq' => $this->getTable('newsletterbooster/queue')), 'queue_start_at')
            ->where('nbq.queue_id = ?', $queueId);

        $dateResult = $this->_getReadAdapter()->fetchRow($getQueueStartDate);
        $queueStartDate = $dateResult['queue_start_at'];

        $getCustomerIds = $this->_getReadAdapter()->select()
            ->from(array('nbu' => $this->getTable('newsletterbooster/unsubscribe')), 'entity_id')
            ->where('nbu.campaign_id = ?', $campaignId)
            ->where('nbu.create_at < ?', $queueStartDate);

        return $this->_getReadAdapter()->fetchRow($getCustomerIds);
    }

}