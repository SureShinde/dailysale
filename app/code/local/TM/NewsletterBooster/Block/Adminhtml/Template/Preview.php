<?php

class TM_NewsletterBooster_Block_Adminhtml_Template_Preview extends Mage_Adminhtml_Block_Widget
{
    /**
     * Prepare html output
     *
     * @return string
     */
    protected function _toHtml()
    {

        /** @var $template Mage_Core_Model_Email_Template */
        $template = Mage::getModel('newsletterbooster/campaign');

        $id = (int)$this->getRequest()->getParam('id');
        if ($id) {
            $template->load($id);
        } else {
            $template->setTemplateType($this->getRequest()->getParam('type'));
            $template->setTemplateText($this->getRequest()->getParam('text'));
            $template->setTemplateStyles($this->getRequest()->getParam('styles'));
        }

        $storeId = $this->getRequest()->getParam('store');
        if ('' == $storeId) {
            $store = Mage::app()->getDefaultStoreView();
            $storeId = $store->getId();
        }

        /* @var $filter Mage_Core_Model_Input_Filter_MaliciousCode */
        $filter = Mage::getSingleton('core/input_filter_maliciousCode');
        $text = $filter->filter($template->getTemplateText());

        $appEmulation = Mage::getSingleton('core/app_emulation');

        /*         NEED ADD Store ID       */

        $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);

        $processor = Mage::helper('cms')->getBlockTemplateProcessor();
        $text = $processor->filter($this->getRequest()->getParam('text'));

        $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);

        $template->setTemplateText($text);

        //Varien_Profiler::start("email_template_proccessing");
        $vars = array();

        $templateProcessed = $template->getProcessedTemplate($vars, true);

        if ($template->isPlain()) {
            $templateProcessed = "<pre>" . htmlspecialchars($templateProcessed) . "</pre>";
        }

        // Varien_Profiler::stop("email_template_proccessing");

        return $templateProcessed;
    }
}
