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
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>                         
  <h3>{ts}Settings{/ts} - {ts}Fields{/ts}</h3>
  <div class="crm-section">
            <div class="label">{$form.recurringSyncExclude.label}</div>
            <div class="content">
              {$form.recurringSyncExclude.html} 
              <div class="description">{ts}When recurring contribution being triggered, new contribution will copy custom fields value from first one. Fields you selected in this option will exclude to synchronize value from old contribution.{/ts}</div>
            </div>
  {include file="CRM/common/chosen.tpl" selector='select[name^=recurringSyncExclude]'}
  </div>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>     
