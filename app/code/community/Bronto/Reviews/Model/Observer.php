<?php

/**
 * @package   Bronto\Reviews
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Reviews_Model_Observer
{
    const NOTICE_IDENTIFER = 'bronto_reviews';

    // Helper
    protected $_helper;
    protected $_singleton;

    public function __construct()
    {
        /* @var Bronto_Reviews_Helper_Data $_helper */
        $this->_helper = Mage::helper(self::NOTICE_IDENTIFER);
        $this->_singleton = Mage::getModel('bronto_reviews/post_purchase');
    }

    /**
     * Scans order for product based post purchases
     *
     * @param Varien_Event_Observer $observer
     * @return boolean
     */
    public function processPostOrders($observer)
    {
        if ($observer->getOrder()) {
            $appEmulation = Mage::getSingleton('core/app_emulation');
            $emulatedInfo = $appEmulation->startEnvironmentEmulation($observer->getOrder()->getStoreId());
            try {
                $this->_processOrder($observer->getOrder());
            } catch (Exception $e) {
                $this->_helper->writeError("Failed to examine order: {$e->getMessage()}");
            }
            $appEmulation->stopEnvironmentEmulation($emulatedInfo);
        }
    }

    /**
     * Scans order for product based post purchases
     *
     * @param Mage_Sales_Model_Order $order
     * @return boolean
     */
    public function _processOrder($order)
    {
        $processTypes = array();
        $storeId = $order->getStoreId();
        $productHelper = Mage::helper('bronto_common/product');
        $reviewEnabled = $this->_helper->isEnabled('store', $storeId);
        $urlSuffix = ltrim($this->_helper->getProductUrlSuffix('store', $storeId), '/');
        foreach ($this->_singleton->getSupportedTypes() as $postType) {
            if ($this->_helper->isPostEnabled($postType, 'store', $storeId)) {
                $processTypes[$postType] = 1;
            }
        }

        if (
            !is_null($order->getOriginalIncrementId()) ||
            (!$reviewEnabled && empty($processTypes))
        ) {
            return false;
        }

        $concurrentLimit = $this->_helper->getConcurrentLimit('store', $storeId);
        $logSingleton = Mage::getModel('bronto_reviews/log');
        $context = Mage::getModel('bronto_reviews/process_context')
            ->setOrder($order)
            ->setConcurrentLimit($concurrentLimit);
        if ($concurrentLimit >= 0) {
            $currentScheduled = $logSingleton
                ->getCollection()
                ->filterByStoreId($storeId)
                ->filterCancelled(false)
                ->filterFuture(time())
                ->addFieldToFilter('customer_email', array('eq' => $order->getCustomerEmail()));
            $context->setCurrentlyScheduled($currentScheduled->getSize());
        }

        $index = 1;
        $timeOfDay = $this->_helper->getDesiredTimeOfDay('store', $storeId);
        $reviewMessage = Mage::getModel('bronto_reviews/message');
        foreach ($order->getAllItems() as $item) {
            $product = $productHelper->getProduct($item->getProductId(), $storeId);
            if (!empty($processTypes)) {
                // Memory map, as to avoid stupid subqueries
                $processed = array();
                $posts = $this->_singleton->getCollection()
                    ->filterByProduct($product->getId())
                    ->filterByStoreId($storeId)
                    ->filterByActive();
                foreach ($posts as $post) {
                    if (array_key_exists($post->getPostType(), $processed)) {
                        continue;
                    }
                    $postContext = $context->hardCopy()->setPost($post);
                    if (isset($processTypes[$post->getPostType()])) {
                        $processed[$post->getPostType()] = true;
                        $days = $this->_helper->getDefaultPostPeriod($post, $storeId);
                        if ($post->getPostType() == Bronto_Reviews_Model_Post_Purchase::TYPE_REORDER && $this->_helper->getDefaultMultiplier($post, $storeId)) {
                            $days *= ($item->getQtyOrdered() * 1);
                        }
                        $days += $this->_helper->getDefaultAdjustment($post, $storeId);
                        $productMessage = Mage::getModel('bronto_reviews/message')
                            ->addParam('product_id', $post->getProductId())
                            ->addParam('product_name', $product->getName())
                            ->addParam('post_id', $post->getId())
                            ->setTimeOfDay($timeOfDay)
                            ->setSendTime($days);
                        $context->incrementSchedule($this->_processMessage(
                            $postContext->setExtra(array(
                                'post_purchase' => $post,
                                'order_item' => $item
                            )),
                            $productMessage));
                    }
                }
            }
            if (!$item->getParentItem()) {
                $product = $productHelper->getConfigurableProduct($product);
                if ($reviewEnabled) {
                    $productUrl = $productHelper->getProductAttribute($product, 'url', $storeId) . $urlSuffix;
                    $reviewUrl = $this->_helper->getReviewsUrl($product, $storeId) . $urlSuffix;
                    $reviewMessage->addDeliveryField("reviewUrl_{$index}", $reviewUrl);
                    $reviewMessage->addDeliveryField("productUrl_{$index}", $reviewUrl);
                }
                $index++;
            }
        }
        if ($reviewEnabled) {
            $days = $this->_helper->getPostPeriod('settings', 'store', $storeId);
            $days += $this->_helper->getPostAdjustment('settings', 'store', $storeId);
            $reviewMessage->setSendTime($days);
            $this->_processMessage($context, $reviewMessage->setTimeOfDay($timeOfDay));
        }
        $logSingleton->flushCancelableDeliveries();
        return true;
    }

    /**
     * Filters a post purchased object
     *
     * @param Varien_Event_Observer $observer
     */
    public function filterPostPurchase($observer)
    {
        $filter = $observer->getFilter();
        $object = $observer->getUnknown();
        if ($object instanceof Bronto_Reviews_Model_Post_Purchase) {
            if ($object->getContent()) {
                $filter->setField('extraContent', $object->getContent());
            }
        } else if ($object instanceof Mage_Sales_Model_Order_Item) {
            $prefix = "postProduct";
            $productHelper = Mage::helper('bronto_common/product');
            $object = $object->getParentItem() ? $object->getParentItem() : $object;
            $product = $productHelper->getProduct($object->getProductId(), $filter->getStoreId());
            $filter->addItemToContext($object);
            $filter->setField("{$prefix}Id", $object->getProductId());
            $filter->setField("{$prefix}Sku", $product->getSku());
            $filter->setField("{$prefix}Price", $filter->formatPrice($object->getPrice()));
            $filter->setField("{$prefix}Total", $filter->formatPrice($object->getRowTotal()));
            $filter->setField("{$prefix}Qty", $object->getQtyOrdered() * 1);
            $filter->setField("{$prefix}Name", $product->getName());
            $filter->setField("{$prefix}Description", $product->getDescription());
            $filter->setField("{$prefix}Url", $product->getProductUrl());
            try {
                $common = Mage::helper('bronto_common');
                $filter->setField("{$prefix}ImgUrl", $common->getProductImageUrl($product));
            } catch (Exception $e) {
                $this->_helper->writeError("Failed to pull post product img: {$e->getMessage()}");
            }
        }
    }

    /**
     * Completes the post purchase message processing by either sending or
     * cancelling a delivery.
     *
     * @param Bronto_Reviews_Model_Process_Context $context
     * @param Bronto_Reviews_Model_Message $postMessage
     * @return int
     */
    protected function _processMessage($context, $postMessage)
    {
        if ($this->_shouldSend($context)) {
            $this->_sendMessage($context, $postMessage);
            return 1;
        } else if ($this->_shouldCancel($context)) {
            $log = Mage::getModel('bronto_reviews/log')
                ->loadByOrderAndPost(
                    $context->getOrder()->getId(),
                    $context->hasPost() ? $context->getPost()->getId() : null)
                ->cancel();
            if ($log->isQueued()) {
                $log->delete();
            }
            return -1;
        } else {
            $this->_helper->writeDebug("Nothing to process... Skipping.");
            return 0;
        }
    }

    /**
     * If it's acceptable to send a message for this type
     *
     * @param Bronto_Reviews_Model_Process_Context $context
     * @return boolean
     */
    protected function _shouldSend($context)
    {
        $storeId = $context->getOrder()->getStoreId();
        $postType = $context->getPostType();
        $status = $context->getOrder()->getStatus();
        return (
            $this->_helper->isPostEnabled($postType, 'store', $storeId) &&
            $this->_helper->getPostTrigger($postType, 'store', $storeId) == $status &&
            $context->isSendingUnlocked() &&
            $this->_passSendLimitCheck($context)
        );
    }

    /**
     * Makes a check for the send limit
     *
     * @param $context
     * @return boolean
     */
    protected function _passSendLimitCheck($context)
    {
        if ($context->getPostType() != Bronto_Reviews_Model_Post_Purchase::TYPE_CARETIP) {
            return true;
        }
        $storeId = $context->getOrder()->getStoreId();
        $sendLimit = $this->_helper->getDefaultSendLimit($context->getPost(), $storeId);
        if (is_null($sendLimit) || $sendLimit < 0) {
            return true;
        }
        $logs = Mage::getModel('bronto_reviews/log')
            ->getCollection()
            ->filterByStoreId($storeId)
            ->filterCancelled(false)
            ->filterByEmail(
                $context->getOrder()->getCustomerEmail(),
                $context->getPost()->getId());
        return $logs->getSize() < $sendLimit;
    }

    /**
     * If it's acceptable to cancel the delivery for this type
     *
     * @param Bronto_Reviews_Model_Process_Context $context
     * @return boolean
     */
    protected function _shouldCancel($context)
    {
        $storeId = $context->getOrder()->getStoreId();
        $status = $context->getOrder()->getStatus();
        $postType = $context->getPostType();
        return (
            $this->_helper->isPostEnabled($postType, 'store', $storeId) &&
            in_array($status, $this->_helper->getPostCancel($postType, 'store', $storeId))
        );
    }

    /**
     * Sends the desired message transactionally
     *
     * @param Bronto_Reviews_Model_Process_Context $context
     * @param Bronto_Reviews_Model_Message $postMessage
     * @param mixed
     */
    protected function _sendMessage($context, $postMessage)
    {
        $order = $context->getOrder();
        $postType = $context->getPostType();
        $storeId = $order->getStoreId();
        $sender = $this->_helper->getPostEmailIdentity($postType, 'store', $storeId);
        if ($sender == 'custom') {
            $sender = array(
                'name' => $this->_helper->getPostSenderName($postType, 'store', $storeId),
                'email' => $this->_helper->getPostSenderEmail($postType, 'store', $storeId)
            );
        }

        $message = new Bronto_Api_Message_Row();
        $message->id = $this->_helper->getDefaultMessage($context->getPost(), $storeId);
        $filterData = array('order' => $order) + $context->getExtra();
        if ($context->hasPost()) {
            unset($filterData['order']);
            $filterData['orderIncrementId'] = $order->getIncrementId();
            $filterData['orderCreatedAt'] = $order->getCreatedAtFormated('long');
            $filterData['orderStatusLabel'] = $order->getStatusLabel();
            $filterData['orderCustomerName'] = $order->getCustomerIsGuest() ?
                $order->getBillingAddress()->getName() :
                $order->getCustomerName();
        }
        $this->_cancelReorderDelivery($context);
        $postMessage
            ->addParam('store_id', $order->getStoreId())
            ->addParam('order_id', $order->getId())
            ->addParam('order_increment_id', $order->getIncrementId())
            ->addParam('customer_email', $order->getCustomerEmail())
            ->addParam('exclusion_list', $postType)
            ->addParam('post_name', $this->_helper->getPostLabel($postType))
            ->setDesignConfig(array('area' => 'frontend', 'store' => $storeId))
            ->setSalesRule($this->_helper->getPostRule($postType, 'store', $storeId))
            ->setProductRecommendation($this->_helper->getPostRecommendation($postType, 'store', $storeId))
            ->setTemplateSendType('triggered')
            ->setSendFlags($this->_helper->getPostSendFlags($postType, 'store', $storeId))
            ->sendTransactional(
                $message,
                $sender,
                array($order->getCustomerEmail()),
                array($order->getCustomerName()),
                $filterData,
                $storeId
            );
        if ($postMessage->getSentSuccess()) {
            $this->_helper->writeDebug(' Successfully created delivery.');
        } else {
            $this->_helper->writeDebug(' Failed to send the message.');
        }
    }

    /**
     * Updates a reorder with a new date
     *
     * @param $context
     * @return boolean
     */
    protected function _cancelReorderDelivery($context)
    {
        if ($context->getPostType() == Bronto_Reviews_Model_Post_Purchase::TYPE_REORDER) {
            $logs = Mage::getModel('bronto_reviews/log')
                ->getCollection()
                ->filterByStoreId($context->getOrder()->getStoreId())
                ->filterCancelled(false)
                ->filterFuture(time())
                ->filterByEmail(
                    $context->getOrder()->getCustomerEmail(),
                    $context->getPost()->getId());
            if ($logs->count()) {
                foreach ($logs as $log) {
                    $log->cancel();
                    $context->getParent()->incrementSchedule(-1);
                }
                return true;
            }
        }
        return false;
    }

    /**
     * Creates a log entry based on the API queue entry
     *
     * @param Varien_Event_Observer $observer
     */
    public function afterQueueSave($observer)
    {
        $queue = $observer->getObject();
        if ($queue->getEmailClass() == 'bronto_reviews/message') {
            $unData = $queue->getUnserializedEmailData();
            try {
                $log = Mage::getModel('bronto_reviews/log')
                    ->loadByOrderAndDeliveryId(
                        $unData['params']['order_id'],
                        $queue->getId());
                $logId = $log->getId();
                $log->setData($unData['params']);
                $log->setId($logId)
                    ->setMessageId($unData['delivery']['messageId'])
                    ->setDeliveryId($queue->getId())
                    ->setDeliveryDate(Mage::getModel('core/date')->gmtDate())
                    ->setFields(serialize($unData['delivery']['fields']));
                $log->save();
            } catch (Exception $e) {
                $this->_helper->writeError("Failed to save log entry for queue save: {$e->getMessage()}");
            }
        }
    }

    /**
     * Saves the form data added to the product catalog
     *
     * @param Varien_Event_Observer $observer
     * @return boolean
     */
    public function productPrepareSave($observer)
    {
        $request = $observer->getRequest();
        $product = $observer->getProduct();
        if (!$product->getId()) {
            return false;
        }
        $storeId = $request->getParam('store', 0);
        $formData = array(
            'active',
            'period',
            'content',
            'adjustment',
            'message',
            'multiply_by_qty',
            'period_type',
            'send_limit'
        );
        foreach ($this->_singleton->getSupportedTypes() as $type) {
            if (!$this->_helper->isPostEnabled($type)) {
                continue;
            }
            try {
                $post = Mage::getModel('bronto_reviews/post_purchase')
                    ->loadByProduct($product->getId(), $type, $storeId);
                // Is set and true, delete if applicable
                if ($request->getParam("{$type}_use_default", false)) {
                    if ($post->getId()) {
                        $post->delete();
                    }
                    continue;
                }
                foreach ($formData as $field) {
                    $requestKey = "{$type}_{$field}";
                    $requestOverride = "{$type}_{$field}_override";
                    if ($request->has($requestKey)) {
                        $post->setData($field, $request->getParam($requestKey));
                    }
                    if ($request->getParam($requestOverride) == 'default') {
                        $post->setData($field, null);
                    }
                }
                // This means the form wasn't loaded
                if (is_null($post->getActive())) {
                    continue;
                }
                $post->save();
            } catch (Exception $e) {
                $this->_helper->writeError("Failed to save post-purchase info: {$e->getMessage()}");
            }
        }
        return true;
    }

    /**
     * Deletes the post purchase information if the product is removed
     *
     * @param Varien_Event_Observer $observer
     * @return boolean
     */
    public function deletePostPurchaseInfo($observer)
    {
        $product = $observer->getProduct();
        if (empty($product) || ($product && !$product->getId())) {
            return false;
        }
        $productId = $product->getId();
        try {
            $logSingleton = Mage::getModel('bronto_reviews/log');
            $posts = $this->_singleton->getCollection()->filterByProduct($productId);
            foreach ($posts as $post) {
                $logs = $logSingleton
                    ->getCollection()
                    ->filterFuture(time())
                    ->filterCancelled(false)
                    ->filterByPost($post->getId());
                foreach ($logs as $log) {
                    $log->cancel();
                }
                $post->delete();
            }
            $logSingleton->flushCancelableDeliveries();
        } catch (Exception $e) {
            $this->_helper->writeError("Failed to handle a product delete: {$e->getMessage()}");
        }
        return true;
    }
}
