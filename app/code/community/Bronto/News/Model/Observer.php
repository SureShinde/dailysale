<?php

class Bronto_News_Model_Observer
{

    /**
     * @var Mage_Core_Helper_Abstract
     */
    protected $_helper;

    /**
     * Sets the helper to be used with this observer
     *
     * @param Mage_Core_Helper_Abstract $helper
     *
     * @return Bronto_News_Model_Observer
     */
    public function setHelper(Mage_Core_Helper_Abstract $helper)
    {
        $this->_helper = $helper;

        return $this;
    }

    /**
     * Gets the helper cached on this request
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
     * Forwards call to helper class
     */
    public function pullNewItems()
    {
        $helper = $this->_getHelper();

        if ($helper->validApiToken()) {
            try {
                $helper->pullNewItems();
            } catch (Exception $e) {
                $helper->writeError('Failed pulling items.');
            }
        }
    }
}
