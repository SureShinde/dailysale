<?php

class TM_NewsletterBooster_Adminhtml_Newsletterbooster_QueueController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Queue list action
     */
    public function indexAction()
    {
        $this->_title(
            Mage::helper('newsletterbooster')->__('NewsletterBooster'))
            ->_title(Mage::helper('newsletterbooster')->__('Queue'));

        if ($this->getRequest()->getQuery('ajax')) {
            $this->_forward('grid');
            return;
        }

        $this->loadLayout();

        $this->_setActiveMenu('newsletterbooster/queue');

        $this->_addContent(
            $this->getLayout()->createBlock('newsletterbooster/adminhtml_queue', 'queue')
        );

        $this->_addBreadcrumb(
            Mage::helper('newsletterbooster')->__('NewsletterBooster Queue'),
            Mage::helper('newsletterbooster')->__('NewsletterBooster Queue')
        );

        $this->renderLayout();
    }


    /**
     * Drop Newsletter queue template
     */
    public function dropAction()
    {
        $this->loadLayout('newsletterbooster_queue_preview');
        $this->renderLayout();
    }

    /**
     * Preview Newsletter queue template
     */
    public function previewAction()
    {
        $this->loadLayout();
        $data = $this->getRequest()->getParams();
        if (empty($data) || !isset($data['id'])) {
            $this->_forward('noRoute');
            return $this;
        }

        // set default value for selected store
        $data['preview_store_id'] = Mage::app()->getDefaultStoreView()->getId();

        $this->getLayout()->getBlock('preview_form')->setFormData($data);
        $this->renderLayout();
    }

    /**
     * Queue list Ajax action
     */
    public function gridAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('newsletterbooster/adminhtml_queue_grid')->toHtml()
        );
    }

    public function startAction()
    {
        $queue = Mage::getModel('newsletterbooster/queue')
            ->load($this->getRequest()->getParam('id'));
        if ($queue->getId()) {
            if (!in_array($queue->getQueueStatus(),
                          array(TM_NewsletterBooster_Model_Queue::STATUS_NEVER,
                                 TM_NewsletterBooster_Model_Queue::STATUS_PAUSE))) {
                   $this->_redirect('*/*');
                return;
            }

            $queue->setQueueStartAt(Mage::getSingleton('core/date')->gmtDate())
                ->setQueueStatus(TM_NewsletterBooster_Model_Queue::STATUS_SENDING)
                ->save();
        }

        $this->_redirect('*/*');
    }

    public function pauseAction()
    {
        $queue = Mage::getSingleton('newsletterbooster/queue')
            ->load($this->getRequest()->getParam('id'));

        if (!in_array($queue->getQueueStatus(),
                      array(TM_NewsletterBooster_Model_Queue::STATUS_SENDING))) {
               $this->_redirect('*/*');
            return;
        }

        $queue->setQueueStatus(TM_NewsletterBooster_Model_Queue::STATUS_PAUSE);
        $queue->save();

        $this->_redirect('*/*');
    }

    public function resumeAction()
    {
        $queue = Mage::getSingleton('newsletterbooster/queue')
            ->load($this->getRequest()->getParam('id'));

        if (!in_array($queue->getQueueStatus(),
                      array(TM_NewsletterBooster_Model_Queue::STATUS_PAUSE))) {
               $this->_redirect('*/*');
            return;
        }

        $queue->setQueueStatus(TM_NewsletterBooster_Model_Queue::STATUS_SENDING);
        $queue->save();

        $this->_redirect('*/*');
    }

    public function cancelAction()
    {
        $queue = Mage::getSingleton('newsletterbooster/queue')
            ->load($this->getRequest()->getParam('id'));

        if (!in_array($queue->getQueueStatus(),
                      array(TM_NewsletterBooster_Model_Queue::STATUS_SENDING))) {
               $this->_redirect('*/*');
            return;
        }

        $queue->setQueueStatus(TM_NewsletterBooster_Model_Queue::STATUS_CANCEL);
        $queue->save();

        $this->_redirect('*/*');
    }

    public function sendingAction()
    {
        // Todo: put it somewhere in config!
        $countOfQueue  = 3;
        $countOfSubscritions = 20;

        $collection = Mage::getResourceModel('newsletterbooster/queue_collection')
            ->setPageSize($countOfQueue)
            ->setCurPage(1)
            ->addOnlyForSendingFilter()
            ->load();

        $collection->walk('sendPerSubscriber', array($countOfSubscritions));
    }

    public function editAction()
    {
        $this->_title(
            Mage::helper('newsletterbooster')->__('NewsletterBooster'))->_title(Mage::helper('newsletterbooster')->__('Queue'));

        Mage::register('tm_current_queue', Mage::getSingleton('newsletterbooster/queue'));

        $id = $this->getRequest()->getParam('id');
        $templateId = $this->getRequest()->getParam('campaign');

        if ($id) {
            $queue = Mage::registry('tm_current_queue')->load($id);
        } elseif ($templateId) {
            $template = Mage::getModel('newsletterbooster/campaign')->load($templateId);
            $queue = Mage::registry('tm_current_queue')
                ->setCampaignId($template->getCampaignId())
                ->setCampaignTitle($template->getTemplateCode());

        }

        $this->loadLayout();

        $this->_setActiveMenu('newsletterbooster/queue');

        $this->getLayout()->getBlock('head')
            ->setCanLoadExtJs(true)
            ->setCanLoadRulesJs(true)
        ;

        $this
            ->_addContent($this->getLayout()->createBlock('newsletterbooster/adminhtml_queue_edit'))
            ->_addLeft($this->getLayout()->createBlock('newsletterbooster/adminhtml_queue_edit_tabs'));

        $this->_addBreadcrumb(
            Mage::helper('newsletterbooster')->__('NewsletterBooster'),
            Mage::helper('newsletterbooster')->__('Queue'),
            $this->getUrl('*/newsletterbooster_queue')
        );

        $this->renderLayout();
    }

    public function saveAction()
    {
        try {
            /* @var $queue Mage_Newsletter_Model_Queue */
            $queue = Mage::getModel('newsletterbooster/queue');

            $templateId = $this->getRequest()->getParam('campaign_id');
            $template = Mage::getModel('newsletterbooster/campaign')->load($templateId);
            if ($templateId && !$this->getRequest()->getParam('queue_id')) {
                /* @var $template Mage_Newsletter_Model_Template */

                if (!$template->getId()) {
                    Mage::throwException(
                        Mage::helper('newsletterbooster')->__('Wrong newsletterbooster campaign.')
                    );
                }

                $queue->setCampaignId($template->getId())
                    ->setQueueStatus(TM_NewsletterBooster_Model_Queue::STATUS_NEVER);

            } else {
                $queue->load($this->getRequest()->getParam('queue_id'));
            }

            if (!in_array($queue->getQueueStatus(),
                   array(TM_NewsletterBooster_Model_Queue::STATUS_NEVER,
                         TM_NewsletterBooster_Model_Queue::STATUS_PAUSE))
            ) {
                $this->_redirect('*/*');
                return;
            }

            if ($queue->getQueueStatus() == TM_NewsletterBooster_Model_Queue::STATUS_NEVER) {
                $serialezeData = serialize($template->getData());
                $queue->setQueueTitle($this->getRequest()->getParam('queue_title'));
                $queue->setQueueStartAtByString($this->getRequest()->getParam('start_at'));
                $queue->setCampaignSerialize($serialezeData);
                $queue->setCampaignId($templateId);
            }

            if ($queue->getQueueStatus() == TM_NewsletterBooster_Model_Queue::STATUS_PAUSE
                && $this->getRequest()->getParam('_resume', false)) {
                $queue->setQueueStatus(TM_NewsletterBooster_Model_Queue::STATUS_SENDING);
            }

            $queue->save();

            $this->_redirect('*/*');
        }
        catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            $id = $this->getRequest()->getParam('id');
            if ($id) {
                $this->_redirect('*/*/edit', array('id' => $id));
            } else {
                $this->_redirectReferer();
            }
        }
    }

    public function trackopenAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('newsletterbooster/adminhtml_queue_edit_tab_opens')->toHtml()
        );
    }

    public function relatedClicksAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('newsletterbooster/adminhtml_queue_edit_tab_ccustomers')->toHtml()
        );
    }

    public function relatedOpensAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('newsletterbooster/adminhtml_queue_edit_tab_ocustomers')->toHtml()
        );
    }
}
