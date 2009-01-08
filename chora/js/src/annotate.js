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
        var elt = e.findElement('SPAN').hide(),
            rev = elt.up('TD').down('A').readAttribute('id').slice(4, -2),
            newelt = new Element('TD', { colspan: 5 }).insert(Chora.loading_text);
        elt.up('TR').insert({ after: new Element('TR').insert(newelt) });

        new Ajax.Updater(newelt, Chora.ANNOTATE_URL + rev);
    }
};

document.observe('dom:loaded', function() {
    $$('.logdisplay').invoke('observe', 'click', Chora_Annotate.showLog.bindAsEventListener(Chora_Annotate));
});
