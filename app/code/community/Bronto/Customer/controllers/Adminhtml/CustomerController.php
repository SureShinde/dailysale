<?php

/**
 * @category   Bronto
 * @package    Bronto_Customer
 */
class Bronto_Customer_Adminhtml_CustomerController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Run immediately
     */
    public function runAction()
    {
        $result = array('total' => 0, 'success' => 0, 'error' => 0);
        $model  = Mage::getModel('bronto_customer/observer');
        $helper = Mage::helper('bronto_customer');
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
                    $storeResult = $model->processCustomersForStore($storeId, $limit);
                    $result['total'] += $storeResult['total'];
                    $result['success'] += $storeResult['success'];
                    $result['error'] += $storeResult['error'];
                    $limit = $limit - $storeResult['total'];
                }
            } else {
                $result = $model->processCustomers(true);
            }

            if (is_array($result)) {
                $this->_getSession()->addSuccess(sprintf("Processed %d Customers (%d Error / %d Success)", $result['total'], $result['error'], $result['success']));
            } else {
                $this->_getSession()->addError('Scheduled Import failed: ' . $result);
            }

        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            $helper->writeError($e);
        }

        $returnParams = array('section' => 'bronto_customer');
        $returnParams = array_merge($returnParams, $helper->getScopeParams());
        $this->_redirect('*/system_config/edit', $returnParams);
    }

    /**
     * Marks all Customers as imported
     */
    public function markAction()
    {
        $helper = Mage::helper('bronto_customer');
        $resource = Mage::getResourceModel('bronto_customer/queue');
        $adapter = $resource->getWriteAdapter();

        try {
            $date = Mage::getSingleton('core/date')->gmtDate();
            $adapter->update(
                $resource->getTable('bronto_customer/queue'),
                array('bronto_imported' => $date),
                array(
                    'bronto_imported IS NULL' => '',
                    'bronto_suppressed IS NULL' => ''
                ));
            $helper->writeInfo("Mark All Customers was explicitly pressed.");
        } catch (Exception $e) {
            $helper->writeError($e);
            $this->_getSession()->addError('Mark All failed: ' . $e->getMessage());
        }

        $returnParams = array('section' => 'bronto_customer');
        $returnParams = array_merge($returnParams, $helper->getScopeParams());
        $this->_redirect('*/system_config/edit', $returnParams);
    }

    /**
     * Reset all Customers
     */
    public function resetAction()
    {
        $helper   = Mage::helper('bronto_customer');
        $storeIds = $helper->getStoreIds();
        $resource = Mage::getResourceModel('bronto_customer/queue');
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
                $resource->getTable('bronto_customer/queue'),
                array(
                    'bronto_imported'   => null,
                    'bronto_suppressed' => null,
                ),
                $where
            );
        } catch (Exception $e) {
            $helper->writeError($e);
            $this->_getSession()->addError('Reset failed: ' . $e->getMessage());
        }

        $returnParams = array('section' => 'bronto_customer');
        $returnParams = array_merge($returnParams, $helper->getScopeParams());
        $this->_redirect('*/system_config/edit', $returnParams);
    }

    /**
     * Pull Customers from Customer Table if not in queue
     */
    public function syncAction()
    {
        $helper   = Mage::helper('bronto_customer');
        $imported = 0;

        try {
            $customers = Mage::helper('bronto_customer')->getMissingCustomers();
            $waiting   = count($customers);

            if ($waiting > 0) {
                foreach ($customers as $customer) {
                    Mage::getModel('bronto_customer/queue')->getCustomerRow($customer['entity_id'], $customer['store_id'])
                        ->setCreatedAt($customer['created_at'])
                        ->setUpdatedAt(Mage::getSingleton('core/date')->gmtDate())
                        ->setBrontoImported(0)
                        ->save();

                    $imported++;
                }
            }
        } catch (Exception $e) {
            Mage::helper('bronto_customer')->writeError($e);
            $this->_getSession()->addError('Sync failed: ' . $e->getMessage());
        }

        if ($imported == $waiting && $waiting == 0) {
            $this->_getSession()->addSuccess($helper->__('All Customers are synced to the queue.'));
        } else {
            $this->_getSession()->addSuccess(sprintf("%d of %d Customers were added to the Queue", $imported, $waiting));
        }
        $returnParams = array('section' => 'bronto_customer');
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
        $collection = Mage::getModel('bronto_customer/queue')->getCollection()
            ->addBrontoSuppressedFilter()
            ->addStoreFilter(Mage::helper('bronto_common')->getStoreIds())
            ->orderByUpdatedAt()
            ->setPageSize($limit)
            ->setCurPage($page);

        $items = $collection->getItems();
        foreach ($items as $item) {
            $customerName  = Mage::getModel('customer/customer')->load($item->getCustomerId())->getName();
            $customerEmail = Mage::getModel('customer/customer')->load($item->getCustomerId())->getEmail();
            $customerLink  = Mage::helper('bronto_common')->getScopeUrl('/customer/edit/', array('id' => $item->getCustomerId()));
            $storeName     = Mage::getModel('core/store')->load($item->getStoreId())->getName();
            $resetLink     = Mage::helper('bronto_common')->getScopeUrl('adminhtml/customer/reset', array('queue_id' => $item->getId()));
            $suppressed[]  = array(
                'updated_at' => $item->getUpdatedAt(),
                'customer'   => "<a href=\"{$customerLink}\">{$customerName}</a>",
                'email'      => $customerEmail,
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
                    <th style="white-space: nowrap">Customer</th>
                    <th style="white-space: nowrap">Customer Email</th>
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
        return $this->_isSectionAllowed('bronto_customer');
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
