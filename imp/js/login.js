/**
 * Provides the javascript for the login.php script.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

var ImpLogin = {
    // The following variables are defined in login.php:
    //  dimp_sel, server_key_error

    submit: function(parentfunc)
    {
        if ($('imp_server_key') && $F('imp_server_key').startsWith('_')) {
            alert(this.server_key_error);
            $('imp_server_key').focus();
            return;
        }

        parentfunc();
    }
};

HordeLogin.submit = HordeLogin.submit.wrap(ImpLogin.submit.bind(ImpLogin));
