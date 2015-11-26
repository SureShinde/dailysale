<?php

/**
 * Copyright (c) 2015 S.L.I. Systems, Inc. (www.sli-systems.com) - All Rights
 * Reserved
 * This file is part of Learning Search Connect.
 * Learning Search Connect is distributed under a limited and restricted
 * license - please visit www.sli-systems.com/LSC for full license details.
 *
 * THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY
 * KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
 * PARTICULAR PURPOSE. TO THE MAXIMUM EXTENT PERMITTED BY APPLICABLE LAW, IN NO
 * EVENT WILL SLI BE LIABLE TO YOU OR ANY OTHER PARTY FOR ANY GENERAL, DIRECT,
 * INDIRECT, SPECIAL, INCIDENTAL OR CONSEQUENTIAL LOSS OR DAMAGES OF ANY
 * CHARACTER ARISING OUT OF THE USE OF THE CODE AND/OR THE LICENSE INCLUDING
 * BUT NOT LIMITED TO PERSONAL INJURY, LOSS OF DATA, LOSS OF PROFITS, LOSS OF
 * ASSIGNMENTS, DATA OR OUTPUT FROM THE SERVICE BEING RENDERED INACCURATE,
 * FAILURE OF CODE, SERVER DOWN TIME, DAMAGES FOR LOSS OF GOODWILL, BUSINESS
 * INTERRUPTION, COMPUTER FAILURE OR MALFUNCTION, OR ANY AND ALL OTHER DAMAGES
 * OR LOSSES OF WHATEVER NATURE, EVEN IF SLI HAS BEEN INFORMED OF THE
 * POSSIBILITY OF SUCH DAMAGES.
 */

/**
 * Feed generation email
 *
 * @package    SLI
 * @subpackage Search
 */
class SLI_Search_Model_Email extends Varien_Object
{
    const DEFAULT_STORE_ID = 0;
    const SLI_FEED_EMAIL_TEMPLATE = 'sli_feed_email_template';

    /**
     * Send feed notification email.
     */
    public function send()
    {
        if ($toEmail = $this->getData('email')) {
            /** @var $mailer Mage_Core_Model_Email_Template_Mailer */
            $mailer = Mage::getModel('core/email_template_mailer');
            /** @var $emailInfo Mage_Core_Model_Email_Info */
            $emailInfo = Mage::getModel('core/email_info');

            $emailInfo->addTo($toEmail);
            $mailer->addEmailInfo($emailInfo);

            // Set all required params and send emails
            $mailer->setSender('general');
            $mailer->setStoreId(self::DEFAULT_STORE_ID);
            $mailer->setTemplateId(self::SLI_FEED_EMAIL_TEMPLATE);
            $mailer->setTemplateParams(
                array(
                    'subject' => $this->getData('subject'),
                    'messages' => (array) $this->getData('msg'),
                )
            );
            $mailer->send();
        }
    }
}
