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
        $config = new Varien_Object(Mage::helper('fiuze_deals')->getConf(Fiuze_Deals_Helper_Data::XML_ROOT));
        $configLayout = $config->getData('layout');
        $layout->getLayout()->getBlock('root')->setTemplate($configLayout);
        $this->renderLayout();
    }
}