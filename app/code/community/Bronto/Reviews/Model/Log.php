<?php

class Bronto_Reviews_Model_Log extends Mage_Core_Model_Abstract
{
    // Thirty minutes to compensate the delivery prep time
    const THIRTY_MINS = 1800;
    const UPDATE_STEP = 50;

    private static $_cancelableDeliveries = array();

    /**
     * @see parent
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('bronto_reviews/log');
    }

    /**
     * Loads the log by the order and delivery id
     *
     * @param int $orderId
     * @param mixed $deliveryId
     * @return Bronto_Reviews_Model_Log
     */
    public function loadByOrderAndDeliveryId($orderId, $deliveryId = null)
    {
        $this->setOrderId($orderId);
        $this->setDeliveryId($deliveryId);
        $this->_getResource()->loadByOrderAndDeliveryId($this, $orderId, $deliveryId);
        return $this;
    }

    /**
     * Loads the log by the order and post purchase
     *
     * @param int $orderId
     * @param int $postId
     * @return Bronto_Reviews_Model_Log
     */
    public function loadByOrderAndPost($orderId, $postId = null)
    {
        $this->setOrderId($orderId);
        $this->setPostId($postId);
        $this->_getResource()->loadByOrderAndPost($this, $orderId, $postId);
        return $this;
    }

    /**
     * Determines if this delivery is cancelable
     *
     * @return boolean
     */
    public function isCancelable()
    {
        $deliveryDate = strtotime($this->getDeliveryDate()) - self::THIRTY_MINS;
        return $this->getDeliveryId() && !$this->getCancelled() && $deliveryDate > time();
    }

    /**
     * Is this delivery currently being queued
     *
     * @return boolean
     */
    public function isQueued()
    {
        return $this->getDeliveryId() && is_numeric($this->getDeliveryId());
    }

    /**
     * Cancels this delivery one way or another
     *
     * @param int $storeId
     */
    public function cancel()
    {
        if ($this->isQueued() || $this->isCancelable()) {
            if (is_numeric($this->getDeliveryId())) {
                Mage::getModel('bronto_common/queue')
                    ->load($this->getDeliveryId())
                    ->delete();
            } else {
                if (!array_key_exists($this->getStoreId(), self::$_cancelableDeliveries)) {
                    self::$_cancelableDeliveries[$this->getStoreId()] = array();
                }
                self::$_cancelableDeliveries[$this->getStoreId()][] = array(
                    'id' => $this->getDeliveryId(),
                    'status' => 'skipped'
                );
                if (count(self::$_cancelableDeliveries[$this->getStoreId()]) == self::UPDATE_STEP) {
                    $this->_flushDeliveriesForStore($this->getStoreId());
                }
            }
            $this->setCancelled(true)->save();
        }
        return $this;
    }

    /**
     * Flushed the deliveries for a given store
     *
     * @param int $storeId
     */
    protected function _flushDeliveriesForStore($storeId)
    {
        if (array_key_exists($storeId, self::$_cancelableDeliveries)) {
            $api = Mage::helper('bronto_common')->getApi(null, 'store', $storeId);
            $deliveryObject = $api->getDeliveryObject();
            $deliveryRows = self::$_cancelableDeliveries[$storeId];
            try {
                $result = $deliveryObject->update($deliveryRows);
                if ($result->hasErrors()) {
                    $errors = array();
                    foreach ($result->getErrors() as $soapFault) {
                        $errors[] = $soapFault['code'] . ": " . $soapFault['message'];
                    }
                    $error = implode('<br />', $errors);
                    Mage::throwException($error);
                }
            } catch (Exception $e) {
                Mage::helper('bronto_reviews')->writeError('Failed Cancelling Deliveries: ' . $e->getMessage());
            }
            unset(self::$_cancelableDeliveries[$storeId]);
        }
    }

    /**
     * Updates all deliveries to be cancelled... in bulk
     */
    public function flushCancelableDeliveries()
    {
        if (!empty(self::$_cancelableDeliveries)) {
            foreach (self::$_cancelableDeliveries as $storeId => $deliveryRows) {
                $this->_flushDeliveriesForStore($storeId);
            }
        }
    }
}
