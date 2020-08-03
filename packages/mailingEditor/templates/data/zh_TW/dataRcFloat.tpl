{* Template name: dataRcFloat.tpl *}

<textarea class="nme-tpl" data-template-level="data" data-template-name="rc-float"></textarea>
<script>
{literal}
(function($) {
  let tplData = {
    "id": "",
    "type": "rc-float",
    "section": "body",
    "data": [{
      "blocks": {}
    }],
    "styles": {
      "block": {
        "padding-top": "10px",
        "padding-right": "10px",
        "padding-bottom": "10px",
        "padding-left": "10px",
        "text-align": "center",
        "background-color": "transparent"
      },
      "elem": {
        "margin-top": "0",
        "margin-right": "0",
        "margin-bottom": "0",
        "margin-left": "0",
        "background-color": "transparent"
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
    "isRichContent": true,
    "weight" : 0
  };

  $(".nme-tpl[data-template-level='data'][data-template-name='rc-float']").val(JSON.stringify(tplData));
}(jQuery));
{/literal}
</script>