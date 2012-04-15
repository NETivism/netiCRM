var cj = jQuery.noConflict(); $ = cj;
cj(document).ready(function(){
  // cj("#crm-container form").each(function(){
  cj.mask.definitions['~']='[+-]';
  // mobile
  cj("#crm-container input[name*=phone]").each(function(){
    var n = cj(this).attr('name');
    var re = /phone-(\w+)-(\d+)/g;
    var match = re.exec(n);

    // mobile
    var idx = match.length-1;
    if(match[idx] == '2'){
      cj(this).mask("+886-999999999");
    }
    else{
    // normal
      cj(this).mask("+886-999999999? #*****");
    }
    // add tip when focus
    cj(this).focus(function(){
      cj('.phone-tip').remove();
      cj('<span class="phone-tip" style="padding-left:10px;">請去掉電話號碼開頭0。如：0391111111，應填寫391111111</span>').insertAfter(this);
    });
    cj(this).blur(function(){
      cj('.phone-tip').remove();
    });
  });
  
});
