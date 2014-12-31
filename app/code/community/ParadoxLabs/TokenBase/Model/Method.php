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

/**
 * Generic payment gateway implementation... everything except the API.
 */
class ParadoxLabs_TokenBase_Model_Method extends Mage_Payment_Model_Method_Cc
{
	protected $_formBlockType				= 'tokenbase/form';
	protected $_infoBlockType				= 'tokenbase/info';
	protected $_code						= 'tokenbase';
	
	// Capabilities
	protected $_isGateway					= false;
	protected $_canAuthorize				= true;
	protected $_canCapture					= true;
	protected $_canCapturePartial			= true;
	protected $_canRefund					= true;
	protected $_canRefundInvoicePartial		= true;
	protected $_canVoid						= true;
	protected $_canUseInternal				= true;
	protected $_canUseCheckout				= true;
	protected $_canUseForMultishipping		= true;
	protected $_canSaveCc					= false;
	protected $_canReviewPayment			= false;
	protected $_canCancelInvoice			= true;
	protected $_canManageRecurringProfiles	= false;
	protected $_canFetchTransactionInfo		= false;
	
	// Persistent values
	protected $_gateway						= null;
	protected $_customer					= null;
	protected $_card						= null;
	protected $_storeId						= 0;
	
	/**
	 * Initialize scope
	 */
	public function __construct()
	{
		$this->setStore( Mage::helper('tokenbase')->getCurrentStoreId() );
		
		return $this;
	}
	
	/**
	 * Set the payment config scope and reinitialize the API
	 */
	public function setStore( $id )
	{
		$this->_storeId	= $id;
		$this->_gateway	= null;
		
		return $this;
	}
	
	/**
	 * Fetch a setting for the current store scope.
	 */
	public function getConfigData( $field, $storeId=null )
	{
		if( is_null( $storeId ) ) {
			$storeId = $this->_storeId;
		}
		
		return Mage::getStoreConfig( 'payment/' . $this->_code . '/' . $field, $storeId );
	}
	
	/**
	 * Fetch an advanced setting for the current store scope.
	 */
	public function getAdvancedConfigData( $field, $storeId=null )
	{
		if( is_null( $storeId ) ) {
			$storeId = $this->_storeId;
		}
		
		return Mage::getStoreConfig( 'payment/' . $this->_code . '_advanced/' . $field, $storeId );
	}
	
	/**
	 * Set the customer to use for payment/card operations.
	 */
	public function setCustomer( $customer )
	{
		$this->_customer = $customer;
		
		return $this;
	}
	
	/**
	 * Get the current customer; fetch from session if necessary.
	 */
	public function getCustomer()
	{
		if( is_null( $this->_customer ) || $this->_customer->getId() < 1 ) {
			$this->setCustomer( Mage::helper('tokenbase')->getCurrentCustomer() );
		}
		
		return $this->_customer;
	}
	
	/**
	 * Initialize/return the API gateway class.
	 */
	public function gateway()
	{
		if( is_null( $this->_gateway ) ) {
			$this->_gateway = Mage::getModel( $this->_code . '/gateway' );
			$this->_gateway->init(array(
				'login'			=> $this->getConfigData('login'),
				'password'		=> $this->getConfigData('trans_key'),
				'secret_key'	=> $this->getConfigData('secrey_key'),
				'test_mode'		=> $this->getConfigData('test')
			));
		}
		
		return $this->_gateway;
	}
	
	/**
	 * Load the given card by ID, authenticate, and store with the object.
	 */
	public function loadAndSetCard( $cardId )
	{
		$this->_log( sprintf( 'loadAndSetCard(%s)', $cardId ) );
		
		$card = Mage::getModel( $this->_code . '/card' );
		$card->load( $cardId );
		
		if( $card && $card->getId() == $cardId ) {
			if( ( $this->getCustomer() && $card->hasOwner( $this->getCustomer()->getId() ) ) 
				|| Mage::app()->getStore()->isAdmin() ) {
				$this->setCard( $card );
				
				return $card;
			}
		}
		else {
			Mage::throwException( Mage::helper('tokenbase')->__('Could not load card %s', $cardId) );
		}
		
		/**
		 * This error will be thrown if the card does not exist OR if we don't have permission to use it.
		 */
		Mage::throwException( Mage::helper('tokenbase')->__('Unable to load payment data. Please check the form and try again.') );
	}
	
	/**
	 * Set the current payment card
	 */
	public function setCard( ParadoxLabs_TokenBase_Model_Card $card )
	{
		$this->_log( sprintf( 'setCard(%s)', $card->getId() ) );
		
		$this->_card = $card;
		
		$this->gateway()->setCard( $card );
		
		$this->getInfoInstance()->setTokenbaseId( $card->getId() )
								->setCcType( $card->getAdditional('cc_type') )
								->setCcLast4( $card->getAdditional('cc_last4') )
								->setCcExpMonth( $card->getAdditional('cc_exp_month') )
								->setCcExpYear( $card->getAdditional('cc_exp_year') );
		
		return $this;
	}
	
	/**
	 * Get the current card
	 */
	public function getCard()
	{
		return $this->_card;
	}
	
	/**
	 * Payment method available?
	 */
	public function isAvailable( $quote=null )
	{
		if( $this->getConfigData('active') && parent::isAvailable() ) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Allow zero-subtotal checkout with card storage by forcing the test bit to zero.
	 */
	public function isApplicableToQuote( $quote, $checksBitMask )
	{
		return parent::isApplicableToQuote( $quote, $checksBitMask & ~self::CHECK_ZERO_TOTAL );
	}
	
	/**
	 * Update the CC info during the checkout process.
	 */
	public function assignData( $data )
	{
		parent::assignData( $data );
		
		if( $data->hasCardId() && $data->getCardId() != '' ) {
			$card = $this->loadAndSetCard( $data->getCardId() );
			
			if( $data->hasCcType() && $data->getCcType() != '' ) {
				$this->getInfoInstance()->setCcType( $data->getCcType() );
			}
			
			if( $data->hasCcLast4() && $data->getCcLast4() != '' ) {
				$this->getInfoInstance()->setCcLast4( $data->getCcLast4() );
			}
			
			if( intval( $data->getCcExpMonth() ) > 0 && intval( $data->getCcExpYear() ) > date('Y') ) {
				$this->getInfoInstance()->setCcExpYear( $data->getCcExpYear() )
										->setCcExpMonth( $data->getCcExpMonth() );
			}
			
			if( $data->hasSavedCcCid() && $data->getSavedCcCid() != '' ) {
				$this->getInfoInstance()->setCcCid( preg_replace( '/[^0-9]/', '', $data->getSavedCcCid() ) );
			}
		}
		else {
			$this->getInfoInstance()->unsetData('tokenbase_id');
		}
		
		if( $data->hasSave() ) {
			$this->getInfoInstance()->setAdditionalInformation( 'save', intval( $data->getSave() ) );
		}
		
		return $this;
	}
	
	/**
	 * Check whether void is available for the given order.
	 */
	public function canVoid(Varien_Object $payment)
	{
		if( parent::canVoid( $payment ) ) {
			if( ( $payment->getOrder() instanceof Mage_Sales_Model_Order ) && $payment->getOrder()->canCancel() ) {
				/**
				 * Bad convention: Auth code is stored as the second part of ext_order_id.
				 * If there is no auth code, it has already been voided or is not relevant.
				 */
				$transactionId = explode( ':', $payment->getOrder()->getExtOrderId(), 2 );
				
				if( !isset( $transactionId[1] ) || empty( $transactionId[1] ) ) {
					return false;
				}
			}
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * Validate the transaction inputs.
	 */
	public function validate()
	{
		$this->_log( sprintf( 'validate(%s)', $this->getInfoInstance()->getCardId() ) );
		
		if( $this->getInfoInstance()->hasTokenbaseId() === false ) {
			return parent::validate();
		}
		
		return $this;
	}
	
	/**
	 * Authorize a transaction
	 */
	public function authorize( Varien_Object $payment, $amount )
	{
		$this->_log( sprintf( 'authorize(%s %s, %s)', get_class( $payment ), $payment->getId(), $amount ) );
		
		$this->_loadOrCreateCard( $payment );
		
		if( $amount <= 0 ) {
			return $this;
		}
		
		/**
		 * Process transaction and results
		 */
		if( $this->getAdvancedConfigData('send_line_items') ) {
			$this->gateway()->setLineItems( $payment->getOrder()->getAllVisibleItems() );
		}
		
		$this->_beforeAuthorize( $payment, $amount );
		$response = $this->gateway()->authorize( $payment, $amount );
		$this->_afterAuthorize( $payment, $amount, $response );
		
		if( $response->getIsFraud() === true ) {
			$payment->setIsTransactionPending(true)
					->setIsFraudDetected(true)
					->setTransactionAdditionalInfo( 'is_transaction_fraud', true );
		}
		else {
			$payment->getOrder()->setStatus( $this->getConfigData('order_status') );
		}
		
		$payment->getOrder()->setExtOrderId( sprintf( '%s:%s', $response->getTransactionId(), $response->getAuthCode() ) );
		
		$payment->setTransactionId( $response->getTransactionId() )
				->setAdditionalInformation( array_merge( $payment->getAdditionalInformation(), $response->getData() ) )
				->setIsTransactionClosed(0);
		
		$this->getCard()->updateLastUse()->save();
		
		$this->_log( json_encode( $response->getData() ) );
		
		return $this;
	}
	
	/**
	 * Capture a transaction [authorize if necessary]
	 */
	public function capture( Varien_Object $payment, $amount )
	{
		$this->_log( sprintf( 'capture(%s %s, %s)', get_class( $payment ), $payment->getId(), $amount ) );
		
		$this->_loadOrCreateCard( $payment );
		
		if( $amount <= 0 ) {
			return $this;
		}
		
		/**
		 * Check for existing auth code.
		 */
		$transactionId = explode( ':', $payment->getOrder()->getExtOrderId() );
		if( !empty( $transactionId[1] ) ) {
			$this->gateway()->setHaveAuthorized( true );
			$this->gateway()->setAuthCode( $transactionId[1] );
			$this->gateway()->setTransactionId( $transactionId[0] );
		}
		else {
			$this->gateway()->setHaveAuthorized( false );
		}
		
		/**
		 * Grab transaction ID from the invoice in case partial invoicing.
		 */
		$realTransactionId	= null;
		$invoice			= Mage::registry('current_invoice');
		if( !is_null( $invoice ) ) {
			if( $invoice->getTransactionId() != '' ) {
				$realTransactionId = $invoice->getTransactionId();
			}
			
			if( $this->getAdvancedConfigData('send_line_items') ) {
				$this->gateway()->setLineItems( $invoice->getAllItems() );
			}
		}
		elseif( $this->getAdvancedConfigData('send_line_items') ) {
			$this->gateway()->setLineItems( $payment->getOrder()->getAllVisibleItems() );
		}
		
		/**
		 * Process transaction and results
		 */
		$this->_beforeCapture( $payment, $amount );
		$response = $this->gateway()->capture( $payment, $amount, $realTransactionId );
		$this->_afterCapture( $payment, $amount, $response );
		
		if( $response->getIsFraud() === true ) {
			$payment->setIsTransactionPending(true)
					->setIsFraudDetected(true)
					->setTransactionAdditionalInfo( 'is_transaction_fraud', true );
		}
		elseif( $this->gateway()->getHaveAuthorized() === false ) {
			$payment->getOrder()->setStatus( $this->getConfigData('order_status') )
								->setExtOrderId( sprintf( '%s:%s', $response->getTransactionId(), $response->getAuthCode() ) );
		}
		
		if( $response->getIsFraud() !== true ) {
			$payment->setIsTransactionClosed(1);
		}
		
		$payment->setTransactionId( $response->getTransactionId() )
				->setAdditionalInformation( array_merge( $payment->getAdditionalInformation(), $response->getData() ) );
		
		$this->getCard()->updateLastUse()->save();
		
		$this->_log( json_encode( $response->getData() ) );
		
		return $this;
	}
	
	/**
	 * Refund a transaction
	 */
	public function refund( Varien_Object $payment, $amount )
	{
		$this->_log( sprintf( 'refund(%s %s, %s)', get_class( $payment ), $payment->getId(), $amount ) );
		
		$this->_loadOrCreateCard( $payment );
		
		/**
		 * Grab transaction ID from the order
		 */
		$transactionId = explode( ':', $payment->getOrder()->getExtOrderId() );
		
		$this->gateway()->setTransactionId( $transactionId[0] );
		
		/**
		 * Grab transaction ID from the invoice in case partial invoicing.
		 */
		$realTransactionId	= null;
		$creditmemo			= $payment->getCreditmemo();
		if( !is_null( $creditmemo ) ) {
			if( $creditmemo->getInvoice()->getTransactionId() != '' ) {
				$realTransactionId = $creditmemo->getInvoice()->getTransactionId();
			}
			
			if( $this->getAdvancedConfigData('send_line_items') ) {
				$this->gateway()->setLineItems( $creditmemo->getAllItems() );
			}
		}
		elseif( $this->getAdvancedConfigData('send_line_items') ) {
			$this->gateway()->setLineItems( $payment->getOrder()->getAllVisibleItems() );
		}
		
		/**
		 * Process transaction and results
		 */
		$this->_beforeRefund( $payment, $amount );
		$response = $this->gateway()->refund( $payment, $amount, $realTransactionId );
		$this->_afterRefund( $payment, $amount, $response );
		
		$payment->setIsTransactionClosed(1)
				->setAdditionalInformation( array_merge( $payment->getAdditionalInformation(), $response->getData() ) );
		
		$this->getCard()->updateLastUse()->save();
		
		$this->_log( json_encode( $response->getData() ) );
		
		return $this;
	}
	
	/**
	 * Void a payment
	 */
	public function void( Varien_Object $payment )
	{
		$this->_log( sprintf( 'void(%s %s)', get_class( $payment ), $payment->getId() ) );
		
		$this->_loadOrCreateCard( $payment );
		
		/**
		 * Grab transaction ID from the order
		 */
		$transactionId = explode( ':', $payment->getOrder()->getExtOrderId() );
		
		$this->gateway()->setTransactionId( $transactionId[0] );
		
		/**
		 * Process transaction and results
		 */
		$this->_beforeVoid( $payment );
		$response = $this->gateway()->void( $payment );
		$this->_afterVoid( $payment, $response );
			
		$transactionId = $response->getTransactionId() != '' && $response->getTransactionId() != '0' ? $response->getTransactionId() : $transactionId[0].'-2';
		
		$payment->getOrder()->setExtOrderId( $transactionId );
		
		$payment->setTransactionId( $transactionId )
				->setAdditionalInformation( array_merge( $payment->getAdditionalInformation(), $response->getData() ) )
				->setShouldCloseParentTransaction(1)
				->setIsTransactionClosed(1)
				->save();
		
		$this->getCard()->updateLastUse()->save();
		
		$this->_log( json_encode( $response->getData() ) );
		
		return $this;
	}
	
	/**
	 * Cancel a payment
	 */
	public function cancel( Varien_Object $payment )
	{
		$this->_log( sprintf( 'cancel(%s %s)', get_class( $payment ), $payment->getId() ) );
		
		return $this->void( $payment );
	}
	
	/**
	 * Fetch transaction info -- fraud detection
	 */
	public function fetchTransactionInfo( Mage_Payment_Model_Info $payment, $transactionId )
	{
		$this->_log( 'fetchTransactionInfo('.$transactionId.')' );
		
		$this->_loadOrCreateCard( $payment );
		
		/**
		 * Process transaction and results
		 */
		$this->_beforeFraudUpdate( $payment, $transactionId );
		$response = $this->gateway()->fraudUpdate( $payment, $transactionId );
		$this->_afterFraudUpdate( $payment, $transactionId, $response );
		
		if( $response->getIsApproved() ) {
			$transaction = $payment->getTransaction($transactionId);
			$transaction->setAdditionalInformation( 'is_transaction_fraud', false );
			
			$payment->setIsTransactionApproved( true );
		}
		elseif( $response->getIsDenied() ) {
			$payment->setIsTransactionDenied( true );
		}
		
		$this->_log( json_encode( $response->getData() ) );
		
		return parent::fetchTransactionInfo( $payment, $transactionId );
	}
	
	/**
	 * Given the current object/payment, load the paying card, or create
	 * one if none exists.
	 */
	protected function _loadOrCreateCard( Varien_Object $payment )
	{
		$this->_log( sprintf( '_loadOrCreateCard(%s %s)', get_class( $payment ), $payment->getId() ) );
		
		if( !is_null( $this->_card ) ) {
			$this->setCard( $this->getCard() );
			
			return $this->getCard();
		}
		elseif( $payment->hasTokenbaseId() ) {
			return $this->loadAndSetCard( $payment->getTokenbaseId() );
		}
		elseif( $payment->hasCcNumber() && $payment->hasCcExpYear() && $payment->hasCcExpMonth() ) {
			$card = Mage::getModel( $this->_code . '/card' );
			$card->setMethod( $this->_code )
				 ->setMethodInstance( $this )
				 ->setCustomer( $this->getCustomer(), $payment )
				 ->importPaymentInfo( $payment );
			
			if( $payment->getOrder() ) {
				$card->setAddress( $payment->getOrder()->getBillingAddress() );
			}
			elseif( $payment->getBillingAddress() ) {
				$card->setAddress( $payment->getBillingAddress() );
			}
			else {
				Mage::throwException( Mage::helper('tokenbase')->__('Could not find billing address.') );
			}
				 
			$card->save();
			
			$this->setCard( $card );
			
			return $card;
		}
		
		/**
		 * This error will be thrown if we were unable to load a card and had no data to create one.
		 */
		Mage::throwException( Mage::helper('tokenbase')->__('Invalid payment data provided. Please check the form and try again.') );
	}
	
	/**
	 * Write a message to the logs, nice and abstractly.
	 */
	protected function _log( $message )
	{
		Mage::helper('tokenbase')->log( $this->_code, $message );
		
		return $this;
	}
	
	/**
	 * Stubs, implement in methods as convenient.
	 */
	protected function _beforeAuthorize( Varien_Object $payment, $amount ) {}
	protected function _beforeCapture( Varien_Object $payment, $amount ) {}
	protected function _beforeFraudUpdate( Varien_Object $payment, $transactionId ) {}
	protected function _beforeRefund( Varien_Object $payment, $amount ) {}
	protected function _beforeVoid( Varien_Object $payment ) {}
	protected function _afterAuthorize( Varien_Object $payment, $amount, Varien_Object $response ) {}
	protected function _afterCapture( Varien_Object $payment, $amount, Varien_Object $response ) {}
	protected function _afterFraudUpdate( Varien_Object $payment, $transactionId, Varien_Object $response ) {}
	protected function _afterRefund( Varien_Object $payment, $amount, Varien_Object $response ) {}
	protected function _afterVoid( Varien_Object $payment, Varien_Object $response ) {}
}
