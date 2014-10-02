<?php

class TM_NewsletterBooster_Block_Adminhtml_Queue_Edit_Tab_Geo
   extends Mage_Adminhtml_Block_Template
{
    protected function _construct()
    {
        parent::_construct();
        $queue = Mage::getModel('newsletterbooster/queue');
        $queue->load($this->getRequest()->getParam('id'));
        $this->setQueue($queue);
        $this->setTemplate('newsletterbooster/statistics/geo.phtml');
    }

    public function getCountryData()
    {
        $queueId = $this->getRequest()->getParam('id');
        $queueModel = $this->getQueue();
        $country = $queueModel->getCountryOpensData($queueId);

        return $country;
    }

    public function getRegionData()
    {
        $queueId = $this->getRequest()->getParam('id');
        $queueModel = $this->getQueue();
        $city = $queueModel->getRegionOpensData($queueId);

        return $city;
    }
}
