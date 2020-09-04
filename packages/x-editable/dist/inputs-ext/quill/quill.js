/**
Quill input

@class div
@extends abstractinput
@final
@example
<div class="paragraph" data-type="xquill" data-pk="1"><p>This is a paragraph block, please write a summary of the article here, which can also be used as an introduction or headline.</p></div>
<script>
$(function(){
    $(".paragraph").editable();
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
        xeditable: {
            delta: {},
            html: {}
        },
        htmlEscape: function(input) {
            return input
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        },
        render: function () {
            var deferred = $.Deferred(), msieOld, quillID;
            // Set status to "Edit" mode
            this.status = 'edit';

            // Generate a unique ID for quill
            quillID = 'quill-' + (new Date()).getTime();
            this.$input.attr('id', quillID);

            // Set class
            this.setClass();

            var blockID = this.options.scope.attributes['data-id']['nodeValue'],
                html = blockID ? this.xeditable.html[blockID] : '',
                text = stripHTML(html);

            // If value is different from placeholder, copy the value to the editor
            if (text !== this.options.placeholder) {
                this.$input.html(html);
            }

            // Change style class to inline style
            // refs https://quilljs.com/guides/how-to-customize-quill/#class-vs-inline
            // Import and set font size of quill
            var quillSize = Quill.import('attributors/style/size');
            quillSize.whitelist = ['13px', '20px', '28px'];
            Quill.register(quillSize, true);

            var quillAlign = Quill.import('attributors/style/align');
            Quill.register(quillAlign, true);

            var toolbarOptions = [
              ['bold', 'italic', 'underline', 'strike'],        // toggled buttons
              [{ 'color': [] }, { 'background': [] }],          // dropdown with defaults from theme
              [{ 'size': ['13px', false, '20px', '28px'] }],
              [{ 'align': [] }],
              [{ 'list': 'ordered'}, { 'list': 'bullet' }],
              ['link']
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

            var tokenToolbar = [];
            var tokenQuillOption = [];
            if (window.nmEditor.tokenTrigger) {
              Quill.register('modules/placeholder', PlaceholderModule.default(Quill))
              $(window.nmEditor.tokenTrigger).find("option").each(function(){
                var tokenName = $(this).attr("value").replace(/(\{|\})/gi, "");
                tokenToolbar.push(tokenName);
                tokenQuillOption.push({id:tokenName, label:tokenName});
              });
              toolbarOptions.push([{"placeholder":tokenToolbar}]);
            }

            var quillOptions = {
              //debug: 'info',
              modules: {
                toolbar: toolbarOptions
              },
              placeholder: this.options.placeholder ? this.options.placeholder : 'Please enter content...',
              //readOnly: true,
              theme: 'snow'
            };
            if (window.nmEditor.tokenTrigger) {
              quillOptions.modules.placeholder = {};
              quillOptions.modules.placeholder.placeholders = tokenQuillOption;
            }

            this.editor = new Quill('#' + quillID, quillOptions);
            this.editor.focus();
        },

        // Call when editing is complete (3）
        value2html: function(value, element) {
            // Get the HTML from the editor content
            var html = this.editor.root.innerHTML;

            // Store HTML in xeditable
            var blockID = this.options.scope.attributes['data-id']['nodeValue'];
            if (blockID) {
                this.xeditable.html[blockID] = html;
            }

            // Output HTML to x-editable
            $(element).html(html);
        },

        // Call after initializing x-editable
        html2value: function(html) {
            var blockID = this.options.scope.attributes['data-id']['nodeValue'];

            if (blockID) {
                this.xeditable.html[blockID] = html;
                return html;
            }
        },

        // Press x-editable / Call when editing is complete (2)
        value2input: function(value) {
        },

        /**
        Returns value of input. Value can be object (e.g. datepicker)

        @method input2value()
        **/
        // Called when editing is complete (1)
        input2value: function() {
            // Set status to "View" mode
            this.status = 'view';
            var blockID = this.options.scope.attributes['data-id']['nodeValue'];

            if (blockID) {
                // Get the content of the editor and store it in xeditable
                this.xeditable.delta[blockID] = this.editor.getContents();
                this.xeditable.html[blockID] = this.editor.root.innerHTML;

                // HTML should be escape, otherwise the 'params' of the event of the x-editable will not be obtained
                return this.htmlEscape(this.xeditable.html[blockID]);
            }
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