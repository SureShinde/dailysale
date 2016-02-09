<?php
/**
* Braintree Payments Extension
*
* This source file is subject to the Braintree Payment System Agreement (https://www.braintreepayments.com/legal)
*
* DISCLAIMER
* This file will not be supported if it is modified.
*
* @copyright   Copyright (c) 2015 Braintree. (https://www.braintreepayments.com/)
*/

class Braintree_Payments_CreditCardController extends Mage_Core_Controller_Front_Action
{
    /**
     * Index action. List of accounts.
     */
    public function indexAction()
    {
        $this->loadLayout();
        $this->_initMessages();
        $this->renderLayout();
    }

    /**
     * Delete account action
     */
    public function deleteAction()
    {
        $this->loadLayout();
        $this->_initMessages();
        $this->renderLayout();
    }

    /**
     * Init layout messages, add page title
     */
    protected function _initMessages()
    {
        $this->_initLayoutMessages('customer/session');
        $this->getLayout()->getBlock('head')->setTitle($this->__('My Credit Cards'));
    }

    /**
     * Add errors from Braintree into customer session
     * 
     * @param Braintree_Result_Error $errors
     */
    protected function _addError($errors)
    {
        $messages = explode("\n", $errors->message);
        foreach ($messages as $error) {
            Mage::getSingleton('customer/session')->addError($this->__($error));
        }
    }

    /**
     * Action predispatch
     *
     * Check if extension and vault are enabled, otherwise no route
     */
    public function preDispatch()
    {
        parent::preDispatch();
        if (!Mage::getStoreConfig('payment/braintree/use_vault') || !Mage::getStoreConfig('payment/braintree/active')) {
            $this->_redirect('noRoute');
        }
        if (!Mage::getSingleton('customer/session')->authenticate($this)) {
            $this->setFlag('', 'no-dispatch', true);
        }
    }

    /**
     * Add new card action
     */
    public function newAction()
    {
        $this->loadLayout();
        $this->_initMessages();
        $braintree = Mage::getModel('braintree_payments/creditcard');
        if ($braintree->exists(Mage::getSingleton('customer/session')->getCustomer()->getId())) {
            $this->getLayout()->getBlock('customer_creditcard_management')
                ->setType(Braintree_Payments_Block_Creditcard_Management::TYPE_NEW_CART);
        } else {
            $this->getLayout()->getBlock('customer_creditcard_management')
                ->setType(Braintree_Payments_Block_Creditcard_Management::TYPE_NEW_CUSTOMER);
        }
        $this->renderLayout();
    }

    /**
     * Save credit cards action
     */
    public function saveAction()
    {
        try {
            Mage::getModel('braintree_payments/creditcard')->saveInVault(Mage::app()->getRequest()->getPost());
            Mage::getSingleton('customer/session')->addSuccess($this->__('Credit card successfully added'));
        } catch (Exception $e) {
            Mage::getSingleton('customer/session')->addError(
                $this->__('There was error during saving card data') . '. ' . $e->getMessage()
            );
        }
        $this->_redirect('customer/creditcard/index');
    }

    /**
     * Save credit cards action
     */
    public function updateAction()
    {
        $token = Mage::app()->getRequest()->getParam('token');
        try {
            Mage::getModel('braintree_payments/creditcard')->saveInVault(Mage::app()->getRequest()->getPost(), $token);
            Mage::getSingleton('customer/session')->addSuccess($this->__('Credit card successfully updated'));
        } catch (Exception $e) {
            Mage::getSingleton('customer/session')->addError(
                $this->__('There was error during saving card data') . '. ' . $e->getMessage()
            );
        }
        $this->_redirect('customer/creditcard/index');
    }

    /**
     * Used as callback for delete
     */
    public function deleteConfirmAction()
    {
        $braintree = Mage::getModel('braintree_payments/creditcard');
        $result = $braintree->deletePaymentMethod($this->getRequest()->getPost('token'));
        if ($result->success) {
            Mage::getSingleton('customer/session')->addSuccess($this->__('Credit card successfully deleted'));
        } else {
            Mage::register('braintree_result', $result);
            $this->_addError($result);
        }
        $this->_redirect('customer/creditcard/index');
    }

    /**
     * Edit card action
     */
    public function editAction()
    {
        if ($this->_hasToken()) {
            $this->loadLayout();
            $this->_initMessages();
            $this->renderLayout();
        } else {
            $this->_redirect('customer/creditcard/index');
        }
    }

    /**
     * If token exists
     * 
     * @return boolean
     */
    protected function _hasToken()
    {
        return Mage::app()->getRequest()->getParam('token') || Mage::registry('braintree_result');
    }
}
