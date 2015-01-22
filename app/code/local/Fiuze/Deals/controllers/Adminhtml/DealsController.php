<?php

/**
 * @category    Fiuze
 * @package     Fiuze_Deals
 * @author     Alena Tsareva <alena.tsareva@webinse.com>
 */
class Fiuze_Deals_Adminhtml_DealsController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Init actions
     *
     * @return Mage_Adminhtml_Cms_PageController
     */

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('fiuze_deals')
            ->_title($this->__('Daily Cron Products'));

        $this->_addBreadcrumb(Mage::helper('fiuze_deals')->__('Fiuze Daily'), Mage::helper('fiuze_deals')->__('Daily Cron Products'), $this->getUrl());
        $this->_title($this->__('Deals'));
        return $this;
    }

    protected function _initGroup()
    {
        $this->_title($this->__('Product Discount'))->_title($this->__('Manage'));

        Mage::register('current_product_deal', Mage::getModel('catalog/product'));
        $userId = $this->getRequest()->getParam('id');
        if (!is_null($userId)) {
            Mage::registry('current_product_deal')->load($userId);
        }
    }

    /**
     * List action for grid
     */
    public function listAction()
    {
        $this->_initAction();
        $this->renderLayout();
    }

    /**
     * List action for grid
     */
    public function gridAction()
    {
        $this->_initAction();
        $this->renderLayout();
    }

    public function editAction()
    {
        $this->_initGroup();
        $this->loadLayout()
            ->_setActiveMenu('fiuze_deals')
            ->_title($this->__('Daily Cron Products'));
        $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Product Discount'), Mage::helper('adminhtml')->__('Product Discount'));
        $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Discount'), Mage::helper('adminhtml')->__('Discount'), $this->getUrl('*/discount'));

        if ($this->getRequest()->getParam('id')) {
            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Edit Discount'), Mage::helper('adminhtml')->__('Edit Discount'));
        } else {
            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('New Discount'), Mage::helper('adminhtml')->__('New Discount'));
        }
        $this->getLayout()->getBlock('content')
            ->append($this->getLayout()->createBlock('fiuze_deals/adminhtml_deals_edit', 'discount')
                ->setEditMode((bool)Mage::registry('current_product_deal')->getId()));

        $this->renderLayout();
    }

    /**
     * Controller for save new deal product
     */
    public function saveAction()
    {
        $data = $this->getRequest()->getParams();

        $id = (int)$this->getRequest()->getParam('id');
        if ($id) {
            $productDeals = Mage::getResourceModel('fiuze_deals/deals_collection')
                ->addFilter('product_id', $id)->getFirstItem();
            try {
                $productDeals->setDealsPrice($data['deal_price']);
                $productDeals->setDealsQty($data['deal_qty']);
                $productDeals->setSortOrder($data['sort_order']);
                $productDeals->setDealsActive($data['deals_active'] ? true : false);
                $productDeals->save();
                $this->getResponse()->setRedirect($this->getUrl('*/*/list'));
                return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->getResponse()->setRedirect($this->getUrl('*/*/edit', array('id' => $id)));
                return;
            }
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('User with id \'' . (int)$id . '\' not found.'));
            $this->getResponse()->setRedirect($this->getUrl('*/*/edit', array('id' => $id)));
            return;
        }
    }

    public function newAction()
    {
        $this->_title($this->__('New'));

        if (!Mage::getResourceModel('fiuze_deals/deals_collection')->count()) {
            Mage::getSingleton('adminhtml/session')->addWarning(Mage::helper('fiuze_deals')->__('Warning: add current deals category in system config'));
            $this->_forward('list');
            return;
        }
        $this->_initAction();
        $this->renderLayout();
    }

    public function cronAction()
    {
        $this->_initAction();
        $this->renderLayout();
    }
}

