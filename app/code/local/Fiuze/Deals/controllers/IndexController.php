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
        }
        $layout = $this->loadLayout();
        $configLayout = Mage::helper('fiuze_deals')->getLayout();
        $layout->getLayout()->getBlock('root')->setTemplate($configLayout);
        $this->renderLayout();
    }
}