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
     * Retrieve best sellers array which contains product id and profit/revenue
     * 
     * @return array
     */
    public function getBestSellers() {
        //get order item by category
        $itemsOrderRow = array();
        foreach($this->getBestSellerCategoryConfig()->getValue() as $key => $item){
            $productCollection = Mage::getResourceModel('catalog/product_collection')
                ->setStoreId(Mage_Core_Model_App::ADMIN_STORE_ID)
                ->addCategoryFilter(Mage::getModel('catalog/category')->load($item['category']));
            $idProduct = array_keys ($productCollection->getItems());
            $itemsOrder = Mage::getResourceModel('sales/order_item_collection')
                ->addFieldToFilter('created_at', array('gteq' => $this->_getPeriod($key)))
                ->addFieldToFilter('parent_item_id', array('null' => true))
                ->addFieldToFilter('product_id', array('in' => $idProduct))
                ->getItems();
            $itemsOrderRow[$key]=$itemsOrder;
        }

        $bestSellersSlice = array();
        foreach($this->getBestSellerCategoryConfig()->getValue() as $key => $item) {
            $bestSellers = $this->_applyCriteria($key, $itemsOrderRow[$key]);
            //get slice of best sellers array using number of products option
            $configArray = $this->getBestSellerCategoryConfig()->getValue();
            $numberProduct = (int)$configArray[$key]['number_of_products'];
            $tmp = array_slice($bestSellers, 0, $numberProduct, true);
            $bestSellersSlice[$key] = array_keys($tmp);
        }

        //retrieve all keys from array
        return $bestSellersSlice;
    }

    /**
     * Returns the item's row total with any discount and also with any tax
     *
     * @param Mage_Sales_Model_Order_Item $item
     * @return float
     */
    protected function _getRowTotalWithDiscountInclTax(Mage_Sales_Model_Order_Item $item) {
        $tax          = ($item->getTaxAmount() ? $item->getTaxAmount() : 0);
        $baseRowTotal = ($item->getRowTotal() - $item->getDiscountAmount() + $tax);

        return (float) ($baseRowTotal);
    }

    /**
     * Retrieve sorted best sellers array using criteria
     * 
     * @param array $items
     * @return array
     */
    protected function _applyCriteria($keyRow, $items) {
        $configArray = $this->getBestSellerCategoryConfig()->getValue();
        $criteria = $configArray[$keyRow]['criteria'];
        switch($criteria){
            case 'revenue':
                $bestSellers = $this->_maxRevenue($keyRow, $items);
                break;
            case 'qty':
                $bestSellers = $this->_maxQty($keyRow, $items);
                break;
            case 'profit':
                $bestSellers = $this->_maxProfit($keyRow, $items);
                break;
        }

        $result = $this->_changeFormatArray($bestSellers);
        arsort($result);
        return $result;
    }
    /**
     * @param array $bestSellers
     * @return array
     */
    private function _changeFormatArray($bestSellers)
    {
        $result = array();
        for (reset($bestSellers); $key = key($bestSellers); next($bestSellers) ) {
            if($bestSellers[$key] instanceof Varien_Object){
                $profit = $bestSellers[$key]->getProfit();
                $parent = $bestSellers[$key]->getParent();
                if($result[$parent]){
                    $result[$parent] = ($result[$parent] > $profit) ? $result[$parent] : $profit;
                }else{
                    $result[$parent] = $profit;
                }
            }else{
                $result[$key] = $bestSellers[$key];
            }
        }
        return $result;
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
    protected function _maxRevenue($keyRow, $orderItems) {
        $configArray = $this->getBestSellerCategoryConfig()->getValue();
        $items = array();

        foreach ($orderItems as $orderItem) {
            $product = $orderItem->getProduct();
            if($product->getTypeId()=='configurable'){
                $productCollection = Mage::getResourceModel('catalog/product_collection')
                    ->setStoreId(Mage_Core_Model_App::ADMIN_STORE_ID)
                    ->addCategoryFilter(Mage::getModel('catalog/category')->load($configArray[$keyRow]['category']));
                $idProduct = array_keys ($productCollection->getItems());
                $itemsSimple = Mage::getResourceModel('sales/order_item_collection')
                    ->addFieldToFilter('created_at', array('gteq' => $this->_getPeriod($keyRow)))
                    ->addFieldToFilter('parent_item_id', array('eq' => $orderItem->getId()))
                    ->addFieldToFilter('product_id', array('in' => $idProduct))
                    ->getItems();
                foreach($itemsSimple as $simple){
                    $productSimple = $simple->getProduct();
                    $profit = (float) $this->_getRowTotalWithDiscountInclTax($orderItem);

                    if ($profit > 0) {
                        if(!is_null($productSimple->getId())){
                            if(!is_null($items[$productSimple->getId()])){
                                $items[$productSimple->getId()]->setParent($product->getId());
                                $items[$productSimple->getId()]->setProfit($items[$productSimple->getId()]->getProfit()+$profit);
                            }else{
                                $object = new Varien_Object();
                                $object->setParent($product->getId());
                                $object->setProfit($profit);
                                $items[$productSimple->getId()]=$object;
                            }
                        }
                    }
                }
            }else{
                if(!is_null($product->getId())){
                    $items[$product->getId()] += $this->_getRowTotalWithDiscountInclTax($orderItem);
                }
            }
        }

        return $items;
    }

    /**
     * Apply max qty criteria
     *
     * example array(
     *      [product ID] => sales_flat_order_item price (include discount and tax)
     * )
     *
     * @param array $orderItems
     * @return array
     */
    protected function _maxQty($keyRow, $orderItems) {
        $configArray = $this->getBestSellerCategoryConfig()->getValue();
        $items = array();

        foreach ($orderItems as $orderItem) {
            $product = $orderItem->getProduct();
            /**
             * @todo will be set real price if cost doesn't exist
             *          check cost in the live db
             */
            if($product->getTypeId()=='configurable'){
                $productCollection = Mage::getResourceModel('catalog/product_collection')
                    ->setStoreId(Mage_Core_Model_App::ADMIN_STORE_ID)
                    ->addCategoryFilter(Mage::getModel('catalog/category')->load($configArray[$keyRow]['category']));
                $idProduct = array_keys ($productCollection->getItems());
                $itemsSimple = Mage::getResourceModel('sales/order_item_collection')
                    ->addFieldToFilter('created_at', array('gteq' => $this->_getPeriod($keyRow)))
                    ->addFieldToFilter('parent_item_id', array('eq' => $orderItem->getId()))
                    ->addFieldToFilter('product_id', array('in' => $idProduct))
                    ->getItems();
                foreach($itemsSimple as $simple){
                    $productSimple = $simple->getProduct();
                    $qty = (float) $simple->getQtyOrdered();
                    if ($qty > 0) {
                        if(!is_null($productSimple->getId())){
                            if(!is_null($items[$productSimple->getId()])){
                                $items[$productSimple->getId()]->setParent($product->getId());
                                $items[$productSimple->getId()]->setProfit($items[$productSimple->getId()]->getProfit()+$qty);
                            }else{
                                $object = new Varien_Object();
                                $object->setParent($product->getId());
                                $object->setProfit($qty);
                                $items[$productSimple->getId()]=$object;
                            }
                        }
                    }
                }
            }else{
                $qty   = $orderItem->getQtyOrdered();
                if ($qty > 0) {
                    if(!is_null($product->getId())){
                        $items[$product->getId()] += $qty;
                    }
                }
            }
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
    protected function _maxProfit($keyRow, $orderItems) {
        $configArray = $this->getBestSellerCategoryConfig()->getValue();
        $items = array();

        foreach ($orderItems as $orderItem) {
            $product = $orderItem->getProduct();
            /**
             * @todo will be set real price if cost doesn't exist
             *          check cost in the live db
             */
            if($product->getTypeId()=='configurable'){
                $productCollection = Mage::getResourceModel('catalog/product_collection')
                    ->setStoreId(Mage_Core_Model_App::ADMIN_STORE_ID)
                    ->addCategoryFilter(Mage::getModel('catalog/category')->load($configArray[$keyRow]['category']));
                $idProduct = array_keys ($productCollection->getItems());
                $itemsSimple = Mage::getResourceModel('sales/order_item_collection')
                    ->addFieldToFilter('created_at', array('gteq' => $this->_getPeriod($keyRow)))
                    ->addFieldToFilter('parent_item_id', array('eq' => $orderItem->getId()))
                    ->addFieldToFilter('product_id', array('in' => $idProduct))
                    ->getItems();
                foreach($itemsSimple as $simple){
                    $productSimple = $simple->getProduct();
                    $cost   = ($productSimple->getCost()) ? $productSimple->getCost() : $productSimple->getPrice();
                    $profit = (float) ($this->_getRowTotalWithDiscountInclTax($orderItem) - $cost * $simple->getQtyOrdered());

                    if ($profit > 0) {
                        if(!is_null($productSimple->getId())){
                            if(!is_null($items[$productSimple->getId()])){
                                $items[$productSimple->getId()]->setParent($product->getId());
                                $items[$productSimple->getId()]->setProfit($items[$productSimple->getId()]->getProfit()+$profit);
                            }else{
                                $object = new Varien_Object();
                                $object->setParent($product->getId());
                                $object->setProfit($profit);
                                $items[$productSimple->getId()]=$object;
                            }
                        }
                    }
                }
            }else{
                $cost   = ($product->getCost()) ? $product->getCost() : $product->getPrice();
                $profit = (float) ($this->_getRowTotalWithDiscountInclTax($orderItem) - $cost) * $orderItem->getQtyOrdered();

                if ($profit > 0) {
                    if(!is_null($product->getId())){
                        $items[$product->getId()] += $profit;
                    }
                }
            }
        }

        return $items;
    }

    /**
     * Retrieve period using days and time
     * 
     * @return string
     */
    protected function _getPeriod($keyRow) {
        $configArray = $this->getBestSellerCategoryConfig()->getValue();
        $days = (int)$configArray[$keyRow]['days_period'];
        $time = $configArray[$keyRow]['time_period'];

        //calculate necessary period
        $timestamp = Mage::getModel('core/date')->timestamp();
        $period    = date('Y-m-d H:i:s', strtotime('-' . $days . ' days -' . $time[0] . ' hours -' . $time[1] . ' minutes -' . $time[2] . ' seconds', $timestamp));

        return $period;
    }

}
