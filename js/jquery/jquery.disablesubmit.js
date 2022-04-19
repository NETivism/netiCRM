(function($){
  $(document).ready(function(){
    var $obj = $('input[data=submit-once]');

    // solve last submitted qfkey
    var last_qfkey = getUrlParams('qfKey');
    if(!last_qfkey){
      if ($('[name=qfKey]').length >= 1) {
        last_qfkey = $('[name=qfKey]').val();
      }
      else if($('input[name=submit_once_check]').length >= 1){
        last_qfkey = $('input[name=submit_once_check]').val();
      }
    }

    if(last_qfkey){
      last_submitted = getCookie(last_qfkey);
      if(last_submitted == 1){
        if($obj.parents("form").has('.error:visible').length > 0 || $obj.parents("form").has('.crm-error').length > 0) {
          last_submitted = 0; // submit validate error from backend
        }
        else{
          last_submitted = 2; // don't change btn stat anymore.
        }
        setCookie(last_qfkey, last_submitted, 3600);
      }
    }

    var qfkey = getUrlParams('qfKey');
    if(!qfkey){
      qfkey = $('[name=qfKey]').val();
      if(!qfkey){
        qfkey = $('[name=submit_once_check]').val();
      }
    }
    var submitted = getCookie(qfkey);
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

          // Not trigger when jvalidate is not enabled.
          if( $obj.parents("form").has('.error:visible').length > 0 || $obj.parents("form").has('.crm-error').length > 0){
            var is_block_by_error = true;
          }
          if(submitted < 1 && !is_block_by_error && !$obj.attr("readonly")){
            setCookie(qfkey, 1, 3600);
            submitted = 1;
            $(this).val($(this).val() + ' ...');
          }
        });
      }

      // prevent double submit
      $obj.parents("form").on('submit', function(e){
        var $form = $(this);
        window.setTimeout(function(){
          if( $form.has('.error:visible').length > 0 || $form.has('.crm-error').length > 0){
            var is_block_by_error = true;
          }
          if ($form.data('submitted') === true || is_block_by_error || $obj.attr("readonly")) {
            // Previously submitted - don't submit again
            e.preventDefault();
          }
          else {
            // Mark it so that the next submit can be ignored
            $form.data('submitted', true);
            // Don't use disabled cause profile edit will have problem. refs #20289 - 9F
            $obj.attr("readonly", true);
            $obj.first().before('<i class="zmdi zmdi-rotate-right zmdi-hc-spin"></i>');
            if ($obj.data('once-msg')) {
              let msg = $obj.data('once-msg');
              $obj.first().parent().prepend('<div class="once-message"><i class="zmdi zmdi-rotate-right zmdi-hc-spin"></i>'+msg+'<div>');
            }
          }
        }, 250);
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
          $obj_click_once.first().before('<i class="zmdi zmdi-rotate-right zmdi-hc-spin"></i>');
          if ($obj_click_once.data('once-msg')) {
            let msg = $obj_click_once.data('once-msg');
            $obj_click_once.first().parent().prepend('<div class="once-message">'+msg+'<div>');
          }
        }
      });
    }
  });
})(jQuery);
