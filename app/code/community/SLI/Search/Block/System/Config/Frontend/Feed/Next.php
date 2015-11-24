<?php

/**
 * Copyright (c) 2015 S.L.I. Systems, Inc. (www.sli-systems.com) - All Rights
 * Reserved
 * This file is part of Learning Search Connect.
 * Learning Search Connect is distributed under a limited and restricted
 * license – please visit www.sli-systems.com/LSC for full license details.
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
 * Determines next time the feed cron will run.
 *
 * @package    SLI
 * @subpackage Search
 */
class SLI_Search_Block_System_Config_Frontend_Feed_Next extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Renders the next scheduled cron time first from the cron table and then
     * from the set cron time if Magento hasnt scheduled it.
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return Zend_Date
     */
    protected function  _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        /** @var SLI_Search_Helper_Data $dataHelper */
        $dataHelper = Mage::helper('sli_search/data');

        if (!$dataHelper->isCronEnabled()) {
            return 'Disabled';
        }

        /** @var Mage_Cron_Model_Resource_Schedule_Collection $cronScheduleCollection */
        $cronScheduleCollection = Mage::getResourceModel('cron/schedule_collection');
        // find pending feed schedules
        $cronScheduleCollection
            ->addFieldToFilter('job_code', SLI_Search_Model_Cron::JOB_CODE_GENERATE_FEEDS)
            ->addFieldToFilter('status', Mage_Cron_Model_Schedule::STATUS_PENDING);
        // limit to lowest scheduled_at datet
        $cronScheduleCollection
            ->getSelect()->reset(Zend_Db_Select::COLUMNS)->columns(array('scheduled_at' => 'min(scheduled_at)'));

        // next scheduled date
        $scheduledAt = $cronScheduleCollection->getFirstItem()->getData('scheduled_at');

        // if nothing scheduled, calculate based on our cron config
        if ($scheduledAt) {
            /** @var Mage_Core_Model_Locale $locale */
            $locale = Mage::app()->getLocale();
            $scheduledAt = $locale->storeDate(null, $scheduledAt, true);
        } else {
            $scheduledAt = $dataHelper->getNextRunDateFromCronTime();
        }

        return $scheduledAt;
    }
}
