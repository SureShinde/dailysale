/*!
 * LeadSpend Email Validation jQuery Plugin
 * jquery.leadspendemail version 0.2
 * 
 * Original author: @this-sam, @leadspend
 * Kudos to: @jtnotat
 * Licensed under the MIT license
 *
 * Requires jQuery
 */

;(function( $, window, document, undefined ){
	// Initialize defaults
	var pluginName = 'leadSpendEmail',
	defaults = {
		timeout: 5,
		debug: false,
		delaySubmit: true, // note: true expects a well-formed form! (i.e. email input and submit button inside form element)
		resultCallback: null
	};
	
	// Constructor
	function LeadSpendEmail( element, options ){
		this.element = element;
		this.resultElement = null;
		this.options = $.extend( {}, defaults, options );
		this._defaults = defaults;
		this._name = pluginName;
		
		this.apiUrl = "https://primary.api.leadspend.com/v2/validity/";
        
		// Actual jsonp call to the LeadSpend API
		this._jsonpValidateEmail = function( emailAddress ) {
			$.getJSON( this.apiUrl + encodeURIComponent( emailAddress ) + "?timeout=" + this.options.timeout + "&callback=?", null )
				.done( $.proxy( this._jsonpValidateEmailDone, this ) )
				.fail( $.proxy( this._jsonpValidateEmailFail, this ) );
		};
		
		// Called on completion of jsonp email validation call (to be called using $.proxy for proper context)
		this._jsonpValidateEmailDone = function( data, textStatus, jqXHR ){
			if ( this.options.debug ){
				console.log( "LeadSpend result: " );
				console.log( data );
			}
			
			this._setResultValue( data.result );
		};
		
		// Called on fail of jsonp email validation call (to be called using $.proxy for proper context)
		this._jsonpValidateEmailFail = function( jqXHR, textStatus, errorThrown ){
			this._setResultPending( false );
			
			if ( this.options.debug ){
				console.log( "Leadspend API $getJSON().fail() called. Logging jqXHR, textStatus, and errorThrown:" );
				console.log( jqXHR );
				console.log( textStatus );
				console.log( errorThrown );
				this._setResultValue( "error" );
			} else {
				this._setResultValue( "unknown" );
			}
		};
		
		// Creates the resultElement, where the email result is stored and accessible for any validation front/back end
		this._createResultElement = function(){			
			// get element ID, and NAME
			elementID = $( this.element ).attr( "id" );
			elementName = $( this.element ).attr( "name" );
			
			resultInputSuffix = "-result";
			
			// append options.suffix to each attr that is set and create the hidden input for the result
			if ( elementID ) {
				resultElementID = elementID + resultInputSuffix;
			} else {
				resultElementID = "";
			}
			
			if ( elementName ){
				resultElementName = elementName + resultInputSuffix;
			} else {
				resultElementName = "";
			}

            // Class attr will always be leadSpendEmail[+suffix]
            resultElementClass = "leadSpendEmail" + resultInputSuffix;


			// Class attr will always be leadSpendEmail[+suffix]
			resultElementClass = "leadSpendEmail" + resultInputSuffix;
			resultElementHtml = "<div class=\"" 	+ resultElementClass +
									   "\" id=\"" 	+ resultElementID +
									   "\" name=\"" + resultElementName + "</div>";

            resultElementHtml = "<div class=\"validation-advice\" id=\"" + resultElementID + "\">Please enter a valid email address.</div>";
			this.resultElement = $( resultElementHtml );
			this.resultElement.hide();
			$( this.element ).after( this.resultElement );
		}
		
		// Set state of email input to pending
		this._setResultPending = function( resultPending ){
			if ( resultPending ){
				this.resultPending = true;
			} else{
				this.resultPending = false;
			}
		};
		
		this._setResultAddress = function( emailAddress ){
			this.resultAddress = emailAddress;
		};
		
		// Sets the value in the resultElement.
		this._setResultValue = function( value ){
			// Only update value and trigger the change event if new value is different
			if ( $( this.resultElement ).val() != value ){
				if ( this.options.debug ) console.log( "Setting resultInput value to: " + value );
				
				// Set actual state of element
				$( this.resultElement ).val( value );
				
				// Update internal state to pending
				if ( value == "pending" ){
					this._setResultPending( true );
				} else {
					this._setResultPending( false );
				}
				
				// Trigger change event for external use/binding
				$( this.resultElement ).trigger( "change" );

				// Trigger focusout event for email address field 
				// $( this.element ).trigger( "focusout" );
				// TODO: trigger other events? 
				
				// call the resultCallback (if it has been set)
				if ( this.options.resultCallback ){
					this.options.resultCallback( this.element, this.resultElement );
				}
				
				// handle delaySubmit
				if ( this.options.delaySubmit && value != "pending" && this.submitPressed ){
					this._handleDelaySubmit();
				}
			}

            if ( $( this.resultElement ).val() != "pending" ){
                if ( $( this.resultElement ).val() != "verified" ){
                    this.resultElement.show();
                }else{
                    this.resultElement.hide();
                }
            }

		};
		
		// returns the email address associated with the current result
		this._getResultAddress = function(){
			return this.resultAddress;
		};
		
		// Binds the submit-delaying function to form submit
		this._bindDelaySubmit = function(){
			  $( this.form ).on( "submit", $.proxy( this._submitHandler, this ) );
		}
		
		// Function actually bound to the submit event (via $.proxy)
		this._submitHandler = function( event ){
				// TODO: If field has not been validated, and result is not pending, force validation.  
				this.submitPressed = true;
				if ( this.options.debug ) console.log( "_submitHandler called" );
				if ( this.resultPending ){
					if ( this.options.debug ) console.log( "_submitHandler preventing submit default" )
					event.preventDefault();
				}
		}
		
		// Actually submits the form, and turns off the submitPressed flag
		this._handleDelaySubmit = function(){
			this.submitPressed = false;

			if ( this.options.debug ) { 
				console.log( "_handleDelaySubmit submitting form" );
				// alert("About to submit form.");
			}

			$( this.form ).find( "[type='submit']" ).click(); // TODO: Add more comprehensive form submit (this is best blanket solution)
		};
		
		// Main email validation function.  Bound to focusout event of input.
		this.validateEmailInput = function(){
			emailAddress = $( this.element ).val();
			
			// Email address must contain an '@' and a '.' and the '@' must come before the '.'
			if ( emailAddress.indexOf( "@" ) < emailAddress.lastIndexOf( "." ) ){
				
				// Now test the pending address.  As long as it is different from the currently pending address, continue.
				if ( emailAddress != this._getResultAddress() ){
					this._setResultValue( "pending" );
					this._setResultAddress( emailAddress );
					this._jsonpValidateEmail( emailAddress );
				}
			} else {
				this._setResultValue( "undeliverable" );
			}
		};
		
        this.init();
	};

	// Code to be called on plugin init
	LeadSpendEmail.prototype.init = function () {
		// Modify form and initialize option-based variables
		$( this.element ).addClass( "leadSpendEmail" );
		this._createResultElement();
		
		if ( this.options.delaySubmit ){			
			this.submitPressed = false;	// for tracking form submit
			this.form = $( this.element ).closest( "form" );
			this._bindDelaySubmit();
		}
		
		$( this.element ).focusout( $.proxy( this.validateEmailInput, this ) )	// binding focusout event
						 .keydown( 	$.proxy( function( event ){					// binding keydown event, specifically enter press
							code = ( event.keyCode ? event.keyCode : event.which );
							if( code == 13 ) {	// enter key
								this.validateEmailInput();
							}
						 }, this ) );
		return this; 
	};
	
	// Constructor wrapper, preventing against multiple instantiations
	$.fn[ pluginName ] = function ( options ){
		return this.each(function () {
			if ( !$.data( this, 'plugin_' + pluginName ) ) {
				$.data( this, 'plugin_' + pluginName, 
				new LeadSpendEmail( this, options ) );
			}
		});
	}
}( jQuery, window, document ) );

// Validate all leadSpendEmail fields by default
jQuery( document ).ready( function(){
    jQuery( ".leadSpendEmail-noconfig" ).leadSpendEmail( {debug: false, timeout: 5, delaySubmit: true} );
} );
