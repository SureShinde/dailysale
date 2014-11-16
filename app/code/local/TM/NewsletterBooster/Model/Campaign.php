<?php
include_once("TM/Mandrill.php");
class TM_NewsletterBooster_Model_Campaign extends Mage_Newsletter_Model_Template
{
    const XML_PATH_TEMPLATE_EMAIL               = 'global/template/email';
    const XML_PATH_SENDING_SET_RETURN_PATH      = 'system/smtp/set_return_path';
    const XML_PATH_SENDING_RETURN_PATH_EMAIL    = 'system/smtp/return_path_email';
    const XML_PATH_DESIGN_EMAIL_LOGO            = 'design/email/logo';
    const XML_PATH_DESIGN_EMAIL_LOGO_ALT        = 'design/email/logo_alt';
    const TYPE_TEXT = 1;
    const TYPE_HTML = 2;

    static protected $_defaultTemplates;

    protected function _construct()
    {
        parent::_construct();
        $this->_init('newsletterbooster/campaign');
    }

    public function getId()
    {
        return $this->getCampaignId();
    }

    public function setId($value)
    {
        return $this->setCampaignId($value);
    }

    static public function getEmailTemplatesAsOptionsArray()
    {
        $options = array(
            array('value'=>'', 'label'=> '')
        );

        $idLabel = array();
        $templates = Mage::getConfig()->getNode(self::XML_PATH_TEMPLATE_EMAIL)->asArray();
        foreach ($templates as $templateId => $row) {
            if (isset($row['@']) && isset($row['@']['module'])) {
                $module = $row['@']['module'];
            } else {
                $module = 'adminhtml';
            }
            if (isset($row['@'])
                && isset($row['@']['module'])
                && 'newsletterbooster' == $row['@']['module']) {
                    $idLabel[$templateId] = Mage::helper($module)->__($row['label']);
            }

        }
        foreach ($idLabel as $templateId => $label) {
            $options[] = array('value' => $templateId, 'label' => $label);
        }

        return $options;
    }

    public function getUnsubscriptionLink() {
        return Mage::helper('newsletterbooster')->getUnsubscribeUrl($this);
    }

    protected function _parseVariablesString($variablesString)
    {
        $variables = array();
        if ($variablesString && is_string($variablesString)) {
            $variablesString = str_replace("\n", '', $variablesString);
            $variables = Zend_Json::decode($variablesString);
        }
        return $variables;
    }

    public function getVariablesOptionArray($withGroup = false)
    {
        $optionArray = array();
        $variables = $this->_parseVariablesString($this->getData('orig_template_variables'));
        if ($variables) {
            foreach ($variables as $value => $label) {
                $optionArray[] = array(
                    'value' => '{{' . $value . '}}',
                    'label' => Mage::helper('core')->__('%s', $label)
                );
            }

            if ($withGroup) {
                $optionArray = array(
                    'label' => Mage::helper('core')->__('Template Variables'),
                    'value' => $optionArray
                );
            }
        }
        return $optionArray;
    }

    public function getProcessedTemplate(array $variables = array(), $usePreprocess = false)
    {
        /* @var $processor Mage_Newsletter_Model_Template_Filter */  
        DebugBreak('!@localhost');
        $processor = Mage::helper('newsletterbooster')->getTemplateProcessor();

        if (!$this->_preprocessFlag) {
            $variables['campaign'] = $this;
        }

        if (Mage::app()->isSingleStoreMode()) {
            $processor->setStoreId(Mage::app()->getStore());
        } else {
            $stores = $this->getData('stores');
            $processor->setStoreId($stores[0]);
        }

        $processor
            ->setIncludeProcessor(array($this, 'getInclude'))
            ->setVariables($variables);
        
        if ($usePreprocess && $this->isPreprocessed()) {
            return $processor->filter($this->getPreparedTemplateText(true));
        }
        $this->setTemplateText($processor->filter($this->getTemplateText()));
        return $processor->filter($this->getPreparedTemplateText());
    }

    public function getPreparedTemplateText($usePreprocess = false)
    {
        $text = $usePreprocess ? $this->getTemplateTextPreprocessed() : $this->getTemplateText();
        $_cmsHelper = Mage::helper('cms');
        $_process = $_cmsHelper->getBlockTemplateProcessor();

        if ($this->_preprocessFlag || $this->isPlain() || !$this->getTemplateStyles()) {
            $text = $_process->filter($text);

            return $text;
        }

        $text = $_process->filter($text);
        $html = "<style type=\"text/css\">\n%s\n</style>\n%s";
        return sprintf($html, $this->getTemplateStyles(), $text);
    }

    public function getInclude($templateCode, array $variables)
    {
        return Mage::getModel('newsletterbooster/campaign')
            ->loadByCode($templateCode)
            ->getProcessedTemplate($variables);
    }

    public function loadDefault($templateId, $locale=null)
    {
        $defaultTemplates = self::getDefaultTemplates();
        if (!isset($defaultTemplates[$templateId])) {
            return $this;
        }

        $data = &$defaultTemplates[$templateId];
        $this->setTemplateType($data['type']=='html' ? self::TYPE_HTML : self::TYPE_TEXT);

        $templateText = Mage::app()->getTranslator()->getTemplateFile(
            $data['file'], 'email', $locale
        );

        if (preg_match('/<!--@subject\s*(.*?)\s*@-->/u', $templateText, $matches)) {
            $this->setTemplateSubject($matches[1]);
            $templateText = str_replace($matches[0], '', $templateText);
        }

        if (preg_match('/<!--@vars\s*((?:.)*?)\s*@-->/us', $templateText, $matches)) {
            $this->setData('orig_template_variables', str_replace("\n", '', $matches[1]));
            $templateText = str_replace($matches[0], '', $templateText);
        }

        if (preg_match('/<!--@styles\s*(.*?)\s*@-->/s', $templateText, $matches)) {
           $this->setTemplateStyles($matches[1]);
           $templateText = str_replace($matches[0], '', $templateText);
        }

        /**
         * Remove comment lines
         */
        $templateText = preg_replace('#\{\*.*\*\}#suU', '', $templateText);

        $this->setTemplateText($templateText);
        $this->setId($templateId);

        return $this;
    }

    /**
     * Retrive default templates from config
     *
     * @return array
     */
    static public function getDefaultTemplates()
    {
        if(is_null(self::$_defaultTemplates)) {
            self::$_defaultTemplates = Mage::getConfig()->getNode(self::XML_PATH_TEMPLATE_EMAIL)->asArray();
        }

        return self::$_defaultTemplates;
    }

    public function sendTestMail($email, $name = null, array $variables = array())
    {
        if (!$this->isValidForSendTest()) {
            Mage::logException(new Exception('This letter cannot be sent.'));

            return false;
        }

        ini_set('SMTP', Mage::getStoreConfig('system/smtp/host'));
        ini_set('smtp_port', Mage::getStoreConfig('system/smtp/port'));

        $mail = $this->getMail();

        $setReturnPath = Mage::getStoreConfig(Mage_Core_Model_Email_Template::XML_PATH_SENDING_SET_RETURN_PATH);
        switch ($setReturnPath) {
            case 1:
                $returnPathEmail = $this->getSenderEmail();
                break;
            case 2:
                $returnPathEmail = Mage::getStoreConfig(Mage_Core_Model_Email_Template::XML_PATH_SENDING_RETURN_PATH_EMAIL);
                break;
            default:
                $returnPathEmail = null;
                break;
        }

        if ('0' !== $this->getTmGateway()) {
            $gatewayId = $this->getTmGateway();
            $gatewayModel = Mage::getModel('newsletterbooster/gateway');
            $gatewayModel->load($gatewayId);

            if (!$gatewayModel->getData('user') && !$gatewayModel->getData('password')) {
                if ($gatewayModel->getData('port')) {
                    $configMail = array(
                        'port' => $gatewayModel->getData('port')
                    );
                } else {
                    $configMail = array();
                }
            } else {
                $configMail = array(
                    'auth' => 'login',
                    'username' => $gatewayModel->getData('user'),
                    'password' => $gatewayModel->getData('password'),
                    'port' => $gatewayModel->getData('port')
                );
            }

            if ($gatewayModel->getData('secure') != "") {
                $configMail['ssl'] = $gatewayModel->getData('secure');
            }

            $mailTransport = new Zend_Mail_Transport_Smtp($gatewayModel->getData('host'), $configMail);
        } else {
            $mailTransport = new Zend_Mail_Transport_Sendmail(Mage::getStoreConfig('system/smtp/host'));
        }
        Zend_Mail::setDefaultTransport($mailTransport);

        $mail->addTo($email, '=?utf-8?B?' . base64_encode($name) . '?=');

        $this->setUseAbsoluteLinks(true);

        $text = $this->getNewsletterText($variables);

        if ($this->getTrackClicks()) {
            $text = $this->trackAllLink($text);
        }

        if ($this->getTrackOpens()) {
            $imgUrl = Mage::getUrl(
                'newsletterbooster/track/index',
                array('queue' => $this->getQueueId(),'entity' => $this->getCustomerId())
            );
            $track = "<img src=". $imgUrl .">";
            $text .= $track;
        }

        if($this->isPlain()) {
            $mail->setBodyText($text);
        } else {
            $mail->setBodyHTML($text);
        }

        $mail->setSubject('=?utf-8?B?' . base64_encode($this->getProcessedTemplateSubject($variables)) . '?=');
        $mail->setFrom($this->getTemplateSenderEmail(), $this->getTemplateSenderName());

        if ('-1' === $this->getTmGateway()) {
            //mandrill API
            $mandrillKey = Mage::getStoreConfig('newsletterbooster/mandrill/api_key');
            $mandrill = new Mandrill($mandrillKey);
            $message = array(
                'subject' => '=?utf-8?B?' . base64_encode($this->getProcessedTemplateSubject($variables)) . '?=',
                'from_email' => $this->getTemplateSenderEmail(),
                'html' => $text,
                'to' => array(
                    array(
                        'email' => $email,
                        'name' => '=?utf-8?B?' . base64_encode($name) . '?='
                    )
                ),
                'merge_vars' => array(array(
                    'rcpt' => $email
                )));

            try {
                $mandrill->messages->send($message);
                $this->_mail = null;
            }
            catch (Exception $e) {
                $this->_mail = null;
                $message = 'Customer Email - ' . $email . '    ' . 'Message: ' . $e->getMessage();
                Mage::log($message, null, 'newsletterbooster.log');
                return false;
            }
            return true;
        } else {
            try {
                $mail->send();
                $this->_mail = null;
            }
            catch (Exception $e) {
                $this->_mail = null;
                $message = 'Customer Email - ' . $email . '    ' . 'Message: ' . $e->getMessage();
                Mage::log($message, null, 'newsletterbooster.log');
                return false;
            }
            return true;
        }
    }

    public function getNewsletterText($vars)
    {
        $template = $this;

        $camp = Mage::getModel('newsletterbooster/campaign')->load($this->getData('campaign_id'));
        $stores = $camp->getData('stores');
        $storeId = is_array($stores) ? $stores[0] : Mage::app()->getDefaultStoreView()->getId();
        /* @var $filter Mage_Core_Model_Input_Filter_MaliciousCode */
        $filter = Mage::getSingleton('core/input_filter_maliciousCode');
        $text = $filter->filter($template->getTemplateText());

        $appEmulation = Mage::getSingleton('core/app_emulation');

        /*         NEED ADD Store ID       */

        $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);
        /*
        $processor = Mage::helper('cms')->getBlockTemplateProcessor();
        $text = $processor->filter($template->getTemplateText());

        $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);

        $template->setTemplateText($text);
          */
        //Varien_Profiler::start("email_template_proccessing");
        $templateProcessed = $template->getProcessedTemplate($vars, true);

        if ($template->isPlain()) {
            $templateProcessed = "<pre>" . htmlspecialchars($templateProcessed) . "</pre>";
        }

        // Varien_Profiler::stop("email_template_proccessing");

        return $templateProcessed;
    }

    public function trackAllLink($text)
    {
        $all_hrefs = array();
        if (preg_match_all('/<a\s+(.*?)href=(["\'][^"\']+["\'])/i', $text, $links, PREG_PATTERN_ORDER)) {
            $all_hrefs = array_unique($links[2]);
        }
        $result = $text;
        foreach ($all_hrefs as $href) {
            if (strstr($href, 'unsubscribe')) {
                continue;
            }
            $trackUrl = Mage::getUrl(
                'newsletterbooster/track/click',
                array('queue' => $this->getQueueId(),'entity' => $this->getCustomerId(), 'redirect' => base64_encode(substr($href, 1, -1)))
            );
            $result = str_replace($href, '"' . $trackUrl . '"', $result);
        }
        return $result;
    }

    public function isValidForSendTest()
    {
        return !Mage::getStoreConfigFlag('system/smtp/disable')
            && $this->getTemplateSenderName()
            && $this->getTemplateSenderEmail()
            && $this->getTemplateSubject();
    }

    public function getOptionArray($store = null)
    {
        return $this->_getResource()->getOptionArray($store);
    }

    public function getTotalCampaignCount($store)
    {
        return $this->_getResource()->getTotalCampaignCount($store);
    }

    public function getFrontendCampaigns()
    {
        return $this->_getResource()->getFrontendCampaigns();
    }
}
