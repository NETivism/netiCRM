$(document).ready(function(){
  $("#crm-container form").each(function(){
    $(".crm-section .label .crm-marker").each(function(){
      if($(this).text() == "*"){
        var inputs = $(this).parents(".crm-section").find(":input:visible:first:not([type=checkbox])");
        inputs.addClass("required");
        /*
        var checkboxes = $(this).parents(".crm-section").find(":input:visible[type=checkbox]");
        checkboxes.addClass("required");
        */
      }
    });
    if($(this).attr("id")){
      var formid = $(this).attr("id");
      $("#"+formid).validate({
        errorPlacement: function (error, element) {
        /*
          if (element.is(":radio") || element.is(":checkbox")) {
            element.parents(".content").find("label.error").remove();
            var c = element.parents(".content");
            error.css({"color":"#E55","padding-left":"10px"}).wrap("div");
            c.prepend(error);
          }
          else {
        */
            error.css({"color":"#E55","padding-left":"10px"});
            error.insertAfter(element);
         /*
          }
         */
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
      /*
      $("#"+formid+" input.required[type=checkbox]").each(function(){
        $(this).rules("add", {
          required:{
            depends: function(element){
              return $(element).parents(".crm-section").find(":input:checkbox:checked").size() == 0;
            }
          }
        });
      });
      $("#"+formid+" input.required[type=checkbox]").click(function(){
        $(this).parents(".content").find("label.error").remove();
        $(this).valid();
      });
      */
    }
  });  
});
