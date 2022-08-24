CKEDITOR.editorConfig = function(config) {
  config.disableNativeSpellChecker = true;
  config.scayt_autoStartup = false;
  config.font_names = 'Arial;Comic Sans MS;Courier New;Tahoma;Times New Roman;Verdana';
  config.fontSize_sizes = '10/10px;11/11px;12/12px;13/13px;14/14px;15/15px;16/16px;18/18px;20/20px;22/22px;24/24px;26/26px;28/28px;36/36px;48/48px;72/72px';
  config.format_tags = 'p;h1;h2;h3';
  // config.indentClasses = [ 'rteindent1', 'rteindent2', 'rteindent3', 'rteindent4' ];

  // [ Left, Center, Right, Justified ]
  // config.justifyClasses = [ 'rteleft', 'rtecenter', 'rteright', 'rtejustify' ];

  // The minimum editor width, in pixels, when resizing it with the resize handle.
  config.resize_minWidth = 450;

  // This toolbar should work fine with "Filtered HTML" filter
  config.toolbar_CiviCRM = [
    ['Maximize'],['RemoveFormat'],
    ['Bold','Italic','Underline','Strike'],
    ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
    ['NumberedList','BulletedList','Outdent','Indent','Blockquote'],
    ['Link','Unlink'],['Image','MediaEmbed'],
    '/',
    ['Format','Font','FontSize'],
    ['PasteFromWord','PasteText'],
    ['Undo','Redo'],
    ['TextColor','BGColor'],
    ['Table','HorizontalRule'],
    ['Source']
  ];
  config.toolbar_CiviCRMBasic = [
    ['Format'],
    ['RemoveFormat'],
    ['Bold','Italic','Underline','Strike'],
    ['NumberedList','BulletedList'],
    ['Link','Unlink'],['Image']
    ['Undo','Redo']
  ];

  /**
   * CKEditor's editing area body ID & class.
   * See http://drupal.ckeditor.com/tricks
   * This setting can be used if CKEditor does not work well with your theme by default.
   */
  config.bodyClass = '';
  config.bodyId = '';
  config.resize_enabled = true;
  config.extraAllowedContent = 'p()[]{*};div()[]{*};li()[]{*};ul()[]{*}';
  config.disallowedContent = {
    img: {
      match: function( element ) {
        if ( element.name === 'img' && element.attributes.src && String( element.attributes.src ).match( /^data\:/ ) ) {
          return true;
        }
        return false;
      }
    }
  };
};
CKEDITOR.dtd.$removeEmpty['i'] = false;
CKEDITOR.dtd.$removeEmpty['span'] = false;
