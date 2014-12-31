<?php

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

    public function getStoreIds()
    {
        return $this->_getResource()->loadStoreIds($this);;
    }

    public function indexSegment($count, $step, $segmentId)
    {
        $this->load($segmentId);

        $this->_productIds = array();
        $this->setCollectedAttributes(array());

        $storeIds = $this->lookupStoreIds($segmentId);
        $customerCollection = Mage::getResourceModel('customer/customer_collection');
        if (!in_array(0, $storeIds)) {
            $customerCollection->addFieldToFilter('store_id', array('in' => $storeIds));
        }
        $customerCollection->getSelect()
            ->order('entity_id')
            ->limit($count, $count * $step);
        $customerCollection->load();

        $this->getConditions()->collectValidatedAttributes($customerCollection);

        Mage::getSingleton('core/resource_iterator')->walk(
            $customerCollection->getSelect(),
            array(array($this, 'callbackValidateProduct')),
            array(
                'attributes' => $this->getCollectedAttributes(),
                'customer'   => Mage::getModel('customer/customer'),
            )
        );
        if (count($this->_productIds) > 0) {
            $this->getResource()->updateSegmentCustomerData($this, $this->_productIds);
        }

        return true;
    }
}
