<?php

/**
 * @package   Bronto\Newsletter
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Newsletter_Model_Queue extends Mage_Core_Model_Abstract
{

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('bronto_newsletter/queue');
    }

    public function getContactRow($subscriber_id, $store_id)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('subscriber_id', $subscriber_id)
            ->addFieldToFilter('store', $store_id);

        if ($collection->count() == 1) {
            return $collection->getFirstItem();
        } else {
            $this->setSubscriberId($subscriber_id)
                ->setCreatedAt(Mage::getSingleton('core/date')->gmtDate())
                ->setStore($store_id);
        }

        return $this;
    }

    /**
     * Get Count of missing subscribers
     *
     * @return int
     */
    public function getMissingSubscribersCount()
    {
        // Get Resources
        $resource = $this->getResource();
        $adapter  = $resource->getWriteAdapter();

        // Build Select Statement
        $select = $adapter->select();
        $select->from(
            array('subscriber' => $resource->getTable('newsletter/subscriber')), array(new Zend_Db_Expr('COUNT(subscriber_id) as count'))
        )
            ->where('NOT EXISTS (?)', $this->_getSubselect($resource, $adapter));

        // Get Results
        $result = $adapter->query($select)->fetch();

        if (array_key_exists('count', $result)) {
            return (int)$result['count'];
        } else {
            return 0;
        }
    }

    /**
     * Get Sub-Select Statement that limits results
     *
     * @param Bronto_Newsletter_Model_Mysql4_Queue $resource
     * @param                                      $adapter
     *
     * @return Varien_Db_Select
     */
    private function _getSubselect($resource, $adapter)
    {
        // Build Sub-Select Statement
        $subselect = $adapter->select()
            ->from(
                array('queue' => $resource->getTable('bronto_newsletter/queue')), array(new Zend_Db_Expr(1))
            )
            ->where('queue.subscriber_id = subscriber.subscriber_id');

        return $subselect;
    }

    /**
     * Get collection of subscribers which aren't already in the queue, but should be
     *
     * @return array
     */
    public function getMissingSubscribers()
    {
        // Get Resources
        $resource = $this->getResource();
        $adapter  = $resource->getWriteAdapter();

        // Get Sync Limit Value
        $count = Mage::helper('bronto_newsletter')->getSyncLimit();

        // Build Select Statement
        $select = $adapter->select();
        $select->from(
            array('subscriber' => $resource->getTable('newsletter/subscriber')),
            array('subscriber_id', 'store_id', 'subscriber_email', 'subscriber_status')
        )
            ->where('NOT EXISTS (?)', $this->_getSubselect($resource, $adapter))
            ->limit($count);

        // Get Results
        $result = $adapter->query($select)->fetchAll();

        return $result;
    }

}
