<?php

/**
 * @category    Fiuze
 * @package     Fiuze_Deals
 * @author     Alena Tsareva <alena.tsareva@webinse.com>
 */

class Fiuze_Deals_Model_Observer{
    const CRON_STRING_PATH  = 'system/cron/cron_expr_heartbeat';

    /**
     *
     * @param Varien_Event_Observer $observer
     */
    public function catalogBlockProductListCollection(Varien_Event_Observer $observer){
        $observer->getCollection()->addAttributeToSelect('*');

    }

    /**
     * Update cron catalog
     *
     * @return public
     */
    public function dailyCatalogUpdate(){

        return true;
    }


    /**
     * Save cron config
     * @param Varien_Event_Observer $observer
     *
     * @return public
     */
    public function adminSystemConfigChanged(Varien_Event_Observer $observer){
        if(strnatcasecmp($observer->getData('name'),'admin_system_config_changed_section_fiuze_deals_cron_time')){
            $timeCron=Mage::helper('fiuze_deals')->getTimeCron();
            list($hh, $mm, $ss) = explode(',', $timeCron);
            $mm = $hh * 60 + $mm;

            $cronExprArray = array(
                intval($mm)? '/'.intval($mm):'*',              # Minute
                '*',                                           # Hour
                '*',                                           # Day of the Month
                '*',                                           # Month of the Year
                '*',                                           # Day of the Week
            );
            $cronExprString = join(' ', $cronExprArray);
            $cronExprString='*'.$cronExprString;

            try {
                Mage::getModel('core/config_data')
                    ->load(self::CRON_STRING_PATH, 'path')
                    ->setValue($cronExprString)
                    ->setPath(self::CRON_STRING_PATH)
                    ->save();
            } catch (Exception $e) {
                Mage::throwException(Mage::helper('adminhtml')->__('Unable to save the cron expression.'));
            }

            //Перезаписать продукты из выбраной категории (активный продукт не сохраняется)
            // Overwrite products from the selected category (active product is not stored)
            $category = Mage::helper('fiuze_deals')->getCategoryCron();
            $currentCategory = $category->getProductCollection();
            $currentCategory->addFieldToFilter('status',Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
                ->joinField(
                    'qty',
                    'cataloginventory/stock_item',
                    'qty',
                    'product_id=entity_id',
                    '{{table}}.stock_id=1',
                    'left'
                )
                ->addAttributeToSelect('*');

            if(Mage::getResourceModel('fiuze_deals/deals_collection')->count()){
                foreach(Mage::getResourceModel('fiuze_deals/deals_collection') as $item){
                    $item ->delete();
                }
            }

            $products = $currentCategory->getItems();
            foreach($products as $product){
                $productDeals = Mage::getModel('fiuze_deals/deals');
                $productDeals->setProductId($product->getEntityId());
                $productDeals->setCategoryId($category->getId());
                $productDeals->setProductName($product->getName());
                $productDeals->setDealsPrice($product->getPrice());
                $productDeals->setDealsQty($product->getQty());
                $productDeals->setDealsActive(false);
                try{
                    $productDeals->save();
                }catch (Exception $ex){
                    Mage::log($ex->getMessage());
                }
            }
        }

        return true;
    }

}























