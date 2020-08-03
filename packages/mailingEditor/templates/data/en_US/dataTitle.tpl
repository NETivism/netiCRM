{* Template name: dataTitle.tpl *}

<textarea class="nme-tpl" data-template-level="data" data-template-name="title"></textarea>
<script>
{literal}
(function($) {
  let tplData = {
    "id": "",
    "type" : "title",
    "section": "body",
    "data" : "You can write a title here",
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
        "margin-left": "0",
        "text-align": "center",
        "text-decoration": "none",
        "letter-spacing": "1px",
        "line-height": "1.4",
        "font-weight": "bold",
        "font-size": "28px",
        "color": "#000000"
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

  $(".nme-tpl[data-template-level='data'][data-template-name='title']").val(JSON.stringify(tplData));
}(jQuery));
{/literal}
</script>