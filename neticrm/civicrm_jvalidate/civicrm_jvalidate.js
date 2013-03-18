$.amask.definitions['~']='[1-9]';
$.amask.definitions['z']='[9]';
$.amask.definitions['A']='[A-Z]';
$.amask.definitions['#']='[0-9#]';
$.amask.phone_add_validate = function(obj){
  $(obj).rules("add", "twphone");

  var mobile = false;
  var n = $(obj).attr('name');
  var re = /phone-(\w+)-(\d+)/g;
  var match = re.exec(n);
  if(match != null){
    var idx = match.length - 1;
    if(match[idx] == '2'){
      mobile = true;
    }
  }
  if(mobile){
    $(obj).amask("0z99-999999");
  }
  else{
    $(obj).amask("0~-9999999?##########");
    // add phone ext box.
    var fid = $(obj).attr("id");
    $('<span href="#" class="ext" rel="'+fid+'"> +'+Drupal.settings.jvalidate.ext+'</span>').insertAfter(obj);
    $("span[rel='"+fid+"']").css({cursor:"pointer",color:"green"});
    $("span[rel='"+fid+"']").click(function(){
      var ext = prompt(Drupal.settings.jvalidate.extprompt);
      if(ext != null && ext != ""){
        var f = '#'+$(this).attr("rel");
        var v = $(f).val().replace(/#.*/, '');
        $(f).val(v+'#'+ext);
      }
    });
  }
}

$.amask.id_add_validate = function(obj){
  $(obj).rules("add", "twid");
  $(obj).amask("a999999999", {completed:function(){ obj.value = obj.value.toUpperCase(); }});
}

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
          $.amask.id_add_validate(this);
        });

        // phone
        $("#"+formid+" input[name*=phone]").each(function(){
          $.amask.phone_add_validate(this);
        });
      }
    }
  });

});
