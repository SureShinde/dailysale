<?php
/**
 * @package     Bronto\Email
 * @copyright   2011-2013 Bronto Software, Inc.
 */
class Bronto_Email_Model_Template_Import extends Bronto_Email_Model_Template
{
    /**
     * @var Bronto_Api_Object
     */
    private $_apiObject;

    /**
     * Load Template to import into Bronto
     *
     * @param int   $templateId
     * @param mixed $storeId
     * @param bool $force
     *
     * @return string
     * @throws Exception
     */
    public function importTemplate($templateId, $storeId = false, $force = false)
    {
        /** @var $template Bronto_Email_Model_Template_Import */
        $template = $this->load($templateId);

        try {
            return $this->processMessage($template, $storeId, $force);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Import template into Bronto
     *
     * @param Bronto_Email_Model_Template $template
     * @param bool                        $storeId
     *
     * @return bool
     */
    protected function processMessage(Bronto_Email_Model_Template $template, $storeId = false, $force = false)
    {
        $data = $template->getData();
        $emt  = Mage::getModel('bronto_common/email_template_templatefilter');

        // Get Store
        $store = null;
        if ($storeId) {
            $store = Mage::app()->getStore($storeId);
        } else if (isset($data['store_id'])) {
            $store = Mage::app()->getStore($data['store_id']);
        }

        if (empty($store)) {
            $store = Mage::app()->getDefaultStoreView();
        }

        if (!$store) {
            $store = Mage::app()->getStore();
        }

        // If module is not enabled for this store, don't proceed
        if (!$force && !Mage::helper('bronto_email')->isEnabled('store', $store->getId())) {
            return false;
        }

        // Get Token
        $api = Mage::helper('bronto_common')->getApi(null, 'store', $store->getId());
        if (!$api) {
            return false;
        }

        $messageOps = $api->transferMessage();
        if (method_exists($emt, 'getInlineCssFile')) {
            $emt
                ->setTemplateProcessor(array($template, 'getTemplateByConfigPath'))
                ->setIncludeProcessor(array($template, 'getInclude'))
                ->setUseAbsoluteLinks(true)
                ->setStoreId($store->getId())
                ->setUseSessionInUrl(false);
        }

        $appEmu = Mage::getSingleton('core/app_emulation');
        $emuInfo = $appEmu->startEnvironmentEmulation($store->getId(), 'frontend');

        // Add Check for required fields
        if (array_key_exists('template_text', $data) && array_key_exists('template_subject', $data)) {
            // Define variables for filtered Subject and Text
            $templateSubject = $emt->filter($data['template_subject']);
            $templateText    = $emt->filter($data['template_text']);
            $templateTextRip = $emt->filter($this->ripTags($data['template_text']));

            if (method_exists($emt, 'getInlineCssFile')) {
                $template->setInlineCssFile($emt->getInlineCssFile());
                $templateText = $template->getPreparedTemplateText($templateText);
            }

            // If message missing subject, use template code
            if ('' == $templateSubject) {
                $templateSubject = $data['template_code'];
                $template->setTemplateSubject($data['template_code']);
            }

            try {
                // Template has invalid or missing required attributes
                if ('' == $templateText || '' == $templateTextRip) {
                    Mage::throwException('Template is missing body');
                }

                $message = $messageOps->read()
                    ->where->name->equalTo($data['template_code'])
                    ->withIncludeContent(true)
                    ->first();
                if (!$message) {
                    $message = $messageOps->createObject()
                        ->withName($data['template_code'])
                        ->withStatus('active');
                }
                $message
                    ->addHtml($templateSubject, $templateText)
                    ->addText($templateSubject, $templateTextRip);

                $writeOps = $message->hasId() ?
                    $messageOps->update()->updateMessage($message) :
                    $messageOps->add()->addMessage($message);
                foreach ($writeOps as $result) {
                    $item = $result->getItem();
                    if ($item->getIsError()) {
                        Mage::throwException($item->getErrorCode() . ' ' . $item->getErrorMessage());
                    } else if ($item->getIsNew()) {
                        $message->withId($item->getId());
                    }
                }
            } catch (Exception $e) {
                $appEmu->stopEnvironmentEmulation($emuInfo);
                Mage::throwException("Failed Importing Template `{$data['template_code']}` : [Bronto] " . $e->getMessage());

                return false;
            }

            // Create Bronto Template Entry
            $brontoTemplate = Mage::getModel('bronto_email/message')
                ->load($template->getId())
                ->setCoreTemplateId($template->getId())
                ->setOrigTemplateText($templateText)
                ->setBrontoMessageId($message->getId())
                ->setBrontoMessageName($message->getName())
                ->setBrontoMessageApproved(1)
                ->setStoreId($store->getId())
                ->setInlineCss($template->getInlineCssFile())
                ->save();

            // Clean Up
            unset($brontoTemplate);
        }
        $appEmu->stopEnvironmentEmulation($emuInfo);

        return true;
    }

    /**
     * Collect all existing and default templates from magento and add to new table
     */
    public function handleDefaults()
    {
        // process existing
        try {
            $this->_processExisting();
        } catch (Exception $e) {
            Mage::throwException($this->__('Failed loading existing templates'));
        }

        // process defaults
        try {
            $allStores = Mage::app()->getStores();
            $this->_processDefaults($allStores);
        } catch (Exception $e) {
            Mage::throwException($this->__('Failed loading default templates'));
        }

        return true;
    }

    /**
     * Load Existing templates into Bronto Email Template table
     *
     * @return bool
     */
    protected function _processExisting()
    {
        $customTemplates = $this->getCollection();
        foreach ($customTemplates as $customTemplate) {
            try {
                /** @var $template Bronto_Email_Model_Message */
                $template = Mage::getModel('bronto_email/message')
                    ->load($customTemplate->getId());

                // If we didn't get a template match, set the Id
                if (is_null($template->getId())) {
                    $template->setId($customTemplate->getId());
                }

                // If message does not already exist, then proceed
                if (!$template->getBrontoMessageId() || is_null($template->getBrontoMessageId())) {
                    $template->setTemplateSendType('magento')
                        ->setOrigTemplateText($customTemplate->getTemplateText())
                        ->setBrontoMessageId(null)
                        ->setBrontoMessageName(null)
                        ->setBrontoMessageApproved(0)
                        ->save();
                }

                // Clean up
                unset($template);
            } catch (Exception $e) {
                Mage::helper('bronto_email')->writeDebug('Bronto Import Existing Templates:' . $e->getMessage());
            }
        }

        return true;
    }

    /**
     * Load Default templates into Bronto Email Template table
     *
     * @param array $allStores
     *
     * @return bool
     */
    protected function _processDefaults(array $allStores)
    {
        foreach ($allStores as $_eachStoreId) {
            $_store     = Mage::app()->getStore($_eachStoreId);
            $_storeCode = $_store->getCode();
            $_storeId   = $_store->getId();
            $_locale    = $_store->getConfig('general/locale/code');

            //process default
            $templates = $this->getDefaultTemplates();
            foreach (array_keys($templates) as $templateToLoad) {
                try {
                    /** @var $template Bronto_Email_Model_Template */
                    $template = Mage::getModel('bronto_email/template');

                    $template->loadByOriginalCode($templateToLoad, $_storeId, $_locale);

                    // Ensure Defaults use pretty label
                    $label = $templates[$templateToLoad]['label'] . ' (' . $_storeCode . ')';
                    if ('en_US' != $_locale) {
                        $label .= ' [' . $_locale . ']';
                    }

                    // Create Core Template
                    $template->setTemplateCode($label);
                    $templateText = trim($template->getTemplateText());
                    $template->setTemplateText($templateText);
                    $template->setAddedAt(Mage::getModel('core/date')->date('Y-m-d H:i:s'));
                    $template->unsTemplateId(); // template ID may be template code, so unset it

                    $template->save();

                    // Get Template ID from Core Template
                    $templateId = $template->getId();

                    // Build Bronto Template
                    /** @var $brontoTemplate Bronto_Email_Model_Message */
                    $brontoTemplate = Mage::getModel('bronto_email/message')
                        ->load($templateId);

                    // If we didn't get a template match, set the Id
                    if (is_null($brontoTemplate->getId())) {
                        $brontoTemplate->setId($templateId);
                    }

                    // If message does not already exist, then proceed
                    if (!$brontoTemplate->getBrontoMessageId() || is_null($brontoTemplate->getBrontoMessageId())) {
                        $brontoTemplate->setTemplateSendType('magento')
                            ->setOrigTemplateText($templateText)
                            ->setBrontoMessageId(null)
                            ->setBrontoMessageName(null)
                            ->setBrontoMessageApproved(0)
                            ->setStoreId($_storeId)
                            ->save();
                    }

                    // Clean up
                    unset($template);
                    unset($brontoTemplate);
                } catch (Exception $e) {

                    Mage::helper('bronto_email')->writeDebug('Bronto Import Default Templates:' . $e->getMessage());
                }
            }
        }

        return true;
    }

    /**
     * Remove HTML and multiple spaces
     *
     * @param string $string
     *
     * @return string
     */
    protected function ripTags($string)
    {
        $string = preg_replace('/<[^>]*>/', ' ', $string);
        // ----- remove multiple spaces -----
        $string = trim(preg_replace('/ {2,}/', ' ', $string));

        return $string;
    }

}
