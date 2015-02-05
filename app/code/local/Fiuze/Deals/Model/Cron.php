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
                $this->_setData($item, (float)$item->getData('deals_price'));

                return;
            }

            //cyclical overkill
            $count = 0;
            while($count < count($productDeals)){
                $item = current($productDeals);
                if($item->getCurrentActive()){

                    // set data for current item (remove element from rotation)
                    $this->_setData($item, (float)$item->getData('origin_special_price'), false, false);

                    // set data for next item (add element to rotation)
                    $item = next($productDeals);
                    if ($item === false){
                        $item = reset($productDeals);
                    }

                    $this->_setData($item, (float)$item->getData('deals_price'));

                    return;
                }

                next($productDeals);
                $count++;
            }
        } catch(Exception $e){
            Mage::logException($e);
        }

        return;
    }

    /**
     * Set special data to product and to rotation element
     *
     * @param $item
     * @param float $specialPrice
     * @param bool $endDate
     * @param int $isCurrent
     */
    protected function _setData($item, $specialPrice, $endDate = true, $isCurrent = 1){
        $product = Mage::getModel('catalog/product')->load($item->getData('product_id'));
        $productSpecialPrice = $product->getSpecialPrice();
        $product->setSpecialPrice($specialPrice);

        $item->setData('origin_special_price', $productSpecialPrice);
        $item->setEndTime((($endDate) ? Mage::helper('fiuze_deals')->getEndDealTime() : 0));
        $item->setCurrentActive($isCurrent);

        try{
            $product->save();
            $item->save();
        } catch(Exception $e){
            Mage::logException($e);
        }

        return;
    }

}























