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
{* template for adding form elements for selecting existing or creating new contact*}
{if $context ne 'search'}
    <tr id="contact-success" style="display:none;">
	<td></td>
	<td><span class="success-status">{ts}New contact has been created.{/ts}</span></td>
    </tr>
    <tr>
	<td class="label">{$form.contact.label}</td>
	<td>{$form.contact.html}
	    {if $form.profiles}
		&nbsp;&nbsp;{ts}OR{/ts}&nbsp;&nbsp;{$form.profiles.html}<div id="contact-dialog" style="display:none;"></div>
	    {/if}
	</td>
    </tr>
{/if}
{literal}
<script type="text/javascript">
  cj( function( ) {
      var contactUrl = {/literal}"{crmURL p='civicrm/ajax/contactlist' q='context=newcontact' h=0 }"{literal};

      cj('#contact').autocomplete( contactUrl, { 
          selectFirst : false, matchContains: true, minChars: 1
      }).result( function(event, data, formatted) { 
          cj("input[name=contact_select_id]").val(data[1]);
      }).focus( );

      cj("#contact").click( function( ) {
          cj("input[name=contact_select_id]").val('');
      });
                                  
      cj("#contact").bind("keypress keyup", function(e) {
          if ( e.keyCode == 13 ) {
              return false;
          }
      });
  });

  function newContact( gid ) {
      var dataURL = {/literal}"{crmURL p='civicrm/profile/create' q='reset=1&snippet=5&context=dialog' h=0 }"{literal};
      dataURL = dataURL + '&gid=' + gid;
      cj.ajax({
         url: dataURL,
         success: function( content ) {
             cj("#contact-dialog").show( ).html( content ).dialog({
         	    	title: "Create New Contact",
             		modal: true,
             		width: 680, 
             		overlay: { 
             			opacity: 0.5, 
             			background: "black" 
             		},

                 beforeclose: function(event, ui) {
                     cj(this).dialog("destroy");
                 }
             });
         }
      });
  }
        
</script>
{/literal}

