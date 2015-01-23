<?php

/**
 * @package   Bronto\Common
 * @copyright 2011-2012 Bronto Software, Inc.
 */
class Bronto_Common_Model_System_Config_Backend_Token extends Mage_Core_Model_Config_Data
{

    protected $_eventPrefix = 'bronto_token_model';

    /**
     * @return Bronto_Common_Model_System_Config_Backend_Token
     */
    protected function _beforeSave()
    {
        $commonHelper = Mage::helper('bronto_common');
        $value        = $this->getValue();
        if (!empty($value)) {
            if ($commonHelper->validApiToken($value) === false) {
                // reset the verified status
                Mage::helper('bronto_verify/apitoken')->setStatus(
                    Mage::helper('bronto_verify/apitoken')->getPath('token_status'),
                    '2',
                    $this->getScope(),
                    $this->getScopeId()
                );

                Mage::throwException($commonHelper->__('The Bronto API Token you have entered appears to be invalid.'));
            }

            // reset the verified status
            Mage::helper('bronto_verify/apitoken')->setStatus(
                Mage::helper('bronto_verify/apitoken')->getPath('token_status'),
                '1',
                $this->getScope(),
                $this->getScopeId()
            );

            // Enable Common Module
            Mage::getModel('core/config_data')
                ->load(Bronto_Common_Helper_Data::XML_PATH_ENABLED, 'path')
                ->setValue(1)
                ->setPath(Bronto_Common_Helper_Data::XML_PATH_ENABLED)
                ->setScope($this->getScope())
                ->setScopeId($this->getScopeId())
                ->save();

            //  API key is new and doesn't match existing API key
            $currentApiKey = $commonHelper->getApiToken();
            if (!empty($currentApiKey) && $currentApiKey !== $value) {
                Mage::getSingleton('adminhtml/session')->addNotice($commonHelper->__(
                    'You have changed your Bronto API Token so all Bronto modules have been disabled for this configuration scope.' .
                    '<br />Please proceed to each module and reconfigure all available options to avoid undesired behavior.'
                ));

                $this->_disableAndUnlink();
            }
        } else {
            Mage::getSingleton('adminhtml/session')->addNotice($commonHelper->__(
                'You have removed your Bronto API Token so all Bronto modules have been disabled for this configuration scope.'
            ));

            // reset the verified status
            Mage::helper('bronto_verify/apitoken')->setStatus(
                Mage::helper('bronto_verify/apitoken')->getPath('token_status'),
                '0',
                $this->getScope(),
                $this->getScopeId()
            );

            $this->_disableAndUnlink(true);
        }

        return parent::_beforeSave();
    }

    /**
     * Reset Roundtrip verification status, disable all modules, and unlink all Bronto Transactional Emails
     *
     * @param bool $includeCommon
     */
    protected function _disableAndUnlink($includeCommon = false)
    {
        $sentry = Mage::getModel('bronto_common/keysentry');
        $sentry->disableModules($this->getScope(), $this->getScopeId(), $includeCommon);

        if (!Mage::helper('bronto_common')->isVersionMatch(Mage::getVersionInfo(), 1, array(array('edition' => 'Professional', 'major' => 9)))) {
            $sentry->unlinkEmails(
                Mage::getModel('bronto_email/message')->getCollection(),
                $this->getScope(),
                $this->getScopeId()
            );
        }
    }
}
