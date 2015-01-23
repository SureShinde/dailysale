<?php

class Bronto_Common_Model_Error extends Mage_Core_Model_Abstract implements Bronto_Util_Retryer_RetryerInterface
{
    protected $_api;

    /**
     * @see parent
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('bronto_common/error');
    }

    /**
     * @param Bronto_Api
     * @return Bronto_Common_Model_Error
     */
    public function setClient(Bronto_Api $api)
    {
        $this->_api = $api;
        return $this;
    }

    /**
     * Gets the API client matching this token
     *
     * @param string $token
     * @return Bronto_Api
     */
    protected function _client($token)
    {
        if (empty($this->_api) || $this->_api->getToken() != $token) {
            $this->setClient(Mage::helper('bronto_common')->getApi($token));
        }

        return $this->_api;
    }

    /**
     * @see parent
     *
     * @param Bronto_Api_Object $object
     * @param int $attempts
     * @return int|false
     */
    public function store(Bronto_Api_Object $object, $attempts = 0)
    {
        // Only deliveries are retried
        if ($object instanceOf Bronto_Api_Delivery) {
            if ($this->hasId() && empty($attempts)) {
                $this
                    ->unsId()
                    ->unsEmailClass();
            }
            // Retry for an email template
            if (method_exists($object, 'getEmailClass')) {
                $this->setEmailClass($object->getEmailClass());
            }
            try {
                $this
                    ->setObject(serialize($object))
                    ->setAttempts($attempts)
                    ->setLastAttempt(Mage::getSingleton('core/date')->gmtDate())
                    ->save();
                Mage::helper('bronto_common')->writeDebug('Storing failed delivery.');
                return $this->getId();
            } catch (Exception $e) {
                Mage::helper('bronto_common')->writeError('Failed to store delivery: ' . $e->getMessage());
                return false;
            }
        }
    }

    /**
     * Restores the serialized object to its former glory
     *
     * @return Bronto_Api_Delivery
     */
    public function restoreObject()
    {
        $delivery = unserialize($this->getObject());
        // Restore the API in all of its glory
        $delivery->setApi($this->_client($delivery->getApi()->getToken()));
        return $delivery;
    }

    /**
     * @see parent
     *
     * @param int $identifier
     * @return bool
     */
    public function attempt($identifier)
    {
        $delivery = $this->restoreObject();
        $method = $delivery->getLastRequestMethod();
        $data = $delivery->getLastRequestData();

        try {
            $rowset = $delivery->doRequest($method, $data, true);
            $this->delete();
            if ($this->hasEmailClass()) {
                // Tie in the request data with a new id
                $deliveryRow = $delivery->createRow(array('id' => $rowset->current()->id) + $data[0]);
                $email = Mage::getModel($this->getEmailClass());
                $email->triggerBeforeAfterSend($deliveryRow);
            }
        } catch (Exception $e) {
            $this->store($delivery, $this->getAttempts() + 1);
            return false;
        }

        return true;
    }
}
