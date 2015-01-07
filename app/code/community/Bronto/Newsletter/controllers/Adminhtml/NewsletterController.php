<?php

/**
 * @category   Bronto
 * @package    Bronto_Newsletter
 */
class Bronto_Newsletter_Adminhtml_NewsletterController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Run immediately
     */
    public function runAction()
    {
        $result = array('total' => 0, 'success' => 0, 'error' => 0);
        $model  = Mage::getModel('bronto_newsletter/observer');
        $helper = Mage::helper('bronto_newsletter');
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
                    $storeResult = $model->processSubscribersForStore($storeId, $limit);
                    $result['total'] += $storeResult['total'];
                    $result['success'] += $storeResult['success'];
                    $result['error'] += $storeResult['error'];
                    $limit = $limit - $storeResult['total'];
                }
            } else {
                $result = $model->processSubscribers(true);
            }

            if (is_array($result)) {
                $this->_getSession()->addSuccess(sprintf("Processed %d Subscribers (%d Error / %d Success)", $result['total'], $result['error'], $result['success']));
            } else {
                $this->_getSession()->addError('Scheduled Sync failed: ' . $result);
            }
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            $helper->writeError($e);
        }

        $returnParams = array('section' => 'bronto_newsletter');
        $returnParams = array_merge($returnParams, $helper->getScopeParams());
        $this->_redirect('*/system_config/edit', $returnParams);
    }

    /**
     * Reset all Subscribers
     */
    public function resetAction()
    {
        $helper   = Mage::helper('bronto_newsletter');
        $resource = Mage::getResourceModel('bronto_newsletter/queue');
        $adapter  = $resource->getWriteAdapter();

        $queue_id = $this->getRequest()->getParam('queue_id', false);
        $suppressed = $this->getRequest()->getParam('suppressed', false);

        try {
            $update = array('bronto_suppressed' => null);
            $where = array();
            if ($queue_id) {
                $update['imported'] = null;
                $where['queue_id = ?'] = $queue_id;
            } else if ($suppressed) {
                $update['imported'] = null;
                $where['bronto_suppressed IS NOT NULL'] = '';
            } else {
                $update['imported'] = 2;
                $where['imported'] = 1;
            }
            $adapter->update(
                $resource->getTable('bronto_newsletter/queue'),
                $update,
                $where
            );
        } catch (Exception $e) {
            $helper->writeError($e);
            $this->_getSession()->addError('Reset failed: ' . $e->getMessage());
        }

        $returnParams = array('section' => 'bronto_newsletter');
        $returnParams = array_merge($returnParams, $helper->getScopeParams());
        $this->_redirect('*/system_config/edit', $returnParams);
    }

    /**
     * Marks all Subscribers as imported
     */
    public function markAction()
    {
        $helper = Mage::helper('bronto_newsletter');
        $resource = Mage::getResourceModel('bronto_newsletter/queue');
        $adapter = $resource->getWriteAdapter();

        try {
            $adapter->update(
                $resource->getTable('bronto_newsletter/queue'),
                array('imported' => 1),
                array('bronto_suppressed IS NULL' => ''));
            $helper->writeInfo("Mark All Subscribers was explicitly pressed.");
        } catch (Exception $e) {
            $helper->writeError($e);
            $this->_getSession()->addError('Mark All failed: ' . $e->getMessage());
        }

        $returnParams = array('section' => 'bronto_newsletter');
        $returnParams = array_merge($returnParams, $helper->getScopeParams());
        $this->_redirect('*/system_config/edit', $returnParams);
    }

    /**
     * Pull Subscribers from Subscribers Table if not in queue
     */
    public function syncAction()
    {
        $helper   = Mage::helper('bronto_newsletter');
        $imported = 0;

        try {
            $subscribers = $helper->getMissingSubscribers();
            $waiting     = count($subscribers);

            if ($waiting > 0) {
                foreach ($subscribers as $subscriber) {
                    // Convert Magento subscriber status to bronto subscriber status
                    switch ($subscriber['subscriber_status']) {
                        case Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED:
                            $status = Bronto_Api_Contact::STATUS_ACTIVE;
                            break;

                        case Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED:
                            $status = Bronto_Api_Contact::STATUS_UNSUBSCRIBED;
                            break;

                        case Mage_Newsletter_Model_Subscriber::STATUS_UNCONFIRMED:
                            $status = Bronto_Api_Contact::STATUS_UNCONFIRMED;
                            break;

                        case Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE:
                        default:
                            $status = Bronto_Api_Contact::STATUS_TRANSACTIONAL;
                            break;
                    }

                    // Create Subscriber
                    $now = Mage::getSingleton('core/date')->gmtDate();
                    Mage::getModel('bronto_newsletter/queue')->getContactRow($subscriber['subscriber_id'], $subscriber['store_id'])
                        ->setStatus($status)
                        ->setSubscriberEmail($subscriber['subscriber_email'])
                        ->setMessagePreference('html')
                        ->setSource('api')
                        ->setImported(0)
                        ->setBrontoSuppressed(null)
                        ->setCreatedAt($now)
                        ->setUpdatedAt($now)
                        ->save();

                    $imported++;
                }
            }
        } catch (Exception $e) {
            $helper->writeError($e);
            $this->_getSession()->addError('Sync failed: ' . $e->getMessage());
        }

        if ($imported == $waiting && $waiting == 0) {
            $this->_getSession()->addSuccess($helper->__('All Subscribers are synced to the queue.'));
        } else {
            $this->_getSession()->addSuccess(sprintf("%d of %d Subscribers were added to the Queue", $imported, $waiting));
        }

        $returnParams = array('section' => 'bronto_newsletter');
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
        $collection = Mage::getModel('bronto_newsletter/queue')->getCollection()
            ->addBrontoSuppressedFilter()
            ->addStoreFilter(Mage::helper('bronto_common')->getStoreIds())
            ->setPageSize($limit)
            ->setCurPage($page);

        $items = $collection->getItems();
        foreach ($items as $item) {
            $subscriber   = Mage::getModel('newsletter/subscriber')->load($item->getSubscriberId());
            $email        = $subscriber->getEmail();
            $resetLink    = Mage::helper('bronto_common')->getScopeUrl('adminhtml/newsletter/reset', array('queue_id' => $item->getId()));
            $suppressed[] = array(
                'subscriber' => $email,
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
                    <th style="white-space: nowrap">Subscriber Email</th>
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
            $html .= '<tr><td colspan="3"><strong>No Suppressed Items</strong></td></tr>';
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
        return $this->_isSectionAllowed('bronto_newsletter');
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
