;
$j = jQuery;
var IWD=IWD||{};
IWD.Signin = {
		
		config : null,
		
		showDialog : false, 
		
		googleHandleRequest : null,
		googleData : null, 
		yahooDialog:null,
		twitterDialog:null,
		opc:false,
		init: function(){			
			if (typeof(SigninConfig)!="undefined"){
				IWD.Signin.config = SigninConfig;
			}else{
				return;
			};
			
			if (typeof(IWD.Signin.config.isLoggedIn)!="undefined" && IWD.Signin.config.isLoggedIn==1){
				return;
			}
			this.initOpenDialog();
			this.initLoginLink();
			this.initLoginForm();
			this.initRegisterLink();
			this.initRegisterForm();			
			this.initForgotPassword();
			this.initForgotForm();
			this.initPaypalLogin();
			this.initYahooLogin();
			this.initTwitterLogin();
			
		}, 
		
		initPaypalLogin: function(){
			$(document).on('click', '.btn-paypal-login', function(e){
				e.preventDefault();
				var paypalUrl = $j('.btn-paypal-login').attr('href');
				mywindow = window.open (paypalUrl, "_PPIdentityWindow_", "location=1, status=0, scrollbars=0, width=400, height=550");
				
			});
		},
		
		initOpenDialog: function(){
			//wishlis link
			$j('.links a').each(function(){			
				var url = $j(this).attr('href');
				if (typeof(url)!="undefined"){
					var regV = /wishlist/gi;
					var result = url.match(regV);
					if (result){
						
						$j(this).click(function(event){
							event.preventDefault();
							IWD.Signin.saveLink();
							$j('#signin-modal').addClass('md-show');
							IWD.Signin.prepareLoginForm();
						});
					}
				}
				
			});
			
			/** close dialog **/
			$j('#signin-modal .close').click(function(){
				$j('#signin-modal').removeClass('md-show');
				if (IWD.Signin.opc==true){
					IWD.ES.Decorator.showDialog();
					IWD.ES.Decorator.hideProcess();
					IWD.ES.Decorator.showPayment();
				}
				IWD.Signin.opc = false;
			})
			
			// link-wishlist
			$j('a.link-wishlist').attr('onclick','');
			$j('a.link-wishlist').click(function(event){
				event.preventDefault();
				IWD.Signin.saveLink();
				$j('#signin-modal').addClass('md-show');
				IWD.Signin.prepareLoginForm();
			});
			
			//email to a friend
			$j('.email-friend a, .emailto-link a').click(function(event){
				event.preventDefault();
				IWD.Signin.saveLink();
				$j('#signin-modal').addClass('md-show');
				IWD.Signin.prepareLoginForm();
			});
			
			
			
		},
		
		saveLink: function(){
			
			var form = {};
		
			if (IWD.Signin.opc==true){
				form.url = document.location.href  + '?opc=true';
			}else{
				form.url = document.location.href;
			}
			
			$j.post(IWD.Signin.config.url + 'signin/json/redirect', form, IWD.Signin.parseLoginResponse, 'json'); 
			
		},
		
		initLoginLink: function(){
			$j('.signin-modal').click(function(e){
				e.preventDefault();		
				IWD.Signin.saveLink();
				$j('#signin-modal').addClass('md-show');
				IWD.Signin.prepareLoginForm();
				$j("html, body").animate({ scrollTop: 0 }, "slow");	
			});
		},
		
		initLoginForm: function(){
			$j(document).on('click','.signin #signin-login',function(e){
				e.preventDefault();
				$j('.signin #login-form').submit();
			});
			$j(document).on('submit', '.signin #login-form', function(event){
				IWD.Signin.showLoader();
				event.preventDefault();
				var form = $j('.signin #login-form').serializeArray();
				$j.post(IWD.Signin.config.url + 'signin/json/login', form, IWD.Signin.parseLoginResponse, 'json');
			});
		},
		
		prepareLoginForm: function(){
			IWD.Signin.showLoader();
			$j.post(IWD.Signin.config.url + 'signin/json/load',{"block":"login"}, IWD.Signin.parseResponseLoad, 'json');
		}, 
		
		parseResponseLoad: function(response){
			IWD.Signin.hideLoader();
			if (typeof(response.id !="undefined")){
				var block = response.id; 
				$j('#signin-ajax-load').html(response[block]);
			}
		},
		
		
		initRegisterLink: function(){
			
			$j(document).on('click','#create-account-singup',function(e){
			
				e.preventDefault();
				$j('.login-form').hide();
				IWD.Signin.insertLoader();		
				IWD.Signin.prepareRegisterForm();
				
			});
			
			$j(document).on('click', '.account-create-signin .back-link, #back-to-login', function(e){
				e.preventDefault();
				$j('#login-header').show();
				$j('#forgot-header').hide();
				$j("html, body").animate({ scrollTop: 0 }, "slow");	
				IWD.Signin.insertLoader();
				IWD.Signin.prepareLoginForm();
			});
		},
		
		initRegisterForm:function(){
			$(document).on('submit', '.account-create-signin #form-validate', function(event){
				event.preventDefault();
				var form = $j('.account-create-signin #form-validate').serializeArray();
				$j.post(IWD.Signin.config.url + 'signin/json/register', form, IWD.Signin.parseRegisterResponse, 'json');
			});
		},
		
		prepareRegisterForm: function(){
			$j.post(IWD.Signin.config.url + 'signin/json/load', {"block":"register"}, IWD.Signin.parseResponseLoad, 'json');
		},
		
		
		
		initForgotPassword:function(){
			$j(document).on('click', '#forgot-password', function(e){
				e.preventDefault();
				$j('#login-header').hide();
				$j('#forgot-header').show();
				IWD.Signin.insertLoader();
				$j.post(IWD.Signin.config.url + 'signin/json/load', {"block":"forgotpassword"}, IWD.Signin.parseResponseLoad, 'json');
			});
		},
		
		initForgotForm:function(){
			$(document).on('submit', '.account-forgotpassword #form-validate', function(event){
				event.preventDefault();
				var form = $j('.account-forgotpassword #form-validate').serializeArray();
				$j.post(IWD.Signin.config.url + 'signin/json/forgotpassword', form, IWD.Signin.parseForgotPasswordResponse, 'json');
			});
		},
		
		
		insertLoader: function(){
			$j('#signin-ajax-load').empty();
			IWD.Signin.showLoader();
		},
		
		showLoader: function(){
			$j('.ajax-loader').show();
		},
		hideLoader: function(){
			$j('.ajax-loader').hide();
		},
		
		parseLoginResponse: function(response){
			if (response==null){return;}
			
			if (typeof(response.error) !="undefined" && response.error==1){
				IWD.Signin.hideLoader();
				//if error show error message				
				$j('#signin-error').remove();
				$j('<div />').attr('id','signin-error').addClass('signin-error').html(response.message).insertAfter('.signin #login-form'); 
			}
			
			
			
			if (typeof(response.linkAfterLogin)!="undefined"){
				
				if (IWD.Signin.opc==true){
					
					//compatibility with OPC
					$j('#signin-modal').removeClass('md-show');
					IWD.ES.Decorator.showDialog();
					IWD.ES.Decorator.showLoader();
					IWD.Signin.opc = false;
					IWD.ES.config.isLoggedIn = 1;
					IWD.ES.Loader.reloadMainBlock();
					IWD.ES.Decorator.hideProcess();
					IWD.ES.Decorator.showPayment();
					return;
				}
				
				if (typeof(response.message)!="undefined"){
					//show message and redirect to url after 2.5s;
					setTimeout(function(){
						setLocation(response.linkAfterLogin);
					}, 2500);
				}else{
					//just redirect to url
					setTimeout(function(){
						setLocation(response.linkAfterLogin);
					}, 500);
					
				}
				
			}
		},
		
		showMessage: function(message){
			IWD.Signin.hideLoader();
			$j('#signin-error').remove();
			$j('<div />').attr('id','signin-error').addClass('signin-error').html(message).insertAfter('#login-form');
		}, 
		
		redirect: function(url){
			setLocation(url);
		}, 
		
		parseRegisterResponse: function(response){
			IWD.Signin.hideLoader();
			$j('#signin-error').remove();
			
			if (typeof(response.error) !="undefined" && response.error==1){
				//if error show error message				
				$j('<div />').attr('id','signin-error').addClass('signin-error').html(response.message).insertAfter('.account-create-signin #form-validate'); 
			};
			
			if (typeof(response.linkAfterLogin)!="undefined"){
				$j('.account-create-signin #form-validate').empty();
				if (typeof(response.message)!="undefined"){
					//show message and redirect to url after 2.5s;
					$j('<div />').attr('id','signin-error').addClass('signin-success').html(response.message).appendTo('.account-create-signin #form-validate'); 
					
					setTimeout(function(){
						setLocation(response.linkAfterLogin);
					}, 2500);
				}else{
					//just redirect to url
					setTimeout(function(){
						setLocation(response.linkAfterLogin);
					}, 500);
					
				}
			}
			
			
			if (typeof(response.emailConfirmation)!="undefined"){
				if (typeof(response.message)!="undefined"){
					//just redirect to url
					$j('<div />').attr('id','signin-error').addClass('signin-success').html(response.message).appendTo('.account-create-signin #form-validate'); 
					
					
				}
			}
		},
		
		parseForgotPasswordResponse: function (response){
			IWD.Signin.hideLoader();
			if (typeof(response.error)!="undefined"){
				$j('<div />').attr('id','signin-error').addClass('signin-error').html(response.message).appendTo('.account-forgotpassword #form-validate'); 
			}else{
				IWD.Signin.insertLoader();
				IWD.Signin.prepareLoginForm();
				
			}
		}, 
		
		//facebook login or register
		loginWithFacebook: function(){
			IWD.Signin.showLoader();
			if (IWD.Signin.config.isLoggedIn!=1){
				
				FB.getLoginStatus(function(response) {
					  if (response.status === 'connected') {
						  FB.api('/me',IWD.Signin.pushFacebookData);
					  } else{
						  FB.login(function(response) {
							    if (response.authResponse) {
							    	FB.api('/me',IWD.Signin.pushFacebookData);
							    } else {

							    }
							}, {"scope": "email"}); 
					  }
				});
	
			}
		},
		
		pushFacebookData: function(response){
			
			var form = {};
			form.firstname = response.first_name;
			form.lastname = response.last_name;
			form.id = response.id;
			form.email = response.email;
			$j.post(IWD.Signin.config.url + 'signin/json/facebook', form, IWD.Signin.parseLoginResponse, 'json');
		},
		
		
		/** YAHOO  **/
		initYahooLogin:function(){
			$j(document).on('click','.btn-yahoo-login', function(e){
				e.preventDefault();
				IWD.Signin.showLoader();
				
				var leftvar = (screen.width-600)/2;			
				var topvar = (screen.height-435)/2;

				IWD.Signin.yahooDialog = window.open(IWD.Signin.config.url + 'signin/yahoo/prepare',"Yahoo","width=600,height=435,resizable=false,scrollbars=false,status=false,toolbar=false,left="+leftvar+",top="+topvar+",status=no,toolbar=no,menubar=no")
				IWD.Signin.yahooDialog.focus();
			});
		},
		
		
		/** TWITTER **/
		initTwitterLogin:function(){
			
			$j(document).on('click','.btn-twitter-login', function(e){
				e.preventDefault();
				IWD.Signin.showLoader();
				var leftvar = (screen.width-600)/2;			
				var topvar = (screen.height-435)/2;
				IWD.Signin.twitterDialog = window.open(IWD.Signin.config.url + 'signin/twitter/prepare/',"Twitter","width=600,height=435,resizable=false,scrollbars=false,status=false,toolbar=false,left="+leftvar+",top="+topvar+",status=no,toolbar=no,menubar=no")
				IWD.Signin.twitterDialog.focus();				
			});
		}
};

$j(document).ready(function(){
	IWD.Signin.init();
});