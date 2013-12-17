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

    selectLang: function()
    {
        // We need to reload the login page here, but only if the user hasn't
        // already entered a username and password.
        if ((!$('horde_user') || !$F('horde_user')) &&
            (!$('horde_pass') || !$F('horde_pass'))) {
            var params = { new_lang: $F('new_lang') };
            self.location = 'login.php?' + Object.toQueryString(params);
        }
    },

    loginButton: function(e)
    {
        if (e.isRightClick()) {
            return;
        }

        if (!e.element().readAttribute('disabled')) {
            this.submit();
        }
        e.stop();
    },

    /* Removes any leading hash that might be on a location string. */
    _removeHash: function(h)
    {
        return (Object.isString(h) && h.startsWith("#")) ? h.substring(1) : h;
    },

    onDomLoad: function()
    {
        var s = $('horde_select_view');

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

        /* Programatically activate views that require javascript. */
        if (s) {
            s.down('option[value=mobile_nojs]').remove();
            if (this.pre_sel) {
                s.selectedIndex = s.down('option[value=' + this.pre_sel + ']').index;
            }
            $('horde_select_view_div').show();
        }
    }

};

document.observe('dom:loaded', HordeLogin.onDomLoad.bind(HordeLogin));
document.on('change', '#new_lang', HordeLogin.selectLang.bind(HordeLogin));
document.on('click', '#login-button', HordeLogin.loginButton.bind(HordeLogin));
