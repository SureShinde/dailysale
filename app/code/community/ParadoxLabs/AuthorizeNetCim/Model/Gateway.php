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
 * @package		AuthorizeNetCim
 * @author		Ryan Hoerr <magento@paradoxlabs.com>
 * @license		http://store.paradoxlabs.com/license.html
 */

class ParadoxLabs_AuthorizeNetCim_Model_Gateway extends ParadoxLabs_TokenBase_Model_Gateway
{
	protected $_code		= 'authnetcim';
	
	protected $_endpointLive	= 'https://api.authorize.net/xml/v1/request.api';
	protected $_endpointTest	= 'https://apitest.authorize.net/xml/v1/request.api';
	
	/**
	 * $_fields sets validation for each input.
	 * 
	 * key => array(
	 *    'maxLength' => int,
	 *    'noSymbols' => true|false,
	 *    'charMask'  => (allowed characters in regex form),
	 *    'enum'      => array( values )
	 * )
	 */
	protected $_fields		= array(
		'amount'					=> array(  ),
		'accountNumber'				=> array( 'maxLength' => 17, 'charMask' => '\d' ),
		'accountType'				=> array( 'enum' => array( 'checking', 'savings', 'businessChecking' ) ),
		'approvalCode'				=> array( 'maxLength' ),
		'bankName'					=> array( 'maxLength' => 50 ),
		'billToAddress'				=> array( 'maxLength' => 60, 'noSymbols' => true ),
		'billToCity'				=> array( 'maxLength' => 40, 'noSymbols' => true ),
		'billToCompany'				=> array( 'maxLength' => 50, 'noSymbols' => true ),
		'billToCountry'				=> array( 'maxLength' => 60, 'noSymbols' => true ),
		'billToFaxNumber'			=> array( 'maxLength' => 25, 'charMask' => '\d\(\)\-\.' ),
		'billToFirstName'			=> array( 'maxLength' => 50, 'noSymbols' => true ),
		'billToLastName'			=> array( 'maxLength' => 50, 'noSymbols' => true ),
		'billToPhoneNumber'			=> array( 'maxLength' => 25, 'charMask' => '\d\(\)\-\.' ),
		'billToState'				=> array( 'maxLength' => 40, 'noSymbols' => true ),
		'billToZip'					=> array( 'maxLength' => 20, 'noSymbols' => true ),
		'cardCode'					=> array( 'maxLength' => 4, 'charMask' => '\d' ),
		'cardNumber'				=> array( 'maxLength' => 16, 'charMask' => 'X\d' ),
		'customerIp'				=> array(  ),
		'customerPaymentProfileId'	=> array(  ),
		'customerProfileId'			=> array(  ),
		'customerShippingAddressId'	=> array(  ),
		'customerType'				=> array( 'enum' => array( 'individual', 'business' ) ),
		'description'				=> array( 'maxLength' => 255 ),
		'dutyAmount'				=> array(  ),
		'dutyDescription'			=> array( 'maxLength' => 255 ),
		'dutyName'					=> array( 'maxLength' => 31 ),
		'echeckType'				=> array( 'enum' => array( 'CCD', 'PPD', 'TEL', 'WEB', 'ARC', 'BOC' ) ),
		'email'						=> array( 'maxLength' => 255 ),
		'expirationDate'			=> array( 'maxLength' => 7 ),
		'invoiceNumber'				=> array( 'maxLength' => 20, 'noSymbols' => true ),
		'loginId'					=> array( 'maxLength' => 20, 'noSymbols' => true ),
		'merchantCustomerId'		=> array( 'maxLength' => 20 ),
		'nameOnAccount'				=> array( 'maxLength' => 22 ),
		'purchaseOrderNumber'		=> array( 'maxLength' => 25, 'noSymbols' => true ),
		'recurringBilling'			=> array( 'enum' => array( 'true', 'false' ) ),
		'refId'						=> array( 'maxLength' => 20 ),
		'routingNumber'				=> array( 'maxLength' => 9, 'charMask' => '\d' ),
		'shipAmount'				=> array(  ),
		'shipDescription'			=> array( 'maxLength' => 255 ),
		'shipName'					=> array( 'maxLength' => 31 ),
		'shipToAddress'				=> array( 'maxLength' => 60, 'noSymbols' => true ),
		'shipToCity'				=> array( 'maxLength' => 40, 'noSymbols' => true ),
		'shipToCompany'				=> array( 'maxLength' => 50, 'noSymbols' => true ),
		'shipToCountry'				=> array( 'maxLength' => 60, 'noSymbols' => true ),
		'shipToFaxNumber'			=> array( 'maxLength' => 25, 'charMask' => '\d\(\)\-\.' ),
		'shipToFirstName'			=> array( 'maxLength' => 50, 'noSymbols' => true ),
		'shipToLastName'			=> array( 'maxLength' => 50, 'noSymbols' => true ),
		'shipToPhoneNumber'			=> array( 'maxLength' => 25, 'charMask' => '\d\(\)\-\.' ),
		'shipToState'				=> array( 'maxLength' => 40, 'noSymbols' => true ),
		'shipToZip'					=> array( 'maxLength' => 20, 'noSymbols' => true ),
		'splitTenderId'				=> array( 'maxLength' => 6 ),
		'taxAmount'					=> array(  ),
		'taxDescription'			=> array( 'maxLength' => 255 ),
		'taxExempt'					=> array( 'enum' => array( 'true', 'false' ) ),
		'taxName'					=> array( 'maxLength' => 31 ),
		'transactionKey'			=> array( 'maxLength' => 16, 'noSymbols' => true ),
		'transactionType'			=> array( 'enum' => array( 'profileTransAuthCapture', 'profileTransCaptureOnly', 'profileTransAuthOnly', 'profileTransRefund', 'profileTransPriorAuthCapture', 'profileTransVoid' ) ),
		'transId'					=> array( 'charMask' => '\d' ),
		'validationMode'			=> array( 'enum' => array( 'liveMode', 'testMode', 'none' ) ),
	);
	
	/**
	 * Set the API credentials so they go through validation.
	 */
	public function clearParameters()
	{
		parent::clearParameters();
		
		$this->setParameter( 'loginId', $this->_defaults['login'] );
		$this->setParameter( 'transactionKey', $this->_defaults['password'] );
		
		return $this;
	}
	
	/**
	 * Send the given request to Authorize.Net and process the results.
	 */
	protected function _runTransaction( $request, $params )
	{
		$auth = array(
			'@attributes'				=> array(
				'xmlns'						=> 'AnetApi/xml/v1/schema/AnetApiSchema.xsd',
			),
			'merchantAuthentication'	=> array(
				'name'						=> $this->getParameter('loginId'),
				'transactionKey'			=> $this->getParameter('transactionKey'),
			)
		);
		
		$xml = $this->_arrayToXml( $request, $auth + $params );
		
		$this->_lastRequest = $xml;
		
		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, $this->_endpoint );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $curl, CURLOPT_HTTPHEADER, array("Content-Type: text/xml") );
		curl_setopt( $curl, CURLOPT_HEADER, 0 );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, $xml );
		curl_setopt( $curl, CURLOPT_POST, 1 );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt( $curl, CURLOPT_TIMEOUT, 15 );
		$this->_lastResponse = curl_exec( $curl );
		
		if( $this->_lastResponse && !curl_errno( $curl ) ) {
			$this->_log .= 'REQUEST: ' . $this->_sanitizeLog( $xml ) . "\n";
			$this->_log .= 'RESPONSE: ' . $this->_sanitizeLog( $this->_lastResponse ) . "\n";
			
			$this->_lastResponse = $this->_xmlToArray( $this->_lastResponse );
			
			/**
			 * Check for basic errors.
			 */
			if( $this->_lastResponse['messages']['resultCode'] != 'Ok' ) {
				$errorCode		= $this->_lastResponse['messages']['message']['code'];
				$errorText		= $this->_lastResponse['messages']['message']['text'];
				
				// Log and spit out generic error. Skip certain warnings we can handle.
				if( !empty($errorCode) && !in_array( $errorCode, array( 'E00027', 'E00039', 'E00040' ) ) ) {
					Mage::helper('tokenbase')->log( $this->_code, sprintf( "API error: %s: %s\n%s", $errorCode, $errorText, $this->_log ) );
					Mage::throwException( Mage::helper('tokenbase')->__( sprintf( 'Authorize.Net CIM Gateway: %s (%s)', $errorText, $errorCode ) ) );
				}
			}
			
			curl_close($curl);
		}
		else {
			Mage::helper('tokenbase')->log( $this->_code, sprintf( 'CURL Connection error: ' . curl_error($curl) . ' (' . curl_errno($curl) . ')' . "\n" . 'REQUEST: ' . $this->_sanitizeLog( $xml ) ) );
			Mage::throwException( Mage::helper('tokenbase')->__( sprintf( 'Authorize.Net CIM Gateway Connection error: %s (%s)', curl_error($curl), curl_errno($curl) ) ) );
		}
		
		return $this->_lastResponse;
	}
	
	/**
	 * Mask certain values in the XML for secure logging purposes.
	 */
	protected function _sanitizeLog( $string )
	{
		$maskAll	= array( 'cardCode' );
		$maskFour	= array( 'cardNumber', 'name', 'transactionKey', 'routingNumber', 'accountNumber' );
		
		foreach( $maskAll as $val ) {
			$string	= preg_replace( '#' . $val . '>(.+?)</' . $val . '#', $val . '>XXX</' . $val, $string );
		}
		
		foreach( $maskFour as $val ) {
			$start	= strpos( $string, '<' . $val . '>' );
			$end	= strpos( $string, '</' . $val . '>', $start );
			$tagLen	= strlen( $val ) + 2;
			
			if( $start !== false && $end > $start ) {
				$string = substr_replace( $string, 'XXXX', $start + $tagLen, $end - 4 - ($start + $tagLen) );
			}
		}
		
		return $string;
	}
	
	/**
	 * Turn transaction results and directResponse into a usable object.
	 */
	protected function _interpretTransaction( $transactionResult )
	{
		/**
		 * Turn the direct response string into an array, as best we can.
		 */
		$directResponse = isset( $transactionResult['directResponse'] ) ? $transactionResult['directResponse'] : '';
		if( strlen( $directResponse ) > 1 ) {
			// Strip out quotes, we don't want any.
			$directResponse			= str_replace( '"', '', $directResponse );
			
			// Use the second character as the delimiter. The first will always be the one-digit response code.
			$directResponse	= explode( substr( $directResponse, 1, 1 ), $directResponse );
		}
		
		if( empty( $directResponse) || count( $directResponse ) == 0 ) {
			Mage::throwException( Mage::helper('tokenbase')->__( 'Authorize.Net CIM Gateway: Transaction failed; no direct response.' ) );
		}
		
		/**
		 * Turn the array into a keyed object and infer some things.
		 */
		$data	= array(
			'response_code'				=> (int)$directResponse[0],
			'response_subcode'			=> (int)$directResponse[1],
			'response_reason_code'		=> (int)$directResponse[2],
			'response_reason_text'		=> $directResponse[3],
			'approval_code'				=> $directResponse[4],
			'auth_code'					=> $directResponse[4],
			'avs_result_code'			=> $directResponse[5],
			'transaction_id'			=> $directResponse[6],
			'invoice_number'			=> $directResponse[7],
			'description'				=> $directResponse[8],
			'amount'					=> $directResponse[9],
			'method'					=> $directResponse[10],
			'transaction_type'			=> $directResponse[11],
			'customer_id'				=> $directResponse[12],
			'md5_hash'					=> $directResponse[37],
			'card_code_response_code'	=> $directResponse[38],
			'cavv_response_code'		=> $directResponse[39],
			'acc_number'				=> $directResponse[50],
			'card_type'					=> $directResponse[51],
			'split_tender_id'			=> $directResponse[52],
			'requested_amount'			=> $directResponse[53],
			'balance_on_card'			=> $directResponse[54],
			'profile_id'				=> $this->getParameter('customerProfileId'),
			'payment_id'				=> $this->getParameter('customerPaymentProfileId'),
			'is_fraud'					=> false,
			'is_error'					=> false,
		);
		
		$response = new Varien_Object();
		$response->setData( $data );
		
		if( $response->getResponseCode() == 4 ) {
			$response->setIsFraud( true );
		}
		
		if( !in_array( $response->getResponseReasonCode() , array( 16, 54 ) ) ) { // Response 54 is 'can't refund; txn has not settled.' 16 is 'cannot find txn' (expired). We deal with them.
			if( $transactionResult['messages']['resultCode'] != 'Ok' 							// Error result
				|| in_array( $response->getResponseCode(), array( 2, 3 ) )						// OR error/decline response code
				|| ( !in_array( $response->getTransactionType(), array( 'credit', 'void' ) )	// OR no transID or auth code on a charge txn
					&& ( $response->getTransactionId() == '' || $response->getAuthCode() == '' ) ) ) {
				$response->setIsError( true );
				
				Mage::helper('tokenbase')->log( $this->_code, sprintf( "Transaction error: %s\n%s\n%s", $response->getResponseReasonText(), json_encode( $response->getData() ), $this->_log ) );
				Mage::throwException( Mage::helper('tokenbase')->__( 'Authorize.Net CIM Gateway: Transaction failed. ' . $response->getResponseReasonText() ) );
			}
		}
		
		return $response;
	}
	
	/**
	 * Magento-exposed actions
	 */
	public function setCard( ParadoxLabs_TokenBase_Model_Card $card )
	{
		$this->setParameter( 'email', $card->getCustomerEmail() );
		$this->setParameter( 'merchantCustomerId', $card->getCustomerId() );
		$this->setParameter( 'customerProfileId', $card->getProfileId() );
		$this->setParameter( 'customerPaymentProfileId', $card->getPaymentId() );
		$this->setParameter( 'customerIp', $card->getCustomerIp() );
		
		return parent::setCard( $card );
	}
	
	public function authorize( $payment, $amount )
	{
		$this->setParameter( 'transactionType', 'profileTransAuthOnly' );
		$this->setParameter( 'amount', $amount );
		$this->setParameter( 'invoiceNumber', $payment->getOrder()->getIncrementId() );
		
		if( $this->getIsReauthorize() !== true ) {
			if( $payment->getOrder()->getBaseTaxAmount() ) {
				$this->setParameter( 'taxAmount', $payment->getOrder()->getBaseTaxAmount() );
			}
			
			if( $payment->getBaseShippingAmount() ) {
				$this->setParameter( 'shipAmount', $payment->getBaseShippingAmount() );
			}
		}
		
		if( $payment->hasCcCid() && $payment->getCcCid() != '' ) {
			$this->setParameter( 'cardCode', $payment->getCcCid() );
		}
		
		$result		= $this->createCustomerProfileTransaction();
		$response	= $this->_interpretTransaction( $result );
		
		return $response;
	}
	
	public function capture( $payment, $amount, $realTransactionId=null )
	{
		if( $this->getHaveAuthorized() && $this->getAuthCode() != '' ) {
			$this->setParameter( 'transactionType', 'profileTransPriorAuthCapture' );
			
			if( !is_null( $realTransactionId ) ) {
				$this->setParameter( 'transId', $realTransactionId );
			}
			elseif( $payment->getTransactionId() != '' ) {
				$this->setParameter( 'transId', $payment->getTransactionId() );
			}
			elseif( $this->hasTransactionId() ) {
				$this->setParameter( 'transId', $this->getTransactionId() );
			}
		}
		elseif( $this->getHaveAuthorized() ) {
			$this->setParameter( 'transactionType', 'profileTransCaptureOnly' );
		}
		else {
			$this->setParameter( 'transactionType', 'profileTransAuthCapture' );
		}
		
		$this->setParameter( 'amount', $amount );
		$this->setParameter( 'invoiceNumber', $payment->getOrder()->getIncrementId() );
		
		if( $this->hasAuthCode() ) {
			$this->setParameter( 'approvalCode', $this->getAuthCode() );
		}
		
		if( $payment->getOrder()->getBaseTotalPaid() <= 0 ) {
			if( $payment->getOrder()->getBaseTaxAmount() ) {
				$this->setParameter( 'taxAmount', $payment->getOrder()->getBaseTaxAmount() );
			}
			
			if( $payment->getBaseShippingAmount() ) {
				$this->setParameter( 'shipAmount', $payment->getBaseShippingAmount() );
			}
		}
		
		if( $payment->hasCcCid() && $payment->getCcCid() != '' ) {
			$this->setParameter( 'cardCode', $payment->getCcCid() );
		}
		
		$result		= $this->createCustomerProfileTransaction();
		$response	= $this->_interpretTransaction( $result );
		
		/**
		 * Check for and handle 'transaction not found' error (expired authorization).
		 */
		if( $response->getResponseReasonCode() == 16 && $this->getParameter('transId') != '' ) {
			Mage::helper('tokenbase')->log( $this->_code, sprintf( "Transaction not found. Attempting to recapture.\n%s", json_encode( $response->getData() ) ) );
			
			$this->unsAuthCode()
				 ->unsHaveAuthorized()
				 ->clearParameters()
				 ->setCard( $this->getCard() );
			
			$response = $this->capture( $payment, $amount, '' );
		}
		
		return $response;
	}
	
	public function refund( $payment, $amount, $realTransactionId=null )
	{
		$this->setParameter( 'transactionType', 'profileTransRefund' );
		$this->setParameter( 'amount', $amount );
		$this->setParameter( 'invoiceNumber', $payment->getOrder()->getIncrementId() );
		
		if( $payment->getBaseShippingAmount() ) {
			$this->setParameter( 'shipAmount', $payment->getBaseShippingAmount() );
		}
		
		if( !is_null( $realTransactionId ) ) {
			$this->setParameter( 'transId', $realTransactionId );
		}
		elseif( $payment->getTransactionId() != '' ) {
			$this->setParameter( 'transId', $payment->getTransactionId() );
		}
		elseif( $this->hasTransactionId() ) {
			$this->setParameter( 'transId', $this->getTransactionId() );
		}
		
		$result		= $this->createCustomerProfileTransaction();
		$response	= $this->_interpretTransaction( $result );
		
		/**
		 * Check for 'transaction unsettled' error.
		 */
		if( $response->getResponseReasonCode() == 54 ) {
			/**
			 * Is this a full refund? If so, just void it. Nobody will see the difference.
			 */
			if( $amount == $payment->getCreditmemo()->getInvoice()->getBaseGrandTotal() ) {
				return $this->clearParameters()->setCard( $this->getCard() )->void( $payment );
			}
			else {
				$response->setIsError( true );
				
				Mage::helper('tokenbase')->log( $this->_code, sprintf( "Transaction error: %s\n%s\n%s", $response->getResponseReasonText(), json_encode( $response->getData() ), $this->_log ) );
				Mage::throwException( Mage::helper('tokenbase')->__( 'Authorize.Net CIM Gateway: Transaction failed. ' . $response->getResponseReasonText() ) );
			}
		}
		
		return $response;
	}
	
	public function void( $payment )
	{
		$this->setParameter( 'transactionType', 'profileTransVoid' );
		
		if( $payment->getTransactionId() != '' ) {
			$this->setParameter( 'transId', $payment->getTransactionId() );
		}
		elseif( $this->hasTransactionId() ) {
			$this->setParameter( 'transId', $this->getTransactionId() );
		}
		
		$result		= $this->createCustomerProfileTransaction();
		$response	= $this->_interpretTransaction( $result );
		
		return $response;
	}
	
	public function fraudUpdate( $payment, $transactionId )
	{
		$this->setParameter( 'transId', $transactionId );
		
		$result		= $this->getTransactionDetails();
		
		$response	= new Varien_Object();
		$response->setData( array( 'is_approved' => false, 'is_denied' => false ) );
		
		if( (int)$result['transaction']['responseReasonCode'] == 254 || $result['transaction']['transactionStatus'] == 'voided' ) { // Transaction pending review -> denied
			$response->setIsDenied( true );
		}
		elseif( (int)$result['transaction']['responseCode'] == 1 ) {
			$response->setIsApproved( true );
		}
		
		return $response;
	}
	
	/**
	 * API methods: See the Authorize.Net CIM XML documentation.
	 */
	public function createCustomerProfile()
	{
		$params = array(
			'profile'					=> array(
				'merchantCustomerId'		=> intval( $this->getParameter('merchantCustomerId') ),
				'description'				=> $this->getParameter('description'),
				'email'						=> $this->getParameter('email'),
			),
		);
		
		$result = $this->_runTransaction( 'createCustomerProfileRequest', $params );
		
		if( isset( $result['customerProfileId'] ) ) {
			return $result['customerProfileId'];
		}
		elseif( isset( $result['messages']['message']['text'] ) && strpos( $result['messages']['message']['text'], 'duplicate' ) !== false ) {
			return preg_replace( '/[^0-9]/', '', $result['messages']['message']['text'] );
		}
		else {
			Mage::helper('tokenbase')->log( $this->_code, $this->_log );
			Mage::throwException( Mage::helper('tokenbase')->__( 'Authorize.Net CIM Gateway: Unable to create customer profile. %s', $result['messages']['message']['text'] ) );
		}
	}
	
	public function createCustomerPaymentProfile()
	{
		$params = array(
			'customerProfileId'			=> $this->getParameter('customerProfileId'),
			'paymentProfile'			=> array(
				'billTo'					=> array(
					'firstName'					=> $this->getParameter('billToFirstName'),
					'lastName'					=> $this->getParameter('billToLastName'),
					'company'					=> $this->getParameter('billToCompany'),
					'address'					=> $this->getParameter('billToAddress'),
					'city'						=> $this->getParameter('billToCity'),
					'state'						=> $this->getParameter('billToState'),
					'zip'						=> $this->getParameter('billToZip'),
					'country'					=> $this->getParameter('billToCountry'),
					'phoneNumber'				=> $this->getParameter('billToPhoneNumber'),
					'faxNumber'					=> $this->getParameter('billToFaxNumber'),
				),
				'payment'					=> array(),
			),
			'validationMode'			=> $this->getParameter('validationMode'),
		);
		
		if( $this->hasParameter('customerType') ) {
			$params['paymentProfile'] = array(
				'customerType'				=> $this->getParameter('customerType')
			) + $params['paymentProfile'];
		}
		
		if( $this->hasParameter('cardNumber') ) {
			$params['paymentProfile']['payment'] = array(
				'creditCard'				=> array(
					'cardNumber'				=> $this->getParameter('cardNumber'),
					'expirationDate'			=> $this->getParameter('expirationDate'),
				),
			);
			
			if( $this->hasParameter('cardCode') ) {
				$params['paymentProfile']['payment']['creditCard']['cardCode'] = $this->getParameter('cardCode');
			}
		}
		elseif( $this->hasParameter('accountNumber') ) {
			$params['paymentProfile']['payment'] = array(
				'bankAccount'				=> array(
					'accountType'				=> $this->getParameter('accountType'),
					'nameOnAccount'				=> $this->getParameter('nameOnAccount'),
					'echeckType'				=> $this->getParameter('echeckType'),
					'bankName'					=> $this->getParameter('bankName'),
					'routingNumber'				=> $this->getParameter('routingNumber'),
					'accountNumber'				=> $this->getParameter('accountNumber'),
				),
			);
		}
		
		$result = $this->_runTransaction( 'createCustomerPaymentProfileRequest', $params );
		
		if( isset( $result['customerPaymentProfileId'] ) ) {
			$paymentId = $result['customerPaymentProfileId'];
		}
		elseif( isset( $result['messages']['message']['text'] ) && strpos( $result['messages']['message']['text'], 'duplicate' ) !== false ) {
			/**
			 * Handle duplicate card errors. Painful process.
			 */
			
			$paymentId = preg_replace( '/[^0-9]/', '', $result['messages']['message']['text'] );
			
			/**
			 * If we still have no payment ID, try to match the duplicate manually.
			 * Authorize.Net does not return the ID in this duplicate error message, contrary to documentation.
			 */
			if( empty( $paymentId ) ) {
				$profile	= $this->getCustomerProfile();
				$lastFour	= substr( $this->getParameter('cardNumber'), -4 );
				
				if( isset( $profile['profile']['paymentProfiles'] ) && count( $profile['profile']['paymentProfiles'] ) > 0 ) {
					if( isset( $profile['profile']['paymentProfiles']['billTo'] ) ) {
						$card		= $profile['profile']['paymentProfiles'];
						$paymentId	= $card['customerPaymentProfileId'];
						
						// Update the card record to ensure CVV and expiry are up to date.
						$this->setParameter( 'customerPaymentProfileId', $paymentId );
						$this->updateCustomerPaymentProfile();
					}
					else {
						foreach( $profile['profile']['paymentProfiles'] as $card ) {
							if( isset( $card['payment']['creditCard'] ) && $lastFour == substr( $card['payment']['creditCard']['cardNumber'], -4 ) ) {
								$paymentId	= $card['customerPaymentProfileId'];
								
								// Update the card record to ensure CVV and expiry are up to date.
								$this->setParameter( 'customerPaymentProfileId', $paymentId );
								$this->updateCustomerPaymentProfile();
								
								break;
							}
						}
					}
				}
			}
		}
		
		if( empty( $paymentId ) ) {
			Mage::helper('tokenbase')->log( $this->_code, $this->_log );
			Mage::throwException( Mage::helper('tokenbase')->__( 'Authorize.Net CIM Gateway: Unable to create payment record.' ) );
		}
		
		return $paymentId;
	}
	
	public function createCustomerShippingAddress()
	{
		$params = array(
			'customerProfileId'			=> $this->getParameter('customerProfileId'),
			'address'					=> array(
				'firstName'					=> $this->getParameter('shipToFirstName'),
				'lastName'					=> $this->getParameter('shipToLastName'),
				'company'					=> $this->getParameter('shipToCompany'),
				'address'					=> $this->getParameter('shipToAddress'),
				'city'						=> $this->getParameter('shipToCity'),
				'state'						=> $this->getParameter('shipToState'),
				'zip'						=> $this->getParameter('shipToZip'),
				'country'					=> $this->getParameter('shipToCountry'),
				'phoneNumber'				=> $this->getParameter('shipToPhoneNumber'),
				'faxNumber'					=> $this->getParameter('shipToFaxNumber'),
			),
		);
		
		$result = $this->_runTransaction( 'createCustomerShippingAddressRequest', $params );
		
		if( isset( $result['customerAddressId'] ) ) {
			return $result['customerAddressId'];
		}
		elseif( isset( $result['messages']['message']['text'] ) && strpos( $result['messages']['message']['text'], 'duplicate' ) !== false ) {
			/**
			 * Handle duplicate address errors. blah.
			 */
			$profile	= $this->getCustomerProfile();
			
			if( isset( $profile['profile']['shipToList'] ) && count( $profile['profile']['shipToList'] ) > 0 ) {
				if( isset( $profile['profile']['shipToList']['customerAddressId'] ) ) {
					return $profile['profile']['shipToList']['customerAddressId'];
				}
				else {
					foreach( $profile['profile']['shipToList'] as $address ) {
						$isDuplicate	= true;
						$fields			= array( 'firstName', 'lastName', 'address', 'zip', 'phoneNumber' );
						
						foreach( $fields as $field ) {
							if( $address[ $field ] != $params['address'][ $field ] ) {
								$isDuplicate = false;
								break;
							}
						}
						
						if( $isDuplicate === true ) {
							return $address['customerAddressId'];
						}
					}
				}
			}
		}
		else {
			Mage::helper('tokenbase')->log( $this->_code, $this->_log );
			Mage::throwException( Mage::helper('tokenbase')->__( 'Authorize.Net CIM Gateway: Unable to create shipping address record.' ) );
		}
	}
	
	public function createCustomerProfileTransaction()
	{
		$type = $this->getParameter('transactionType');
		
		$params = array(
			'transaction'				=> array(
				$type						=> array(
				),
			),
			'extraOptions'				=> array( '@cdata' => 'x_duplicate_window=15' ),
		);
		
		if( $this->hasParameter('amount') ) {
			$params['transaction'][ $type ]['amount'] = $this->formatAmount( $this->getParameter('amount') );
		}
		
		// Add customer IP?
		if( $this->hasParameter('customerIp') ) {
			$params['extraOptions']['@cdata'] .= '&x_customer_ip=' . $this->getParameter('customerIp');
		}
		
		// Add tax amount?
		if( $this->hasParameter('taxAmount') ) {
			$params['transaction'][ $type ]['tax'] = array(
				'amount'				=> $this->formatAmount( $this->getParameter('taxAmount') ),
				'name'					=> $this->getParameter('taxName'),
				'description'			=> $this->getParameter('taxDescription'),
			);
		}
		
		// Add shipping amount?
		if( $this->hasParameter('shipAmount') ) {
			$params['transaction'][ $type ]['shipping'] = array(
				'amount'				=> $this->formatAmount( $this->getParameter('shipAmount') ),
				'name'					=> $this->getParameter('shipName'),
				'description'			=> $this->getParameter('shipDescription'),
			);
		}
		
		// Add duty amount?
		if( $this->hasParameter('dutyAmount') ) {
			$params['transaction'][ $type ]['duty'] = array(
				'amount'				=> $this->formatAmount( $this->getParameter('dutyAmount') ),
				'name'					=> $this->getParameter('dutyName'),
				'description'			=> $this->getParameter('dutyDescription'),
			);
		}
		
		// Add line items?
		if( !is_null( $this->_lineItems ) && count( $this->_lineItems ) > 0 ) {
			$params['transaction'][ $type ]['lineItems'] = array();
			
			foreach( $this->_lineItems as $item ) {
				if( $item instanceof Varien_Object == false ) {
					continue;
				}
				
				if( $item->getQty() > 0 ) {
					$qty = $item->getQty();
				}
				else {
					$qty = $item->getQtyOrdered();
				}
				
				if( $qty <= 0 || $item->getPrice() <= 0 ) {
					continue;
				}
				
				$params['transaction'][ $type ]['lineItems'][] = array(
					'itemId'				=> substr( $item->getSku(), 0, 31 ),
					'name'					=> substr( $item->getName(), 0, 31 ),
					'quantity'				=> $this->formatAmount( $qty ),
					'unitPrice'				=> $this->formatAmount( $item->getPrice() - $item->getDiscountAmount() ),
				);
			}
		}
		
		$params['transaction'][ $type ]['customerProfileId']		= $this->getParameter('customerProfileId');
		$params['transaction'][ $type ]['customerPaymentProfileId']	= $this->getParameter('customerPaymentProfileId');
		
		// Various other optional or conditional fields
		if( $this->hasParameter('customerShippingAddressId') ) {
			$params['transaction'][ $type ]['customerShippingAddressId'] = $this->getParameter('customerShippingAddressId');
		}
		
		if( $this->hasParameter('invoiceNumber') && $type != 'profileTransPriorAuthCapture' ) {
			$params['transaction'][ $type ]['order'] = array(
				'invoiceNumber'			=> $this->getParameter('invoiceNumber'),
				'description'			=> $this->getParameter('description'),
				'purchaseOrderNumber'	=> $this->getParameter('purchaseOrderNumber'),
			);
		}
		
		if( $this->hasParameter('cardCode') ) {
			$params['transaction'][ $type ]['cardCode'] = $this->getParameter('cardCode');
		}
		
		if( $this->hasParameter('transId') ) {
			$params['transaction'][ $type ]['transId'] = $this->getParameter('transId');
		}
		
		if( $this->hasParameter('splitTenderId') ) {
			$params['transaction'][ $type ]['splitTenderId'] = $this->getParameter('splitTenderId');
		}
		
		if( $this->hasParameter('approvalCode') && !in_array( $type, array( 'profileTransRefund', 'profileTransPriorAuthCapture', 'profileTransAuthOnly' ) ) ) {
			$params['transaction'][ $type ]['approvalCode'] = $this->getParameter('approvalCode');
		}
		
		return $this->_runTransaction( 'createCustomerProfileTransactionRequest', $params );
	}
	
	public function deleteCustomerProfile()
	{
		$params = array(
			'customerProfileId'			=> $this->getParameter('customerProfileId'),
		);
		
		return $this->_runTransaction( 'deleteCustomerProfileRequest', $params );
	}
	
	public function deleteCustomerPaymentProfile()
	{
		$params = array(
			'customerProfileId'			=> $this->getParameter('customerProfileId'),
			'customerPaymentProfileId'	=> $this->getParameter('customerPaymentProfileId'),
		);
		
		return $this->_runTransaction( 'deleteCustomerPaymentProfileRequest', $params );
	}
	
	public function deleteCustomerShippingAddress()
	{
		$params = array(
			'customerProfileId'			=> $this->getParameter('customerProfileId'),
			'customerShippingAddressId'	=> $this->getParameter('customerShippingAddressId'),
		);
		
		return $this->_runTransaction( 'deleteCustomerShippingAddressRequest', $params );
	}
	
	public function getCustomerProfileIds()
	{
		return $this->_runTransaction( 'getCustomerProfileIdsRequest', array() );
	}
	
	public function getCustomerProfile()
	{
		$params = array(
			'customerProfileId'			=> $this->getParameter('customerProfileId'),
		);
		
		return $this->_runTransaction( 'getCustomerProfileRequest', $params );
	}
	
	public function getCustomerPaymentProfile()
	{
		$params = array(
			'customerProfileId'			=> $this->getParameter('customerProfileId'),
			'customerPaymentProfileId'	=> $this->getParameter('customerPaymentProfileId'),
		);
		
		return $this->_runTransaction( 'getCustomerPaymentProfileRequest', $params );
	}
	
	public function getCustomerShippingAddress()
	{
		$params = array(
			'customerProfileId'			=> $this->getParameter('customerProfileId'),
			'customerShippingAddressId'	=> $this->getParameter('customerShippingAddressId'),
		);
		
		return $this->_runTransaction( 'getCustomerShippingAddressRequest', $params );
	}
	
	public function getTransactionDetails()
	{
		$params = array(
			'transId'					=> $this->getParameter('transId'),
		);
		
		return $this->_runTransaction( 'getTransactionDetailsRequest', $params );
	}
	
	public function updateCustomerProfile()
	{
		$params = array(
			'profile'					=> array(
				'merchantCustomerId'		=> $this->getParameter('merchantCustomerId'),
				'description'				=> $this->getParameter('description'),
				'email'						=> $this->getParameter('email'),
				'customerProfileId'			=> $this->getParameter('customerProfileId'),
			),
		);
		
		return $this->_runTransaction( 'updateCustomerProfileRequest', $params );
	}
	
	public function updateCustomerPaymentProfile( $type='credit' )
	{
		$params = array(
			'customerProfileId'			=> $this->getParameter('customerProfileId'),
			'paymentProfile'			=> array(
				'billTo'					=> array(
					'firstName'					=> $this->getParameter('billToFirstName'),
					'lastName'					=> $this->getParameter('billToLastName'),
					'company'					=> $this->getParameter('billToCompany'),
					'address'					=> $this->getParameter('billToAddress'),
					'city'						=> $this->getParameter('billToCity'),
					'state'						=> $this->getParameter('billToState'),
					'zip'						=> $this->getParameter('billToZip'),
					'country'					=> $this->getParameter('billToCountry'),
					'phoneNumber'				=> $this->getParameter('billToPhoneNumber'),
					'faxNumber'					=> $this->getParameter('billToFaxNumber'),
				),
				'payment'					=> array(),
				'customerPaymentProfileId'	=> $this->getParameter('customerPaymentProfileId'),
			),
		);
		
		if( $type == 'credit' ) {
			$params['paymentProfile']['payment'] = array(
				'creditCard'				=> array(
					'cardNumber'				=> $this->getParameter('cardNumber'),
					'expirationDate'			=> $this->getParameter('expirationDate'),
				),
			);
			
			if( $this->hasParameter('cardCode') ) {
				$params['paymentProfile']['payment']['creditCard']['cardCode'] = $this->getParameter('cardCode');
			}
		}
		elseif( $type == 'echeck' ) {
			$params['paymentProfile']['payment'] = array(
				'bankAccount'				=> array(
					'accountType'				=> $this->getParameter('accountType'),
					'nameOnAccount'				=> $this->getParameter('nameOnAccount'),
					'echeckType'				=> $this->getParameter('echeckType'),
					'bankName'					=> $this->getParameter('bankName'),
					'routingNumber'				=> $this->getParameter('routingNumber'),
					'accountNumber'				=> $this->getParameter('accountNumber'),
				),
			);
		}
		
		return $this->_runTransaction( 'updateCustomerPaymentProfileRequest', $params );
	}
	
	public function updateCustomerShippingAddress()
	{
		$params = array(
			'customerProfileId'			=> $this->getParameter('customerProfileId'),
			'address'					=> array(
				'firstName'					=> $this->getParameter('shipToFirstName'),
				'lastName'					=> $this->getParameter('shipToLastName'),
				'company'					=> $this->getParameter('shipToCompany'),
				'address'					=> $this->getParameter('shipToAddress'),
				'city'						=> $this->getParameter('shipToCity'),
				'state'						=> $this->getParameter('shipToState'),
				'zip'						=> $this->getParameter('shipToZip'),
				'country'					=> $this->getParameter('shipToCountry'),
				'phoneNumber'				=> $this->getParameter('shipToPhoneNumber'),
				'faxNumber'					=> $this->getParameter('shipToFaxNumber'),
				'customerShippingAddressId'	=> $this->getParameter('customerShippingAddressId'),
			),
		);
		
		return $this->_runTransaction( 'updateCustomerShippingAddressRequest', $params );
	}
	
	public function validateCustomerPaymentProfile()
	{
		$params = array(
			'customerProfileId'			=> $this->getParameter('customerProfileId'),
			'customerPaymentProfileId'	=> $this->getParameter('customerPaymentProfileId'),
			'customerShippingAddressId'	=> $this->getParameter('customerShippingAddressId'),
			'validationMode'			=> $this->getParameter('validationMode'),
		);
		
		return $this->_runTransaction( 'validateCustomerPaymentProfileRequest', $params );
	}
}
