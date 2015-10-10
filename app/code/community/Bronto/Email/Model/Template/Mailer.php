<?php
/**
 * @package     Bronto\Email
 * @copyright   2011-2013 Bronto Software, Inc.
 */
class Bronto_Email_Model_Template_Mailer
    extends Mage_Core_Model_Email_Template_Mailer
{
    /**
     * Send all emails from email list
     *
     * @see self::$_emailInfos
     *
     * @return Bronto_Email_Model_Template_Mailer
     */
    public function send()
    {
        // If using the 'Do Not Send' option, just return $this and be done.
        if ($this->getTemplateId() == 'nosend') {
            return $this;
        }

        // Try loading template
        $emailTemplate = Mage::getModel('bronto_email/template');
        $emailTemplate->load($this->getTemplateId());

        // If sending through bronto is not enabled, push through parent
        if (!Mage::helper('bronto_email')->canSendBronto($emailTemplate)) {
            return parent::send();
        }

        $message = new Bronto_Api_Model_Message();
        $message->withId($emailTemplate->getBrontoMessageId());

        // Send all emails from corresponding list
        while (!empty($this->_emailInfos)) {
            $emailInfo = array_pop($this->_emailInfos);

            // Handle "Bcc" recepients of the current email
            if ($emailTemplate->getTemplateSendType() == 'magento') {
                $emailTemplate->addBcc($emailInfo->getBccEmails());
            } else {
                foreach ($emailInfo->getBccEmails() as $bcc) {
                    $emailInfo->addTo($bcc);
                }
            }

            // Set required design parameters and delegate email sending to Mage_Core_Model_Email_Template
            $emailTemplate->setDesignConfig(array('area' => 'frontend', 'store' => $this->getStoreId()))
                ->sendTransactional(
                    $message,
                    $this->getSender(),
                    $emailInfo->getToEmails(),
                    $emailInfo->getToNames(),
                    $this->getTemplateParams(),
                    $this->getStoreId()
                );
        }

        return $this;
    }
}
