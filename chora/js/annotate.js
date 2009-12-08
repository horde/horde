/**
 * Chora annotate.php javascript code.
 *
 * @author Michael Slusarz <slusarz@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

var Chora_Annotate = {
    showLog: function(e) {
        var elt = e.element(), rev, newelt;
        if (!elt.hasClassName('logdisplay')) {
            elt = elt.up('SPAN.logdisplay');
            if (!elt) {
                return;
            }
        }

        e.stop();

        rev = elt.hide().up('TD').down('A').readAttribute('rev');
        newelt = new Element('TD', { colspan: 5 }).insert(Chora.loading_text);

        elt.up('TR').insert({ after: new Element('TR', { className: 'logentry' }).insert(newelt) });

        new Ajax.Updater(newelt, Chora.ANNOTATE_URL +  '=' + rev);
    }
};

document.observe('click', Chora_Annotate.showLog.bindAsEventListener(Chora_Annotate));
