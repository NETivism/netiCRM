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
      "ops": {},
      "html": "<p>請輸入內文...</p>"
    },
    "styles": {
      "block": {
        "padding-top": "10px",
        "padding-right": "10px",
        "padding-bottom": "10px",
        "padding-left": "10px",
        "background-color": "#ffffff"
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
    "weight" : 0
  };

  $(".nme-tpl[data-template-level='data'][data-template-name='paragraph']").val(JSON.stringify(tplData));
}(jQuery));
{/literal}
</script>