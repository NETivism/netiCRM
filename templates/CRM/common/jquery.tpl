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
{*
 * If you MODIFY this file, please make sure you also modify jquery.files.tpl.
 * Cannot get rid of this since we use it for joomla, standalone, print
 * html profile etc
 *}
<script type="text/javascript" src="{$config->resourceBase}packages/jquery/jquery.js"></script>
<script type="text/javascript" src="{$config->resourceBase}packages/jquery/jquery-ui/js/jquery-ui-1.9.1.custom.min.js"></script>
<script type="text/javascript" src="{$config->resourceBase}packages/jquery/jquery-ui/development-bundle/ui/i18n/jquery.ui.datepicker-{$config->lcMessages|replace:'_':'-'}.js"></script>
<style type="text/css">@import url("{$config->resourceBase}packages/jquery/jquery-ui/css/custom-theme/jquery-ui-1.9.1.custom.css");</style>

<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery.autocomplete.js"></script>
<style type="text/css">@import url("{$config->resourceBase}packages/jquery/css/jquery.autocomplete.css");</style>

<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jsTree.v.1.0rc2/jquery.jstree.min.js"></script>
<style type="text/css">@import url("{$config->resourceBase}packages/jquery/plugins/jsTree.v.1.0rc2/themes/default/style.css");</style>

<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery.menu.pack.js"></script>
<style type="text/css">@import url("{$config->resourceBase}packages/jquery/css/menu.css");</style>

<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery.chainedSelects.js"></script>

<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery.contextMenu.js"></script>
<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery.tableHeader.js"></script>

{*allow select/unselect checkboxes functionality only for search*}
<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/checkboxselect.js"></script>

{if $defaultWysiwygEditor eq 2}
    <script type="text/javascript" src="{$config->resourceBase}packages/ckeditor/ckeditor.js"></script>
{/if}

<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery.form.js"></script>

<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery.tokeninput.js"></script>
<style type="text/css">@import url("{$config->resourceBase}packages/jquery/css/token-input-facebook.css");</style>

<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery.timeentry.pack.js"></script>

<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery.toolTip.js"></script>

<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/DataTables/datatables.min.js"></script>
<style type="text/css">@import url("{$config->resourceBase}packages/jquery/plugins/DataTables/datatables.min.css");</style>

<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery.FormNavigate.js"></script>

<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery.chosen.js"></script>
<style type="text/css">@import url("{$config->resourceBase}packages/jquery/css/jquery.chosen.css");</style>

<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery.modal.min.js"></script>

<script type="text/javascript" src="{$config->resourceBase}js/rest.js"></script>

<script type="text/javascript" src="{$config->resourceBase}js/jquery/jquery.crmaccordions.js"></script>
<script type="text/javascript" src="{$config->resourceBase}js/jquery/jquery.crmasmselect.js"></script>
<script type="text/javascript" src="{$config->resourceBase}js/jquery/jquery.disablesubmit.js"></script>

<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery.dashboard.js"></script>
<style type="text/css">@import url("{$config->resourceBase}packages/jquery/css/dashboard.css");</style>

{* CRM-6819: localize datepicker *}
{if $l10nURL}
  <script type="text/javascript" src="{$l10nURL}"></script>
{/if}

<script type="text/javascript">var cj = jQuery.noConflict(); $ = cj;</script>

<style type="text/css">@import url("{$config->resourceBase}packages/fontawesome/css/font-awesome.min.css");</style>

<style type="text/css">@import url("{$config->resourceBase}packages/material-design-iconic-font/css/material-design-iconic-font.min.css");</style>
