$(document).ready(function(){
	$('#backtotop').hide();


	$( "#subscribe-pop" ).dialog({
		height: 475,
		width: 625,
		//autoOpen: false,
		dialogClass: 'dialogSignup',
		modal: true,
		show: {effect: "drop",
			direction:"right"},
		hide: {effect: "drop",
			direction:"down"},
		draggable: false,
		resizable: false
	});

	//$("#subscribe-pop").dialog("open");
});