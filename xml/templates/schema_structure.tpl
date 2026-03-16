<?php

/*
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
*/

/**
 * Defines the internationalization schema structure for translatable database columns
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */
class CRM_Core_I18n_SchemaStructure {ldelim}
  /**
   * Returns the list of internationalized columns.
   *
   * @return array
   */
  public static function &columns() {ldelim}
    static $result = NULL;
    if (!$result) {ldelim}
      $result = [
{foreach from=$columns key=table item=types}
        '{$table}' => [
{foreach from=$types key=column item=type}
          '{$column}' => "{$type}",
{/foreach}
        ],
{/foreach}
      ];
    {rdelim}
    return $result;
  {rdelim}
  /**
   * Returns the list of internationalized indices.
   *
   * @return array
   */
  public static function &indices() {ldelim}
    static $result = NULL;
    if (!$result) {ldelim}
      $result = [
{foreach from=$indices key=table item=tableIndices}
        '{$table}' => [
{foreach from=$tableIndices key=name item=info}
          '{$name}' => [
            'name' => '{$info.name}',
            'field' => [
{foreach from=$info.field item=field}
              '{$field}',
{/foreach}
            ],
{if $info.unique}            'unique' => 1,{"\n"}{/if}
          ],
{/foreach}
        ],
{/foreach}
      ];
    {rdelim}
    return $result;
  {rdelim}
  /**
   * Returns the list of internationalized tables.
   *
   * @return array
   */
  public static function &tables() {ldelim}
    static $result = NULL;
    if (!$result) {ldelim}
      $result = array_keys(self::columns());
    {rdelim}
    return $result;
  {rdelim}
{rdelim}

