var cj = jQuery.noConflict(); $ = cj;
cj(document).ready(function(){
  cj("#crm-container form").each(function(){
    cj(".crm-section .label .crm-marker").each(function(){
      if(cj(this).text() == "*"){
        var inputs = cj(this).parents(".crm-section").find(":input:visible:first:not([type=checkbox])");
        inputs.addClass("required");
        var checkboxes = cj(this).parents(".crm-section").find(":input:visible[type=checkbox]");
        checkboxes.addClass("required");
      }
    });
    if(cj(this).attr("id")){
      var formid = cj(this).attr("id");
      cj("#"+formid).validate({
        errorPlacement: function (error, element) {
          if (element.is(":radio") || element.is(":checkbox")) {
            element.parents(".content").find("label.error").remove();
            var c = element.parents(".content");
            error.css({"color":"#E55","padding-left":"10px"}).wrap("div");
            c.prepend(error);
          }
          else {
            error.css({"color":"#E55","padding-left":"10px"});
            error.insertAfter(element);
          }
        }
      });
      cj("#"+formid+" input.required:visible:not([type=checkbox])").each(function(){
        cj(this).rules("add", {required:true });
      });
      cj("#"+formid+" input.required:visible:not([type=checkbox])").blur(function(){
        cj(this).valid();
      });
      cj("#"+formid+" input.required[type=checkbox]").each(function(){
        cj(this).rules("add", {
          required:{
            depends: function(element){
              return cj(element).parents(".crm-section").find(":input:checkbox:checked").size() == 0;
            }
          }
        });
      });
      cj("#"+formid+" input.required[type=checkbox]").click(function(){
        cj(this).parents(".content").find("label.error").remove();
        cj(this).valid();
      });
      cj("#"+formid+" input[name*=email]").each(function(){
        cj(this).rules("add", {required:true,email:true});
      });
      cj("#"+formid+" input[name*=url]").each(function(){
        cj(this).rules("add", {required:true,url:true});
      });
    }
  });  
});
