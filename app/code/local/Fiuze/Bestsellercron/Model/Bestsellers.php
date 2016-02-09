<?php

/**
 * Best Sellers Model
 *
 * @category   Fiuze
 * @package    Fiuze_Bestsellercron
 * @author     Alena Tsareva <alena.tsareva@webinse.com>
 */
class Fiuze_Bestsellercron_Model_Bestsellers extends Mage_Core_Model_Abstract {
    const XML_PATH_GENERAL = 'bestsellers_settings_sec/bestsellers_settings_grp/general';
    const XML_PATH_NUMBER_PRODUCTS = 'bestsellers_settings_sec/bestsellers_settings_grp/products';
    const XML_PATH_TIME_PERIOD     = 'bestsellers_settings_sec/bestsellers_settings_grp/time';
    const XML_PATH_DAYS_PERIOD     = 'bestsellers_settings_sec/bestsellers_settings_grp/days';
    const XML_PATH_CRITERIA        = 'bestsellers_settings_sec/bestsellers_settings_grp/criteria';
    const XML_PATH_BESTSELLER_CATEGORY = 'bestsellers_settings_sec/bestsellers_settings_grp/bestseller_category';
    const XML_PATH_BESTSELLER_ROWID = 'bestsellers_settings_sec/bestsellers_settings_grp/bestseller_rowid';

    private $_criteria;
    /** Id Product For Category
     * @var array
     */
    private $idProductForCategory = array();

    public function __construct() {
        $this->_criteria = Mage::getStoreConfig(self::XML_PATH_CRITERIA);
        parent::__construct();
    }

    protected function _getItemsOrder($period, $categoryId, $fullCategory = false){
        if(!$fullCategory){
            $idProduct = $this->_getIdProductForCategory($categoryId,true);
        }else{
            $idProduct = $this->_getIdProductForCategory(false,true);
        }
        $itemsOrder = Mage::getResourceModel('sales/order_item_collection')
            ->addFieldToFilter('created_at', array('gteq' => $period))
            ->addFieldToFilter('parent_item_id', array('null' => true))
            ->addFieldToFilter('product_id', array('in' => $idProduct))
            ->getItems();
        return $itemsOrder;
    }

    protected function _getItemsMissing($categoryId, $productOrderId){
        $productCollection = Mage::getResourceModel('catalog/product_collection')
            ->setStoreId(Mage_Core_Model_App::ADMIN_STORE_ID)
            ->addCategoryFilter(Mage::getModel('catalog/category')->load($categoryId));
        $idProduct = array_keys ($productCollection->getItems());
        $idProduct = array_diff($idProduct, $productOrderId);
        return $idProduct;
    }

    /**
     * Retrieve best sellers array which contains product id and profit/revenue
     * 
     * @return array
     */
    public function getBestSellers() {
        //get order item by category
        $item = $this->getCurrentConfig();
        //if category bestseller
        if($this->getBestSellersCategory()){
            $itemsOrder = $this->_getItemsOrder($this->_getPeriod(), $item['category'], true);
        }else{
            $itemsOrder = $this->_getItemsOrder($this->_getPeriod(), $item['category'], false);
        }
        $bestSellers = $this->_applyCriteria($itemsOrder, $this->_getPeriod());
        //get slice of best sellers array using number of products option
        $numberProduct = (int)Fiuze_Bestsellercron_Model_Bestsellers::_getCountProducts($item);
        if($this->getBestSellersCategory()){
            $tmp = array_slice($bestSellers, 0, $numberProduct, true);
            $bestSellersSlice = array_keys($tmp);
            $merge = array_slice($bestSellersSlice, 0, $numberProduct, true);
        }else{
            $tmp = array_slice($bestSellers, 0, count($bestSellers), true);
            $bestSellersSlice = array_keys($tmp);
            $merge = array_slice($bestSellersSlice, 0, count($bestSellers), true);
        }
        //if result<$numberProduct
        if(count($merge) < $numberProduct || !$item['checkbox']){
            $isTimePeriodHistory = $item['history'];
            if($isTimePeriodHistory){
                //if category bestseller
                if($this->getBestSellersCategory()){
                    $itemsOrderHistory = $this->_getItemsOrder($this->_getPeriod($isTimePeriodHistory), $item['category'], true);
                }else{
                    $itemsOrderHistory = $this->_getItemsOrder($this->_getPeriod($isTimePeriodHistory), $item['category'], false);
                }
                $bestSellersHistory = $this->_applyCriteria($itemsOrderHistory, $this->_getPeriod($isTimePeriodHistory));
                //get slice of best sellers array using number of products option
                if($this->getBestSellersCategory()){
                    $tmp = array_slice($bestSellersHistory, 0, $numberProduct, true);
                    $bestSellersSliceHistory = array_keys($tmp);
                    $result = array_diff($bestSellersSliceHistory,$merge);
                    $mergeHistory = array_slice($result, 0, $numberProduct, true);
                    $mergeHistory = array_merge($merge, $mergeHistory);
                    $merge = array_slice($mergeHistory, 0, $numberProduct, true);
                }else{
                    if($item['checkbox']){
                        $tmp = array_slice($bestSellersHistory, 0, $item['count_products'], true);
                    }else{
                        $tmp = $bestSellersHistory;
                    }
                    $bestSellersSliceHistory = array_keys($tmp);
                    $result = array_diff($bestSellersSliceHistory,$merge);
                    $mergeHistory = array_slice($result, 0, count($bestSellers), true);
                    $mergeHistory = array_merge($merge, $mergeHistory);
                    if($item['checkbox']){
                        $merge = array_slice($mergeHistory, 0, $item['count_products'], true);
                    }else{
                        $merge = $mergeHistory;
                    }
                }
            }
        }
        return $merge;
    }

    /**
     * return Number of products for row
     * @param $config array
     * @return int
     */
    public static function _getCountProducts($config){
        $numberOfProducts = $config['number_of_products'];
        $value = 0;
        // if field "Search the store"
        if($config['checkbox']){
            $value = $numberOfProducts['count_products'];
        }else{
            if($numberOfProducts['checkbox'] != 'checked'){
                $value = $numberOfProducts['count_products'];
            }
        }
        return $value;
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
    protected function _applyCriteria($items, $period) {
        $config = $this->getCurrentConfig();
        $fullCategory = $this->getBestSellersCategory();
        $criteria = $config['criteria'];
        switch($criteria){
            case 'revenue':
                $bestSellers = $this->_maxRevenue($items, $fullCategory, $period);
                break;
            case 'qty':
                $bestSellers = $this->_maxQty($items, $fullCategory, $period);
                break;
            case 'profit':
                $bestSellers = $this->_maxProfit($items, $fullCategory, $period);
                break;
//            case 'price':
//                $bestSellers = $this->_priceFilter($items, $fullCategory, $period);
//                break;
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
    protected function _maxRevenue($orderItems, $fullCategory, $period) {
        $config = $this->getCurrentConfig();
        $items = array();

        foreach ($orderItems as $orderItem) {
            $product = $orderItem->getProduct();

                if(!is_null($product->getId() AND $orderItem->_data['created_at']>$this->_getPeriod())){
                    if(isset($items[$product->getId()])){
                        $items[$product->getId()] += $this->_getRowTotalWithDiscountInclTax($orderItem);
                    }else{
                        $items[$product->getId()] = $this->_getRowTotalWithDiscountInclTax($orderItem);
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
    protected function _maxQty($orderItems, $fullCategory, $period) {
        $config = $this->getCurrentConfig();
        $items = array();

        foreach ($orderItems as $orderItem) {
            $product = $orderItem->getProduct();

            if(!is_null($product->getId() AND $orderItem->_data['created_at']>$this->_getPeriod())){
                if(isset($items[$product->getId()])){
                    $items[$product->getId()] += $this->_getRowTotalWithDiscountInclTax($orderItem);
                }else{
                    $items[$product->getId()] = $this->_getRowTotalWithDiscountInclTax($orderItem);
                }
            }

        }

        return $items;
    }

    /** Id Product For Category
     * @var array
     */
    protected function _getIdProductForCategory($idCategory = false, $panch_flag=false) {
        if(!$idCategory){
            $id_product = null;
            if(!in_array(false, $this->idProductForCategory)){
//                $productCollection = Mage::getResourceModel('catalog/product_collection')
//                    ->setStoreId(Mage_Core_Model_App::ADMIN_STORE_ID);
//                $this->idProductForCategory["false"] = array_keys ($productCollection->getItems());

                #$this->idProductForCategory["false"] = Mage::getModel('sales/order')->getItemsCollection();
                $this->getCurrentConfig('days_period');

                $data_for_form = $this->getCurrentConfig('days_period')*86400;

                $usl = Mage::getModel('core/date')->timestamp(time()) - $data_for_form;
                $usl = date('Y-m-d h:i:s', $usl);
                $this->idProductForCategory["false"] = Mage::getModel('sales/order')->getItemsCollection()->addAttributeToFilter('created_at',array('gteq'=>$usl))->getData();

                foreach ($this->idProductForCategory["false"] as $item){
                    $id_product[] = $item['product_id'];
                }

            }
            /// ///
            if($panch_flag){
                //                $productCollection = Mage::getResourceModel('catalog/product_collection')
                //                    ->setStoreId(Mage_Core_Model_App::ADMIN_STORE_ID);
                //                $this->idProductForCategory["false"] = array_keys ($productCollection->getItems());

                #$this->idProductForCategory["false"] = Mage::getModel('sales/order')->getItemsCollection();
                $this->getCurrentConfig('days_period');

                $data_for_form = $this->getCurrentConfig('history')*86400;

                $usl = Mage::getModel('core/date')->timestamp(time()) - $data_for_form;
                $usl = date('Y-m-d h:i:s', $usl);
                $this->idProductForCategory["false"] = Mage::getModel('sales/order')->getItemsCollection()->addAttributeToFilter('created_at',array('gteq'=>$usl))->getData();

                foreach ($this->idProductForCategory["false"] as $item){
                    $id_product[] = $item['product_id'];
                }

            }
            if (!is_null($id_product)) {
                return $id_product;
            }else {
                return false;
            }
        }

        if(!$this->idProductForCategory[$idCategory]){
            $productCollection = Mage::getResourceModel('catalog/product_collection')
                ->setStoreId(Mage_Core_Model_App::ADMIN_STORE_ID);
            $productCollection->addCategoryFilter(Mage::getModel('catalog/category')->load($idCategory));
            $this->idProductForCategory[$idCategory] = array_keys ($productCollection->getItems());
        }
        return $this->idProductForCategory[$idCategory];
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
    protected function _maxProfit($orderItems, $fullCategory, $period) {
        $config = $this->getCurrentConfig();
        $items = array();

        foreach ($orderItems as $orderItem) {
            $product = $orderItem->getProduct();

            if(!is_null($product->getId() AND $orderItem->_data['created_at']>$this->_getPeriod())){
                if(isset($items[$product->getId()])){
                    $items[$product->getId()] += $this->_getRowTotalWithDiscountInclTax($orderItem);
                }else{
                    $items[$product->getId()] = $this->_getRowTotalWithDiscountInclTax($orderItem);
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
    protected function _getPeriod($day = null) {
        $config = $this->getCurrentConfig();
        $days = (int)$config['days_period'];
        $time = $config['time_period'];

        if(!is_null($day)){
            $days = $day;
            $time[0] = 0;
            $time[1] = 0;
            $time[2] = 0;
        }

        //calculate necessary period
        $timestamp = Mage::getModel('core/date')->timestamp();
        $period    = date('Y-m-d H:i:s', strtotime('-' . $days . ' days -' . $time[0] . ' hours -' . $time[1] . ' minutes -' . $time[2] . ' seconds', $timestamp));

        return $period;
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
    protected function _priceFilter($orderItems, $fullCategory, $period) {
        $config = $this->getCurrentConfig();
        $items = array();

        foreach ($orderItems as $orderItem) {
            $product = $orderItem->getProduct();
            if($product->getTypeId()=='configurable'){
                if(!$fullCategory){
                    $idProduct = $this->_getIdProductForCategory($config['category']);
                }else{
                    $idProduct = $this->_getIdProductForCategory();
                }

                $itemsSimple = Mage::getResourceModel('sales/order_item_collection')
                    ->addFieldToFilter('created_at', array('gteq' => $period))
                    ->addFieldToFilter('parent_item_id', array('eq' => $orderItem->getId()))
                    ->addFieldToFilter('product_id', array('in' => $idProduct))
                    ->getItems();
                foreach($itemsSimple as $simple){
                    $productSimple = $simple->getProduct();
                    $price = $orderItem->getPrice();
                    if ($price) {
                        if(!is_null($productSimple->getId())){
                            if(!is_null($items[$productSimple->getId()])){
                                $items[$productSimple->getId()]->setParent($product->getId());
                                $items[$productSimple->getId()]->setProfit($price);
                            }else{
                                $object = new Varien_Object();
                                $object->setParent($product->getId());
                                $object->setProfit($price);
                                $items[$productSimple->getId()]=$object;
                            }
                        }
                    }
                }
            }else{
                $price = $orderItem->getPrice();
                if ($price) {
                    if(!is_null($product->getId())){
                        $items[$product->getId()] = $price;
                    }
                }
            }
        }

        return $items;
    }


    /**
     * Filter product by price
     *
     * @param $bestSellersArray
     * @return array
     */
    public function filterBestsellersByPrice($bestSellersArray){
        $priceFilter = $this->getCurrentConfig('price_filter');
        $priceFilter = explode("-",trim($priceFilter));
        foreach ($priceFilter as $filter){
            if(!is_numeric($filter)){
                return $bestSellersArray;
            }
        }
        unset($filter);
        if(count($priceFilter)==1){
            //price filter if one number
            foreach ($bestSellersArray as $id) {
                $product = Mage::getModel('catalog/product')->load($id);

                if($product->getFinalPrice()==$priceFilter['0']) {
                    $bestsell[] = $product['entity_id'];
                }
            }
        }else {
            //price filter if two number
            foreach ($bestSellersArray as $id) {
                $product = Mage::getModel('catalog/product')->load($id);

                if ($product->getFinalPrice() >= $priceFilter['0'] AND $product->getFinalPrice() <= $priceFilter['1']) {
                    $bestsell[] = $product['entity_id'];
                }
            }
        }
        return $bestsell;
    }
}
