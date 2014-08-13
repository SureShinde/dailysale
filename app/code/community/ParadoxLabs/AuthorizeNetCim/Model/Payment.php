<?php
/**
 * Authorize.Net CIM - Payment model. "The brains."
 *
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Having a problem with the plugin?
 * Not sure what something means?
 * Need custom development?
 * Give us a call!
 *
 * @category	ParadoxLabs
 * @package		ParadoxLabs_AuthorizeNetCim
 * @author		Ryan Hoerr <ryan@paradoxlabs.com>
 */


class ParadoxLabs_AuthorizeNetCim_Model_Payment extends Mage_Payment_Model_Method_Cc
{
	protected $_formBlockType			= 'authnetcim/form';
	protected $_infoBlockType			= 'authnetcim/info';
	protected $_code					= 'authnetcim';
	protected $_debug					= false;
	protected $_admin					= false;
	
	// Can-dos
	protected $_isGateway				= false;
	protected $_canAuthorize			= true;
	protected $_canCapture				= true;
	protected $_canCapturePartial		= true;
	protected $_canRefund				= true;
	protected $_canRefundInvoicePartial = true;
	protected $_canVoid					= true;
	protected $_canUseInternal			= true;
	protected $_canUseCheckout			= true;
	protected $_canUseForMultishipping	= true;
	protected $_canSaveCc				= false; // Don't want Magento saving the card itself.
	protected $_canReviewPayment		= false;
	protected $_canCancelInvoice		= true;
	protected $_canManageRecurringProfiles = false;
	protected $_canFetchTransactionInfo = true;
	
	protected $cim						= null;
	protected $_invoice					= null;
	protected $_customer				= null;
	protected $_storeId					= 0;
	
	/**
	 * Initialize Authorize.net CIM class and related flags.
	 */
	public function __construct() {
		if( Mage::app()->getStore()->isAdmin() ) {
			$this->_admin = true;
		}
		
		if( $this->_admin && Mage::registry('current_order') != false ) {
			$this->setStore( Mage::registry('current_order')->getStoreId() );
		}
		elseif( $this->_admin && Mage::registry('current_invoice') != false ) {
			$this->setStore( Mage::registry('current_invoice')->getStoreId() );
		}
		elseif( $this->_admin && Mage::registry('current_creditmemo') != false ) {
			$this->setStore( Mage::registry('current_creditmemo')->getStoreId() );
		}
		elseif( $this->_admin && Mage::registry('current_customer') != false ) {
			$this->setStore( Mage::registry('current_customer')->getStoreId() );
		}
		elseif( $this->_admin && Mage::getSingleton('adminhtml/session_quote')->getStoreId() > 0 ) {
			$this->setStore( Mage::getSingleton('adminhtml/session_quote')->getStoreId() );
		}
		else {
			$this->setStore( Mage::app()->getStore()->getId() );
		}
		
		return $this;
	}
	
	/**
	 * Set the payment config scope and reinitialize the API
	 */
	public function setStore( $id ) {
		$this->_storeId = $id;
		
		$this->initializeApi( true );
		
		return $this;
	}
	
	/**
	 * Set the customer to use for payment/card operations.
	 */
	public function setCustomer( $customer ) {
		$this->_customer = $customer;
		
		return $this;
	}
	
	/**
	 * Fetch a setting for the current store scope.
	 */
    public function getConfigData( $field, $storeId=null ) {
        if( is_null( $storeId ) ) {
            $storeId = $this->_storeId;
        }

        return Mage::getStoreConfig( 'payment/' . $this->getCode() . '/' . $field, $storeId );
    }
    
    /**
     * Get the current customer; fetch from session if necessary.
     */
	public function getCustomer() {
		if( isset( $this->_customer ) ) {
			$customer = $this->_customer;
		}
		elseif( $this->_admin ) {
			$customer = Mage::getModel('customer/customer')->load( Mage::getSingleton('adminhtml/session_quote')->getCustomerId() );
		}
		else {
			$customer = Mage::getSingleton('customer/session')->getCustomer();
		}
		
		$this->setCustomer( $customer );
		
		return $customer;
	}
	
	/**
	 * Initialize the API gateway class. 'force' will reinitialize
	 * in the current config scope.
	 */
	protected function initializeApi( $force=false ) {
		if( $force === true ) {
			$this->cim = null;
		}
		
		if( is_null( $this->cim ) ) {
			$this->_debug = $this->getConfigData('debug');
			
			$this->cim = Mage::getModel('authnetcim/api')->init(	$this->getConfigData('login'),
																	$this->getConfigData('trans_key'),
																	$this->getConfigData('test'),
																	$this->getConfigData('validation_mode') );
		}
		
		return $this;
	}
	
	/**
	 * Permanently delete cards that are past the refund period.
	 */
	public function cleanOldCards() {
		$cards = Mage::getModel('authnetcim/card')->getCollection()
						->addFieldToFilter( 'added', array( 'lt' => (time() - (120*86400)) ) );
		
		foreach( $cards as $card ) {
			if( $this->deletePaymentProfile( $card->getPaymentId(), $card->getProfileId() ) ) {
				$card->delete();
			}
		}
	}
	
	/**
	 * Update the CC info during the checkout process.
	 */
	public function assignData( $data ) {
		parent::assignData( $data );
		
		$post = Mage::app()->getRequest()->getParam('payment');
		
		if( !empty( $post['payment_id'] ) ) {
			$card = $this->getPaymentInfoById( $post['payment_id'], false );
			
			if( $card && $card->getCardNumber() != '' ) {
				$this->getInfoInstance()->setCcLast4( substr( $card->getCardNumber(), -4 ) )
										->setCcType( '' );
			}
		}
		
		return $this;
	}
	
	/**
	 * Validate the transaction inputs.
	 */
	public function validate() {
		if( $this->_debug ) Mage::log('validate()', null, 'authnetcim.log');
		
		$post = Mage::app()->getRequest()->getParam('payment');
		
		if( empty($post['payment_id']) || !empty($post['cc_number']) ) {
			try {
				return parent::validate();
			}
			catch(Exception $e) {
				return $e->getMessage();
			}
		}
		else {
			return true;
		}
	}

	/**
	 * Authorize a transaction
	 */
	public function authorize(Varien_Object $payment, $amount) {
		if( $this->_debug ) Mage::log('authorize()', null, 'authnetcim.log');
		
		$post = Mage::app()->getRequest()->getParam('payment');
		$profile_id = 0;
		
		if( !empty($post['payment_id']) && empty($post['cc_number']) ) {
			$profile_id = intval( $post['payment_id'] );
			
			$payment->getOrder()->setExtCustomerId( $profile_id )->save();
		}
		
		// Set 'save card' checkbox if checked
		if( ( isset( $post['save_card'] ) && $post['save_card'] == 1 ) || $profile_id > 0 ) {
			$payment->setSaveCard( true );
		}
		
		return $this->bill( $payment, $amount, 'profileTransAuthOnly' );
	}

	/**
	 * Capture a transaction [authorize if necessary]
	 */
	public function capture(Varien_Object $payment, $amount) {
		if( $this->_debug ) Mage::log('capture()', null, 'authnetcim.log');
	
		$post = Mage::app()->getRequest()->getParam('payment');
		$profile_id = 0;
		
		if( !empty($post['payment_id']) && empty($post['cc_number']) ) {
			$profile_id = intval( $post['payment_id'] );
			
			$payment->getOrder()->setExtCustomerId( $profile_id )->save();
		}
		
		$trans_id = explode( ':', $payment->getOrder()->getExtOrderId() );
		$type     = !empty($trans_id[1]) ? 'profileTransPriorAuthCapture' : 'profileTransAuthCapture';
		
		// Set 'save card' checkbox if checked
		if( ( isset( $post['save_card'] ) && $post['save_card'] == 1 ) || $profile_id > 0 || !empty( $trans_id[1] ) ) {
			$payment->setSaveCard( true );
		}
		
		// Handle partial-invoice with expired auth
		if( $type == 'profileTransPriorAuthCapture' && $payment->getOrder()->getTotalPaid() > 0 ) {
			$type = 'profileTransCaptureOnly';
		}
		
		// Grab the invoice in case partial invoicing
		$invoice = Mage::registry('current_invoice');
		if( !is_null( $invoice ) ) {
			$this->_invoice = $invoice;
		}
		
		return $this->bill( $payment, $amount, $type );
	}

	/**
	 * Refund a transaction
	 */
	public function refund(Varien_Object $payment, $amount) {
		if( $this->_debug ) Mage::log('refund()', null, 'authnetcim.log');
		
		// Grab the invoice in case partial invoicing
		$creditmemo = Mage::registry('current_creditmemo');
		if( !is_null( $creditmemo ) ) {
			$this->_invoice = $creditmemo->getInvoice();
		}
		
		// Never unsave the card here.
		$payment->setSaveCard( true );
		
		return $this->bill( $payment, $amount, 'profileTransRefund' );
	}

	/**
	 * Void a payment
	 */
	public function void(Varien_Object $payment) {
		if( $this->_debug ) Mage::log('void()', null, 'authnetcim.log');
		
		try {
			$_customer   = Mage::getModel('customer/customer')->load( $payment->getOrder()->getCustomerId() );
			
			$profile_id  = $this->getProfileId( $_customer );
			$trans_id    = explode( ':', $payment->getOrder()->getExtOrderId() );
			
			$this->cim->setParameter( 'customerProfileId', $profile_id );
			$this->cim->setParameter( 'transId', $trans_id[0] );
			$this->cim->voidCustomerProfileTransaction();
			
			$this->checkCimErrors();
			
			$trans_id = $this->cim->getTransactionID() ? $this->cim->getTransactionID() : $trans_id[0].'-2';
			
			Mage::log( $this->cim->getDirectResponse(), null, 'authnetcim.log', true );
			
			$payment->getOrder()->setExtOrderId($trans_id);
			
			$payment->setTransactionId($trans_id)
					->setIsTransactionClosed(1)
					->setShouldCloseParentTransaction(1)
					->save();
		}
		catch (AuthnetCIMException $e) {
			Mage::log( $e->getMessage(), null, 'authnetcim.log', true );
		}
		
		return $this;
	}
	
	/**
	 * Cancel a payment
	 */
	public function cancel(Varien_Object $payment) {
		if( $this->_debug ) Mage::log('cancel()', null, 'authnetcim.log');
		
		return $this->void($payment);
	}
	
	/**
	 * Fetch transaction info -- for use with fraud detection
	 */
	public function fetchTransactionInfo(Mage_Payment_Model_Info $payment, $transactionId) {
		if( $this->_debug ) Mage::log('fetchTransactionInfo('.$transactionId.')', null, 'authnetcim.log');
		
		$transaction = $payment->getTransaction($transactionId);
		
		if (!$transaction->getAdditionalInformation('is_transaction_fraud')) {
			return parent::fetchTransactionInfo($payment, $transactionId);
		}
		
		$this->cim->clearParameters();
		$this->cim->setParameter( 'transaction_id', $transactionId );
		$this->cim->getTransactionDetails();
		
		$this->checkCimErrors( true );
		
		Mage::log( json_encode( (array)$this->cim->raw->transaction ), null, 'authnetcim.log' );
		
		if( (int)$this->cim->raw->transaction->responseCode == 1 ) { // Transaction approved
			$transaction->setAdditionalInformation( 'is_transaction_fraud', false );
			$payment->setIsTransactionApproved( true );
		}
		elseif( (int)$this->cim->raw->transaction->getResponseReasonCode == 254 ) { // Transaction pending review -> denied
			$payment->setIsTransactionDenied( true );
		}
		
		return parent::fetchTransactionInfo($payment, $transactionId);
	}
	
	/**
	 * Payment method available? Yes.
	 */
	public function isAvailable($quote=null) {
		return (bool)($this->getConfigData('active'));
	}
	
	/**
	 * Fetch current customer's payment profiles and masked
	 * card number if available.
	 */
	public function getPaymentInfo( $profile_id=0, $exclude=true ) {
		if( $this->_debug ) Mage::log('getPaymentInfo('.$profile_id.')', null, 'authnetcim.log');

		$_customer = $this->getCustomer();

		if( empty($profile_id) ) {
			$profile_id = $this->getProfileId($_customer);
		}
		
		if( !empty($profile_id) ) {
			$this->cim->setParameter( 'customerProfileId', $profile_id );
			$this->cim->getCustomerProfile();

			$this->checkCimErrors();

			if( $this->cim->getCode() == 'E00040' ) {
				$profile_id = $this->createCustomerProfile( $_customer );
				return $this->getPaymentInfo( $profile_id );
			}
			
			// Check for cards we don't have.
			$cards = Mage::getModel('authnetcim/card')->getCollection()
							->addFieldToFilter( 'customer_id', $_customer->getId() );
			
			$excludeIds = array();
			if( count($cards) > 0 ) {
				foreach( $cards as $card ) {
					$excludeIds[] = $card->getPaymentId();
				}
			}
			
			$info = array();
			if( count($this->cim->raw->profile->paymentProfiles) ) {
				foreach( $this->cim->raw->profile->paymentProfiles as $payment ) {
					if( $exclude && in_array( $payment->customerPaymentProfileId, $excludeIds ) ) {
						continue;
					}
					
					$a = new Varien_Object();
					$a->setPaymentId( $payment->customerPaymentProfileId );
					$a->setCardNumber( $payment->payment->creditCard->cardNumber );
					$info[] = $a;
				}
			}
			
			return $info;
		}
		else {
			return false;
		}
	}
	
	/**
	 * Fetch a payment profile by ID.
	 */
	public function getPaymentInfoById( $payment_id, $raw=false, $profile_id=0 ) {
		if( $this->_debug ) Mage::log('getPaymentInfoById('.$payment_id.')', null, 'authnetcim.log');

		if( intval( $profile_id ) < 1 ) {
			$profile_id = $this->getProfileId( $this->getCustomer() );
		}
		
		if( !empty($profile_id) && !empty($payment_id) ) {
			$this->cim->clearParameters();
			$this->cim->setParameter( 'customerProfileId', $profile_id );
			$this->cim->setParameter( 'customerPaymentProfileId', $payment_id );
			$this->cim->getCustomerPaymentProfile();

			$this->checkCimErrors();

			if( $this->cim->getCode() == 'E00040' ) {
				Mage::log( 'CIM: '.$this->cim->responses, null, 'authnetcim.log', true );
				return false;
			}
			
			if( $raw ) {
				return $this->cim->raw->paymentProfile;
			}
			
			$a = new Varien_Object();
			if( count($this->cim->raw->paymentProfile) ) {
				$a->setPaymentId( $this->cim->raw->paymentProfile->customerPaymentProfileId );
				$a->setCardNumber( $this->cim->raw->paymentProfile->payment->creditCard->cardNumber );
			}
			
			return $a;
		}
		else {
			return new Varien_Object();
		}
	}
	
	/**
	 * Fetch full payment profile data for a customer.
	 */
	public function getPaymentProfiles( $profile_id=0, $exclude=true ) {
		if( $this->_debug ) Mage::log('getPaymentProfiles('.$profile_id.')', null, 'authnetcim.log');
		
		$this->initializeApi();

		$_customer = $this->getCustomer();
		
		if( empty($profile_id) ) {
			$profile_id = $this->getProfileId($_customer);
		}
		
		if( !empty($profile_id) ) {
			$this->cim->setParameter( 'customerProfileId', $profile_id );
			$this->cim->getCustomerProfile();

			$this->checkCimErrors();

			if( $this->cim->getCode() == 'E00040' ) {
				$profile_id = $this->createCustomerProfile( $_customer );
				return $this->getPaymentProfiles( $profile_id );
			}
			
			// Check for cards we don't have.
			$cards = Mage::getModel('authnetcim/card')->getCollection()
							->addFieldToFilter( 'customer_id', $_customer->getId() );
			
			if( $exclude && count($cards) > 0 ) {
				$excludeIds = array();
				foreach( $cards as $card ) {
					$excludeIds[] = $card->getPaymentId();
				}
				
				$remove = array();
				$i = 0;
				foreach( $this->cim->raw->profile->paymentProfiles as $card ) {
					if( in_array( $card->customerPaymentProfileId, $excludeIds ) ) {
						$remove[] = $i;
					}
					$i++;
				}
				
				$remove = array_reverse($remove);
				foreach( $remove as $i ) {
					unset( $this->cim->raw->profile->paymentProfiles[ $i ] );
				}
			}
			
			return $this->cim->raw->profile->paymentProfiles;
		}
		else {
			return false;
		}
	}
	
	/**
	 * Generate an Authorize.net CIM Payment Profile from My Account form.
	 */
	public function createCustomerPaymentProfileFromForm( $input, $profile_id=0 ) {
		if( $this->_debug ) Mage::log('createCustomerPaymentProfileFromForm()', null, 'authnetcim.log');
		
		try {
			$_customer = $this->getCustomer();
			
			if( $profile_id < 1 ) {
				$profile_id = $this->getProfileId( $_customer );
			}
			
			$this->cim->setParameter( 'customerProfileId', $profile_id );
			$this->cim->setParameter( 'billToFirstName', $input['firstname'] );
			$this->cim->setParameter( 'billToLastName', $input['lastname'] );
			$this->cim->setParameter( 'billToAddress', $input['address1'] );
			$this->cim->setParameter( 'billToCity', $input['city'] );
			$this->cim->setParameter( 'billToState', $input['state'] );
			$this->cim->setParameter( 'billToZip', $input['zip'] );
			$this->cim->setParameter( 'billToCountry', $input['country'] );
			$this->cim->setParameter( 'cardNumber', $input['cc_number'] );
			if( isset($input['cc_cid']) && !empty($input['cc_cid']) )
				$this->cim->setParameter( 'cardCode', $input['cc_cid'] );
			$this->cim->setParameter( 'expirationDate', sprintf("%04d-%02d", $input['cc_exp_year'], $input['cc_exp_month']) );
			
			$this->cim->createCustomerPaymentProfile();
			$payment_id = $this->cim->getPaymentProfileId();
			
			$this->checkCimErrors( false );

			if( $this->cim->getCode() == 'E00040' ) {
				$profile_id = $this->createCustomerProfile( $_customer );
				return $this->createCustomerPaymentProfileFromForm( $input, $profile_id );
			}
			elseif( $this->cim->getCode() == 'E00039' || intval( $payment_id ) <= 0 ) {
				/**
				 * If we still have no ID, try to match it manually.
				 * AuthNet does not return the ID in its duplicate error message, contrary to documentation.
				 */
				$info = $this->getPaymentInfo( $profile_id, false );
				$lastFour = substr( $input['cc_number'], -4 );

				if( $info && is_array($info) > 0 ) {
					foreach( $info as $inf ) {
						if( $lastFour == substr( $inf->getCardNumber(), -4 ) ) {
							$payment_id = $inf->getPaymentId();
							
							$this->updateCustomerPaymentProfile( $payment_id, $input, $profile_id );
							break;
						}
					}
				}
				
				if( intval( $payment_id ) > 0 ) {
					$card = Mage::getModel('authnetcim/card')->load( $payment_id, 'payment_id' );
					
					if( $card->getId() > 0 ) {
						$card->delete();
					}
				}
			}
			
			if( intval( $payment_id ) <= 0 ) {
				$this->checkCimErrors( true );
			}
			
			return $payment_id;
		}
		catch (AuthnetCIMException $e) {
			Mage::log( $e->getMessage(), null, 'authnetcim.log', true );
		}
		
		return false;
	}
	
	/**
	 * Remove a customer's payment profile
	 */
	public function deletePaymentProfile( $payment_id, $profile_id=0, $really=true ) {
		if( $this->_debug ) Mage::log('deletePaymentProfile('.$payment_id.')', null, 'authnetcim.log');

		if( $profile_id < 1 ) {
			$profile_id = $this->getProfileId( $this->getCustomer() );
		}
		
		if( !empty($profile_id) ) {
			if( $really ) {
				$this->cim->setParameter( 'customerProfileId', $profile_id );
				$this->cim->setParameter( 'customerPaymentProfileId', $payment_id );
				$this->cim->deleteCustomerPaymentProfile();

				$this->checkCimErrors();
				
				if( $this->_debug ) Mage::log( "Deleted $payment_id.", null, 'authnetcim.log' );
			}
			else {
				$card = Mage::getModel('authnetcim/card');
				$card->setProfileId( $profile_id )
					 ->setPaymentId( $payment_id )
					 ->setCustomerId( $this->getCustomer()->getId() )
					 ->setAdded( time() )
					 ->save();
					 
				if( $this->_debug ) Mage::log( "Queued $payment_id for deletion.", null, 'authnetcim.log' );
			}
			
			/**
			 * Suspend any profiles using that card.
			 */
			$db			= Mage::getSingleton('core/resource')->getConnection('core_read');
			$rp_table	= Mage::getSingleton('core/resource')->getTableName('sales/recurring_profile');
			$sql		= $db->select()
							 ->from( $rp_table, array('internal_reference_id') )
							 ->where( 'method_code="authnetcim" AND (state="active" OR state="pending") AND additional_info LIKE "%'.intval($payment_id).'%"' );
			$data		= $db->fetchAll($sql);
			
			$count = 0;
			if( count($data) > 0 ) {
				foreach( $data as $pid ) {
					$profile	= Mage::getModel('sales/recurring_profile')->loadByInternalReferenceId( $pid['internal_reference_id'] );
					$adtl		= $profile->getAdditionalInfo();
					if( $adtl['payment_id'] == $payment_id ) {
						$profile->setState( Mage_Sales_Model_Recurring_Profile::STATE_SUSPENDED );
						$profile->save();
						$count++;
					}
				}
			}
			
			if( $count > 0 ) {
				Mage::log( "Card deleted; automatically suspended $count recurring profiles.", null, 'authnetcim.log' );
			}
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * Get or create customer profile ID
	 */
	protected function getProfileId($_customer, $payment=null) {
		if( $this->_debug ) Mage::log('getProfileId()', null, 'authnetcim.log');
		
		$profile_id = $_customer->getAuthnetcimProfileId();
		if( intval($profile_id) < 1 ) {
			$profile_id	= $this->createCustomerProfile( $_customer, $payment );
		}
		
		return !empty($profile_id) ? $profile_id : 0;
	}
	
	/**
	 * Generate an Authorize.net CIM customer profile.
	 */
	protected function createCustomerProfile($_customer, $payment=null) {
		if( $this->_debug ) Mage::log('createCustomerProfile()', null, 'authnetcim.log');
		
		try {
			$email 	= $_customer->getEmail();
			$uid 	= $_customer->getEntityId();

			/**
			 * If not logged in, we must be checking out as a guest--try to grab their info.
			 */
			if( empty($email) || $uid < 2 ) {
				$sess = Mage::getSingleton('core/session')->getData();

				if( $payment != null && $payment->getQuote() != null ) {
					$email 	= $payment->getQuote()->getCustomerEmail();
					$uid 	= is_numeric($payment->getQuote()->getCustomerId()) ? $payment->getQuote()->getCustomerId() : 0;
				}
				elseif( $payment != null && $payment->getOrder() != null ) {
					$email 	= $payment->getOrder()->getCustomerEmail();
					$uid 	= is_numeric($payment->getOrder()->getCustomerId()) ? $payment->getOrder()->getCustomerId() : 0;
				}
				elseif( isset($sess['visitor_data']) && !empty($sess['visitor_data']['quote_id']) ) {
					$quote 	= Mage::getModel('sales/quote')->load( $sess['visitor_data']['quote_id'] );

					$email 	= $quote->getCustomerEmail();
					$uid 	= is_numeric($quote->getCustomerId()) ? $quote->getCustomerId() : 0;
				}
				
				$_customer->setEmail( $email );
				$_customer->setEntityId( $uid );
			}

			/**
			 * Failsafe: We must have some email to go through here. The data might not
			 * actually be available.
			 */
			if( empty($email) ) {
				Mage::log("No customer email found; can't create a CIM profile.", null, 'authnetcim.log');
				return false;
			}

			$this->cim->setParameter( 'email', $email );
			$this->cim->setParameter( 'merchantCustomerId', $uid );
			$this->cim->createCustomerProfile();
			
			$profile_id = $this->cim->getProfileID();
			
			$this->checkCimErrors();

			/**
			 * Handle 'duplicate' errors
			 */
			if( strpos($this->cim->getResponse(), 'duplicate') !== false ) {
				$profile_id = preg_replace( '/[^0-9]/', '', $this->cim->getResponse() );
			}
			

			$_customer->setAuthnetcimProfileId( $profile_id );
			if( $_customer->getData('entity_id') > 0 ) {
				$_customer->save();
			}
			
			return $profile_id;
		}
		catch (AuthnetCIMException $e) {
			Mage::log( $e->getMessage(), null, 'authnetcim.log', true );
			return false;
		}
	}
	
	/**
	 * Generate an Authorize.net CIM payment profile.
	 */
	protected function createCustomerPaymentProfile($_customer, $payment, $profile_id=0) {
		if( $this->_debug ) Mage::log('createCustomerPaymentProfile()', null, 'authnetcim.log');
		
		try {
			$order		= $payment->getOrder();
			$billing	= $order->getBillingAddress();
			
			if( empty($profile_id) ) {
				$profile_id = $this->getProfileId($_customer, $payment);
			}

			if( !empty($order) ) {
				$billing_id = $order->getExtCustomerId();
			}
			else {
				$billing_id = 0;
			}
			
			$this->cim->clearParameters();
			
			$wasDuplicate = false;
			
			/**
			 * If we have no payment profile, create one.
			 */
			if( intval($billing_id) <= 0 && !empty($profile_id) && !empty($order) && $payment->getCcNumber() ) {
				$this->cim->setParameter( 'customerProfileId', $profile_id );
				$this->cim->setParameter( 'billToFirstName', $billing->getFirstname() );
				$this->cim->setParameter( 'billToLastName', $billing->getLastname() );
				$this->cim->setParameter( 'billToAddress', $billing->getStreet(1) );
				$this->cim->setParameter( 'billToCity', $billing->getCity() );
				$this->cim->setParameter( 'billToState', $billing->getRegion() );
				$this->cim->setParameter( 'billToZip', $billing->getPostcode() );
				$this->cim->setParameter( 'billToCountry', $billing->getCountry() );
				if($billing->getTelephone())
					$this->cim->setParameter( 'billToPhoneNumber', $billing->getTelephone() );
				if($billing->getFax())
					$this->cim->setParameter( 'billToFaxNumber', $billing->getFax() );
				$this->cim->setParameter( 'cardNumber', $payment->getCcNumber() );
				if($payment->getCcCid())
					$this->cim->setParameter( 'cardCode', $payment->getCcCid() );
				$this->cim->setParameter( 'expirationDate', sprintf("%04d-%02d", $payment->getCcExpYear(), $payment->getCcExpMonth()) );
				
				$this->cim->createCustomerPaymentProfile();
				$billing_id = $this->cim->getPaymentProfileId();

				$this->checkCimErrors();
				
				/**
				 * Handle 'duplicate' errors
				 */
				if( strpos($this->cim->getResponse(), 'duplicate') !== false ) {
					$billing_id		= preg_replace( '/[^0-9]/', '', $this->cim->getResponse() );
					$wasDuplicate	= true;
				}
			}
			/**
			 * If we do have a payment profile, update it.
			 */
			elseif( intval($billing_id) > 0 && !empty($profile_id) && !empty($order) ) {
				$card		= $this->getPaymentInfoById( $billing_id, false, $profile_id );
				
				if( $card && $card->getCardNumber() != '' ) {
					$data = array(
						'firstname'		=> $billing->getFirstname(),
						'lastname'		=> $billing->getLastname(),
						'address1'		=> $billing->getStreet(1),
						'city'			=> $billing->getCity(),
						'state'			=> $billing->getRegion(),
						'zip'			=> $billing->getPostcode(),
						'country'		=> $billing->getCountry(),
						'cc_number'		=> $card->getCardNumber()
					);

					$this->updateCustomerPaymentProfile( $billing_id, $data, $profile_id );
				}
			}
			
			$errorMsg = $this->cim->getResponse();
			$errorCode = $this->cim->getCode();

			/**
			 * If we still have no ID, try to match it manually.
			 * AuthNet does not return the ID in its duplicate error message, contrary to documentation.
			 */
			if( intval($billing_id) <= 0 ) {
				$info = $this->getPaymentInfo( $profile_id, false );
				$lastFour = substr( $payment->getCcNumber(), -4 );

				if( $info && is_array($info) > 0 ) {
					foreach( $info as $inf ) {
						if( $lastFour == substr( $inf->getCardNumber(), -4 ) ) {
							$billing_id		= $inf->getPaymentId();
							$wasDuplicate	= true;
							break;
						}
					}
				}
			}
			
			/**
			 * Do we already have this card not-stored?
			 */
			if( intval( $billing_id ) > 0 ) {
				$card = Mage::getModel('authnetcim/card')->load( $billing_id, 'payment_id' );
				
				if( $card->getId() > 0 ) {
					$card->delete();
				}
			}
			
			/**
			 * Did we have a duplicate ID? We should update card info then.
			 * Could have a new CVV or expiration date.
			 */
			if( $wasDuplicate === true && intval( $billing_id ) > 0 && !empty($profile_id) && !empty($order) && $payment->getCcNumber() ) {
				$this->cim->setParameter( 'customerProfileId', $profile_id );
				$this->cim->setParameter( 'customerPaymentProfileId', $billing_id );
				$this->cim->setParameter( 'billToFirstName', $billing->getFirstname() );
				$this->cim->setParameter( 'billToLastName', $billing->getLastname() );
				$this->cim->setParameter( 'billToAddress', $billing->getStreet(1) );
				$this->cim->setParameter( 'billToCity', $billing->getCity() );
				$this->cim->setParameter( 'billToState', $billing->getRegion() );
				$this->cim->setParameter( 'billToZip', $billing->getPostcode() );
				$this->cim->setParameter( 'billToCountry', $billing->getCountry() );
				if($billing->getTelephone())
					$this->cim->setParameter( 'billToPhoneNumber', $billing->getTelephone() );
				if($billing->getFax())
					$this->cim->setParameter( 'billToFaxNumber', $billing->getFax() );
				$this->cim->setParameter( 'cardNumber', $payment->getCcNumber() );
				if($payment->getCcCid())
					$this->cim->setParameter( 'cardCode', $payment->getCcCid() );
				$this->cim->setParameter( 'expirationDate', sprintf("%04d-%02d", $payment->getCcExpYear(), $payment->getCcExpMonth()) );
				
				$this->cim->updateCustomerPaymentProfile();

				$this->checkCimErrors();
			}

			/**
			 * Bad profile ID -- must have changed API logins. Clear and try again.
			 */
			if( $errorCode == 'E00040' ) {
				$new_profile_id = $this->createCustomerProfile( $_customer, $payment );
				
				if( $new_profile_id != $profile_id ) {
					return $this->createCustomerPaymentProfile( $_customer, $payment, $new_profile_id );
				}
			}

			if( intval($billing_id) <= 0 ) {
				Mage::log( 'Unable to get/create payment ID.', null, 'authnetcim.log', true );
				Mage::log( 'CIM: '.$this->cim->responses, null, 'authnetcim.log', true );
				Mage::throwException( "Authorize.Net CIM Gateway: Payment failed. " . $errorMsg );
			}
			
			return $billing_id;
		}
		catch (AuthnetCIMException $e) {
			Mage::log( $e->getMessage(), null, 'authnetcim.log', true );
		}
		
		return false;
	}
	
	/**
	 * Modify an Authorize.net CIM payment profile.
	 */
	public function updateCustomerPaymentProfile($billing_id, $data, $profile_id=0) {
		if( $this->_debug ) Mage::log('updateCustomerPaymentProfile('.$billing_id.')', null, 'authnetcim.log');
		
		try {
			if( $profile_id < 1 ) {
				$profile_id = $this->getProfileId( $this->getCustomer() );
			}

			if( !empty($profile_id) && !empty($billing_id) ) {
				if( !empty($data) ) {
					$this->cim->setParameter( 'customerProfileId', $profile_id );
					$this->cim->setParameter( 'customerPaymentProfileId', $billing_id );
					$this->cim->setParameter( 'billToFirstName', $data['firstname'] );
					$this->cim->setParameter( 'billToLastName', $data['lastname'] );
					$this->cim->setParameter( 'billToAddress', $data['address1'] );
					$this->cim->setParameter( 'billToCity', $data['city'] );
					$this->cim->setParameter( 'billToState', $data['state'] );
					$this->cim->setParameter( 'billToZip', $data['zip'] );
					$this->cim->setParameter( 'billToCountry', $data['country'] );
					
					if( !empty($data['cc_cid']) )
						$this->cim->setParameter( 'cardCode', $data['cc_cid'] );
						
					if( !empty($data['cc_exp_year']) && !empty($data['cc_exp_month']) )
						$this->cim->setParameter( 'expirationDate', sprintf("%04d-%02d", $data['cc_exp_year'], $data['cc_exp_month']) );
					else
						$this->cim->setParameter( 'expirationDate', 'XXXX' );
					
					$this->cim->setParameter( 'cardNumber', (string)$data['cc_number'] );
					
					$this->cim->updateCustomerPaymentProfile();
				}
			}

			$this->checkCimErrors();
			
			if( $this->cim->isError() ) {
				Mage::log( 'CIM: '.$this->cim->responses, null, 'authnetcim.log', true );
				Mage::throwException( $this->cim->getResponse() );
			}
			
			return $billing_id;
		}
		catch (AuthnetCIMException $e) {
			Mage::log( $e->getMessage(), null, 'authnetcim.log', true );
		}
		
		return false;
	}
	
	/**
	 * Generate an authorize or capture transaction from existing profiles.
	 */
	protected function bill( $payment, $amount, $type = 'profileTransAuthOnly' ) {
		if( $this->_debug ) Mage::log('bill(), type='.$type, null, 'authnetcim.log');
		
		$this->initializeApi();
		
		try {
			$this->cim->clearParameters();

			$_customer  = Mage::getModel('customer/customer')->load( $payment->getOrder()->getCustomerId() );
			
			$profile_id = $this->getProfileId( $_customer, $payment );
			$payment_id = $this->createCustomerPaymentProfile( $_customer, $payment );
			$trans_id   = explode( ':', $payment->getOrder()->getExtOrderId() );
			
			// Handle transaction ID for partial invoicing
			if( !is_null( $this->_invoice ) && $this->_invoice->getTransactionId() != '' ) {
				$trans_id[0] = $this->_invoice->getTransactionId();
			}
			
			if( empty($profile_id) || empty($payment_id) ) {
				Mage::log( "\n".$this->cim->responses, null, 'authnetcim.log', true );
				Mage::throwException( "Unable to create CIM profile. Please try again." );
			}

			if( $amount <= 0 ) {
				return $this;
			}
			
			$this->cim->setParameter( 'invoiceNumber', $payment->getOrder()->getIncrementId() );
			$this->cim->setParameter( 'amount', round( $amount, 4 ) );
			
			if( $payment->getOrder()->getBaseTaxAmount() && ( $type == 'profileTransAuthOnly' || $type == 'profileTransAuthCapture' ) )
				$this->cim->setParameter( 'taxAmount', round( $payment->getOrder()->getBaseTaxAmount(), 4 ) );
			
			if( $payment->getBaseShippingAmount() )
				$this->cim->setParameter( 'shipAmount', round( $payment->getBaseShippingAmount(), 4 ) );
			
			if( !empty($trans_id[1]) )
				$this->cim->setParameter( 'approvalCode', $trans_id[1] );
			
			$this->cim->setParameter( 'customerProfileId', $profile_id );
			$this->cim->setParameter( 'customerPaymentProfileId', $payment_id );

			// Handle PriorAuth with no transaction ID--never authorized.
			if( empty($trans_id[0]) && $type == 'profileTransPriorAuthCapture' ) {
				$type = 'profileTransAuthCapture';
			}

			if( $type == 'profileTransRefund' || $type == 'profileTransPriorAuthCapture' ) {
				$this->cim->setParameter( 'transId', $trans_id[0] );
			}
			
			$this->cim->createCustomerProfileTransaction( $type );

			$this->checkCimErrors();
			
			if( $this->cim->isError() || ( $type != 'profileTransRefund' && ( !$this->cim->getTransactionID() || !$this->cim->getAuthCode() ) ) ) {
				Mage::log( "\n".$this->cim->responses, null, 'authnetcim.log', true );
				Mage::throwException( "Authorize.Net CIM Gateway: Transaction failed. ".$this->cim->getResponse() );
			}
			
			// Check for fraud status
			if( $this->cim->getResponseType() == 4 ) {
				$payment->setIsTransactionPending(true)
						->setIsFraudDetected(true)
						->setTransactionAdditionalInfo( 'is_transaction_fraud', true );
			}
			
			// Record transaction result
			$response = $this->getCimResponse();
			$response->setProfileId( (int) $profile_id )
					 ->setPaymentId( (int) $payment_id );
			
			// If we need to, don't save the card
			if( $payment->getSaveCard() == false ) {
				$card = Mage::getModel('authnetcim/card');
				$card->setProfileId( $profile_id )
					 ->setPaymentId( $payment_id )
					 ->setCustomerId( $payment->getOrder()->getCustomerId() )
					 ->setAdded( time() )
					 ->save();
			}
			
			$payment->setTransactionId( $this->cim->getTransactionID() )
					->setCcLast4( $this->cim->getCcLast4() )
					->setCcType( $this->cim->getCcType() )
					->setAdditionalInformation( $response->getData() );
			
			if( $type == 'profileTransAuthOnly' ) {
				$payment->setIsTransactionClosed(0);
			}
			else {
				$payment->setIsTransactionClosed(1);
			}
			
			if( !in_array( $type, array( 'profileTransRefund', 'profileTransPriorAuthCapture', 'profileTransCaptureOnly' ) ) ) {
				$payment->getOrder()->setExtOrderId( $this->cim->getTransactionID().':'.$this->cim->getAuthCode() );
				
				if( !$payment->getIsFraudDetected() ) {
					$payment->getOrder()->setState( $this->getConfigData('order_status') )
										->setStatus( $this->getConfigData('order_status') );
				}
			}

			$payment->getOrder()->setExtCustomerId( $payment_id )
								->save();

			Mage::log( $this->cim->getDirectResponse(), null, 'authnetcim.log', true );
		}
		catch (AuthnetCIMException $e) {
			Mage::log( $e->getMessage(), null, 'authnetcim.log', true );
		}
		
		return $this;
	}
	
	/**
	 * Parse Authorize.Net direct response into object
	 */
	protected function getCimResponse() {
		$result = new Varien_Object;
		$r 		= explode( $this->cim->getDelimiter(), str_replace('"','',$this->cim->getDirectResponse()) );
		
		if( count($r) > 0 ) {
			$result->setResponseCode((int)$r[0])
				->setResponseSubcode((int)$r[1])
				->setResponseReasonCode((int)$r[2])
				->setResponseReasonText($r[3])
				->setApprovalCode($r[4])
				->setAvsResultCode($r[5])
				->setTransactionId($r[6])
				->setInvoiceNumber($r[7])
				->setDescription($r[8])
				->setAmount($r[9])
				->setMethod($r[10])
				->setTransactionType($r[11])
				->setCustomerId($r[12])
				->setMd5Hash($r[37])
				->setCardCodeResponseCode($r[38])
				->setCAVVResponseCode( (isset($r[39])) ? $r[39] : null)
				->setAccNumber($r[50])
				->setCardType($r[51])
				->setSplitTenderId($r[52])
				->setRequestedAmount($r[53])
				->setBalanceOnCard($r[54]);
		}
		
		return $result;
	}

	/**
	 * Handle game-over errors
	 */
	public function checkCimErrors( $err=false ) {
		$from = Mage::getStoreConfig('trans_email/ident_general/email');
		$code = $this->cim->getCode();
		
		// Bad login ID / trans key
		if( $code == 'E00007' ) {
			$subj = 'Authorize.Net CIM Payment Module - Invalid API details';
			$body = "Warning: Your Authorize.net CIM API Login ID or Transaction Key appears to be incorrect, or you may be using live credentials with test mode enabled. The payment module is unable to authenticate properly. CIM purchasing will not work properly until this is fixed.";
			mail( $from, $subj, $body, "From: " . $from . "\r\n" );
		}

		// CIM not enabled
		if( $code == 'E00044' ) {
			$subj = 'Authorize.Net CIM Payment Module - CIM not enabled';
			$body = "Warning: CIM is not enabled on your Authorize.net account. CIM purchasing will not work properly until this is fixed.";
			mail( $from, $subj, $body, "From: " . $from . "\r\n" );
		}

		// Generic error
		if( $this->cim->isError() && !empty($code) ) {
			Mage::log('API error: '.$code.': '.$this->cim->getResponse(), null, 'authnetcim.log', true );
			
			if( $err ) {
				Mage::throwException( 'Authorize.Net CIM Gateway: '.$this->cim->getResponse() );
			}
		}
	}
}
