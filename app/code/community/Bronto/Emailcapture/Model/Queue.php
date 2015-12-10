<?php

/**
 * @package   Bronto\Emailcapture
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Emailcapture_Model_Queue extends Mage_Core_Model_Abstract
{
    protected $_tid;
    protected $_queueId;
    protected $_cookie;
    protected $_cookieName = 'bmec';

    public function _construct()
    {
        parent::_construct();
        $this->_init('bronto_emailcapture/queue');

        $this->_cookie = Mage::getModel('core/cookie');
    }

    /**
     * Set Cookie Object to be used
     *
     * @param Mage_Core_Model_Cookie $cookie
     *
     * @return $this
     */
    public function setCookie(Mage_Core_Model_Cookie $cookie)
    {
        $this->_cookie = $cookie;

        return $this;
    }

    /**
     * Get Cookie to use
     *
     * @return mixed
     */
    public function getCookie()
    {
        return $this->_cookie;
    }

    /**
     * Build QueueId
     *
     * @return string
     */
    protected function _getQueueId()
    {
        if (!$this->_queueId) {
            $this->_queueId = $this->_getTid() . '_' . Mage::app()->getStore()->getStoreId();
        }

        return $this->_queueId;
    }

    /**
     * Get TID from cookie and update cookie
     *
     * @return string
     */
    protected function _getTid()
    {
        if (!$this->_tid) {
            // Get TID and Ensure cookie has current tid
            $this->_tid = $this->getCookie()->get($this->_cookieName, false);

            if (!$this->_tid) {
                $this->_tid = md5(time() . mt_rand(1, 1000000));
                Mage::getModel('core/cookie')->set($this->_cookieName, $this->_tid, Mage::helper('bronto_emailcapture')->getCookieTtl());
            }
        }

        return $this->_tid;
    }

    /**
     * Public call to validate the email
     *
     * @param string $email
     * @return bool
     */
    public function isValidEmail($email)
    {
        try {
            return (bool) Zend_Validate::is($email, 'EmailAddress');
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Update Email Address based on TID and current Store ID
     *
     * @param $email
     *
     * @return $this
     */
    public function updateEmail($email)
    {
        // Validate Email Address
        if ($this->isValidEmail($email)) {
          // Build Collection Select
          $this->load($this->_getQueueId())
              ->setId($this->_getQueueId())
              ->setEmailAddress($email)
              ->setUpdatedAt(Mage::getSingleton('core/date')->gmtDate())
              ->save();
        }

        return $this;
    }

    /**
     * Get Email Address for Current TID and Store ID
     *
     * @return Mage_Core_Model_Abstract
     */
    public function getCurrentEmail()
    {
        return $this->load($this->_getQueueId())->getEmailAddress();
    }

    /**
     * Flush all items from Queue that are older than the ttl of the cookie
     */
    public function flushQueue()
    {
        $ttl = Mage::helper('bronto_emailcapture')->getCookieTtl(false);

        $where = array('updated_at < ?' => date(strtotime('-' . $ttl . 'days')));
        $resource = Mage::getSingleton('core/resource');
        $this->_getResource()->getWriteAdapter()->delete($resource->getTableName('bronto_emailcapture/queue'), $where);
    }
}
