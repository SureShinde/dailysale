<?php

include_once "app/code/core/Mage/Sales/controllers/OrderController.php";

class Unirgy_Rma_OrderController extends Mage_Sales_OrderController
{
    public function rmaAction()
    {
        $this->_viewAction();
    }

    public function newRmaAction()
    {
        try {
            $rma = $this->_initRma();
            $this->_viewAction();
        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
            $this->_redirect('*/*/view', array('order_id'=>$this->getRequest()->getParam('order_id')));
        }
    }

    public function saveRmaAction()
    {
        try {
            $this->_saveRma();
            Mage::getSingleton('core/session')->addSuccess(
                Mage::getStoreConfig('urma/message/customer_success')
            );
            $this->_redirect('*/*/rma', array('order_id'=>$this->getRequest()->getParam('order_id')));
        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
            $this->_redirect('*/*/newRma', array('order_id'=>$this->getRequest()->getParam('order_id')));
        }
    }

    protected function _initRma($forSave=false)
    {
        $rma = false;
        $rmaId = $this->getRequest()->getParam('rma_id');
        $orderId = $this->getRequest()->getParam('order_id');
        if ($rmaId) {
            $rma = Mage::getModel('urma/rma')->load($rmaId);
        } elseif ($orderId) {
            $order      = Mage::getModel('sales/order')->load($orderId);

            if (!$order->getId()) {
                Mage::throwException(Mage::helper('udropship')->__('The order no longer exists.'));
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
        return $rma;
    }

    public function printLabelAction()
    {
        try {
            if ($rma = $this->_initRma()) {
                Mage::getModel('udropship/label_batch')
                    ->setForcedFilename('rma_label_'.$rma->getIncrementId())
                    ->setVendor($rma->getVendor())
                    ->renderRmas(array($rma))
                    ->prepareLabelsDownloadResponse();
            } else {
                $response = array(
                    'error'     => true,
                    'message'   => Mage::helper('udropship')->__('Cannot initialize rma.'),
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
                'message'   => Mage::helper('udropship')->__('Cannot printf label.'),
            );
        }
        if (is_array($response)) {
            Mage::getSingleton('core/session')->addError($response['message']);
            $this->_redirect('*/*/rma', array('order_id'=>$this->getRequest()->getParam('order_id')));
        }
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
            $rma->setIsCutomer(true);
            $trans->addObject($rma);
        }
        $trans->addObject($rma->getOrder())->save();

        foreach ($rmas as $rma) {
            $rma->sendEmail(!empty($data['send_email']), $comment);
            Mage::helper('urma')->sendNewRmaNotificationEmail($rma, $comment);
        }
        Mage::helper('udropship')->processQueue();
    }
}