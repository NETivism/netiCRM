$(document).ready(function(){
  // $("#crm-container form").each(function(){
  $.mask.definitions['~']='[+-]';
  // mobile
  $("#crm-container input[name*=phone]").each(function(){
    var n = $(this).attr('name');
    var re = /phone-(\w+)-(\d+)/g;
    var match = re.exec(n);
// console.log(match);
    var mobile = false;
    var tip;
    if(match != null){
      var idx = match.length - 1;
      if(match[idx] == '2'){
        mobile = true;
      }
    }
    // mobile
    if(mobile){
      $(this).mask("+886-999999999");
      tip = '請去掉電話號碼開頭0。如：0933222111，應填寫933222111';
    }
    else{
    // normal
      $(this).mask("+886-99999999?9#*****");
      tip = '請去掉電話號碼開頭0。如：089-333222（台東號碼），應填寫89333222';
    }
    // add tip when focus
    $(this).focus(function(){
      $('.phone-tip').remove();
      $('<span class="phone-tip" style="padding-left:10px;">'+tip+'</span>').insertAfter(this);
    });
    $(this).blur(function(){
      $('.phone-tip').remove();
    });
  });
  
});
