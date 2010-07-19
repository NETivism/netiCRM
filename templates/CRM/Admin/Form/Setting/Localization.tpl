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

    <fieldset><legend>{ts}Language and Currency{/ts}</legend>    
        <dl>
            <dt>{$form.lcMessages.label}</dt><dd>{$form.lcMessages.html}</dd>
            <dt>&nbsp;</dt><dd class="description">{ts}Default language used for this installation.{/ts}</dd>
            {if $form.languageLimit}
              <dt>{$form.languageLimit.label}</dt><dd>{$form.languageLimit.html}</dd>
              <dt>&nbsp;</dt><dd class="description">{ts}Languages available to users of this installation.{/ts}</dd>
              <dt>{$form.addLanguage.label}</dt><dd>{$form.addLanguage.html}</dd>
              <dt>&nbsp;</dt><dd class="description">{ts}Add a new language to this installation.{/ts}</dd>
            {/if}
            <dt>{$form.inheritLocale.label}</dt><dd>{$form.inheritLocale.html}</dd>
            <dt>&nbsp;</dt><dd class="description">{ts}If checked, CiviCRM will follow CMS language changes.{/ts}</dd>
            <dt>{$form.defaultCurrency.label}</dt><dd>{$form.defaultCurrency.html}</dd>
            <dt>&nbsp;</dt><dd class="description">{ts}Default currency assigned to contributions and other monetary transactions.{/ts}</dd>
            <dt>{$form.lcMonetary.label}</dt><dd>{$form.lcMonetary.html}</dd>
            <dt>&nbsp;</dt><dd class="description">{ts}Locale for monetary display (affects formatting specifiers below).{/ts}</dd>
            <dt>{$form.moneyformat.label}</dt><dd>{$form.moneyformat.html}</dd>
            <dt>&nbsp;</dt><dd class="description">{ts}Format for displaying monetary amounts.{/ts}</dd>
            <dt>{$form.moneyvalueformat.label}</dt><dd>{$form.moneyvalueformat.html}</dd>
            <dt>&nbsp;</dt><dd class="description">{ts}Format for displaying monetary values.{/ts}</dd>
            <dt>{$form.customTranslateFunction.label}</dt><dd>{$form.customTranslateFunction.html}</dd>
            <dt>&nbsp;</dt><dd class="description">{ts}Function name to use for translation inplace of the default CiviCRM translate function. {/ts}</dd>
            <dt>{$form.legacyEncoding.label}</dt><dd>{$form.legacyEncoding.html}</dd>
            <dt>&nbsp;</dt><dd class="description">{ts}If import files are NOT encoded as UTF-8, specify an alternate character encoding for these files. The default of <strong>Windows-1252</strong> will work for Excel-created .CSV files on many computers.{/ts}</dd>
            <dt>{$form.fieldSeparator.label}</dt><dd>{$form.fieldSeparator.html}</dd>
            <dt>&nbsp;</dt><dd class="description">{ts}Global CSV separator character. Modify this setting to enable import and export of different kinds of CSV files (for example: ',' ';' ':' '|' ).{/ts}</dd>
        </dl>
    </fieldset>
    <fieldset><legend>{ts}Contact Address Fields - Selection Values{/ts}</legend>
        <dl>
            <dt>{$form.defaultContactCountry.label}</dt><dd>{$form.defaultContactCountry.html}</dd>
            <dt>&nbsp;</dt><dd class="description">{ts}This value is selected by default when adding a new contact address.{/ts}</dd>
            <dt>{$form.countryLimit.label}</dt><dd>{$form.countryLimit.html}</dd>
            <dt class="">&nbsp;</dt><dd class="description">{ts}Which countries are available in the Country selection field when adding or editing contact addresses. Profile and Custom 'Country' fields also use this setting. To include ALL countries, leave the right-hand box empty.{/ts}</dd>
            <dt>{$form.provinceLimit.label}</dt><dd>{$form.provinceLimit.html}</dd>
            <dt class="">&nbsp;</dt><dd class="description">{ts}State/province listings are populated dynamically based on the selected Country for all standard contact address editing forms, as well as for <strong>Profile forms which include both a Country and a State/Province field</strong>.  This setting controls which countries' states and/or provinces are available in the State / Province selection field <strong>for Custom Fields</strong> or for Profile forms which do NOT include a Country field.{/ts}</dd>
        </dl>
    </fieldset>
    <fieldset><legend>{ts}Multiple Languages Support{/ts}</legend>    
      <dl>
        {if $form.languageLimit}
          <dt>&nbsp;</dt><dd class="description">{ts 1="http://documentation.civicrm.org"}This is a multilingual installation. It contains certain schema differences compared to regular installations of CiviCRM. Please <a href="%1">refer to the documentation</a> for details.{/ts}</dd>
          <dt>{$form.makeSinglelingual.label}</dt><dd>{$form.makeSinglelingual.html}</dd>
          <dt>&nbsp;</dt><dd class="description">{ts}Check this box and click 'Save' to switch this installation from multi- to single-language.{/ts}</dd>
          <dd class="description"><span style="color:red">{$warning}</span>
        {elseif $form.makeMultilingual}
          <dt>{$form.makeMultilingual.label}</dt><dd>{$form.makeMultilingual.html}</dd>
          <dt>&nbsp;</dt><dd class="description">{ts}Check this box and click 'Save' to switch this installation from single- to multi-language, then add further languages.{/ts}</dd>
          <dd class="description"><span style="color:red">{$warning}</span>
        {else}
          <dd class="description">{ts}In order to use this functionality, the installation's database user must have privileges to create triggers (in MySQL 5.0 this means the SUPER privilege). This install does not seem to have the required privilege enabled.{/ts}</dd>
          <dd class="description"><span style="color:red">{$warning}</span>
        {/if}
      </dl>
    </fieldset>
    <dl>
        <dt></dt><dd>{$form.buttons.html}</dd>
    </dl>
<div class="spacer"></div>
</div>
