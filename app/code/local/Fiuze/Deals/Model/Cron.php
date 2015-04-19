<?php

/**
 * Cron
 *
 * @category    Fiuze
 * @package     Fiuze_Deals
 * @author      Webinse Team <info@webinse.com>
 */
class Fiuze_Deals_Model_Cron extends Mage_Core_Model_Abstract
{
    protected $_pendingSchedules;

    /**
     * Update cron product
     */
    public function dailyCatalogUpdate()
    {
        $dealResource = Mage::getResourceModel('fiuze_deals/deals_collection');
        $productDeals = $dealResource->addFilter('deals_active', 1)
            ->addFieldToFilter(
                array(
                    'deals_qty',
                    'current_active',
                ),
                array(
                    array('gt' => 0),
                    array('eq' => 1),
                )
            )
            ->addOrder('sort_order', Varien_Data_Collection::SORT_ORDER_ASC)
            ->getItems();

        if (!$productDeals) {
            return;
        }

        $productActive = Mage::getResourceModel('fiuze_deals/deals_collection')->addFilter('current_active', 1)->getSize();
        try {
            if (count($productDeals) == 1) {
                $product = array_shift($productDeals);
                if (!$product->getData('deals_qty') && $product->getData('current_active')) {
                    $this->_setData($product, (float)$product->getData('origin_special_price'), false, false);
                    return;
                }
                if (!$productActive) {
                    $this->_setData($product, (float)$product->getData('deals_price'));
                    return;
                }
            }

            //cyclical overkill
            $count = 0;
            reset($productDeals);
            while ($count < count($productDeals)) {
                $item = current($productDeals);
                if ($item->getCurrentActive()) {

                    // set data for current item (remove element from rotation)
                    $this->_setData($item, (float)$item->getData('origin_special_price'), false, false);

                    // set data for next item (add element to rotation)
                    $item = next($productDeals);
                    if ($item === false) {
                        $item = reset($productDeals);
                    }

                    $this->_setData($item, (float)$item->getData('deals_price'));
                    return;
                }

                next($productDeals);
                $count++;
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
        return;
    }

    /**
     * Set special data to product and to rotation element
     *
     * @param $item
     * @param float $specialPrice
     * @param bool $endDate
     * @param int $isCurrent
     */
    protected function _setData($item, $specialPrice, $endDate = true, $isCurrent = 1)
    {
        $product = Mage::getModel('catalog/product')->load($item->getData('product_id'));
        $productSpecialPrice = $product->getSpecialPrice();
        if (!$productSpecialPrice) {
            $productSpecialPrice = $product->getPrice();
        }
        if ($specialPrice) {
            $product->setSpecialPrice($specialPrice);
        }

        $item->setData('origin_special_price', $productSpecialPrice);
        $item->setEndTime((($endDate) ? Mage::helper('fiuze_deals')->getEndDealTime() : 0));
        $item->setCurrentActive($isCurrent);

        try {
            $product->save();
            $item->save();
        } catch (Exception $e) {
            Mage::logException($e);
        }
        return;
    }

    /**
     * Generate cron schedule
     *
     * @return Mage_Cron_Model_Observer
     */
    public function generate()
    {
        $schedules = $this->getPendingSchedules();
        $exists = array();
        foreach ($schedules->getIterator() as $schedule) {
            $exists[$schedule->getJobCode() . '/' . $schedule->getScheduledAt()] = 1;
        }

        /**
         * generate global crontab jobs
         */
        $config = Mage::getConfig()->getNode('crontab/jobs');
        if ($config instanceof Mage_Core_Model_Config_Element) {
            $this->_generateJobs($config->children(), $exists);
        }

        return $this;
    }

    public function getPendingSchedules()
    {
        if (!$this->_pendingSchedules) {
            $this->_pendingSchedules = Mage::getModel('cron/schedule')->getCollection()
                ->addFieldToFilter('job_code', 'fiuze_deals_scheduler')
                ->addFieldToFilter('status', Mage_Cron_Model_Schedule::STATUS_PENDING)
                ->load();
        }
        return $this->_pendingSchedules;
    }

    /**
     * Generate jobs for config information
     *
     * @param   $jobs
     * @param   array $exists
     * @return  Mage_Cron_Model_Observer
     */
    protected function _generateJobs($jobs, $exists)
    {
        $scheduleAheadFor = 24 * 60 * 60;
        $schedule = Mage::getModel('cron/schedule');

        foreach ($jobs as $jobCode => $jobConfig) {
            if ($jobCode == "fiuze_deals_scheduler") {
                $cronExpr = null;
                if ($jobConfig->schedule->config_path) {
                    $cronExpr = Mage::getStoreConfig((string)$jobConfig->schedule->config_path);
                }
                if (empty($cronExpr) && $jobConfig->schedule->cron_expr) {
                    $cronExpr = (string)$jobConfig->schedule->cron_expr;
                }
                if (!$cronExpr || $cronExpr == 'always') {
                    continue;
                }

                $now = time();
                $timeAhead = $now + $scheduleAheadFor;
                $schedule->setJobCode($jobCode)
                    ->setCronExpr($cronExpr)
                    ->setStatus(Mage_Cron_Model_Schedule::STATUS_PENDING);

                for ($time = $now; $time < $timeAhead; $time += 60) {
                    $ts = strftime('%Y-%m-%d %H:%M:00', $time);
                    if (!empty($exists[$jobCode . '/' . $ts])) {
                        // already scheduled
                        continue;
                    }
                    if (!$schedule->trySchedule($time)) {
                        // time does not match cron expression
                        continue;
                    }
                    $schedule->unsScheduleId()->save();
                }
            }
        }
        return $this;
    }

}























