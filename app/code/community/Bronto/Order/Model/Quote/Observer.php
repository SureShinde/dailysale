<?php

/**
 * @package   Bronto\Order
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Order_Model_Quote_Observer
{
    /**
     * This event should only fire on the "frontend". It reads Bronto's
     * "tid" cookie value and assigns to the shopping cart.
     *
     * @param Varien_Event_Observer $observer
     */
    public function addTidToQuote(Varien_Event_Observer $observer)
    {
        /* @var $quote Mage_Sales_Model_Quote */
        $quote = $observer->getQuote();

        /* @var $contactQueue Bronto_Order_Model_Queue */
        $orderRow = Mage::getModel('bronto_order/queue')
            ->getOrderRow(null, $quote->getId(), $quote->getStoreId());

        // Somehow we got a situation where an order was placed on this entry
        if ($orderRow->getOrderId() && !is_null($orderRow->getBrontoTid())) {
            return;
        }

        $tid = Mage::helper('bronto_order')->getTidKey();
        foreach (Mage::getModel('core/cookie')->get() as $key => $value) {
            if ('tid_' . $tid == $key) {
                try {
                    $orderRow->setBrontoTid($value)->save();
                } catch (Exception $e) {
                    Mage::helper('bronto_order')->writeError("Failed to save tid on a quote: " . $e->getMessage());
                }
                break;
            }
        }
    }
}
