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
 * Trigger feed generation.
 *
 * @package    SLI
 * @subpackage Search
 */
class SLI_Search_Adminhtml_SliController extends Mage_Adminhtml_Controller_Action
{
    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')
            ->isAllowed('system/config/sli_search/manual_feed');
    }

    /**
     * Feed generation action, linked to from the admin UI
     * uri: /admin/sli/generateFeeds
     */
    public function generateFeedsAction()
    {
        $response = array();

        $storeIds = array();
        if ($storeId = $this->getRequest()->getParam("storeId")) {
            $storeIds[] = $storeId;
        }

        /** @var $dataHelper SLI_Search_Helper_Data */
        $dataHelper = Mage::helper('sli_search/data');

        try {
            /** @var $feedManager SLI_Search_Model_FeedManager */
            $feedManager = Mage::getModel('sli_search/FeedManager');
            $response = $feedManager->generateFeedForStores($storeIds);
        } catch (SLI_Search_Exception $e) {
            $response['messages'] = array("Error" => $e->getMessage());
            $response['error'] = true;
        } catch (Exception $e) {
            Mage::logException($e);
            $response['messages'] = array("Error" => "An unknown error occurred. Please contact your SLI provider");;
            $response['error'] = true;
        }

        // email results
        if ($dataHelper->sendEmail($response['error'])) {
            Mage::getModel('sli_search/email')
                ->setData('msg', $response['messages'])
                ->setData('subject', 'Manual Feed Generation')
                ->setData('email', $dataHelper->getFeedEmail())
                ->send();
        }

        $this->getResponse()
            ->setHeader("Content-Type", "application/json")
            ->setBody(json_encode($response));
    }
}
