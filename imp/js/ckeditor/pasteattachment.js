/**
 * Paste attachment plugin for CKEditor.
 *
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

CKEDITOR.plugins.add('pasteattachment', {

    init: function(editor)
    {
        function attachCallback(r)
        {
            if (r.success) {
                editor.insertHtml(r.img);
            }
        };

        editor.on('contentDom', function(e1) {
            e1.editor.document.on('drop', function(e2) {
                DimpCompose.uploadAttachmentAjax(
                    e2.data.$.dataTransfer.files,
                    { img_tag: 1 },
                    attachCallback
                );
                e2.data.preventDefault();
            });
        });

        editor.on('paste', function(ev) {
            if (ev.data.html) {
                var data, i,
                    a = [],
                    span = new Element('SPAN').insert(ev.data.html).down();

                if (span && span.match('IMG')) {
                    data = span.readAttribute('src').split(',', 2);
                    data[1] = atob(data[1]);
                    a.length = data[1].length;

                    for (i = 0; i < a.length; ++i) {
                        a[i] = data[1].charCodeAt(i);
                    }

                    DimpCompose.uploadAttachmentAjax(
                        [ new Blob([ new Uint8Array(a) ], { type: data[0].split(':')[1].split(';')[0] }) ],
                        { img_tag: 1 },
                        attachCallback
                    );

                    ev.data.html = '';
                } else {
                    ev.data.html = ev.data.html.stripTags();
                }
            }
        });
    }

});
