<?php
class Bronto_Email_Model_Template extends Bronto_Common_Model_Email_Template
{
    /**
     * @var string
     */
    protected $_helper = 'bronto_email';

    /**
     * Constructor
     */
    public function _construct()
    {
        $this->_init('bronto_email/template');
    }

    /**
     * Get Template from original template code and store Id
     *
     * @param      $templateCode
     * @param bool $storeId
     * @param null $locale
     *
     * @return $this
     */
    public function loadByOriginalCode($templateCode, $storeId = false, $locale = null)
    {
        $originalTemplate = $this->getResource()->loadByOriginalCode($templateCode, $storeId);

        if (count($originalTemplate)) {
            $this->addData($originalTemplate);
        } else {
            $this->loadDefault($templateCode, $locale);
            $this->setOrigTemplateCode($templateCode);
        }

        return $this;
    }

    /**
     * Handle loading Existing and Default Magento templates
     *
     * @return boolean
     */
    public function handleDefaultTemplates()
    {
        /** @var $templateMode Bronto_Email_Model_Template_Import */
        $templateModel = Mage::getModel('bronto_email/template_import');

        // Process Templates
        try {
            $templateModel->handleDefaults();
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
        }

        return true;
    }

    /**
     * Load default email template from locale translate
     *
     * @param string $templateId
     * @param mixed  $locale
     *
     * @return $this
     */
    public function loadDefault($templateId, $locale = null)
    {
        $defaultTemplates = self::getDefaultTemplates();

        if (!is_string($templateId) || !array_key_exists($templateId, $defaultTemplates) || $templateId == 'nosend') {
            return $this;
        }

        $data = & $defaultTemplates[$templateId];
        $this->setTemplateType($data['type'] == 'html' ? self::TYPE_HTML : self::TYPE_TEXT);

        $templateText = Mage::app()->getTranslator()->getTemplateFile(
            $data['file'], 'email', $locale
        );

        if (preg_match('/<!--@subject\s*(.*?)\s*@-->/', $templateText, $matches)) {
            $this->setTemplateSubject($matches[1]);
            $templateText = str_replace($matches[0], '', $templateText);
        }

        if (preg_match('/<!--@vars\n((?:.)*?)\n@-->/us', $templateText, $matches)) {
            $this->setData('orig_template_variables', str_replace("\n", '', $matches[1]));
            $templateText = str_replace($matches[0], '', $templateText);
        }

        if (preg_match('/<!--@styles\s*(.*?)\s*@-->/sm', $templateText, $matches)) {
            $this->setTemplateStyles($matches[1]);
            $templateText = str_replace($matches[0], '', $templateText);
        }

        /**
         * Remove comment lines
         */
        $templateText = preg_replace('#\{\*.*\*\}#suU', '', $templateText);

        $this->setTemplateText($templateText);
        $this->setOrigTemplateText($templateText);
        $this->setId($templateId);

        return $this;
    }

    /**
     * Collect all system config paths where current template is used as default
     *
     * @return array
     */
    public function getSystemConfigPathsWhereUsedAsDefault()
    {
        $templateCode = $this->getOrigTemplateCode();
        if (!$templateCode) {
            return array();
        }

        $templatePaths = Mage::helper($this->_helper)->getTemplatePaths();
        $paths         = array();

        // find nodes which are using $templateCode value
        $defaultCfgNodes = Mage::getConfig()->getXpath('default/*/*[*="' . $templateCode . '"]');

        if (!is_array($defaultCfgNodes)) {
            return array();
        }

        foreach ($defaultCfgNodes as $node) {
            // create email template path in system.xml
            $sectionName = $node->getParent()->getName();
            $groupName   = $node->getName();
            $fieldName   = substr($templateCode, strlen($sectionName . '_' . $groupName . '_'));
            $path        = implode('/', array($sectionName, $groupName, $fieldName));

            if (in_array($path, $templatePaths)) {
                $paths[] = array('path' => $path);
            }
        }

        return $paths;
    }

    /**
     * Collect all system config paths where current template is currently used
     *
     * @return array
     */
    public function getSystemConfigPathsWhereUsedCurrently()
    {
        $templateCode = $this->getTemplateId();
        if (!$templateCode) {
            return array();
        }

        $paths = Mage::helper($this->_helper)->getTemplatePaths();

        $configData = $this->_getResource()->getSystemConfigByPathsAndTemplateId($paths, $templateCode);
        if (!$configData) {
            return array();
        }

        return $configData;
    }

    /**
     * @see parent
     */
    protected function _emailClass()
    {
        return 'bronto_email/template';
    }

    /**
     * Log about the functionality of sending the email before it goes out
     *
     * @param Bronto_Api_Contact_Row $contact
     * @param Bronto_Api_Message_Row $message
     *
     * @return void
     */
    protected function _beforeSend(Bronto_Api_Contact_Row $contact, Bronto_Api_Message_Row $message)
    {
        Mage::dispatchEvent('bronto_email_send_before');

        if (Mage::helper('bronto_email')->isLogEnabled()) {
            $this->_log = Mage::getModel('bronto_email/log');
            $this->_log->setCustomerEmail($contact->email);
            $this->_log->setContactId($contact->id);
            $this->_log->setMessageId($message->id);
            $this->_log->setMessageName($message->name);
            $this->_log->setSuccess(0);
            $this->_log->setSentAt(new Zend_Db_Expr('NOW()'));
            $this->_log->save();
        }
    }

    /**
     * Log data on sending message
     *
     * @param bool                    $success
     * @param string                  $error
     * @param Bronto_Api_Delivery_Row $delivery
     *
     * @return void
     */
    protected function _afterSend($success, $error = null, Bronto_Api_Delivery_Row $delivery = null)
    {
        Mage::dispatchEvent('bronto_email_send_after');

        if (Mage::helper('bronto_email')->isLogEnabled()) {
            $this->_log->setSuccess((int)$success);
            if (!empty($error)) {
                $this->_log->setError($error);
            }
            if ($delivery) {
                $this->_log->setDeliveryId($delivery->id);
                if (Mage::helper('bronto_email')->isLogFieldsEnabled()) {
                    $this->_log->setFields(serialize($delivery->getFields()));
                }
            }
            $this->_log->save();
            $this->_log = null;
        }
    }
}
