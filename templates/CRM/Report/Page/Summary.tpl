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
{* Display monthly and yearly contributions using Google charts (Bar and Pie) *} 
{if $hasChart}

  <div class="column-summary-count">
    <div>線上捐款{$contribute_total}元</div>
    <div>線上活動報名{$participant_total}次</div>
    <div>聯絡人{$contact_total}人</div>
    <div>寄出電子報{$mailing}封</div>
  </div>

  <div class="column-contact-source">
    <h3>聯絡人來源</h3>
    {* chartist *}
    {include file="CRM/common/chartist.tpl" chartist=$chartContact}
    <div id="{$chartContact.id}"></div>

  </div>

  <div class="column-contribution-instrument">
    <h3>捐款工具</h3>
  {* chartist *}
  {include file="CRM/common/chartist.tpl" chartist=$chartInsSum}
  <div id="chart-pie-with-legend-contribute-instrument"></div>

  </div>

  <div class="column-contribution-times">
    <h3>捐款回流率</h3>
  {include file="CRM/common/chartist.tpl" chartist=$chartContribTimes}
  <div id="chart-pie-with-legend-contribute-times"></div>
  </div>

  <div class="column-mailing-delivered">
    <h3>電子報發送</h3>
    {include file="CRM/common/chartist.tpl" chartist=$chartMailing}
  <div id="chart-bar-mailing"></div>
  </div>

  <div class="column-contribution-types">

  </div>


{/if}
