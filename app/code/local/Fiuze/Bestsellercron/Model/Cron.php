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

    private $_bestSellerCategory;

    public function __construct(){
        $this->_bestSellerCategory = Mage::getModel('catalog/category')->load(Mage::getStoreConfig(self::XML_PATH_CATEGORY));

        parent::__construct();
    }

    public function bestSellers(){
        if(!$this->_bestSellerCategory->getId()){
            Mage::log('Fiuze_Bestsellercron: Please choose category in the System->Configuration->Catalog->Fiuze Bestsellers Cron tab.');
            return false;
        }

        //set admin area if method run in the controller
        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

        $this->_clearBestSellerCategory();
        $bestSellersArray = Mage::getModel('bestsellercron/bestsellers')->getBestSellers();
        $this->_assignBestSellersToCategory($bestSellersArray);

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
        $productCollection = Mage::getResourceModel('catalog/product_collection')
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('entity_id', array('in' => $bestSellers))
            ->getItems();

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
