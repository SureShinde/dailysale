<?php

class Bronto_Common_Model_Error extends Mage_Core_Model_Abstract implements Bronto_Api_Retryer
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
     * @see parent
     *
     * @param Bronto_Object $object
     * @param string $apiToken
     * @param int $attempts
     * @return int|false
     */
    public function store(Bronto_Object $object, $apiToken, $attempts = 0)
    {
        // Only deliveries are retried
        if ($object->getTransferType() == 'Delivery') {
            if ($this->hasId() && empty($attempts)) {
                $this->unsId();
            }
            try {
                $this
                    ->setObject(serialize($object->withToken($apiToken)))
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
     * @see parent
     *
     * @param int $identifier
     * @return bool
     */
    public function attempt($identifier)
    {
        $request = unserialize($this->getObject());
        $api = new Bronto_Api($request->getToken());

        try {
            $this->delete();
            $deliveryOps = $api->transferDelivery();
            foreach ($deliveryOps->createWritePager($request) as $result) {
                $delivery = $result->getOriginal();
                $item = $result->getItem();
                if ($item->getIsError()) {
                    Mage::throwException("Failed to send failed delivery {$item->getErrorString()}");
                }
                if ($delivery->hasEmailClass()) {
                    $delivery->withId($item->getId());
                    $email = Mage::getModel($delivery->getEmailClass());
                    $email->triggerBeforeAfterSend($deliveryOps, $delivery);
                }
            }
        } catch (Exception $e) {
            $this->store($delivery, $request->getToken(), $this->getAttempts() + 1);
            return false;
        }

        return true;
    }
}
