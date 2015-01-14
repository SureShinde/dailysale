<?php

class Bronto_News_Adminhtml_AnnouncementController extends Mage_Adminhtml_Controller_Action
{

    /**
     * @var Mage_Core_Helper_Abstract
     */
    protected $_helper;

    /**
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
     * @param Mage_Core_Helper_Abstract $helper
     *
     * @return $this
     */
    public function setHelper(Mage_Core_Helper_Abstract $helper)
    {
        $this->_helper = $helper;

        return $this;
    }

    /**
     * Refresh Feeds and send back to requestUri
     * Example: admin/announcement/refresh
     */
    public function refreshAction()
    {
        $helper  = $this->_getHelper();
        $session = Mage::getSingleton('adminhtml/session');

        try {
            $helper->pullNewItems();
            $session->addSuccess('Successfully refreshed Announcements & News.');
        } catch (Exception $e) {
            $helper->writeError($e->getMessage());
            $helper->writeError($e->getTraceAsString());
            $session->addError('Failed to refresh Announcements & News.');
        }

        $this->_redirectReferer();
    }
}
