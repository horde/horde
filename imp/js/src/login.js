/**
 * Provides the javascript for the login.php script.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

var ImpLogin = {
    // The following variables are defined in login.php:
    //  autologin_url, imp_auth, lang_url, show_list

    _reload: function()
    {
        window.top.document.location = this.autologin_url + $F('server_key');
    },

    submit: function()
    {
        if (this.show_list && $F('server_key').startsWith('_')) {
            return;
        }

        if (!$F('imapuser')) {
            alert(IMP.text.login_username);
            $('imapuser').focus();
        } else if (!$F('pass')) {
            alert(IMP.text.login_password);
            $('pass').focus();
        } else {
            $('loginButton').disable();
            if (this.ie_clientcaps) {
                try {
                    $('ie_version').setValue(objCCaps.getComponentVersion("{89820200-ECBD-11CF-8B85-00AA005B4383}","componentid"));
                } catch (e) { }
            }
            $('imp_login').submit();
        }
    },

    _selectLang: function()
    {
        // We need to reload the login page here, but only if the user hasn't
        // already entered a username and password.
        if (!$F('imapuser') && !$F('pass')) {
            var params = { new_lang: $F('new_lang') };
            if (this.lang_url) {
                params.url = this.lang_url;
            }
            self.location = 'login.php?' + Object.toQueryString(params);
        }
    },

    /* Removes any leading hash that might be on a location string. */
    _removeHash: function(h)
    {
        return (Object.isString(h) && h.startsWith("#")) ? h.substring(1) : h;
    },

    onDomLoad: function()
    {
        if (this.imp_auth) {
            if (parent.frames.horde_main) {
                if (this.nomenu) {
                    parent.location = self.location;
                } else {
                    document.imp_login.target = '_parent';
                }
            }
        }

        document.observe('change', this._changeHandler.bindAsEventListener(this));
        document.observe('click', this._clickHandler.bindAsEventListener(this));

        // Need to capture hash information if it exists in URL
        if (location.hash) {
            $('anchor_string').setValue(this._removeHash(location.hash));
        }

        if (!$F('imapuser')) {
            $('imapuser').focus();
        } else {
            $('pass').focus();
        }

        if (this.reloadmenu && window.parent.frames.horde_menu) {
            window.parent.frames.horde_menu.location.reload();
        }
    },

    _changeHandler: function(e)
    {
        switch (e.element().readAttribute('id')) {
        case 'new_lang':
            this._selectLang();
            break;

        case 'server_key':
            this._reload();
            break;
        }
    },

    _clickHandler: function(e)
    {
        if (e.isRightClick()) {
            return;
        }

        var elt = e.element();

        while (Object.isElement(elt)) {
            switch (elt.readAttribute('id')) {
            case 'btn_login':
                this._reload();
                break;

            case 'loginButton':
                this.submit();
                break;
            }

            elt = elt.up();
        }
    }

};

document.observe('dom:loaded', ImpLogin.onDomLoad.bind(ImpLogin));
