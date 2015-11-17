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
 * System configuration backend model for the cron frequency system configuration
 *
 * @package    SLI
 * @subpackage Search
 */
class SLI_Search_Model_System_Config_Backend_Cron extends Mage_Core_Model_Config_Data
{
    /**
     * When frequency system configuration saves, save the values from the frequency
     * and time as a cron line that Magento cron will use.
     */
    protected function _afterSave()
    {
        /** @var $dataHelper SLI_Search_Helper_Data */
        $dataHelper = Mage::helper('sli_search/data');

        // get UI values
        $frequency = $this->getData('groups/cron/fields/frequency/value');
        $time = $this->getData('groups/cron/fields/time/value');

        $cronTab = $dataHelper->getCronTimeAsCrontab($frequency, $time);
        try {
            $this->saveCronTab($cronTab);
        } catch (Exception $e) {
            throw new Exception(Mage::helper('cron')->__('Unable to save the cron expression.'));
        }

        return parent::_afterSave();
    }

    /**
     * Save crontab line for feed generation cron job.
     *
     * @param string $cronTab The crontab line.
     * @param string $model
     */
    public function saveCronTab($cronTab, $model = null)
    {
        Mage::getModel('core/config_data')
            ->load(SLI_Search_Model_Cron::CRON_GENERATE_FEEDS_SCHEDULE_EXPR_PATH, 'path')
            ->setValue($cronTab)
            ->setPath(SLI_Search_Model_Cron::CRON_GENERATE_FEEDS_SCHEDULE_EXPR_PATH)
            ->save();

        if ($model) {
            // install only
            Mage::getModel('core/config_data')
                ->load(SLI_Search_Model_Cron::CRON_GENERATE_FEEDS_MODEL_RUN_PATH, 'path')
                ->setValue($model)
                ->setPath(SLI_Search_Model_Cron::CRON_GENERATE_FEEDS_MODEL_RUN_PATH)
                ->save();
        }
    }
}
