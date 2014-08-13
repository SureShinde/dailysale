<?php
class IWD_Signin_Model_Observer{
	
	public function checkRequiredModules($observer){
		$cache = Mage::app()->getCache();
		
		if (Mage::getSingleton('admin/session')->isLoggedIn()) {
			if (!Mage::getConfig()->getModuleConfig('IWD_All')->is('active', 'true')){
				if ($cache->load("iwd_signin")===false){
					$message = 'Important: Please setup IWD_ALL in order to finish <strong>IWD Social Media Login</strong> installation.<br />
						Please download <a href="http://iwdextensions.com/media/modules/iwd_all.tgz" target="_blank">IWD_ALL</a> and setup it via Magento Connect.<br />
						Please refer to installation <a href="https://docs.google.com/document/d/1-E62tOufV2GEIBDVT8P0nkYE8cMtOAI1KD9R9WBQAuE/edit" target="_blank">guide</a>';
				
					//Mage::getSingleton('adminhtml/session')->addNotice($message);
					$cache->save('true', 'iwd_signin', array("iwd_signin"), $lifeTime=1);
				}
				$available = Mage::helper('signin')->isAvailableVersion();
			}
		}
		
		
	}
	
	
	public function checkCustomerLogin($observer){
		$customer = $observer->getCustomer();
		//check yahoo 
		
		$guid = Mage::getSingleton('customer/session')->getYahooGuid();
		if ($guid && $guid!=null){
			//assign yahoo to customer
			$model = Mage::getModel('signin/related');
			$model->setData('social', 'yahoo');
			$model->setData('hash', $guid);
			$model->setData('customer_id', $customer->getId());
			
			try{
				$model->save();
				Mage::getSingleton('customer/session')->addSuccess(Mage::helper('signin')->__('Your %s account has been linked with your Yahoo login', Mage::app ()->getStore ()->getFrontendName ()));
			}catch(Exception $e){
				Mage::logException($e);
			}
			
		}
		
		//twitter
		
		$guid = Mage::getSingleton('customer/session')->getTwitterGuid();
		if ($guid && $guid!=null){
			//assign yahoo to customer
			$model = Mage::getModel('signin/related');
			$model->setData('social', 'twitter');
			$model->setData('hash', $guid);
			$model->setData('customer_id', $customer->getId());
				
			try{
				$model->save();
				Mage::getSingleton('customer/session')->addSuccess(Mage::helper('signin')->__('Your %s account has been linked with your Twitter login', Mage::app ()->getStore ()->getFrontendName ()));
			}catch(Exception $e){
				Mage::logException($e);
			}
				
		}
	}
	
	
}