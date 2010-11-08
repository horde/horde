/**
 * Provides the javascript for the login.php script.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

var HordeLogin = {
    // Variables set by outside code: user_error, pass_error

    submit: function()
    {
        if ($('horde_user') && !$F('horde_user')) {
            alert(HordeLogin.user_error);
            $('horde_user').focus();
        } else if ($('horde_pass') && !$F('horde_pass')) {
            alert(HordeLogin.pass_error);
            $('horde_pass').focus();
        } else {
            $('login_button').disable();
            if (Prototype.Browser.IE) {
                try {
                    document.body.style.behavior = "url(#default#clientCaps)";
                    $('ie_version').setValue(document.body.getComponentVersion("{89820200-ECBD-11CF-8B85-00AA005B4383}", "componentid"));
                } catch (e) {}
            }
            $('login_post').setValue(1);
            $('horde_login').submit();
        }
    },

    _selectLang: function()
    {
        // We need to reload the login page here, but only if the user hasn't
        // already entered a username and password.
        if ((!$('horde_user') || !$F('horde_user')) &&
            (!$('horde_pass') || !$F('horde_pass'))) {
            var params = { new_lang: $F('new_lang') };
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
        document.observe('change', this._changeHandler.bindAsEventListener(this));
        document.observe('click', this._clickHandler.bindAsEventListener(this));

        // Need to capture hash information if it exists in URL
        if (location.hash) {
            $('anchor_string').setValue(this._removeHash(location.hash));
        }

        if ($('horde_user') && !$F('horde_user')) {
            $('horde_user').focus();
        } else if ($('horde_pass') && !$F('horde_pass')) {
            $('horde_pass').focus();
        }
    },

    _changeHandler: function(e)
    {
        switch (e.element().readAttribute('id')) {
        case 'new_lang':
            this._selectLang();
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
            case 'login-button':
                this.submit();
                e.stop();
                break;
            }

            elt = elt.up();
        }
    }

};

document.observe('dom:loaded', HordeLogin.onDomLoad.bind(HordeLogin));
