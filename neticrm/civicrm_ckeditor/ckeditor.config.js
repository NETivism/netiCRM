// $Id: ckeditor.config.js,v 1.2.2.7 2010/03/11 12:39:54 wwalc Exp $

/*
 WARNING: clear browser's cache after you modify this file.
 If you don't do this, you may notice that browser is ignoring all your changes.
 */
CKEDITOR.editorConfig = function(config) {
  // plug-in 
  config.extraPlugins = 'MediaEmbed';
  
  // disabled
  config.disableNativeSpellChecker = true;
  config.scayt_autoStartup = false;
  config.font_names = '微軟正黑體;新細明體;標楷體;Arial;Comic Sans MS;Courier New;Tahoma;Times New Roman;Verdana';
  config.fontSize_sizes = '10/10px;11/11px;13/13px;15/15px;16/16px;18/18px;20/20px;22/22px;24/24px;26/26px;28/28px;36/36px;48/48px;72/72px';
  config.format_tags = 'p;h1;h2;h3';
  // config.indentClasses = [ 'rteindent1', 'rteindent2', 'rteindent3', 'rteindent4' ];

  // [ Left, Center, Right, Justified ]
  // config.justifyClasses = [ 'rteleft', 'rtecenter', 'rteright', 'rtejustify' ];

  // The minimum editor width, in pixels, when resizing it with the resize handle.
  config.resize_minWidth = 450;

  // Protect PHP code tags (<?...?>) so CKEditor will not break them when
  // switching from Source to WYSIWYG.
  // Uncommenting this line doesn't mean the user will not be able to type PHP
  // code in the source. This kind of prevention must be done in the server
  // side
  // (as does Drupal), so just leave this line as is.
  config.protectedSource.push(/<\?[\s\S]*?\?>/g); // PHP Code

  // Define as many toolbars as you need, you can change toolbar names and remove or add buttons.
  // List of all buttons is here: http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.config.html#.toolbar_Full

  // This toolbar should work fine with "Filtered HTML" filter
  config.toolbar_CiviCRM = [
    ['Maximize'],
    ['Bold','Italic','Underline','Strike','RemoveFormat'],
    ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
    ['NumberedList','BulletedList','Outdent','Indent','Blockquote'],
    ['Link','Unlink','Image','MediaEmbed'],
    '/',
    ['Format','Font','FontSize'],
    ['PasteFromWord','PasteText'],
    ['TextColor','BGColor'],
    ['Table','HorizontalRule'],
    ['Source']
   ];

  /*
   * This toolbar is dedicated to users with "Full HTML" access some of commands
   * used here (like 'FontName') use inline styles, which unfortunately are
   * stripped by "Filtered HTML" filter
   */
  /*
  config.toolbar_CiviCRMFull = [
      ['Source'],
      ['Cut','Copy','Paste','PasteText','PasteFromWord','-','SpellChecker', 'Scayt'],
      ['Undo','Redo','Find','Replace','-','SelectAll','RemoveFormat'],
      ['Image','Flash','Table','HorizontalRule','Smiley','SpecialChar'],
      '/',
      ['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
      ['NumberedList','BulletedList','-','Outdent','Indent','Blockquote'],
      ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
      ['Link','Unlink','Anchor','LinkToNode', 'LinkToMenu'],
      '/',
      ['Format','Font','FontSize'],
      ['TextColor','BGColor'],
      ['Maximize', 'ShowBlocks'],
     ];
  */

  /*
   * Append here extra CSS rules that should be applied into the editing area.
   * Example: 
   * config.extraCss = 'body {color:#FF0000;}';
   */
  config.extraCss = '';

  /**
   * CKEditor's editing area body ID & class.
   * See http://drupal.ckeditor.com/tricks
   * This setting can be used if CKEditor does not work well with your theme by default.
   */
  config.bodyClass = '';
  config.bodyId = '';
  config.skin = 'BootstrapCK-Skin';
  config.resize_enabled = false;
};
