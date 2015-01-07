<?php

class Bronto_Reviews_Model_Post_Purchase extends Mage_Core_Model_Abstract
{
    const PERIOD_DAILY   = 'daily';
    const PERIOD_WEEKLY  = 'weekly';
    const PERIOS_MONTHLY = 'monthly';

    const TYPE_CARETIP = 'caretip';
    const TYPE_REORDER = 'reorder';

    private static $_supportedTypes = array(
        self::TYPE_REORDER,
        self::TYPE_CARETIP
    );

    /**
     * @see parent
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('bronto_reviews/post_purchase');
    }

    /**
     * Loads a single reorder config by ID and type
     *
     * @param int $productId
     * @param string $type
     * @param int $storeId
     * @return Bronto_Reviews_Model_PostPurchase
     */
    public function loadByProduct($productId, $type, $storeId = 0)
    {
        $this
            ->setProductId($productId)
            ->setPostType($type)
            ->setStoreId($storeId);
        $this->getResource()->loadByProduct($this, $productId, $type, $storeId);
        return $this;
    }

    /**
     * Returns the supported types
     *
     * @return array
     */
    public function getSupportedTypes()
    {
        return self::$_supportedTypes;
    }
}
