{if $action eq 2}
<div class="crm-block crm-form-block crm-aicompletion-form-block">
  <table class="form-layout">
    <tr>
      <td class="label">{$form.is_template.label}</td>
      <td>
        {$form.is_template.html}
      </td>
    </tr>
    <tr id="template-title-wrapper">
      <td class="label">{$form.template_title.label}</td>
      <td>
        {$form.template_title.html}
      </td>
    </tr>
  </table>

  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>
{/if}

<fieldset>
  <legend>{ts}Details{/ts}</legend>
  <div class="crm-block crm-content-block crm-note-view-block">
    <table class="crm-info-panel">
      <tr>
        <td class="label">{ts}Created by{/ts}</td>
        <td>
          <a href="{crmURL a=true p='civicrm/contact/view' q="reset=1&cid=`$item.contact_id`"}">{$item.display_name}</a>
        </td>
      </tr>
      <tr>
        <td class="label">{ts}Created Date{/ts}</td>
        <td>{$item.created_date|crmDate}</td>
      </tr>
      <tr>
        <td class="label">{ts}Used for{/ts}</td>
        <td>{ts}{$item.component}{/ts}</td>
      </tr>
      <tr>
        <td class="label">{ts}Copywriting Role{/ts}</td>
        <td>{$item.ai_role}</td>
      </tr>
      <tr>
        <td class="label">{ts}Tone Style{/ts}</td>
        <td>{$item.tone_style}</td>
      </tr>
      <tr>
        <td class="label">{ts}Context{/ts}</td>
        <td>{$item.context|nl2br}</td>
      </tr>
      <tr>
        <td class="label">{ts}Result{/ts}</td>
        <td>
          <textarea class="huge" data="copy-text">{$item.output_text}</textarea>
          {include file="CRM/common/copyText.tpl"}
          <div class="description font-red">
            <i class="zmdi zmdi-info-outline"></i>{ts}Remember to verify AI-generated text before using it.{/ts}<br>
          </div>
        </td>
      </tr>
      {if $item.is_template}
      <tr>
        <td class="label">{ts}Template Title{/ts}</td>
        <td>{$item.template_title}</td>
      </tr>
      {/if}
    </table>
    <div class="crm-submit-buttons">
      <input type="button" name='cancel' value="{ts}Done{/ts}" onclick="location.href='{crmURL p='civicrm/admin/aicompletion' q='reset=1'}';"/>
    </div>
  </div>
</fieldset>