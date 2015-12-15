<?php

class Bronto_Reviews_Model_Mysql4_Post_Purchase extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * @see parent
     */
    protected function _construct()
    {
        $this->_init('bronto_reviews/post_purchase', 'entity_id');
    }

    /**
     * Retrieves the post purchase config from the DB
     *
     * @param Bronto_Reviews_Model_PostPurchase $object
     * @param int $productId
     * @param string $type
     * @param int $storeId
     * @return void
     */
    public function loadByProduct($object, $productId, $type, $storeId = 0)
    {
        $read = $this->_getReadAdapter();
        $select = $this->_getLoadSelect('product_id', $productId, $object);
        $fieldNameToValue = array('post_type' => $type, 'store_id' => $storeId);
        foreach ($fieldNameToValue as $fieldName => $fieldValue) {
            $fieldType = $read->quoteIdentifier(sprintf("%s.%s", $this->getMainTable(), $fieldName));
            $select->where("{$fieldType} = ?", $fieldValue);
        }
        $data = $read->fetchRow($select);
        if ($data) {
            $object->setData($data);
        }
    }
}
