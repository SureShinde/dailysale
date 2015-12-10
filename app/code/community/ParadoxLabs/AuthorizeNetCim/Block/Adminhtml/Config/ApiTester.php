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

class ParadoxLabs_AuthorizeNetCim_Block_Adminhtml_Config_ApiTester extends ParadoxLabs_TokenBase_Block_Adminhtml_Config_ApiTest
{
	protected $_code	= 'authnetcim';
	
	/**
	 * Test the API connection and report common errors.
	 */
	protected function _testApi() {
		$method = Mage::helper('payment')->getMethodInstance( $this->_code );
		$method->setStore( $this->_getStoreId() );
		
		// Don't bother if details aren't entered.
		if( $method->getConfigData('login') == '' || $method->getConfigData('trans_key') == '' ) {
			return 'Enter API credentials and save to test.';
		}
		
		$gateway = $method->gateway();
		
		try {
			// Run the test call -- simple profile request. It won't exist, that's okay.
			$gateway->setParameter( 'customerProfileId', '1' );
			$gateway->getCustomerProfile();
			
			return 'Authorize.Net CIM connected successfully.';
		}
		catch( Exception $e ) {
			/**
			 * Handle common configuration errors.
			 */
			
			$result		= $gateway->getLastResponse();
			$errorCode	= $result['messages']['message']['code'];
			
			// Bad login ID / trans key
			if( in_array( $errorCode, array( 'E00005', 'E00006', 'E00007', 'E00008' ) ) ) {
				return sprintf( 'Your API credentials are invalid. (%s)', $errorCode );
			}
			// Test mode active
			elseif( $errorCode == 'E00009' ) {
				return sprintf( 'Your account has test mode enabled. It must be disabled for CIM to work properly. (%s)', $errorCode );
			}
			// CIM not enabled
			elseif( $errorCode == 'E00044' ) {
				return sprintf( 'Your account does not have CIM enabled. Please contact your Authorize.Net support rep to resolve this. (%s)', $errorCode );
			}
			
			return $e->getMessage();
		}
	}
}
