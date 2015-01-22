<?php

/**
 * Cron
 *
 * @category    Fiuze
 * @package     Fiuze_Deals
 * @author      Webinse Team <info@webinse.com>
 */
class Fiuze_Deals_Model_Cron extends Mage_Core_Model_Abstract{

    /**
     * Update cron product
     *
     * @return public
     */
    public function dailyCatalogUpdate(){
        $dealResource = Mage::getResourceModel('fiuze_deals/deals_collection');
        $productDeals = $dealResource->addFilter('deals_active', 1)
            ->addFieldToFilter('deals_qty', array("gt" => 0))
            ->addOrder('sort_order', Varien_Data_Collection::SORT_ORDER_ASC)
            ->getItems();

        if(!$productDeals){
            return;
        }

        $productActive = $dealResource->addFilter('current_active', 1)->getSize();
        try{
            if(!$productActive){
                $item = array_shift($productDeals);
                $item->setCurrentActive(1);

                //set origin special price
                $product = Mage::getModel('catalog/product')->load($item->getData('product_id'));
                $product->setSpecialPrice((float)$item->getData('deals_price'));

                $item->setData('origin_special_price', $product->getSpecialPrice());
                $item->setEndTime(Mage::helper('fiuze_deals')->getEndDealTime());

                $product->save();
                $item->save();
                return;
            }

            //cyclical overkill
            foreach($productDeals as $item){
                if($item->getCurrentActive()){
                    $item->setCurrentActive(0);
                    $item->setEndTime(0);

                    // set origin special price
                    $this->_setActive($item);

                    // set next element to rotation
                    $item = current($productDeals);
                    $item->setCurrentActive(1);
                    $item->setEndTime(Mage::helper('fiuze_deals')->getEndDealTime());

                    //set deals special price
                    $this->_setActive($item);

                    return;
                }
            }
        } catch(Exception $e){
            Mage::logException($e);
        }

        return;
    }

    /**
     * Set special price to item
     *
     * @param $item
     */
    protected function _setActive($item){
        $product = Mage::getModel('catalog/product')->load($item->getData('product_id'));
        $product->setSpecialPrice((float)$item->getData('origin_special_price'));

        $item->setData('origin_special_price', $product->getSpecialPrice());
        $product->save();
        $item->save();
    }

}























