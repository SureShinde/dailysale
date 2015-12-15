<?php
class IWD_Signin_JsonController extends Mage_Core_Controller_Front_Action
{

 	protected function _getSession()
    {
        return Mage::getSingleton('customer/session');
    }
    
    public function postDispatch()
    {
    	parent::postDispatch();
    	$this->_getSession()->unsNoReferer(false);
    }
	
	
	/** RENDER BLOCK BY NAME **/
	protected function _prepareBlock($blockName){
		
		$layout = $this->getLayout();
		$update = $layout->getUpdate();
		
		$this->_initLayoutMessages('customer/session');
		$this->_initLayoutMessages('catalog/session');
		
		$update->load('signin_customer_' . $blockName);
		$layout->generateXml();
		$layout->generateBlocks();
		$output = $layout->getOutput();
		
		return $output;
	}
	
	
	public function redirectAction(){
		$url = $this->getRequest()->getParam('url', false);
		Mage::getSingleton('core/session')->setSigninRedirect($url);
	} 
	
	
	/** RENDER LOGIN + REGISTRATION FORM **/
	public function loadAction(){
		$response = array('error'=>false);
		$blockName = $this->getRequest()->getParam('block', false);
		
		if (empty($blockName) || !$blockName){			
			$blockName = 'login';
		}
		
		$response = array('id'=>$blockName, $blockName=>$this->_prepareBlock($blockName));
		
			
		
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
	}
	
	
	/** CHECK LOGIN DATA FROM LOGIN FORM **/
	public function loginAction()
	{
		$response = array();
		$redirectUrl = Mage::getSingleton('core/session')->getSigninRedirect();
		if ($this->_getSession()->isLoggedIn()) {
			$response['redirect'] = Mage::getBaseUrl('link', Mage::app()->getStore()->isFrontUrlSecure());
			$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
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
						$response['linkAfterLogin'] = $redirectUrl;
						$response['message'] = $this->__('Thank you for registering with %s.', Mage::app()->getStore()->getFrontendName());
						
						$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
						return;
					}
					
					if (!Mage::getStoreConfigFlag(Mage_Customer_Helper_Data::XML_PATH_CUSTOMER_STARTUP_REDIRECT_TO_DASHBOARD)){
						$response['linkAfterLogin'] = $redirectUrl;
					}else{
						$response['linkAfterLogin'] = Mage::helper('customer')->getLoginUrl();
					}
					
					$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
					return;
					
				} catch (Mage_Core_Exception $e) {
					
					switch ($e->getCode()) {
						case Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED:
							$value = Mage::helper('customer')->getEmailConfirmationUrl($login['username']);
							$message = Mage::helper('customer')->__('This account is not confirmed. <a href="%s">Click here</a> to resend confirmation email.', $value);
							break;
						case Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD:
							$message = $e->getMessage();
							break;
						default:
							$message = $e->getMessage();
					}
					
					
					$response['error'] = 1;
					$response['message'] = $message;
					
					$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
					return;
					
				} catch (Exception $e) {
					// Mage::logException($e); // PA DSS violation: this exception log can disclose customer password
				}
				
				
			} else {
				

				$response['error'] = 1;
				$response['message'] = $this->__('Login and password are required.');
					
				$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
				return;
			}
		}
		
		$response['error'] = 1;
		$response['message'] = $this->__('Login and password are required.');
			
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
		return;
		
	}
	
	
	
	/**
	 * Create customer account action
	 */
	public function registerAction()
	{
		$response = array();
		
		$session = $this->_getSession();
		if ($session->isLoggedIn()) {
			
			$response['redirect'] = Mage::getBaseUrl('link', Mage::app()->getStore()->isFrontUrlSecure());
			$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
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
	
					Mage::dispatchEvent('customer_register_success',
											array('account_controller' => $this, 'customer' => $customer)
										);
	
					if ($customer->isConfirmationRequired()) {
						
						$customer->sendNewAccountEmail(
								'confirmation',
								$session->getBeforeAuthUrl(),
								Mage::app()->getStore()->getId()
						);
						
						$message = $this->__('Account confirmation is required. Please, check your email for the confirmation link. To resend the confirmation email please <a href="%s">click here</a>.', Mage::helper('customer')->getEmailConfirmationUrl($customer->getEmail()));
						$response['emailConfirmation'] = 1;
						
					} else {
						//if (!Mage::helper('signin')->isEnableCustomerActivation()){
							$session->setCustomerAsLoggedIn($customer);
						//}
						
						$this->_welcomeCustomer($customer);
						
						$message = $this->__('Thank you for registering with %s.', Mage::app()->getStore()->getFrontendName());
						$response['linkAfterLogin'] = Mage::getBaseUrl('link', true);;
					}
					
					//if (Mage::helper('signin')->isEnableCustomerActivation()){
					//	$response['message'] .=  '<br />';
					//	$response['message'] .= $this->__('');
					//}
					
					
					$response['message'] = $message;
					$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
					return;
				} else {
					
					$session->setCustomerFormData($this->getRequest()->getPost());
					if (is_array($errors)) {						
							$errors = implode('<br />', $errors);
						
					} else {
							$errors = $this->__('Invalid customer data');
					}
					

					$response['error'] = 1;
					$response['message'] = $errors;
					$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
					return;
					
				}
			} catch (Mage_Core_Exception $e) {
				
				$session->setCustomerFormData($this->getRequest()->getPost());
				
				if ($e->getCode() === Mage_Customer_Model_Customer::EXCEPTION_EMAIL_EXISTS) {
					$url = Mage::getUrl('customer/account/forgotpassword');
					$message = $this->__('There is already an account with this email address. If you are sure that it is your email address, <a href="%s" id="forgot-password">click here</a> to get your password and access your account.', $url);

				} else {
					$message = $e->getMessage();
				}
				
				$response['error'] = 1;
				$response['message'] = $message;
				$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
				return; 
			} catch (Exception $e) {
				
				$response['error'] = 1;
				$response['message'] = $this->__('Cannot save the customer.');
				$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
				return;
			}
		}
	
		return;
	}
	
	
	
	public function forgotpasswordAction(){
		$response = array();
		$email = (string) $this->getRequest()->getPost('email');
		
		if ($email) {
			if (!Zend_Validate::is($email, 'EmailAddress')) {
				$this->_getSession()->setForgottenEmail($email);
				
				$response['error'] = 1;
				$response['message'] = $this->__('Invalid email address.');
				$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
				return;
			}
		
			/** @var $customer Mage_Customer_Model_Customer */
			$customer = Mage::getModel('customer/customer')
					->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
					->loadByEmail($email);
		
			if ($customer->getId()) {
				try {
					$newResetPasswordLinkToken = Mage::helper('customer')->generateResetPasswordLinkToken();
					$customer->changeResetPasswordLinkToken($newResetPasswordLinkToken);
					$customer->sendPasswordResetConfirmationEmail();
				} catch (Exception $exception) {
					
					$response['error'] = 1;
					$response['message'] = $exception->getMessage();
					$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
				
					return;
				}
			}
			$this->_getSession()->addSuccess(Mage::helper('customer')->__('If there is an account associated with %s you will receive an email with a link to reset your password.', Mage::helper('customer')->htmlEscape($email)));
			$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
			return;
		} else {
			
			
			$response['error'] = 1;
			$response['message'] = $this->__('Please enter your email.');
			$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
			
			return;
		}
	}
	
	protected function _welcomeCustomer(Mage_Customer_Model_Customer $customer, $isJustConfirmed = false){
		
		$customer->sendNewAccountEmail($isJustConfirmed ? 'confirmed' : 'registered', '', Mage::app()->getStore()->getId());
		
	}
	
	
	public function facebookAction(){
		$redirectUrl = Mage::getSingleton('core/session')->getSigninRedirect();
		$session = $this->_getSession();
		if ($session->isLoggedIn()) {
		
			$response['redirect'] = Mage::getBaseUrl('link', Mage::app()->getStore()->isFrontUrlSecure());
			$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
			return;
		
		}
		
		$response = array();
		
		$params = $this->getRequest()->getParams();
					
		$userUId = Mage::getModel('signin/facebook')->getUser(); 
	
		if ($params['id']==$userUId && !empty($params['email'])){
			//login or register
			try{
				$customerSession = Mage::getSingleton('customer/session');
				$customerModel = Mage::getModel('customer/customer')->setWebsiteId(Mage::app()->getStore()->getWebsiteId());

				$customer = $customerModel->loadByEmail($params['email']);
				
				if ($customer->getConfirmation() && $customer->isConfirmationRequired()) {
					throw Mage::exception('Mage_Core', Mage::helper('customer')->__('This account is not confirmed.'),
							Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED
					);
				}
				
				
					
				if ($customer->getId()){
					//login
					$customerSession->setCustomerAsLoggedIn($customer);
					$customerSession->renewSession();
					$response['linkAfterLogin'] = $redirectUrl;
				}else{
					//register
					$response = $this->registerFromFacebook($redirectUrl);
				}
				
				
				
				
			}catch (Mage_Core_Exception $e) {
					
					 switch ($e->getCode()) {
                        case Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED:
                            $value = Mage::helper('customer')->getEmailConfirmationUrl($customer->getEmail());
                            $message =  Mage::helper('customer')->__('This account is not confirmed. <a href="%s">Click here</a> to resend confirmation email.', $value);
                            break;
                        case Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD:
                            $message = $e->getMessage();
                            break;
                        default:
                            $message = $e->getMessage();
                    }
     

                    $response['error'] = true;
                    $response['message'] = $message;
                   
					
			}catch(Exception $e){
				$response['error'] = true;
				$response['message'] = $e->getMessage();;
			}
			
			
		}else{
			$response['error'] = true;
			$response['message'] = $this->__('Invalid customer data.');
		}
		
		
		
		
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
	}
	
	
	
	protected function registerFromFacebook($redirectUrl){

		$response = array();
		
		$session = $this->_getSession();
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
		
		
			/**
			 * Initialize customer group id
			 */
			$customer->getGroupId();
		
			try {
				$customerErrors = $customerForm->validateData($customerData);
				if ($customerErrors !== true) {
					$errors = array_merge($customerErrors, $errors);
				} else {
					$password = $customer->generatePassword();
					$customerForm->compactData($customerData);
					$customer->setPassword($password);
					$customer->setConfirmation($password);
					$customerErrors = $customer->validate();
					if (is_array($customerErrors)) {
						$errors = array_merge($customerErrors, $errors);
					}
				}
		
				$validationResult = count($errors) == 0;
		
				if (true === $validationResult) {

					$customer->save();
					$customer->setConfirmation(null);
					$customer->save();
		
					Mage::dispatchEvent('customer_register_success',
						array('account_controller' => $this, 'customer' => $customer)
					);
		
				
					//if (!Mage::helper('signin')->isEnableCustomerActivation()){
					$session->setCustomerAsLoggedIn($customer);
					//}
	
					$this->_welcomeCustomer($customer);
	
					$message = $this->__('Thank you for registering with %s.', Mage::app()->getStore()->getFrontendName());
					$response['linkAfterLogin'] = $redirectUrl;
					
						
					$response['message'] = $message;
					return $response;
				} else {
						
					$session->setCustomerFormData($this->getRequest()->getPost());
					if (is_array($errors)) {
						$errors = implode('<br />', $errors);
		
					} else {
						$errors = $this->__('Invalid customer data');
					}
						
		
					$response['error'] = 1;
					$response['message'] = $errors;
					return $response;
						
				}
			} catch (Mage_Core_Exception $e) {
		
				$session->setCustomerFormData($this->getRequest()->getPost());
		
				if ($e->getCode() === Mage_Customer_Model_Customer::EXCEPTION_EMAIL_EXISTS) {
					$url = Mage::getUrl('customer/account/forgotpassword');
					$message = $this->__('There is already an account with this email address. If you are sure that it is your email address, <a href="%s">click here</a> to get your password and access your account.', $url);
		
				} else {
					$message = $e->getMessage();
				}
		
				$response['error'] = 1;
				$response['message'] = $message;
				return $response;
			} catch (Exception $e) {
		
				$response['error'] = 1;
				$response['message'] = $this->__('Cannot save the customer.');
				
				return $response;
			}
		}
		
		return;
	}
	
	
	public function googleAction(){
		$redirectUrl = Mage::getSingleton('core/session')->getSigninRedirect();
		if (empty($redirectUrl) || !$redirectUrl){
			$redirectUrl = Mage::getBaseUrl('link', Mage::app()->getStore()->isFrontUrlSecure());
		}
		
		if($this->getRequest()->getParam('code', false)) {
			$user = Mage::getModel('signin/google')->auth();
			
			if (!$user){
				$this->_redirectUrl(Mage::getBaseUrl('link', true));
			}
		}else{
			$this->_redirectUrl(Mage::getBaseUrl('link', true));
		}
		//if google authorize 
		
		
		$session = $this->_getSession();
		if ($session->isLoggedIn()) {
		
			$this->_redirectUrl($redirectUrl);
			return;
		
		}
		
		$response = array();
		
		$params = $this->getRequest()->getParams();
			
		if ($user['id']){
			
			$params['email'] = $user['email'];
			$params['firstname'] = $user['given_name'];
			$params['lastname'] = $user['family_name'];
			
			
			//login or register
			try{
				$customerSession = Mage::getSingleton('customer/session');
				$customerModel = Mage::getModel('customer/customer')->setWebsiteId(Mage::app()->getStore()->getWebsiteId());
					
					
				$customer = $customerModel->loadByEmail($params['email']);
					
				if ($customer->getId()){
					//login
					
					if ($customer->getConfirmation() && $customer->isConfirmationRequired()) {
						throw Mage::exception('Mage_Core', Mage::helper('customer')->__('This account is not confirmed.'),
								Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED
						);
					}
					
					
					
						$customerSession->loginById($customer->getId());
					$customerSession->setCustomerAsLoggedIn($customer);
					
					$this->_redirectUrl($redirectUrl);
					return;
				}else{
					//register
										
					$this->registerFromGoogle($params, $redirectUrl);
					return;
				}

			}catch (Mage_Core_Exception $e) {
					
					 switch ($e->getCode()) {
                        case Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED:
                            $value = Mage::helper('customer')->getEmailConfirmationUrl($customer->getEmail());
                            $message =  Mage::helper('customer')->__('This account is not confirmed. <a href="%s">Click here</a> to resend confirmation email.', $value);
                            break;
                        case Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD:
                            $message = $e->getMessage();
                            break;
                        default:
                            $message = $e->getMessage();
                    }
                    $session->addError($message);
                    $session->setUsername($customer->getEmail());
                    
                   
					
				}catch(Exception $e){
				
					$session->addError($this->__('Invalid customer data'));
				}
		
		
		}else{
			$session->addError($this->__('Invalid customer data'));
		}
		
		$this->_redirectUrl($redirectUrl);
		
		
	}
	
	
	public function registerFromGoogle($params, $redirectUrl){

			$response = array();
		
			$session = $this->_getSession();
			$session->setEscapeMessages(true); // prevent XSS injection in user input
		
			
				$errors = array();
		
				if (!$customer = Mage::registry('current_customer')) {
					$customer = Mage::getModel('customer/customer')->setId(null);
				}
		
				/* @var $customerForm Mage_Customer_Model_Form */
				$customerForm = Mage::getModel('customer/form');
				$customerForm->setFormCode('customer_account_create')
							->setEntity($customer);
		
				$customerData = $params;
		
		
				/**
				 * Initialize customer group id
				*/
				$customer->getGroupId();
		
				  try {
		                $customerErrors = $customerForm->validateData($customerData);
		                if ($customerErrors !== true) {
		                    $errors = array_merge($customerErrors, $errors);
		                } else {
		                    $customerForm->compactData($customerData);
		                    $password = $customer->generatePassword();
							$customerForm->compactData($customerData);
							$customer->setPassword($password);
							$customer->setConfirmation($password);
		                    $customerErrors = $customer->validate();
		                    if (is_array($customerErrors)) {
		                        $errors = array_merge($customerErrors, $errors);
		                    }
		                }
		                
		                $validationResult = count($errors) == 0;
		
		                if (true === $validationResult) {
		                    $customer->save();
		
		                    Mage::dispatchEvent('customer_register_success',
		                        array('account_controller' => $this, 'customer' => $customer)
		                    );
		
		                    if ($customer->isConfirmationRequired()) {
		                        $customer->sendNewAccountEmail(
		                            'confirmation',
		                            $session->getBeforeAuthUrl(),
		                            Mage::app()->getStore()->getId()
		                        );
		                        $session->addSuccess($this->__('Account confirmation is required. Please, check your email for the confirmation link. To resend the confirmation email please <a href="%s">click here</a>.', Mage::helper('customer')->getEmailConfirmationUrl($customer->getEmail())));
		                        $this->_redirectSuccess($redirectUrl);
		                        return;
		                    } else {
		                        $session->setCustomerAsLoggedIn($customer);
		                       	$this->_welcomeCustomer($customer);
		                        $this->_redirectSuccess($redirectUrl);
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
		        
		
		        $this->_redirectUrl($redirectUrl);
				
		
			return;
		
	}
}