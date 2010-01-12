/**
 * Provides the javascript for managing accounts.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

var ImpAccountsPrefs = {
    // Variables set by other code: confirm_delete

    _sendData: function(a, d, c)
    {
        $('accounts_action').setValue(a)
        $('accounts_data').setValue(d);
        if (c) {
            $('prefs').getInputs('hidden', 'actionID').first().clear();
        }
        $('prefs').submit();
    },

    clickHandler: function(e)
    {
        if (e.isRightClick()) {
            return;
        }

        var elt = e.element();

        while (Object.isElement(elt)) {
            if (elt.hasClassName('accountsdelete')) {
                if (window.confirm(this.confirm_delete)) {
                    this._sendData('delete', elt.up('TR').readAttribute('id').substring(11));
                }
                e.stop();
                return;
            }

            switch (elt.readAttribute('id')) {
            case 'add_button':
                this._sendData('add', '');
                break;

            case 'cancel_button':
                this._sendData('', '', true);
                break;

            case 'new_button':
                this._sendData('new', '', true);
                break;
            }

            elt = elt.up();
        }
    }

};

document.observe('dom:loaded', function() {
    document.observe('click', ImpAccountsPrefs.clickHandler.bindAsEventListener(ImpAccountsPrefs));
});
