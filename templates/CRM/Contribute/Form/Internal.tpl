  <div id="pcp" class="crm-block crm-form-block crm-contribution-internal-form-block">
    <h3>{ts}Search{/ts}</h3>
    <table class="form-layout-compressed">
      <tr>
        <td>{$form.contact_id.label}</td><td>{$form.contact_id.html} {if $display_name}({$display_name}){/if}</td>
      </tr>
      <tr>
        <td>{$form.contribution_page_id.label}</td><td>{$form.contribution_page_id.html}</td>
      </tr>
        <td><div class="crm-submit-buttons">{$form.buttons.html}</div></td>
    </table>
  </div>