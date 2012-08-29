/**
 * Provides the javascript for the login.php script.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
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
            $('login-button').disable();
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
        // Need to capture hash information if it exists in URL
        if (location.hash) {
            $('anchor_string').setValue(this._removeHash(location.hash));
        }

        if ($('horde_user') && !$F('horde_user')) {
            $('horde_user').focus();
        } else if ($('horde_pass') && !$F('horde_pass')) {
            $('horde_pass').focus();
        } else {
            $('login-button').focus();
        }

        /* Activate dynamic view(s). */
        var s = $('horde_select_view');
        if (s) {
            s.down('option[value=dynamic]').show();
            s.down('option[value=smartmobile]').show();
            if (this.pre_sel) {
                s.selectedIndex = s.down('option[value=' + this.pre_sel + ']').index;
            }
        }
    },

    changeHandler: function(e)
    {
        switch (e.element().readAttribute('id')) {
        case 'new_lang':
            this._selectLang();
            break;
        }
    },

    clickHandler: function(e)
    {
        if (e.isRightClick()) {
            return;
        }

        var elt = e.element();

        while (Object.isElement(elt)) {
            switch (elt.readAttribute('id')) {
            case 'login-button':
                if (!elt.readAttribute('disabled')) {
                    this.submit();
                }
                e.stop();
                break;
            }

            elt = elt.up();
        }
    }

};

document.observe('dom:loaded', HordeLogin.onDomLoad.bind(HordeLogin));
document.observe('change', HordeLogin.changeHandler.bindAsEventListener(HordeLogin));
document.observe('click', HordeLogin.clickHandler.bindAsEventListener(HordeLogin));
