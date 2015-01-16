<?php

/**
 * @category    Fiuze
 * @package     Fiuze_Deals
 * @author     Alena Tsareva <alena.tsareva@webinse.com>
 */
class Fiuze_Deals_Model_Observer
{
    const CRON_STRING_PATH = 'system/cron/cron_expr_heartbeat';

    /**
     *
     * @param Varien_Event_Observer $observer
     */
    public function catalogBlockProductListCollection(Varien_Event_Observer $observer)
    {
        $observer->getCollection()->addAttributeToSelect('*');

    }

    /**
     * Update cron product
     *
     * @return public
     */
    public static function dailyCatalogUpdate()
    {
        $productDeals = Mage::getResourceModel('fiuze_deals/deals_collection')
            ->addFilter('deals_active', 1)
            ->addOrder('sort_order', Varien_Data_Collection::SORT_ORDER_ASC)->getItems();
        $productActive = Mage::getResourceModel('fiuze_deals/deals_collection')
            ->addFilter('current_active', 1)->getSize();
        if (!$productActive) {
            $item = array_shift($productDeals);
            $item->setCurrentActive(1);
            $item->save();
            return;
        }
        //cyclical overkill
        foreach ($productDeals as $item) {
            if ($item->getCurrentActive()) {
                $item->setCurrentActive(0);
                $item->setEndTime(0);
                $item->save();
                $item = current($productDeals);
                $item->setCurrentActive(1);
                list($hh, $mm, $ss) = explode(",", Mage::helper('fiuze_deals')->getTimeCron());
                $mm += $hh * 60;
                $date = new Zend_Date();
                $date->add($mm, Zend_Date::MINUTE);
                $item->setEndTime($date);
                $item->save();
                return;
            }
        }
        return;
    }


    /**
     * Save cron config
     * @param Varien_Event_Observer $observer
     *
     * @return public
     */
    public function adminSystemConfigChanged(Varien_Event_Observer $observer)
    {
        if (strnatcasecmp($observer->getData('name'), 'admin_system_config_changed_section_fiuze_deals_cron_time')) {
            $timeCron = Mage::helper('fiuze_deals')->getTimeCron();
            list($hh, $mm, $ss) = explode(',', $timeCron);
            $mm += $hh * 60;

            $cronExprArray = array(
                intval($mm) ? '/' . intval($mm) : '*',         # Minute
                '*',                                           # Hour
                '*',                                           # Day of the Month
                '*',                                           # Month of the Year
                '*',                                           # Day of the Week
            );
            $cronExprString = join(' ', $cronExprArray);
            $cronExprString = '*' . $cronExprString;

            try {
                Mage::getModel('core/config_data')
                    ->load(self::CRON_STRING_PATH, 'path')
                    ->setValue($cronExprString)
                    ->setPath(self::CRON_STRING_PATH)
                    ->save();
            } catch (Exception $e) {
                Mage::throwException(Mage::helper('adminhtml')->__('Unable to save the cron expression.'));
            }

            // Overwrite products from the selected category (active product is not stored)
            $category = Mage::helper('fiuze_deals')->getCategoryCron();
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

            if (Mage::getResourceModel('fiuze_deals/deals_collection')->count()) {
                foreach (Mage::getResourceModel('fiuze_deals/deals_collection') as $item) {
                    $item->delete();
                }
            }

            $products = $currentCategory->getItems();
            foreach ($products as $product) {
                $productDeals = Mage::getModel('fiuze_deals/deals');
                $productDeals->setData('product_id', (int)$product->getEntityId());
                $productDeals->setData('category_id', (int)$category->getId());
                $productDeals->setData('product_name', $product->getName());
                $productDeals->setData('deals_price', (float)$product->getPrice());
                $productDeals->setData('deals_qty', (int)$product->getQty());
                $productDeals->setData('deals_active', true);
                $productDeals->setData('sort_order', 0);
                $productDeals->setData('current_active', 0);
                $productDeals->setData('origin_special_price', (float)$product->getSpecialPrice());
                try {
                    $productDeals->save();
                } catch (Exception $ex) {
                    Mage::log($ex->getMessage());
                }
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
        $categoryIds = $product->getCategoryIds();
        $categoryDeal = Mage::getResourceModel('fiuze_deals/deals_collection')
            ->addFieldToSelect('category_id')->getFirstItem()->getData();

        //verify the change category
        $countId = Mage::getResourceModel('fiuze_deals/deals_collection')
            ->addFilter('product_id', $product->getEntityId())
            ->addFieldToSelect('product_id')->count();
        if ($countId) {
            $intersect = array_intersect($categoryIds, $categoryDeal);
            if (!count($intersect)) {
                Mage::getModel('fiuze_deals/deals')
                    ->load($product->getEntityId(), 'product_id')
                    ->delete();
            }
        }

        $intersect = array_intersect($categoryIds, $categoryDeal);
        if (count($intersect)) {
            $productDeals = Mage::getModel('fiuze_deals/deals')->load($product->getEntityId(), 'product_id');
            $productDeals->setData('product_id', $product->getEntityId());
            $productDeals->setData('category_id', (int)$intersect[0]);
            $productDeals->setData('product_name', $product->getName());
            $productDeals->setData('deals_price', $product->getPrice());
            $productDeals->setData('deals_qty', (int)$product->getQty());
            $productDeals->setData('deals_active', true);
            $productDeals->setData('sort_order', 0);
            $productDeals->setData('current_active', 0);
            $productDeals->setData('origin_special_price', (float)$product->getSpecialPrice());
            try {
                $productDeals->save();
            } catch (Exception $ex) {
                Mage::logException($ex);
            }
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
        $productId = $product->getEntityId();

        try{
            $productDeals = Mage::getModel('fiuze_deals/deals')->load($productId, 'product_id');
            $productDeals->delete();
        }catch (Exception $ex){
            Mage::logException($ex);
        }

    }



}























