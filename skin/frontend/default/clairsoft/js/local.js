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
		height: 200,
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
		$j.cookie('subscribe', 'closed', { expires: 15, path: '/' });
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

});