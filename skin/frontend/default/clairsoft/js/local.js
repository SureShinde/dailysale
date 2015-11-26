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

	//adaptive homepage for large screens

	$j('head').append("<style>.toolbar-bottom{clear:both}</style>");jQuery('.category-products li.item').unwrap();jQuery('body').append('<style> @media (min-width:1200px) { .page,#top-wrapper .top-container {width: 1200px; padding-right: 15px; padding-left: 15px; } .nav-container{ right: 0; left: auto;} .cms-index-index li.item:nth-of-type(3n), .category-products li.item:nth-of-type(3n){ margin-right: 2.5%;} .cms-index-index li.item:nth-of-type(4n), .category-products li.item:nth-of-type(4n){ margin-right: 0px;} .cms-index-index li.item, .category-products li.item{ max-width: 23.1%; width: auto; margin-right: 2.5%; position: relative; height: 412px; } .category-products li.item{ height: 402px;} .cms-index-index li.item .product-image, .category-products li.item .product-image{ width: 100%; height: auto;} .cms-index-index li.item .product-image img, .category-products li.item .product-image img{ max-width: 100%; height: auto; margin: 0 auto; } .category-products li.item .products-grid-image-container{ margin-bottom: 10px;} .cms-index-index .actions a.view-deal-btn, .category-products .actions a.view-deal-btn{ font-size: 14px; margin: 13px 8px 0 0; padding: 10px;} .cms-index-index .price-box .special-price-container, .category-products .price-box .special-price-container, .cms-index-index .price-box .regular-price, .category-products .price-box .regular-price{ margin-left: -52px; left: 50%;} .cms-index-index .price-box .percent-off, .category-products .price-box .percent-off{ background-size: contain; width: 95px;} .cms-index-index .price-box .percent-off span:first-child, .category-products .price-box .percent-off span:first-child{ padding-top: 1.1em;} .cms-index-index .product-index-description-container, .category-products .product-index-description-container{ width: 100%;} } @media (min-width:1500px){ .page, #top-wrapper .top-container{ width: 100%; max-width: 1600px;} .cms-index-index li.item:nth-of-type(3n), .category-products li.item:nth-of-type(3n), .cms-index-index li.item:nth-of-type(4n), .category-products li.item:nth-of-type(4n){ margin-right: 2%;}.cms-index-index li.item:nth-of-type(5n), .category-products li.item:nth-of-type(5n){ margin-right: 0;} .cms-index-index li.item, .category-products li.item{ width: 18.4%; margin-right: 2%; height: 422px;} }</style>');jQuery('.col-main > img:eq(0)').css({'display':'block','margin':'0 auto'});

});