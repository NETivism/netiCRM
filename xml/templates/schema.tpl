-- +--------------------------------------------------------------------+
-- | CiviCRM version 3.3                                                |
-- +--------------------------------------------------------------------+
-- | Copyright CiviCRM LLC (c) 2004-2010                                |
-- +--------------------------------------------------------------------+
-- | This file is a part of CiviCRM.                                    |
-- |                                                                    |
-- | CiviCRM is free software; you can copy, modify, and distribute it  |
-- | under the terms of the GNU Affero General Public License           |
-- | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
-- |                                                                    |
-- | CiviCRM is distributed in the hope that it will be useful, but     |
-- | WITHOUT ANY WARRANTY; without even the implied warranty of         |
-- | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
-- | See the GNU Affero General Public License for more details.        |
-- |                                                                    |
-- | You should have received a copy of the GNU Affero General Public   |
-- | License and the CiviCRM Licensing Exception along                  |
-- | with this program; if not, contact CiviCRM LLC                     |
-- | at info[AT]civicrm[DOT]org. If you have questions about the        |
-- | GNU Affero General Public License or the licensing of CiviCRM,     |
-- | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
-- +--------------------------------------------------------------------+
{$database.comments}

{include file="drop.tpl"}

-- /*******************************************************
-- *
-- * Create new tables
-- *
-- *******************************************************/

{foreach from=$tables item=table}
-- /*******************************************************
-- *
-- * {$table.name}
{if $table.comment}
-- *
-- * {$table.comment}
{/if}
-- *
-- *******************************************************/
CREATE TABLE {$table.name} (
{assign var='first' value=true}

{foreach from=$table.fields item=field}
{if ! $first},{/if}
{assign var='first' value=false}

     `{$field.name}` {$field.sqlType} {if $field.required}NOT NULL{/if} {if $field.autoincrement}AUTO_INCREMENT{/if} {if $field.default|count_characters}DEFAULT {$field.default}{/if} {if $field.comment}COMMENT '{$field.comment}'{/if}
{/foreach} {* table.fields *}

{if $table.primaryKey}
{if ! $first},{/if}
{assign var='first' value=false}

    PRIMARY KEY ( {$table.primaryKey.name} )
{/if} {* table.primaryKey *}

{if $table.index}
  {foreach from=$table.index item=index}
  {if ! $first},{/if}
  {assign var='first' value=false}
  {if $index.unique} UNIQUE{/if} INDEX {$index.name}(
  {assign var='firstIndexField' value=true}
  {foreach from=$index.field item=fieldName}
    {if $firstIndexField}{assign var='firstIndexField' value=false}{else}, {/if}{$fieldName}
  {/foreach}
)
{/foreach} {* table.index *}
{/if} {* table.index *}

{if $table.foreignKey}
{foreach from=$table.foreignKey item=foreign}
{if ! $first},{/if}
{assign var='first' value=false}
     {if $mysql eq 'simple'} INDEX FKEY_{$foreign.name} ( {$foreign.name} ) , {/if} 
     CONSTRAINT {$foreign.uniqName} FOREIGN KEY ({$foreign.name}) REFERENCES {$foreign.table}({$foreign.key}) {if $foreign.onDelete}ON DELETE {$foreign.onDelete}{/if}
{/foreach} {* table.foreignKey *}
{/if} {* table.foreignKey *}

) {if $table.engine} ENGINE={$table.engine} {elseif $mysql eq 'modern' } {$table.attributes_modern} {else} {$table.attributes_simple} {/if} ;

{/foreach} {* tables *}
