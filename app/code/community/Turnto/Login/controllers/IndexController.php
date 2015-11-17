<?php
class Turnto_Login_IndexController extends Mage_Core_Controller_Front_Action {
	public function indexAction()
        {
                $this->loadLayout();
                
		$this->renderLayout();
        }

        public function regAction()
        {
                $this->loadLayout();

                $this->renderLayout();
        }


	public function successAction()
        {
        	echo '<html><head></head><body><script type="text/javascript">parent.TurnTo.localAuthenticationComplete();</script><h3>Loading...</h3></body></html>';
	}



   /**
     * Retrieve customer session model object
     *
     * @return Mage_Customer_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('customer/session');
    }


   /**
     * Login post action
     */
    public function loginAction()
    {
        //$result["error"]=0;

        //$this->getResponse()->setHeader('Content-Type', 'application/json');

	if ($this->_getSession()->isLoggedIn()) {
             //$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
             $this->_redirectSuccess(Mage::getUrl('*/*/success', array('_secure'=>false)));
	     return;
        }
        $session = $this->_getSession();

        if ($this->getRequest()->isPost()) {
            $login = $this->getRequest()->getPost('login');
            if (!empty($login['username']) && !empty($login['password'])) {
                try {
                    $session->login($login['username'], $login['password']);
                    if ($session->getCustomer()->getIsJustConfirmed()) {
                        $this->_welcomeCustomer($session->getCustomer(), true);
                    }
                    $this->_redirectSuccess(Mage::getUrl('*/*/success', array('_secure'=>false)));
		    return;
                } catch (Mage_Core_Exception $e) {
                    switch ($e->getCode()) {
                        case Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED:
                            //$result["error"] = 1;
                            $value = Mage::helper('customer')->getEmailConfirmationUrl($login['username']);
                            $message = Mage::helper('customer')->__('This account is not confirmed. <a href="%s">Click here</a> to resend confirmation email.', $value);
                            break;
                        case Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD:
                            //$result["error"] = 1;
                            $message = $e->getMessage();
                            break;
                        default:
                            //$result["error"] = 1;
			    $message = $e->getMessage();
                    }
                    $session->addError($message);
                    $session->setUsername($login['username']);
                } catch (Exception $e) {
                    // Mage::logException($e); // PA DSS violation: this exception log can disclose customer password
                }
            } else {
		//$result["error"] = 1;
                $session->addError($this->__('Login and password are required.'));
            }
        }

        //$this->_loginPostRedirect();
        //$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        $this->_redirectError(Mage::getUrl('*/*/index', array('_secure' => true)));
    }

    public function getUserStatusAction()
    {
       $session = Mage::getSingleton('customer/session');
       $this->getResponse()->setHeader('Content-Type', 'application/json');
       if($session->isLoggedIn()) {
            $customer = $session->getCustomer();
            $result = array();

            $result['user_auth_token'] = $customer->getEntityId();
            $result['first_name'] = $customer->getFirstname();
            $result['last_name'] = $customer->getLastname();
            $result['email'] = $customer->getEmail();
            $result['email_confirmed'] = true;
            $result['nickname'] = null;
       } else {
           $result['error'] = "User is logged out";
       }

       $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
   }

   /**
     * Customer logout action
     */
    public function logoutAction()
    {
        $this->_getSession()->logout()->setBeforeAuthUrl(Mage::getUrl());
    }

    public function registerAction()
    {
        $session = $this->_getSession();
        if ($session->isLoggedIn()) {
            $this->_redirect('*/*/');
            return;
        }
        $session->setEscapeMessages(true); // prevent XSS injection in user input
        if ($this->getRequest()->isPost()) {
            $errors = array();

            if (!$customer = Mage::registry('current_customer')) {
                $customer = Mage::getModel('customer/customer')->setId(null);
            }

            /* @var $customerForm Mage_Customer_Model_Form */
            $customerForm = Mage::getModel('customer/form');
            $customerForm->setFormCode('customer_account_create')
                ->setEntity($customer);

            $customerData = $customerForm->extractData($this->getRequest());

            if ($this->getRequest()->getParam('is_subscribed', false)) {
                $customer->setIsSubscribed(1);
            }

            /**
             * Initialize customer group id
             */
            $customer->getGroupId();

            if ($this->getRequest()->getPost('create_address')) {
                /* @var $address Mage_Customer_Model_Address */
                $address = Mage::getModel('customer/address');
                /* @var $addressForm Mage_Customer_Model_Form */
                $addressForm = Mage::getModel('customer/form');
                $addressForm->setFormCode('customer_register_address')
                    ->setEntity($address);

                $addressData    = $addressForm->extractData($this->getRequest(), 'address', false);
                $addressErrors  = $addressForm->validateData($addressData);
                if ($addressErrors === true) {
                    $address->setId(null)
                        ->setIsDefaultBilling($this->getRequest()->getParam('default_billing', false))
                        ->setIsDefaultShipping($this->getRequest()->getParam('default_shipping', false));
                    $addressForm->compactData($addressData);
                    $customer->addAddress($address);

                    $addressErrors = $address->validate();
                    if (is_array($addressErrors)) {
                        $errors = array_merge($errors, $addressErrors);
                    }
                } else {
                    $errors = array_merge($errors, $addressErrors);
                }
            }

            try {
                $customerErrors = $customerForm->validateData($customerData);
                if ($customerErrors !== true) {
		    $errors = array_merge($customerErrors, $errors);
                } else {
                    $customerForm->compactData($customerData);
                    $customer->setPassword($this->getRequest()->getPost('password'));
                    $customer->setConfirmation($this->getRequest()->getPost('confirmation'));
                    $customerErrors = $customer->validate();
                    if (is_array($customerErrors)) {
                        $errors = array_merge($customerErrors, $errors);
                    }
                }

                $validationResult = count($errors) == 0;

                if (true === $validationResult) {
                    $customer->save();

                    if ($customer->isConfirmationRequired()) {
                        $customer->sendNewAccountEmail('confirmation', $session->getBeforeAuthUrl());
                        $session->addSuccess($this->__('Account confirmation is required. Please, check your email for the confirmation link. To resend the confirmation email please <a href="%s">click here</a>.', Mage::helper('customer')->getEmailConfirmationUrl($customer->getEmail())));
                        $this->_redirectSuccess(Mage::getUrl('*/*/success', array('_secure'=>false)));
			//$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                        return;
                    } else {
                        $session->setCustomerAsLoggedIn($customer);
                        //$url = $this->_welcomeCustomer($customer);
                        //$this->_redirectSuccess($url);
                        $this->_redirectSuccess(Mage::getUrl('*/*/success', array('_secure'=>false)));
			//$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
			return;
                    }
                } else {
                    $session->setCustomerFormData($this->getRequest()->getPost());
                    if (is_array($errors)) {
                        foreach ($errors as $errorMessage) {
			   $session->addError($errorMessage);
                        }
                    } else {
                        $session->addError($this->__('Invalid customer data'));
                    }
                }
            } catch (Mage_Core_Exception $e) {
		$session->setCustomerFormData($this->getRequest()->getPost());
                if ($e->getCode() === Mage_Customer_Model_Customer::EXCEPTION_EMAIL_EXISTS) {
                    $url = Mage::getUrl('customer/account/forgotpassword');
                    $message = $this->__('There is already an account with this email address. If you are sure that it is your email address, <a href="%s">click here</a> to get your password and access your account.', $url);
                    $session->setEscapeMessages(false);
                } else {
                    $message = $e->getMessage();
                }
                $session->addError($message);
            } catch (Exception $e) {
                $session->setCustomerFormData($this->getRequest()->getPost())
                    ->addException($e, $this->__('Cannot save the customer.'));
            }
        }

	//$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        $this->_redirectError(Mage::getUrl('*/*/reg', array('_secure' => true)));
    }
}


