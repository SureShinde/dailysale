<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    Unirgy_DropshipPayout
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */
 
class Unirgy_DropshipPayout_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function isVendorEnabled($vendor, $scheduled=false)
    {
        if (!$scheduled) {
            return $vendor->getData("payout_type");
        } else {
            $psCodes = $this->getPayoutSchedules('code2schedule');
            return $vendor->getData("payout_type")=='scheduled'
                && (array_key_exists($vendor->getData("payout_schedule_type"), $psCodes) || $vendor->getData("payout_schedule"));
        }
    }
    
    public function createPayout($vendor, $status='pending', $payoutType=null)
    {
        if (!$vendor instanceof Unirgy_Dropship_Model_Vendor) {
            $vendor = Mage::helper('udropship')->getVendor($vendor);
        }
        $payout = Mage::getModel('udpayout/payout')->addData(array(
            'payout_type' => !is_null($payoutType) ? $payoutType : $vendor->getPayoutType(),
            'payout_method' => $vendor->getPayoutMethod(),
            'payout_status' => $status,
            'vendor_id' => $vendor->getId(),
            'po_type' => $vendor->getStatementPoType()
        ));
        return $payout->initTotals();
    }
    
    public function processPost()
    {
        $hlp = Mage::helper('udpayout');

        $r = Mage::app()->getRequest();
        $dateFrom = $r->getParam('date_from');
        $dateTo = $r->getParam('date_to');

        $datetimeFormatInt = Varien_Date::DATETIME_INTERNAL_FORMAT;
        $dateFormat = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
        if ($r->getParam('use_locale_timezone')) {
            $dateFrom = Mage::helper('udropship')->dateLocaleToInternal($dateFrom, $dateFormat, true);
            $dateTo = Mage::helper('udropship')->dateLocaleToInternal($dateTo, $dateFormat, true);
            $dateTo = Mage::app()->getLocale()->date($dateTo, $datetimeFormatInt, null, false)
                        ->addDay(1)->subSecond(1)->toString($datetimeFormatInt);
        } else {
            $dateFrom = Mage::app()->getLocale()->date($dateFrom, $dateFormat, null, false)->toString($datetimeFormatInt);
            $dateTo = Mage::app()->getLocale()->date($dateTo, $dateFormat, null, false)->addDay(1)->subSecond(1)->toString($datetimeFormatInt);
        }

        if ($r->getParam('all_vendors')) {
            $vendors = Mage::getModel('udropship/vendor')->getCollection()
                ->addFieldToFilter('status', 'A')
                ->getAllIds();
        } else {
            $vendors = $r->getParam('vendor_ids');
        }
        
        if (empty($vendors)) {
            Mage::throwException(Mage::helper('udropship')->__('Please select vendors'));
        }
        
        $payouts = Mage::getResourceModel('udpayout/payout_collection')->setDateFrom($dateFrom)->setDateTo($dateTo);
        foreach ($vendors as $vId) {
            $payout = $this->createPayout(
                $vId, 
                Unirgy_DropshipPayout_Model_Payout::STATUS_PENDING, 
                Unirgy_DropshipPayout_Model_Payout::TYPE_MANUAL
            );
            $payout->setUseLocaleTimezone($r->getParam('use_locale_timezone'));
            $payout->setDateFrom($dateFrom)->setDateTo($dateTo);
            $payouts->addExternalPayout($payout, false);
        }
        $payouts->addPendingPos($vendors);
        $payouts->finishPayout()->saveOrdersPayouts();
        
        return $payouts;
    }

    public function getPayoutSchedules($hashType=false)
    {
        $ps = Mage::getStoreConfig('udropship/payout/payout_schedules');
        $ps = Mage::helper('udropship')->unserialize($ps);
        usort($ps, array(Mage::helper('udropship'), 'sortBySortOrder'));
        $psCodes = array();
        if (is_array($ps)) {
            $psCodes = array();
            foreach ($ps as $_ps) {
                if ($hashType=='code2title') {
                    $psCodes[@$_ps['code']] = @$_ps['title'];
                } elseif ($hashType=='code2schedule') {
                    $psCodes[@$_ps['code']] = @$_ps['schedule'];
                } else {
                    $psCodes[] = $_ps;
                }
            }
        }
        return $psCodes;
    }
    
    public function generateSchedules()
    {
        $now = time();
        $scheduleAheadFor = Mage::getStoreConfig(Mage_Cron_Model_Observer::XML_PATH_SCHEDULE_AHEAD_FOR)*60;
        $timeAhead = $now + $scheduleAheadFor;

        $exists = array();
        $scheduled = Mage::getModel('udpayout/payout')->getCollection()
            ->addFieldToFilter('scheduled_at', array('datetime'=>true, 'from'=>strftime('%Y-%m-%d %H:%M:00', $now)));
        foreach ($scheduled as $p) {
            $exists[$p->getVendorId().'/'.$p->getScheduledAt()] = 1;
        }

        $schedule = Mage::getModel('cron/schedule');

        $vendors = Mage::getModel('udropship/vendor')->getCollection();
        $psCodes = $this->getPayoutSchedules('code2schedule');
        $vendors->getSelect()->where("payout_type='scheduled'");
        if (empty($psCodes)) {
            $vendors->getSelect()->where("payout_schedule<>''");
        } else {
            $vendors->getSelect()->where("payout_schedule_type in (?) OR (payout_schedule<>'' AND payout_schedule is not null)", array_keys($psCodes));
        }

        foreach ($vendors as $vId=>$v) {
            $v->afterLoad();
            $payout = $this->createPayout(
                $vId,
                Unirgy_DropshipPayout_Model_Payout::TYPE_SCHEDULED, 
                Unirgy_DropshipPayout_Model_Payout::STATUS_SCHEDULED
            );

            try {
            if ($v->getData("payout_schedule_type") && array_key_exists($v->getData("payout_schedule_type"), $psCodes)) {
                $schedule->setCronExpr($psCodes[$v->getData("payout_schedule_type")]);
            } else {
                $schedule->setCronExpr($v->getData("payout_schedule"));
            }
            } catch (Exception $e) {
                continue;
            }

            for ($time = $now; $time < $timeAhead; $time += 60) {
                $ts = strftime('%Y-%m-%d %H:%M:00', $time);
                if (empty($exists["{$vId}/{$ts}"]) && $schedule->trySchedule($time)) {
                    $payout->unsPayoutId()->setScheduledAt($ts)->save();
                }
            }
        }

        return $this;
    }
    
    public function cleanupSchedules()
    {
        return $this;
    }
    
    public function getEmptyPayoutOrder($format=false)
    {
        return Mage::helper('udropship')->getStatementEmptyOrderAmounts($format);
    }
    
    public function getEmptyPayoutTotals($format=false)
    {
        return Mage::helper('udropship')->getStatementEmptyTotalsAmount($format);
    }
    
    public function getEmptyCalcPayoutTotals($format=false)
    {
        return Mage::helper('udropship')->getStatementEmptyCalcTotalsAmount($format);
    }
}
