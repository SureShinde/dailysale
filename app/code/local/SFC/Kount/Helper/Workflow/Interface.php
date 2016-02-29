<?php
// @codingStandardsIgnoreStart
/**
 * StoreFront Consulting Kount Magento Extension
 *
 * PHP version 5
 *
 * @category  SFC
 * @package   SFC_Kount
 * @copyright 2009-2015 StoreFront Consulting, Inc. All Rights Reserved.
 *
 */
// @codingStandardsIgnoreEnd

/**
 * Abstract interface to represent a workflow helper
 *
 */
interface SFC_Kount_Helper_Workflow_Interface
{
    /**
     * @param Mage_Sales_Model_Order_Payment $payment
     * @throws \Exception
     */
    public function onSalesOrderPaymentPlaceStart(Mage_Sales_Model_Order_Payment $payment);

    /**
     * @param Mage_Sales_Model_Order $order
     * @throws \Exception
     */
    public function onSalesOrderServiceQuoteSubmitFailure(Mage_Sales_Model_Order $order);

    /**
     * @param Mage_Sales_Model_Order $order
     * @throws \Exception
     */
    public function onCheckoutSubmitAllAfter(Mage_Sales_Model_Order $order);

}
