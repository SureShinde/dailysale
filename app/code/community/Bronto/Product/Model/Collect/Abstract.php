<?php

abstract class Bronto_Product_Model_Collect_Abstract {
    protected $_products = array();
    protected $_hash = array();
    protected $_excluded = array();
    protected $_storeId;
    protected $_recommendation;
    protected $_remainingCount;

    protected $_source;
    protected $_product = null;

    /**
     * Implementors override this specialized collection method
     * Implementors should return a product hash table
     *
     * @return array
     */
    public abstract function collect();

    /**
     * Tells the factory that an associated product is required
     *
     * @return bool
     */
    public function isProductRelated()
    {
        return false;
    }

    /**
     * Tells the factory that an associated source is required
     *
     * @return bool
     */
    public function isSourceRequired()
    {
        return false;
    }

    /**
     * Returns the computed recommendations for this collector
     *
     * @return array
     */
    public function getProducts()
    {
        if (!$this->isReachedMax()) {
            return $this->collect();
        }
        return $this->_products;
    }

    /**
     * Determines if this collector has filled up
     *
     * @return bool
     */
    public function isReachedMax()
    {
        return $this->getRemainingCount() <= 0;
    }

    /**
     * Sets the Product recommendation to gather related products
     *
     * @param Bronto_Product_Model_Recommendation $rec
     * @return Bronto_Product_Model_Collect_Abstract
     */
    public function setRecommendation(Bronto_Product_Model_Recommendation $rec)
    {
        $this->_recommendation = $rec;
        return $this;
    }

    /**
     * Sets the original hash to be treated like shopping context
     *
     * @param array $originalHash
     * @return Bronto_Product_Model_Collect_Abstract
     */
    public function setOriginalHash($originalHash)
    {
        $this->_hash = $originalHash;
        return $this;
    }

    /**
     * Sets the excluded products hash to be used to dedupe the collection
     *
     * @param array $excluded
     * return Bronto_Product_Model_Collect_Abstract
     */
    public function setExcluded($excluded)
    {
        $this->_excluded = $excluded;
        return $this;
    }

    /**
     * Sets the store Id for processing
     *
     * @param mixed $storeId
     * @return Bronto_Product_Model_Collect_Abstract
     */
    public function setStoreId($storeId)
    {
        $this->_storeId = $storeId;
        return $this;
    }

    /**
     * Gets the current number of recommendations
     *
     * @return int
     */
    public function getRemainingCount()
    {
        if (is_null($this->_remainingCount)) {
            $this->_remainingCount = $this->_recommendation->getNumberOfItems();
        }
        return $this->_remainingCount;
    }

    /**
     * Adjusts the remaining count
     *
     * @param int $remainingCount
     * @return Bronto_Product_Model_Collect_Abstract
     */
    public function setRemainingCount($remainingCount)
    {
        $this->_remainingCount = $remainingCount;
        return $this;
    }

    /**
     * Sets the product for a related product collector
     *
     * @param Mage_Catalog_Model_Product $product
     * @return Bronto_Product_Model_Collect_Abstract
     */
    public function setProduct($product = null)
    {
        $this->_product = $product;
        return $this;
    }

    /**
     * Sets the source for a source required collector
     *
     * @param string $source
     * @return Bronto_Product_Model_Collect_Abstract
     */
    public function setSource($source)
    {
        $this->_source = $source;
        return $this;
    }

    /**
     * Gets the store id
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->_storeId;
    }

    /**
     * Fills the products from the collection, only returning those added
     *
     * @param mixed $productsOrIds
     * @return array
     */
    protected function _fillProducts($productsOrIds)
    {
        $products = array();
        $helper = Mage::helper('bronto_common/product');
        foreach ($productsOrIds as $productOrId) {
            if ($this->_remainingCount - count($products) <= 0) {
                break;
            }
            if ($productOrId instanceof Mage_Reports_Model_Event) {
                $productId = $productOrId->getObjectId();
            } else if (is_numeric($productOrId)) {
                $productId = $productOrId;
            } else {
                $productId = $productOrId->getId();
            }
            $product = $helper->getProduct($productId, $this->getStoreId());
            if (!$product->getId()) {
                continue;
            }
            $product = $helper->getConfigurableProduct($product);
            if (!$this->_isValidProduct($product->getId())) {
                continue;
            }
            $products[$product->getId()] = $product;
        }
        return $products;
    }

    /**
     * Tests if this product can be added to the pool
     *
     * @param string $productId
     * @return bool
     */
    protected function _isValidProduct($productId)
    {
        if (array_key_exists($productId, $this->_excluded)) {
            return false;
        }
        if (array_key_exists($productId, $this->_products)) {
            return false;
        }
        return true;
    }
}
