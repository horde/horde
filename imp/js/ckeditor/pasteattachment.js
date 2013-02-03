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

        function frOnload(n, e)
        {
            HordeCore.doAction('addAttachment', {
                composeCache: $F(DimpCompose.getCacheElt()),
                file_upload: e.target.result,
                file_upload_dataurl: true,
                file_upload_filename: n
            }, {
                callback: attachCallback
            });
        };

        editor.on('contentDom', function(e1) {
            e1.editor.document.on('drop', function (e2) {
                var f = e2.data.$.dataTransfer, fr;

                if (f && f.files) {
                    fr = new FileReader();

                    $R(0, f.files.length - 1).each(function(n) {
                        fr.onload = frOnload.curry(f.files[n].name);
                        fr.readAsDataURL(f.files[n]);
                    });
                }

                e2.data.preventDefault();
            });
        });

        editor.on('paste', function(ev) {
            if (ev.data.html) {
                var span = new Element('SPAN').insert(ev.data.html).down();

                if (span.match('IMG')) {
                    HordeCore.doAction('addAttachment', {
                        composeCache: $F(DimpCompose.getCacheElt()),
                        file_upload: span.readAttribute('src'),
                        file_upload_dataurl: true
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
