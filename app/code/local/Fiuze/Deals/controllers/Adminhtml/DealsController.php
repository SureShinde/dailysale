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
    public function saveGridAction()
    {
        $sortOrder = $this->getRequest()->getParam('sort_order');
        if(isset($sortOrder)){
            $productActive = Mage::getResourceModel('fiuze_deals/deals_collection')
                ->addFieldToSelect('entity_id')
                ->addFieldToSelect('product_id')
                ->addFieldToSelect('sort_order')
                        ->getItems();
            foreach($productActive as $item){
                try{
                    $key = $item->getData('product_id');
                    $item->setData('sort_order',$sortOrder[$key]);
                    $item->save();
                }catch (Exception $ex){
                    Mage::logException($ex);
                }
            }
        }
        $this->getResponse()->setRedirect($this->getUrl('*/*/list'));
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
                $product = Mage::getModel('catalog/product')->load($id);
                $qty = (int)$product->getStockItem()->getQty();
                if(!$qty){
                    Mage::getSingleton('adminhtml/session')->addError(Mage::helper('fiuze_deals')->__('Please make sure that product quantity greater than 0.'));
                    Mage::app()->getResponse()->setRedirect(Mage::helper("adminhtml")->getUrl("adminhtml/deals/edit", array('id' => $id)));
                    return;
                }
                $productDeals->setData('product_id', $id);
                $productDeals->setData('product_name', $product->getName());
                $productDeals->setData('origin_special_price', $product->getData('special_price'));
                $productDeals->setData('category_id', Mage::helper('fiuze_deals')->getCategoryCron()->getData('entity_id'));

                $productDeals->setDealsPrice($data['deal_price']);
                $productDeals->setDealsQty($data['deal_qty']);
                $productDeals->setSortOrder($data['sort_order']);
                $productDeals->setDealsActive($data['deals_active'] ? true : false);

                if($product->getStockItem()->getQty()){
                    if(!($data['deals_active'] ? true : false)){
                        $productDeals->setData('current_active', 0);
                        $productDeals->save();
                        Mage::getSingleton('fiuze_deals/cron')->dailyCatalogUpdate();
                    }else{
                        $productDeals->save();
                    }
                    if(!$productDeals->getDealsQty()){
                        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('fiuze_deals')->__('The product won`t be displayed on the frontend due to `Deal Qty` (it should be greater then 0).'));
                    }
                    Mage::dispatchEvent('fiuze_deals_save_after', array('object'=>$productDeals));
                    $this->getResponse()->setRedirect($this->getUrl('*/*/list'));
                }else{
                    Mage::getSingleton('core/session')->addWarning('Qty product 0');
                    $this->getResponse()->setRedirect($this->getUrl('*/*/list'));
                }

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


    /**
     * Controller for change deal status
     */
    public function massStatusAction()
    {
        $status =(int) $this->getRequest()->getParam('status');
        $ids = $this->getRequest()->getParam('banners');
        if (!$ids) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Please select Items(s)'));
        } else {
            try {
                foreach ($ids as $id) {
                    $productDeals = Mage::getModel('fiuze_deals/deals')
                        ->load($id, 'product_id');
                    $productDeals->setData('deals_active', $status);
                    if(!($status ? true : false)){
                        $productDeals->setData('current_active', 0);
                        $productDeals->save();
                        Mage::getSingleton('fiuze_deals/cron')->dailyCatalogUpdate();
                    }else{
                        $productDeals->save();
                    }
                }
                $this->_getSession()->addSuccess(
                    $this->__('Total of %d record(s) were successfully updated', count($ids))
                );
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
        }
        $dealResource = Mage::getResourceModel('fiuze_deals/deals_collection');
        if ($dealResource->addFilter('current_active', 1)->getSize() == 0) {
            $item = Mage::getResourceModel('fiuze_deals/deals_collection')
                ->addFilter('deals_active', 1)
                ->getFirstItem();
            if($item->getData()){
                $item->setEndTime(Mage::helper('fiuze_deals')->getEndDealTime());
                $item->setCurrentActive(1);
                $item->save();
            }
        }
        $this->_redirect('*/*/list');
    }
}

