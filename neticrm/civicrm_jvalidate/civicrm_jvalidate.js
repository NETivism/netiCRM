$.amask.definitions['~']='[1-9]';
$.amask.definitions['o']='[0]';
$.amask.definitions['z']='[9]';
$.amask.definitions['A']='[A-Z]';
$.amask.definitions['#']='[0-9#]';

$.amask.phone_add_validate = function(obj, admin){
  var mobile_mask = function(obj){
    var fid = $(obj).attr("id");
    $("span[rel="+fid+"]").remove();
    $(obj).amask("0z99-999999");
  }
  var phone_mask = function(obj){
    $(obj).amask("0~-9999999?##########");
    // add phone ext box.
    var fid = $(obj).attr("id");
    $("span[rel="+fid+"]").remove();
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

  $(obj).rules("add", "twphone");
  $(obj).css("max-width", "280px")

  var mobile = false;
  if(admin){
    var $p = $(obj).parents("tr:first");
    var $type = $p.find("select[name*='phone_type_id']");
    if($type.val() == 2){
      mobile = true;
    }
  }
  else{
    var n = $(obj).attr('name');
    var re = /phone-(\w+)-(\d+)/g;
    var match = re.exec(n);
    if(match != null){
      var idx = match.length - 1;
      if(match[idx] == '2'){
        mobile = true;
      }
    }
  }
  if(mobile){
    mobile_mask(obj);
  }
  else{
    phone_mask(obj);
  }

  // phone type change
  $("select[name*='phone_type_id']").change(function(){
    var type_id = $(this).val();
    $(this).parents('tr:first').find("input[name$='[phone]']").each(function(){
      if(type_id==2){
        mobile_mask(this);
      }
      else{
        phone_mask(this);
      }
    });
  });
}

$.amask.id_add_validate = function(obj){
  $(obj).rules("add", "twid");
  if($(obj).val()){
    if($(obj).valid()){
      $(obj).amask("a999999999", {completed:function(){ obj.value = obj.value.toUpperCase(); }});
    }
    else{
      $(obj).rules("remove", "twid");
    }
  }

  // add id validate remove rule.
  var fid = $(obj).attr("id");
  $("span[rel="+fid+"]").remove();
  $('<span href="#" class="valid-id" rel="'+fid+'"> '+Drupal.settings.jvalidate.notw+'</span>').insertAfter(obj);
  $("span[rel='"+fid+"']").css({cursor:"pointer",color:"green"});
  $("span[rel='"+fid+"']").click(function(){
    var notw = prompt(Drupal.settings.jvalidate.notwprompt);
    if(notw != null && notw != ""){
      $(obj).rules("remove", "twid");
      $(obj).unmask();
      $(obj).val(notw);
    }
  });
}

function parse_url(name, url){
  var parse_url = /^(?:([A-Za-z]+):)?(\/{0,3})([0-9.\-A-Za-z]+)(?::(\d+))?(?:\/([^?#]*))?(?:\?([^#]*))?(?:#(.*))?$/;
  var result = parse_url.exec(url);
  var names = ['url', 'scheme', 'slash', 'host', 'port', 'path', 'query', 'fragment'];

  if (name == 'fragment') {
    return result[7];
  }
  if (name == 'path'){
    return result[5];
  }
  else {
    var results = new RegExp('[\\?&]' + name + '=([^&#]*)').exec(url);
  }
  if (!results) { return ''; }
  return results[1] || '';
};

$(document).ready(function(){
  var lang = $('html').attr('lang');
  var skiptwcheck = typeof(Drupal.settings.skiptwcheck) == 'undefined' ? 0 : 1;
  var path = parse_url('path', document.URL);
  var action = parse_url('action', document.URL) == 'update' ? 'update' : 'add';
  var admin = path.match('civicrm/contact/add') ? 1 : 0;
  var is_event = path.match('civicrm/event/register') ? 1 : 0;

  if(admin){
    $("form input.form-submit").addClass('cancel');
  }
  if(is_event){
    $("form input.form-submit[name$='next_skip']").addClass('cancel');
    $("form input.form-submit[name$='back']").addClass('cancel');
  }

  $("#crm-container form").each(function(){
    $(".crm-section .label .crm-marker").each(function(){
      if($(this).text() == "*"){
        var inputs = $(this).parents(".crm-section:first").find(":input:visible:first:not([type=checkbox])");
        inputs.addClass("required");

        var checkboxes = $(this).parents(".crm-section:first").find(":input:visible[type=checkbox]");
        checkboxes.parents("div.content:first").addClass("ckbox");
      }
    });
    if($(this).attr("id")){
      var formid = $(this).attr("id");
      $("#"+formid).validate({
        errorPlacement: function (error, element) {
          if(admin){
            error.css({"color":"#E55","padding-left":"10px","display":"block"});
            error.appendTo($(element).parent());
          }
          else if (element.is(":radio")) {
            var $c = element.parent();
            $c.find("label.error").remove();
            error.css({"color":"#E55","padding-left":"10px","display":"block"});
            $c.prepend(error);
          }
          else if (element.is(":checkbox")) {
            var $c = element.parents('div.ckbox:first');
            $c.find("label.error").remove();
            error.css({"color":"#E55","padding-left":"10px","display":"block"});
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

      // add further validate when dynamic adding new element
      if(admin){
        $("#addEmail,#addPhone").click(function(){
          setTimeout(function(){
            $("#"+formid+" input[name*=email]:not(#email_1_email)").each(function(){
              $(this).rules("add", {required:false,email:true});
            });
            $("#"+formid+" input[name$='[phone]']:not(#phone_1_phone)").each(function(){
              $.amask.phone_add_validate(this, admin);
            });
          },1200);
        });
      }
      // only validate required when not in contact adding
      else{
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
          var $p = $(this).parents("div.ckbox:first");
          $p.find("label.error").remove();
          $(this).valid();
        });
        $("#"+formid+" input[name*=url]").each(function(){
          $(this).rules("add", {required:false,url:true});
        });
      }

      // only add validate when language is chinese.
      if(lang == 'zh-hant' && !skiptwcheck){
        // twid
        $("#"+formid+" input[name*=legal_identifier]").each(function(){
          $.amask.id_add_validate(this);
        });

        // phone
        if(admin){
          $("#"+formid+" input[name$='[phone]']").each(function(){
            $.amask.phone_add_validate(this, admin);
          });
        }
        else{
          $("#"+formid+" input[name*=phone]").each(function(){
            $.amask.phone_add_validate(this, admin);
          });
        }
      }
    }
  });
});
