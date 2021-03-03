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
      "html": "<div>這裡可以撰寫文章的摘要簡介，也可以當作引言或頭條來使用，建議文字量不要太多，簡而有力更吸睛！直接在文字上點擊即可進入編輯模式，你可以使用編輯器為文字加上超連結，或是快速讓文字變成粗體以及改變顏色，當然也可以加入項目清單，立刻來編輯看看吧！</div>"
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
        "margin-left": "0",
        "text-align": "left",
        "font-size": "16px",
        "color": "#000000"
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
}(cj));
{/literal}
</script>