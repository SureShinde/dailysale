<?php

class Bronto_Product_Model_Mysql4_Recommendation_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * @see parent
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('bronto_product/recommendation');
    }

    /**
     * Filters recommendations that can be sent in emails
     *
     * @return Bronto_Product_Model_Mysql4_Recommendation_Collection
     */
    public function onlyEmailBased()
    {
        return $this->addFieldToFilter('content_type', array('eq' => 'api'));
    }

    /**
     * Filters recommendations that can be used to update content tags
     *
     * @return Bronto_Product_Model_Mysql4_Recommendation_Collection
     */
    public function onlyContentTagBased()
    {
        return $this->addFieldToFilter('content_type', array('eq' => 'content_tag'));
    }

    /**
     * Filters recommendations that can be used for this store only
     *
     * @param int $storeId
     * @return Bronto_Product_Model_Mysql4_Recommendation_Collection
     */
    public function addStoreToFilter($storeId = 1)
    {
        return $this->addFieldToFilter('store_id', array('eq' => $storeId));
    }

    /**
     * Filters a store on a particular scope with the admin scope included
     *
     * @param int $storeId
     * @return Bronto_Product_Model_Mysql4_Recommendation_Collection
     */
    public function addAnyStoreFilter($storeId)
    {
        if ($storeId != 0) {
            return $this->addFieldToFilter('store_id', array('in' => array(0, $storeId)));
        }
        return $this;
    }

    /**
     * Filters the recommendations by a given name
     *
     * @param string $name
     * @return Bronto_Product_Model_Mysql4_Recommendation_Collection
     */
    public function addNameToFilter($name)
    {
        return $this->addFieldToFilter('name', array('eq' => $name));
    }

    /**
     * Filters the recommendations by ending with a certain name
     *
     * @param string $name
     * @return Bronto_Product_Model_Mysql4_Recommendation_Collection
     */
    public function nameEndsWith($name)
    {
        return $this->addFieldToFilter('name', array('like' => "%$name"));
    }

    /**
     * Orders the recommendations by name, in specified direction
     *
     * @param string $dir
     * @return Bronto_Product_Model_Mysql4_Recommendation_Collection
     */
    public function orderAlphebetically($dir = 'asc')
    {
        return $this->addOrder('name', $dir);
    }
}
