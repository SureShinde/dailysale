<?php

class Bronto_Reviews_Model_Mysql4_Log_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * @see parent
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('bronto_reviews/log');
    }

    /**
     * Filters by cancelled entries
     *
     * @return Bronto_Reviews_Model_Mysql4_Log_Collection
     */
    public function filterCancelled($cancelled = true)
    {
        return $this->addFieldToFilter('cancelled', array('eq' => (int)$cancelled));
    }

    /**
     * Filters the log by some time in some operation
     *
     * @param string $operator
     * @param int $time
     * @return Bronto_Reviews_Model_Mysql4_Log_Collection
     */
    public function filterTime($operator, $time = null)
    {
        $date = date('Y-m-d H:i:s', empty($time) ? time() : $time);
        return $this->addFieldToFilter('delivery_date', array($operator => $date));
    }

    /**
     * Gets all logs which are old
     *
     * @param int $time
     * @return Bronto_Reviews_Model_Mysql4_Log_Collection
     */
    public function filterOld($time = null)
    {
        return $this->filterTime('lteq', $time);
    }

    /**
     * Gets all logs scheduled in the future
     *
     * @param int $time
     * @return Bronto_Reviews_Model_Mysql4_Log_Collection
     */
    public function filterFuture($time = null)
    {
        return $this->filterTime('gteq', $time);
    }

    /**
     * Filters by a given store view
     *
     * @param int $storeId
     * @return Bronto_Reviews_Model_Mysql4_Log_Collection
     */
    public function filterByStoreId($storeId)
    {
        return $this->addFieldToFilter('store_id', array('eq' => $storeId));
    }

    /**
     * Filters by a given post id
     *
     * @param int $postId
     * @return Bronto_Reviews_Model_Mysql4_Log_Collection
     */
    public function filterByPost($postId)
    {
        return $this->addFieldToFilter('post_id', array('eq' => $postId));
    }

    /**
     * Filters logs made by a certain email
     *
     * @param string $email
     * @param int $postId
     * @return Bronto_Reviews_Model_Mysql4_Log_Collection
     */
    public function filterByEmail($email, $postId = null)
    {
        if (is_null($postId)) {
            $this->addFieldToFilter('post_id', array('null' => true));
        } else {
            $this->addFieldToFilter('post_id', array('eq' => $postId));
        }
        return $this->addFieldToFilter('customer_email', array('eq' => $email));
    }
}
