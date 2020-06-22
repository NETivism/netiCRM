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
      "url": "https://unsplash.it/1360/600?image=972",
      "width" : "600",
      "height" : "265",
      "fileName": "example_image"
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
        "margin-left": "0"
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
    "weight" : 0
  };

  $(".nme-tpl[data-template-level='data'][data-template-name='image']").val(JSON.stringify(tplData));
}(jQuery));
{/literal}
</script>