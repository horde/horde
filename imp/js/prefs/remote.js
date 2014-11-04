/**
 * Managing remote accounts.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2013-2014 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */

var ImpRemotePrefs = {

    // Variables set by PHP code: confirm_delete, empty_email, empty_password,
    //     next, wait

    _sendData: function(a, d, c)
    {
        $('remote_action').setValue(a);
        $('remote_data').setValue(d);
        if (c) {
            $('prefs').getInputs('hidden', 'actionID').first().clear();
        }
        $('prefs').submit();
    },

    _autoconfigCallback: function(r)
    {
        if (r.success) {
            $('remote_type').setValue(r.mconfig.imap ? 'imap' : 'pop3');
            $('remote_server').setValue(r.mconfig.host);
            $('remote_user').setValue(r.mconfig.username);
            $('remote_port').setValue(r.mconfig.port);
            $('remote_secure_autoconfig').setValue(r.mconfig.tls);

            if ($F('remote_label').blank()) {
                $('remote_label').setValue(r.mconfig.label);
            }

            $('remote_password').remove();
            $('autoconfig_button').hide();
            $('add_button').show();
        } else {
            $('autoconfig_button').setValue(this.next);
        }

        $('prefs').enable();
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

            case 'autoconfig_button':
                if ($F('remote_email').blank()) {
                    window.alert(this.empty_email);
                } else if ($F('remote_password').empty()) {
                    window.alert(this.empty_password);
                } else {
                    HordeCore.doAction(
                        'autoconfigAccount',
                        {
                            email: $F('remote_email'),
                            // Base64 encode just to keep password data from
                            // being plaintext. A trivial obfuscation, but
                            // will prevent passwords from leaking in the
                            // event of some sort of data dump.
                            password: Base64.encode($F('remote_password')),
                            password_base64: true,
                            secure: ~~($F('remote_secure') == 'yes')
                        },
                        {
                            callback: this._autoconfigCallback.bind(this)
                        }
                    );
                    elt.setValue(this.wait);
                    $('prefs').disable();
                }
                e.stop();
                break;

            case 'advanced_show':
                $('prefs').select('.imp-remote-autoconfig').invoke('hide');
                $('remote_secure_autoconfig').remove();
                $('prefs').select('.imp-remote-advanced').invoke('show');
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
