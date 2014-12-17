(function($){
  $(document).ready(function(){
    var qfkey = getUrlParams('qfKey');
    var submitted = getCookie(qfkey);
    var $obj = $('input[onclick*=submitOnce]');
    if($obj.length && qfkey){
      if(submitted == "1" || submitted >= 1){
        $obj.each(function(){
          $(this)[0].onclick = null;
        });
        $obj.css({"color":"#aaa","cursor":"not-allowed"});
        $obj.attr("disabled", true);
        $('.disable-submit-message').show();
      }
      else{
        // set cookie
        setCookie(qfkey, 1, 3600);
        var code = $obj.attr('onclick').replace('return', '');
        $obj.click(function(){
          eval(code);
        });
      }
    }
  });
})(jQuery);
