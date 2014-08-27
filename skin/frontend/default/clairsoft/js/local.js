var $j = jQuery.noConflict();

$j(document).ready(function(){

	// midnight timer

	function showTimes() {
		var now = new Date();
		var hrs = 23-now.getHours();
			hrs = ("0" + hrs).slice(-2);
		var mins = 59-now.getMinutes();
			mins = ("0" + mins).slice(-2);
		var secs = 59-now.getSeconds();
			secs = ("0" + secs).slice(-2);
		var str = '';
		//str = now.toString();
		str += hrs+':'+mins+':'+secs+'';
		document.getElementById('timer').innerHTML = str;
	}

	setInterval( function(){showTimes();},1000);
	
	//sticky stuff

	var stickyTop = $j('.wrapper').offset().top + 380; // returns number

	$j(window).scroll(function(){ // scroll event
		var windowTop = $j(window).scrollTop(); // returns number

		if (stickyTop < windowTop) {
			$j('#backtotop').fadeIn();
		}else{
			$j('#backtotop').fadeOut();}
	});

	var backtotopEnter = function(){
		$j(this).stop(true, false).animate({paddingRight: '125px'}, 'fast'); };

	var backtotopLeave = function(){
		$j(this).stop(true, false).animate({paddingRight: '0px'}, 'fast'); };

	$j('#backtotop').hover(backtotopEnter, backtotopLeave).click(function(){
		$j('html, body').animate({ scrollTop: 0 }, 'fast');
		return false;
	});

	$j("#subscribe-pop").hide();

	var subscribeCookie = $j.cookie('subscribe', 'open');
	//var subscribeCookie = "open";

	$j( "#subscribe-pop" ).dialog({
		height: 275,
		width: 425,
		autoOpen: false,
		dialogClass: 'dialogSignup',
		modal: true,
		show: {effect: "drop",
			direction:"right"},
		hide: {effect: "drop",
			direction:"down"},
		draggable: false,
		resizable: false,
		close: function(){
			$j.cookie('subscribe', 'closed', { expires: 1, path: '/' });
			//alert( subscribeCookie );
		}
	});

	//$j("#subscribe-pop").dialog("open");

	$j(window).resize(function() {
    	$j("#subscribe-pop").dialog( "option", "position", { my: "center", at: "center", of: window } );
    	//$j("#subscribe-pop").dialog("close");
	});

	//timer script for popup action

	var idleTime = 0;

	function timerIncrement() {
		idleTime = idleTime + 1;

			if (subscribeCookie === "open" && idleTime > 2) {
				$j("#subscribe-pop").dialog("open");
				idleTime = 0;
			}}

	setInterval(timerIncrement, 1000); // 1 second

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