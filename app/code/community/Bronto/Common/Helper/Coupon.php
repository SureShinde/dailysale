<?php

class Bronto_Common_Helper_Coupon extends Bronto_Common_Helper_Data
{

    const XML_PATH_ENABLED         = 'bronto_coupon/apply_coupon/enabled';
    const XML_PATH_SUCCESS_MESSAGE = 'bronto_coupon/apply_coupon/success_message';
    const XML_PATH_COUPON_PARAM    = 'bronto_coupon/apply_coupon/coupon_code_param';
    const XML_PATH_ERROR_PARAM     = 'bronto_coupon/apply_coupon/error_message_param';
    const XML_PATH_LINK_MESSAGE    = 'bronto_coupon/apply_coupon/link';
    const XML_PATH_USES_OBSERVER   = 'bronto_coupon/apply_coupon/use_observer';

    const INVALID_CODE  = 'invalid';
    const DEPLETED_CODE = 'depleted';
    const EXPIRED_CODE  = 'expired';
    const CONFLICT_CODE = 'conflict';
    const FORCE_PARAM   = '___force_code';

    protected $_validCodes = array(
        self::INVALID_CODE  => 'translateCode',
        self::EXPIRED_CODE  => 'translateCode',
        self::DEPLETED_CODE => 'translateCode',
        self::CONFLICT_CODE => 'translateConflict'
    );

    /**
     * Gets the display name for the coupon module
     *
     * @return string
     */
    public function getName()
    {
        return $this->__('Bronto Coupon Management');
    }

    /**
     * Is this module enabled
     *
     * @param string $scope
     * @param int $scopeId
     * @return boolean
     */
    public function isEnabled($scope = 'default', $scopeId = 0)
    {
        return (bool)$this->getAdminScopedConfig(self::XML_PATH_ENABLED, $scope, $scopeId);
    }

    /**
     * Disable Module for Specified Scope
     *
     * @param string $scope
     * @param int $scopeId
     * @param bool $deleteConfig
     *
     * @return bool
     */
    public function disableModule($scope = 'default', $scopeId = 0, $deleteConfig = false)
    {
        return $this->_disableModule(self::XML_PATH_ENABLED, $scope, $scopeId, $deleteConfig);
    }

    /**
     * Determines if the supplied code is a valid one
     *
     * @param string $code
     * @return boolean
     */
    public function isValidErrorCode($code)
    {
        return array_key_exists($code, $this->_validCodes);
    }

    /**
     * Gets the coupon code param used to parse out of the URL
     *
     * @param string $scope
     * @param int $scopeId
     * @return string
     */
    public function getCouponParam($scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(self::XML_PATH_COUPON_PARAM, $scope, $scopeId);
    }

    /**
     * Gets the error code param used to parse out of the URL
     *
     * @param string $scope
     * @param int $scopeId
     * @return string
     */
    public function getErrorCodeParam($scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(self::XML_PATH_ERROR_PARAM, $scope, $scopeId);
    }

    /**
     * Gets both params used in the settings
     *
     * @param string $scope
     * @param int $scopeId
     * @return array
     */
    public function getParams($scope = 'default', $scopeId = 0)
    {
        return array(
            $this->getCouponParam($scope, $scopeId),
            $this->getErrorCodeParam($scope, $scopeId)
        );
    }

    /**
     * Gets the error code message from the configuration scope
     *
     * @param string $errorCode
     * @param string $couponCode
     * @param string $scope
     * @param int $scopeId
     * @return string
     */
    public function getErrorMessage($errorCode, $couponCode, $scope = 'default', $scopeId = 0)
    {
        $baseMessage = $this->getAdminScopedConfig('bronto_coupon/apply_coupon/' . $errorCode, $scope, $scopeId);
        $translateCallback = $this->_validCodes[$errorCode];
        return $this->$translateCallback($baseMessage, empty($couponCode) ? 'code' : $couponCode);
    }

    /**
     * Gets the the success message
     *
     * @param string $couponCode
     * @param string $scope
     * @param int $scopeId
     * @return string
     */
    public function getSuccessMessage($couponCode, $scope = 'default', $scopeId = 0)
    {
        return $this->translateCode($this->getAdminScopedConfig(self::XML_PATH_SUCCESS_MESSAGE, $scope, $scopeId), $couponCode);
    }

    /**
     * Gets the link message content
     *
     * @param string $scope
     * @param int $scopeId
     * @return string
     */
    public function getLinkContent($scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(self::XML_PATH_LINK_MESSAGE, $scope, $scopeId);
    }

    /**
     * Uses a controller observer
     *
     * @param string $scope
     * @param int $scopeId
     * @return boolean
     */
    public function isObservingController($scope = 'default', $scopeId = 0)
    {
        return (bool)$this->getAdminScopedConfig(self::XML_PATH_USES_OBSERVER, $scope, $scopeId);
    }

    /**
     * Translate a message using the coupon code
     *
     * @param string $message
     * @param string $couponCode
     * @param string $key
     * @return string
     */
    public function translateCode($message, $couponCode, $key = 'code')
    {
        return str_replace('{' . $key . '}', $couponCode, $message);
    }

    /**
     * Translate the conflict message
     *
     * @param string $message
     * @param string $couponCode
     * @return string
     */
    public function translateConflict($message, $couponCode)
    {
        $couponParam = $this->getCouponParam();
        $forceUrl = Mage::app()->getStore()->getUrl('*/*/*', array(
            $couponParam => $couponCode,
            self::FORCE_PARAM => 1,
        ));
        $quote = Mage::getSingleton('checkout/cart')->getQuote();
        $linkContent = $this->getLinkContent();
        $replacements = array(
            'link' => '<a href="' . $forceUrl . '">' . $linkContent . '</a>',
            'oldCode' => $quote->getCouponCode(),
            'newCode' => $couponCode,
        );
        foreach ($replacements as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }
        return $message;
    }

    /**
     * Sets the coupon code either in the session or on the quote
     *
     * @param string $couponCode
     */
    public function applyCode($couponCode = null)
    {
        $session = Mage::getSingleton('core/session');
        if (is_null($couponCode)) {
            $couponCode = $session->getCouponCode();
        } else {
            $session->setCouponCode($couponCode);
        }
        $quote = Mage::getSingleton('checkout/cart')->getQuote();
        if ($quote && $couponCode) {
            $quote->setCouponCode($couponCode)->save();
            $session->unsCouponCode($couponCode);
        }
    }

    /**
     * Validates the coupon code given certain high level constraints
     *
     * @param string $couponCode
     * @param boolean $force
     * @return Mage_Salesrule_Model_Coupon
     */
    protected function _validateCode($couponCode, $force = false)
    {
        $websiteId = Mage::app()->getWebsite()->getId();
        $customerGroupId = Mage::getSingleton('customer/session')->getCustomerGroupId();
        $rules = Mage::getModel('salesrule/rule')
            ->getCollection()
            ->setValidationFilter($websiteId, $customerGroupId, $couponCode)
            ->addFieldToFilter('main_table.coupon_type', array('in' => array(Mage_SalesRule_Model_Rule::COUPON_TYPE_SPECIFIC, Mage_SalesRule_Model_Rule::COUPON_TYPE_AUTO)));
        foreach ($rules as $rule) {
            $coupon = Mage::getModel('salesrule/coupon')->loadByCode($couponCode);
            if ($coupon->getUsageLimit() && $coupon->getTimesUsed() >= $coupon->getUsageLimit()) {
                Mage::throwException('depleted');
            }
            $quote = Mage::getSingleton('checkout/cart')->getQuote();
            if ($quote) {
                if (!$force && $quote->getCouponCode() && $quote->getCouponCode() != $couponCode) {
                    Mage::throwException('conflict');
                }
            }
            return $coupon;
        }
        Mage::throwException('invalid');
    }

    /**
     * Takes in an HTTP request and applies the code
     *
     * @param Mage_Core_Controller_Request_Http $request
     * @return boolean
     */
    public function applyCodeFromRequest($request)
    {
        list($couponParam, $errorParam) = $this->getParams();
        $session     = Mage::getSingleton('core/session');
        $errorCode   = $request->getParam($errorParam, null);
        $couponCode  = $request->getParam($couponParam, null);
        if ($errorCode || $couponCode) {
            if (!empty($couponCode)) {
                $force = $request->has(self::FORCE_PARAM);
                try {
                    $coupon = $this->_validateCode($couponCode, $force);
                    if (!$this->isCouponApplied($couponCode)) {
                        $this->applyCode($couponCode);
                        $session->addSuccess($this->getSuccessMessage($couponCode));
                    }
                    return true;
                } catch (Exception $e) {
                    $errorCode = $e->getMessage();
                }
            }
            if (!$this->isValidErrorCode($errorCode)) {
                $errorCode = self::INVALID_CODE;
            }
            $session->addError($this->getErrorMessage($errorCode, $couponCode));
        }
        return false;
    }

    /**
     * Has this coupon already been applied
     *
     * @param string $couponCode
     * @return boolean
     */
    public function isCouponApplied($couponCode)
    {
        $session = Mage::getSingleton('core/session');
        $quote = Mage::getSingleton('checkout/cart')->getQuote();
        if ($session->getCouponCode() == $couponCode) {
            return true;
        }
        if ($quote) {
            return $quote->getCouponCode() && $quote->getCouponCode() == $couponCode;
        }
        return false;
    }
}
