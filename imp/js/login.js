/**
 * Login page.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2014 Horde LLC
 * @license    GPLv2 (http://www.horde.org/licenses/gpl)
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
