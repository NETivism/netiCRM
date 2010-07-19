(function($){ $.fn.toolTip = function(){
  var clickedElement = null;
  return this.each(function() {
    var text = $(this).children().find('div.crm-help').html();
    if(text != undefined) {
      $(this).bind( 'click', function(e){
	$(document).unbind('click');
	$("#toolTip").remove();
	if ( clickedElement == $(this).children().attr('id') ) { clickedElement = null; return; }
	  var tipX = e.pageX + 12;
	  var tipY = e.pageY + 12;
	  $("body").append("<div id='toolTip' style='position: absolute; z-index: 100; display: none;'>" + text + "</div>");
	  $("#toolTip").width("50%");
	  $("#toolTip").fadeIn("medium");
	  var tipWidth  = $("#toolTip").outerWidth(true);
	  var tipHeight = $("#toolTip").outerHeight(true);
	  if(tipX + tipWidth > $(window).scrollLeft() + $(window).width()) tipX = e.pageX - tipWidth;
	  if($(window).height()+$(window).scrollTop() < tipY + tipHeight) tipY = (e.pageY > tipHeight) ? e.pageY - tipHeight : tipY;
	  $("#toolTip").css("left", tipX).css("top", tipY);
	  clickedElement = cj(this).children().attr('id');
      }).bind( 'mouseout', function() {
	$(document).click( function() {
	  $("#toolTip").hide();
	  $(document).unbind('click');
	});
     });
    }
  });
}})(jQuery);