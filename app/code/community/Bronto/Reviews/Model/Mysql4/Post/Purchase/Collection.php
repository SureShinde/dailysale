<?php

class Bronto_Reviews_Model_Mysql4_Post_Purchase_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * @see parent
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('bronto_reviews/post_purchase');
    }

    /**
     * Adds the post purchase type to the mix
     *
     * @param string $type
     * @return Bronto_Reviews_Model_Mysql4_Post_Purchase_Collection
     */
    public function filterByType($type)
    {
        return $this->addFieldToFilter('post_type', array('eq' => $type));
    }

    /**
     * Gets the post purchase by a specific store
     *
     * @param int $storeId
     * @param boolean $strict search
     * @return Bronto_Reviews_Model_Mysql4_Post_Purchase_Collection
     */
    public function filterByStoreId($storeId = 0, $strict = false)
    {
        if (empty($storeId) || $strict) {
            $this->addFieldToFilter('store_id', array('eq' => $storeId));
        } else {
            $this->addFieldToFilter('store_id', array('in' => array(0, $storeId)));
            $this->setOrder('store_id', 'DESC');
        }
        return $this;
    }

    /**
     * Gets all post purchase related emails by products
     *
     * @param mixed $productId
     * @return Bronto_Reviews_Model_Mysql4_Post_Purchase_Collection
     */
    public function filterByProduct($productId, $storeId = 0, $strict = false)
    {
        $productParams = is_array($productId) ?
            array('in' => $productId) :
            array('eq' => $productId);
        return $this->addFieldToFilter('product_id', $productParams);
    }

    /**
     * Gets all enabledpost purchase related emails
     *
     * @param boolean $active (Optional)
     * @return Bronto_Reviews_Model_Mysql4_PostPurchase_Collection
     */
    public function filterByActive($active = true)
    {
        return $this->addFieldToFilter('active', array('eq' => (int)$active));
    }
}
