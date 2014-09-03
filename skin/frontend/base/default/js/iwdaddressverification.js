;

var $j = false;
if(typeof(jQuery) != 'undefined' && jQuery != undefined)
	$j = jQuery;

IWD_AV = {		
	init: function(){	
		IWD.OPC.Plugin.event('responseSaveOrder', IWD_AV.validationResponseSaveOrder);
	},
	
	validationResponseSaveOrder: function(response){
		
		if (typeof(response.address_validation) != "undefined"){
			$j('#checkout-address-validation-load').empty().html(response.address_validation);
			IWD.OPC.Checkout.hideLoader();
			IWD.OPC.Checkout.unlockPlaceOrder();
			return;
		}
	}
};

var is_opc_checkout = false;

if($j)
{	
	$j(document).ready(function()
	{
		// detect if IWD OPC exists
	    if(typeof(IWD) != 'undefined' && IWD != undefined)
	    {
	    	if(typeof(IWD.OPC) != 'undefined' && IWD.OPC != undefined && IWD.OPC != '' && IWD.OPC)
	    	{
	    		is_opc_checkout = true;
	    	}
	    }
		
	    if(is_opc_checkout)
	    {
	    	IWD_AV.init();
	    }
	});
}

function continue_verification()
{
	var va_bill_exist = false;
	var va_ship_exist = false;
	
	var va_bill_id = -1;
    $$('.va_bill_id').each(function(el){
    	va_bill_exist = true; // check if exist any radiobox
        if (el.checked)
        	va_bill_id = el.value; // get checked value 
    });

	var va_ship_id = -1;
    $$('.va_ship_id').each(function(el){
    	va_ship_exist = true; // check if exist any radiobox
        if (el.checked)
        	va_ship_id = el.value; // get checked value
    });

    // check if user made all choices
    if(va_bill_exist && va_bill_id == -1)
    {
    	alert('Please, make your choice');
    	return false;
    }

    if(va_ship_exist && va_ship_id == -1)
    {
    	alert('Please, make your choice');
    	return false;
    }
    //
    
    close_verification();

	if(va_bill_id != -1 && va_bill_id != 'use_original_address')
	{
		copy_valid_address('billing', va_bill_id);
		// open new address form
		if(!is_opc_checkout)
			billing.newAddress(true);
		else
		{
			// for IWD OPC
			IWD.OPC.saveOrderStatus = false;
			
			var obj =$('billing-address-select');
			if(obj != null && obj != undefined && typeof(obj) != 'undefined')
			{
				if(obj.value != '') // customer has address, new to open new form
				{
					iwdopc_new_address('billing', true);
				}
			}
		}
	}
	
	if(va_ship_id != -1  && va_ship_id != 'use_original_address')
	{
		copy_valid_address('shipping', va_ship_id);	
		// open new address form
		if(!is_opc_checkout)
			shipping.newAddress(true);
		else
		{
			// for IWD OPC
			IWD.OPC.saveOrderStatus = false;
			
			var obj =$('shipping-address-select');
			if(obj != null && obj != undefined && typeof(obj) != 'undefined')
			{
				if(obj.value != '') // customer has address, new to open new form
				{
					iwdopc_new_address('shipping', true);
				}
			}			
		}
	}
	
	// for IWD OPC
	if(is_opc_checkout)
	{
		var continue_iwdopc = false;

		// update 
		var ship_updated = false;
		if(va_bill_id != -1  && va_bill_id != 'use_original_address')
		{
		    if ($('shipping:same_as_billing') && $('shipping:same_as_billing').checked)
		    	IWD.OPC.Billing.setBillingForShipping(true);
		    
	    	ship_updated = true;
	    	IWD.OPC.Billing.validateForm();
		}
		else if(va_bill_id == 'use_original_address')
		{
			continue_iwdopc = 'billing';
		}
		
		if(!ship_updated)
		{
			if(va_ship_id != -1 && va_ship_id != 'use_original_address')
			{
				continue_iwdopc = false;
			
				IWD.OPC.Shipping.validateForm();
			}
			else if(va_ship_id == 'use_original_address')
			{
				continue_iwdopc = 'shipping';
			}			
		}

		// if choosed original address, need some special logic
		if(continue_iwdopc != '')
		{
			if(IWD.OPC.saveOrderStatus == true)
				IWD.OPC.saveOrder();
			else
			{				
				if(continue_iwdopc == 'billing')
					IWD.OPC.Billing.validateForm();
				if(continue_iwdopc == 'shipping')
					IWD.OPC.Shipping.validateForm();
			}
		}
	}
}

function iwdopc_new_address(type, new_address)
{
    if (new_address){    	
        var selectElement = $j('#'+type+'-address-select');
        if (selectElement) {
            selectElement.val('');
        }
        $j('#'+type+'-new-address-form').show();
    } else {
    	$j('#'+type+'-new-address-form').hide();
    }
}

function close_verification()
{
	$("iwdPopupOverlay").hide();
	$('address-verification-results').hide();
}
function copy_valid_address(type, candidate_id)
{
	if(candidate_id != '')
		candidate_id = '_'+candidate_id;

	$(type+':street1').value	= $('va_'+type+'_street'+candidate_id).value;
	if($(type+':street2'))
		$(type+':street2').value	= '';
	if($(type+':street3'))
		$(type+':street3').value	= '';
	$(type+':city').value		= $('va_'+type+'_city'+candidate_id).value;
	$(type+':region_id').value	= $('va_'+type+'_region'+candidate_id).value;
	$(type+':postcode').value	= $('va_'+type+'_postcode'+candidate_id).value;	
}
