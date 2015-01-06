/**
 * Provides the javascript for the admin user update page.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2014-2015 Horde LLC
 * @license    LGPL-2 (http://www.horde.org/licenses/lgpl)
 */

var HordeAdminUserUpdate = {

    // Set in admin/user.php: pass_error

    onSubmit: function(e)
    {
        if ($('user_pass_1') &&
            $F('user_pass_1') != $F('user_pass_2')) {
            $('user_pass_1', 'user_pass_2').invoke('setValue', '');
            window.alert(this.pass_error);
            e.stop();
        }
    }

};

document.on('submit', '#updateuser', HordeAdminUserUpdate.onSubmit.bind(HordeAdminUserUpdate));
