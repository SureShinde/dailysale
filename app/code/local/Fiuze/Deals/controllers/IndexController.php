<?php

class Fiuze_Deals_IndexController extends Mage_Core_Controller_Front_Action
{
    public function preDispatch()
    {
        parent::preDispatch();
        if (!Mage::helper('fiuze_deals')->getEnabled()) {
            $this->_redirectUrl(Mage::helper('core/url')->getHomeUrl());
        }
        //Mage::helper('fiuze_deals')->ifStoreChangedRedirect();

        //set current product for deals
        Mage::helper('fiuze_deals')->getProductCron();
    }

    public function indexAction()
    {
        $layout = $this->loadLayout();
        $block = $layout->getLayout()->createBlock('fiuze_deals/blockkk')->setTemplate('deals/product_deal.phtml');
        $this->getLayout()->getBlock('content')->append($block);
        $this->renderLayout();
    }
}