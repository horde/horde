/**
 * Paste attachment plugin for CKEditor.
 *
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

CKEDITOR.plugins.add('pasteattachment', {

    init: function(editor)
    {
        function attachCallback(r)
        {
            var iframe = editor.getThemeSpace('contents').$.down('IFRAME');

            Prototype.Selector.select('[dropatc_id=' + r.file_id + ']', iframe.contentDocument || iframe.contentWindow.document).each(function(elt) {
                if (r.success) {
                    elt.setAttribute(r.img.related[0], r.img.related[1]);
                    elt.setAttribute('height', elt.height);
                    elt.setAttribute('width', elt.width);
                } else {
                    elt.parentNode.removeChild(elt);
                }
            });
        };

        function uploadAtc(files)
        {
            DimpCompose.uploadAttachmentAjax(
                files,
                { img_data: 1 },
                attachCallback
            ).each(function(file) {
                var fr = new FileReader();
                fr.onload = function(e) {
                    var elt = new CKEDITOR.dom.element('img');
                    elt.setAttributes({
                        dropatc_id: file.key,
                        src: e.target.result
                    });
                    editor.insertElement(elt);
                };
                fr.readAsDataURL(file.value);
            });
        };

        function fireEventInParent(type)
        {
            var evt;

            try {
                evt = new CustomEvent(type, { bubbles: true, cancelable: true });
            } catch (ex) {
                evt = document.createEvent('DragEvent');
                evt.initEvent(type, true, true);
            }
            editor.getThemeSpace('contents').$.dispatchEvent(evt);
        };

        editor.on('contentDom', function(e1) {
            editor.document.on('drop', function(e2) {
                /* Only support images for now. */
                if (e2.data.$.dataTransfer.files[0].type.startsWith('image/')) {
                    uploadAtc(e2.data.$.dataTransfer.files);
                    e2.data.preventDefault();
                }
                fireEventInParent('drop');
            });
            editor.document.on('dragover', function(e) {
                e.data.preventDefault();
                fireEventInParent('dragover');
            });
        });

        editor.on('paste', function(ev) {
            if (ev.data.html) {
                var data, i,
                    a = [],
                    span = new Element('SPAN').insert(ev.data.html).down();

                /* Only support images for now. */
                if (span && span.match('IMG')) {
                    data = span.readAttribute('src').split(',', 2);
                    data[1] = Base64.atob(data[1]);
                    a.length = data[1].length;

                    for (i = 0; i < a.length; ++i) {
                        a[i] = data[1].charCodeAt(i);
                    }

                    uploadAtc([ new Blob([ new Uint8Array(a) ], { type: data[0].split(':')[1].split(';')[0] }) ]);

                    ev.data.html = '';
                }
            }
        });
    }

});
