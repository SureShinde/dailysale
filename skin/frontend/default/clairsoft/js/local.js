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

	//Experiment engine savings banner

	//Check to see if on checkout to trigger
	if(window.location.href.indexOf('onestepcheckout') > -1){

		var ITEM_FORMAT = 'all',
				TOTAL_FORMAT = 'absolute';

		// Data Extraction Functions
	    function getItemSavingsData($itemRow) {
	        var discountedPrice = parseFloat($itemRow.find('span.cart-price > span.price').text().slice(1)),
	            originalUnitPrice = parseFloat($itemRow.find('span.cart-price > span#hiddenSRP > span.price').text().slice(1)),
	            quantity = parseInt($itemRow.find('.box-qty input[type=hidden]').attr('value')),
	            originalTotal = originalUnitPrice * quantity;
	        return {
	            original: originalTotal,
	            absoluteSavings: originalTotal - discountedPrice,
	            percentSavings: 1 - (discountedPrice / originalTotal)
	        };
	    }

	    function calculateTotalSavings() {
	        var totalSavings = {absolute: 0, original: 0};
	        $j('#checkout-review-table tbody:eq(1) tr').each(function (index, elem) {
	            var $elem = $j(elem),
	                savingsData = getItemSavingsData($elem);
	            // $(elem).find('td:eq(0)').append(formRestyledProductName($elem, savingsData));
	            totalSavings.absolute += savingsData.absoluteSavings;
	            totalSavings.original += savingsData.original;
	        });

	        totalSavings.percent = totalSavings.absolute / totalSavings.original;

					var coupon = parseFloat($j('#checkout-review-table tfoot tr:eq(2) span.price').text().replace('$',''));
					if (coupon < 0){　totalSavings.coupon = coupon;　} else {　totalSavings.coupon = 0;}

					return totalSavings;
	    }

			// Formatting Functions
	    function formatSavings(savingsData, format) {
	        if (format === 'absolute') {
	            return '$' + Math.round((savingsData.absolute - savingsData.coupon) * 100) / 100;
	        } else if (format === 'percent') {
	            return Math.round(100*percent) + '%';
	        } else {
	            return '$' + savingsData.absolute + ' (' + Math.round(100*savingsData.percent) + '%)';
	        }
	    }

	    function formRestyledProductName($itemRow, savingsData) {
	        var $productNameWrapper = $j('<div class="ee-item-title"></div>');

	        $productNameWrapper.append($itemRow.find('h2.product-name').detach());
	        $productNameWrapper.append('<p class="savings">You save: ' + formatSavings(savingsData, ITEM_FORMAT) + '</p>');

	        return $productNameWrapper;
	    }

	    function doVariation() {
	        var totalSavings = calculateTotalSavings();
	        $j('#checkout-review-table tfoot tr:eq(1) td:eq(0)').text('Retail Price');
	        $j('#checkout-review-table tfoot tr:eq(1) span.price').text('$' + totalSavings.original.toFixed(2)).css({'text-decoration': 'line-through'});
	        var savingElem = $j('.ee-savingmsg');
	        if(savingElem.length > 0){
	            savingElem.find('span').text( 'Nice Work! You Saved ' + formatSavings(totalSavings, TOTAL_FORMAT) );
	        } else {
	            var savingHtml = '<li class="success-msg ee-savingmsg"><ul><li><span>Nice Work! You Saved ' + formatSavings(totalSavings, TOTAL_FORMAT) + '.</span></li></ul></li>';
	            $j('ol.one-step-checkout li.address-order').before( savingHtml );
	        }
	    }

	    var originalReviewShow = window.reviewShow;
	    window.reviewShow = function (){
	        doVariation();
	        originalReviewShow();
	    }

	}　// End checkout wrapper

});
