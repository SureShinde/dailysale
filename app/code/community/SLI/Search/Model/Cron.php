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
 * Cron activities for the sli feed generation
 *
 * @package    SLI
 * @subpackage Search
 */
class SLI_Search_Model_Cron
{
    // matching config.xml job code
    const JOB_CODE_GENERATE_FEEDS = 'sli_search';
    // matching config.xml path for model + cron_expr
    const CRON_GENERATE_FEEDS_SCHEDULE_EXPR_PATH = 'crontab/jobs/sli_search/schedule/cron_expr';
    const CRON_GENERATE_FEEDS_MODEL_RUN_PATH = 'crontab/jobs/sli_search/run/model';

    /**
     * Generates the feeds and sends email of status when done
     */
    public function generateFeeds()
    {
        /** @var $dataHelper SLI_Search_Helper_Data */
        $dataHelper = Mage::helper('sli_search/data');
        if ($dataHelper->isCronEnabled()) {
            try {
                /** @var $feedManager SLI_Search_Model_FeedManager */
                $feedManager = Mage::getModel('sli_search/FeedManager');
                $msg = $feedManager->generateFeedForStores();
            } catch (SLI_Search_Exception $e) {
                $msg = array('messages' => array('Error' => $e->getMessage()));
            } catch (Exception $e) {
                $msg = array(
                    'messages' => array(
                        'Unknown Error' => "{$e->getMessage()} in {$e->getFile()} on line {$e->getLine()}."
                            . "Please contact your SLI provider."
                    )
                );
            }

            $this->sendEmail($msg);

            return true;
        }

        return false;
    }

    /**
     * If there is a system config email set, send out the cron notification email.
     *
     * @param string $msg
     */
    protected function sendEmail($msg)
    {
        /** @var $dataHelper SLI_Search_Helper_Data */
        $dataHelper = Mage::helper('sli_search/data');
        if ($cronEmail = $dataHelper->getCronEmail()) {
            Mage::getModel('sli_search/email')
                ->setData('msg', $msg['messages'])
                ->setData('subject', 'Scheduled feed generation')
                ->setData('email', $cronEmail)
                ->send();
        }
    }
}
