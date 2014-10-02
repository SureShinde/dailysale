<?php

class TM_NewsletterBooster_Block_Adminhtml_Queue_Edit_Tab_Tracks
   extends Mage_Adminhtml_Block_Template
{
    protected function _construct()
    {
        parent::_construct();
        $queue = Mage::getModel('newsletterbooster/queue');
        $queue->load($this->getRequest()->getParam('id'));
        $this->setQueue($queue);
        $this->setTemplate('newsletterbooster/statistics/trackopen.phtml');
    }

    public function getRecipients()
    {
        return $this->getQueue()->getRecipients();
    }

    public function getQueueOpensData()
    {
        $queueId = $this->getRequest()->getParam('id');
        $queueModel = $this->getQueue();
        $opens = $queueModel->getOpensCount($queueId);
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
        $queue = $this->getQueue();
        $send = $queue->getProcessed();
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
        $queueId = $this->getRequest()->getParam('id');
        $queueModel = $this->getQueue();
        $clicks = $queueModel->getClicksCount($queueId);
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
        $queueId = $this->getRequest()->getParam('id');
        $queueModel = $this->getQueue();
        $opens = $queueModel->getOpensCount($queueId);
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
        $queueId = $this->getRequest()->getParam('id');
        $queueModel = $this->getQueue();
        $unsubscribe = $queueModel->getUnsubscribeCount($queueId);

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
        $queue = $this->getQueue();
        $errors = $queue->getErrors();
        $persent = ($errors * 100) / $this->getRecipients();
        $data = array();

        $data['errors'] = (int)$errors;
        $data['tooltip'] = Mage::helper('newsletterbooster')->__(
            'Errors: '.(int)$errors.' ('.round($persent,2).'%)'
        );
        return $data;
    }
}
