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
{if $action eq 8}
  {include file="CRM/Contribute/Form/PCP/Delete.tpl"}
{else}
    <div id="pcp" class="crm-block crm-form-block crm-pcp-search-form-block">
      <h3>{ts}Search Personal Campaign Pages{/ts}</h3>
      <table class="form-layout-compressed">
        <tr>
          <td>{$form.title.label}<br />{$form.title.html}</td>
          <td>{$form.contact_id.label}<br />{$form.contact_id.html}</td>
          <td>{$form.contribution_page_id.label}<br />{$form.contribution_page_id.html}</td>
          <td>{$form.status_id.label}<br />{$form.status_id.html}</td>
          <td><div class="crm-submit-buttons">{$form.buttons.html}</div></td>
        </tr>
      </table>
      {include file="CRM/common/chosen.tpl" selector="select#contact_id,select#contribution_page_id"}
    </div>
{/if}
