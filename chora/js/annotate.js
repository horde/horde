/**
 * Chora annotate.php javascript code.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael Slusarz <slusarz@horde.org>
 */

var Chora_Annotate = {
    showLog: function(e) {
        var elt = e.findElement('span.logdisplay'), rev, newelt;
        if (!elt) {
            return;
        }
        e.stop();
        if (elt.retrieve('expanded')) {
            elt.up('tr').next('tr').remove();
            elt.store('expanded', false);
        } else {
            rev = elt.readAttribute('rev');
            newelt = new Element('td', { colspan: 6 }).insert(Chora.loading_text);
            elt.up('tr').insert({ after: new Element('tr', { className: 'logentry' }).insert(newelt) });
            elt.store('expanded', true);
            new Ajax.Updater(newelt, Chora.ANNOTATE_URL + '=' + rev);
        }
    }
};

document.observe('click', Chora_Annotate.showLog.bindAsEventListener(Chora_Annotate));
