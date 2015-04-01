<?php

class Unirgy_Rma_Adminhtml_Order_RmaController extends Mage_Adminhtml_Controller_Action
{
    protected function _getItemQtys()
    {
        $data = $this->getRequest()->getParam('urma');
        if (isset($data['items'])) {
            $qtys = $data['items'];
        } else {
            $qtys = array();
        }
        return $qtys;
    }

    protected function _initRma($forSave=true)
    {
        $rma = false;
        $rmaId = $this->getRequest()->getParam('rma_id');
        $orderId = $this->getRequest()->getParam('order_id');
        if ($rmaId) {
            $rma = Mage::getModel('urma/rma')->load($rmaId);
            if (!$rma->getId()) {
                Mage::throwException($this->__('This Return no longer exists.'));
            }
        } elseif ($orderId) {
            $order      = Mage::getModel('sales/order')->load($orderId);

            if (!$order->getId()) {
                Mage::throwException($this->__('The order no longer exists.'));
            }

            $data = $this->getRequest()->getParam('rma');
            if (isset($data['items'])) {
                $qtys = $data['items'];
            } else {
                $qtys = array();
            }
            if (isset($data['items_condition'])) {
                $conditions = $data['items_condition'];
            } else {
                $conditions = array();
            }

            if ($forSave) {
                $rma = Mage::getModel('urma/serviceOrder', $order)->prepareRmaForSave($qtys, $conditions);
            } else {
                $rma = Mage::getModel('urma/serviceOrder', $order)->prepareRma($qtys);
            }

        }

        Mage::register('current_rma', $rma);
        if (!empty($rma)) {
            if ($forSave) {
                reset($rma);
                $_rma = current($rma);
                Mage::register('current_order', $_rma->getOrder());
            } else {
                Mage::register('current_order', $rma->getOrder());
            }
        }

        return $rma;
    }

    protected function _saveRma()
    {
        $rmas = $this->_initRma(true);
        $data = $this->getRequest()->getPost('rma');
        $data['send_email'] = true;
        $comment = '';

        if (empty($rmas)) {
            Mage::throwException('Return could not be created');
        }

        foreach ($rmas as $rma) {
            $order = $rma->getOrder();
            $rma->register();
        }

        if (!empty($data['comment_text'])) {
            foreach ($rmas as $rma) {
                $rma->addComment($data['comment_text'], true, true);
            }
            $comment = $data['comment_text'];
        }

        if (!empty($data['send_email'])) {
            foreach ($rmas as $rma) {
                $rma->setEmailSent(true);
            }
        }
        $rma->setRmaReason(@$data['rma_reason']);

        $order->setCustomerNoteNotify(!empty($data['send_email']));
        $order->setIsInProcess(true);
        $trans = Mage::getModel('core/resource_transaction');
        foreach ($rmas as $rma) {
            $rma->setIsAdmin(true);
            $rma->setUsername(Mage::getSingleton('admin/session')->getUser()->getUsername());
            $trans->addObject($rma);
        }
        $trans->addObject($rma->getOrder())->save();

        foreach ($rmas as $rma) {
            $rma->sendEmail(!empty($data['send_email']), $comment);
            Mage::helper('urma')->sendNewRmaNotificationEmail($rma, $comment);
        }
        Mage::helper('udropship')->processQueue();

        return $rmas;
    }

    public function saveAction()
    {
        try {
            $this->_saveRma();
            $this->_getSession()->addSuccess($this->__('The Return has been created.'));
            Mage::getSingleton('adminhtml/session')->getCommentText(true);
            $this->_redirect('adminhtml/sales_order/view', array('order_id' => $this->getRequest()->getParam('order_id')));
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            $this->_redirect('*/*/new', array('order_id'=>$this->getRequest()->getParam('order_id')));
        }
    }
    
    public function newAction()
    {
        if ($rma = $this->_initRma(false)) {
            $this->_title($this->__('New Return'));

            if ($comment = Mage::getSingleton('adminhtml/session')->getCommentText(true)) {
                $rma->setCommentText($comment);
            }

            $this->loadLayout()
                ->_setActiveMenu('sales/order')
                ->renderLayout();
        } else {
            $this->_redirect('*/sales_order/view', array('order_id'=>$this->getRequest()->getParam('order_id')));
        }
    }
    
    public function viewAction()
    {
        if ($rma = $this->_initRma(false)) {
            $this->_title($this->__('View Return'));

            $this->loadLayout();
            
            $this->_setActiveMenu('sales/order')
                ->renderLayout();
        }
        else {
            $this->_forward('noRoute');
        }
    }
    
    public function addCommentAction()
    {
        try {
            $data = $this->getRequest()->getPost('comment');
            $rma = $this->_initRma(false);
            if (empty($data['comment']) && $data['status']==$rma->getRmaStatus()) {
                Mage::throwException($this->__('Comment text field cannot be empty.'));
            }

            $lhlp = Mage::helper('urma');
            $status = $data['status'];

            if (isset($data['is_customer_notified'])) {
                $data['is_visible_on_front'] = true;
            }
            if (isset($data['is_vendor_notified'])) {
                $data['is_visible_to_vendor'] = true;
            }

            $statusSaveRes = true;
            if ($status!=$rma->getRmaStatus()) {
                $oldStatus = $rma->getRmaStatus();
                $changedComment = $this->__("%s\n\n[%s has changed the shipment status to %s]", $data['comment'], 'Administrator', $status);

                if (isset($data['resolution_notes'])) {
                    $rma->setResolutionNotes($data['resolution_notes']);
                }
                $rma->setRmaStatus($status)->save();
                $commentText = $changedComment;

                $comment = Mage::getModel('urma/rma_comment')
                    ->setComment($commentText)
                    ->setIsCustomerNotified(isset($data['is_customer_notified']))
                    ->setIsVisibleOnFront(isset($data['is_visible_on_front']))
                    ->setIsVendorNotified(isset($data['is_vendor_notified']))
                    ->setIsVisibleToVendor(isset($data['is_visible_to_vendor']))
                    ->setUsername(Mage::getSingleton('admin/session')->getUser()->getUsername())
                    ->setRmaStatus($status);
                $rma->addComment($comment);
                $rma->sendUpdateEmail(!empty($data['is_customer_notified']), $data['comment']);
                $rma->getCommentsCollection()->save();
                if (isset($data['is_vendor_notified'])) {
                    Mage::helper('urma')->sendRmaCommentNotificationEmail($rma, $data['comment']);
                    Mage::helper('udropship')->processQueue();
                }
            } else {
                $comment = Mage::getModel('urma/rma_comment')
                    ->setComment($data['comment'])
                    ->setIsCustomerNotified(isset($data['is_customer_notified']))
                    ->setIsVisibleOnFront(isset($data['is_visible_on_front']))
                    ->setIsVendorNotified(isset($data['is_vendor_notified']))
                    ->setIsVisibleToVendor(isset($data['is_visible_to_vendor']))
                    ->setUsername(Mage::getSingleton('admin/session')->getUser()->getUsername())
                    ->setRmaStatus($status);
                $rma->addComment($comment);
                if (isset($data['resolution_notes'])) {
                    $rma->setResolutionNotes($data['resolution_notes']);
                }
                $rma->sendUpdateEmail(!empty($data['is_customer_notified']), $data['comment']);
                $rma->getCommentsCollection()->save();
                if (isset($data['is_vendor_notified'])) {
                    Mage::helper('urma')->sendRmaCommentNotificationEmail($rma, $data['comment']);
                    Mage::helper('udropship')->processQueue();
                }
            }

            $this->loadLayout();
            $response = $this->getLayout()->getBlock('order_comments')->toHtml();
        } catch (Mage_Core_Exception $e) {
            $response = array(
                'error'     => true,
                'message'   => $e->getMessage()
            );
            $response = Zend_Json::encode($response);
        } catch (Exception $e) {
            Mage::logException($e);
            $response = array(
                'error'     => true,
                'message'   => $this->__('Cannot add new comment.')
            );
            $response = Zend_Json::encode($response);
        }
        $this->getResponse()->setBody($response);
    }
    
    public function rmasTabAction()
    {
        $this->_initRma(false);
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('urma/adminhtml_salesOrderViewTab_rmas')->toHtml()
        );
    }

    public function createLabelAction()
    {
        try {
            $hlp = Mage::helper('udropship');
            $data = $this->getRequest()->getPost('rma');
            if (empty($data['generate_label'])) {
                Mage::throwException($this->__('Wrong generate label request.'));
            }
            if ($rma = $this->_initRma(false)) {
                $labelData = array();
                foreach (array('weight','value','length','width','height','reference','package_count') as $_glKey) {
                    if (isset($data['label_info'][$_glKey])) {
                        $labelData[$_glKey] = $data['label_info'][$_glKey];
                    }
                }
                $extraLblInfo = @$data['extra_label_info'];
                $extraLblInfo = is_array($extraLblInfo) ? $extraLblInfo : array();
                $data = array_merge($data, $extraLblInfo);

                $oldUdropshipMethod = $rma->getUdropshipMethod();
                $oldUdropshipMethodDesc = $rma->getUdropshipMethodDescription();
                if (!empty($data['label_info']['use_method_code'])) {
                    list($useCarrier, $useMethod) = explode('_', $data['label_info']['use_method_code'], 2);
                    if (!empty($useCarrier) && !empty($useMethod)) {
                        $rma->setUdropshipMethod($data['label_info']['use_method_code']);
                        $carrierMethods = Mage::helper('udropship')->getCarrierMethods($useCarrier);
                        $rma->setUdropshipMethodDescription(
                            Mage::getStoreConfig('carriers/'.$useCarrier.'/title', $rma->getOrder()->getStoreId())
                            .' - '.$carrierMethods[$useMethod]
                        );
                    }
                }

                // generate label
                $batch = Mage::getModel('udropship/label_batch')
                    ->setVendor($rma->getVendor())
                    ->processRmas(array($rma), $labelData);

                if (!empty($data['label_info']['use_method_code'])) {
                    $rma->setUdropshipMethod($oldUdropshipMethod);
                    $rma->setUdropshipMethodDescription($oldUdropshipMethodDesc);
                    $rma->getResource()->saveAttribute($rma, 'udropship_method');
                    $rma->getResource()->saveAttribute($rma, 'udropship_method_description');
                }

                // if batch of 1 label is successfull
                if ($batch->getShipmentCnt() && $batch->getLastTrack()) {
                    if (!empty($data['comment'])) {
                        $comment = Mage::getModel('urma/rma_comment')
                            ->setComment($data['comment'])
                            ->setIsCustomerNotified(isset($data['is_customer_notified']))
                            ->setIsVisibleOnFront(isset($data['is_visible_on_front']))
                            ->setUsername(Mage::getSingleton('admin/session')->getUser()->getUsername())
                            ->setRmaStatus($rma->getRmaStatus());
                        $rma->addComment($comment);
                    }
                    $rma->setData('__dummy',1)->save();
                    $rma->sendUpdateEmail(!empty($data['is_customer_notified']), @$data['comment']);
                    Mage::getSingleton('adminhtml/session')->addSuccess('Label was succesfully created');
                } else {
                    if ($batch->getErrors()) {
                        $errs = array();
                        foreach ($batch->getErrors() as $error=>$cnt) {
                            $errs[] = $hlp->__($error, $cnt);
                        }
                        Mage::throwException(implode("\n", $errs));
                    }
                }

                $response = array(
                    'ajaxExpired'  => true,
                    'ajaxRedirect' => $this->getUrl('*/*/view', array('rma_id' => $this->getRequest()->getParam('rma_id')))
                );
                $response = Zend_Json::encode($response);
            } else {
                $response = array(
                    'error'     => true,
                    'message'   => $this->__('Cannot initialize rma for adding tracking number.'),
                );
            }
        } catch (Mage_Core_Exception $e) {
            $response = array(
                'error'     => true,
                'message'   => $e->getMessage(),
            );
        } catch (Exception $e) {
            $response = array(
                'error'     => true,
                'message'   => $this->__('Cannot add tracking number.'),
            );
        }
        if (is_array($response)) {
            $response = Mage::helper('core')->jsonEncode($response);
        }
        $this->getResponse()->setBody($response);
    }

    public function printLabelAction()
    {
        try {
            if ($rma = $this->_initRma(false)) {
                Mage::getModel('udropship/label_batch')
                    ->setForcedFilename('rma_label_'.$rma->getIncrementId())
                    ->setVendor($rma->getVendor())
                    ->renderRmas(array($rma))
                    ->prepareLabelsDownloadResponse();
            } else {
                $response = array(
                    'error'     => true,
                    'message'   => $this->__('Cannot initialize rma.'),
                );
            }
        } catch (Mage_Core_Exception $e) {
            $response = array(
                'error'     => true,
                'message'   => $e->getMessage(),
            );
        } catch (Exception $e) {
            $response = array(
                'error'     => true,
                'message'   => $this->__('Cannot printf label.'),
            );
        }
        if (is_array($response)) {
            $response = Mage::helper('core')->jsonEncode($response);
        }
    }

    public function addTrackAction()
    {
        try {
            $carrier = $this->getRequest()->getPost('carrier');
            $number  = $this->getRequest()->getPost('number');
            $finalPrice = $this->getRequest()->getPost('final_price');
            $title  = $this->getRequest()->getPost('title');
            if (empty($carrier)) {
                Mage::throwException($this->__('The carrier needs to be specified.'));
            }
            if (empty($number)) {
                Mage::throwException($this->__('Tracking number cannot be empty.'));
            }
            if ($rma = $this->_initRma(false)) {
                $track = Mage::getModel('urma/rma_track')
                    ->setNumber($number)
                    ->setFinalPrice($finalPrice)
                    ->setCarrierCode($carrier)
                    ->setTitle($title);
                $rma->addTrack($track)->setData('___dummy',1)->save();

                $this->loadLayout();
                $response = $this->getLayout()->getBlock('rma_tracking')->toHtml();
            } else {
                $response = array(
                    'error'     => true,
                    'message'   => $this->__('Cannot initialize rma for adding tracking number.'),
                );
            }
        } catch (Mage_Core_Exception $e) {
            $response = array(
                'error'     => true,
                'message'   => $e->getMessage(),
            );
        } catch (Exception $e) {
            $response = array(
                'error'     => true,
                'message'   => $this->__('Cannot add tracking number.'),
            );
        }
        if (is_array($response)) {
            $response = Mage::helper('core')->jsonEncode($response);
        }
        $this->getResponse()->setBody($response);
    }

    public function removeTrackAction()
    {
        $trackId    = $this->getRequest()->getParam('track_id');
        $rmaId = $this->getRequest()->getParam('rma_id');
        $track = Mage::getModel('urma/rma_track')->load($trackId);
        if ($track->getId()) {
            try {
                if ($rmaId = $this->_initRma(false)) {
                    $track->delete();

                    $this->loadLayout();
                    $response = $this->getLayout()->getBlock('rma_tracking')->toHtml();
                } else {
                    $response = array(
                        'error'     => true,
                        'message'   => $this->__('Cannot initialize rma for delete tracking number.'),
                    );
                }
            } catch (Exception $e) {
                $response = array(
                    'error'     => true,
                    'message'   => $this->__('Cannot delete tracking number.'),
                );
            }
        } else {
            $response = array(
                'error'     => true,
                'message'   => $this->__('Cannot load track with retrieving identifier.'),
            );
        }
        if (is_array($response)) {
            $response = Mage::helper('core')->jsonEncode($response);
        }
        $this->getResponse()->setBody($response);
    }

    public function viewTrackAction()
    {
        $trackId    = $this->getRequest()->getParam('track_id');
        $poId = $this->getRequest()->getParam('rma_id');
        $track = Mage::getModel('urma/rma_track')->load($trackId);
        if ($track->getId()) {
            try {
                $response = $track->getNumberDetail();
            } catch (Exception $e) {
                $response = array(
                    'error'     => true,
                    'message'   => $this->__('Cannot retrieve tracking number detail.'),
                );
            }
        } else {
            $response = array(
                'error'     => true,
                'message'   => $this->__('Cannot load track with retrieving identifier.'),
            );
        }

        if ( is_object($response)){
            $className = Mage::getConfig()->getBlockClassName('adminhtml/template');
            $block = new $className();
            $block->setType('adminhtml/template')
                ->setIsAnonymous(true)
                ->setTemplate('urma/rma/tracking/info.phtml');

            $block->setTrackingInfo($response);

            $this->getResponse()->setBody($block->toHtml());
        } else {
            if (is_array($response)) {
                $response = Mage::helper('core')->jsonEncode($response);
            }

            $this->getResponse()->setBody($response);
        }
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/udropship/urma')
        && (
            !in_array($this->getRequest()->getActionName(), array('new', 'save'))
            || Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/urma')
        );
    }
}
