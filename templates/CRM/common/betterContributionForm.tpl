<script>
{literal}
cj(function($){
  if($('#custom_{/literal}{$receiptTitle}{literal},#custom_{/literal}{$receiptSerial}{literal}').length >= 1){
    // receiptTitle, receiptSerial
    var r_person = mdFormElement('radio', '{/literal}{ts}Individual Donor{/ts}{literal}', {name:'receipt_type', id:'r_person', checked:'checked'});
    var receipt_type = mdFormElement('radio', '{/literal}{ts}Organization Donor{/ts}{literal}', {name:'receipt_type', id:'r_company'});

    $('<div class="crm-section receipt_type"><div class="label"></div><div class="content">' + r_person + receipt_type +'</div></div>')
    .insertBefore($('.custom_{/literal}{$receiptTitle}{literal}-section'));
    //var OddOrEven = $('.custom_{/literal}{$receiptTitle}{literal}-section').attr('class').match(/crm-odd|crm-even/)[0];
    //$('.receipt_type').addClass(OddOrEven);

    $('#custom_{/literal}{$receiptTitle}{literal}').addClass('ignore-required');
    $('#custom_{/literal}{$receiptSerial}{literal}').addClass('ignore-required');

    var same_as = mdFormElement('checkbox', '{/literal}{ts}Same as Contributor{/ts}{literal}', { name:'same_as_post', id:'same_as'});
    $(same_as).insertBefore($('#custom_{/literal}{$receiptTitle}{literal}')).find('input').addClass('ignore-required');

    var $same_as_md = $('.md-checkbox[for="same_as"]');
    var $same_as_md_parent = $same_as_md.parent('.md-elem');
    $same_as_md.insertBefore($same_as_md_parent);
    $same_as_md.wrap('<div class="same-as-wrapper"></div>');

    $('#same_as').change(doCheckSameAs);
    $('#custom_{/literal}{$receiptSerial}{literal}').keyup(checkTWorOrgID).blur(checkTWorOrgID);
    $('.receipt_type input').change(function(){
      if($('#r_person').is(':checked')){
        $('#custom_{/literal}{$receiptTitle}{literal}').attr('placeholder',"{/literal}{ts}Contact Name{/ts}{literal}");
        $('#custom_{/literal}{$receiptSerial}{literal}').attr('placeholder',"{/literal}{ts}Legal Identifier{/ts}{literal}");
      }
      if($('#r_company').is(':checked')){
        $('#custom_{/literal}{$receiptTitle}{literal}').attr('placeholder',"{/literal}{ts}Organization{/ts}{literal}");
        $('#custom_{/literal}{$receiptSerial}{literal}').attr('placeholder',"{/literal}{ts}Sic Code{/ts}{literal}");
      }
    });

    $('#Main').submit('#custom_{/literal}{$receiptSerial}{literal}',function(){
      if(isShowChecked()){
        if(checkTWorOrgID()){
          return true;
        }else{
          $(window).scrollTop($('#custom_{/literal}{$receiptSerial}{literal}').offset().top - $(window).height()/2);
          return false;
        }
      }
      return true;
    });
    $('#custom_{/literal}{$receiptTitle}{literal},#custom_{/literal}{$receiptSerial}{literal}').focus(clearNameIdErrorMessage);
  }
  $('.receipt_type input').trigger('change').change(updateName);

  // Display Donor Credit
  if($('#custom_{/literal}{$receiptDonorCredit}{literal}').length>=1){
    var hornor_name = [
      mdFormElement('radio', '{/literal}{ts}Full Name{/ts}{literal}', {name:'receipt_name', id:'r_name_full'}),
      mdFormElement('radio', '{/literal}{ts}Part of Name{/ts}{literal}', {name:'receipt_name', id:'r_name_half'}),
      mdFormElement('radio', '{/literal}{ts}Anonymity{/ts}{literal}', {name:'receipt_name', id:'r_name_hide'}),
      mdFormElement('radio', '{/literal}{ts}Custom Name{/ts}{literal}', {name:'receipt_name', id:'r_name_custom'})
    ];
    var items = hornor_name.join('');

    $(items).insertBefore($('#custom_{/literal}{$receiptDonorCredit}{literal}'));

    $r_name_items_md = $('.md-radio[for^="r_name"]');
    $r_name_items_md_parent = $r_name_items_md.parent('.md-elem');
    $r_name_items_md.insertBefore($r_name_items_md_parent);
    $r_name_items_md.wrapAll('<div class="r-name-items"></div>');

    $('#last_name,#first_name,#legal_identifier').keyup(updateName);
    $('.custom_{/literal}{$receiptDonorCredit}{literal}-section input[type=radio]').change(updateName);
    updateName;

    $('.custom_{/literal}{$receiptDonorCredit}{literal}-section input[type=radio]').change(function (){
      var r_name_id = $(this).attr('id');
      var $r_name_textfield = $(this).closest('.r-name-items').next('.md-elem');
      if (r_name_id != 'r_name_custom') {
        $r_name_textfield.addClass('md-elem-readonly');
      }
      else {
        $r_name_textfield.removeClass('md-elem-readonly');
      }
    });
  }


  // Yes No Selection
  if($('.custom_{/literal}{$receiptYesNo}{literal}-section').length >= 1){
    $('.custom_{/literal}{$receiptYesNo}{literal}-section .content input').change(showHideReceiptFields);
    $('.custom_{/literal}{$receiptYesNo}{literal}-section .content input').trigger('change');
    $('.custom_{/literal}{$receiptYesNo}{literal}-section .content input').change(setRequiredFields);
    setRequiredFields();
  }

  if ($('#is_for_organization').length > 0) {
    formElemRebuild('#is_for_organization', 'checkbox');
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
      // remove $receiptSerial part in #18692
    }
    else{
      {/literal}{if $receiptTitle}{literal}
      $('.custom_{/literal}{$receiptTitle}{literal}-section .label label .crm-marker').remove();
      $('#custom_{/literal}{$receiptTitle}{literal}').removeClass('required');
      {/literal}{/if}{literal}
      // remove $receiptSerial part in #18692
    }
  }

  function isShowChecked(){
    // radio option
    if($($('[name=custom_{/literal}{$receiptYesNo}{literal}]')[0]).attr('type') == 'radio'){
      var $no_label = false;
      $('.custom_{/literal}{$receiptYesNo}{literal}-section .content input[type="radio"]').each(function(){
        if($(this).val().match(/0|false|no/)){
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

  function doCheckSameAs(){
    var $sameas = $('#same_as');
    var error = false;
    if( $sameas.is(':checked') && $('#r_person').is(':checked')){
      if($('#legal_identifier').length >= 1 && $('#custom_{/literal}{$receiptSerial}{literal}').length > 1){
        if(($('#legal_identifier').val() == '' ) || !validTWID($('#legal_identifier').val())){
          error = true;
        }
      }
      if($('#last_name,#first_name').length>1 && $('#custom_{/literal}{$receiptTitle}{literal}').length > 1){
        if(($('#last_name').val()+$('#first_name').val()) == ''){
          error = true;
        }
      }
      if(error){
        $sameas.prop('checked', false);
        if($('.name-id-error').length === 0){
          $sameas.parent().append('<label for="same_as" generated="true" class="error name-id-error" style="color: rgb(238, 85, 85); padding-left: 10px;">{/literal}{ts}Please verify name and Legal Identifier fields.{/ts}{literal}</label>');
        }
        return
      }
      $('#custom_{/literal}{$receiptTitle}{literal}').parent('.md-elem').addClass('md-elem-readonly');
    }
    else {
      $('#custom_{/literal}{$receiptTitle}{literal}').parent('.md-elem').removeClass('md-elem-readonly');
    }
    updateName();
  }

  function updateName(){
    if($('#r_person').is(':checked')){
      // $('#same_as').parents('.same-as-wrapper').show('slow');
    }
    else{
      // $('#same_as').parents('.same-as-wrapper').hide('slow');
      if($('#same_as').is(':checked')){
        $('#same_as').trigger('click');
      }
    }
    if($('#same_as').is(':checked') && $('#r_company').is(':checked') && $('#is_for_organization').length > 0 && $('#is_for_organization').is(':checked')){
      $('#custom_{/literal}{$receiptTitle}{literal}').val($('#organization_name').val());
    }else if($('#same_as').is(':checked') && $('#last_name,#first_name').length > 1 && $('#r_person').is(':checked')){
      $('#custom_{/literal}{$receiptTitle}{literal}').val($('#last_name').val()+$('#first_name').val()).attr('readonly','readonly');
    }
    else{
      $('#custom_{/literal}{$receiptTitle}{literal}').removeAttr('readonly');
    }
    if($('#same_as').is(':checked') && $('#r_company').is(':checked') && $('#is_for_organization').length > 0 && $('#is_for_organization').is(':checked')){
      $('#custom_{/literal}{$receiptSerial}{literal}').val($('#sic_code').val());
    }else if($('#same_as').is(':checked') && $('#legal_identifier').length >= 1 && $('#r_person').is(':checked')){
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

    clearNameIdErrorMessage();

  }

  function clearNameIdErrorMessage(){
    $('.name-id-error').remove();
  }

  function checkTWorOrgID(){
    removeTWIDErrorMsg();
    var value = $('#custom_{/literal}{$receiptSerial}{literal}').val();
    if(validTWID(value) || validOrgID(value)){
      $('#custom_{/literal}{$receiptSerial}{literal}').removeClass('error');
      return true;
    }else{
      $('#custom_{/literal}{$receiptSerial}{literal}').addClass('error').parent().append('<label for="custom_{/literal}{$receiptSerial}{literal}" class="error-twid" style="padding-left: 10px;color: #e55;">{/literal}{ts}Please enter correct Data ( in valid format ).{/ts}{literal}</label>');
      return false;
    }
  }

  function removeTWIDErrorMsg(){
    while($('#custom_{/literal}{$receiptSerial}{literal}').parent().find('.error-twid').length>=1){
      $('#custom_{/literal}{$receiptSerial}{literal}').parent().find('.error-twid').remove();
    }
  }

  function formElemRebuild(elem, type) {
    var $elem = $(elem);
    var classname = ' crm-form-elem crm-form-' + type;
    if (type == 'checkbox' || type == 'radio') {
      if ($elem.next('label').length > 0) {
        var $wrap = $elem.next('label');
        $wrap.addClass(classname);
        $wrap.html('<span class="elem-label">' + $wrap.html() + '</span>');
        $elem.prependTo($wrap);
      }
    }
  }
});

function validTWID(value){
  if(value=='')return true;
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

function validOrgID(value){
  if(value=='')return true;
  var checkRegex = RegExp("^[0-9]{8}$");
  if(checkRegex.test(value)){
    return true;
  }
  return false;
}
{/literal}
</script>
