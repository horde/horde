/**
 * Paste ignore plugin for CKEditor.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2013-2014 Horde LLC
 * @license    GPLv2 (http://www.horde.org/licenses/gpl)
 */

CKEDITOR.plugins.add('pasteignore', {
    init: function(editor) {
        editor.on('contentDom', function(e1) {
            e1.editor.document.on('drop', function (e2) {
                e2.data.preventDefault(true);
            });
        });
    }
});
