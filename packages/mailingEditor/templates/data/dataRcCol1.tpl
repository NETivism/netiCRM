{* Template name: dataRcCol1.tpl *}

<textarea class="nme-tpl" data-template-level="data" data-template-name="rc-col-1"></textarea>
<script>
{literal}
(function($) {
  let tplData = {
    "id": "",
    "type": "rc-col-1",
    "section": "body",
    "data": [{
      "blocks": {}
    }],
    "styles": {
      "block": {
        "padding-top": "0",
        "padding-right": "0",
        "padding-bottom": "0",
        "padding-left": "0",
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
    "weight" : 0
  };

  $(".nme-tpl[data-template-level='data'][data-template-name='rc-col-1']").val(JSON.stringify(tplData));
}(jQuery));
{/literal}
</script>