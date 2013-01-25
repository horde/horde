/**
 * ckeditor/paste.js - Paste plugin for CKEditor.
 *
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

CKEDITOR.plugins.add('paste', {
    init: function(editor) {
        editor.on('contentDom', function(e1) {
            e1.editor.document.on('drop', function (e2) {
                e2.data.preventDefault(true);
            });
        });

        editor.on('paste', function(ev) {
            if (ev.data.html) {
                ev.data.html = ev.data.html.stripTags();
            }
        });
    }
});
