{*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.1                                                |
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
{* This form is for Contact Add/Edit interface *}
{if $addBlock}
{include file="CRM/Contact/Form/Edit/$blockName.tpl"}
{else}
<div class="crm-submit-buttons">
   {$form.buttons.html}
</div>
<span style="float:right;"><a href="#expand" id="expand">{ts}Expand all tabs{/ts}</a></span>
<br/>
<div class="accordion ui-accordion ui-widget ui-helper-reset">
    <h3 class="head"> 
        <span class="ui-icon ui-icon-triangle-1-e" id='contact'></span><a href="#">{ts}Contact Details{/ts}</a>
    </h3>
    <div id="contactDetails" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom">
        <fieldset>
        {include file="CRM/Contact/Form/Edit/$contactType.tpl"}
        <br/>
        <table class="form-layout-compressed">
            {foreach from=$blocks item="label" key="block"}
               {include file="CRM/Contact/Form/Edit/$block.tpl"}
            {/foreach}
		</table>
		<table class="form-layout-compressed">
            <tr class="last-row">
            {if $form.home_URL}
              <td>{$form.home_URL.label}<br />
                  {$form.home_URL.html}
              </td>
            {/if}
              <td>{$form.contact_source.label}<br />
                  {$form.contact_source.html}
              </td>
              <td>{$form.external_identifier.label}<br />
                  {$form.external_identifier.html}
              </td>
              {if $contactId}
				<td><label for="internal_identifier">{ts}Internal Id{/ts}</label><br />{$contactId}</td>
			  {/if}
            </tr>            
        </table>

        {*  add dupe buttons *}
        {$form._qf_Contact_refresh_dedupe.html}
        {if $isDuplicate}&nbsp;&nbsp;{$form._qf_Contact_upload_duplicate.html}{/if}
        <div class="spacer"></div>
        </fieldset>
    </div>
    
    {foreach from = $editOptions item = "title" key="name"}
        {include file="CRM/Contact/Form/Edit/$name.tpl"}
    {/foreach}
</div>
<br />
<div class="crm-submit-buttons">
   {$form.buttons.html}
</div>

{literal}
<script type="text/javascript" >
var action = "{/literal}{$action}{literal}";
cj(function( ) {
    cj('.accordion .head').addClass( "ui-accordion-header ui-helper-reset ui-state-default ui-corner-all");

    cj('.accordion .head').hover( function( ) { 
        cj(this).addClass( "ui-state-hover");
    }, function() { 
        cj(this).removeClass( "ui-state-hover");
    }).bind('click', function( ) { 
        var checkClass = cj(this).find('span').attr( 'class' );
        var len        = checkClass.length;
        if ( checkClass.substring( len - 1, len ) == 's' ) {
            cj(this).find('span').removeClass( ).addClass('ui-icon ui-icon-triangle-1-e');
        } else {
            cj(this).find('span').removeClass( ).addClass('ui-icon ui-icon-triangle-1-s');
        }
        cj(this).next( ).toggle(); return false; 
    }).next( ).hide( );
    
    cj('span#contact').removeClass( ).addClass('ui-icon ui-icon-triangle-1-s');
    cj("#contactDetails").show( );
	
	cj('div.accordion div.ui-accordion-content').each( function() {
		//remove tab which doesn't have any element
		if ( ! cj.trim( cj(this).text() ) ) { 
			ele     = cj(this);
			prevEle = cj(this).prev();
			cj( ele ).remove();
			cj( prevEle).remove();
		}
		//open tab if form rule throws error
		if ( cj(this).children().find('span.error').text() ) {
			cj(this).show().prev().children('span:first').removeClass( ).addClass('ui-icon ui-icon-triangle-1-s');
		}
	});
	if ( action == 2 ) {
		//highlight the tab having data inside.
		cj('div.accordion div.ui-accordion-content :input').each( function() { 
			var element = cj(this).closest("div.ui-accordion-content").attr("id");
			eval('var ' + element + ' = "";');
			switch( cj(this).attr('type') ) {
			case 'checkbox':
			case 'radio':
			  if( cj(this).is(':checked') ) {
			    eval( element + ' = true;'); 
			  }
			  break;
			  
			case 'text':
			case 'textarea':
			  if( cj(this).val() ) {
			    eval( element + ' = true;');
			  }
			  break;
			  
			case 'select-one':
			case 'select-multiple':
			  if( cj('select option:selected' ) && cj(this).val() ) {
			    eval( element + ' = true;');
			  }
			  break;		
			  
			case 'file':
			  if( cj(this).next().html() ) eval( element + ' = true;');
			  break;
			}
			if( eval( element + ';') ) { 
			  cj(this).closest("div.ui-accordion-content").prev().children('a:first').css( 'font-weight', 'bold' );
			}
		});
	}
});

cj('a#expand').click( function( ){
    if( cj(this).attr('href') == '#expand') {   
        var message     = {/literal}"{ts}Collapse all tabs{/ts}"{literal};
        var className   = 'ui-icon ui-icon-triangle-1-s';
        var event       = 'show';
        cj(this).attr('href', '#collapse');
    } else {
        var message     = {/literal}"{ts}Expand all tabs{/ts}"{literal};
        var className   = 'ui-icon ui-icon-triangle-1-e';
        var event       = 'hide';
        cj(this).attr('href', '#expand');
    }
    
    cj(this).html(message);
    cj('div.accordion div.ui-accordion-content').each(function() {
        cj(this).parent().find('h3 span').removeClass( ).addClass(className);
        eval( " var showHide = cj(this)." + event + "();" );
    }); 
});

//current employer default setting
var employerId = "{/literal}{$currentEmployer}{literal}";
if ( employerId ) {
    var dataUrl = "{/literal}{crmURL p='civicrm/ajax/contactlist' h=0 q="org=1&id=" }{literal}" + employerId ;
    cj.ajax({ 
        url     : dataUrl,   
        async   : false,
        success : function(html){
            //fixme for showing address in div
            htmlText = html.split( '|' , 2);
            cj('input#current_employer').val(htmlText[0]);
            cj('input#current_employer_id').val(htmlText[1]);
        }
    }); 
}

cj("input#current_employer").click( function( ) {
    cj("input#current_employer_id").val('');
});
</script>
{/literal}

{* include common additional blocks tpl *}
{include file="CRM/common/additionalBlocks.tpl"}

{* include jscript to warn if unsaved form field changes *}
{include file="CRM/common/formNavigate.tpl"}

{/if}
