<?php

/**
 * @package   Newsletter
 * @copyright 2011-2012 Bronto Software, Inc.
 */
class Bronto_Newsletter_Block_Checkout_Onepage_Newsletter extends Mage_Checkout_Block_Onepage_Abstract
{
    private $_mode = 'loggedin';
    private $_show = true;
    private $_checked = false;

    /**
     * @return bool
     */
    public function isSubscribed()
    {
        return Mage::helper('bronto_newsletter')->isCustomerSubscribed($this->getCustomer());
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return Mage::helper('bronto_newsletter')->isEnabled();
    }

    /**
     * @return string
     */
    public function getCssSelector()
    {
        return Mage::helper('bronto_newsletter')->getCssSelector();
    }

    /**
     * @return string
     */
    public function getCheckboxStyle()
    {
        return Mage::helper('bronto_newsletter')->getCheckboxStyle();
    }

    /**
     * @return bool
     */
    public function isEnabledCheckedByDefault()
    {
        return Mage::helper('bronto_newsletter')->isEnabledCheckedByDefault();
    }

    /**
     * @return bool
     */
    public function isEnabledForGuestCheckout()
    {
        return Mage::helper('bronto_newsletter')->isEnabledForGuestCheckout();
    }

    /**
     * @return bool
     */
    public function isEnabledForRegisterCheckout()
    {
        return Mage::helper('bronto_newsletter')->isEnabledForRegisterCheckout();
    }

    /**
     * @return bool
     */
    public function isEnabledForLoggedinCheckout()
    {
        return Mage::helper('bronto_newsletter')->isEnabledForLoggedinCheckout();
    }

    /**
     * This allows checkbox field to pre-load
     *
     * @return boolean
     */
    public function isEnabledForLoadingCheckout()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isEnabledIfAlreadySubscribed()
    {
        return Mage::helper('bronto_newsletter')->isEnabledIfAlreadySubscribed();
    }

    /**
     * Get Url for Ajax call
     *
     * @return string
     */
    public function getRequestUrl()
    {
        $store = Mage::app()->getStore();
        return Mage::getSingleton('core/url')->getUrl('btnewsletter/index/checkbox', array('_secure' => $store->isCurrentlySecure()));
    }

    /**
     * Get Url for Updating Subscription Status
     *
     * @return string
     */
    public function getSubscribeUrl()
    {
        $store = Mage::app()->getStore();
        return Mage::getSingleton('core/url')->getUrl('btnewsletter/index/subscribe', array('_secure' => $store->isCurrentlySecure()));
    }

    /**
     * Set checkout mode
     *
     * @param string $mode 'guest', 'register', 'loggedin'
     *
     * @return Bronto_Newsletter_Block_Checkout_Onepage_Newsletter
     */
    public function setMode($mode)
    {
        if ($this->isCustomerLoggedIn()) {
            $mode = 'loggedin';
        } elseif (!in_array($mode, array('loggedin', 'guest', 'register'))) {
            $mode = 'loading';
        }

        $this->_mode = $mode;

        return $this;
    }

    /**
     * Get value of Checked parameter
     *
     * @param bool $asInt
     *
     * @return bool|int
     */
    public function getChecked($asInt = false)
    {
        if ($asInt) {
            return ($this->_checked) ? 1 : 0;
        }

        return $this->_checked;
    }

    /**
     * Get Checkbox Checked status
     *
     * @return string
     */
    public function getCheckboxChecked()
    {
        return ($this->_checked) ? ' checked="checked"' : '';
    }

    /**
     * Get Checkbox visibility
     *
     * @return string
     */
    public function getCheckboxShow()
    {
        return ($this->_show) ? '' : ' style="display:none;"';
    }

    /**
     * Get Checkbox Checked value
     *
     * @return string
     */
    public function getCheckboxValue()
    {
        return ($this->_checked) ? '1' : '0';
    }

    /**
     * Get the text to display for the checkbox label
     *
     * @return bool
     */
    public function getCheckboxLabelText()
    {
        return Mage::helper('bronto_newsletter')->getCheckboxLabelText();
    }

    /**
     * Calculate checkbox display settings
     */
    protected function _setCheckboxStatus()
    {
        // If customer subscribed, or checked by default is enabled, set checked
        if ($this->isSubscribed() || $this->isEnabledCheckedByDefault()) {
            $this->_checked = true;

            // Set Initial subscription status to active
            Mage::getSingleton('checkout/session')
                ->setIsSubscribed(Bronto_Api_Model_Contact::STATUS_ACTIVE);
        } else {
            // Set Initial subscription status to transactional
            Mage::getSingleton('checkout/session')
                ->setIsSubscribed(Bronto_Api_Model_Contact::STATUS_TRANSACTIONAL);
        }

        // If module enabled and checkbox enabled for checkout method, show it
        $methodName = 'isEnabledFor' . ucfirst($this->_mode) . 'Checkout';
        if (!$this->isEnabled() || (!method_exists($this, $methodName) || !$this->$methodName())) {
            $this->_show = false;
        }

        // If customer subscribed, but checkbox not enabled if subscribed, hide
        if ($this->isSubscribed() && !$this->isEnabledIfAlreadySubscribed()) {
            $this->_show = false;
        }
    }
}
