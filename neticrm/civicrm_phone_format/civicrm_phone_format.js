var cj = jQuery.noConflict(); $ = cj;
$(document).ready(function(){
  // $("#crm-container form").each(function(){
  cj.mask.definitions['~']='[+-]';
  // mobile
  $("#crm-container input[name*=phone]").each(function(){
    var n = $(this).attr('name');
    var re = /phone-(\w+)-(\d+)/g;
    var match = re.exec(n);

    // mobile
    var idx = match.length-1;
    if(match[idx] == '2'){
      $(this).mask("+886-999999999");
    }
    else{
    // normal
      $(this).mask("+886-999999999? #*****");
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
