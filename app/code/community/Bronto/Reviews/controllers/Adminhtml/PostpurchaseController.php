<?php

class Bronto_Reviews_Adminhtml_PostpurchaseController extends Mage_Adminhtml_Controller_Action
{
    protected $_header = 'Post Purchase';
    protected $_module = 'bronto_reviews';

    /**
     * Gets the block for the grid for certain things
     *
     * @return Mage_Adminhtml_Block_Abstract
     */
    public function getBlock($key)
    {
        return $this->getLayout()
            ->createBlock("{$this->_module}/adminhtml_reviews_{$key}", $key);
    }

    /**
     * Grid for all of the deliveries for post purchase products
     */
    public function deliveriesAction()
    {
        $this->_title($this->__('Bronto Deliveries'))->_title($this->__('Logs'));
        $this->loadLayout()->_setActiveMenu('system/email');
        $this->_addContent($this->getBlock("delivery"))->renderLayout();
        return $this;
    }

    /**
     * Ajax call for grid on order tab
     */
    public function orderAction()
    {
        $orderId = intval($this->getRequest()->getParam('order_id'));
        $order = Mage::getModel('sales/order')->load($orderId);
        Mage::register('current_order', $order);
        $this->getResponse()->setBody(
            $this
                ->getLayout()
                ->createBlock('bronto_reviews/adminhtml_sales_order_view_tab_post_purchase')
                ->toHtml()
        );
    }

    /**
     * Clear cancelled or old deliveries
     */
    public function clearAction()
    {
        $type = $this->getRequest()->getParam('type', 'old');
        $logSingleton = Mage::getModel('bronto_reviews/log');
        $session = Mage::getSingleton('adminhtml/session');
        try {
            if ($type == 'old') {
                $now = time();
                $count = $logSingleton->getCollection()->filterOld($now)->getSize();
                $logSingleton->getResource()->clearOld($now);
            } else {
                $count = $logSingleton->getCollection()->filterCancelled()->getSize();
                $logSingleton->getResource()->clearCancelled();
            }
            $session->addSuccess($this->__('Cleared %d %s post-purchase delivery logs.', $count, $type));
        } catch (Exception $e) {
            $session->addError($this->__('Failed to clear logs: %s', $e->getMessage()));
        }
        $this->_redirect('*/*/deliveries');
    }

    /**
     * Gets the post purchase form for the post purchase type
     */
    public function formAction()
    {
        $postType = $this->getRequest()->getParam('type', Bronto_Reviews_Model_Post_Purchase::TYPE_REORDER);
        $productId = $this->getRequest()->getParam('product_id', 0);
        Mage::register('product', Mage::getModel('catalog/product')->load($productId));
        $this->getResponse()->setBody($this->getBlock("form_{$postType}")->toHtml());
    }

    /**
     * Cancels the delivery from being sent
     */
    public function cancelAction()
    {
        $logIds = $this->getRequest()->getParam('id', array());
        $cancelled = 0;
        if (is_numeric($logIds)) {
            $logIds = array($logIds);
        }

        $session = Mage::getSingleton('adminhtml/session');
        if (count($logIds) > 0) {
            foreach ($logIds as $logId) {
                $log = Mage::getModel('bronto_reviews/log')->load($logId);
                if ($log->hasLogId()) {
                    try {
                        $log->cancel();
                        $cancelled++;
                    } catch (Exception $e) {
                        $session->addError($e->getMessage());
                    }
                }
            }
            Mage::getModel('bronto_reviews/log')->flushCancelableDeliveries();
            $session->addSuccess($this->__('Total of %d post-purchase email(s) have been successfully cancelled.', $cancelled));
        } else {
            $session->addError($this->__('Please select post-purchase email(s).'));
        }
        $this->_redirectToReferrer('*/*/deliveries');
    }

    /**
     * Purges any deliveries
     */
    public function deleteAction()
    {
        $logIds = $this->getRequest()->getParam('id', array());
        $deleted = 0;
        if (is_numeric($logIds)) {
            $logIds = array($logIds);
        }

        $session = Mage::getSingleton('adminhtml/session');
        if (count($logIds) > 0) {
            foreach ($logIds as $logId) {
                $log = Mage::getModel('bronto_reviews/log')->load($logId);
                if ($log->hasLogId()) {
                    try {
                        $log->cancel()->delete();
                        $deleted++;
                    } catch (Exception $e) {
                        $session->addError($e->getMessage());
                    }
                }
            }
            Mage::getModel('bronto_reviews/log')->flushCancelableDeliveries();
            $session->addSuccess($this->__('Total of %d post-purchase email(s) have been purged.', $deleted));
        } else {
            $session->addError($this->__('Please select post purchase email(s).'));
        }
        $this->_redirectToReferrer('*/*/deliveries');
    }

    /**
     * Returns to the referrer or a default URL
     *
     * @param string $defaultUrl
     */
    protected function _redirectToReferrer($defaultUrl)
    {
        $refererUrl = $this->getRequest()->getServer('HTTP_REFERER');
        if (!empty($refererUrl)) {
            return $this->getResponse()->setRedirect($refererUrl);
        }
        return $this->_redirect($defaultUrl);
    }
}
