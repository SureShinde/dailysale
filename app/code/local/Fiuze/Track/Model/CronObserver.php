<?php
/**
 * @author Fiuze Team
 * @category Fiuze
 * @package Fiuze_Track
 * @copyright Copyright (c) 2016 Fiuze
 */

class Fiuze_Track_Model_CronObserver {

    const CONFIG_NODE_JOBS = 'crontab/jobs_aftership';
    const AFTERSHIP_STATUS_NODE = 'aftership_options/messages/status';
    const AFTERSHIP_ENABLE_CRON_NOTIFY_NODE = 'aftership_options/messages/cron_job_enable';
    const AFTERSHIP_ENABLE_CRON_SCHEDULE_NODE = 'aftership_options/messages/cron_schedule';

    const CACHE_KEY_LAST_SCHEDULE_GENERATE_AT   = 'cron_last_schedule_generate_at_aftrship';
    const CACHE_KEY_LAST_HISTORY_CLEANUP_AT     = 'cron_last_history_cleanup_at_aftrship';

    const XML_PATH_SCHEDULE_GENERATE_EVERY  = 'system/cron/schedule_generate_every';
    const XML_PATH_SCHEDULE_AHEAD_FOR       = 'system/cron/schedule_ahead_for';
    const XML_PATH_SCHEDULE_LIFETIME        = 'system/cron/schedule_lifetime';
    const XML_PATH_HISTORY_CLEANUP_EVERY    = 'system/cron/history_cleanup_every';
    const XML_PATH_HISTORY_SUCCESS          = 'system/cron/history_success_lifetime';
    const XML_PATH_HISTORY_FAILURE          = 'system/cron/history_failure_lifetime';

    const REGEX_RUN_MODEL = '#^([a-z0-9_]+/[a-z0-9_]+)::([a-z0-9_]+)$#i';

    protected $_pendingSchedules;

    /**
     * Process cron queue
     * Generate tasks schedule
     * Cleanup tasks schedule
     *
     * @param Varien_Event_Observer $observer
     */
    public function runCronAftership($observer)
    {
        if (Mage::getStoreConfig(self::AFTERSHIP_STATUS_NODE) &&
            Mage::getStoreConfig(self::AFTERSHIP_ENABLE_CRON_NOTIFY_NODE)) {
            $schedules = $this->getPendingSchedules();
            $jobsRoot = Mage::getConfig()->getNode(self::CONFIG_NODE_JOBS);

            /** @var $schedule Mage_Cron_Model_Schedule */
            foreach ($schedules->getIterator() as $schedule) {
                $jobConfig = $jobsRoot->{$schedule->getJobCode()};
                $this->_processJob($schedule, $jobConfig);
            }

            $this->generate();
        }

        $this->cleanup();
    }

    public function getPendingSchedules()
    {
        if (!$this->_pendingSchedules) {
            $this->_pendingSchedules = Mage::getModel('track/scheduleAftership')->getCollection()
                ->addFieldToFilter('status', Aftership_Track_Model_ScheduleAftership::STATUS_PENDING)
                ->load();
        }
        return $this->_pendingSchedules;
    }

    /**
     * Generate cron schedule
     *
     * @return Mage_Cron_Model_Observer
     */
    public function generate()
    {
        /**
         * check if schedule generation is needed
         */
        $lastRun = Mage::app()->loadCache(self::CACHE_KEY_LAST_SCHEDULE_GENERATE_AT);
        if ($lastRun > time() - Mage::getStoreConfig(self::XML_PATH_SCHEDULE_GENERATE_EVERY)*60) {
            return $this;
        }

        $schedules = $this->getPendingSchedules();
        $exists = array();
        foreach ($schedules->getIterator() as $schedule) {
            $exists[$schedule->getJobCode().'/'.$schedule->getScheduledAt()] = 1;
        }

        /**
         * generate global crontab jobs
         */
        $config = Mage::getConfig()->getNode(self::CONFIG_NODE_JOBS);
        if ($config instanceof Mage_Core_Model_Config_Element) {
            $this->_generateJobs($config->children(), $exists);
        }

        /**
         * save time schedules generation was ran with no expiration
         */
        Mage::app()->saveCache(time(), self::CACHE_KEY_LAST_SCHEDULE_GENERATE_AT, array('crontab'), null);

        return $this;
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
        $scheduleAheadFor = Mage::getStoreConfig(self::XML_PATH_SCHEDULE_AHEAD_FOR)*60;

        foreach ($jobs as $jobCode => $jobConfig) {
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
            $tamePeriod = explode(',', $cronExpr);

            foreach($tamePeriod as $period){
                $scheduleCount = Mage::getModel('track/scheduleAftership')->getCollection()
                    ->addFieldToFilter('status', Aftership_Track_Model_ScheduleAftership::STATUS_PENDING)
                    ->addFieldToFilter('job_code', $jobCode)
                    ->addFieldToFilter('cron_expr', $period)->count();
                if(!$scheduleCount){
                    $schedule = Mage::getModel('track/scheduleAftership');
                    $currentTime = time();
                    $schedule->setJobCode($jobCode);
                    $schedule->setCronExpr($period);
                    $schedule->setStatus(Aftership_Track_Model_ScheduleAftership::STATUS_PENDING);

                    $ts = strftime('%Y-%m-%d %H:%M:00', $currentTime);
                    if (!$schedule->trySchedule($currentTime)) {
                        continue;
                    }
                    $schedule->unsScheduleId()->save();
                }
            }
        }
        return $this;
    }

    /**
     * Process cron task
     *
     * @param Mage_Cron_Model_Schedule $schedule
     * @param $jobConfig
     * @param bool $isAlways
     * @return Mage_Cron_Model_Observer
     */
    protected function _processJob($schedule, $jobConfig, $isAlways = false)
    {
        $runConfig = $jobConfig->run;
        if (!$isAlways) {
            $scheduleLifetime = Mage::getStoreConfig(self::XML_PATH_SCHEDULE_LIFETIME) * 60;
            $now = time();
            $time = strtotime($schedule->getScheduledAt());
            if ($time > $now) {
                return;
            }
        }

        $errorStatus = Mage_Cron_Model_Schedule::STATUS_ERROR;
        try {
            if (!$isAlways) {
                if ($time < $now - $scheduleLifetime) {
                    $errorStatus = Aftership_Track_Model_ScheduleAftership::STATUS_MISSED;
                    Mage::throwException(Mage::helper('cron')->__('Too late for the schedule.'));
                }
            }
            if ($runConfig->model) {
                if (!preg_match(self::REGEX_RUN_MODEL, (string)$runConfig->model, $run)) {
                    Mage::throwException(Mage::helper('cron')->__('Invalid model/method definition, expecting "model/class::method".'));
                }
                if (!($model = Mage::getModel($run[1])) || !method_exists($model, $run[2])) {
                    Mage::throwException(Mage::helper('cron')->__('Invalid callback: %s::%s does not exist', $run[1], $run[2]));
                }
                $callback = array($model, $run[2]);
                $arguments = array($schedule);
            }
            if (empty($callback)) {
                Mage::throwException(Mage::helper('cron')->__('No callbacks found'));
            }

            if (!$isAlways) {
                if (!$schedule->tryLockJob()) {
                    // another cron started this job intermittently, so skip it
                    return;
                }
                /**
                though running status is set in tryLockJob we must set it here because the object
                was loaded with a pending status and will set it back to pending if we don't set it here
                 */
            }

            $schedule
                ->setExecutedAt(strftime('%Y-%m-%d %H:%M:%S', time()))
                ->save();

            call_user_func_array($callback, $arguments);

            $schedule
                ->setStatus(Aftership_Track_Model_ScheduleAftership::STATUS_SUCCESS)
                ->setFinishedAt(strftime('%Y-%m-%d %H:%M:%S', time()));

        } catch (Exception $e) {
            $schedule->setStatus($errorStatus)
                ->setMessages($e->__toString());
        }
        $schedule->save();

        return $this;
    }

    /**
     * Clean up the history of tasks
     *
     * @return Mage_Cron_Model_Observer
     */
    public function cleanup()
    {
        // check if history cleanup is needed
        $lastCleanup = Mage::app()->loadCache(self::CACHE_KEY_LAST_HISTORY_CLEANUP_AT);
        if ($lastCleanup > time() - Mage::getStoreConfig(self::XML_PATH_HISTORY_CLEANUP_EVERY)*60) {
            return $this;
        }

        $history = Mage::getModel('cron/schedule')->getCollection()
            ->addFieldToFilter('status', array('in'=>array(
                Mage_Cron_Model_Schedule::STATUS_SUCCESS,
                Mage_Cron_Model_Schedule::STATUS_MISSED,
                Mage_Cron_Model_Schedule::STATUS_ERROR,
            )))
            ->load();

        $historyLifetimes = array(
            Mage_Cron_Model_Schedule::STATUS_SUCCESS => Mage::getStoreConfig(self::XML_PATH_HISTORY_SUCCESS)*60,
            Mage_Cron_Model_Schedule::STATUS_MISSED => Mage::getStoreConfig(self::XML_PATH_HISTORY_FAILURE)*60,
            Mage_Cron_Model_Schedule::STATUS_ERROR => Mage::getStoreConfig(self::XML_PATH_HISTORY_FAILURE)*60,
        );

        $now = time();
        foreach ($history->getIterator() as $record) {
            if (strtotime($record->getExecutedAt()) < $now-$historyLifetimes[$record->getStatus()]) {
                $record->delete();
            }
        }

        // save time history cleanup was ran with no expiration
        Mage::app()->saveCache(time(), self::CACHE_KEY_LAST_HISTORY_CLEANUP_AT, array('crontab'), null);

        return $this;
    }

}