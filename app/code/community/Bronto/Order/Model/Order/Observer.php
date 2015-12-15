<?php

/**
 * @package   Bronto\Order
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Order_Model_Order_Observer
{
    /**
     * Called when refunding an order.
     *
     * @param Varien_Event_Observer $observer
     */
    public function resetCreditMemoOrder(Varien_Event_Observer $observer)
    {
        /* @var $order Mage_Sales_Model_Order */
        $order = $observer->getCreditmemo()->getOrder();

        /* @var $contactQueue Bronto_Order_Model_Queue */
        Mage::getModel('bronto_order/queue')
            ->getOrderRow($order->getId(), $order->getQuoteId(), $order->getStoreId())
            ->setBrontoImported(null)
            ->save();
    }

    /**
     * Called when cancelling an order.
     *
     * @param Varien_Event_Observer $observer
     */
    public function resetPaymentCancelOrder(Varien_Event_Observer $observer)
    {
        /* @var $order Mage_Sales_Model_Order */
        $order = $observer->getPayment()->getOrder();

        /* @var $contactQueue Bronto_Order_Model_Queue */
        Mage::getModel('bronto_order/queue')
            ->getOrderRow($order->getId(), $order->getQuoteId(), $order->getStoreId())
            ->setBrontoImported(null)
            ->save();
    }

    /**
     * If an Order's status is changing,
     * just reset the flag anyways...
     *
     * @param Varien_Event_Observer $observer
     */
    public function markOrderForReimport(Varien_Event_Observer $observer)
    {
        /* @var $order Mage_Sales_Model_Order */
        $order = $observer->getOrder();

        /* @var $contactQueue Bronto_Order_Model_Queue */
        $orderRow = Mage::getModel('bronto_order/queue')
            ->getOrderRow($order->getId(), $order->getQuoteId(), $order->getStoreId());

        $managedKey = Mage::helper('bronto_order')->getTidKey();

        foreach (Mage::getModel('core/cookie')->get() as $key => $value) {
            // If managed key was found, use it; otherwise
            // If an original key was found, use that instead
            if ($key == 'tid_' . $managedKey) {
                $orderRow->setBrontoTid($value);
                break;
            } else if (preg_match('/^tid_/', $key)) {
                $orderRow->setBrontoTid($value);
            }
        }

        $orderRow
            ->setCreatedAt($order->getCreatedAt())
            ->setUpdatedAt($order->getUpdatedAt())
            ->setBrontoImported(null)
            ->save();
    }
}
