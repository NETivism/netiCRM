<tr>
  <td>
  {$form.mailing_id.label}
    <br />
  {$form.mailing_id.html}
  </td>
<td>
  <div>
  {$form.mailing_job_status.label}: {$form.mailing_job_status.html}
  </div>
  <div>
  {$form.mailing_bounce_types.label}: {$form.mailing_bounce_types.html}
  </div>
</td>
</tr>
<tr><td><label>{ts}Date of delivery{/ts}</label></td></tr>
<tr>
  <td>
  {$form.mailing_date_low.label}
  {include file="CRM/common/jcalendar.tpl" elementName=mailing_date_low} 
  </td>
  <td>
  {$form.mailing_date_high.label}
  {include file="CRM/common/jcalendar.tpl" elementName=mailing_date_high} 
  </td>
</tr>
<tr>
  <td>
  {$form.mailing_delivery_status.label}<br />
  {$form.mailing_delivery_status.html}
  </td>
  <td>
  {$form.mailing_open_status.label}<br />
  {$form.mailing_open_status.html}
  </td>
</tr>
<tr>
  <td>
  {$form.mailing_click_status.label}<br />
  {$form.mailing_click_status.html}
  </td>
  <td>
  {$form.mailing_reply_status.label}<br />
  {$form.mailing_reply_status.html}
  </td>
</tr>
<tr>
  <td>
    {$form.mailing_unsubscribe.html} 
    {$form.mailing_unsubscribe.label}
  </td>
  <td>
    {$form.mailing_optout.html} 
    {$form.mailing_optout.label}
  </td>
</tr>
<tr>
  <td>{* campaign in Advance search *}
      {include file="CRM/Campaign/Form/addCampaignToComponent.tpl" campaignContext="componentSearch"
       campaignTrClass='crmCampaign' campaignTdClass='crmCampaignContainer'}
  </td>
</tr>
{include file="CRM/common/chosen.tpl" selector="select#mailing_id,select#mailing_bounce_types"}
