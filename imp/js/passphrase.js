/**
 * Provides the javascript for handling the passphrase dialog.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
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
