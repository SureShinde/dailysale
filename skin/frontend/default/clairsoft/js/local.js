var $j = jQuery.noConflict();

$j(document).ready(function(){

	if( $j(window).width() <= 414 ){
		$j('.onestepcheckout-index-index .logo').click(function(e){
			e.preventDefault();
		});
	}

	//sticky stuff

	var stickyTop = $j('.wrapper').offset().top + 380; // returns number

	if (window.location.href.indexOf('onestepcheckout') > -1) {
		$j('#backtotop').remove();
	} else {
		$j('#backtotop').hide();
	}

	$j(window).scroll(function(){ // scroll event
		var windowTop = $j(window).scrollTop(); // returns number

		if (stickyTop < windowTop) {
			$j('#backtotop').fadeIn();
		}else{
			$j('#backtotop').fadeOut();}
	});

	var backtotopEnter = function(){
		$j(this).stop(true, false).animate({paddingRight: '135px'}, 'fast'); };

	var backtotopLeave = function(){
		$j(this).stop(true, false).animate({paddingRight: '0px'}, 'fast'); };

	$j('#backtotop').hover(backtotopEnter, backtotopLeave).click(function(){
		$j('html, body').animate({ scrollTop: 0 }, 'fast');
		return false;
	});

	//main page border hovers

	var borderEnter = function(){
		var borderColor = "#1b75bb";
		$j(this).stop(true, false).animate({
			borderTopColor: borderColor,
			borderRightColor: borderColor,
			borderBottomColor: borderColor,
			borderLeftColor: borderColor
		});};

	var borderLeave = function(){
		var borderColor = "#ccc";
		$j(this).stop(true, false).animate({
			borderTopColor: borderColor,
			borderRightColor: borderColor,
			borderBottomColor: borderColor,
			borderLeftColor: borderColor
		}, 'slow'); };

	$j('.products-grid li.item').hover(borderEnter, borderLeave);

	//remove html tags from customer credit cart section

	function removeAllHtmlInsideElement(){
		$j(".credit-payment button").html($j(".credit-payment button").text());
	}

	removeAllHtmlInsideElement();

	//pop up sign up and login buttons

	$j("a[title='Sign Up']").click(function(e){
		e.preventDefault();
		$j('html, body').animate({
	        scrollTop: 0
	      }, 'slow');
		$j('#signin-modal').addClass('md-show');
		//IWD.Signin.prepareLoginForm();
		//$j('.login-form').hide();
      IWD.Signin.insertLoader();
      IWD.Signin.prepareRegisterForm();
	    });
	    $j(document).on('click', '.account-create-signin .back-link, .account-forgotpassword .back-link', function (e) {
	      e.preventDefault();
	      $j('html, body').animate({
	        scrollTop: 0
	      }, 'slow');
	      IWD.Signin.insertLoader();
	      IWD.Signin.prepareLoginForm();
	});

	$j("a[title='Log In']").click(function(e){
		e.preventDefault();
		$j('html, body').animate({
	        scrollTop: 0
	      }, 'slow');
		$j('#signin-modal').addClass('md-show');
		IWD.Signin.prepareLoginForm();
		$j('.login-form').hide();
      IWD.Signin.insertLoader();
      IWD.Signin.prepareLoginForm();
	    });
	    $j(document).on('click', '.account-create-signin .back-link, .account-forgotpassword .back-link', function (e) {
	      e.preventDefault();
	      $j('html, body').animate({
	        scrollTop: 0
	      }, 'slow');
	      IWD.Signin.insertLoader();
	      IWD.Signin.prepareLoginForm();
	});

	//remove duplicate items from homepage

	var seen = {};
	$j('.cms-index-index li.item').each(function() {
	    var txt = $j(this).attr('class');
	    if (seen[txt])
	        $j(this).remove();
	    else
	        seen[txt] = true;
	});

	//change qty dropdown to text input for 10+ qty

	$j('#qty').change(function(e){
	    var selected_item = $j(this).val();
	    e.preventDefault();

	    if(selected_item === "other"){
	        $j('#otherqty').val("10").removeClass('hidden').focus().select();
	        $j('#qty').val("").remove();
	    }else{
	        $j('#otherqty').val(selected_item).addClass('hidden');
	    }
	});

});
