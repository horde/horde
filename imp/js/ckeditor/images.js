/**
 * CKEditor 3 doesn't support onchange event for content body.
 * Instead, need to track attached images to detect deletions, since this may
 * influence attachment limits.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2014-2015 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */

var ImpCkeditorImgs = {};
// 'related_attr' property set in IMP_Script_Package_ComposeBase

(function (ob) {
    var active = [];

    function checkRemove(ev)
    {
        var del = [],
            n = ev.editor.name;

        active[n].each(function(a) {
            if (!a.parentNode) {
                del.push(a);
            }
        });

        ob.remove(ev.editor, del);
    };

    ob.add = function(editor, elt)
    {
        var n = editor.name;

        if (!active[n]) {
            active[n] = [];
            editor.on('afterUndoImage', checkRemove);
            editor.resetUndo();
        }

        active[n].push(elt);
    };

    ob.remove = function(editor, elts)
    {
        var ids = [],
            n = editor.name;

        if (!elts.size()) {
            return;
        }

        elts.invoke('getAttribute', ob.related_attr).compact().each(function(r) {
            var s = r.split(';', 2);
            ids.push(s[1]);
        });

        ImpCompose.attachlist.removeAttach(ids, true);

        // Array.without() doesn't support array input.
        active[n] = active[n].findAll(function(v) {
            return !elts.include(v);
        });

        if (!active[n].size()) {
            delete active[n];
            editor.removeListener('afterUndoImage', checkRemove);
        }

        editor.resetUndo();
    };

}(ImpCkeditorImgs));
