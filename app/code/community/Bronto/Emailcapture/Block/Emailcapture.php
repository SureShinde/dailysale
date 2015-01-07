<?php

/**
 * @package    Bronto/Emailcapture
 * @copyright  2011-2013 Bronto Software, Inc.
 */
class Bronto_Emailcapture_Block_Emailcapture extends Mage_Core_Block_Template
{
    /**
     * Get URL to post AJAX calls to
     *
     * @return string url to controller action for handling email capture
     */
    public function getTargetUrl()
    {
        $secure = Mage::app()->getFrontController()->getRequest()->isSecure();
        return Mage::getUrl('emailcapture/index/capture', array('_secure' => $secure));
    }

    /**
     * Check if module is enabled
     *
     * @param string $scope
     * @param int    $scopeId
     *
     * @return bool
     */
    public function isEnabled($scope = 'default', $scopeId = 0)
    {
        return Mage::helper('bronto_emailcapture')->isEnabled($scope, $scopeId);
    }

    /**
     * Get CSS Selector for Email Capture Fields
     *
     * @return mixed
     */
    public function getFieldSelector()
    {
        return Mage::helper('bronto_emailcapture')->getFieldSelector();
    }
}
