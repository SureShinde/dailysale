<?php

class Bronto_Common_Model_Queue extends Mage_Core_Model_Abstract
{
    protected $_eventPrefix = 'bronto_common_queue';

    /**
     * @var Bronto_Api
     */
    protected $_api;

    /**
     * @var Bronto_Api_Delivery_Object
     */
    protected $_deliveryObject;

    /**
     * @var array Bronto_Api_Contact_Row
     */
    protected $_contacts;

    /**
     * @var Bronto_Api_Message_Row
     */
    protected $_message;

    /**
     * @var array
     */
    protected $_unserializedData;

    /**
     * @see parent
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('bronto_common/queue');
    }

    /**
     * Flags the obtained the items for holding
     *
     * @param array $items
     * @return array
     */
    public function flagForHolding($items)
    {
        $ids = array();
        foreach ($items as $item) {
            $ids[] = $item->getId();
        }
        if (empty($ids)) {
            return array();
        }
        $this->_getResource()->flagForHolding($ids);
        return $ids;
    }

    /**
     * Sets the Bronto_Api
     *
     * @param Bronto_Api
     * @return Bronto_Common_Model_Queue
     */
    public function setApi($api)
    {
        $this->_api = $api;
        return $this;
    }

    /**
     * Gets the Bronto_Api for this queue
     *
     * @return Bronto_Api
     */
    protected function _api()
    {
        if (is_null($this->_api)) {
            $this->_api = Mage::helper('bronto_common')->getApi(null, 'store',  $this->getStoreId());
        }
        return $this->_api;
    }

    /**
     * Gets the delivery object for this delivery
     *
     * @return Bronto_Api_Delivery_Object
     */
    protected function _deliveryObject()
    {
        if (is_null($this->_deliveryObject)) {
            $this->_deliveryObject = Mage::getModel('bronto_common/delivery', array(
                'api' => $this->_api(),
                'email_class' => $this->getEmailClass()
            ));
        }
        return $this->_deliveryObject;
    }

    /**
     * Sets the unserialized data for emails
     *
     * @param array
     * @return Bronto_Common_Model_Queue
     */
    public function setUnserializedEmailData($data)
    {
        $this->_unserializedData = $data;
        return $this->setEmailData(serialize($data));
    }

    /**
     * Gets the unserialized data for emails
     *
     * @return Varien_Object
     */
    public function getUnserializedEmailData()
    {
        if (is_null($this->_unserializedData)) {
            $this->_unserializedData = unserialize($this->getEmailData());
        }
        return new Varien_Object($this->_unserializedData);
    }

    /**
     * Gets the contacts to send the delivery
     *
     * @return array Bronto_Api_Contact_Row
     */
    public function getContacts()
    {
        if (is_null($this->_contacts)) {
            $emails = $this->getUnserializedEmailData()->getEmails();
            $contactHelper = Mage::helper('bronto_common/contact');
            $this->_contacts = $contactHelper->getContactsByEmail($emails, null, $this->getStoreId(), true);
        }
        return $this->_contacts;
    }

    /**
     * Gets the specialized data
     *
     * @return Varien_Object
     */
    public function getAdditionalData()
    {
        return new Varien_Object($this->getUnserializedEmailData()->getParams());
    }

    /**
     * Gets the Bronto Message associated with this delivery
     *
     * @return Bronto_Api_Message_Row
     */
    public function getMessage()
    {
        if (is_null($this->_message)) {
            $deliveryData = $this->getUnserializedEmailData()->getDelivery();
            $this->_message = Mage::helper('bronto_common/message')->getMessageById($deliveryData['messageId']);
        }
        return $this->_message;
    }

    /**
     * Gets the Recommendation info supplied by the email model
     *
     * @return Varien_Object
     */
    public function getRecommendationInfo()
    {
        return new Varien_Object($this->getUnserializedEmailData()->getRecommendation());
    }

    /**
     * Creates a Bronto_Api_Delivery_Row from internals
     *
     * @param array $additionalFields
     * @return Bronto_Api_Delivery_Row
     */
    public function prepareDelivery($additionalFields = array())
    {
        $delivery = $this->_deliveryObject()->createRow();
        $deliveryData = $this->getUnserializedEmailData()->getDelivery();
        foreach ($deliveryData as $field => $value) {
            if ($field == 'fields' && !empty($additionalFields)) {
                $value = array_merge($value, $additionalFields);
            }
            $delivery->{$field} = $value;
        }
        return $delivery;
    }

    /**
     * Sends a Bronto Delivery
     *
     * @reutrn bool
     */
    public function send()
    {
        $template = Mage::getModel($this->getEmailClass());
        return $template->sendDeliveryFromQueue($this);
    }
}
