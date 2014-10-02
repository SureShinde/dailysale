<?php

class TM_NewsletterBooster_Block_Adminhtml_Queue_Edit_Tab_Tracktotal
   extends Mage_Adminhtml_Block_Dashboard_Bar
{
    protected function _construct()
    {
        parent::_construct();
        $queue = Mage::getModel('newsletterbooster/queue');
        $queue->load($this->getRequest()->getParam('id'));
        $this->setQueue($queue);
        $this->setTemplate('dashboard/totalbar.phtml');
    }

    protected function _prepareLayout()
    {
        $queueId = $this->getRequest()->getParam('id');
        $queueModel = $this->getQueue();
        /* Opens */
        $opens = $queueModel->getOpensCount($queueId);
        $persentOpens = ($opens * 100) / $this->getRecipients();
        /* Send */
        $send = $queueModel->getProcessed();
        $persentSend = ($send * 100) / $this->getRecipients();
        /* Click */
        $clicks = $queueModel->getClicksCount($queueId);
        $persentClicks = ($clicks * 100) / $this->getRecipients();
        /* UnOpens */
        $unOpens = $this->getRecipients() - $opens;
        $persentUnOpens = ($unOpens * 100) / $this->getRecipients();
        /* Unsubscribe */
        $unsubscribe = $queueModel->getUnsubscribeCount($queueId);
        $persentUnsubscribe = ($unsubscribe * 100) / $this->getRecipients();
        /* Errors */
        $errors = $queueModel->getErrors();
        $persentErrors = ($errors * 100) / $this->getRecipients();

        $this->addTotal(
            Mage::helper('newsletterbooster')->__('Sended'),
            round($persentSend,2) . ' %', true
        );
        $this->addTotal(
            Mage::helper('newsletterbooster')->__('Opens'),
            round($persentOpens,2) . ' %', true
        );

        $this->addTotal(
            Mage::helper('newsletterbooster')->__('Clicks'),
            round($persentClicks,2) . ' %', true
        );

        $this->addTotal(
            Mage::helper('newsletterbooster')->__('Unopens'),
            round($persentUnOpens,2) . ' %', true
        );

        $this->addTotal(
            Mage::helper('newsletterbooster')->__('Unsubscribe'),
            round($persentUnsubscribe,2) . ' %', true
        );

        $this->addTotal(
            Mage::helper('newsletterbooster')->__('Errors'),
            round($persentErrors,2) . ' %', true
        );
    }

    public function getRecipients()
    {
        return $this->getQueue()->getRecipients();
    }
}
