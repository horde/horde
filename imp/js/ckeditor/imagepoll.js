/**
 * Utility object used to poll for deletion of image (attachment) data.
 *
 * CKEditor 3 doesn't support onchange event for content body.
 * Instead, poll the attached images to detect deletions, since this may
 * influence attachment limits.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2014 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */

var IMP_Ckeditor_Imagepoll = {};
// 'related_attr' property set in IMP_Script_Package_ComposeBase

(function (ob) {

    var active = [];

    ob.add = function(elt)
    {
        if (!active.size()) {
            new PeriodicalExecuter(function(pe) {
                var del = [];

                active.each(function(a) {
                    if (!a.parentNode) {
                        del.push(a);
                    }
                });

                if (del.size()) {
                    ob.remove(del);

                    // Array.without() doesn't support array input.
                    active = active.findAll(function(v) {
                        return !del.include(v);
                    });

                    if (!active.size()) {
                        pe.stop();
                    }
                }
            }, 2);
        }

        active.push(elt);
    };

    ob.remove = function(elts)
    {
        var ids = [];

        elts.invoke('getAttribute', ob.related_attr).compact().each(function(r) {
            var s = r.split(';', 2);
            ids.push(s[1]);
        });

        if (ids.size()) {
            DimpCore.doAction(
                'deleteAttach',
                DimpCompose.actionParams({
                    atc_indices: Object.toJSON(ids),
                    quiet: 1
                })
            );
        }
    };

}(IMP_Ckeditor_Imagepoll));
