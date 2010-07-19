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
<div class="form-item">
<fieldset><legend>{ts}Debugging and Error Handling{/ts}</legend>
    
        <dl>
            <dt>{$form.debug.label}</dt><dd>{$form.debug.html}</dd>
            <dt>&nbsp;</dt><dd class="description">{ts}Set this value to <strong>Yes</strong> if you want to use one of CiviCRM's debugging tools. <strong>This feature should NOT be enabled for production sites</strong>{/ts} {help id='debug'}</dd>
            {if $form.userFrameworkLogging}
            <dt>{$form.userFrameworkLogging.label}</dt><dd>{$form.userFrameworkLogging.html}</dd>
            <dt>&nbsp;</dt><dd class="description">{ts}Set this value to <strong>Yes</strong> if you want CiviCRM error/debugging messages to also appear in Drupal error logs{/ts} {help id='userFrameworkLogging'}</dd>
            {/if}
            <dt>{$form.backtrace.label}</dt><dd>{$form.backtrace.html}</dd>
            <dt>&nbsp;</dt><dd class="description">{ts}Set this value to <strong>Yes</strong> if you want to display a backtrace listing when a fatal error is encountered. <strong>This feature should NOT be enabled for production sites</strong>{/ts}</dd>
            <dt>{$form.fatalErrorTemplate.label}</dt><dd>{$form.fatalErrorTemplate.html}</dd>
            <dt>&nbsp;</dt><dd class="description">{ts}Enter the path and filename for a custom Smarty template if you want to define your own screen for displaying fatal errors.{/ts}</dd>
            <dt>{$form.fatalErrorHandler.label}</dt><dd>{$form.fatalErrorHandler.html}</dd>
            <dt>&nbsp;</dt><dd class="description">{ts}Enter the path and class for a custom PHP error-handling function if you want to override built-in CiviCRM error handling for your site.{/ts}</dd>
            <dt></dt><dd>{$form.buttons.html}</dd>
         </dl>
   
<div class="spacer"></div>
</fieldset>
</div>
