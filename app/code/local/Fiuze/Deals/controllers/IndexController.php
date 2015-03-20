<?php

class Fiuze_Deals_IndexController extends Mage_Core_Controller_Front_Action
{
    public function preDispatch()
    {
        parent::preDispatch();
        if (!Mage::helper('fiuze_deals')->isEnabled()) {
            $this->_redirectUrl(Mage::helper('core/url')->getHomeUrl());
        }

        //set current product for deals
        Mage::helper('fiuze_deals')->getProductCron();
    }

    public function indexAction()
    {
        $deals = $productActive = Mage::getResourceModel('fiuze_deals/deals_collection')
            ->addFilter('current_active', 1)->getFirstItem();

        if (!$deals->getId()) {
            $this->_redirectUrl(Mage::helper('core/url')->getHomeUrl());
            return;
        }

        $timeDeals = new Zend_Date($deals->getEndTime());
        if($deals->getId() && $timeDeals->compare(new Zend_Date())==-1){
            Mage::getModel('fiuze_deals/cron')->dailyCatalogUpdate();
            //set current product for deals
            Mage::unregister('product');
            Mage::helper('fiuze_deals')->getProductCron();
        }

        $layout = $this->loadLayout();
        $configLayout = Mage::helper('fiuze_deals')->getLayout();
        $layout->getLayout()->getBlock('root')->setTemplate($configLayout);
        $this->renderLayout();
    }

    public function testAction(){
        $model = new Fiuze_Deals_Model_Cron();
        $model->dailyCatalogUpdate();
    }
}