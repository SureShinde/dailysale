<?php

class Fiuze_Track_Model_ScheduleAftership extends Mage_Core_Model_Abstract
{
    const STATUS_PENDING = 'pending';
    const STATUS_RUNNING = 'running';
    const STATUS_SUCCESS = 'success';
    const STATUS_MISSED = 'missed';
    const STATUS_ERROR = 'error';

    public function _construct()
    {
        $this->_init('track/scheduleAftership');
    }

    /**
     * Checks the observer's cron expression against time
     *
     * Supports $this->setCronExpr('* 0-5,10-59/5 2-10,15-25 january-june/2 mon-fri')
     *
     * @param Varien_Event $event
     * @return boolean
     */
    public function trySchedule($time)
    {
        $e = $this->getCronExpr();
        if (!$e || !$time) {
            return false;
        }
        if (!is_numeric($time)) {
            $time = strtotime($time);
        }

        $d = getdate(Mage::getSingleton('core/date')->timestamp($time));
        $timePeriod = (int)$this->getCronExpr() * 60 * 60;

        $this->setCreatedAt(strftime('%Y-%m-%d %H:%M:%S', time()));
        $this->setScheduledAt(strftime('%Y-%m-%d %H:%M', $time +=$timePeriod ));

        return true;
    }


    /**
     * Sets a job to STATUS_RUNNING only if it is currently in STATUS_PENDING.
     * Returns true if status was changed and false otherwise.
     *
     * @param $oldStatus
     * This is used to implement locking for cron jobs.
     *
     * @return boolean
     */
    public function tryLockJob($oldStatus = self::STATUS_PENDING)
    {
        return $this->_getResource()->trySetJobStatusAtomic($this->getId(), self::STATUS_RUNNING, $oldStatus);
    }
}
