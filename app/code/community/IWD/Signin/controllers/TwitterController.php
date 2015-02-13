<?php
include 'iwd/signin/twitter/Twitter.php';
class IWD_Signin_TwitterController extends Mage_Core_Controller_Front_Action{
	
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
				echo 'window.opener.IWD.Signin.twitterDialog.close();';
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
	
		$model = new Twitter();
		$code = $model->apponly_request(array(
                                'without_bearer' => true,
                                'method' => 'POST',
                                'url' => $model->url('oauth/request_token', ''),
                                'params' => array(
                                  'oauth_callback' =>  Mage::getBaseUrl('link', $_secure) . 'signin/twitter/callback',
                                ),
                              ));
		
		if ($code != 200) {
		    echo ("There was an error communicating with Twitter. {$model->response['response']}");
		    return;
		}
		
		$token = $model->extract_params($model->response['response']);
		
		// check the callback has been confirmed
		if ($token['oauth_callback_confirmed'] !== 'true') {
		    echo ('The callback was not confirmed by Twitter so we cannot continue.');
		} else {
		    $url = $model->url('oauth/authenticate', '') . "?oauth_token={$token['oauth_token']}";
		}
		
		Mage::getSingleton('customer/session')->setTwitterToken($token);		
		
		return $url;

		
	}

	
	public function callbackAction(){
		
		$_scheme = Mage::app()->getRequest()->getScheme();
		if ($_scheme=='https'){
			$_secure = true;
		}else{
			$_secure = false;
		}
		
		
		
		$token = Mage::getSingleton('customer/session')->getTwitterToken();
		
		
		$params = $this->getRequest()->getParams();
		if ($params['oauth_token'] !== $token['oauth_token']) {
		    echo "The oauth token you started with doesn't match the one you've been redirected with. do you have multiple tabs open?";		   
		    return;
		}
		
		if (!isset($params['oauth_verifier'])) {
		    echo 'The oauth verifier is missing so we cannot continue. did you deny the appliction access?';

		    return;
		}
		$model = new Twitter();
		// update with the temporary token and secret
		$model->reconfigure(array_merge($model->config, array(
		    'token'  => $token['oauth_token'],
		    'secret' => $token['oauth_token_secret'],
		)));
		
		$code = $model->user_request(array(
		    'method' => 'POST',
		    'url' => $model->url('oauth/access_token', ''),
		    'params' => array(
		        'oauth_verifier' => trim($params['oauth_verifier']),
		    )
		));
		
		if ($code == 200) {
		    $oauth_creds = $model->extract_params($model->response['response']);
		
		}else{
		    $message = Mage::helper('customer')->__('Invalid callback request. Oops. Sorry.');
			echo '<script>';
			echo 'window.opener.IWD.Signin.showMessage("' .  $message  . '");';
			echo 'window.opener.IWD.Signin.twitterDialog.close();';
			echo '</script>';
			return;
		}
		

		
				
		$userId = $oauth_creds['user_id'];
		$email = $oauth_creds['screen_name'].'@twitter.com';
		

		/** authorize by request **/
		
		$redirectUrl = Mage::getSingleton('core/session')->getSigninRedirect();
		
		$session = $this->_getSession();
		if ($session->isLoggedIn()) {
		
			$redirectUrl = Mage::getBaseUrl('link', Mage::app()->getStore()->isFrontUrlSecure());
			echo '<script>';
			echo 'window.opener.IWD.Signin.redirect("' .  $redirectUrl  . '");';
			echo 'window.opener.IWD.Signin.twitterDialog.close();';
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
				echo 'window.opener.IWD.Signin.twitterDialog.close();';
				echo '</script>';
				return;
			}else{
				//register
				$response = $this->registerFromTwitter($oauth_creds);
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
			echo 'window.opener.IWD.Signin.twitterDialog.close();';
			echo '</script>';
				
				
		}catch(Exception $e){

			echo '<script>';
			echo 'window.opener.IWD.Signin.showMessage("' .  $e->getMessage()  . '");';
			echo 'window.opener.IWD.Signin.twitterDialog.close();';
			echo '</script>';
		}

	}
	
	
	protected function registerFromTwitter($token){
	
		
		$firstname = $token['screen_name'];
		$response = array();
	
		$session = $this->_getSession();
		$session->setEscapeMessages(true); // prevent XSS injection in user input
	
		
			$errors = array();
	
			
			$customer = Mage::getModel('customer/customer')->setId(null);
			
	
			/* @var $customerForm Mage_Customer_Model_Form */
			$customerForm = Mage::getModel('customer/form');
			$customerForm->setFormCode('customer_account_create')->setEntity($customer);
			$email = $token['screen_name'].'@twitter.com';
			$customerData = array('firstname'=>$firstname,'lastname'=>$firstname, 'email'=>$email);
	
	
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
					echo 'window.opener.IWD.Signin.twitterDialog.close();';
					echo '</script>';
					
					return ;
				
			} catch (Mage_Core_Exception $e) {
	
				$session->setCustomerFormData($this->getRequest()->getPost());
	
			
					$message = $e->getMessage();
					echo '<script>';
					echo 'window.opener.IWD.Signin.showMessage("' .  $e->getMessage()  . '");';
					echo 'window.opener.IWD.Signin.twitterDialog.close();';
					echo '</script>';
					
				return ;
			} catch (Exception $e) {
	
			
				$message = $this->__('Cannot save the customer.');
				
				$message = $e->getMessage();
				echo '<script>';
				echo 'window.opener.IWD.Signin.showMessage("' .  $e->getMessage()  . '");';
				echo 'window.opener.IWD.Signin.twitterDialog.close();';
				echo '</script>';
	
				return ;
			}
	
	
		return;
	}

	protected function checkRelated($guid){
		
		$collection = Mage::getModel('signin/related')->getCollection()
				->addFieldToFilter('social',array('eq'=>'twitter'))
				->addFieldToFilter('hash',array('eq'=>$guid));
		$item = $collection->getFirstItem();
		if ($item->getId()){
			return $item->getData('customer_id');			
		}
		Mage::getSingleton('customer/session')->setTwitterGuid($guid);
		return false;
	}

}