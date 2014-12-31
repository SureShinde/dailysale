<?php

class Fiuze_Bestsellercron_Model_Calculate extends Mage_Core_Model_Abstract
{
    private $numberOfProducts;
    private $config;
    private $hours;

    public function __construct()
    {
        $this->config = new Varien_Object(Mage::getStoreConfig('bestsellers_settings_sec/bestsellers_settings_grp'));
        $this->numberOfProducts = $this->config->getListMode();
        $this->hours = $this->config->getHours();
        parent::__construct();
    }

    private function clearBestProducts()
    {
         $category = Mage::getResourceModel('catalog/category_collection')->addFieldToFilter('name', 'Bestsellers')->getLastItem();
         $categoryId = $category->getId();
         $productCollection = Mage::getResourceModel('catalog/product_collection')->addCategoryFilter($category);
         foreach($productCollection as $product){
             $categoryIds = $product->getCategoryIds();
             if($key = array_search($categoryId,$categoryIds) !== FALSE){
                 unset($categoryIds[$key]);
             }
         }
        return $categoryId;
    }

    public function prepareBestsellers()
    {
        $categoryId = $this->clearBestProducts();
        $bestsellersCollection = $this->getBestsellers();
        $this->addToBestsellersCategory($bestsellersCollection, $categoryId);
    }

    private function getBestsellers()
    {
        $storeId = (int)Mage::app()->getStore()->getId();
        $this->clearBestProducts();
        $collection = Mage::getResourceModel('catalog/product_collection')->addAttributeToSelect('*');
        $date = new Zend_Date();
        $fromDate = Mage::getModel('core/date')->date('Y-M-d');
        $toDate = $date->setDay(Mage::getModel('core/date')->date('d') + $this->hours / 24)->getDate()->get('Y-MM-dd');
        $collection->getSelect()
            ->joinLeft(
                array('aggregation' => $collection->getResource()->getTable('sales/bestsellers_aggregated_monthly')),
                "e.entity_id = aggregation.product_id AND aggregation.store_id={$storeId} AND aggregation.period BETWEEN '{$fromDate}' AND '{$toDate}'",
                array('SUM(aggregation.qty_ordered) AS sold_quantity')
            )
            ->group('e.entity_id')
            ->order(array('sold_quantity DESC', 'e.created_at'));

        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);
        return $collection;
    }

    private function addToBestsellersCategory($productCollection, $categoryId)
    {
        $i = 0;
        foreach ($productCollection as $product) {
            $i++;
            if($i <= $this->numberOfProducts ){
                $productCategories = $product->getCategoryIds();
                //Mage::log(var_dump($productCategories), null, 'bestsellers.log', false);
                array_push($productCategories, $categoryId);
                try {
                    $product->setCategoryIds($productCategories)->save();
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }
        }
    }
}