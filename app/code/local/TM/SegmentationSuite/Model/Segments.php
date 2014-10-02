<?php
/**
 * DO NOT REMOVE OR MODIFY THIS NOTICE
 *
 * EasyBanner module for Magento - flexible banner management
 *
 * @author Templates-Master Team <www.templates-master.com>
 */

class TM_SegmentationSuite_Model_Segments extends Mage_CatalogRule_Model_Rule
{
     /**
     * Matched product ids array
     *
     * @var array
     */
    protected $_productIds;

    /**
     * Limitation for products collection
     *
     * @var int|array|null
     */
    protected $_productsFilter = null;

    protected function _construct()
    {
        parent::_construct();
        $this->_init('segmentationsuite/segments');
        $this->setIdFieldName('segment_id');
    }

    protected function _afterSave()
    {
        $this->_getResource()->addSegmentStoreIds($this);
        if ((int)$this->getData('segment_status') != 0) {
            $this->_getResource()->updateSegmentCustomerData($this);
        } else {
            $this->_getResource()->deleteSegmentIndex($this->getId());
        }
        parent::_afterSave();
    }

    public function getConditionsInstance()
    {
        return Mage::getModel('segmentationsuite/rule_condition_combine');
    }

    public function getOptionArray()
    {
        return $this->_getResource()->getOptionArray();
    }

    public function loadStoreIds($object)
    {
        $rulesId = $object->getId();
        $storeIds = array();
        if ($rulesId) {
            $storeIds = $this->lookupStoreIds($rulesId);
        }
        $object->setStoreIds($storeIds);
    }

    /**
     * Get store ids, to which specified item is assigned
     *
     * @param int $id
     * @return array
     */
    public function lookupStoreIds($id)
    {
        return $this->_getResource()->lookupStoreIds($id);
    }

    public function getLabelsData($productId, $mode)
    {
        return $this->_getResource()->getProductLabelsData($productId, $mode);
    }

    /**
     * Get array of product ids which are matched by rule
     *
     * @return array
     */
    public function getMatchingCustomerIds()
    {
        if (is_null($this->_productIds)) {
            $this->_productIds = array();
            $this->setCollectedAttributes(array());
            $storeIds = $this->lookupStoreIds($this->getId());

            $customerCollection = Mage::getResourceModel('customer/customer_collection');
            if (!in_array(0, $storeIds)) {
                $customerCollection->addFieldToFilter('store_id', array('in' => $storeIds));
            }

            $this->getConditions()->collectValidatedAttributes($customerCollection);

            Mage::getSingleton('core/resource_iterator')->walk(
                $customerCollection->getSelect(),
                array(array($this, 'callbackValidateProduct')),
                array(
                    'attributes' => $this->getCollectedAttributes(),
                    'customer'    => Mage::getModel('customer/customer'),
                )
            );

        }
        return $this->_productIds;
    }

    /**
     * Callback function for product matching
     *
     * @param $args
     * @return void
     */
    public function callbackValidateProduct($args)
    {
        $customer = clone $args['customer'];
        $customer->setData($args['row']);

        if ($this->getConditions()->validate($customer)) {
            $this->_productIds[] = $customer->getId();
        }
    }

    /**
     * Apply rule to product
     *
     * @param int|Mage_Catalog_Model_Product $product
     * @param array $websiteIds
     * @return void
     */
    public function applyToProduct($product, $websiteIds=null)
    {
        if (is_numeric($product)) {
            $product = Mage::getModel('catalog/product')->load($product);
        }
        if (is_null($websiteIds)) {
            $websiteIds = explode(',', $this->getWebsiteIds());
        }
        $this->getResource()->applyToProduct($this, $product, $websiteIds);
    }

    /**
     * Apply all price rules, invalidate related cache and refresh price index
     *
     * @return Mage_CatalogRule_Model_Rule
     */
    public function applyAll()
    {
        $collection = $this->getResourceCollection();
        $collection->getSelect()
            ->where('rules_id>3')
            ->where('label_status=1');

        $collection->walk(array($this->_getResource(), 'updateProlabelsRuleProductData'));
//        $this->_getResource()->applyAllRulesForDateRange();
    }


    /**
     * Apply all price rules to product
     *
     * @param  int|Mage_Catalog_Model_Product $product
     * @return Mage_CatalogRule_Model_Rule
     */
    public function applyAllRulesToProduct($product)
    {
        $this->_getResource()->applyAllRulesForDateRange(NULL, NULL, $product);
        $this->_invalidateCache();

        if ($product instanceof Mage_Catalog_Model_Product) {
            $productId = $product->getId();
        } else {
            $productId = $product;
        }

        if ($productId) {
            Mage::getSingleton('index/indexer')->processEntityAction(
                new Varien_Object(array('id' => $productId)),
                Mage_Catalog_Model_Product::ENTITY,
                Mage_Catalog_Model_Product_Indexer_Price::EVENT_TYPE_REINDEX_PRICE
            );
        }
    }


    /**
     * Return true if banner status = 1
     * and banner linked to active placeholder
     *
     * @return boolean
     */
    public function isActive()
    {
        if ($this->getData('label_status')/* && count($this->getPlaceholderIds(true))*/) {
            return true;
        }
        return false;
    }

    public function getStoreIds()
    {
        return $this->_getResource()->loadStoreIds($this);;
    }
}
