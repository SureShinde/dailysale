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
 * Cron Frequency Model
 *
 * @package    SLI
 * @subpackage Search
 */
class SLI_Search_Model_System_Config_Source_Cron_Frequency
{
    const CRON_EVERY_HOUR = '1H';
    const CRON_3_HOURLY = '3H';
    const CRON_6_HOURLY = '6H';
    const CRON_12_HOURLY = '12H';
    const CRON_DAILY = 'D';

    protected static $OPTIONS;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (!self::$OPTIONS) {
            self::$OPTIONS = array(
                array(
                    'label' => Mage::helper('cron')->__('Every Hour'),
                    'value' => self::CRON_EVERY_HOUR,
                ),
                array(
                    'label' => Mage::helper('cron')->__('3 Hourly'),
                    'value' => self::CRON_3_HOURLY,
                ),
                array(
                    'label' => Mage::helper('cron')->__('6 Hourly'),
                    'value' => self::CRON_6_HOURLY,
                ),
                array(
                    'label' => Mage::helper('cron')->__('12 Hourly'),
                    'value' => self::CRON_12_HOURLY,
                ),
                array(
                    'label' => Mage::helper('cron')->__('Daily'),
                    'value' => self::CRON_DAILY,
                ),
            );
        }
        return self::$OPTIONS;
    }
}

