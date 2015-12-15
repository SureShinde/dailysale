<?php

class Bronto_News_Model_Item extends Mage_Core_Model_Abstract
{

    const TYPE_GENERAL = 'general';
    const TYPE_RELEASE = 'releases';
    const TYPE_OTHER   = 'other';

    /**
     * @var Mage_Core_Helper_Abstract
     */
    protected $_helper;

    /**
     * Sets the helper to be used with config data
     *
     * @param Mage_Core_Helper_Abstract $helper
     *
     * @return Bronto_News_Model_Item
     */
    public function setHelper(Mage_Core_Helper_Abstract $helper)
    {
        $this->_helper = $helper;

        return $this;
    }

    /**
     * Gets the helper used for this model
     *
     * @return Mage_Core_Helper_Abstract
     */
    protected function _getHelper()
    {
        if (is_null($this->_helper)) {
            $this->setHelper(Mage::helper('bronto_news'));
        }

        return $this->_helper;
    }

    /**
     * @see parent
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('bronto_news/item');
    }

    /**
     * @see parent
     */
    public function afterCommitCallback()
    {
        parent::afterCommitCallback();
        if (!$this->getSilence()) {
            $this->_getHelper()->createAnnouncement($this);
        }

        return $this;
    }

    /**
     * Is this RSS item an alert item
     *
     * @return boolean
     */
    public function isAlert()
    {
        return (
            $this->getType() != self::TYPE_GENERAL ||
            preg_match('/^\[?ALERT\]?/i', $this->getTitle())
        );
    }

    /**
     * Returns the "Magento Alert title" for the given notification
     *
     * @return string
     */
    public function getAlertTitle()
    {
        return "[Bronto Alert] {$this->getTitle()}";
    }

    /**
     * Marks all alerts as read
     *
     * @return Bronto_News_Model_Item
     */
    public function markAlertAsRead()
    {
        $notification = Mage::getModel('adminnotification/inbox')
            ->load($this->getNotificationId(), 'notification_id');

        if ($notification->hasNotificationId()) {
            $notification->setIsRead(1)->save();
        }

        return $this;
    }

    /**
     * Returns a collection of RSS items based on type
     *
     * @param string $type
     *
     * @return Mage_Core_Model_Resource_Db_Collection_Abstract
     */
    public function getItemsOfType($type)
    {
        return $this
            ->getCollection()
            ->addFieldToFilter('type', $type)
            ->orderByMostRecent();
    }

    /**
     * Returns the most recent, limited release notes
     *
     * @return Mage_Core_Model_Resource_Db_Collection_Abstract
     */
    public function getLimitedReleaseNotes()
    {
        return $this
            ->getItemsOfType(self::TYPE_RELEASE)
            ->setPageSize($this->_getHelper()->getThreshold());
    }

    /**
     * Returns the most recent, limited general notes
     *
     * @return Mage_Core_Model_Resource_Db_Collection_Abstract
     */
    public function getLimitedGeneralNotes()
    {
        return $this
            ->getItemsOfType(self::TYPE_GENERAL)
            ->setPageSize($this->_getHelper()->getThreshold());
    }
}
