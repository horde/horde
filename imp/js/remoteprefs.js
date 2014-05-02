/**
 * Managing remote accounts.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2013-2014 Horde LLC
 * @license    GPLv2 (http://www.horde.org/licenses/gpl)
 */

var ImpRemotePrefs = {

    // Variables set by other code: confirm_delete

    _sendData: function(a, d, c)
    {
        $('remote_action').setValue(a);
        $('remote_data').setValue(d);
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
            if (elt.hasClassName('remotedelete')) {
                if (window.confirm(this.confirm_delete)) {
                    this._sendData('delete', elt.readAttribute('data-id'));
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

document.observe('click', ImpRemotePrefs.clickHandler.bindAsEventListener(ImpRemotePrefs));
