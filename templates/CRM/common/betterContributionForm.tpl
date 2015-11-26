<script>
{literal}
jQuery(function($){
  if($('#custom_{/literal}{$receiptTitle}{literal},#custom_{/literal}{$receiptSerial}{literal}').length>=1){
    // receiptTitle, receiptSerial
    $('<div class="crm-section receipt_type"><div class="label"></div><div class="content"><input type="radio" name="receipt_type" id="r_person" ><label for="r_person">{/literal}{ts}Individual{/ts}{literal}</label><input name="receipt_type" type="radio" id="r_company" ><label for="r_company">{/literal}{ts}Legal{/ts}{literal}</label></div></div>')
    .insertBefore($('.custom_{/literal}{$receiptTitle}{literal}-section'));
    var OddOrEven = $('.custom_{/literal}{$receiptTitle}{literal}-section').attr('class').match(/crm-odd|crm-even/)[0];
    $('.receipt_type').addClass(OddOrEven);

    $('<span><input type="checkbox" name="same_as_post" id="same-as"><label for="same-as">{/literal}{ts}Same as Contributor{/ts}{literal}</label></span>').insertAfter($('#custom_{/literal}{$receiptTitle}{literal}'));
    $('#same-as').change(update_name);
    $('.receipt_type input').change(function(){
      if($('#r_person').is(':checked')){
        $('label[for="custom_{/literal}{$receiptTitle}{literal}"]').text('{/literal}{ts}Contact Name{/ts}{literal}');
        $('label[for="custom_{/literal}{$receiptSerial}{literal}"]').text('{/literal}{ts}Legal Identifier{/ts}{literal}');
      }
      if($('#r_company').is(':checked')){
        $('label[for="custom_{/literal}{$receiptTitle}{literal}"]').text('{/literal}{ts}Organization Name{/ts}{literal}');
        $('label[for="custom_{/literal}{$receiptSerial}{literal}"]').text('{/literal}{ts}Sic Code{/ts}{literal}');
      }
    })
  }

  // Display Donor Credit 
  if($('#custom_{/literal}{$receiptDonorCredit}{literal}').length>=1){

    var items = "<input type='radio' name='receipt_name' id='r_name_full' ><label for='r_name_full'>{/literal}{ts}Full Name{/ts}{literal}</label>";
items += "<input name='receipt_name' type='radio' id='r_name_half' ><label for='r_name_half'>{/literal}{ts}Part of Name{/ts}{literal}</label>";
items += "<input name='receipt_name' type='radio' id='r_name_hide' ><label for='r_name_hide'>{/literal}{ts}Anonymity{/ts}{literal}</label>";
items += "<input name='receipt_name' type='radio' id='r_name_custom' ><label for='r_name_custom'>{/literal}{ts}Custom Name{/ts}{literal}</label>";

    $(items).insertBefore($('#custom_{/literal}{$receiptDonorCredit}{literal}'));

    



    $('#last_name,#first_name,#legal_identifier').keyup(update_name);
    $('.custom_{/literal}{$receiptDonorCredit}{literal}-section input[type=radio]').change(update_name);
    update_name();

  }


  // Yes No Selection
  if($('.custom_{/literal}{$receiptYesNo}{literal}-section').length>=1){
    $('.custom_{/literal}{$receiptYesNo}{literal}-section .content input').change(showHideReceiptFields);
    $('.custom_{/literal}{$receiptYesNo}{literal}-section .content input').trigger('change');
  }


  function showHideReceiptFields(){
    // radio option 
    if($($('[name=custom_{/literal}{$receiptYesNo}{literal}]')[0]).attr('type') == 'radio'){
      var $no_label = false;
      $('.custom_{/literal}{$receiptYesNo}{literal}-section .content label').each(function(){
        if($(this).text().match(/不|{/literal}{ts}No{/ts}{literal}|no|don't|No|Don't/)){
          $no_label = $(this);
        }
      });
      var showFields = !$('#'+$no_label.attr('for')).is(':checked');
    }

    // checkbox 
    if($('.custom_{/literal}{$receiptYesNo}{literal}-section .content input.form-checkbox').attr('type') == 'checkbox'){
      var checkbox_is_no = $('.custom_12-section input+label').text().match(/不|{/literal}{ts}No{/ts}{literal}|no|don't|No|Don't/)?true:false;
      var showFields = checkbox_is_no ^ $('.custom_12-section .content input.form-checkbox').is(':checked');
    }
    if(showFields){
      {/literal}{if $receiptTitle}{literal}
      $('.custom_{/literal}{$receiptTitle}{literal}-section').show('slow');
      $('.custom_{/literal}{$receiptTitle}{literal}-section .label label .crm-marker').remove();
      $('.custom_{/literal}{$receiptTitle}{literal}-section').find('.label label').append('<span class="crm-marker" title="{/literal}{ts}This field is required.{/ts}{literal}">*</span>');
      {/literal}{/if}{literal}
      {/literal}{if $receiptSerial}{literal}
      $('.custom_{/literal}{$receiptSerial}{literal}-section').show('slow');
      $('.custom_{/literal}{$receiptSerial}{literal}-section .label label .crm-marker').remove();
      $('.custom_{/literal}{$receiptSerial}{literal}-section').find('.label label').append('<span class="crm-marker" title="{/literal}{ts}This field is required.{/ts}{literal}">*</span>');
      {/literal}{/if}{literal}
      $('.receipt_type').show('slow');
    }else{
      {/literal}{if $receiptTitle}{literal}
      $('.custom_{/literal}{$receiptTitle}{literal}-section').hide('slow');
      $('.custom_{/literal}{$receiptTitle}{literal}-section .label label .crm-marker').remove();
      {/literal}{/if}{literal}
      {/literal}{if $receiptSerial}{literal}
      $('.custom_{/literal}{$receiptSerial}{literal}-section').hide('slow');
      $('.custom_{/literal}{$receiptSerial}{literal}-section .label label .crm-marker').remove();
      {/literal}{/if}{literal}
      $('.receipt_type').hide('slow');
    }
  }


  function update_name(){
    if($('#same-as').is(':checked') && $('#last_name,#first_name').length > 1 && $('#r_person').is(':checked')){
        $('#custom_{/literal}{$receiptTitle}{literal}').val($('#last_name').val()+$('#first_name').val());
        //$('#custom_{/literal}{$receiptTitle}{literal}').attr('disabled','true');
      }
    if($('#same-as').is(':checked') && $('#legal_identifier').length >= 1 && $('#r_person').is(':checked')){
     $('#custom_{/literal}{$receiptSerial}{literal}').val($('#legal_identifier').val());
     $('#custom_{/literal}{$receiptSerial}{literal}').attr('disabled','true');
    }
    /*
    if(!$('#same-as').is(':checked')){
      $('#custom_{/literal}{$receiptSerial}{literal},#custom_{/literal}{$receiptTitle}{literal}').removeAttr('disabled');
    }
    */

    //Full Name
    if($('#r_name_full[@checked]').val()){
      if($('#last_name,#first_name').length>1){
        $('#custom_{/literal}{$receiptDonorCredit}{literal}').val($('#last_name').val()+$('#first_name').val());
        $('#custom_{/literal}{$receiptDonorCredit}{literal}').attr('disabled','true');
      }
    }

    // Part of Name
    if($('#r_name_half[@checked]').val()){
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
          }else{
            first_name = "*";
          }
          var name = last_name+first_name;
        }else{
          var name = "";
        }
        

        
        $('#custom_{/literal}{$receiptDonorCredit}{literal}').val(name);
        $('#custom_{/literal}{$receiptDonorCredit}{literal}').attr('disabled','true');
      }
    }

    // Anonymity
    if($('#r_name_hide[@checked]').val()){
      if($('#last_name,#first_name').length>1){
        $('#custom_{/literal}{$receiptDonorCredit}{literal}').val('{/literal}{ts}Anonymity{/ts}{literal}');
        $('#custom_{/literal}{$receiptDonorCredit}{literal}').attr('disabled','true');
      }
    }

    // Custom Name
    if($('#r_name_custom[@checked]').val()){
      if($('#last_name,#first_name').length>1){
        $('#custom_{/literal}{$receiptDonorCredit}{literal}').val($('#last_name').val()+$('#first_name').val());
        $('#custom_{/literal}{$receiptDonorCredit}{literal}').removeAttr('disabled');
      }
    }

  }

});

{/literal}
</script>
