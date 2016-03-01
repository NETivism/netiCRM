<script>
{literal}
cj(function($){
  if($('#custom_{/literal}{$receiptTitle}{literal},#custom_{/literal}{$receiptSerial}{literal}').length >= 1){
    // receiptTitle, receiptSerial
    $('<div class="crm-section receipt_type"><div class="label"></div><div class="content"><input type="radio" name="receipt_type" id="r_person" checked="checked"><label for="r_person">{/literal}{ts}Individual{/ts}{literal}</label><input name="receipt_type" type="radio" id="r_company" ><label for="r_company">{/literal}{ts}Legal{/ts}{literal}</label></div></div>')
    .insertBefore($('.custom_{/literal}{$receiptTitle}{literal}-section'));
    //var OddOrEven = $('.custom_{/literal}{$receiptTitle}{literal}-section').attr('class').match(/crm-odd|crm-even/)[0];
    //$('.receipt_type').addClass(OddOrEven);

    $('<div><input type="checkbox" name="same_as_post" id="same-as" novalidate="novalidate"><label for="same-as">{/literal}{ts}Same as Contributor{/ts}{literal}</label></div>')
    .insertBefore($('#custom_{/literal}{$receiptTitle}{literal}'));

    $('#same-as').change(updateName);
    $('.receipt_type input').change(function(){
      if($('#r_person').is(':checked')){
        $('#custom_{/literal}{$receiptTitle}{literal}').attr('placeholder',"{/literal}{ts}Contact Name{/ts}{literal}");
        $('#custom_{/literal}{$receiptSerial}{literal}').attr('placeholder',"{/literal}{ts}Legal Identifier{/ts}{literal}");
        $('#custom_{/literal}{$receiptSerial}{literal}').off("keyup").keyup(function(){
          var value = $(this).val();
          while($(this).next().attr('class')=='error'){
            $(this).next().remove();
          }
          console.log(value);
          if(validTWID(value)){
            $(this).removeClass('error');
          }else{
            $(this).addClass('error').parent().append('<label for="custom_{/literal}{$receiptSerial}{literal}" class="error" style="padding-left: 10px;">{/literal}{ts}Please enter correct Data ( in valid format ).{/ts}{literal}</label>');
          }
        })
        
      }
      if($('#r_company').is(':checked')){
        $('#custom_{/literal}{$receiptTitle}{literal}').attr('placeholder',"{/literal}{ts}Organization{/ts}{literal}");
        $('#custom_{/literal}{$receiptSerial}{literal}').attr('placeholder',"{/literal}{ts}Sic Code{/ts}{literal}");
        $('#custom_{/literal}{$receiptSerial}{literal}').off("keyup")
      }
    });
  }
  $('.receipt_type input').trigger('change').change(updateName);

  // Display Donor Credit 
  if($('#custom_{/literal}{$receiptDonorCredit}{literal}').length>=1){

    var items = "<input type='radio' name='receipt_name' id='r_name_full' ><label for='r_name_full'>{/literal}{ts}Full Name{/ts}{literal}</label>";
items += "<input name='receipt_name' type='radio' id='r_name_half' ><label for='r_name_half'>{/literal}{ts}Part of Name{/ts}{literal}</label>";
items += "<input name='receipt_name' type='radio' id='r_name_hide' ><label for='r_name_hide'>{/literal}{ts}Anonymity{/ts}{literal}</label>";
items += "<input name='receipt_name' type='radio' id='r_name_custom' ><label for='r_name_custom'>{/literal}{ts}Custom Name{/ts}{literal}</label>";

    $(items).insertBefore($('#custom_{/literal}{$receiptDonorCredit}{literal}'));

    $('#last_name,#first_name,#legal_identifier').keyup(updateName);
    $('.custom_{/literal}{$receiptDonorCredit}{literal}-section input[type=radio]').change(updateName);
    updateName;
  }


  // Yes No Selection
  if($('.custom_{/literal}{$receiptYesNo}{literal}-section').length >= 1){
    $('.custom_{/literal}{$receiptYesNo}{literal}-section .content input').change(showHideReceiptFields);
    $('.custom_{/literal}{$receiptYesNo}{literal}-section .content input').trigger('change');
    $('.custom_{/literal}{$receiptYesNo}{literal}-section .content input').change(setRequiredFields);
  }

  function showHideReceiptFields(){
    if(isShowChecked()){
      {/literal}{if $receiptTitle}{literal}
      $('.custom_{/literal}{$receiptTitle}{literal}-section').show('slow');
      {/literal}{/if}{literal}
      {/literal}{if $receiptSerial}{literal}
      $('.custom_{/literal}{$receiptSerial}{literal}-section').show('slow');
      {/literal}{/if}{literal}
      $('.receipt_type').show('slow');
    }
    else{
      {/literal}{if $receiptTitle}{literal}
      $('.custom_{/literal}{$receiptTitle}{literal}-section').hide('slow');
      {/literal}{/if}{literal}
      {/literal}{if $receiptSerial}{literal}
      $('.custom_{/literal}{$receiptSerial}{literal}-section').hide('slow');
      {/literal}{/if}{literal}
      $('.receipt_type').hide('slow');
    }
  }

  function setRequiredFields(){
    if(isShowChecked()){
      {/literal}{if $receiptTitle}{literal}
      $('.custom_{/literal}{$receiptTitle}{literal}-section .label label .crm-marker').remove();
      $('.custom_{/literal}{$receiptTitle}{literal}-section').find('.label label').append('<span class="crm-marker" title="{/literal}{ts}This field is required.{/ts}{literal}">*</span>');
      $('#custom_{/literal}{$receiptTitle}{literal}').addClass('required');
      {/literal}{/if}{literal}
      {/literal}{if $receiptSerial}{literal}
      $('.custom_{/literal}{$receiptSerial}{literal}-section .label label .crm-marker').remove();
      $('.custom_{/literal}{$receiptSerial}{literal}-section').find('.label label').append('<span class="crm-marker" title="{/literal}{ts}This field is required.{/ts}{literal}">*</span>');
      $('#custom_{/literal}{$receiptSerial}{literal}').addClass('required');
      {/literal}{/if}{literal}
    }
    else{
      {/literal}{if $receiptTitle}{literal}
      $('.custom_{/literal}{$receiptTitle}{literal}-section .label label .crm-marker').remove();
      $('#custom_{/literal}{$receiptTitle}{literal}').removeClass('required');
      {/literal}{/if}{literal}
      {/literal}{if $receiptSerial}{literal}
      $('.custom_{/literal}{$receiptSerial}{literal}-section .label label .crm-marker').remove();
      $('#custom_{/literal}{$receiptSerial}{literal}').removeClass('required');
      {/literal}{/if}{literal}
    }
  }

  function isShowChecked(){
    // radio option 
    if($($('[name=custom_{/literal}{$receiptYesNo}{literal}]')[0]).attr('type') == 'radio'){
      var $no_label = false;
      $('.custom_{/literal}{$receiptYesNo}{literal}-section .content input[type="radio"]').each(function(){
        if(!$(this).val().match(/1|true|yes/)){
          $no_label = $(this);
        }
      });
      var showFields = !$no_label.is(':checked');
      return showFields;
    }

    // checkbox
    if($('.custom_{/literal}{$receiptYesNo}{literal}-section .content input.form-checkbox').attr('type') == 'checkbox'){
      var checkbox_is_no = $('.custom_{/literal}{$receiptYesNo}{literal}-section input:checked').text().match(/{/literal}{ts}No{/ts}{literal}|no|don't|No|Don't/) ? true : false;
      var showFields = checkbox_is_no ^ $('.custom_{/literal}{$receiptYesNo}{literal}-section .content input.form-checkbox').is(':checked');
    }
    return showFields;
  }


  function updateName(){
    if($('#r_person').is(':checked')){
      $('#same-as').parent('div').show();
    }
    else{
      $('#same-as').parent('div').hide();
    }
    if($('#same-as').is(':checked') && $('#last_name,#first_name').length > 1 && $('#r_person').is(':checked')){
        $('#custom_{/literal}{$receiptTitle}{literal}').val($('#last_name').val()+$('#first_name').val()).attr('readonly','readonly');
    }
    else{
      $('#custom_{/literal}{$receiptTitle}{literal}').removeAttr('readonly');
    }
    if($('#same-as').is(':checked') && $('#legal_identifier').length >= 1 && $('#r_person').is(':checked')){
      $('#custom_{/literal}{$receiptSerial}{literal}').val($('#legal_identifier').val()).attr('readonly', 'readonly');
    }
    else{
      $('#custom_{/literal}{$receiptSerial}{literal}').removeAttr('readonly');
    }

    //Full Name
    if($('#r_name_full:checked').val()){
      if($('#last_name,#first_name').length>1){
        $('#custom_{/literal}{$receiptDonorCredit}{literal}').val($('#last_name').val()+$('#first_name').val());
        $('#custom_{/literal}{$receiptDonorCredit}{literal}').attr('readonly','readonly');
      }
    }

    // Part of Name
    if($('#r_name_half:checked').val()){
      if($('#last_name,#first_name').length>1){
        var last_name = $('#last_name').val()?$('#last_name').val():"";
        var first_name = $('#first_name').val()?$('#first_name').val():"";
        if(last_name || first_name){
          var last_name_leng = last_name.length;
          if(last_name_leng){
            last_name = last_name[0];
            for (var i = 1; i < last_name_leng; i++) {
              last_name += "*";
            };  
          }
          

          var first_name_leng = first_name.length;
          if(first_name_leng>1){
            
            first_name = first_name[first_name_leng-1];
            for (var i = 0; i < first_name_leng-1; i++) {
              first_name = "*"+first_name;

            };
          }
          else{
            first_name = "*";
          }
          var name = last_name+first_name;
        }
        else{
          var name = "";
        }

        $('#custom_{/literal}{$receiptDonorCredit}{literal}').val(name);
        $('#custom_{/literal}{$receiptDonorCredit}{literal}').attr('readonly','readonly');
      }
    }

    // Anonymity
    if($('#r_name_hide:checked').val()){
      if($('#last_name,#first_name').length>1){
        $('#custom_{/literal}{$receiptDonorCredit}{literal}').val('{/literal}{ts}Anonymity{/ts}{literal}');
        $('#custom_{/literal}{$receiptDonorCredit}{literal}').attr('readonly','readonly');
      }
    }

    // Custom Name
    if($('#r_name_custom:checked').val()){
      if($('#last_name,#first_name').length>1){
        $('#custom_{/literal}{$receiptDonorCredit}{literal}').val($('#last_name').val()+$('#first_name').val());
        $('#custom_{/literal}{$receiptDonorCredit}{literal}').removeAttr('readonly');
      }
    }

    $('#custom_{/literal}{$receiptTitle}{literal} input.required:visible:not([type=checkbox])').trigger('blur');

  }

});

function validTWID(value){
  value = value.toUpperCase();
  var tab = "ABCDEFGHJKLMNPQRSTUVXYWZIO";
  var A1 = new Array (1,1,1,1,1,1,1,1,1,1,2,2,2,2,2,2,2,2,2,2,3,3,3,3,3,3 );
  var A2 = new Array (0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5 );
  var Mx = new Array (9,8,7,6,5,4,3,2,1,1);

  if ( value.length != 10 ){
    return false;
  }
  var i = tab.indexOf( value.charAt(0) );
  if ( i == -1 ){
    return false;
  }
  var sum = A1[i] + A2[i]*9;

  for( i=1; i<10; i++ ){
    var v = parseInt( value.charAt(i) );
    if ( isNaN(v) ){
      return false;
    }
    sum = sum + v * Mx[i];
  }
  if ( sum % 10 != 0 ){
    return false;
  }
  return true;
}


{/literal}
</script>
