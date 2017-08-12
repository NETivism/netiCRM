(function($){
  $(document).ready(function(){
    var qfkey = getUrlParams('qfKey');
    if(!qfkey){
      qfkey = $('[name=submit_once_check]').val();
    }
    var submitted = getCookie(qfkey);
    var $obj = $('input[data=submit-once]');
    if($obj.length && qfkey){
      if(submitted == "1" || submitted >= 1){
        $obj.each(function(){
          $(this)[0].onclick = null;
        });
        $obj.css({"color":"#aaa","cursor":"not-allowed"});
        $obj.attr("readonly", true);
        $('.disable-submit-message').show();
        $obj.parents("form").on('submit', function(e){
          e.preventDefault();
        });
      }
      else{
        // set cookie
        $obj.bind('click', function(e){
          // If attribute is readonly, don't enable.
          if(submitted < 1 && $obj.parents("form").has('.error:visible').length == 0 && !$obj.attr("readonly")){
            setCookie(qfkey, 1, 3600);
            submitted = 1;
            $(this).val($(this).val() + ' ...');
          }
        });
      }

      // prevent double submit
      $obj.parents("form").on('submit', function(e){
        var $form = $(this);
        if ($form.data('submitted') === true || $form.has('.error:visible').length > 0 || $obj.attr("readonly")) {
          // Previously submitted - don't submit again
          e.preventDefault();
        }
        else {
          // Mark it so that the next submit can be ignored
          $form.data('submitted', true);
          // Don't use disabled cause profile edit will have problem. refs #20289 - 9F
          $obj.attr("readonly", true);
        }
      });
    }

    var $obj_click_once = $('input[data=click-once]');
    if($obj_click_once.length){
      // prevent double submit
      $obj_click_once.parents("form").on('submit', function(e){
        var $form = $(this);
        if ($form.data('submitted') === true || $obj_click_once.attr("readonly")) {
          // Previously submitted - don't submit again
          e.preventDefault();
        }
        else {
          // Mark it so that the next submit can be ignored
          $form.data('submitted', true);
          // Don't use disabled cause profile edit will have problem. refs #20289 - 9F
          $obj_click_once.attr("readonly", true);
        }
      });
    }
  });
})(jQuery);
