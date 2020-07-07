{* Template name: dataButton.tpl *}

<textarea class="nme-tpl" data-template-level="data" data-template-name="button"></textarea>
<script>
{literal}
(function($) {
  let tplData = {
    "id": "",
    "type" : "button",
    "section": "body",
    "data" : "閱讀更多",
    "styles": {
      "block": {
        "padding-top": "20px",
        "padding-right": "40px",
        "padding-bottom": "20px",
        "padding-left": "40px",
        "text-align": "center",
        "background-color": "#ffffff"
      },
      "elemContainer": {
        "margin-left": "auto",
        "margin-right": "auto",
        "border-radius": "3px",
        "background-color": "#222222"
      },
      "elemContainerInner": {
        "padding-top": "10px",
        "padding-right": "10px",
        "padding-bottom": "10px",
        "padding-left": "10px",
        "width": "auto",
        "font-size": "18px"
      },
      "elem": {
        "margin-top": "0",
        "margin-right": "0",
        "margin-bottom": "0",
        "margin-left": "0",
        "text-align": "center",
        "letter-spacing": "0",
        "font-weight": "bold",
        "color": "#ffffff"
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

  $(".nme-tpl[data-template-level='data'][data-template-name='button']").val(JSON.stringify(tplData));
}(jQuery));
{/literal}
</script>