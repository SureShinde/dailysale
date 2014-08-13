<?php
class IWD_Signin_YahooController extends Mage_Core_Controller_Front_Action{
	
	
	public function indexAction(){
		$this->getToken();
	}
	
	public function prepareAction(){
		
		$response = array();
		
		try{
			$url = $this->getToken();
			$response['error'] = false;
			$response['url'] = $url;
		}catch(Exception $e){
			$response['error'] = true;
			$response['message'] = $e->getMessage();
		}
		
		
		
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
	} 
	
	
	protected function getToken(){
	
		$config = array(
				'callbackUrl' => Mage::getBaseUrl('link', true) . 'signin/yahoo/callback',
				'siteUrl' => 'https://api.login.yahoo.com/oauth/v2/get_request_token',
				'consumerKey' => Mage::getStoreConfig(IWD_Signin_Helper_Data::XML_PATH_YAHOO_KEY),
				'consumerSecret' => Mage::getStoreConfig(IWD_Signin_Helper_Data::XML_PATH_YAHOO_SECRET)
		);
		$consumer = new Zend_Oauth_Consumer($config);
		$token = $consumer->getRequestToken();
	
		Mage::getSingleton('customer/session')->setYahooToken($token);
		return 'https://api.login.yahoo.com/oauth/v2/request_auth?oauth_token='.$token->getToken();
	
	
	}
	
	public function callbackAction(){
	
		$config = array(
				'callbackUrl' => Mage::getBaseUrl('link', true) . 'signin/twitter/callback',
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
	
		$profile = $this->checkRelated($token->getParam('xoauth_yahoo_guid'));
	
		if (!$profile){
			$message = Mage::helper('signin')->__('Your Yahoo account not linked to internal store account. Please login with store account and we will create link with yahoo and in next time you will be able login with Yahoo.');
			echo '<script>';
			echo 'window.opener.IWD.Signin.showMessage("' .  $message  . '");';
			echo 'window.opener.IWD.Signin.yahooDialog.close();';
			echo '</script>';
				
			return ;
		}else{
			$id = $profile->getCustomerId();
				
			if (Mage::getSingleton('customer/session')->loginById($id)){
				$redirectUrl = Mage::getSingleton('core/session')->getSigninRedirect();
				if(empty($redirectUrl)){
					$redirectUrl = Mage::getBaseUrl('link', true) . 'customer/account';
				}
				echo '<script>';
				echo 'window.opener.IWD.Signin.redirect("' .  $redirectUrl  . '");';
				echo 'window.opener.IWD.Signin.yahooDialog.close();';
				echo '</script>';
				return;
			}else{
				$message = Mage::helper('customer')->__('Wrong customer account specified.');
				echo '<script>';
				echo 'window.opener.IWD.Signin.showMessage("' .  $message  . '");';
				echo 'window.opener.IWD.Signin.yahooDialog.close();';
				echo '</script>';
				return;
			}
		}
	}

	protected function checkRelated($guid){
		
		$collection = Mage::getModel('signin/related')->getCollection()
				->addFieldToFilter('social',array('eq'=>'yahoo'))
				->addFieldToFilter('hash',array('eq'=>$guid));
		$item = $collection->getFirstItem();
		if ($item->getId()){
			return $item;			
		}
		Mage::getSingleton('customer/session')->setYahooGuid($guid);
		return false;
	}
}