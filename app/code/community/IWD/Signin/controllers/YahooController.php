<?php
class IWD_Signin_YahooController extends Mage_Core_Controller_Front_Action{
	
	protected function _getSession(){
		return Mage::getSingleton('customer/session');
	}
	
	public function indexAction(){
		$this->getToken();
	}
	
	public function prepareAction(){
		
		$response = array();

		
		try{
			$url = $this->getToken();
			$this->_redirectUrl($url);
		}catch(Exception $e){
				echo '<script>';
				echo 'window.opener.IWD.Signin.showMessage("' .  $e->getMessage()  . '");';
				echo 'window.opener.IWD.Signin.yahooDialog.close();';
				echo '</script>';
		}		
	} 
	
	
	protected function getToken(){
		$_scheme = Mage::app()->getRequest()->getScheme();
		if ($_scheme=='https'){
			$_secure = true;
		}else{
			$_secure = false;
		}
		
		$config = array(
				'callbackUrl' => Mage::getBaseUrl('link', $_secure) . 'signin/yahoo/callback',
				'siteUrl' => 'https://api.login.yahoo.com/oauth/v2/get_request_token',
				'consumerKey' => Mage::getStoreConfig(IWD_Signin_Helper_Data::XML_PATH_YAHOO_KEY),
				'consumerSecret' => Mage::getStoreConfig(IWD_Signin_Helper_Data::XML_PATH_YAHOO_SECRET)
		);
		$consumer = new Zend_Oauth_Consumer($config);
		
		try{
		  $token = $consumer->getRequestToken();
		}catch(Exception $e){
		    echo $e->getMessage();die();
		}
		Mage::getSingleton('customer/session')->setYahooToken($token);
		return 'https://api.login.yahoo.com/oauth/v2/request_auth?oauth_token='.$token->getToken();

	}
	
	public function callbackAction(){
		$_scheme = Mage::app()->getRequest()->getScheme();
		if ($_scheme=='https'){
			$_secure = true;
		}else{
			$_secure = false;
		}
		
		$config = array(
				'callbackUrl' => Mage::getBaseUrl('link', $_secure) . 'signin/yahoo/callback',
				'siteUrl' => 'https://api.login.yahoo.com/oauth/v2/get_token',
				'consumerKey' => Mage::getStoreConfig(IWD_Signin_Helper_Data::XML_PATH_YAHOO_KEY),
				'consumerSecret' => Mage::getStoreConfig(IWD_Signin_Helper_Data::XML_PATH_YAHOO_SECRET)
		);
	
		$consumer = new Zend_Oauth_Consumer($config);
	
		$token = Mage::getSingleton('customer/session')->getYahooToken();
		if (!empty($_GET) && $token){
			try{
				$token = $consumer->getAccessToken(
						$_GET,
						$token
				);
					
			}catch(Exception $e){
				$message = $e->getMessage();
				echo '<script>';
				echo 'window.opener.IWD.Signin.showMessage("' .  $message  . '");';
				echo 'window.opener.IWD.Signin.yahooDialog.close();';
				echo '</script>';
				return;
			}
				
		}else{
				
			$message = Mage::helper('customer')->__('Invalid callback request. Oops. Sorry.');
			echo '<script>';
			echo 'window.opener.IWD.Signin.showMessage("' .  $message  . '");';
			echo 'window.opener.IWD.Signin.yahooDialog.close();';
			echo '</script>';
			return;
		}
		

	
		
		
		$userId = $token->getParam('xoauth_yahoo_guid');
		$email = $token->getParam('xoauth_yahoo_guid').'@yahoo.com';
		
		/** authorize by request **/
		
		$redirectUrl = Mage::getSingleton('core/session')->getSigninRedirect();
		
		$session = $this->_getSession();
		if ($session->isLoggedIn()) {
		
			$redirectUrl = Mage::getBaseUrl('link', Mage::app()->getStore()->isFrontUrlSecure());
			echo '<script>';
			echo 'window.opener.IWD.Signin.redirect("' .  $redirectUrl  . '");';
			echo 'window.opener.IWD.Signin.yahooDialog.close();';
			echo '</script>';
			return;
		
		}
		
		
		
		try{
				
			$customerId = $this->checkRelated($userId);
			$customerSession = Mage::getSingleton('customer/session');
			$customerModel = Mage::getModel('customer/customer')->setWebsiteId(Mage::app()->getStore()->getWebsiteId());
			$customer = $customerModel->load($customerId);
		
		
			if ($customer->getId()){
				//login
				$customerSession->setCustomerAsLoggedIn($customer);
				$customerSession->renewSession();
				echo '<script>';
				echo 'window.opener.IWD.Signin.redirect("' .  $redirectUrl  . '");';
				echo 'window.opener.IWD.Signin.yahooDialog.close();';
				echo '</script>';
				return;
			}else{
				//register
				$response = $this->registerFromYahoo($token);
				return;
			}
		
		
		}catch (Mage_Core_Exception $e) {
		
			switch ($e->getCode()) {
				case Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD:
					$message = $e->getMessage();
					break;
				default:
					$message = $e->getMessage();
			}
		
			echo '<script>';
			echo 'window.opener.IWD.Signin.showMessage("' .  $message  . '");';
			echo 'window.opener.IWD.Signin.yahooDialog.close();';
			echo '</script>';
		
		
		}catch(Exception $e){
		
			echo '<script>';
			echo 'window.opener.IWD.Signin.showMessage("' .  $e->getMessage()  . '");';
			echo 'window.opener.IWD.Signin.yahooDialog.close();';
			echo '</script>';
		}

	}
	
	
	protected function registerFromYahoo($token){
	
	
		$firstname = $token->getParam('xoauth_yahoo_guid');
		$response = array();
	
		$session = $this->_getSession();
		$session->setEscapeMessages(true); // prevent XSS injection in user input
	
	
		$errors = array();
	
			
		$customer = Mage::getModel('customer/customer')->setId(null);
			
	
		/* @var $customerForm Mage_Customer_Model_Form */
		$customerForm = Mage::getModel('customer/form');
		$customerForm->setFormCode('customer_account_create')->setEntity($customer);
		$email = $token->getParam('xoauth_yahoo_guid').'@yahoo.com';
		$customerData = array('firstname'=>$firstname,'lastname'=>' ', 'email'=>$email);
	
	
		/**
		 * Initialize customer group id
		*/
		$customer->getGroupId();
	
		try {
	
	
			$password = $customer->generatePassword();
			$customerForm->compactData($customerData);
			$customer->setPassword($password);
			$customer->setConfirmation('');
			$customer->setEmail($email);
			$customerErrors = $customer->validate();
			$errors = $customerErrors;
			$validationResult = count($errors) == 0;
	
	
			$customer->save();
			$customer->setConfirmation(null);
			$customer->save();
			Mage::dispatchEvent('customer_register_success',
			array('account_controller' => $this, 'customer' => $customer)
			);
	
			$session->setCustomerAsLoggedIn($customer);
	
			$redirectUrl = Mage::getUrl('customer/account/edit/', array('_secure'=>true));
				
			echo '<script>';
			echo 'window.opener.IWD.Signin.redirect("' .  $redirectUrl  . '");';
			echo 'window.opener.IWD.Signin.yahooDialog.close();';
			echo '</script>';
				
			return ;
	
		} catch (Mage_Core_Exception $e) {
	
			$session->setCustomerFormData($this->getRequest()->getPost());
	
				
			$message = $e->getMessage();
			echo '<script>';
			echo 'window.opener.IWD.Signin.showMessage("' .  $e->getMessage()  . '");';
			echo 'window.opener.IWD.Signin.yahooDialog.close();';
			echo '</script>';
				
			return ;
		} catch (Exception $e) {
	
				
			$message = $this->__('Cannot save the customer.');
	
			$message = $e->getMessage();
			echo '<script>';
			echo 'window.opener.IWD.Signin.showMessage("' .  $e->getMessage()  . '");';
			echo 'window.opener.IWD.Signin.yahooDialog.close();';
			echo '</script>';
	
			return ;
		}
	
	
		return;
	}

	protected function checkRelated($guid){
		
		$collection = Mage::getModel('signin/related')->getCollection()
				->addFieldToFilter('social',array('eq'=>'yahoo'))
				->addFieldToFilter('hash',array('eq'=>$guid));
		$item = $collection->getFirstItem();
		if ($item->getId()){
			return $item->getData('customer_id');			
		}
		Mage::getSingleton('customer/session')->setYahooGuid($guid);
		return false;
	}
}