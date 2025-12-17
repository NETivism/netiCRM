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
{if $showCMS }{*true if is_cms_user field is set *}
  <div class="messages help cms_user_help-section">
  {if $isPcP}
    {ts}Please fill in the basic information to register for an account that can edit a personal campaign page. The account registration rules are as follows:{/ts}</br>
    {ts}1. One email can only register one account.{/ts}</br>
    {ts}2. If you have already registered an account on this site, you can click Forgot Password, enter your email, and get the login link from your mailbox.{/ts}</br>
  {else}
    {if !$isCMS}
      {ts}If you would like to create an account on this site, check the box below and enter a user name{/ts}
      {if $form.cms_pass}
        {ts}and a password{/ts}
      {/if}
      {else}
        {ts}Please enter a user name to create an account{/ts}
      {/if}
      {ts 1=$loginUrl}If you already have an account, <a href='%1'>please login</a> before completing this form.{/ts}
  {/if}
    </div>
  <div>{$form.cms_create_account.html} {$form.cms_create_account.label}</div>
  <div id="details" class="crm_user_signup-section">
    <div class="crm-section cms_name-section">
      <div class="label" for="">{$form.cms_name.label}</div>
      <div class="content">
        {$form.cms_name.html}
        {if $config->userFrameworkVersion < 8}<a id="checkavailability" href="#" onClick="return false;">{ts}<strong>Check Availability</strong>{/ts}</a>{/if}
        <div id="msgbox" style="display:none"></div>
        <div class="description">{ts}Your preferred username; punctuation is not allowed except for periods, hyphens, and underscores.{/ts}</div>
      </div>
    </div>
    {if $form.cms_pass}
    <div class="crm-section cms_pass-section">
      <div class="label">{$form.cms_pass.label}</div>
      <div class="content">
        {$form.cms_pass.html}
      </div>
    </div>
    <div class="crm-section crm_confirm_pass-section">
      <div class="label">{$form.cms_confirm_pass.label}</div>
      <div class="content">
        {$form.cms_confirm_pass.html}
      </div>
    </div>
    {/if}
  </div>

{literal}
<script type="text/javascript">
{/literal}
{if !$isCMS}
{literal}
  if ( document.getElementsByName("cms_create_account")[0].checked ) {
    show('details');
  }
  else {
	  hide('details');
  }
{/literal}
{/if}
{literal}
function showMessage(frm){
  var cId = {/literal}'{$cId}'{literal};
  if ( cId ) {
	  alert('{/literal}{ts escape="js"}You are logged-in user{/ts}{literal}');
	  frm.checked = false;
  }
  else {
	  var siteName = {/literal}'{$config->userFrameworkBaseURL}'{literal};
	  alert('{/literal}{ts escape="js"}Please login if you have an account on this site with the link{/ts}{literal} ' + siteName  );
  }
}
var lastName = null;
{/literal}{if $config->userFrameworkVersion < 8}{literal}
cj("#checkavailability").click(function() {
  var cmsUserName = cj.trim(cj("#cms_name").val());
  if ( lastName == cmsUserName) {
    /*if user checking the same user name more than one times. avoid the ajax call*/
    return;
  }
  /*don't allow special character and for joomla minimum username length is two*/

  var spchar = "\<|\>|\"|\'|\%|\;|\(|\)|\&|\\\\|\/";

  {/literal}{if $config->userFramework == "Drupal"}{literal}
	spchar = spchar + "|\~|\`|\:|\@|\!|\=|\#|\$|\^|\*|\{|\}|\\[|\\]|\+|\?|\,"; 
  {/literal}{/if}{literal}	
  var r = new RegExp( "["+spchar+"]", "i");
  /*regular expression \\ matches a single backslash. this becomes r = /\\/ or r = new RegExp("\\\\").*/
  if ( r.exec(cmsUserName) ) {
	  alert('{/literal}{ts escape="js"}Your username contains invalid characters{/ts}{literal}');
    return;
  } 
  {/literal}{if $config->userFramework == "Joomla"}{literal}
	else
  if ( cmsUserName && cmsUserName.length < 2 ) {
	  alert('{/literal}{ts escape="js"}Your username is too short{/ts}{literal}');
	  return;	
	}
  {/literal}{/if}{literal}
  if (cmsUserName) {
    /*take all messages in javascript variable*/
    var check        = "{/literal}{ts}Checking...{/ts}{literal}";
    var available    = "{/literal}<i class='zmdi zmdi-check-circle'></i>{ts}This username is currently available.{/ts}{literal}";
    var notavailable = "{/literal}<i class='zmdi zmdi-close-circle'></i>{ts}This username is taken.{/ts}{literal}";
         
    //remove all the class add the messagebox classes and start fading
    cj("#msgbox").removeClass().addClass('cmsmessagebox').text(check).fadeIn("slow");
	 
    //check the username exists or not from ajax
    var qfKey = '{/literal}{$cmsQfKey}{literal}';
	  var contactUrl = {/literal}"{crmURL p='civicrm/ajax/cmsuser' h=0 q="qfKey=`$cmsQfKey`&ctrName=`$cmsCtrName`"}"{literal};
	 
    cj.post(contactUrl,{ cms_name:cj("#cms_name").val() } ,function(data) {
	    if ( data.name == "no") { // user name not available
	      cj("#msgbox").fadeTo(200,0.1,function() {
          cj(this).html(notavailable).addClass('cmsmessagebox cmsmessagebox-error').fadeTo(900,1);
        });
	    }
      else {
	      cj("#msgbox").fadeTo(200,0.1,function() {
          cj(this).html(available).addClass('cmsmessagebox cmsmessagebox-success').fadeTo(900,1);
        });
	    }	    
	  }, "json");
	  lastName = cmsUserName;
  }
  else {
    cj("#msgbox").removeClass().text('').fadeIn("fast");
  }
});
{/literal}{/if}{*do not check user name on drupal9*}{literal}

</script>
{/literal}
  {if !$isCMS}	
    {include file="CRM/common/showHideByFieldValue.tpl" 
    trigger_field_id    ="cms_create_account"
    trigger_value       =""
    target_element_id   ="details" 
    target_element_type ="block"
    field_type          ="radio"
    invert              = 0
    }
  {/if}
{/if}{*showCMS*}
