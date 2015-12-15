<?php

/**
 * @category Bronto
 * @package  Order
 */
class Bronto_Order_Adminhtml_OrderController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Run immediately
     */
    public function runAction()
    {
        $result = array('total' => 0, 'success' => 0, 'error' => 0);
        $model  = Mage::getModel('bronto_order/observer');
        $helper = Mage::helper('bronto_order');
        $limit  = $helper->getLimit();

        try {
            if ($storeIds = $helper->getStoreIds()) {
                if (!is_array($storeIds)) {
                    $storeIds = array($storeIds);
                }
                foreach ($storeIds as $storeId) {
                    if ($limit <= 0) {
                        continue;
                    }
                    $storeResult = $model->processOrdersForStore($storeId, $limit);
                    $result['total'] += $storeResult['total'];
                    $result['success'] += $storeResult['success'];
                    $result['error'] += $storeResult['error'];
                    $limit = $limit - $storeResult['total'];
                }
            } else {
                $result = $model->processOrders(true);
            }

            if (is_array($result)) {
                $this->_getSession()->addSuccess(sprintf("Processed %d Orders (%d Error / %d Success)", $result['total'], $result['error'], $result['success']));
            } else {
                $this->_getSession()->addError('Scheduled Import failed: ' . $result);
            }
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            $helper->writeError($e);
        }

        $returnParams = array('section' => 'bronto_order');
        $returnParams = array_merge($returnParams, $helper->getScopeParams());
        $this->_redirect('*/system_config/edit', $returnParams);
    }

    /**
     * Reset all Orders
     */
    public function resetAction()
    {
        $helper   = Mage::helper('bronto_order');
        $storeIds = $helper->getStoreIds();
        $resource = Mage::getResourceModel('bronto_order/queue');
        $adapter  = $resource->getWriteAdapter();

        $queue_id = $this->getRequest()->getParam('queue_id', false);
        $suppressed = $this->getRequest()->getParam('suppressed', false);

        $where = array();
        if ($storeIds) {
            $where = array('store_id IN (?)' => $storeIds);
        }

        if ($queue_id) {
            $where['queue_id = ?'] = $queue_id;
        }

        if ($suppressed) {
            $where['bronto_suppressed IS NOT NULL'] = '';
        }

        try {
            $adapter->update(
                $resource->getTable('bronto_order/queue'), array(
                    'bronto_imported'   => null,
                    'bronto_suppressed' => null,
                ), $where
            );
        } catch (Exception $e) {
            $helper->writeError($e);
            $this->_getSession()->addError('Reset failed: ' . $e->getMessage());
        }

        $returnParams = array('section' => 'bronto_order');
        $returnParams = array_merge($returnParams, $helper->getScopeParams());
        $this->_redirect('*/system_config/edit', $returnParams);
    }

    /**
     * Mark all Orders as imported
     */
    public function markAction()
    {
        $helper   = Mage::helper('bronto_order');
        $storeIds = $helper->getStoreIds();
        $resource = Mage::getResourceModel('bronto_order/queue');
        $adapter  = $resource->getWriteAdapter();

        if ($storeIds) {
            $where = array('store_id IN (?)' => $storeIds);
        }
        $where['bronto_suppressed IS NULL'] = '';

        try {
            $date = Mage::getSingleton('core/date')->gmtDate();
            $adapter->update(
                $resource->getTable('bronto_order/queue'),
                array('bronto_imported' => $date),
                $where
            );
            $helper->writeInfo("Mark All Orders was explicitly pressed.");
        } catch (Exception $e) {
            $helper->writeError($e);
            $this->_getSession()->addError('Mark failed: ' . $e->getMessage());
        }

        $returnParams = array('section' => 'bronto_order');
        $returnParams = array_merge($returnParams, $helper->getScopeParams());
        $this->_redirect('*/system_config/edit', $returnParams);
    }

    /**
     * Pull Orders from Order Table if not in queue
     */
    public function syncAction()
    {
        $helper   = Mage::helper('bronto_order');
        $imported = 0;

        try {
            $waiting = $helper->getMissingOrdersCount();
            if ($waiting) {
                foreach ($helper->getMissingOrders() as $order) {
                    Mage::getModel('bronto_order/queue')->getOrderRow($order['entity_id'], null, $order['store_id'])
                        ->setQuoteId($order['quote_id'])
                        ->setCreatedAt($order['created_at'])
                        ->setUpdatedAt(Mage::getSingleton('core/date')->gmtDate())
                        ->setBrontoImported(0)
                        ->save();

                    $imported++;
                }
            }
        } catch (Exception $e) {
            $helper->writeError($e);
            $this->_getSession()->addError('Sync failed: ' . $e->getMessage());
        }

        if ($imported == $waiting && $waiting == 0) {
            $this->_getSession()->addSuccess($helper->__('All Orders are synced to the queue.'));
        } else {
            $this->_getSession()->addSuccess(sprintf("%d of %d Orders were added to the Queue", $imported, $waiting));
        }

        $returnParams = array('section' => 'bronto_order');
        $returnParams = array_merge($returnParams, $helper->getScopeParams());
        $this->_redirect('*/system_config/edit', $returnParams);
    }

    /**
     * Action to handle providing suppression table in config page
     */
    public function suppressionAction()
    {
        $request = $this->getRequest();
        $page    = $request->getParam('page', 1);
        $limit   = 10;

        // Get Suppressed Items
        $suppressed = array();
        $collection = Mage::getModel('bronto_order/queue')->getCollection()
            ->addBrontoSuppressedFilter()
            ->addStoreFilter(Mage::helper('bronto_common')->getStoreIds())
            ->orderByUpdatedAt()
            ->setPageSize($limit)
            ->setCurPage($page);

        $items = $collection->getItems();
        foreach ($items as $item) {
            $order        = Mage::getModel('sales/order')->load($item->getOrderId());
            $orderLink    = Mage::helper('bronto_common')->getScopeUrl('/sales_order/view/', array('order_id' => $item->getOrderId()));
            $customerName = Mage::getModel('customer/customer')->load($order->getCustomerId())->getName();
            $customerLink = Mage::helper('bronto_common')->getScopeUrl('/customer/edit/', array('id' => $item->getCustomerId()));
            $storeName    = Mage::getModel('core/store')->load($item->getStoreId())->getName();
            $resetLink    = Mage::helper('bronto_common')->getScopeUrl('adminhtml/order/reset', array('queue_id' => $item->getId()));
            $suppressed[] = array(
                'updated_at' => $item->getUpdatedAt(),
                'order'      => "<a href=\"{$orderLink}\">{$order->getIncrementId()}</a>",
                'customer'   => "<a href=\"{$customerLink}\">{$customerName}</a>",
                'store_id'   => $storeName,
                'reason'     => $item->getBrontoSuppressed(),
                'action'     => "<a href=\"{$resetLink}\">Reset</a>",
            );
        }

        $prevPage = ($page > 1) ? $page - 1 : false;

        $remaining = $collection->getSize() - ($limit * $page);
        $nextPage  = ($remaining > 0) ? $page + 1 : false;

        $html = $this->_getSuppressionTableHtml($suppressed, $prevPage, $nextPage);

        $this->getResponse()->setBody($html);
    }

    /**
     * Get HTML table for suppression items
     *
     * @param $suppressedItems
     * @param $prevPage
     * @param $nextPage
     *
     * @return string
     */
    protected function _getSuppressionTableHtml($suppressedItems, $prevPage, $nextPage)
    {
        $html = '';
        if ($prevPage) {
            $html .= '<div class="bronto-suppression-interface-control previous" onclick="loadSuppressionTable(' . $prevPage . ')">Load Newer</div>';
        }
        $html .= '
        <table class="border">
            <thead>
                <tr class="headings">
                    <th style="white-space: nowrap">Date Suppressed</th>
                    <th style="white-space: nowrap">Order</th>
                    <th style="white-space: nowrap">Customer</th>
                    <th style="white-space: nowrap">Store</th>
                    <th width="100%">Reason for Suppression</th>
                    <th style="white-space: nowrap">Action</th>
                </tr>
            </thead>
            <tbody>';

        if (count($suppressedItems)) {
            foreach ($suppressedItems as $suppressed) {
                $html .= '<tr>';
                foreach ($suppressed as $value) {
                    $html .= "<td style=\"white-space: nowrap\">{$value}</td>";
                }
                $html .= '</tr>';
            }
        } else {
            $html .= '<tr><td colspan="6"><strong>No Suppressed Items</strong></td></tr>';
        }

        $html .= '
            </tbody>
        </table>';
        if ($nextPage) {
            $html .= '<div class="bronto-suppression-interface-control next" onclick="loadSuppressionTable(' . $nextPage . ')">Load Older</div>';
        }

        return $html;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_isSectionAllowed('bronto_order');
    }

    /**
     * Check if specified section allowed in ACL
     *
     * Will forward to deniedAction(), if not allowed.
     *
     * @param string $section
     *
     * @return bool
     */
    protected function _isSectionAllowed($section)
    {
        try {
            $session        = Mage::getSingleton('admin/session');
            $resourceLookup = "admin/system/config/{$section}";
            if ($session->getData('acl') instanceof Mage_Admin_Model_Acl) {
                $resourceId = $session->getData('acl')->get($resourceLookup)->getResourceId();
                if (!$session->isAllowed($resourceId)) {
                    throw new Exception('');
                }

                return true;
            }
        } catch (Zend_Acl_Exception $e) {
            $this->norouteAction();
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);

            return false;
        } catch (Exception $e) {
            $this->deniedAction();
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);

            return false;
        }

        return false;
    }

}
