<?php
require_once 'IWD/Opc/controllers/JsonController.php';
class IWD_AddressVerification_JsonController extends IWD_Opc_JsonController
{
	var $_cur_layout = null;
	
    protected function _getUpdatedLayout()
    {
    	$this->_initLayoutMessages('checkout/session');
        if ($this->_cur_layout === null)
        {
            $layout = $this->getLayout();
            $update = $layout->getUpdate();            
            $update->load('checkout_onepage_index');
            
            $layout->generateXml();
            $layout->generateBlocks();
            $this->_cur_layout = $layout;
        }

        return $this->_cur_layout;
    }
	
	protected function _getAddressCandidatesHtml()
    {
    	$layout	= $this->_getUpdatedLayout();
        return $layout->getBlock('checkout.addresscandidates')->toHtml();
    }
	
    public function getVerification()
    {
        return Mage::getSingleton('addressverification/verification');
    }

    /**
     * save checkout billing address
     */
    public function saveBillingAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        
        if ($this->getRequest()->isPost())
        {
	    	if (!Mage::helper('addressverification')->isAddressVerificationEnabled())
	    	{
	    		parent::saveBillingAction();
	    		return;
	    	}
	    	else
	    	{
	    		$validation_enabled	= Mage::helper('addressverification')->getEnabledVerification();
	    		if(!$validation_enabled)
	    		{
	    			parent::saveBillingAction();
	    			return;
	    		}
	    		else
	    		{
	    			$this->getVerification()->setVerificationLib($validation_enabled);

	    			$this->getVerification()->getCheckout()->setShippingWasValidated(false);
	    			$this->getVerification()->getCheckout()->setShippingValidationResults(false);
	    			
		            $data = $this->getRequest()->getPost('billing', array());
		            $customerAddressId = $this->getRequest()->getPost('billing_address_id', false);

		            $allow_not_valid	= Mage::helper('addressverification')->allowNotValidAddress(); // if not valid addresses allowed for checkout
		            
			        if($this->_checkChangedAddress($data, 'Billing', $customerAddressId, $validation_enabled))
			        {
	        			$this->getVerification()->getCheckout()->setBillingWasValidated(false);
	        			// for save method
						$this->getVerification()->getCheckout()->setSaveBillingWasValidated(false);
			        }

			        if($allow_not_valid)
			        	$bill_was_validated	= $this->getVerification()->getCheckout()->getBillingWasValidated();
			        else
			        	$bill_was_validated	= false;
			        
			        if($bill_was_validated)
			        {
			        	parent::saveBillingAction();
			        	return;
			        }
			        else
			        {
			            if (isset($data['email'])) {
			                $data['email'] = trim($data['email']);
			            }

			            $result = $this->getOnepage()->saveBilling($data, $customerAddressId);

			            if (!isset($result['error']))
			            {
			            	// run validation
					        $bill_validate	= $this->getVerification()->validate_address('Billing');
					        if($bill_validate)
					        	$this->getVerification()->getCheckout()->setBillingWasValidated(true);
					        else
					        	$this->getVerification()->getCheckout()->setBillingWasValidated(false);

					        // check if exist validation errors
					        if(isset($bill_validate) && is_array($bill_validate)
					        	&& isset($bill_validate['error']) && !empty($bill_validate['error']))
					        {
								$this->getVerification()->getCheckout()->setShowValidationResults('billing');
        
			                    $result['address_validation'] = $this->_getAddressCandidatesHtml();

			                    $this->getVerification()->getCheckout()->setShowValidationResults(false);

						        // clear validation results
						        $this->getVerification()->getCheckout()->setBillingValidationResults(false);
        	
								$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
								return;
							}
								
					        // clear validation results
					        $this->getVerification()->getCheckout()->setBillingValidationResults(false);

							parent::saveBillingAction();						        
							return;
			            }
			        }	    			
	    		}
	    	}
        }
	}
    
    /**
     * Shipping address save action
     */
    public function saveShippingAction()
    {
        if ($this->_expireAjax()) {
            return;
        }

        if ($this->getRequest()->isPost())
        {
	    	if (!Mage::helper('addressverification')->isAddressVerificationEnabled())
	    	{
	    		parent::saveShippingAction();
	    		return;
	    	}
	    	else
	    	{
	    		$validation_enabled	= Mage::helper('addressverification')->getEnabledVerification();
	    		if(!$validation_enabled)
	    		{
	    			parent::saveShippingAction();
	    			return;
	    		}
	    		else
	    		{
	    			$this->getVerification()->setVerificationLib($validation_enabled);

		            $data = $this->getRequest()->getPost('shipping', array());
		            $customerAddressId = $this->getRequest()->getPost('shipping_address_id', false);

		            $allow_not_valid	= Mage::helper('addressverification')->allowNotValidAddress(); // if not valid addresses allowed for checkout
		            
			        if($this->_checkChangedAddress($data, 'Shipping', $customerAddressId, $validation_enabled))
			        {
	        			$this->getVerification()->getCheckout()->setShippingWasValidated(false);
	        			// for save mothod
						$this->getVerification()->getCheckout()->setSaveShippingWasValidated(false);
			        }

			        if($allow_not_valid)
			        	$ship_was_validated	= $this->getVerification()->getCheckout()->getShippingWasValidated();
			        else
			        	$ship_was_validated	= false;
			        
			        if($ship_was_validated)
			        {
			        	parent::saveShippingAction();
			        	return;
			        }
			        else
			        {
			            $result = $this->getOnepage()->saveShipping($data, $customerAddressId);

			            if (!isset($result['error']))
			            {
			            	// run validation
					        $ship_validate	= $this->getVerification()->validate_address('Shipping');
					        if($ship_validate)
					        	$this->getVerification()->getCheckout()->setShippingWasValidated(true);
					        else
					        	$this->getVerification()->getCheckout()->setShippingWasValidated(false);

					        // check if exist validation errors
					        if(isset($ship_validate) && is_array($ship_validate)
					        	&& isset($ship_validate['error']) && !empty($ship_validate['error']))
					        {
								$this->getVerification()->getCheckout()->setShowValidationResults('shipping');
        
								$result['address_validation'] = $this->_getAddressCandidatesHtml();

			                    $this->getVerification()->getCheckout()->setShowValidationResults(false);

						        // clear validation results
						        $this->getVerification()->getCheckout()->setShippingValidationResults(false);
        	
								$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
								return;
							}

					        // clear validation results
					        $this->getVerification()->getCheckout()->setShippingValidationResults(false);

							parent::saveShippingAction();						        
							return;
			            }
			        }	    			
	    		}		
            }
        }
    }

    public function saveOrderAction()
    {
        if ($this->_expireAjax()) {
            return;
        }

	    if (!Mage::helper('addressverification')->isAddressVerificationEnabled())
	    {
	    	parent::saveOrderAction();
	    	return;
	    }
        
   		$validation_enabled	= Mage::helper('addressverification')->getEnabledVerification();
   		if(!$validation_enabled)
   		{
   			parent::saveOrderAction();
   			return;
   		}

   		$allow_not_valid	= Mage::helper('addressverification')->allowNotValidAddress(); // if not valid addresses allowed for checkout

        if($allow_not_valid)
        {
        	$bill_was_validated	= $this->getVerification()->getCheckout()->getSaveBillingWasValidated();
        	$ship_was_validated	= $this->getVerification()->getCheckout()->getSaveShippingWasValidated();
        }
        else
        {
        	$bill_was_validated	= false;
        	$ship_was_validated	= false;
        }

        if($bill_was_validated && $ship_was_validated)
        {
        	parent::saveOrderAction();
        	return;
        }
        ////

		$this->getVerification()->setVerificationLib($validation_enabled);

		$ship_data = array();
        $this->getVerification()->getCheckout()->setSaveBillingWasValidated(false);

		if(!$this->getVerification()->getQuote()->isVirtual())
		{
			$ship_data = $this->getVerification()->getQuote()->getShippingAddress()->getData();
			if (isset($ship_data['same_as_billing']) && $ship_data['same_as_billing'] == 1)
				$this->getVerification()->getCheckout()->setSaveShippingWasValidated(true);
			else
        		$this->getVerification()->getCheckout()->setSaveShippingWasValidated(false);
		}

    	/// Address Verification
        if($allow_not_valid)
        {
        	$bill_was_validated	= $this->getVerification()->getCheckout()->getSaveBillingWasValidated();
        	$ship_was_validated	= $this->getVerification()->getCheckout()->getSaveShippingWasValidated();
        }
        else
        {
        	$bill_was_validated	= false;
        	$ship_was_validated	= false;
        }

        if(!$bill_was_validated)
        {
	        $bill_validate	= $this->getVerification()->validate_address('Billing');
	        if($bill_validate)
	        	$this->getVerification()->getCheckout()->setSaveBillingWasValidated(true);
	        else
	        	$this->getVerification()->getCheckout()->setSaveBillingWasValidated(false);
        }
        
        if(!$this->getVerification()->getQuote()->isVirtual())
        {
	        if(!$ship_was_validated)
	        {
	        	// check if shipping is the same as billing
				if (isset($ship_data['same_as_billing']) && $ship_data['same_as_billing'] == 1)
					$this->getVerification()->getCheckout()->setSaveShippingWasValidated(true);
				else
				{
		        	$ship_validate	= $this->getVerification()->validate_address('Shipping');
			        if($ship_validate)
			        	$this->getVerification()->getCheckout()->setSaveShippingWasValidated(true);
			        else
			        	$this->getVerification()->getCheckout()->setSaveShippingWasValidated(false);
				}
	        }
        }

        // check if exist validation results for any address
        if((isset($bill_validate) && is_array($bill_validate)) || (isset($ship_validate) && is_array($ship_validate)))
        {
        	if((isset($bill_validate) && isset($bill_validate['error']) && !empty($bill_validate['error'])) ||
        	   (isset($ship_validate) && isset($ship_validate['error']) && !empty($ship_validate['error'])) 
			)
			{
				$error_type = '';
				if(isset($bill_validate) && isset($bill_validate['error']) && !empty($bill_validate['error']))
					$error_type = 'billing';
				if(isset($ship_validate) && isset($ship_validate['error']) && !empty($ship_validate['error']))
				{
					if($error_type == 'billing')
						$error_type = 'both';
					else
						$error_type = 'shipping';
				}
				
				$this->getVerification()->getCheckout()->setShowValidationResults($error_type);
        
				$result['address_validation'] = $this->_getAddressCandidatesHtml();

				$this->getVerification()->getCheckout()->setShowValidationResults(false);

		        // clear validation results
		        $this->getVerification()->getCheckout()->setBillingValidationResults(false);
		        $this->getVerification()->getCheckout()->setShippingValidationResults(false);

				$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
				return;
			}
        }
        
        // clear validation results
        $this->getVerification()->getCheckout()->setBillingValidationResults(false);
        $this->getVerification()->getCheckout()->setShippingValidationResults(false);
    	//// End Address Verification

        parent::saveOrderAction();
    }
    
    protected function _checkChangedAddress($data, $addr_type = 'Billing', $addr_id = false, $check_city_street = false)
    {
    	$method	= "get{$addr_type}Address";
        $address = $this->getVerification()->getQuote()->{$method}();

        if(!$addr_id)
        {
	        if(($address->getRegionId()	!= $data['region_id']) || ($address->getPostcode() != $data['postcode']) || ($address->getCountryId() != $data['country_id']))
	        	return true;

        	// if need to compare street and city
        	if($check_city_street)
        	{
        		// check street address
        		$street1	= $address->getStreet();
        		$street2	= $data['street'];

        		if(is_array($street1))
        		{
        			if(is_array($street2))
        			{
        				if(trim(strtolower($street1[0])) != trim(strtolower($street2[0])))
        				{
        					return true;
        				}
        				if(isset($street1[1]))
        				{
        					if(isset($street2[1]))
        					{
        						if(trim(strtolower($street1[1])) != trim(strtolower($street2[1])))
        							return true;        						
        					}
        					else
        					{
        						if(!empty($street1[1]))
        							return true;
        					}
        				}
        				else
        				{
        					$s21	= trim($street2[1]);
        					if(isset($street2[1]) && !empty($s21))
        						return true;
        				}
        			}
        			else
        			{
        				if(trim(strtolower($street1[0])) != trim(strtolower($street2)))
        					return true;
        			}
        		}
        		else
        		{
        			if(is_array($street2))
        			{
        				if(trim(strtolower($street1)) != trim(strtolower($street2[0])))
        					return true;
        			}
        			else
        			{
        				if(trim(strtolower($street1)) != trim(strtolower($street2)))
        					return true;
        			}
        		}
        		
        		// check city
        		$add_city	= $address->getCity();
        		$add_city	= trim(strtolower($add_city));
        		if( $add_city	!= trim(strtolower($data['city'])))
        			return true;
        	}
        	
			return false;
        }
        else{
        	if($addr_id != $address->getCustomerAddressId())
        		return true;
        	else
        		return false;
        }
    }
    
}