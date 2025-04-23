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

    const TEXT_EMPTY_CLASS = 'is-text-empty';

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
            var entityMap = {
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&apos;"
              };

            return String(input).replace(/[&<>"']/g, function (s) {
                return entityMap[s];
            });
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
                blockClass = this.options.scope.attributes['class']['nodeValue'],
                html = blockID ? this.xeditable.html[blockID] : '',
                text = stripHTML(html);

            // If value is different from placeholder, copy the value to the editor
            if (text !== this.options.placeholder) {
                this.$input.html(html);
            }

            // refs #29481. Replace <p> with <div>
            var quillBlock = Quill.import('blots/block');
            class DivBlock extends quillBlock {}
            DivBlock.tagName = 'DIV';
            Quill.register('blots/block', DivBlock, true);

            // Change style class to inline style
            // refs https://quilljs.com/guides/how-to-customize-quill/#class-vs-inline
            // Import and set font size of quill
            var quillSize = Quill.import('attributors/style/size');
            quillSize.whitelist = ['13px', '20px', '28px'];
            Quill.register(quillSize, true);

            var quillAlign = Quill.import('attributors/style/align');
            Quill.register(quillAlign, true);

            // Added inline style whitelist to Quill
            var quillParchment = Quill.import('parchment');
            var quillParchmentAttrs = {};

            // Inline Style Whitelist: text-decoration
            quillParchmentAttrs.textDecoration = {};
            quillParchmentAttrs.textDecoration.attrConfig = {
                scope: quillParchment.Scope.INLINE,
                whitelist: ['none', 'underline', 'overline', 'line-through']
            };
            quillParchmentAttrs.textDecoration.attrStyle = new quillParchment.Attributor.Style('text-decoration', 'text-decoration', quillParchmentAttrs.textDecoration.attrConfig);

            // Register attributor
            for (var attr in quillParchmentAttrs) {
                if (quillParchmentAttrs[attr].attrStyle) {
                    quillParchment.register(quillParchmentAttrs[attr].attrStyle);
                }
            }

            var toolbarColor = ['#000000', '#e60000', '#ff9900', '#ffff00', '#008a00', '#0066cc', '#9933ff', '#ffffff', '#facccc', '#ffebcc', '#ffffcc', '#cce8cc', '#cce0f5', '#ebd6ff', '#bbbbbb', '#f06666', '#ffc266', '#ffff66', '#66b966', '#66a3e0', '#c285ff', '#888888', '#a10000', '#b26b00', '#b2b200', '#006100', '#0047b2', '#6b24b2', '#444444', '#5c0000', '#663d00', '#666600', '#003700', '#002966', '#3d1466'];
            var toolbarOptions = [
              ['bold', 'italic', 'underline', 'strike'],        // toggled buttons
              [{ 'color': toolbarColor }, { 'background': [] }],          // dropdown with defaults from theme
              [{ 'size': ['13px', false, '20px', '28px'] }],
              [{ 'align': [] }],
              [{ 'list': 'ordered'}, { 'list': 'bullet' }],
              ['link'],
              ['emoji']
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

            if (blockClass.indexOf("nmee-title") != -1) {
                toolbarOptions = [
                    [{ 'color': toolbarColor }, { 'background': [] }],
                    ['link'],
                    ['emoji']
                ];
            }

            var tokenToolbar = [];
            var tokenQuillOption = [];
            if (window.nmEditor.tokenTrigger) {
              Quill.register('modules/placeholder', PlaceholderModule.default(Quill))
              $(window.nmEditor.tokenTrigger).find("option").each(function(){
                var tokenName = $(this).attr("value");
                tokenToolbar.push(tokenName);
                tokenQuillOption.push({id:tokenName, label:tokenName});
              });
              toolbarOptions.push([{"placeholder":tokenToolbar}]);
            }

            var quillOptions = {
              //debug: 'info',
              modules: {
                toolbar: toolbarOptions,
                'emoji-toolbar': true
              },
              placeholder: this.options.placeholder ? this.options.placeholder : 'Please enter content...',
              //readOnly: true,
              theme: 'snow'
            };
            if (window.nmEditor.tokenTrigger) {
              quillOptions.modules.placeholder = {};
              quillOptions.modules.placeholder.delimiters = ['', ''];
              quillOptions.modules.placeholder.placeholders = tokenQuillOption;
            }

            this.editor = new Quill('#' + quillID, quillOptions);

            // Added plain clipboard feature to quill
            // refs https://quilljs.com/docs/modules/#extending
            // refs https://quilljs.com/docs/modules/clipboard/#addmatcher
            // refs https://github.com/quilljs/quill/issues/1184#issuecomment-384935594
            // refs https://stackoverflow.com/a/55026088
            this.editor.clipboard.addMatcher(Node.ELEMENT_NODE, function (node, delta) {
                var ops = [];
                delta.ops.forEach(function(op) {
                  if (op.insert && typeof op.insert === 'string') {
                    ops.push({
                      insert: op.insert
                    });
                  }
                });
                delta.ops = ops;
                return delta;
            });

            this.editor.focus();
            var d = this.editor.getContents();
            if (Array.isArray(d.ops) && d.ops.length) {
                var lastIndex = d.ops.length - 1,
                lastOp = d.ops[lastIndex];

                if (lastOp.insert && typeof lastOp.insert === 'string') {
                    // refs https://github.com/quilljs/quill/issues/1235#issuecomment-273044116
                    // Because quill will generate two line breaks at the end of the content, we need to remove one line break so that the edited content is consistent with the content when browsing.
                    d.ops[lastIndex].insert = lastOp.insert.replace(/\n$/, "");
                    this.editor.setContents(d);
                }
            }
        },

        // Call when editing is complete (3）
        value2html: function(value, element) {
            var html = '',
                text = '',
                blockID = this.options.scope.attributes['data-id']['nodeValue'];

            // Get the HTML of this block from xeditable
            if (blockID) {
                html = this.xeditable.html[blockID];
                text = stripHTML(html);
            }

            if (text.trim() === '' && !$(element).hasClass(TEXT_EMPTY_CLASS)) {
                $(element).addClass(TEXT_EMPTY_CLASS);
            }
            else {
                $(element).removeClass(TEXT_EMPTY_CLASS);
            }

            // Output HTML to x-editable
            $(element).html(html);
            // Replace quill emoji blot to simple emoji entity
            $(element).find(".ql-emojiblot").each(function() {
                var $emojiBlot = $(this),
                emoji = typeof $emojiBlot.context !== "undefined" ? $emojiBlot.context.innerText.trim() : $emojiBlot[0].innerText.trim();

                $emojiBlot.after(emoji);
                $emojiBlot.remove();
            });

            // Replace quill placeholder tokens to simple token strings
            $(element).find(".ql-placeholder-content").each(function() {
              var $placeholderContent = $(this),
                  tokenValue = $placeholderContent.attr('data-id');

              // If we can't get the token value from data-id, try to extract it from the inner text
              if (!tokenValue || tokenValue === "") {
                  tokenValue = typeof $placeholderContent.context !== "undefined" ? 
                              $placeholderContent.context.innerText.trim() : 
                              $placeholderContent[0].innerText.trim();
              }

              // Insert the token value directly and remove the placeholder element
              $placeholderContent.after(tokenValue);
              $placeholderContent.remove();
          });
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
                var html = this.editor.root.innerHTML;

                // Save data to xeditable
                this.xeditable.delta[blockID] = this.editor.getContents();
                this.xeditable.html[blockID] = html;

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