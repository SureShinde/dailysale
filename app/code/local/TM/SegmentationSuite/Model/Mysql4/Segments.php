<?php

class TM_SegmentationSuite_Model_Mysql4_Segments extends Mage_CatalogRule_Model_Resource_Rule
{
    protected $_productIds = null;

    protected $_productCollection = null;

    protected function _construct()
    {
        $this->_init('segmentationsuite/segments', 'segment_id');
    }

    public function getOptionArray()
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getMainTable())
        ;
        $rowset = array();
        foreach ($this->_getReadAdapter()->fetchAll($select) as $row) {
            $rowset[] = array('label' => $row['segment_title'], 'value' => $row['segment_id']);
        }

        return $rowset;
    }

    public function load(Mage_Core_Model_Abstract $object, $value, $field=null)
    {
        if (!intval($value) && is_string($value)) {
            $field = 'identifier'; // You probably don't have an identifier...
        }
        return parent::load($object, $value, $field);
    }

    protected function _afterLoad(Mage_Core_Model_Abstract $object)
    {
        $select = $this->_getReadAdapter()->select()
	        ->from($this->getTable('segmentationsuite/store'))
	        ->where('segment_id = ?', $object->getId());

        if ($data = $this->_getReadAdapter()->fetchAll($select)) {
            $storesArray = array();
            foreach ($data as $row) {
                $storesArray[] = $row['store_id'];
            }
            $object->setData('store_id', $storesArray);
        }

        return parent::_afterLoad($object);
    }

    public function deleteSegmentIndex($segmentId)
    {
        try {
            $deleteWhere = $this->_getWriteAdapter()->quoteInto('segment_id = ?', $segmentId);

            /* delete segment indexs */
            $this->_getWriteAdapter()->delete(
                $this->getTable('segmentationsuite/index'), $deleteWhere);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function addSegmentStoreIds($object) {
        $deleteWhere = $this->_getWriteAdapter()->quoteInto('segment_id = ?', $object -> getId());

        /* store_view */
        $this->_getWriteAdapter()->delete($this->getTable('segmentationsuite/store'), $deleteWhere);

        foreach ($object->getData('stores') as $storeId) {
            $data = array('segment_id' => $object -> getId(), 'store_id' => $storeId);
            $this -> _getWriteAdapter() -> insert($this -> getTable('segmentationsuite/store'), $data);
        }
        return $this;
    }

    public function loadStoreIds($object) {
        $rulesId = $object -> getId();
        $storeIds = array();
        if ($rulesId) {
            $storeIds = $this -> lookupStoreIds($rulesId);
        }
        $object->setStoreId($storeIds);
    }

    /**
     * Get store ids, to which specified item is assigned
     *
     * @param int $id
     * @return array
     */
    public function lookupStoreIds($id) {
        $result = $this->_getReadAdapter() -> fetchCol(
            $this->_getReadAdapter()->select()->from(
                $this->getTable('segmentationsuite/store'), 'store_id')
                    ->where('segment_id = ?', $id));
        return $result;
    }

    /**
     * Update customers which are matched for rule
     *
     * @param Mage_CatalogRule_Model_Rule $rule
     * @return Mage_CatalogRule_Model_Resource_Rule
     */
    public function updateSegmentCustomerData($segment)
    {
        $segmentId = $segment->getId();
        $this->deleteSegmentIndex($segmentId);
        $write = $this -> _getWriteAdapter();
        $write -> beginTransaction();

        //        Varien_Profiler::start('__MATCH_PRODUCTS__');
        $customerIds = $segment->getMatchingCustomerIds();

        //        Varien_Profiler::stop('__MATCH_PRODUCTS__');

        $rows = array();

        try {
            foreach ($customerIds as $customerId) {
                $rows[] = array('segment_id' => $segmentId, 'entity_id' => $customerId, );
                if (count($rows) == 1000) {
                    $write -> insertMultiple($this -> getTable('segmentationsuite/index'), $rows);
                    $rows = array();
                }
            }
            if (!empty($rows)) {
                $write -> insertMultiple($this -> getTable('segmentationsuite/index'), $rows);
            }
            $write -> commit();
        } catch (Exception $e) {
            $write -> rollback();
            throw $e;
        }
        return $this;
    }

    /**
     * Get all product ids matched for rule
     *
     * @param int $ruleId
     * @return array
     */
    public function getRuleProductIds($ruleId) {
        $read = $this -> _getReadAdapter();
        $select = $read -> select() -> from($this -> getTable('prolabels/index'), 'entity_id') -> where('rules_id=?', $ruleId);
        return $read -> fetchCol($select);
    }


    /**
     * Apply catalog rule to product
     *
     * @param Mage_CatalogRule_Model_Rule $rule
     * @param Mage_Catalog_Model_Product $product
     * @param array $websiteIds
     * @return Mage_CatalogRule_Model_Resource_Rule
     */
    public function applyToProduct($rule, $product, $websiteIds) {
        if (!$rule -> getIsActive()) {
            return $this;
        }

        $ruleId = $rule -> getId();
        $productId = $product -> getId();

        $write = $this -> _getWriteAdapter();
        $write -> beginTransaction();

        $customerGroupIds = $rule -> getCustomerGroupIds();

        $fromTime = strtotime($rule -> getFromDate());
        $toTime = strtotime($rule -> getToDate());
        $toTime = $toTime ? $toTime + self::SECONDS_IN_DAY - 1 : 0;

        $sortOrder = (int)$rule -> getSortOrder();
        $actionOperator = $rule -> getSimpleAction();
        $actionAmount = $rule -> getDiscountAmount();
        $actionStop = $rule -> getStopRulesProcessing();

        $rows = array();
        try {
            foreach ($websiteIds as $websiteId) {
                foreach ($customerGroupIds as $customerGroupId) {
                    $rows[] = array('rules_id' => $ruleId, 'product_id' => $productId, );

                    if (count($rows) == 1000) {
                        $write -> insertMultiple($this -> getTable('prolabels/index'), $rows);
                        $rows = array();
                    }
                }
            }

            if (!empty($rows)) {
                $write -> insertMultiple($this -> getTable('prolabels/index'), $rows);
            }
        } catch (Exception $e) {
            $write -> rollback();
            throw $e;
        }

        $this -> applyAllRulesForDateRange(null, null, $product);
        $write -> commit();

        return $this;
    }

    public function deleteAllLabelIndex() {
        try {
            $write = $this -> _getWriteAdapter();
            $write -> beginTransaction();
            $write -> delete($this -> getTable('prolabels/index'));
            $write -> commit();
            $write -> query("ALTER TABLE " . $this -> getTable('prolabels/index') . " AUTO_INCREMENT = 1");
        } catch (Exception $e) {
            $write -> rollback();
        }
        return $this;
    }

    public function apllySystemRule(TM_ProLabels_Model_Label $rule, $productId) {
        if (!$rule -> getData('label_status')) {
            return $this;
        }
        $this -> _validateSystemRule($rule -> getId(), $productId);

        return $this;
    }

    protected function _validateSystemRule($ruleId, $productId) {
        switch ($ruleId) {
            case '1' :
                $this -> validateOnSale($ruleId, $productId);
                break;
            case '2' :
                $this -> validateInStock($ruleId, $productId);
                break;
            case '3' :
                $this -> validateIsNew($ruleId, $productId);
                break;
        }
    }

    public function validateInStock($ruleId, $productId) {
        $result = array();
        $model = Mage::getModel('catalog/product');
        $out = Mage::getStoreConfig("prolabels/instock/out");
        $minItems = (int)Mage::getStoreConfig("prolabels/instock/minitems");
        try {
            $product = $model -> load($productId);
            if ($product -> getTypeInstance() instanceof Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Type_Configurable) {
                $model = new Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Type_Configurable();
                $simpleProductIds = $model -> getChildrenIds($product -> getId());
                foreach (current($simpleProductIds) as $productId) {
                    $simpleProduct = Mage::getModel('catalog/product') -> load($productId);
                    $productQty = $simpleProduct -> getData('stock_item') -> qty;
                    $quantity = $quantity + (int)$productQty;
                }
            } elseif ($product -> getTypeInstance() instanceof Mage_Bundle_Model_Product_Type) {
                $groupSum = array();
                foreach ($product->getTypeInstance()->getOptions() as $option) {
                    if (!$option -> getData('required')) {
                        return $this;
                    }
                    $selections = $option -> getSelections();
                    if (count($selections) < 1) {
                        continue;
                    }
                    //                    $sum = 0;
                    foreach ($selections as $simpleProduct) {
                        $sum += $simpleProduct -> getData('stock_item') -> qty;
                    }
                    $groupSum[] = $sum;
                    $sum = 0;
                }
                if (count($groupSum) < 1) {
                    $quantity = 999999;
                } else {
                    $quantity = min($groupSum);
                }

            } else {
                $quantity = $product -> getData('stock_item') -> qty;
            }

            if (!$product -> getData('stock_item') -> is_in_stock || $quantity == 0) {
                if ($out) {
                    $result = array('rules_id' => $ruleId, 'product_id' => $product -> getId(), );
                }
            } else {
                if ($quantity > 0 && $quantity < $minItems) {
                    $result = array('rules_id' => $ruleId, 'product_id' => $product -> getId(), );
                }
            }
            if (count($result) > 0) {
                $model = Mage::getModel('prolabels/index');
                $model -> addData($result);
                $model -> save();
            }
        } catch (Exception $e) {
            throw $e;
        }

        return $this;
    }

    public function validateIsNew($ruleId, $productId) {
        $write = $this -> _getWriteAdapter();
        $model = Mage::getModel('catalog/product');
        try {
            $product = $model -> load($productId);
            if ($product -> getData('news_from_date') === null && $product -> getData('news_to_date') === null) {
                return $this;
            }

            $today = Mage::getModel('core/date')->timestamp(time());

            if ($product->getData('news_from_date') && null === $product->getData('news_to_date')) {
                $from = Mage::getModel('core/date')->timestamp($product->getData('news_from_date'));
                if ($today > $from) {
                    $result = array('rules_id' => $ruleId, 'product_id' => $product -> getId(), );
                    $model = Mage::getModel('prolabels/index');
	            $model -> addData($result);
	            $model -> save();
                    return $this;
                }
            }

            if ($product->getData('news_to_date') && null === $product->getData('news_from_date')) {
                $to =  Mage::getModel('core/date')->timestamp($product->getData('news_to_date'));
                if ($today < $to) {
                    $result = array('rules_id' => $ruleId, 'product_id' => $product -> getId(), );
                    $model = Mage::getModel('prolabels/index');
	            $model -> addData($result);
	            $model -> save();
                    return $this;
                }
            }
            $from = Mage::getModel('core/date')->timestamp($product->getData('news_from_date'));
            $to =  Mage::getModel('core/date')->timestamp($product->getData('news_to_date'));

            if ($from && $today < $from) {
                return false;
            }
            if ($to && $today > $to) {
                return false;
            }
            if (!$to && !$from) {
                return false;
            }

            $result = array('rules_id' => $ruleId, 'product_id' => $product -> getId(), );
            $model = Mage::getModel('prolabels/index');
            $model -> addData($result);
            $model -> save();
        } catch (Exception $e) {
            throw $e;
        }
        return $this;
    }

    public function validateOnSale($ruleId, $productId) {
        $result = array();
        $model = Mage::getModel('catalog/product');
        try {
            $product = $model -> load($productId);
            if ($product -> getTypeInstance() instanceof Mage_Catalog_Model_Product_Type_Grouped) {
                $simpleProductIds = $product -> getTypeInstance() -> getAssociatedProductIds();
                $price = 0;
                $finalPrice = 0;
                $i = 0;
                foreach ($simpleProductIds as $simpleProductId) {
                    $simpleProduct = Mage::getModel('catalog/product') -> load($simpleProductId);

                    if ($simpleProduct -> getData('special_price')) {
                        $finalPrice = $simpleProduct -> getData('special_price');
                        $price = $simpleProduct -> getData('price');
                        if ($i == 0) {
                            Mage::register('prolabelprice', $price);
                            Mage::register('prolabelfinalprice', $finalPrice);
                        }

                        if ($i > 0) {
                            if (($price - $finalPrice) > (Mage::registry('prolabelprice') - Mage::registry('prolabelfinalprice'))) {
                                Mage::unregister('prolabelprice');
                                Mage::unregister('prolabelfinalprice');
                                Mage::register('prolabelprice', $price);
                                Mage::register('prolabelfinalprice', $finalPrice);
                            }
                        }
                        $i++;
                    }
                }
                if (Mage::registry('prolabelfinalprice') && Mage::registry('prolabelprice')) {
                    $result = array('rules_id' => $ruleId, 'product_id' => $product -> getId(), );
                }
            }
            if ($product -> getTypeInstance() instanceof Mage_Bundle_Model_Product_Type) {
                if ($product -> getData('special_price') && $product -> getData('special_price') !== null) {
                    $result = array('rules_id' => $ruleId, 'product_id' => $product -> getId(), );
                }
            } elseif ($product -> getData('price') > $product -> getData('special_price') && $product -> getData('special_price') > 0 && $this -> checkSpecailDate($product)) {
                $result = array('rules_id' => $ruleId, 'product_id' => $product -> getId(), );
            }

            if (count($result) > 0) {
                $model = Mage::getModel('prolabels/index');
                $model -> addData($result);
                $model -> save();
            }
        } catch (Exception $e) {
            throw $e;
        }

        return $this;
    }

    public function checkSpecailDate($product)
    {
        $today = Mage::getModel('core/date')->timestamp(time());

        if ($product->getData('special_from_date') && null === $product->getData('special_to_date')) {
            $from = Mage::getModel('core/date')->timestamp($product->getData('special_from_date'));
            if ($today > $from) {
                return true;
            }
            return false;
        }

        if ($product->getData('special_to_date') && null === $product->getData('special_from_date')) {
            $to =  Mage::getModel('core/date')->timestamp($product->getData('special_to_date'));
            if ($today < $to) {
                return true;
            }
            return false;
        }
        $from = Mage::getModel('core/date')->timestamp($product->getData('special_from_date'));
        $to =  Mage::getModel('core/date')->timestamp($product->getData('special_to_date'));

        if ($from && $today < $from) {
            return false;
        }
        if ($to && $today > $to) {
            return false;
        }
        if (!$to && !$from) {
            return false;
        }
        return true;
    }

    public function getItemsToProcess($count = 1, $step = 0) {
        $connection = $this -> _getReadAdapter();

        $labelSelect = $connection->select()
            ->from(array('cp' => $this -> getTable('segmentationsuite/segments')), 'segment_id')
            ->order('segment_id')
            -> limit($count, $count * $step);

        $result = $connection -> fetchCol($labelSelect);
        return $result;
    }

    public function getProductRuleIds($productId) {
        return $this -> _getReadAdapter() -> fetchCol($this -> _getReadAdapter() -> select() -> from($this -> getTable('index'), 'rules_id') -> where('product_id = ?', $productId));
    }

    public function getProductLabelsData($productId, $mode) {
        $rulesIds = $this -> getProductRuleIds($productId);
        $result = $this -> _getReadAdapter() -> fetchAll(
            $this -> _getReadAdapter() -> select()
            -> from($this -> getTable('label')) -> where('rules_id in (?)', $rulesIds));
        return $result;
    }

    public function reindexAllSystemLabels() {
        $collection = Mage::getModel('catalog/product') -> getCollection();
        $collection -> walk(array($this, 'updateLabelDataForProduct'));
        return $this;
    }

    public function updateLabelDataForProduct($product) {
        $productId = $product -> getId();
        $rule = Mage::getModel('prolabels/label');
        for ($i = 1; $i <= 3; $i++) {
            $rule -> load($i);
            $this -> apllySystemRule($rule, $productId);
        }

        return $this;
    }

}
