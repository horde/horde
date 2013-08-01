/**
 * Provides the javascript for the login.php script.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

HordeLogin.submit = HordeLogin.submit.wrap(function(parentfunc) {
    var k = $('imp_server_key');
    if (k && $F(k).startsWith('_')) {
        alert(HordeLogin.server_key_error);
        k.focus();
    } else {
        parentfunc();
    }
});
