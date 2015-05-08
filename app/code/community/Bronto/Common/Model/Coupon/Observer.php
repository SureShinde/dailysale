<?php

class Bronto_Common_Model_Coupon_Observer
{
    const TARGET_AREA = 'frontend';

    protected $_helper;

    /**
     * Simple constructor override for helpers
     */
    public function __construct()
    {
        $this->_helper = Mage::helper('bronto_common/coupon');
    }

    /**
     * Is the observer applicable in this case?
     *
     * @param Mage_Core_Controller_Front_Action $action
     * @return boolean
     */
    protected function _isApplicable($action)
    {
        return (
            $action->getLayout()->getArea() == self::TARGET_AREA &&
            $this->_helper->isEnabled() &&
            $this->_helper->isObservingController()
        );
    }

    /**
     * Sets the coupon code on the quote or on the session
     *
     * @param Varien_Event_Observer $observer
     */
    public function addCodeToSession($observer)
    {
        $action = $observer->getControllerAction();
        if ($this->_isApplicable($action)) {
            $this->_helper->applyCodeFromRequest($action->getRequest());
        }
        return false;
    }

    /**
     * Sets the coupon code on the quote
     *
     * @param Varien_Event_Observer $observer
     */
    public function addCodeToQuote($observer)
    {
        $this->_helper->applyCode();
    }
}
