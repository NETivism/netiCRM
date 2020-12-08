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
{if $suppress}
<div class="messages">
  {$suppress}
</div>
{else}
<div class="messages crm-error error-ci">
  <strong>{ts}We are very sorry that there an error occurred. Please contact system administrator for further support. Thanks for your help in improving this open source project.{/ts}</strong><br>{ts}Error Code:{/ts}
  {if $message}{$message}{/if}
  {if $error.message && $message != $error.message}
    {$error.message}
  {/if}
</div>
{if $debug}<div><hr>{$debug}</div>{/if}
{if $backtrace}<div><hr>{$backtrace}</div>{/if}
{/if}