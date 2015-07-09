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
        if($deals->getId() && $timeDeals->compare(new Zend_Date())==-1) {
            $timeCur = new Zend_Date();
            if($timeCur->getTimestamp() - $timeDeals->getTimestamp() > 10){
                $schedules = Mage::getModel('cron/schedule')->getCollection()
                    ->addFieldToFilter('job_code', 'fiuze_deals_scheduler')
                    ->addFieldToFilter('status', Mage_Cron_Model_Schedule::STATUS_PENDING)
                    ->addFieldToFilter('scheduled_at', array('lt' => Mage::getModel('core/date')->gmtDate()))
                    ->addOrder('scheduled_at', 'ASC')->getItems();
                if($schedules){
                    foreach($schedules as $schedule){
                        $schedule->delete();
                    }
                }
                $item = Mage::getResourceModel('fiuze_deals/deals_collection')
                    ->addFilter('current_active', 1)
                    ->getFirstItem();
                if($item->getData()){
                    $item->setEndTime(Mage::helper('fiuze_deals')->getEndDealTime());
                    $item->save();
                }
                Mage::getModel('fiuze_deals/cron')->generate();
                Mage::getConfig()->init()->loadEventObservers('crontab');
                Mage::app()->addEventArea('crontab');
                Mage::dispatchEvent('default');
            }
        }
        $layout = $this->loadLayout();
        $configLayout = Mage::helper('fiuze_deals')->getLayout();
        $layout->getLayout()->getBlock('root')->setTemplate($configLayout);
        $this->renderLayout();
    }

    public function testAction(){
        foreach (Mage::getModel('cron/schedule')->getCollection() as $key => $schedule) {
            if($schedule->getJobCode() == 'fiuze_deals_scheduler'){
                $schedule->delete();
            }
        };
        Mage::getModel('fiuze_deals/cron')->generate();
        Mage::getConfig()->init()->loadEventObservers('crontab');
        Mage::app()->addEventArea('crontab');
        Mage::dispatchEvent('default');
    }

    public function checkEndDealTimeAction(){
        $idCheck =(int) $this->getRequest()->getParam('productActive');
        $result['result'] = false;
        if($idCheck){
            $deals = $productActive = Mage::getResourceModel('fiuze_deals/deals_collection')
                ->addFilter('current_active', 1)->getFirstItem();
            if($deals->getId() != $idCheck){
                $result['result'] = true;
            }
        }
        $json = Zend_Json::encode($result);
        $this->getResponse()->setBody($json);
    }
}