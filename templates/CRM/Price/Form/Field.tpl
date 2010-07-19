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
{*Javascript function controls showing and hiding of form elements based on html type.*}
{literal}
<script type="text/Javascript">
    function option_html_type(form) { 
        var html_type = document.getElementById("html_type");
        var html_type_name = html_type.options[html_type.selectedIndex].value;

        if (html_type_name == "Text") {
            document.getElementById("price").style.display="block";
            document.getElementById("showoption").style.display="none";
        } else {
            document.getElementById("price").style.display="none";
            document.getElementById("showoption").style.display="block";
        }

        if (html_type_name == 'Radio' || html_type_name == 'CheckBox') {
			      document.getElementById("optionsPerLine").style.display="block";
			      document.getElementById("optionsPerLineDef").style.display="block";
        } else {
			      document.getElementById("optionsPerLine").style.display="none";
			      document.getElementById("optionsPerLineDef").style.display="none";
        }

        var radioOption, checkBoxOption;

        for (var i=1; i<=11; i++) {
            radioOption = 'radio'+i;
            checkBoxOption = 'checkbox'+i	
            if (html_type_name == 'Radio' || html_type_name == 'CheckBox' || html_type_name == 'Select') {
                if (html_type_name == "CheckBox") {
                    document.getElementById(checkBoxOption).style.display="block";
                    document.getElementById(radioOption).style.display="none";
                } else {
                    document.getElementById(radioOption).style.display="block";	
                    document.getElementById(checkBoxOption).style.display="none";
                }
            }
        }
	
    }
</script>
{/literal}
<fieldset><legend>{ts}Price Field{/ts}</legend>

    <div class="form-item">
        <dl class="html-adjust">
        <dt>{$form.label.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_price_field' field='label' id=$id}{/if}</dt><dd>{$form.label.html}</dd>
        <dt>{$form.html_type.label}</dt><dd>{$form.html_type.html} {help id="id-negative"}</dd>
        {if $action neq 4 and $action neq 2}
            <dt>&nbsp;</dt><dd class="description">{ts}Select the html type used to offer options for this field{/ts}</dd>
        {/if}
        </dl>
        <div class="spacer"></div>
        <div id="price" {if $action eq 2 && $form.html_type.value.0 eq 'Text'} class="show-block" {else} class="hide-block" {/if}>
        <dl class="html-adjust">
        <dt>{$form.price.label}</dt><dd>{$form.price.html}</dd>
        {if $action neq 4}
        <dt>&nbsp;</dt><dd class="description">{ts}Unit price{/ts}
        {/if}
        </dl>
        </div>

    {if $action eq 1}
        {* Conditionally show table for setting up selection options - for field types = radio, checkbox or select *}
        <div id='showoption' class="hide-block">{ include file="CRM/Price/Form/OptionFields.tpl"}</div>
    {/if}
        <dl class="html-adjust">
	    <dt id="optionsPerLine" {if $action eq 2 && ($form.html_type.value.0 eq 'CheckBox' || $form.html_type.value.0 eq 'Radio')}class="show-block"{else} class="hide-block" {/if}>{$form.options_per_line.label}</dt>	
	    <dd id="optionsPerLineDef" {if $action eq 2 && ($form.html_type.value.0 eq 'CheckBox' || $form.html_type.value.0 eq 'Radio')}class="show-block"{else} class="hide-block"{/if}>{$form.options_per_line.html|crmReplace:class:two}</dd>

        <dt>{$form.is_display_amounts.label}</dt><dd>{$form.is_display_amounts.html}</dd>
        {if $action neq 4}
        <dt>&nbsp;</dt><dd class="description">{ts}Display amount next to each option? If no, then the amount should be in the option description.{/ts}</dd>
        {/if}

        <dt>{$form.weight.label}</dt><dd>{$form.weight.html|crmReplace:class:two}</dd>
        {if $action neq 4}
        <dt>&nbsp;</dt><dd class="description">{ts}Weight controls the order in which fields are displayed in a group. Enter a positive or negative integer - lower numbers are displayed ahead of higher numbers.{/ts}</dd>
        {/if}

        <dt>{$form.help_post.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_price_field' field='help_post' id=$id}{/if}</dt><dd>&nbsp;{$form.help_post.html|crmReplace:class:huge}&nbsp;</dd>
        {if $action neq 4}
        <dt>&nbsp;</dt><dd class="description">{ts}Explanatory text displayed to users for this field.{/ts}</dd>
        {/if}
<!--
        <dt>{$form.active_on.label}</dt><dd>{$form.active_on.html}</dd>
        {if $action neq 4}
        <dt>&nbsp;</dt><dd class="description">{ts}Date this field becomes effective (optional){/ts}</dd>
        {/if}

        <dt>{$form.expire_on.label}</dt><dd>{$form.expire_on.html}</dd>
        {if $action neq 4}
        <dt>&nbsp;</dt><dd class="description">{ts}Date this field expires (optional){/ts}</dd>
        {/if}
-->
        <dt>{$form.is_required.label}</dt><dd>&nbsp;{$form.is_required.html}</dd>
        </dl>
        <dl class="html-adjust">
        <dt>{$form.is_active.label}</dt><dd>&nbsp;{$form.is_active.html}</dd>
        </dl>    
   </div>
    
    <div id="crm-submit-buttons" class="form-item">
    <dl class="html-adjust">
    {if $action ne 4}
        <dt>&nbsp;</dt><dd>{$form.buttons.html}</dd>
    {else}
        <dt>&nbsp;</dt><dd>{$form.done.html}</dd>
    {/if} {* $action ne view *}
    </dl>    
    </div> 
</fieldset>

<script type="text/javascript">
    option_html_type(this.form);
</script>

{* Give link to view/edit choice options if in edit mode and html_type is one of the multiple choice types *}
{if $action eq 2 AND ($form.data_type.value.1.0 eq 'CheckBox' OR $form.data_type.value.1.0 eq 'Radio' OR $form.data_type.value.1.0 eq 'Select') }
    <div class="action-link">
        <a href="{crmURL p="civicrm/admin/event/field/option" q="reset=1&action=browse&fid=`$id`"}">&raquo; {ts}View / Edit Multiple Choice Options{/ts}</a>
    </div>
{/if}
