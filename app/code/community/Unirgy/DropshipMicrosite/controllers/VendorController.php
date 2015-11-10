<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    Unirgy_DropshipMicrosite
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

class Unirgy_DropshipMicrosite_VendorController extends Mage_Core_Controller_Front_Action
{
    protected $_loginFormChecked = false;

    protected function _setTheme()
    {
        $theme = explode('/', Mage::getStoreConfig('udropship/vendor/interface_theme'));
        if (empty($theme[0]) || empty($theme[1])) {
            $theme = 'default/default';
        }
        Mage::getDesign()->setPackageName($theme[0])->setTheme($theme[1]);
    }

    protected function _renderPage($handles=null, $active=null)
    {
        $this->_setTheme();
        $this->loadLayout($handles);
        if (($root = $this->getLayout()->getBlock('root'))) {
            $root->addBodyClass('udropship-vendor');
        }
        if ($active && ($head = $this->getLayout()->getBlock('header'))) {
            $head->setActivePage($active);
        }
        $this->_initLayoutMessages('udropship/session');
        if (Mage::helper('udropship')->isModuleActive('Unirgy_DropshipVendorMembership')) {
            $this->_initLayoutMessages('udmember/session');
        }
        $this->renderLayout();
    }

    public function registerAction()
    {
        Mage::getSingleton('customer/session')->setData('umicrosite_registration_form_show_captcha',1);
        $this->_renderPage(null, 'register');
    }

    public function registerPostAction()
    {
        $session = Mage::getSingleton('udropship/session');
        $hlp = Mage::helper('umicrosite');
        try {
            $data = $this->getRequest()->getParams();
            $session->setRegistrationFormData($data);
            $this->checkCaptcha();
            $reg = Mage::getModel('umicrosite/registration')
                ->setData($data)
                ->validate()
                ->save();
            if (!Mage::getStoreConfig('udropship/microsite/auto_approve')) {
                $hlp->sendVendorSignupEmail($reg);
            }
            $hlp->sendVendorRegistration($reg);
            $session->unsRegistrationFormData();
            if (Mage::getStoreConfig('udropship/microsite/auto_approve')) {
                $vendor = $reg->toVendor();
                $vendor->setStatus(Unirgy_Dropship_Model_Source::VENDOR_STATUS_INACTIVE);
                if (Mage::getStoreConfig('udropship/microsite/auto_approve')==Unirgy_DropshipMicrosite_Model_Source::AUTO_APPROVE_YES_ACTIVE
                ) {
                    $vendor->setStatus(Unirgy_Dropship_Model_Source::VENDOR_STATUS_ACTIVE);
                }
                $_FILES = array();
                if (!Mage::getStoreConfigFlag('udropship/microsite/skip_confirmation')) {
                    $vendor->setSendConfirmationEmail(1);
                    $vendor->save();
                    $session->addSuccess(Mage::helper('udropship')->__('Thank you for application. Instructions were sent to your email to confirm it'));
                } else {
                    $vendor->save();
                    Mage::getSingleton('udropship/session')->loginById($vendor->getId());
                    if (!$this->_getVendorSession()->getBeforeAuthUrl()) {
                        $this->_getVendorSession()->setBeforeAuthUrl(Mage::getUrl('udropship'));
                    }
                }
            } else {
                $session->addSuccess(Mage::helper('udropship')->__('Thank you for application. As soon as your registration has been verified, you will receive an email confirmation'));
            }
        } catch (Exception $e) {
            $session->addError($e->getMessage());
            if ($this->getRequest()->getParam('quick')) {
                $this->_redirect('udropship/vendor/login');
            } else {
                $this->_redirect('*/*/register');
            }
            return;
        }
        $this->_loginPostRedirect();
    }

    public function registerSuccessAction()
    {
        $this->_renderPage(null, 'register');
    }

    protected function _loginPostRedirect()
    {
        $this->_getVendorSession()->loginPostRedirect($this);
    }
    protected function _getVendorSession()
    {
        return Mage::getSingleton('udropship/session');
    }

    public function checkCaptcha()
    {
        if (!Mage::helper('udropship')->isModuleActive('Mage_Captcha')) return $this;
        Mage::getSingleton('customer/session')->setData('umicrosite_registration_form_show_captcha',1);
        $formId = 'umicrosite_registration_form';
        $captchaModel = Mage::helper('captcha')->getCaptcha($formId);
        if ($captchaModel->isRequired()) {
            if (!$captchaModel->isCorrect($this->_getCaptchaString($this->getRequest(), $formId))) {
                Mage::throwException(Mage::helper('udropship')->__('Incorrect CAPTCHA.'));
            }
        }
        return $this;
    }

    protected function _getCaptchaString($request, $formId)
    {
        $captchaParams = $request->getPost(Mage_Captcha_Helper_Data::INPUT_NAME_FIELD_VALUE);
        return $captchaParams[$formId];
    }
}