/**
Quill input

@class textarea
@extends abstractinput
@final
@example
<a href="#" id="comments" data-type="textarea" data-pk="1">awesome comment!</a>
<script>
$(function(){
    $('#comments').editable({
        url: '/post',
        title: 'Enter comments',
        rows: 10
    });
});
</script>
**/
(function ($) {
    "use strict";
 
    var stripHTML = function (html) {
       var tmp = document.createElement("DIV");
       tmp.innerHTML = html;
       return tmp.textContent || tmp.innerText || "";
    }

    var XQuill = function (options) {
        this.init('xquill', options, XQuill.defaults);
    };

    $.fn.editableutils.inherit(XQuill, $.fn.editabletypes.abstractinput);

    $.extend(XQuill.prototype, {
        status: 'view',
        editor: null,
        delta: null,
        xeditable: {
            html: '',
            value: ''
        },
        render: function () {
            var deferred = $.Deferred(), msieOld, quillID;
            console.log('===== render =====');
            // 將狀態設為「編輯」模式
            this.status = 'edit';

            // 為 quill 產生一個獨一無二的 ID
            quillID = 'quill-' + (new Date()).getTime();
            this.$input.attr('id', quillID);

            // 設定 class
            this.setClass();

            var html = this.xeditable.html,
                text = stripHTML(html);

            //console.log(text);
            //console.log(html);

            // 如果預設內容跟 placeholder 不一樣，則將預設內容複製一份到編輯器內
            if (text !== this.options.placeholder) {
                this.$input.html(html);
            }
            //this.setAttr('placeholder');
            //this.setAttr('rows');
                           
            //ctrl + enter
            /*
            this.$input.keydown(function (e) {
                if (e.ctrlKey && e.which === 13) {
                    $(this).closest('form').submit();
                }
            });
            this.$input.on("click", function() {
                console.log($(this));
                console.log($(this)[0]);
                console.log($(this.$input).get(0));
            });
            */

            var toolbarOptions = [
              ['bold', 'italic', 'underline', 'strike'],        // toggled buttons
              [{ 'color': [] }, { 'background': [] }],          // dropdown with defaults from theme
              [{ 'list': 'ordered'}, { 'list': 'bullet' }],
              ['link'],
              //['image']

              //['blockquote', 'code-block'],
              //[{ 'header': 1 }, { 'header': 2 }],               // custom button values
              //[{ 'script': 'sub'}, { 'script': 'super' }],      // superscript/subscript
              //[{ 'indent': '-1'}, { 'indent': '+1' }],          // outdent/indent
              //[{ 'direction': 'rtl' }],                         // text direction

              //[{ 'size': ['small', false, 'large', 'huge'] }],  // custom dropdown
              //[{ 'header': [1, 2, 3, 4, 5, 6, false] }],

              //[{ 'font': [] }],
              //[{ 'align': [] }],

              //['clean']                                         // remove formatting button
            ];
            var quillOptions = {
              //debug: 'info',
              modules: {
                toolbar: toolbarOptions
              },
              placeholder: this.options.placeholder ? this.options.placeholder : '請輸入內容...',
              //readOnly: true,
              theme: 'snow'
            };

            this.editor = new Quill('#' + quillID, quillOptions);
            this.editor.focus(); 
        },

        // 編輯完成時呼叫（第三順位）
        value2html: function(value, element) {
            console.log('===== value2html =====');
            //console.log(element);
            //console.log(value);
            //$(element).html(value);
            //console.log(this.editor);

            // 取得編輯器內容的 HTML
            var html = this.editor.root.innerHTML;

            // 將 HTML 儲存於 xeditable
            this.xeditable.html = html;

            // 將 HTML 輸出到 x-editable 觸控器中
            $(element).html(html);
        },

        // 初始化 x-editable 之後呼叫
        html2value: function(html) {
            console.log('===== html2value =====');
            //console.log(html);
            //console.log(this);
            this.xeditable.html = html;
            return html;
        },

        // 按下 x-editable / 編輯完成時呼叫（第二順位）
        value2input: function(value) {
            console.log('===== value2input =====');
            //console.log(value);
            //var delta = this.editor.getContents();
            //console.log(delta);
            //this.$input.data("wysihtml5").editor.setValue(value, true);
        },

        /**
        Returns value of input. Value can be object (e.g. datepicker)

        @method input2value() 
        **/
        // 編輯完成時呼叫（第一順位）
        input2value: function() { 
            console.log('===== input2value =====');
            // 將狀態設為「瀏覽」模式
            this.status = 'view';

            // 取得編輯器的內容並儲存於 delta
            this.delta = this.editor.getContents();
            console.log(this.delta);
            //return this.$input.val();
        },

       //using `white-space: pre-wrap` solves \n  <--> BR conversion very elegant!
       /* 
       value2html: function(value, element) {
            var html = '', lines;
            if(value) {
                lines = value.split("\n");
                for (var i = 0; i < lines.length; i++) {
                    lines[i] = $('<div>').text(lines[i]).html();
                }
                html = lines.join('<br>');
            }
            $(element).html(html);
        },
       
        html2value: function(html) {
            if(!html) {
                return '';
            }

            var regex = new RegExp(String.fromCharCode(10), 'g');
            var lines = html.split(/<br\s*\/?>/i);
            for (var i = 0; i < lines.length; i++) {
                var text = $('<div>').html(lines[i]).text();

                // Remove newline characters (\n) to avoid them being converted by value2html() method
                // thus adding extra <br> tags
                text = text.replace(regex, '');

                lines[i] = text;
            }
            return lines.join("\n");
        },
         */
        activate: function() {
            $.fn.editabletypes.text.prototype.activate.call(this);
        }
    });

    XQuill.defaults = $.extend({}, $.fn.editabletypes.abstractinput.defaults, {
        /**
        @property tpl
        @default <textarea></textarea>
        **/
        tpl:'<div></div>',
        /**
        @property inputclass
        @default input-large
        **/
        inputclass: 'quill-editor',
        /**
        Placeholder attribute of input. Shown when input is empty.

        @property placeholder
        @type string
        @default null
        **/
        placeholder: null,
        /**
        Number of rows in textarea

        @property rows
        @type integer
        @default 7
        **/        
        rows: 7        
    });

    $.fn.editabletypes.xquill = XQuill;

}(window.jQuery));