{*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
{strip}
{if $batchUpdate}
    {if !$elementId}
        {assign var='elementId'   value=$form.field.$elementIndex.$elementName.id}
    {/if}
    {assign var="tElement" value=$elementName|cat:"_time"}
    {assign var="timeElement" value=field_`$elementIndex`_`$elementName`_time}
    {$form.field.$elementIndex.$elementName.html|crmReplace:class:dateplugin}
    &nbsp;&nbsp;{$form.field.$elementIndex.$tElement.label}&nbsp;&nbsp;{$form.field.$elementIndex.$tElement.html|crmReplace:class:six}
{elseif $elementIndex}
    {if !$elementId}
        {assign var='elementId'   value=$form.$elementName.$elementIndex.id}
    {/if}
    {assign var="timeElement" value=$elementName|cat:"_time.$elementIndex"}
    {$form.$elementName.$elementIndex.html|crmReplace:class:dateplugin}
{elseif $blockId and $blockSection}
    {if !$elementId}
        {assign var='elementId'   value=$form.$blockSection.$blockId.$elementName.id}
    {/if}
    {assign var="tElement" value=`$elementName`_time}
    {$form.$blockSection.$blockId.$elementName.html|crmReplace:class:dateplugin}
    {assign var="timeElement" value=`$blockSection`_`$blockId`_`$elementName`_time}
    &nbsp;&nbsp;{$form.$blockSection.$blockId.$tElement.label}&nbsp;&nbsp;{$form.$blockSection.$blockId.$tElement.html|crmReplace:class:six}
{else}
    {if !$elementId}
        {assign var='elementId'   value=$form.$elementName.id}
    {/if}
    {assign var="timeElement" value=$elementName|cat:'_time'}
    {$form.$elementName.html|crmReplace:class:dateplugin}
{/if}
{assign var='dateFormated' value=$elementId|cat:"_hidden"}<input type="text" name="{$dateFormated}" id="{$dateFormated}" class="hiddenElement"/>
{if $timeElement AND !$tElement}
    &nbsp;&nbsp;{$form.$timeElement.label}&nbsp;&nbsp;{$form.$timeElement.html|crmReplace:class:six}
{/if}
{if $action neq 4 && $action neq 1028}
    <span class="crm-clear-link">(<a href="javascript:clearDateTime( '{$elementId}' );">{ts}clear{/ts}</a>)</span>
{/if}
{if not $form.$elementName.frozen}
<script type="text/javascript">
    {literal}
    cj( function() {
      {/literal}
      var element_date   = "#{$elementId}";
      {if $timeElement}
          var element_time  = "#{$timeElement}";
          var time_format   = cj( element_time ).attr('timeFormat');
          {literal}
              cj(element_time).timeEntry({ show24Hours : time_format, spinnerImage: '' });
          {/literal}
      {/if}
      var currentYear = new Date().getFullYear();
      var date_format = cj( element_date ).attr('format');
      var alt_field   = 'input#{$dateFormated}';
      var yearRange   = currentYear - parseInt( cj( element_date ).attr('startOffset') );
          yearRange  += ':';
          yearRange  += currentYear + parseInt( cj( element_date ).attr('endOffset'  ) );
      {literal}

      var lcMessage = {/literal}"{$config->lcMessages}"{literal};
      var localisation = lcMessage.replace("_", "-");
      var defaultDate = (cj( element_date ).attr('formattype') == "birth")?'-30y':'today';
      if(date_format.search('d') == -1){
        cj(element_date).click(function(){
          cj('.ui-datepicker-calendar').hide();
          cj('.ui-corner-all').click(function(){
            cj('.ui-datepicker-calendar').hide();
          });
        });
        cj(element_date).datepicker({  
          changeMonth: true,
          changeYear: true,
          showButtonPanel: true,
          dateFormat: date_format,
          altField: alt_field,
          altFormat: 'mm/dd/yy',
          onChangeMonthYear: function(dateText, inst){
            setTimeout(function(){cj('.ui-datepicker-calendar').hide();},1);
          },
          onClose: function(dateText, inst) { 
            cj(".ui-datepicker-calendar").show();
            var month = cj("#ui-datepicker-div .ui-datepicker-month :selected").val();
            var year = cj("#ui-datepicker-div .ui-datepicker-year :selected").val();
            cj(this).datepicker('setDate', new Date(year, month, 1));
            cj('.ui-corner-all').off('click');
          }
        })
      }else{
        cj(element_date).datepicker({
                                      closeAtTop        : true,
                                      dateFormat        : date_format,
                                      changeMonth       : true,
                                      changeYear        : true,
                                      altField          : alt_field,
                                      altFormat         : 'mm/dd/yy',
                                      yearRange         : yearRange,
                                      regional          : localisation,
                                      defaultDate       : defaultDate
                                  });
      }
      cj(element_date).click( function( ) {
          hideYear( this );
      });
      cj('.ui-datepicker-trigger').click( function( ) {
          hideYear( cj(this).prev() );
      });
    });

    function hideYear( element ) {
        var format = cj( element ).attr('format');
        if ( format == 'dd-mm' || format == 'mm/dd' ) {
            cj(".ui-datepicker-year").css( 'display', 'none' );
        }
    }

    function clearDateTime( element ) {
        cj('input#' + element + ',input#' + element + '_time').val('');
    }
    {/literal}
</script>
{/if}
{/strip}
