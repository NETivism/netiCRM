$(document).ready(function(){
  var lang = $('html').attr('lang');
  var skiptwcheck = typeof(Drupal.settings.skiptwcheck) == 'undefined' ? 0 : 1;

  $("#crm-container form").each(function(){
    $(".crm-section .label .crm-marker").each(function(){
      if($(this).text() == "*"){
        var inputs = $(this).parents(".crm-section").find(":input:visible:first:not([type=checkbox])");
        inputs.addClass("required");

        var checkboxes = $(this).parents(".crm-section").find(":input:visible[type=checkbox]");
        checkboxes.parents("div.content").addClass("ckbox");
      }
    });
    if($(this).attr("id")){
      var formid = $(this).attr("id");
      $("#"+formid).validate({
        errorPlacement: function (error, element) {
          if (element.is(":radio") || element.is(":checkbox")) {
            var $c = element.parent();
            $c.find("label.error").remove();
            error.css({"color":"#E55","padding-left":"10px"}).wrap("div");
            $c.prepend(error);
          }
          else {
            error.css({"color":"#E55","padding-left":"10px"});
            error.insertAfter(element);
          }
        }
      });
      $("#"+formid+" input[name*=email]").each(function(){
        $(this).rules("add", {required:false,email:true});
      });
      $("#"+formid+" input[name*=url]").each(function(){
        $(this).rules("add", {required:false,url:true});
      });
      $("#"+formid+" input.required:visible:not([type=checkbox])").each(function(){
        $(this).rules("add", {required:true });
      });
      $("#"+formid+" input.required:visible:not([type=checkbox])").blur(function(){
        $(this).valid();
      });
      var $ckbox = $("#"+formid+" div.ckbox");
      $ckbox.each(function(){
        $(this).find("input").each(function(){
          $(this).rules("add", 'ckbox');
        });
      });
      $ckbox.find("input:checkbox").click(function(){
        var $p = $(this).parent("div.ckbox");
        $p.find("label.error").remove();
        $(this).valid();
      });

      // only add validate when language is chinese.
      if(lang == 'zh-hant' && !skiptwcheck){
        // twid
        $("#"+formid+" input[name*=legal_identifier]").each(function(){
          $(this).rules("add", "twid");
        });

        // phone
        $("#"+formid+" input[name*=phone]").each(function(){
          console.log($(this));
          $(this).rules("add", "twphone");
        });
      }
    }
  });  
});
