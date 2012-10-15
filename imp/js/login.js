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
        var k = $('imp_server_key');

        if (k && $F(k).startsWith('_')) {
            alert(this.server_key_error);
            k.focus();
        } else {
            parentfunc();
        }
    }
};

HordeLogin.submit = HordeLogin.submit.wrap(ImpLogin.submit.bind(ImpLogin));
