<?php

class Bronto_Order_Block_Redemption extends Mage_Core_Block_Template
{
    /**
     * Gets the siteKey for the coupon redemption
     *
     * @return string
     */
    public function getRedemptionSiteHash()
    {
        return Mage::helper('bronto_common')->getCouponSiteHash();
    }

    /**
     * Override for populating the template variables
     *
     * @see parent
     */
    protected function _beforeToHtml()
    {
        $this->_prepareCouponVariables();
    }

    /**
     * Prepares the coupon variables for the template
     *
     * @return void
     */
    protected function _prepareCouponVariables()
    {
        $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
        if ($orderId) {
            $order = Mage::getModel('sales/order')->load($orderId);
            if ($order->getId()) {
                $this->setData(array(
                    'email_address' => $order->getCustomerEmail(),
                    'coupon_used' => $order->getCouponCode(),
                    'order_increment_id' => $order->getIncrementId(),
                    'order_subtotal' => $order->getSubtotal(),
                    'discount_amount' => $order->getDiscountAmount(),
                    'order' => $order,
                ));
            }
        }
    }
}
