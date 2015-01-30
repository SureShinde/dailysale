<?php

/**
 * Observer
 *
 * @category    Fiuze
 * @package     Fiuze_Deals
 * @author      Webinse Team <info@webinse.com>
 */
class Fiuze_Deals_Model_Observer{

    const CRON_STRING_PATH = 'fiuze_deals_cron_time/fiuze_deals_cron_time_grp/scheduler';

    /**
     * Initial route name for deals rotation in the frontend
     *
     * @param Varien_Event_Observer $observer
     */
    public function initControllerRouters(Varien_Event_Observer $observer){
        $front = $observer->getEvent()->getFront();
        $object = new Fiuze_Deals_Controller_Router();
        $front->addRouter('blowout', $object);
    }

    /**
     * Save deal rotation config
     * add product from category to deal
     *
     * @param Varien_Event_Observer $observer
     *
     * @return public
     */
    public function adminSystemConfigChanged(Varien_Event_Observer $observer){
        if(strnatcasecmp($observer->getData('name'), 'admin_system_config_changed_section_fiuze_deals_cron_time')){
            $helper = Mage::helper('fiuze_deals');
            $cronExprString = $helper->getCronExpr();

            try{
                Mage::getModel('core/config_data')
                    ->load(self::CRON_STRING_PATH, 'path')
                    ->setValue($cronExprString)
                    ->setPath(self::CRON_STRING_PATH)
                    ->save();

                $this->_clearCache();
            } catch(Exception $e){
                Mage::logException($e);
                Mage::throwException(Mage::helper('adminhtml')->__('Unable to save the cron expression.'));
            }

            // Overwrite products from the selected category (active product is not stored)
            $category = $helper->getCategoryCron();

            if(Mage::getResourceModel('fiuze_deals/deals_collection')->count()){
                foreach(Mage::getResourceModel('fiuze_deals/deals_collection') as $item){
                    if ($item->getCategoryId() == $category->getId()){
                        return true;
                    }

                    $item->delete();
                }
            }

            $currentCategory = $category->getProductCollection();
            $currentCategory->addFieldToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
                ->joinField(
                    'qty',
                    'cataloginventory/stock_item',
                    'qty',
                    'product_id=entity_id',
                    '{{table}}.stock_id=1',
                    'left'
                )
                ->addAttributeToFilter('qty', array("gt" => 0))
                ->addAttributeToSelect('*');

            $products = $currentCategory->getItems();
            foreach($products as $product){
                $this->_saveProduct($product, array('category_id' => (int)$category->getId()));
            }
        }

        return true;
    }

    /**
     * Add new product in category
     * @param Varien_Event_Observer $observer
     *
     * @return boolean
     */
    public function catalogProductSaveAfter(Varien_Event_Observer $observer){
        $product = $observer->getProduct();
        $categoryIds = $product->getCategoryIds();
        $categoryDeal = Mage::getResourceModel('fiuze_deals/deals_collection')
            ->addFieldToSelect('category_id')->getFirstItem()->getData();

        //verify the change category
        $countId = Mage::getResourceModel('fiuze_deals/deals_collection')
            ->addFilter('product_id', $product->getEntityId())
            ->addFieldToSelect('product_id')->count();
        if($countId){
            $intersect = array_intersect($categoryIds, $categoryDeal);
            if(!count($intersect)){
                Mage::getModel('fiuze_deals/deals')
                    ->load($product->getEntityId(), 'product_id')
                    ->delete();
            }
        }

        $intersect = array_intersect($categoryIds, $categoryDeal);
        if(count($intersect)){
            $this->_saveProduct($product, array('category_id' => (int)$intersect[0]));
        }
    }

    /**
     * Delete product in category
     * @param Varien_Event_Observer $observer
     *
     * @return boolean
     */
    public function catalogProductDeleteAfter(Varien_Event_Observer $observer){
        $product = $observer->getProduct();

        try{
            Mage::getModel('fiuze_deals/deals')->load($product->getEntityId(), 'product_id')
                ->delete();
        } catch(Exception $e){
            Mage::logException($e);
        }
    }


    /**
     * Set deal_qty attribute when create order
     *
     * @param Varien_Event_Observer $observer
     */
    public function salesOrderPlaceAfter(Varien_Event_Observer $observer){
        $order = $observer->getOrder();

        foreach($order->getItemsCollection() as $item){
            $productDeals = Mage::getModel('fiuze_deals/deals')->load($item->getProductId(), 'product_id');
            if($productDeals->getData()){
                try{
                    $productDeals->setData('deals_qty', ($productDeals->getDealsQty() - (int)$item->getQtyOrdered()))
                        ->save();
                } catch(Exception $e){
                    Mage::logException($e);
                }
            }
        }
    }

    /**
     * Clear magento cache
     */
    protected function _clearCache(){
        Mage::app()->cleanCache();
    }

    protected function _saveProduct($product, $params){
        $productDeals = Mage::getModel('fiuze_deals/deals')->load($product->getEntityId(), 'product_id');
        $productDeals->setData('product_id', (int)$product->getEntityId());
        $productDeals->setData('category_id', (isset($params['category_id'])) ? $params['category_id'] : 0);
        $productDeals->setData('product_name', $product->getName());
        $productDeals->setData('deals_price', (float)$product->getPrice());
        $productDeals->setData('deals_qty', (int)$product->getQty());
        $productDeals->setData('deals_active', false);
        $productDeals->setData('sort_order', 0);
        $productDeals->setData('current_active', 0);
        $productDeals->setData('origin_special_price', (float)$product->getSpecialPrice());
        try{
            $productDeals->save();
        } catch(Exception $e){
            Mage::logException($e);
        }
    }

}























