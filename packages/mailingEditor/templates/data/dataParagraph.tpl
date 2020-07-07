{* Template name: dataParagraph.tpl *}

<textarea class="nme-tpl" data-template-level="data" data-template-name="paragraph"></textarea>
<script>
{literal}
(function($) {
  let tplData = {
    "id": "",
    "type" : "paragraph",
    "section": "body",
    "data" : {
      "html": "<p>請輸入內文...</p>"
    },
    "styles": {
      "block": {
        "padding-top": "20px",
        "padding-right": "40px",
        "padding-bottom": "20px",
        "padding-left": "40px",
        "background-color": "#ffffff"
      },
      "elem": {
        "margin-top": "0",
        "margin-right": "0",
        "margin-bottom": "0",
        "margin-left": "0"
      }
    },
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

  $(".nme-tpl[data-template-level='data'][data-template-name='paragraph']").val(JSON.stringify(tplData));
}(jQuery));
{/literal}
</script>