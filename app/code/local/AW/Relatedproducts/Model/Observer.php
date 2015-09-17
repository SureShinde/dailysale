<?php
/**
 * aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This software is designed to work with Magento community edition and
 * its use on an edition other than specified is prohibited. aheadWorks does not
 * provide extension support in case of incorrect edition use.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Relatedproducts
 * @version    1.4.4
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


class AW_Relatedproducts_Model_Observer
{
    protected function _getHelper($ext = '')
    {
        return Mage::helper('relatedproducts' . ($ext ? '/' . $ext : ''));
    }

    /**
     * Observe placing of new order
     * @param Varien_Object $observer
     */
    public function updateRelatedproductsOrderStatus($observer)
    {
        /** @var $helper AW_Relatedproducts_Helper_Data */
        $helper = $this->_getHelper();
        /** @var $order Mage_Sales_Model_Order */
        $order = $observer->getEvent()->getOrder();
        $storeId = $order->getStoreId();
        $oldStatus = $order->getOrigData('status');
        $newStatus = $order->getData('status');
        if (($oldStatus && $oldStatus != $newStatus) && !in_array($newStatus, $helper->getAllowStatuses($storeId))
            && in_array($oldStatus, $helper->getAllowStatuses($storeId))
        ) {
            Mage::getModel('relatedproducts/relatedproducts')->getResource()->resetStatistics();
        }

        if (!in_array($order->getStatus(), $helper->getAllowStatuses($storeId))) {
            return;
        }
        $helper->updateRelationsForOrderItems($order);
        return $this;
    }

    public function replaceCrossselsBlock($observer)
    {
        /** @var $helper AW_Relatedproducts_Helper_Data */
        $helper = $this->_getHelper();

        /** @var $configHelper AW_Relatedproducts_Helper_Config */
        $configHelper = $this->_getHelper('config');
        if (!$observer->getBlock() instanceof Mage_Checkout_Block_Cart
            || $helper->getExtDisabled()
            || !$configHelper->getCheckoutBlockEnabled()
        ) {
            return $this;
        }
        /** @var $layout Mage_Core_Model_Layout */
        $layout = Mage::app()->getLayout();

        /** @var $shoppingCartBlock Mage_Checkout_Block_Cart */
        $shoppingCartBlock = $observer->getBlock();

        /** @var $wbtabBlock AW_Relatedproducts_Block_Relatedproducts */
        $wbtabBlock = $layout->createBlock('relatedproducts/relatedproducts')
            ->setTemplate('aw_relatedproducts/cartlist.phtml')
            ->setCheckoutMode();
        $crosssellBlock = $shoppingCartBlock->getChild('crosssell');
        if ($crosssellBlock instanceof AW_Autorelated_Block_Blocks) {
            $wbtabBlock->setData('_aw_arp2_cs_block', $crosssellBlock);
        }
        $shoppingCartBlock->setChild('crosssell', $wbtabBlock);
        return $this;
    }
}