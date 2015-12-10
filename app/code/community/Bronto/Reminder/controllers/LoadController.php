<?php

/**
 * @package   Bronto\Reminder
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Reminder_LoadController extends Mage_Core_Controller_Front_Action
{
    /**
     * Open quote by link
     * Example: /reminder/load/index/id/1
     */
    public function indexAction()
    {
        // Acquire Parameters
        $storeCode  = $this->getRequest()->getParam('___store', 'default');
        $quoteId    = $this->getRequest()->getParam('id', false);
        $wishlistId = $this->getRequest()->getParam('wishlist_id', false);
        $ruleId     = $this->getRequest()->getParam('rule_id', 0);
        $messageId  = $this->getRequest()->getParam('message_id', 0);

        // Load store from store code and get ID
        $store   = Mage::getModel('core/store')->load($storeCode);
        $storeId = $store->getId();

        // Set Defaults
        $wishlist    = false;
        $redirectUrl = false;

        // Set Current Store to the acquired store id
        Mage::app()->setCurrentStore($storeId);

        // If quote ID is good, send to cart; If wishlist ID is good, send to wishlist
        if ($quote = $this->_handleQuote($quoteId, $storeId)) {
            $redirectUrl = Mage::app()->getStore()->getUrl('checkout/cart');
        } else if ($wishlist = $this->_handleWishlist($wishlistId, $storeId)) {
            $redirectUrl = Mage::app()->getStore()->getUrl('wishlist');
        } else {
            $this->_redirectUrl(Mage::app()->getStore()->getUrl('checkout/cart'));
            return;
        }

        // Get Customer ID from Quote/Wishlist
        $customerId = $this->_getCustomerId($ruleId, $quote, $wishlist);

        if ($customerId) {
            $log = Mage::getModel('bronto_reminder/rule')
                ->getRuleLogItems($ruleId, $storeId, $customerId, $messageId);

            if (!empty($messageId)) {
                Mage::getSingleton('checkout/session')->setBrontoMessageId($messageId);
            }

            if (isset($log['bronto_delivery_id']) && !empty($log['bronto_delivery_id'])) {
                Mage::getSingleton('checkout/session')->setBrontoDeliveryId($log['bronto_delivery_id']);
            }
        }

        if (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) {
            $redirectUrl .= '?' . $_SERVER['QUERY_STRING'];
        }

        // Check for persistent cookie
        $pCookie  = Mage::getModel('core/cookie')->get('persistent_shopping_cart', false);
        $isClear  = (int)Mage::getStoreConfig('persistent/options/logout/clear');
        $persist  = (int)Mage::getStoreConfig('persistent/options/enabled');
        $loggedIn = Mage::getSingleton('customer/session')->isLoggedIn();

        if ($customerId && $persist) {
            $session = Mage::getSingleton('customer/session');
            $forceLogin = false;
            if ($loggedIn && $customerId != Mage::getSingleton('customer/session')->getCustomer()->getId()) {
                $session->logout()->renewSession();
                $forceLogin = true;
            }
            if ($forceLogin || (!$loggedIn && (!$pCookie || ($pCookie && !$isClear)))) {
                $session->setBeforeAuthUrl($redirectUrl);
                $redirectUrl = Mage::app()->getStore()->getUrl('customer/account/login');
            }
        }

        $this->_redirectUrl($redirectUrl);
    }

    /**
     * Use Store Code to pull Store Object
     *
     * @param string $storeCode
     *
     * @return boolean
     */
    protected function _getStoreByCode($storeCode)
    {
        $stores = array_keys(Mage::app()->getStores());
        foreach ($stores as $id) {

            $store = Mage::app()->getStore($id);
            if ($store->getCode() == $storeCode) {
                return $store;
            }
        }

        return false;
    }

    /**
     * Handle Quote
     *
     * @param int|string $quoteId
     * @param int|string $storeId
     *
     * @return boolean|Mage_Sales_Model_Quote
     */
    protected function _handleQuote($quoteId, $storeId)
    {
        if (!$quoteId = Mage::helper('core')->decrypt(base64_decode(urldecode($quoteId)))) {
            return false;
        }

        /* @var $quote Mage_Sales_Model_Quote */
        $quote = Mage::getModel('sales/quote')
            ->setStoreId($storeId)
            ->load($quoteId);

        // Check if quote still exists and is still active
        if ($quote->getId() && $quote->getIsActive()) {
            Mage::getSingleton('checkout/session')->setQuoteId($quote->getId());
            Mage::getSingleton('checkout/session')->resetCheckout();
        } else {
            return false;
        }

        return $quote;
    }

    /**
     * Handle Wishlist
     *
     * @param int|string $wishlistId
     * @param int|string $storeId
     *
     * @return boolean|Mage_Wishlist_Model_Wishlist
     */
    protected function _handleWishlist($wishlistId, $storeId)
    {
        if (!$wishlistId = Mage::helper('core')->decrypt(base64_decode(urldecode($wishlistId)))) {
            return false;
        }

        /* @var $quote Mage_Wishlist_Model_Wishlist */
        $wishlist = Mage::getModel('wishlist/wishlist')
            ->setStoreId($storeId)
            ->load($wishlistId);

        if ($wishlist->getId()) {
            return $wishlist;
        }

        return false;
    }

    /**
     * Get Customer ID from Quote/Wishlist
     *
     * @param int                          $ruleId
     * @param Mage_Sales_Model_Quote       $quote
     * @param Mage_Wishlist_Model_Wishlist $wishlist
     *
     * @return int
     */
    protected function _getCustomerId($ruleId, $quote, $wishlist)
    {
        if (!$ruleId || (!$quote && !$wishlist)) {
            return 0;
        }
        if ($quote) {
            return ($quote->getCustomerId()) ? $quote->getCustomerId() : 0;
        }
        if ($wishlist) {
            return ($wishlist->getCustomerId()) ? $wishlist->getCustomerId() : 0;
        }

        return 0;
    }
}
