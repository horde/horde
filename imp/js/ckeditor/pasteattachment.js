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
                    e2.data.$.dataTransfer,
                    { img_tag: 1 },
                    attachCallback
                );
                e2.data.preventDefault();
            });
        });

        editor.on('paste', function(ev) {
            if (ev.data.html) {
                var span = new Element('SPAN').insert(ev.data.html).down();

                if (span && span.match('IMG')) {
                    HordeCore.doAction('addAttachment', {
                        composeCache: $F(DimpCompose.getCacheElt()),
                        file_upload: span.readAttribute('src'),
                        img_tag: 1,
                        json_return: 1
                    }, {
                        callback: attachCallback
                    });

                    ev.data.html = '';
                } else {
                    ev.data.html = ev.data.html.stripTags();
                }
            }
        });
    }

});
