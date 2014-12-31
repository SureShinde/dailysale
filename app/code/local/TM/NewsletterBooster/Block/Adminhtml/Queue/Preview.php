<?php

class TM_NewsletterBooster_Block_Adminhtml_Queue_Preview extends Mage_Adminhtml_Block_Widget
{

    protected function _toHtml()
    {
        /* @var $template Mage_Newsletter_Model_Template */
        $template = Mage::getModel('newsletterbooster/campaign');
        if($id = (int)$this->getRequest()->getParam('id')) {
            $queue = Mage::getModel('newsletterbooster/queue');
            $queue->load($id);
            $serializeTemplate = unserialize($queue->getCampaignSerialize());
            $template->setData($serializeTemplate);
        }

        $storeId = (int)$this->getRequest()->getParam('store_id');
        if(!$storeId) {
            $storeId = Mage::app()->getDefaultStoreView()->getId();
        }

        Varien_Profiler::start("newsletter_queue_proccessing");
        $vars = array();

        $vars['subscriber'] = Mage::getModel('newsletter/subscriber');

        $template->emulateDesign($storeId);
        $templateProcessed = $template->getProcessedTemplate($vars, true);

        $template->revertDesign();

        if($template->isPlain()) {
            $templateProcessed = "<pre>" . htmlspecialchars($templateProcessed) . "</pre>";
        }

        Varien_Profiler::stop("newsletter_queue_proccessing");

        return $templateProcessed;
        
    }

}
