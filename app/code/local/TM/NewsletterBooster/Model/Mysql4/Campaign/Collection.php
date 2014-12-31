<?php

class TM_NewsletterBooster_Model_Mysql4_Campaign_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('newsletterbooster/campaign');
    }

    protected function _afterLoad()
    {
        parent::_afterLoad();

        $queueModel = Mage::getModel('newsletterbooster/queue');
        $segmentModel = Mage::getModel('segmentationsuite/segments');
        $gatewayModel = Mage::getModel('newsletterbooster/gateway');
        
        foreach ($this->_items as $item) {
            $queueSent = $queueModel->getSendedQueue($item->getCampaignId());
            $segmentModel->load($item->getTmSegment());
            $segmentTitle = $segmentModel->getData('segment_title');

            if ('0' === $item->getTmGateway()) {
                $gateway = Mage::helper('newsletterbooster')->__('Store Mail');
            } else {
                $gatewayModel->load($item->getTmGateway());
                $gateway = $gatewayModel->getData('name');
            }
            
            $item->addData(array('queue_count' => $queueSent));
            $item->addData(array('segment_title' => $segmentTitle));
            $item->addData(array('name' => $gateway));
        }
        return $this;
    }

}
