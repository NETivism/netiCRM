<div class="crm-block crm-form-block crm-batch-search-form-block">
  <table class="form-layout-compressed">
    <tr>
      <td>
        {$form.label.label}<br />
        {$form.label.html}
      </td>
      <td>
        {$form.type_id.label}<br />
        {$form.type_id.html}
      </td>
      <td>
        {$form.status_id.label}<br />
        {$form.status_id.html}
      </td>
      <td>
        {include file="CRM/common/formButtons.tpl"}
      </td>
    </tr>
  </table>
</div>

{include file="CRM/common/chosen.tpl" selector="select#type_id, select#status_id"}
