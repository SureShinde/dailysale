var $j = jQuery.noConflict();

$j(document).ready(function(){

	//sticky stuff

	var stickyTop = $j('.wrapper').offset().top + 380; // returns number
	$j('#backtotop').hide();
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

//popup window with cookie

	//set dialog options
	$j("#subscribe-pop").hide();
	$j( "#subscribe-pop" ).dialog({
		height: 220,
		width: 425,
		autoOpen: false,
		dialogClass: 'dialogSubscribe',
		modal: true,
		show: {effect: "drop",
			direction:"right"},
		hide: {effect: "drop",
			direction:"down"},
		draggable: false,
		resizable: false,
		close: function(){
			$j.cookie('subscribe', 'closed', { expires: 15, path: '/' });
		}
	});

	$j("#subscribe-pop button").click(function(){
		$j.cookie('subscribe', 'closed', { expires: 730, path: '/' });
	});

	//timer script for popup action

	var idleTime = 0;
	function timerIncrement() {
		idleTime = idleTime + 1;
			if (idleTime > 15 && $j.cookie('subscribe') !== "closed"){
				$j("#subscribe-pop").dialog("open");
				idleTime = 0;
			}
		}

	setInterval(timerIncrement, 1000); // 1 second

	//move subscribe window to center on resize
	$j(window).resize(function() {
    	$j("#subscribe-pop").dialog( "option", "position", { my: "center", at: "center", of: window } );
    	//$j("#subscribe-pop").dialog("close");
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

	// var result = $j("#newsletter-result").val();

	// Validation.add('leadSpendEmail-noconfig', 'Please enter a valid email adddddress. For example johndoe@domain.com.', function(v) {
	//     if (result === "disposable" || result === "unreachable" || result === "illegimate" || result === "undeliverable" || result === "unknown" || result === "error"){
	//     	return false;
	//     } else {
	// 	    return true;
	// 	}

	// });

});