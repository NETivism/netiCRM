{* Template name: dataImage.tpl *}

<textarea class="nme-tpl" data-template-level="data" data-template-name="image"></textarea>
<script>
{literal}
(function($) {
  let tplData = {
    "id": "",
    "type" : "image",
    "section": "body",
    "data" :  {
      "url": "",
      "width" : "680",
      "height" : "383",
      "fileName": "default_image",
      "isDefault": true
    },
    "styles": {
      "block": {
        "padding-top": "0",
        "padding-right": "0",
        "padding-bottom": "0",
        "padding-left": "0",
        "background-color": "#ffffff"
      },
      "elemContainerInner": {
        "text-align": "center"
      },
      "elem": {
        "margin-top": "0",
        "margin-right": "0",
        "margin-bottom": "0",
        "margin-left": "0",
        "max-width": "100%"
      }
    },
    "link": "",
    "control": {
      "sortable": true,
      "delete": true,
      "clone": true
    },
    "override": {
      "block": false,
      "elem": false
    },
    "isRichContent": false,
    "weight" : 0
  };

  $(".nme-tpl[data-template-level='data'][data-template-name='image']").val(JSON.stringify(tplData));
}(jQuery));
{/literal}
</script>