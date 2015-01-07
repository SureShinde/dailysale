<?php

/**
 * Best Sellers Model
 *
 * @category   Fiuze
 * @package    Fiuze_Bestsellercron
 * @author     Alena Tsareva <alena.tsareva@webinse.com>
 */
class Fiuze_Bestsellercron_Model_Bestsellers extends Mage_Core_Model_Abstract {

    const XML_PATH_NUMBER_PRODUCTS = 'bestsellers_settings_sec/bestsellers_settings_grp/products';
    const XML_PATH_TIME_PERIOD     = 'bestsellers_settings_sec/bestsellers_settings_grp/time';
    const XML_PATH_DAYS_PERIOD     = 'bestsellers_settings_sec/bestsellers_settings_grp/days';
    const XML_PATH_CRITERIA        = 'bestsellers_settings_sec/bestsellers_settings_grp/criteria';

    private $_criteria;

    public function __construct() {
        $this->_criteria = Mage::getStoreConfig(self::XML_PATH_CRITERIA);
        parent::__construct();
    }

    /**
     * Retrieve bese sellers array which contains product id and profit/revenue
     * 
     * @return array
     */
    public function getBestSellers() {
        $items = Mage::getResourceModel('sales/order_item_collection')
                ->addFieldToFilter('created_at', array('gteq' => $this->_getPeriod()))
                ->addFieldToFilter('parent_item_id', array('null' => true));

        $bestSellers = $this->_applyCriteria($items);

        //get slice of best sellers array using number of products option
        $bestSellersSlice = array_slice($bestSellers, 0, Mage::getStoreConfig(self::XML_PATH_NUMBER_PRODUCTS), true);

        //retrieve all keys from array
        return array_keys($bestSellersSlice);
    }

    /**
     * Returns the item's row total with any discount and also with any tax
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return string
     */
    protected function _getRowTotalWithDiscountInclTax(Mage_Sales_Model_Order_Item $item) {
        $tax          = ($item->getTaxAmount() ? $item->getTaxAmount() : 0);
        $baseRowTotal = ($item->getRowTotal() - $item->getDiscountAmount() + $tax);

        return (float) ($baseRowTotal / $item->getQtyOrdered());
    }

    /**
     * Retrieve sorted best sellers array using criteria
     * 
     * @param array $items
     * @return array
     */
    protected function _applyCriteria($items) {
        $bestSellers = array();
        if ($this->_criteria == 'revenue') {
            $bestSellers = $this->_maxRevenue($items);
        } else {
            $bestSellers = $this->_maxProfit($items);
        }

        arsort($bestSellers);
        return $bestSellers;
    }

    /**
     * Apply max revenue criteria
     * 
     * example array(
     *      [product ID] => sales_flat_order_item price (include discount and tax)
     * )
     * 
     * @param array $orderItems
     * @return array
     */
    protected function _maxRevenue($orderItems) {
        $items = array();

        foreach ($orderItems as $orderItem) {
            $product                  = $orderItem->getProduct();
            $items[$product->getId()] = $this->_getRowTotalWithDiscountInclTax($orderItem);
        }

        return $items;
    }

    /**
     * Apply max profit criteria
     * 
     * example array(
     *      [product ID] => sales_flat_order_item price (include discount and tax) - product cost/real price
     * )
     * 
     * @param array $orderItems
     * @return array
     */
    protected function _maxProfit($orderItems) {
        $items = array();

        foreach ($orderItems as $orderItem) {
            $product = $orderItem->getProduct();

            /**
             * @todo will be set real price if cost doesn't exist
             *          check cost in the live db
             */
            $cost   = ($product->getCost()) ? $product->getCost() : $product->getPrice();
            $profit = (float) ($this->_getRowTotalWithDiscountInclTax($orderItem) - $cost);

            if ($profit > 0) {
                $items[$product->getId()] = $profit;
            }
        }

        return $items;
    }

    /**
     * Retrieve period using days and time
     * 
     * @return string
     */
    protected function _getPeriod() {
        $days = (int) Mage::getStoreConfig(self::XML_PATH_DAYS_PERIOD);
        $time = explode(',', Mage::getStoreConfig(self::XML_PATH_TIME_PERIOD));

        //calculate necessary period
        $timestamp = Mage::getModel('core/date')->timestamp();
        $period    = date('Y-m-d H:i:s', strtotime('-' . $days . ' days -' . $time[0] . ' hours -' . $time[1] . ' minutes -' . $time[2] . ' seconds', $timestamp));

        return $period;
    }

}
