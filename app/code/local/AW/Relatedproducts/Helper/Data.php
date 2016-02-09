<?php
/**
 * aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This software is designed to work with Magento community edition and
 * its use on an edition other than specified is prohibited. aheadWorks does not
 * provide extension support in case of incorrect edition use.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Relatedproducts
 * @version    1.4.4
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


class AW_Relatedproducts_Helper_Data extends Mage_Core_Helper_Abstract
{
    /*
     * 	Take $relatedIds array and establish relations to each other
     */
    function updateRelations($relatedIds, $storeId = null)
    {
        if ($storeId === null) {
            $storeId = Mage::app()->getStore()->getId();
        }
        foreach ($relatedIds as $id) {
            //fetch relations for each of the ID's
            /** @var $model AW_Relatedproducts_Model_Relatedproducts */
            $model = Mage::getModel('relatedproducts/relatedproducts');
            $coll = $model->getCollection()
                ->addStoreFilter($storeId)
                ->addProductFilter($id)
                ->load()
            ;
            $arr = $this->_prepareRelatedIdsForProductId($relatedIds, $id);
            if (sizeof($coll) > 0) {
                $_relatedModel = $coll->getFirstItem();

                //take current related products
                $savedRelatedIds = unserialize($_relatedModel->getData('related_array'));
                foreach ($arr as $key => $value) {
                    if (!empty($savedRelatedIds[$key])) {
                        $savedRelatedIds[$key] += 1;
                    } else {
                        //increment the relation counter
                        $savedRelatedIds[$key] = 1;
                    }
                }
                $arr = $savedRelatedIds;
                $model->setId($_relatedModel->getId());
            }
            $arr = serialize($arr);
            $model
                ->setStoreId($storeId)
                ->setProductId($id)
                ->setRelatedArray($arr)
                ->save()
            ;
        }
        return $this;
    }

    public function isEnterprise()
    {
        return Mage::helper('awall/versions')->getPlatform() == AW_All_Helper_Versions::EE_PLATFORM;
    }

    public function checkVersion($version)
    {
        return version_compare(Mage::getVersion(), $version, '>=');
    }

    /**
     * Retrives Advanced Reviews Disabled Flag
     * @return boolean
     */
    public function getExtDisabled()
    {
        return Mage::getStoreConfig('advanced/modules_disable_output/AW_Relatedproducts');
    }

    /**
     *
     * @param <type> $storeId
     * @return array
     */
    public function getAllowStatuses($storeId = null)
    {
        $res = explode(",", Mage::getStoreConfig('relatedproducts/general/process_orders', $storeId));
        return count($res) ? $res : array(Mage_Sales_Model_Order::STATE_COMPLETE);
    }

    /**
     * @return AW_Relatedproducts_Helper_Config
     */
    public function _getConfigHelper()
    {
        return Mage::helper('relatedproducts/config');
    }

    public function isInstalledForProduct($productId, $storeId = null)
    {
        if ($storeId === null) {
            $storeId = Mage::app()->getStore()->getId();
        }
        /** @var $relatedCollection AW_Relatedproducts_Model_Mysql4_Relatedproducts_Collection */
        $relatedCollection = Mage::getModel('relatedproducts/relatedproducts')->getCollection();
        $relatedCollection->addProductFilter($productId)
            ->addStoreFilter($storeId);
        return ($relatedCollection->getSize() > 0);
    }

    /**
     * Retrives table name for Model Entity Name
     * @param string $modelEntity
     * @return string
     */
    public function getTableName($modelEntity)
    {
        try {
            $table = Mage::getSingleton('core/resource')->getTableName($modelEntity);
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
        }
        return $table;
    }

    /**
     * Index sales data for current product
     * @param      $productId
     * @param null $storeId
     * @param null $productsToDisplay
     *
     * @return $this
     */
    public function installForProduct($productId, $storeId = null, $productsToDisplay = null)
    {
        if ($storeId === null) {
            $storeId = Mage::app()->getStore()->getId();
        }
        $orders = $this->_getOrdersForProduct($productId, $storeId, $productsToDisplay);
        foreach ($orders as $order) {
            $order = Mage::getModel('sales/order')->load($order->getId());
            $this->updateRelationsForOrderItems($order);
        }
        return $this;
    }

    public function updateRelationsForOrderItems(Mage_Sales_Model_Order $order)
    {
        $items = $order->getAllItems();
        $ids = array();
        if (count($items)) {
            foreach ($items as $item) {
                if ($item->getParentItemId()) {
                    continue;
                }
                array_push($ids, $item->getProductId());
            }
        }
        if (count($ids) > 1) {
            $this->updateRelations($ids, $order->getStoreId());
        }
        return $this;
    }

    protected function _prepareRelatedIdsForProductId(array $relatedIds, $productId)
    {
        $arr = array();
        foreach ($relatedIds as $i) {
            /*
             * not the product for itself
             * set relation rate to 1 for all
             */
            if ($i == $productId) {
                continue;
            }
            $arr[$i] = 1;
        }
        return $arr;
    }

    protected function _getOrdersForProduct($productId, $storeId, $productsToDisplay)
    {
        $configHelper = $this->_getConfigHelper();
        $orders = Mage::getModel('sales/order')->getCollection();
        $orders
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('status', array('in' => $this->getAllowStatuses()))
        ;
        if ($productsToDisplay === null) {
            $productsToDisplay = $configHelper->getGeneralProductsToDisplay($storeId);
        }
        $catalogCategoryTable = $this->getTableName('catalog/category_product');

        $orderAlias = 'e';
        if ($this->isEnterprise()) {
            $itemTable = $this->getTableName('sales/order_item');
            $orderAlias = 'main_table';
        }

        if ($this->checkVersion('1.4.1.0')) {
            $itemTable = $this->getTableName('sales/order_item');
            $orderAlias = 'main_table';
        }

        if (!isset($itemTable)) {
            $itemTable = $orders->getTable('sales_flat_order_item');
        }

        $orders->getSelect()
            ->join(
                array('item' => $itemTable),
                $orderAlias . ".entity_id = item.order_id AND item.parent_item_id IS NULL",
                array()
            )
            ->join(
                array('item1' => $itemTable),
                $orderAlias . ".entity_id = item1.order_id AND item1.parent_item_id IS NULL",
                array('i_count' => 'COUNT( item1.product_id )')
            )
            ->where($orderAlias . '.store_id = ?', $storeId)
            ->where('item.product_id = ?', $productId)
            ->group($orderAlias . '.entity_id')
            ->order('i_count DESC')
            ->limit($productsToDisplay)
        ;

        if ($configHelper->getGeneralSameCategory($storeId)) {
            $orders->getSelect()
                # Join cats of main product
                ->joinRight(
                    array('mainProd' => $catalogCategoryTable),
                    "mainProd.product_id = item.product_id",
                    array()
                )
                # Join cats of sub products
                ->joinLeft(array('subProd' => $catalogCategoryTable), "subProd.product_id = item1.product_id", array())
                ->where('mainProd.category_id = subProd.category_id');
        }
        $orders->load();
        return $orders;
    }
}