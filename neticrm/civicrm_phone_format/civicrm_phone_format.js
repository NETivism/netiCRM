$(document).ready(function(){
  // $("#crm-container form").each(function(){
  $.mask.definitions['~']='[+-]';
  // mobile
  $("#crm-container input[name*=phone]").each(function(){
    var n = $(this).attr('name');
    var re = /phone-(\w+)-(\d+)/g;
    var match = re.exec(n);
    var mobile = false;
    if(match != null){
      var idx = match.length;
      if(match[idx] == '2'){
        mobile = true;
      }
    }
    // mobile
    if(mobile){
      $(this).mask("+886-999999999");
    }
    else{
    // normal
      $(this).mask("+886-999999999?#*****");
    }
    // add tip when focus
    $(this).focus(function(){
      $('.phone-tip').remove();
      $('<span class="phone-tip" style="padding-left:10px;">請去掉電話號碼開頭0。如：0391111111，應填寫391111111</span>').insertAfter(this);
    });
    $(this).blur(function(){
      $('.phone-tip').remove();
    });
  });
  
});
