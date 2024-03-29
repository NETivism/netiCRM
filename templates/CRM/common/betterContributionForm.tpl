<script>
{literal}
cj(function($){
  if($('#custom_{/literal}{$receiptTitle}{literal},#custom_{/literal}{$receiptSerial}{literal}').length >= 1){
    // receiptTitle, receiptSerial
    var r_person = mdFormElement('radio', '{/literal}{ts}Individual Donor{/ts}{literal}', {name:'receipt_type', id:'r_person', value:'r_person'{/literal}{if $receipt_type neq 'r_company'}, checked:'checked'{/if}{literal}});
    var receipt_type = mdFormElement('radio', '{/literal}{ts}Organization Donor{/ts}{literal}', {name:'receipt_type', id:'r_company', value:'r_company'{/literal}{if $receipt_type eq 'r_company'}, checked:'checked'{/if}{literal}});

    $('<div class="crm-section receipt_type"><div class="label"></div><div class="content">' + r_person + receipt_type +'</div></div>')
    .insertBefore($('.custom_{/literal}{$receiptTitle}{literal}-section'));
    //var OddOrEven = $('.custom_{/literal}{$receiptTitle}{literal}-section').attr('class').match(/crm-odd|crm-even/)[0];
    //$('.receipt_type').addClass(OddOrEven);

    // Check if is_for_organization is on
    var $is_for_organization = $('[name="is_for_organization"]');
    if ($is_for_organization.length) {
      $('.receipt_type').css({'height':0,'margin':0,'overflow':'hidden'});
      if ($is_for_organization.attr('type') == 'checkbox') {
        $is_for_organization.change(function(){
          doChangeSameAsTarget();
        });
        if ($is_for_organization.is(':checked')) {
          doChangeSameAsTarget();
        }
      }
      else if($is_for_organization.attr('type') == 'hidden'){
        doChangeSameAsTarget();
      }
    }

    $('#custom_{/literal}{$receiptTitle}{literal}').addClass('ignore-required');
    $('#custom_{/literal}{$receiptSerial}{literal}').addClass('ignore-required');

    var same_as = mdFormElement('checkbox', '{/literal}{ts}Same as Contributor{/ts}{literal}', { name:'same_as_post', id:'same_as'});
    $(same_as).insertBefore($('#custom_{/literal}{$receiptTitle}{literal}')).find('input').addClass('ignore-required');

    var $same_as_md = $('.md-checkbox[for="same_as"]');
    var $same_as_md_parent = $same_as_md.parent('.md-elem');
    $same_as_md.insertBefore($same_as_md_parent);
    $same_as_md.wrap('<div class="same-as-wrapper"></div>');

    $('#same_as').change(doCheckSameAs);
    $('#custom_{/literal}{$receiptSerial}{literal}').keyup(doCheckTWorOrgID).blur(doCheckTWorOrgID);
    $('.receipt_type input').change(function(){
      if($('#r_person').is(':checked')){
        $('#custom_{/literal}{$receiptTitle}{literal}').attr('placeholder',"{/literal}{ts}Contact Name{/ts}{literal}");
        $('#custom_{/literal}{$receiptSerial}{literal}').attr('placeholder',"{/literal}{ts}Legal Identifier{/ts}{literal}");
        $('.same-as-wrapper').show('fast');
      }
      if($('#r_company').is(':checked')){
        $('#custom_{/literal}{$receiptTitle}{literal}').attr('placeholder',"{/literal}{ts}Organization{/ts}{literal}");
        $('#custom_{/literal}{$receiptSerial}{literal}').attr('placeholder',"{/literal}{ts}Sic Code{/ts}{literal}");
        if ($('[name="is_for_organization"]').length == 0) {
          $(same_as).prop('checked',false);
          $('.same-as-wrapper').hide('fast');
        }
      }
      doUpdateName();
    });

    {/literal}{if $receiptSerial}{literal}
    $('#Main').submit('#custom_{/literal}{$receiptSerial}{literal}',function(){
      {/literal}{if $receiptYesNo}{literal}
      // If has $receiptYesNo field, And need no receipt. skip id check.
      if(getRequireType() == 0) return true;
      {/literal}{/if}{literal}
      if($('#custom_{/literal}{$receiptSerial}{literal}').length > 0){
        if(doCheckTWorOrgID()){
          return true;
        }else{
          doScrollTo($('#custom_{/literal}{$receiptSerial}{literal}'));
          return false;
        }
      }
      return true;
    });
    $('#Main').submit('#custom_{/literal}{$receiptTitle}{literal}',function(){
      {/literal}{if $receiptTitle}{literal}
      // If has $receiptYesNo field, And need no receipt. skip id check.
      if(getRequireType() == 0) return true;
      {/literal}{/if}{literal}
      if($('#custom_{/literal}{$receiptTitle}{literal}').length > 0 && $('#custom_{/literal}{$receiptTitle}{literal}').hasClass('required') && $('#custom_{/literal}{$receiptTitle}{literal}').val() == ''){
        return false;
      }
      return true;
    });
    {/literal}{/if}{literal}

  }
  $('.receipt_type input').trigger('change').change(doCheckSameAs);

  // Display Donor Credit
  if($('#custom_{/literal}{$receiptDonorCredit}{literal}').length>=1){
    var hornor_name = [
      mdFormElement('radio', '{/literal}{ts}Full Name{/ts}{literal}', {name:'receipt_name', id:'r_name_full', value:'r_name_full'{/literal}{if $receipt_name eq 'r_name_full'}, checked: 'checked'{/if}{literal}}),
      mdFormElement('radio', '{/literal}{ts}Part of Name{/ts}{literal}', {name:'receipt_name', id:'r_name_half', value:'r_name_half'{/literal}{if $receipt_name eq 'r_name_half'}, checked: 'checked'{/if}{literal}}),
      {/literal}{if !$forbidCustomDonorCredit}{literal}
      mdFormElement('radio', '{/literal}{ts}Custom Name{/ts}{literal}', {name:'receipt_name', id:'r_name_custom', value:'r_name_custom'{/literal}{if $receipt_name eq 'r_name_custom'}, checked: 'checked'{/if}{literal}})
      {/literal}{/if}{literal}
    ];
    var items = hornor_name.join('');

    $(items).insertBefore($('#custom_{/literal}{$receiptDonorCredit}{literal}'));

    $r_name_items_md = $('.md-radio[for^="r_name"]');
    $r_name_items_md_parent = $r_name_items_md.parent('.md-elem');
    $r_name_items_md.insertBefore($r_name_items_md_parent);
    $r_name_items_md.wrapAll('<div class="r-name-items"></div>');

    $('#last_name,#first_name,#legal_identifier,#organization_name,#sic_code').keyup(doUpdateName);
    $('.custom_{/literal}{$receiptDonorCredit}{literal}-section input[type=radio]').change(doUpdateName);
    doUpdateName();

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
    $('.custom_{/literal}{$receiptYesNo}{literal}-section .content input').change(doShowHideReceiptFields);
    $('.custom_{/literal}{$receiptYesNo}{literal}-section .content input').trigger('change');
    $('.custom_{/literal}{$receiptYesNo}{literal}-section .content input').change(setRequiredFields);
    setRequiredFields();
  }

  if ($('#is_for_organization').length > 0) {
    formElemRebuild('#is_for_organization', 'checkbox');
  }

  {/literal}{if $same_as}{literal}
  $('#same_as').prop('checked',true);
  doUpdateName();
  {/literal}{/if}{literal}


  /**
   * Show or hide receipt related fields
   */
  function doShowHideReceiptFields(){
    doClearNameIdErrorMessage();
    $('.upload-info').remove();
    if($('#r_company').length > 0){
      $('#r_company').removeAttr('readonly').parent('label').css('pointer-events','');
      $('.upload-person-only-info').remove();
    }

    if(getRequireType() > 0){
      {/literal}{if $receiptTitle}{literal}
      $('.custom_{/literal}{$receiptTitle}{literal}-section').show('slow');
      {/literal}{/if}{literal}
      {/literal}{if $receiptSerial}{literal}
      $('.custom_{/literal}{$receiptSerial}{literal}-section').show('slow');
      if(getRequireType() == 2){
        if($('#r_person').length > 0){
          $('#r_person').prop('checked',true).trigger('change');
          $('#r_company').attr('readonly','readonly').parent('label').css('pointer-events','none');
          $('.receipt_type .content').append($('<div class="description upload-person-only-info">{/literal}{ts}To upload data is only for person contribution.{/ts}{literal}</div>'));
        }
        $('.custom_{/literal}{$receiptSerial}{literal}-section .content').append('<div class="description upload-info">{/literal}{ts}Please fill legal identifier to upload data.{/ts}{literal}</div>');
      }
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
    {/literal}{if $receiptTitle}{literal}
    $('.custom_{/literal}{$receiptTitle}{literal}-section .label label .crm-marker').remove();
    $('#custom_{/literal}{$receiptTitle}{literal}').removeClass('required');
    {/literal}{/if}{literal}

    {/literal}{if $receiptSerial}{literal}
    $('.custom_{/literal}{$receiptSerial}{literal}-section .label label .crm-marker').remove();
    $('#custom_{/literal}{$receiptSerial}{literal}').removeClass('required');
    {/literal}{/if}{literal}
    // remove $receiptSerial part in #18692

    var requireType = getRequireType();
    if(requireType > 0){
      {/literal}{if $receiptTitle}{literal}
      $('.custom_{/literal}{$receiptTitle}{literal}-section .label label .crm-marker').remove();
      $('.custom_{/literal}{$receiptTitle}{literal}-section').find('.label label').append('<span class="crm-marker" title="{/literal}{ts}This field is required.{/ts}{literal}">*</span>');
      $('#custom_{/literal}{$receiptTitle}{literal}').addClass('required');
      {/literal}{/if}{literal}
      // remove $receiptSerial part in #18692
      if(requireType == 2){
        $('.custom_{/literal}{$receiptSerial}{literal}-section .label label .crm-marker').remove();
        $('.custom_{/literal}{$receiptSerial}{literal}-section').find('.label label').append('<span class="crm-marker" title="{/literal}{ts}This field is required.{/ts}{literal}">*</span>');
        $('#custom_{/literal}{$receiptSerial}{literal}').addClass('required');
      }
      if($('#same_as').prop('checked') && Object.keys(doCheckSameAs(false)).length > 0 ){
        $('#same_as').prop('checked',false);
        doUpdateName();
      }
    }
  }

/**
 * Get need receipt field selected value. if the field is checkbox, only return boolean
 * @return number or boolean
 */
  function getRequireType(){
    // radio option
    if($('.custom_{/literal}{$receiptYesNo}{literal}-section input[type=radio]:checked').length){
      return $('.custom_{/literal}{$receiptYesNo}{literal}-section input[type=radio]:checked').val();
    }

    // checkbox
    if($('.custom_{/literal}{$receiptYesNo}{literal}-section .content input.form-checkbox').attr('type') == 'checkbox'){
      var checkbox_is_no = $('.custom_{/literal}{$receiptYesNo}{literal}-section input:checked').text().match(/{/literal}{ts}No{/ts}{literal}|no|don't|No|Don't/) ? true : false;
      var showFields = checkbox_is_no ^ $('.custom_{/literal}{$receiptYesNo}{literal}-section .content input.form-checkbox').is(':checked');
    }
    return showFields;
  }

  /**
   * Occur when press same_as button. Valid legal id and last_name, first_name fields, display error message.
   */
  function doCheckSameAs(isShowError){
    var originalId = "{/literal}{$originalId}{literal}";
    isShowError = (typeof(isShowError) !== 'undefined') ? isShowError : true;
    doClearNameIdErrorMessage();
    var $sameas = $('#same_as');
    var error = [];
    var legalIdentifier = $('#legal_identifier').val();
    if( $sameas.is(':checked') && $('#r_person').is(':checked')){
      if($('#legal_identifier').length >= 1 && $('#custom_{/literal}{$receiptSerial}{literal}').length >= 1 && $('#custom_{/literal}{$receiptSerial}{literal}').hasClass('required')){
        if($('#legal_identifier').val() == '' ){
          error['legal_identifier'] = '{/literal}{ts}Please fill legal identifier to upload data.{/ts}{literal}';
        }else if(!validTWID(legalIdentifier) && !validResidentID(legalIdentifier)){
          error['legal_identifier'] = '{/literal}{ts}Invalid value for field(s){/ts}{literal}';
        }
      }
      if($('#last_name,#first_name').length>1 && $('#custom_{/literal}{$receiptTitle}{literal}').length >= 1){
        if(($('#last_name').val()+$('#first_name').val()) == ''){
          error['last_name'] = '{/literal}{ts}This field is required.{/ts}{literal}';
          error['first_name'] = '{/literal}{ts}This field is required.{/ts}{literal}';
        }
      }
      if(Object.keys(error).length > 0 && isShowError){
        $sameas.prop('checked', false);
        if($('.name-id-error').length === 0){
          $sameas.parent().append('<label for="same_as" generated="true" class="error name-id-error" style="color: rgb(238, 85, 85); padding-left: 10px;">{/literal}{ts}Please verify name and Legal Identifier fields.{/ts}{literal}</label>');
          for(var id in error){
            var $element = $('#'+id);
            if(!$element.is('.error')){
              $element.addClass('error');
              $ele_next = $element.next();
              if(!$ele_next.is('label.error')){
                $element.after('<label for="'+id+'" generated="true" class="error" style="color: rgb(238, 85, 85); padding-left: 10px;">'+error[id]+'</label>');
              }else{
                $ele_next.show().text(error[id]);
              }
            }
          }
          doScrollTo($element);
        }
        return error;
      }
      $('#custom_{/literal}{$receiptTitle}{literal}').parent('.md-elem').addClass('md-elem-readonly');
    }
    else {
      $('#custom_{/literal}{$receiptTitle}{literal}').parent('.md-elem').removeClass('md-elem-readonly');
    }
    doUpdateName();
    return error;
  }

  /**
   * Scroll window to $element y-position.
   * @param   $element    jQuery object
   */
  function doScrollTo($element){
    var $html = $('html, body');
    var $w = $(window);
    $html.animate({scrollTop:$element.offset().top - $w.height()/2}, 'fast');
  }

  /**
   * Update last_name, first_name, and id to receipt fileds. Should trigger when any related fields change.
   */
  function doUpdateName(){
    var $is_for_organization = $('[name="is_for_organization"]');
    if($is_for_organization.is('[type="checkbox"]') && $is_for_organization.is(':checked')) {
      var is_for_organization = 1;
    }
    else if($is_for_organization.is('[type="hidden"]') && $is_for_organization.val() == 1) {
      var is_for_organization = 1;
    }

    if($('#r_person').is(':checked') || ($('#r_company').is(':checked') && is_for_organization)){
      // $('#same_as').parents('.same-as-wrapper').show('slow');
    }
    else{
      // $('#same_as').parents('.same-as-wrapper').hide('slow');
      if($('#same_as').is(':checked')){
        $('#same_as').trigger('click');
      }
    }

    // For Name
    if($('#same_as').is(':checked') && $('#r_company').is(':checked') && is_for_organization){
      $('#custom_{/literal}{$receiptTitle}{literal}').val($('#organization_name').val()).attr('readonly', 'readonly');
    }else if($('#same_as').is(':checked') && $('#last_name,#first_name').length > 1 && $('#r_person').is(':checked')){
      $('#custom_{/literal}{$receiptTitle}{literal}').val($('#last_name').val()+$('#first_name').val()).attr('readonly','readonly');
    }
    else{
      $('#custom_{/literal}{$receiptTitle}{literal}').removeAttr('readonly');
    }

    // For ReceiptSerial Number
    if($('#same_as').is(':checked') && $('#r_company').is(':checked') && is_for_organization){
      $('#custom_{/literal}{$receiptSerial}{literal}').val($('#sic_code').val()).attr('readonly', 'readonly');
    }else if($('#same_as').is(':checked') && $('#legal_identifier').length >= 1 && $('#r_person').is(':checked')){
      if(/^_+$/.test($('#legal_identifier').val())){
        $('#custom_{/literal}{$receiptSerial}{literal}').val("").attr('readonly', 'readonly');
      }else{
        $('#custom_{/literal}{$receiptSerial}{literal}').val($('#legal_identifier').val()).attr('readonly', 'readonly');
      }
    }
    else{
      $('#custom_{/literal}{$receiptSerial}{literal}').removeAttr('readonly');
    }

    //Full Name
    if($('#r_name_full:checked').val()){
      if($('#last_name,#first_name').length>1){
        if (is_for_organization) {
          $('#custom_{/literal}{$receiptDonorCredit}{literal}').val($('#organization_name').val());
        }
        else {
          $('#custom_{/literal}{$receiptDonorCredit}{literal}').val($('#last_name').val()+$('#first_name').val());
        }
        $('#custom_{/literal}{$receiptDonorCredit}{literal}').attr('readonly','readonly');
      }
      else {
        if (is_for_organization) {
          $('#custom_{/literal}{$receiptDonorCredit}{literal}').val($('#organization_name').val());
        }
        $('#custom_{/literal}{$receiptDonorCredit}{literal}').attr('readonly','readonly');
      }
    }

    // Part of Name
    else if($('#r_name_half:checked').val()){
      if (is_for_organization) {
        var name = $('#organization_name').val();
        if (name.length > 2) {
          name = name.substr(0,1) + "*".repeat(name.length - 2) + name.substr(-1);
        }
        else {
          name = "*".repeat(name.length);
        }
      }
      else if($('#last_name,#first_name').length>1){
        var last_name = $('#last_name').val() ? $('#last_name').val() : "";
        var first_name = $('#first_name').val() ? $('#first_name').val() : "";
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

      }
      if (name) {
        $('#custom_{/literal}{$receiptDonorCredit}{literal}').val(name);
        $('#custom_{/literal}{$receiptDonorCredit}{literal}').attr('readonly','readonly');
      }
    }

    // Custom Name
    else if($('#r_name_custom:checked').val()){
      if($('#last_name,#first_name').length>1){
        $('#custom_{/literal}{$receiptDonorCredit}{literal}').val($('#last_name').val()+$('#first_name').val());
        $('#custom_{/literal}{$receiptDonorCredit}{literal}').removeAttr('readonly');
      }
      else if ($is_for_organization) {
        $('#custom_{/literal}{$receiptDonorCredit}{literal}').val($('#organization_name').val());
        $('#custom_{/literal}{$receiptDonorCredit}{literal}').removeAttr('readonly');
      }
    }
    else {
      $('#custom_{/literal}{$receiptDonorCredit}{literal}').attr('readonly','readonly');
    }

    $('#custom_{/literal}{$receiptTitle}{literal} input.required:visible:not([type=checkbox])').trigger('blur');

  }

/**
 * Clear error messages of receipt fields .
 */
  function doClearNameIdErrorMessage(){
    var $elements = $('#custom_{/literal}{$receiptTitle}{literal},#custom_{/literal}{$receiptSerial}{literal}');
    $elements.removeClass('error')
    if($elements.next('label.error').length>0){
      $elements.next('label.error').remove();
    }
    $('.name-id-error').remove();
  }

/**
 * Valid receipt id field
 * @return boolean  passed or not
 */
  function doCheckTWorOrgID(){
    var originalId = "{/literal}{$originalId}{literal}";
    if (originalId) {
      $('#custom_{/literal}{$receiptSerial}{literal}').removeClass('error');
      return true;
    }
    while($('#custom_{/literal}{$receiptSerial}{literal}').parent().find('.error-twid').length>=1){
      $('#custom_{/literal}{$receiptSerial}{literal}').parent().find('.error-twid').remove();
    }
    var value = $('#custom_{/literal}{$receiptSerial}{literal}').val();
    if(validTWID(value) || validOrgID(value) || validResidentID(value)){
      $('#custom_{/literal}{$receiptSerial}{literal}').removeClass('error');
      return true;
    }else{
      $('#custom_{/literal}{$receiptSerial}{literal}').addClass('error').parent().append('<label for="custom_{/literal}{$receiptSerial}{literal}" class="error-twid" style="padding-left: 10px;color: #e55;">{/literal}{ts}Please enter correct Data ( in valid format ).{/ts}{literal}</label>');
      return false;
    }
  }

  function doChangeSameAsTarget(){
    var $is_for_organization = $('[name="is_for_organization"]');
    if($is_for_organization.is('[type="checkbox"]') && $is_for_organization.is(':checked')) {
      var is_for_organization = 1;
    }
    else if($is_for_organization.is('[type="hidden"]') && $is_for_organization.val() == 1) {
      var is_for_organization = 1;
    }
    if (is_for_organization) {
      $('#r_company').prop('checked', true);
      {/literal}{if $receiptYesNo}{literal}
      $('.custom_{/literal}{$receiptYesNo}{literal}-section input[value=2]').prop('checked', false).closest('tr').hide();
      $('.custom_{/literal}{$receiptYesNo}{literal}-section input[value=2]').prop('checked', false).closest('label').hide();
      {/literal}{/if}{literal}
    }
    else {
      $('#r_person').prop('checked', true);
      {/literal}{if $receiptYesNo}{literal}
      $('.custom_{/literal}{$receiptYesNo}{literal}-section input[value=2]').closest('tr').show();
      $('.custom_{/literal}{$receiptYesNo}{literal}-section input[value=2]').closest('label').show();
      {/literal}{/if}{literal}
    }
    $('#same_as').prop('checked',false);
    $('.receipt_type input').trigger('change');
    doUpdateName();
  }

/**
 * Used on #is_for_organization fields
 * @param  {[type]} elem [description]
 * @param  {[type]} type [description]
 */
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

/**
 * Validate TW ID, Should match TW ID formula.
 * @param  String value
 * @return boolean
 */
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

/**
 * Validate Organize ID. Should be 8 numbers.
 * @param  String value
 * @return boolean
 */
function validOrgID(value){
  if(value=='')return true;
  var checkRegex = RegExp("^[0-9]{8}$");
  if(checkRegex.test(value)){
    return true;
  }
  return false;
}

/**
 * Validate Resident Permit ID, Should match Resident Permit ID formula.
 * @param  String value
 * @return boolean
 */
function validResidentID(value) {
  if (value == '') return true;
  value = value.toUpperCase();
  var tab = "ABCDEFGHJKLMNPQRSTUVXYWZIO";
  var c = (tab.indexOf(value.substr(0, 1)) + 10) + '' + (tab.indexOf(value.substr(1, 1)) % 10) + value.substr(2, 8);
  var checkCode = parseInt(c.substr(0, 1));
  for (var i = 1; i <= 9; i++) {
    checkCode += (parseInt(c.substr(i, 1)) * (10 - i)) % 10;
  }
  checkCode += parseInt(c.substr(10, 1));
  if (checkCode % 10 == 0) {
    return true;
  }
  return false;
}
{/literal}
</script>
