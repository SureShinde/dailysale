<?php

/**
 * Observer
 *
 * @category    Fiuze
 * @package     Fiuze_Deals
 * @author      Webinse Team <info@webinse.com>
 */
class Fiuze_Deals_Model_Observer
{

    const CRON_STRING_PATH = 'fiuze_deals_cron_time/fiuze_deals_cron_time_grp/scheduler';

    /**
     * Change Deal Quantity for deals product rotation in the frontend
     *
     * @param Varien_Event_Observer $observer
     */
    public function fiuzeDealsSaveAfter(Varien_Event_Observer $observer)
    {
        $object = $observer->getObject();
        if ($object instanceof Fiuze_Deals_Model_Deals) {
            $dealsQty = $object->getData('deals_qty');
            $dealsActive = $object->getData('current_active');
            if (!$dealsQty && $dealsActive) {
                Mage::getModel('fiuze_deals/cron')->dailyCatalogUpdate();
                return;
            }
            $productActive = Mage::getResourceModel('fiuze_deals/deals_collection')->addFilter('current_active', 1)->getSize();
            if (!$productActive) {
                Mage::getModel('fiuze_deals/cron')->dailyCatalogUpdate();
                return;
            }
        }
    }

    /**
     * Initial timer for deals product rotation in the frontend
     *
     * @param Varien_Event_Observer $observer
     */
    public function controllerActionLayoutGenerateBlocksAfter(Varien_Event_Observer $observer)
    {
        //limit to the product view page
        if ($observer->getAction() instanceof Mage_Catalog_ProductController) {
            $idProduct = (int)$observer->getAction()->getRequest()->getParam('id');
            $productActive = Mage::getResourceModel('fiuze_deals/deals_collection')
                ->addFilter('product_id', $idProduct)
                ->addFilter('current_active', 1)
                ->getFirstItem();
            if ($productActive->getData()) {
                $layout = $observer->getLayout();
                $pageHead = $layout->getBlock('head');
                $pageHead->removeItem('skin_js', 'js/product.js');

                $pageBlockContent = $layout->getBlock('content');
                $blockScriptProduct = $layout->createBlock('core/template', 'scriptproduct')
                    ->setTemplate('fiuze/deals/product_deal.phtml');
                $pageBlockContent->append($blockScriptProduct);
            }
        }

    }

    /**
     * Initial route name for deals rotation in the frontend
     *
     * @param Varien_Event_Observer $observer
     */
    public function initControllerRouters(Varien_Event_Observer $observer)
    {
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
    public function adminSystemConfigChanged(Varien_Event_Observer $observer)
    {
        if (strnatcasecmp($observer->getData('name'), 'admin_system_config_changed_section_fiuze_deals_cron_time')) {
            $helper = Mage::helper('fiuze_deals');
            $cronExprString = $helper->getCronExpr();

            try {
                Mage::getModel('core/config_data')
                    ->load(self::CRON_STRING_PATH, 'path')
                    ->setValue($cronExprString)
                    ->setPath(self::CRON_STRING_PATH)
                    ->save();

                $this->_clearCache();
            } catch (Exception $e) {
                Mage::logException($e);
                Mage::throwException(Mage::helper('adminhtml')->__('Unable to save the cron expression.'));
            }

            // Overwrite products from the selected category (active product is not stored)
            $category = $helper->getCategoryCron();

            if (Mage::getResourceModel('fiuze_deals/deals_collection')->count()) {
                foreach (Mage::getResourceModel('fiuze_deals/deals_collection') as $item) {
                    if ($item->getCategoryId() == $category->getId()) {
                        return true;
                    }

                    $item->delete();
                }
            }

            $currentCategory = $category->getProductCollection();
            $currentCategory->addFieldToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)//Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG

                ->joinField(
                    'qty',
                    'cataloginventory/stock_item',
                    'qty',
                    'product_id=entity_id',
                    '{{table}}.stock_id=1',
                    'left'
                )
                ->joinField(
                    'is_in_stock',
                    'cataloginventory/stock_item',
                    'is_in_stock',
                    'product_id=entity_id',
                    '{{table}}.is_in_stock=1',
                    'left'
                )
                ->joinField(
                    'position_product',
                    'catalog/category_product',
                    'position',
                    'product_id=entity_id',
                    'at_position_product.category_id=cat_pro.category_id',
                    'left'
                )
                ->addAttributeToFilter('is_in_stock', array("notnull" => 'is_in_stock'))
                ->addAttributeToFilter('visibility', array( 'nin' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE))
                ->addAttributeToFilter('qty', array("gt" => 0))
                ->addAttributeToSelect('*');

            $products = $currentCategory->getItems();
            foreach ($products as $product) {
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
    public function catalogProductSaveAfter(Varien_Event_Observer $observer)
    {
        $product = $observer->getProduct();

        //if save admin/catalog_product/edit
        $this->_changeQtyStock($product);

        $this->_changeSpecialPrice($product);

        //if change catalog product (del)
        $paramTab = Mage::app()->getRequest()->getParam('tab');
        if (isset($paramTab) && $paramTab == 'product_info_tabs_categories') {
            $categoryIds = $product->getCategoryIds();
            $categoryDeal = Mage::helper('fiuze_deals')->getCategoryCron();
            $inArray = in_array($categoryDeal->getId(), $categoryIds);
            $productDeals = Mage::getModel('fiuze_deals/deals')->load($product->getEntityId(), 'product_id');
            try {
                if ($inArray) {
                    if (!$productDeals->getData()) {
                        $this->_saveProduct($product, array('category_id' => (int)$categoryDeal->getId()));
                        $productDeals->save();
                    }
                } else {
                    $productDeals->delete();
                }
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }

    //return qty one of request
    private function _changeQtyStock($product)
    {
        if (!Mage::registry('product_qty_request')) {
            Mage::register('product_qty_request', 1);
            $productParam = Mage::app()->getRequest()->getParam('product');
            $idProduct = (int)Mage::app()->getRequest()->getParam('id');
            $productActive = Mage::getResourceModel('fiuze_deals/deals_collection')
                ->addFilter('product_id', $idProduct)
                ->addFilter('current_active', 1)
                ->getFirstItem();
            //if change product is active
            if ($productActive->getData()) {
                $qty = $productParam['stock_data']['qty'];
                $isInStock = $productParam['stock_data']['is_in_stock'];
                if($isInStock){
                    if ($isInStock == 0) {
                        Mage::getModel('fiuze_deals/cron')->dailyCatalogUpdate();
                    }
                }
            }
        }
    }

    /**
     * Delete product in category
     * @param Varien_Event_Observer $observer
     *
     * @return boolean
     */
    public function catalogProductDeleteAfter(Varien_Event_Observer $observer)
    {
        $product = $observer->getProduct();

        try {
            Mage::getModel('fiuze_deals/deals')->load($product->getEntityId(), 'product_id')
                ->delete();
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }


    /**
     * Set deal_qty attribute when create order
     *
     * @param Varien_Event_Observer $observer
     */
    public function salesOrderPlaceAfter(Varien_Event_Observer $observer)
    {
        $order = $observer->getOrder();

        foreach ($order->getItemsCollection() as $item) {
            $productDeals = Mage::getModel('fiuze_deals/deals')->load($item->getProductId(), 'product_id');
            if ($productDeals->getData()) {
                try {
                    $productDeals->setData('deals_qty', ($productDeals->getDealsQty() - (int)$item->getQtyOrdered()))
                        ->save();
                    Mage::dispatchEvent('fiuze_deals_save_after', array('object' => $productDeals));
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }
        }
    }

    /**
     * Clear magento cache
     */
    protected function _clearCache()
    {
        Mage::app()->cleanCache();
    }

    protected function _saveProduct($product, $params)
    {
        $productDeals = Mage::getModel('fiuze_deals/deals')->load($product->getEntityId(), 'product_id');
        $productDeals->setData('product_id', (int)$product->getEntityId());
        $productDeals->setData('category_id', (isset($params['category_id'])) ? $params['category_id'] : 0);
        $productDeals->setData('product_name', $product->getName());
        $productDeals->setData('deals_price', (float)$product->getPrice());
        $productDeals->setData('deals_qty', (int)$product->getQty());
        $productDeals->setData('deals_active', false);
        $productDeals->setData('sort_order', $product->getPositionProduct());
        $productDeals->setData('current_active', 0);
        $productDeals->setData('origin_special_price', (float)$product->getSpecialPrice());
        try {
            $productDeals->save();
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     *
     *
     * @param Varien_Event_Observer $observer
     */
    public function cataloginventoryStockItemSaveAfter(Varien_Event_Observer $observer)
    {
        $item = $observer->getItem();
        $qtyCorrection = $item->getQtyCorrection();
        $productId = $item->getProductId();

        if ($qtyCorrection < 0) {
            $productDeals = Mage::getModel('fiuze_deals/deals')->load($productId, 'product_id');
            if ($productDeals->getData()) {
                if($item->getQty() < $productDeals->getDealsQty()){
                    $productDeals->setDealsQty($productDeals->getDealsQty() + $qtyCorrection);
                    if ($productDeals->getDealsQty() <= 0) {
                        $productDeals->setDealsQty(0);
                        try {
                            $productDeals->save();
                            Mage::dispatchEvent('fiuze_deals_save_after', array('object' => $productDeals));
                        } catch (Exception $e) {
                            Mage::logException($e);
                        }
                    }else{
                        try {
                            $productDeals->save();
                        } catch (Exception $e) {
                            Mage::logException($e);
                        }
                    }

                }
            }
        }
    }

    //Change origin_special_price in deals
    private function _changeSpecialPrice($product)
    {
        $productDeals = Mage::getResourceModel('fiuze_deals/deals_collection')
            ->addFilter('product_id', $product->getId())
            ->getFirstItem();
        if ($productDeals->getData()) {
            try{
                $productDeals->setData('origin_special_price', $product->getData('special_price'));
                $productDeals->save();
            }catch (Exception $ex){
                Mage::logException($ex);
            }
        }
    }

    /**
     * The deal quantity check product cart add
     * @param Varien_Event_Observer $observer
     */
    public function productCartAdd(Varien_Event_Observer $observer)
    {
        $quoteItem = $observer->getEvent()->getItem();
        $qty = $quoteItem->getQty();
        $stockItem = $quoteItem->getProduct()->getStockItem();
        $productId = $stockItem->getProductId();


        $result = $this->checkQuoteItemQty($productId, $qty);
        if ($result->getHasError()) {

            $quoteItem->addErrorInfo(
                'cataloginventory',
                Mage_CatalogInventory_Helper_Data::ERROR_QTY,
                $result->getMessage()
            );

            $quoteItem->getQuote()->addErrorInfo(
                $result->getQuoteMessageIndex(),
                'cataloginventory',
                Mage_CatalogInventory_Helper_Data::ERROR_QTY,
                $result->getQuoteMessage()
            );
        }
    }


    public function checkQuoteItemQty($productId, $qty)
    {
        $productDeals = Mage::getResourceModel('fiuze_deals/deals_collection')
            ->addFilter('product_id', $productId)
            ->addFilter('current_active', true)
            ->getFirstItem();

        $result = new Varien_Object();
        $result->setHasError(false);
        if ($productDeals->getData()) {
            $qtyForCheck = $productDeals->getData('deals_qty');
            if ($qty > $qtyForCheck) {
                $result->setHasError(true)
                    ->setMessage(
                        Mage::helper('cataloginventory')->__('The minimum quantity allowed for purchase is %s.', $qtyForCheck * 1)
                    )
                    ->setErrorCode('qty_min')
                    ->setQuoteMessage(Mage::helper('cataloginventory')->__('Some of the products cannot be ordered in requested quantity.'))
                    ->setQuoteMessageIndex('qty');
                return $result;
            }
        }
        return $result;
    }
}























