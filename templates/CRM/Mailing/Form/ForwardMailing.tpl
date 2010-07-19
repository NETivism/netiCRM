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
<div class="form-item">
<fieldset>
<legend>{ts}Forward Mailing{/ts}</legend>
<dt class="label">From</dt><dd>{$fromEmail}</dd>      
<br />{ts}Please enter up to 5 email addresses to receive the mailing.{/ts}
<dl>
<dt class="label">{$form.email_0.label}</dt><dd>{$form.email_0.html}</dd>
<dt class="label">{$form.email_1.label}</dt><dd>{$form.email_1.html}</dd>
<dt class="label">{$form.email_2.label}</dt><dd>{$form.email_2.html}</dd>
<dt class="label">{$form.email_3.label}</dt><dd>{$form.email_3.html}</dd>
<dt class="label">{$form.email_4.label}</dt><dd>{$form.email_4.html}</dd>
</dl>
<div id="comment_show">
    <a href="#" class="button" onclick="hide('comment_show'); show('comment'); document.getElementById('forward_comment').focus(); return false;"><span>&raquo; {ts}Add Comment{/ts}</span></a>
</div><div class="spacer"></div>
<div id="comment" style="display:none">
        <div class="form-item">
            <table class="form-layout">
            <tr><td><a href="#" onclick="hide('comment'); show('comment_show'); return false;"><img src="{$config->resourceBase}i/TreeMinus.gif" class="action-icon" alt="{ts}close section{/ts}"/></a></a>
                <label>{$form.forward_comment.label}</label></td>
                <td>{$form.forward_comment.html}<br /><br />
              &nbsp;{$form.html_comment.html}<br /></td>
       	    </tr>
            </table>
        </div>
</div> 
<dt></dt><dd>{$form.buttons.html}</dd>
</fieldset>
</div>

{literal}
<script type="text/javascript" >
var editor = {/literal}"{$editor}"{literal};
{/literal}
{if $editor eq "fckeditor"}
{literal}
	function FCKeditor_OnComplete( editorInstance )
	{
	 	oEditor = FCKeditorAPI.GetInstance('html_comment');
		loadEditor();	
		editorInstance.Events.AttachEvent( 'OnFocus') ;
    	}
{/literal}
{/if}
{if $editor eq "tinymce"}
{literal}
	function customEvent() {
		loadEditor();
		tinyMCE.get('html_comment').onKeyPress.add(function(ed, e) {
 		});
	}

tinyMCE.init({
	oninit : "customEvent"
});

{/literal}
{/if}
{literal}
</script>
{/literal}
