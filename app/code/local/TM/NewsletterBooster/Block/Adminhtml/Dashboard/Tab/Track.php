<?php

class TM_NewsletterBooster_Block_Adminhtml_Dashboard_Tab_Track
    extends Mage_Adminhtml_Block_Template
{
    protected function _construct()
    {
        parent::_construct();
        $this->setCampaign($this->getRequest()->getParam('campaign'));
        $queue = Mage::getModel('newsletterbooster/queue');
        $recipients = $queue->getCampaignRecipientsCount($this->getRequest()->getParam('campaign'));

        $this->setRecipients($recipients);
        $this->setTemplate('newsletterbooster/dashboard/charts/trackopen.phtml');
    }

    public function CampaignSelected()
    {
        if ($this->getRequest()->getParam('campaign')) {
            return true;
        }
        return false;
    }

    public function getQueueOpensData()
    {
        $queueModel = Mage::getModel('newsletterbooster/queue');
        $opens = $queueModel->getCampaignOpensCount($this->getCampaign());

        $persent = ($opens * 100) / $this->getRecipients();
        $data = array();
        $data['opens'] = (int)$opens;
        $data['tooltip'] = Mage::helper('newsletterbooster')->__(
            'Opens: '.(int)$opens.' ('.round($persent,2).'%)'
        );

        return $data;
    }

    public function getQueueSendData()
    {
        $queueModel = Mage::getModel('newsletterbooster/queue');

        $send = $queueModel->getCampaignQueueProcessed($this->getCampaign());

        $persent = ($send * 100) / $this->getRecipients();
        $data = array();

        $data['send'] = (int)$send;
        $data['tooltip'] = Mage::helper('newsletterbooster')->__(
            'Sended: '.(int)$send.' ('.round($persent,2).'%)'
        );
        return $data;
    }

    public function getQueueClicksData()
    {
        $queueModel = Mage::getModel('newsletterbooster/queue');
        $clicks = $queueModel->getCampaignClicksCount($this->getCampaign());
        $persent = ($clicks * 100) / $this->getRecipients();
        $data = array();

        $data['clicks'] = (int)$clicks;
        $data['tooltip'] = Mage::helper('newsletterbooster')->__(
            'Clicks: '.(int)$clicks.' ('.round($persent,2).'%)'
        );

        return $data;
    }

    public function getQueueUnOpensData()
    {
        $queueModel = Mage::getModel('newsletterbooster/queue');
        $opens = $queueModel->getCampaignOpensCount($this->getCampaign());
        $unOpens = $this->getRecipients() - $opens;
        $persent = ($unOpens * 100) / $this->getRecipients();
        $data = array();
        $data['unopens'] = (int)$unOpens;
        $data['tooltip'] = Mage::helper('newsletterbooster')->__(
            'UnOpens: '.(int)$unOpens.' ('.round($persent,2).'%)'
        );

        return $data;
    }

    public function getQueueUnsubscribeData()
    {
        $queueModel = Mage::getModel('newsletterbooster/queue');
        $unsubscribe = $queueModel->getCampaignUnsubscribeCount($this->getCampaign());

        $persent = ($unsubscribe * 100) / $this->getRecipients();
        $data = array();
        $data['unsubscribe'] = (int)$unsubscribe;
        $data['tooltip'] = Mage::helper('newsletterbooster')->__(
            'Unsubscribe: '.(int)$unsubscribe.' ('.round($persent,2).'%)'
        );

        return $data;
    }

    public function getQueueErrorsData()
    {
        $queueModel = Mage::getModel('newsletterbooster/queue');
        $errors = $queueModel->getCampaignQueueErrors($this->getCampaign());
        $persent = ($errors * 100) / $this->getRecipients();
        $data = array();

        $data['errors'] = (int)$errors;
        $data['tooltip'] = Mage::helper('newsletterbooster')->__(
            'Errors: '.(int)$errors.' ('.round($persent,2).'%)'
        );
        return $data;
    }
}

