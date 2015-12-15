<?php

/**
 * @package   Bronto\Order
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Order_Model_Observer
{

    const NOTICE_IDENTIFIER = 'bronto_order';
    const ERROR_APPENDER = ' This is most likely caused by an invalid character in the request.';

    private $_helper;

    public function __construct()
    {
        /* @var Bronto_Order_Helper_Data $_helper */
        $this->_helper = Mage::helper(self::NOTICE_IDENTIFIER);
    }

    public function setHelper($helper)
    {
        $this->_helper = $helper;
    }

    /**
     * Verify that all requirements are met for this module
     *
     * @param Varien_Event_Observer $observer
     *
     * @return null
     * @access public
     */
    public function checkBrontoRequirements(Varien_Event_Observer $observer)
    {
        if (!Mage::getSingleton('admin/session')->isLoggedIn()) {
            return;
        }

        // Verify Requirements
        if (!$this->_helper->varifyRequirements(self::NOTICE_IDENTIFIER, array('soap', 'openssl'))) {
            return;
        }
    }

    /**
     * Creates a lookup table for states used in the import proccess
     *
     * @param array $allStates
     * @param array $iterativeStates
     * @return array
     */
    protected function _createStateMap($allStates, $iterativeStates)
    {
        $stateMap = array();
        foreach ($iterativeStates as $stateName) {
            if (array_key_exists($stateName, $allStates)) {
                $stateMap[$stateName] = $allStates[$stateName];
            }
        }
        return $stateMap;
    }

    /**
     * Function to persist orders to be flushed to Bronto
     *
     * @param Mage_Sales_Model_Order $order
     * @param Bronto_Api_Order $brontoOrder
     * @param array $context
     */
    protected function _persistOrder($order, $brontoOrder, $context)
    {
        extract($context);
        // Get visible items from order
        $items = $order->getAllVisibleItems();

        // Keep product order by using a new array
        $fullItems = array();
        $brontoOrderItems = array();

        // loop through the items. if it's a bundled item,
        // replace the parent item with the child items.
        foreach ($items as $item) {
            $itemProduct = Mage::getModel('catalog/product')->load($item->getProductId());

            // Handle product based on product type
            switch ($itemProduct->getTypeId()) {

                // Bundled products need child items
                case Mage_Catalog_Model_Product_Type::TYPE_BUNDLE:
                    if (count($item->getChildrenItems()) > 0) {
                        foreach ($item->getChildrenItems() as $childItem) {
                            if ($childItem->getPrice() != 0) {
                                $item->setPrice(0);
                            }
                            $fullItems[] = $childItem;
                        }
                    }
                    $fullItems[] = $item;

                    break;

                // Configurable products just need simple config item
                case Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE:
                    $childItems = $item->getChildrenItems();
                    if (1 === count($childItems)) {
                        $childItem = $childItems[0];

                        // Collect options applicable to the configurable product
                        $productAttributeOptions = $itemProduct->getTypeInstance(true)->getConfigurableAttributesAsArray($itemProduct);

                        // Build Selected Options Name
                        $nameWithOptions = array();
                        foreach ($productAttributeOptions as $productAttribute) {
                            $itemValue         = $productHelper->getProductAttribute($childItem->getProductId(), $productAttribute['attribute_code'], $storeId);
                            $nameWithOptions[] = $productAttribute['label'] . ': ' . $itemValue;
                        }

                        // Set parent product name to include selected options
                        $parentName = $item->getName() . ' [' . implode(', ', $nameWithOptions) . ']';
                        $item->setName($parentName);
                    }

                    $fullItems[] = $item;
                    break;

                // Grouped products need parent and child items
                case Mage_Catalog_Model_Product_Type::TYPE_GROUPED:
                    // This condition probably never gets hit, parent grouped items don't show in order
                    $fullItems[] = $item;
                    foreach ($item->getChildrenItems() as $child_item) {
                        $fullItems[] = $child_item;
                    }
                    break;

                // Anything else (namely simples) just get added to array
                default:
                    $fullItems[] = $item;
                    break;
            }
        }

        // Cycle through newly created array of products
        foreach ($fullItems as $item/* @var $item Mage_Sales_Model_Order_Item */) {
            // If product has a parent, get that parent product
            $parent = false;
            if ($item->getParentItem()) {
                $parent = Mage::getModel('catalog/product')->setStoreId($storeId)->load($item->getParentItem()->getProductId());
            }

            /* @var $product Mage_Catalog_Model_Product */
            $product = Mage::getModel('catalog/product')->setStoreId($storeId)->load($item->getProductId());

            // If there is a parent product, use that to get category ids
            if ($parent) {
                $categoryIds = $parent->getCategoryIds();
            } else {
                $categoryIds = $product->getCategoryIds();
            }

            // If the product type is simple and the description
            // is empty, then attempt to find a parent product
            // to backfill the description.
            $parentProduct = $productHelper->getConfigurableProduct($product);
            if (!$product->getData($descriptionAttr)) {
                $product->setData($descriptionAttr, $parentProduct->getData($descriptionAttr));
            }

            if (empty($categoryIds)) {
                $categoryIds = $parentProduct->getCategoryIds();
            }

            // Cycle through category ids to pull category details
            $categories = array();
            foreach ($categoryIds as $categoryId) {
                /* @var $category Mage_Catalog_Model_Category */
                $category     = Mage::getModel('catalog/category')->load($categoryId);
                $parent       = $category->getParentCategory();
                $categories[] = $parent->getUrlKey() ? $parent->getUrlKey() : $parent->formatUrlKey($parent->getName());
                $categories[] = $category->getUrlKey() ? $category->getUrlKey() : $category->formatUrlKey($category->getName());
            }

            // Check to ensure there are no duplicate categories
            $categories = array_unique($categories);

            // Write orderItem
            $brontoOrderItems[] = array(
                'id'          => $item->getId(),
                'sku'         => $item->getSku(),
                'name'        => $item->getName(),
                'description' => $product->getData($descriptionAttr),
                'category'    => implode(' ', $categories),
                'image'       => $this->_helper->getItemImg($item, $product, $storeId),
                'url'         => $this->_helper->getItemUrl($item, $product, $storeId),
                'quantity'    => (int)$item->getQtyOrdered(),
                'price'       => $this->_helper->getItemPrice($item, $basePrefix, $inclTaxes, $inclDiscounts)
            );
        }
        if ($inclShipping && $order->getState() == Mage_Sales_Model_Order::STATE_COMPLETE && $order->hasShipments()) {
            $shippingObject = new Varien_Object(array(
                'qty_ordered' => 1,
                'base_row_total' => $order->getBaseShippingAmount(),
                'row_total' => $order->getShippingAmount(),
                'base_tax_amount' => $order->getBaseShippingTaxAmount(),
                'tax_amount' => $order->getShippingTaxAmount(),
                'base_discount_amount' => $order->getBaseShippingDiscountAmount(),
                'discount_amount' => $order->getShippingDiscountAmount()
            ));
            $descriptions = array();
            foreach ($order->getTracksCollection() as $track) {
                if ($track->hasTrackNumber() && $track->hasTitle()) {
                    $descriptions[] = "{$track->getTitle()} - {$track->getTrackNumber()}";
                }
            }
            $shipmentItem = array(
                'sku' => 'SHIPPING',
                'name' => $order->getShippingDescription(),
                'description' => implode("<br/>", $descriptions),
                'quantity' => 1,
                'price' => $this->_helper->getItemPrice($shippingObject, $basePrefix, $inclTaxes, $inclDiscounts)
            );
            $brontoOrderItems[] = $shipmentItem;
        }
        $brontoOrder->withProducts($brontoOrderItems);
    }

    /**
     * Skips the order because it's in one invalid state or another
     *
     * @param Mage_Sales_Model_Order $order
     * @param Bronto_Order_Model_Queue $orderRow
     * @param string $reason
     */
    protected function _skipOrder($order, $orderRow, $reason)
    {
        $importDate = Mage::getSingleton('core/date')->gmtDate();
        $orderRow->setBrontoImported($importDate)->setBrontoSuppressed(null)->save();
        $this->_helper->writeInfo("  Skipping order id {$order->getId()} #{$order->getIncrementId()}: {$reason}");
    }

    /**
     * Process specified number of items for specified store
     *
     * @param mixed $storeId can be store object or id
     * @param int   $limit   must be greater than 0
     *
     * @return array
     * @access public
     */
    public function processOrdersForStore($storeId, $limit)
    {
        // Define default results
        $result = array('total' => 0, 'success' => 0, 'error' => 0);

        // If limit is false or 0, return
        if (!$limit) {
            $this->_helper->writeDebug('  Limit empty. Skipping...');

            return $result;
        }

        // Get Store object and ID
        $store   = Mage::app()->getStore($storeId);
        $storeId = $store->getId();

        // Log that we have begun importing for this store
        $this->_helper->writeDebug("Starting Order Import process for store: {$store->getName()} ({$storeId})");

        // If module is not enabled for this store, log that fact and return
        if (!$store->getConfig(Bronto_Order_Helper_Data::XML_PATH_ENABLED)) {
            $this->_helper->writeDebug('  Module disabled for this store. Skipping...');

            return $result;
        }

        // Retrieve Store's configured API Token
        $token = $store->getConfig(Bronto_Common_Helper_Data::XML_PATH_API_TOKEN);

        /* @var $api Bronto_Common_Model_Api */
        $uploadMax = $this->_helper->getBulkLimit('store', $store->getId());
        $api = $this->_helper->getApi($token, 'store', $store->getId());

        /* @var $orderObject Bronto_Api_Operation_Order */
        $orderObject = $api->transferOrder();
        $apiFlusher = Mage::getModel('bronto_common/flusher')->setHelper('bronto_order');
        $deleteOrders = $orderObject->delete($uploadMax)->withFlusher($apiFlusher);
        $addOrUpdateOrders = $orderObject->addOrUpdate($uploadMax)->withFlusher($apiFlusher);

        // Retrieve order queue rows limited to current limit and filtered
        // Filter out imported, suppressed, other stores, and items without order ids
        $orderRows = Mage::getModel('bronto_order/queue')
            ->getCollection()
            ->addBrontoNotImportedFilter()
            ->addBrontoNotSuppressedFilter()
            ->addBrontoHasOrderFilter()
            ->orderByUpdatedAt()
            ->setPageSize($limit)
            ->addStoreFilter($storeId)
            ->getItems();

        // If we didn't get any order queue rows with this pull, log and return
        if (empty($orderRows)) {
            $this->_helper->writeVerboseDebug('  No Orders to process. Skipping...');

            return $result;
        }

        /* @var $productHelper Bronto_Common_Helper_Product */
        $productHelper   = Mage::helper('bronto_common/product');
        $descriptionAttr = $store->getConfig(Bronto_Order_Helper_Data::XML_PATH_DESCRIPTION);
        $basePrefix      = $this->_helper->getPriceAttribute('store', $store->getId());
        $inclTaxes       = $this->_helper->isTaxIncluded('store', $store->getId());
        $inclDiscounts   = $this->_helper->isDiscountIncluded('store', $store->getId());
        $inclShipping    = $this->_helper->isShippingIncluded('store', $store->getId());
        $allStates       = Mage::getModel('bronto_order/system_config_source_order_state')->toArray();
        $importStateMap  = $this->_createStateMap($allStates, $this->_helper->getImportStates('store', $store->getId()));
        $deleteStateMap  = $this->_createStateMap($allStates, $this->_helper->getDeleteStates('store', $store->getId()));
        $this->_helper->writeDebug(" Importing: " . implode(', ', $importStateMap));
        $this->_helper->writeDebug("  Deleting: " . implode(', ', $deleteStateMap));

        // Cycle through each order queue row
        foreach ($orderRows as $orderRow/* @var $orderRow Bronto_Order_Model_Queue */) {
            $orderId = $orderRow->getOrderId();
            $quoteId = $orderRow->getQuoteId();

            // Check if the order id is still attached to an order in magento
            $order = Mage::getModel('sales/order')->load($orderId);
            if ($order->getId()) {
                // Log that we are processing the current order
                $this->_helper->writeDebug("  Processing Order ID: {$orderId} \t #{$order->getIncrementId()}");

                /* @var $brontoOrder Bronto_Api_Model_Order */
                $brontoOrder = $orderObject->createObject()
                    ->withEmail($order->getCustomerEmail())
                    ->withId($order->getIncrementId())
                    ->withOrderDate(date('c', strtotime($order->getCreatedAt())))
                    ->withQueueRow($orderRow->getData());

                // If there is a conversion tracking id attached to this order, add it to the row
                if ($tid = $orderRow->getBrontoTid()) {
                    $brontoOrder->withTid($tid);
                }

                if (!$brontoOrder->hasId()) {
                    $this->_skipOrder($order, $orderRow, "Invalid increment ID");
                    $result['success']++;
                    $result['total']++;
                } else if (array_key_exists($order->getState(), $importStateMap)) {
                    $this->_persistOrder($order, $brontoOrder, array(
                        'productHelper' => $productHelper,
                        'descriptionAttr' => $descriptionAttr,
                        'basePrefix' => $basePrefix,
                        'inclTaxes' => $inclTaxes,
                        'inclDiscounts' => $inclDiscounts,
                        'inclShipping' => $inclShipping,
                        'storeId' => $storeId,
                    ));
                    $addOrUpdateOrders->addOrder($brontoOrder);
                } else if (array_key_exists($order->getState(), $deleteStateMap)) {
                    $deleteOrders->deleteOrder($brontoOrder);
                } else {
                    $this->_skipOrder($order, $orderRow, "Invalid state {$allStates[$order->getState()]}");
                    $result['success']++;
                    $result['total']++;
                }

            } else {
                $this->_skipOrder($order, $orderRow, "Invalid order");
                $result['success']++;
                $result['total']++;
            }
        }

        $deleteOrders->flush();
        $addOrUpdateOrders->flush();
        extract($apiFlusher->getResult());
        $result['success'] += $success;
        $result['error'] += $error;
        $result['total'] += $total;

        // Log results
        $this->_helper->writeDebug('  Success: ' . $result['success']);
        $this->_helper->writeDebug('  Error:   ' . $result['error']);
        $this->_helper->writeDebug('  Total:   ' . $result['total']);

        return $result;
    }

    /**
     * Process Orders for all stores
     *
     * @param bool $brontoCron
     *
     * @return array
     */
    public function processOrders($brontoCron = false)
    {
        // Set default result values
        $result = array(
            'total'   => 0,
            'success' => 0,
            'error'   => 0,
        );

        // Only allow cron to run if isset to use mage cron or is coming from bronto cron
        if (Mage::helper('bronto_order')->canUseMageCron() || $brontoCron) {
            // Get limit value from config
            $limit = $this->_helper->getLimit();

            // Pull array of stores to cycle through
            $stores = Mage::app()->getStores(true);

            // Cycle through stores
            foreach ($stores as $_store) {
                // If limit is spent, don't process
                if ($limit <= 0) {
                    continue;
                }

                // Process Orders for store and collect results
                $storeResult = $this->processOrdersForStore($_store, $limit);

                // Append results to totals
                $result['total'] += $storeResult['total'];
                $result['success'] += $storeResult['success'];
                $result['error'] += $storeResult['error'];

                // Decrement limit by resultant total
                $limit = $limit - $storeResult['total'];
            }
        }

        return $result;
    }

}
