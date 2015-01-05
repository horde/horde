/**
 * Handling of the passphrase dialog.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2014-2015 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */

var ImpPassphraseDialog = {

    display: function(data)
    {
        HordeDialog.display(Object.extend(data, {
            form_id: 'imp_passphrase',
            password: true
        }));
    },

    onClick: function(e)
    {
        switch (e.element().identify()) {
        case 'imp_passphrase':
            HordeCore.doAction(
                'checkPassphrase',
                e.findElement('FORM').serialize(true),
                { callback: this.callback.bind(this) }
            );
            break;
        }
    },

    callback: function(r)
    {
        if (r) {
            $('imp_passphrase').fire('ImpPassphraseDialog:success');
            HordeDialog.close();
        }
    }

};

document.observe('HordeDialog:onClick', ImpPassphraseDialog.onClick.bindAsEventListener(ImpPassphraseDialog));
