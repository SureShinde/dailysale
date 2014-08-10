document.observe('dom:loaded', function(){

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

//back to top

	$('backtotop').hide();

	Event.observe(window, 'scroll', function(){
		var windowTop = document.viewport.getScrollOffsets(); // returns number
		if(windowTop[1] > 300){
			$('backtotop').appear();
		} else if(windowTop[1] <= 0){
			$('backtotop').fade();
		}

	});

	$('backtotop').observe('click', function(){
		Effect.ScrollTo('top-wrapper', {duration: 0.5});
	});

	//border hovers

	// var itemElements = $$('li.item');
	// itemElements.each(function(items){
	// 	// items.morph('border: #1b75bb');
	// 	items.observe('mouseover', function(){
	// 		this.morph('border: #1b75bb',{duration: 0.3});
	// 	});
	// 	items.observe('mouseleave', function(){
	// 		itemElements.cancel();
	// 		this.morph('border: #ccc', {duration: 0.8});
	// 	});
	// });

	// var xsellElements = $$('div.single-xsell-container');

	// xsellElements.each(function(xsell){
	// 	// items.morph('border: #1b75bb');
	// 	xsell.observe('mouseover', function(){
	// 		// this.morph('border: #1b75bb',{duration: 0.3});
	// 		this.hide();
	// 	});
	// 	xsell.observe('mouseleave', function(){
	// 		this.morph('border: #ccc', {duration: 0.8});
	// 	});
	// });

	
	$('email-subscribe').hide();

});

