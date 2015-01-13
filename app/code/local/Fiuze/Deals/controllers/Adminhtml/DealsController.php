<?php

/**
 * @category    Fiuze
 * @package     Fiuze_Deals
 * @author     Alena Tsareva <alena.tsareva@webinse.com>
 */

class Fiuze_Deals_Adminhtml_DealsController extends Mage_Adminhtml_Controller_Action {
    /**
     * Init actions
     *
     * @return Mage_Adminhtml_Cms_PageController
     */

    protected function _initAction(){
        $this->loadLayout()
            ->_setActiveMenu('fiuze_deals')
            ->_title($this->__('Daily Cron Products'));

        $this->_addBreadcrumb(Mage::helper('fiuze_deals')->__('Fiuze Daily'), Mage::helper('fiuze_deals')->__('Daily Cron Products'), $this->getUrl());

        return $this;
    }

    protected function _initGroup()
    {
        $this->_title($this->__('Product Discount'))->_title($this->__('Manage'));

        Mage::register('current_user', Mage::getModel('catalog/product'));
        $userId = $this->getRequest()->getParam('id');
        if (!is_null($userId)) {
            Mage::registry('current_user')->load($userId);
        }
    }

    /**
     * List action for grid
     */
    public function listAction() {
        $this->_initAction();
        $this->renderLayout();
    }

    public function editAction()
    {
        $this->_initGroup();
        $this->loadLayout();
        $this->_setActiveMenu('hr/dailydeals');
        $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Product Discount'), Mage::helper('adminhtml')->__('Product Discount'));
        $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Discount'), Mage::helper('adminhtml')->__('Discount'), $this->getUrl('*/discount'));

        if ($this->getRequest()->getParam('id')) {
            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Edit Discount'), Mage::helper('adminhtml')->__('Edit Discount'));
        } else {
            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('New Discount'), Mage::helper('adminhtml')->__('New Discount'));
        }
        $this->getLayout()->getBlock('content')
            ->append($this->getLayout()->createBlock('fiuze_deals/adminhtml_deals_edit', 'discount')
                ->setEditMode((bool)Mage::registry('current_user')->getId()));

        $this->renderLayout();
    }
}

