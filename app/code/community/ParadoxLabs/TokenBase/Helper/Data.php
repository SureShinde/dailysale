<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 * 
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 * 
 * Want to customize or need help with your store?
 *  Phone: 717-431-3330
 *  Email: sales@paradoxlabs.com
 *
 * @category	ParadoxLabs
 * @package		TokenBase
 * @author		Ryan Hoerr <magento@paradoxlabs.com>
 * @license		http://store.paradoxlabs.com/license.html
 */

class ParadoxLabs_TokenBase_Helper_Data extends Mage_Core_Helper_Abstract
{
	protected $_address	= null;
	protected $_card	= null;
	protected $_cards	= null;
	
	/**
	 * Return active payment methods (if any) implementing tokenbase.
	 * @return array Method codes implementing tokenbase
	 */
	public function getActiveMethods()
	{
		$active		= array();
		$methods	= $this->getAllMethods();
		
		foreach( $methods as $method ) {
			if( Mage::getStoreConfig( 'payment/' . $method . '/active' ) == 1 ) {
				$active[] = $method;
			}
		}
		
		return $active;
	}
	
	/**
	 * Return all tokenbase-derived payment methods, without an active check.
	 */
	public function getAllMethods()
	{
		return array_keys( Mage::getConfig()->getNode('global/tokenbase/methods')->asArray() );
	}
	
	/**
	 * Return store scope based on the available info... the admin panel makes this complicated.
	 */
	public function getCurrentStoreId()
	{
		if( Mage::app()->getStore()->isAdmin() ) {
			if( Mage::registry('current_order') != false ) {
				return Mage::registry('current_order')->getStoreId();
			}
			elseif( Mage::registry('current_customer') != false ) {
				return Mage::registry('current_customer')->getStoreId();
			}
			elseif( Mage::registry('current_invoice') != false ) {
				return Mage::registry('current_invoice')->getStoreId();
			}
			elseif( Mage::registry('current_creditmemo') != false ) {
				return Mage::registry('current_creditmemo')->getStoreId();
			}
			elseif( Mage::registry('current_recurring_profile') != false ) {
				return Mage::registry('current_recurring_profile')->getStoreId();
			}
			elseif( Mage::getSingleton('adminhtml/session_quote')->getStoreId() > 0 ) {
				return Mage::getSingleton('adminhtml/session_quote')->getStoreId();
			}
		}
		
		return Mage::app()->getStore()->getId();
	}
	
	/**
	 * Return current customer based on the available info.
	 */
	public function getCurrentCustomer()
	{
		if( Mage::app()->getStore()->isAdmin() ) {
			if( Mage::registry('current_order') != false ) {
				return Mage::getModel('customer/customer')->load( Mage::registry('current_order')->getCustomerId() );
			}
			elseif( Mage::registry('current_customer') != false ) {
				return Mage::registry('current_customer');
			}
			elseif( Mage::registry('current_invoice') != false ) {
				return Mage::getModel('customer/customer')->load( Mage::registry('current_invoice')->getCustomerId() );
			}
			elseif( Mage::registry('current_creditmemo') != false ) {
				return Mage::getModel('customer/customer')->load( Mage::registry('current_creditmemo')->getCustomerId() );
			}
			elseif( Mage::registry('current_recurring_profile') != false ) {
				return Mage::getModel('customer/customer')->load( Mage::registry('current_recurring_profile')->getCustomerId() );
			}
			else {
				$customer = Mage::getSingleton('adminhtml/session_quote')->getCustomer();
				
				if( $customer->getId() < 1 && Mage::getSingleton('adminhtml/session_quote')->hasQuoteId() && Mage::getSingleton('adminhtml/session_quote')->getQuote()->getCustomerEmail() ) {
					$customer->setEmail( Mage::getSingleton('adminhtml/session_quote')->getQuote()->getCustomerEmail() );
				}
				
				return $customer;
			}
		}
		elseif( Mage::registry('current_customer') != false ) {
			return Mage::registry('current_customer');
		}
		
		return Mage::getSingleton('customer/session')->getCustomer();
	}
	
	/**
	 * Return available CC types for a given method.
	 */
	public function getCcAvailableTypes( $method )
	{
		$config	= Mage::getConfig()->getNode('global/payment/cc/types')->asArray();
		$avail	= explode( ',', Mage::helper('payment')->getMethodInstance( $method )->getConfigData('cctypes') );
		
		$types	= array();
		foreach( $config as $data ) {
			if( in_array( $data['code'], $avail ) !== false ) {
				$types[ $data['code'] ] = $data['name'];
			}
		}
		
		return $types;
	}
	
	/**
	 * Return an array of month options for expiry dates.
	 */
	public function getCcMonths()
	{
		$months = Mage::app()->getLocale()->getTranslationList('month');
		foreach( $months as $key => $value ) {
			$monthNum		= ($key < 10) ? '0' . $key : $key;
			$months[ $key ]	= $monthNum . ' - ' . $value;
		}
		
		return $months;
	}
	
	/**
	 * Return an array of year options for expiry dates.
	 */
	public function getCcYears()
	{
		$first	= date("Y");
		$years	= array();
		for( $index=0; $index <= 10; $index++ ) {
			$years[ $first + $index ] = $first + $index;
		}
		
		return $years;
	}
	
	/**
	 * Get the address block for dynamic state/country selection on forms.
	 */
	public function getAddressBlock()
	{
		if( is_null( $this->_address ) ) {
			$this->_address = Mage::app()->getLayout()->createBlock('directory/data');
		}
		
		return $this->_address;
	}
	
	/**
	 * Return active card model for edit (if any).
	 */
	public function getActiveCard( $method=null )
	{
		$method = is_null( $method ) ? Mage::registry('tokenbase_method') : $method;
		
		if( is_null( $this->_card ) ) {
			if( Mage::registry('active_card') ) {
				$this->_card = Mage::registry('active_card');
			}
			else {
				$this->_card = Mage::getModel( $method . '/card' );
			}
			
			/**
			 * Import prior form data from the session, if possible.
			 */
			$session = Mage::getSingleton('customer/session');
			if( Mage::app()->getStore()->isAdmin() == false && $session->hasTokenbaseFormData() ) {
				$data = $session->getTokenbaseFormData( true );
				
				if( isset( $data['billing'] ) && count( $data['billing'] ) > 0 ) {
					$address = Mage::getModel('customer/address');
					
					$addressForm = Mage::getModel('customer/form');
					$addressForm->setFormCode('customer_address_edit');
					$addressForm->setEntity( $address );
					
					$addressData = $addressForm->extractData( $addressForm->prepareRequest( $data['billing'] ) );
					
					$addressForm->compactData( $addressData );
					
					$this->_card->setAddress( $address );
				}
				
				if( isset( $data['payment'] ) && count( $data['payment'] ) > 0 ) {
					$cardData = $data['payment'];
					$cardData['method']		= $method;
					$cardData['card_id']	= $data['id'];
					$cardData['cc_cid']		= '000'; // This bypasses the validation check in importData below. Does not matter otherwise.
					
					$newPayment = Mage::getModel('sales/quote_payment');
					$newPayment->setQuote( Mage::getSingleton('checkout/session')->getQuote() );
					$newPayment->importData( $cardData );
					
					$this->_card->importPaymentInfo( $newPayment );
				}
			}
		}
		
		return $this->_card;
	}
	
	/**
	 * Get stored cards for the currently-active method.
	 */
	public function getActiveCustomerCardsByMethod( $method=null )
	{
		if( is_null( $this->_cards ) ) {
			$method = is_null( $method ) ? Mage::registry('tokenbase_method') : $method;
			
			Mage::dispatchEvent( 'tokenbase_before_load_active_cards', array( 'method' => $method, 'customer' => $this->getCurrentCustomer() ) );
			
			$this->_cards = Mage::getModel('tokenbase/card')->getCollection();
			
			if( Mage::app()->getStore()->isAdmin() && Mage::getSingleton('adminhtml/session_quote')->hasQuoteId() && Mage::getSingleton('adminhtml/session_quote')->getQuote()->getPayment()->getTokenbaseId() > 0 ) {
				$tokenbaseId = Mage::getSingleton('adminhtml/session_quote')->getQuote()->getPayment()->getTokenbaseId();
				
				if( $this->getCurrentCustomer()->getId() > 0 ) {
					// Manual select -- only because collections don't let us do the complex condition. (soz.)
					$this->_cards->getSelect()->where( sprintf( "id='%s' or (active=1 and customer_id='%s')", $tokenbaseId, $this->getCurrentCustomer()->getId() ) );
				}
				else {
					$this->_cards->addFieldToFilter( 'id', $tokenbaseId );
				}
			}
			elseif( $this->getCurrentCustomer()->getId() > 0 ) {
				$this->_cards->addFieldToFilter( 'active', 1 )
							 ->addFieldToFilter( 'customer_id', $this->getCurrentCustomer()->getId() );
			}
			else {
				return array();
			}
			
			if( !is_null( $method ) ) {
				$this->_cards->addFieldToFilter( 'method', $method );
			}
			
			Mage::dispatchEvent( 'tokenbase_after_load_active_cards', array( 'method' => $method, 'customer' => $this->getCurrentCustomer(), 'cards' => $this->_cards ) );
		}
		
		return $this->_cards;
	}
	
	/**
	 * Get customer address dropdown
	 */
	public function getAddressesHtmlSelect( $type='billing', $default=0 )
	{
		$options = array();
		foreach( $this->getCurrentCustomer()->getAddresses() as $address ) {
			$options[] = array(
				'value' => $address->getId(),
				'label' => $address->format('oneline')
			);
		}
		
		$select = Mage::app()->getLayout()->createBlock('core/html_select')
						->setName( $type.'_address_id' )
						->setId( $type.'-address-select' )
						->setClass( 'address-select' )
						->setValue( $default )
						->setOptions( $options );
		
		$select->addOption('', Mage::helper('checkout')->__('Change Address'));
		
		return $select->getHtml();
	}
	
	/**
	 * Get customer country dropdown
	 */
	public function getCountryHtmlSelect( $name, $default='US', $id=null )
	{
		return $this->getAddressBlock()->getCountryHtmlSelect( $default, $name, $id );
	}
	
	/**
	 * Write a message to the logs, nice and abstractly.
	 */
	public function log( $code, $message )
	{
		Mage::log( $message, null, $code . '.log', true );
		
		return $this;
	}
}
