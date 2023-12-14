CKEDITOR.editorConfig = function(config) {
  config.removePlugins = 'easyimage,cloudservices,exportpdf,image';
  config.disableNativeSpellChecker = true;
  config.scayt_autoStartup = false;
  config.font_names = 'Arial;Comic Sans MS;Courier New;Tahoma;Times New Roman;Verdana';
  config.fontSize_sizes = '10/10px;11/11px;12/12px;13/13px;14/14px;15/15px;16/16px;18/18px;20/20px;22/22px;24/24px;26/26px;28/28px;36/36px;48/48px;72/72px';
  config.format_tags = 'p;h1;h2;h3';
  config.resize_minWidth = 450;
  config.resize_enabled = true;

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
    ['Link','Unlink'],['Image'],
    ['Undo','Redo']
  ];

  /**
   * CKEditor's editing area body ID & class.
   * See http://drupal.ckeditor.com/tricks
   * This setting can be used if CKEditor does not work well with your theme by default.
   */
  config.bodyClass = '';
  config.bodyId = '';
};

// allow font-icons
CKEDITOR.dtd.$removeEmpty['i'] = false;
CKEDITOR.dtd.$removeEmpty['span'] = false;

// disallow data image
CKEDITOR.on('instanceReady', function (event) {
  event.editor.on('paste', function(evt) {
    // remove data img
    let html = evt.data.dataValue;
    evt.data.dataValue = html.replace( /<img[^>]*src="data:image\/(bmp|dds|gif|jpg|jpeg|png|psd|pspimage|tga|thm|tif|tiff|yuv|ai|eps|ps|svg);base64,.*?"[^>]*>/gi, '');
  });
});