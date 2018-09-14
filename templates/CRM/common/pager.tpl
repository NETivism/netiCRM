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
{if $pager and $pager->_response}
    {if $pager->_response.numPages > 1}
        <div class="crm-pager">
          <span class="crm-pager-nav">
          {$pager->_response.first}&nbsp;
          {$pager->_response.back}&nbsp;
          {$pager->_response.next}&nbsp;
          {$pager->_response.last}&nbsp;          
          {$pager->_response.status}          
          </span>
          {if ! isset($noForm) || ! $noForm}
            <span>
            {if $location eq 'top'}
              {$pager->_response.titleTop}&nbsp;<input class="form-submit" name="{$pager->_response.buttonTop}" value="{ts}Go{/ts}" type="submit"/>
            {else}
              {$pager->_response.titleBottom}&nbsp;<input class="form-submit" name="{$pager->_response.buttonBottom}" value="{ts}Go{/ts}" type="submit"/>
            {/if}
            </span>
          {/if}
        </div>
    {/if}
    
    {* Controller for 'Rows Per Page' *}
    {if $location eq 'bottom' and $pager->_totalItems > 25}
     <div class="crm-pager-per-num">
           <label>{ts}Rows per page:{/ts}</label> 
           {$pager->_response.twentyfive} | {$pager->_response.fifty} | {$pager->_response.onehundred}
     </div>
    {/if}

{/if}
