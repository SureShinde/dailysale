<?php

class Bronto_Reviews_Model_Message extends Bronto_Common_Model_Email_Template
{
    protected $_helper = 'bronto_reviews';
    protected $_apiLogFile = 'bronto_reviews_api.log';

    protected $_additionalFields = array();
    protected $_additionalData = array();

    /**
     * @see parent
     */
    protected function _emailClass()
    {
        return 'bronto_reviews/message';
    }

    /**
     * Set the send period for this message
     *
     * @param int $sendPeriod
     * @return Bronto_Reviews_Model_Message
     */
    public function setSendTime($sendPeriod)
    {
        return $this->addParam('sendPeriod', max($sendPeriod, 0));
    }

    /**
     * Set the time of day for this message
     *
     * @param int $timeOfDay
     * @return Bronto_Reviews_Model_Message
     */
    public function setTimeOfDay($timeOfDay)
    {
        return $this->addParam('timeOfDay', $timeOfDay);
    }

    /**
     * Sets some arbitrary param value to be serialized
     *
     * @param string $key
     * @param mixed $value
     * @return Bronto_Reviews_Model_Message
     */
    public function addParam($key, $value)
    {
        $this->_additionalData[$key] = $value;
        return $this;
    }

    /**
     * Gets all of the serialized values
     *
     * @return array
     */
    public function getAdditionalData()
    {
        return $this->_additionalData;
    }

    /**
     * Sets some arbitrary delivery fields to be sent
     *
     * @param string $key
     * @param mixed $value
     * @return Bronto_Reviews_Model_Message
     */
    public function addDeliveryField($key, $value)
    {
        $this->_additionalFields[$key] = $value;
        return $this;
    }

    /**
     * @see parent
     */
    protected function _startTime($storeId)
    {
        $now = time();
        $sendPeriod = $this->_additionalData['sendPeriod'];
        $timeOfDay = $this->_additionalData['timeOfDay'];
        $currentDays = strtotime('+' . abs($sendPeriod) . ' days', $now);
        if ($timeOfDay > -1) {
            $currentHour = (int)date('H', $now);
            // Target time is in the future: add the diff
            if ($currentHour < $timeOfDay) {
                $currentDays += (($timeOfDay - $currentHour) * 60 * 60);
            // Target time is earlier in the day: add one day, and sub diff
            } else if ($timeOfDay < $currentHour) {
                $currentDays += (24 * 60 * 60) - (($currentHour - $timeOfDay) * 60 * 60);
            }
        }
        return date('c', $currentDays);
    }

    /**
     * @see parent
     */
    protected function _additionalFields($delivery, $variables)
    {
        foreach ($this->_additionalFields as $key => $value) {
            $delivery->setField($key, $value, 'html');
        }
    }

    /**
     * @see parent
     */
    protected function _additionalData()
    {
        return $this->_additionalData;
    }

    /**
     * @see parent
     */
    protected function setSendQueue($queue)
    {
        $this->_additionalData = $queue->getAdditionalData()->getData();
        $this->setData('send_queue', $queue);
        return $this;
    }

    /**
     * @see parent
     */
    protected function _afterSend($success, $error = null, Bronto_Api_Delivery_Row $delivery = null)
    {
        $helper = Mage::helper($this->_helper);
        if (!is_null($delivery)) {
            if ($success) {
                $queue = $this->getSendQueue();
                $orderId = $queue->getAdditionalData()->getOrderId();
                $log = Mage::getModel('bronto_reviews/log')
                    ->loadByOrderAndDeliveryId($orderId, $queue->getId());
                $logId = $log->getId();
                $log->setData($queue->getAdditionalData()->getData());
                $log->setId($logId)
                    ->setDeliveryId($delivery->id)
                    ->setMessageId($delivery->messageId)
                    ->setMessageName($this->getBrontoMessageName())
                    ->setFields(serialize($delivery->getFields()))
                    ->setDeliveryDate(date('Y-m-d H:i:s', strtotime($delivery->start)));
                $log->save();
            }
            $status = $success ? 'Successful' : 'Failed';

            $helper->writeVerboseDebug("===== $status Reviews Delivery =====", $this->_apiLogFile);
            $helper->writeVerboseDebug(var_export($delivery->getApi()->getLastRequest(), true), $this->_apiLogFile);
            $helper->writeVerboseDebug(var_export($delivery->getApi()->getLastResponse(), true), $this->_apiLogFile);
        }
    }
}
