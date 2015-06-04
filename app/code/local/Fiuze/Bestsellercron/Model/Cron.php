<?php

/**
 * Best Sellers Cron Model
 *
 * @category   Fiuze
 * @package    Fiuze_Bestsellercron
 * @author     Alena Tsareva <alena.tsareva@webinse.com>
 */
class Fiuze_Bestsellercron_Model_Cron extends Mage_Core_Model_Abstract{

    const XML_PATH_CATEGORY = 'bestsellers_settings_sec/bestsellers_settings_grp/category';
    const XML_PATH_CATEGORY_FORM = 'bestsellers_settings_sec/bestsellers_settings_grp/general';

    private $_bestSellerCategory;
    private $_bestSellerCategoryRowId;
    private $_bestSellerCategoryConfig;

    public function __construct(){
        $this->_bestSellerCategory = Mage::getModel('catalog/category')->load(Mage::getStoreConfig(Fiuze_Bestsellercron_Model_Bestsellers::XML_PATH_BESTSELLER_CATEGORY));
        $this->_bestSellerCategoryConfig = Mage::getModel('bestsellercron/system_config_backend_general')
            ->load(self::XML_PATH_CATEGORY_FORM, 'path');
        $this->_bestSellerCategoryRowId = Mage::getStoreConfig(Fiuze_Bestsellercron_Model_Bestsellers::XML_PATH_BESTSELLER_ROWID);
        parent::__construct();
    }

    public function bestSellers($arguments){
        if($arguments instanceof Mage_Cron_Model_Schedule){
            if(!$this->_bestSellerCategoryConfig->getValue()){
                Mage::log('Fiuze_Bestsellercron: Please choose _bestSellerCategoryConfig in the System->Configuration->Catalog->Fiuze Bestsellers Cron tab.');
                return false;
            }
            if(!$this->_bestSellerCategory->getId()){
                Mage::log('Fiuze_Bestsellercron: Please choose category in the System->Configuration->Catalog->Fiuze Bestsellers Cron tab.');
                return false;
            }
            $jobCode = $arguments->getJobCode();
            $bestSellerConfig = $this->_bestSellerCategoryConfig;
        if(!is_null($bestSellerConfig)) {
            //set admin area if method run in the controller
            Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
            $valueArray = $bestSellerConfig->getValue();
            if(array_key_exists($jobCode, $valueArray)) {
                //if category bestseller
                if ($jobCode == $this->_bestSellerCategoryRowId) {
                    $currentConfig = $valueArray[$jobCode];
                    $bestsellersModel = Mage::getModel('bestsellercron/bestsellers')->setCurrentConfig($currentConfig);
                    $bestSellersArray =$bestsellersModel->getBestSellers();

                    //$this->_clearBestSellerCategory();
                    $this->_assignBestSellersToCategory($bestSellersArray);
                    $this->_sortCategoryConfig($bestSellersArray, $currentConfig);
                }else{
                    $currentConfig = $valueArray[$jobCode];
                    $bestsellersModel = Mage::getModel('bestsellercron/bestsellers')->setCurrentConfig($currentConfig);
                    $bestSellersArray = $bestsellersModel->getBestSellers();
                    $this->_sortCategoryConfig($bestSellersArray, $currentConfig);
                }
            }

        }

        }

        return true;
    }

    /**
     * Sorted category by bestSeller
     * @param $bestSellers
     * @param $currentConfig
     * @return bool
     * @throws Exception
     */
    protected function _sortCategoryConfig($bestSellers, $currentConfig){
        $flipped_arr = array_flip($bestSellers);
        $maxValue = max($flipped_arr) + 1;
        $category = Mage::getModel('catalog/category')->setStoreId(Mage_Core_Model_App::ADMIN_STORE_ID)->load($currentConfig['category']);

        $productCollection = Mage::getResourceModel('catalog/product_collection')
            ->setStoreId(Mage_Core_Model_App::ADMIN_STORE_ID)
            ->addCategoryFilter($category);
        $idProduct = array_keys ($productCollection->getItems());
        $idProduct = array_flip($idProduct);
        $idProduct = array_fill_keys(array_keys($idProduct), $maxValue);

        for (reset($idProduct); $key = key($idProduct); next($idProduct) ) {
            if(array_key_exists($key, $flipped_arr)){
                $idProduct[$key] = $flipped_arr[$key];
            }
        }

        $category->setPostedProducts($idProduct);
        $category->save();
        return true;
    }

    /**
     * Remove all products from best seller category
     *
     * @return boolean
     */
    protected function _clearBestSellerCategory(){
        $productCollection = Mage::getResourceModel('catalog/product_collection')
            ->addAttributeToFilter('bestsellercron_flag', true)
            ->addCategoryFilter($this->_bestSellerCategory);

        foreach($productCollection as $product){
            $categoryIds = $product->getCategoryIds();
            $categoryKey = array_search($this->_bestSellerCategory->getId(), $categoryIds);

            if($categoryKey === FALSE){
                continue;
            }

            unset($categoryIds[$categoryKey]);
            try{
                $product->setCategoryIds($categoryIds)
                    ->setBestsellercronFlag(false)
                    ->save();
            } catch(Exception $e){
                Mage::logException($e);
            }
        }

        return true;
    }

    /**
     * Assign all new best sellers to category
     *
     * @param array $bestSellers
     * @return boolean
     */
    protected function _assignBestSellersToCategory($bestSellers){
        $productCollectionResource = Mage::getResourceModel('catalog/product_collection');
        $orValueArray = array();
        foreach($bestSellers as $item) {
            array_push($orValueArray, array('in' => $item));
        }
        $productCollectionResource->addAttributeToFilter('entity_id', $orValueArray);
        $productCollectionResource->addAttributeToSelect('*');
        $productCollection = $productCollectionResource->getItems();

        foreach($productCollection as $product){
            $categoryIds = $product->getCategoryIds();
            array_push($categoryIds, $this->_bestSellerCategory->getId());

            try{
                $product->setCategoryIds($categoryIds)
                    ->setBestsellercronFlag(true)
                    ->save();
            } catch(Exception $e){
                Mage::logException($e);
            }
        }

        Mage::app()->setCurrentStore(Mage::getModel('core/store')->load(Mage_Core_Model_App::ADMIN_STORE_ID));
        $category = Mage::getModel('catalog/category')->setStoreId(Mage_Core_Model_App::ADMIN_STORE_ID)->load($this->_bestSellerCategory->getId());

        $flipped_arr = array_flip($bestSellers);
        $category->setPostedProducts($flipped_arr);
        $category->save();

        return true;
    }

}
