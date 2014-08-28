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