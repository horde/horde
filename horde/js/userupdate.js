/**
 * Provides the javascript for the admin user update page.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
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
    },

    onDomLoad: function()
    {
        $('updateuser').observe('submit', this.onSubmit.bindAsEventListener(this));
    }

};

document.observe('dom:loaded', HordeAdminUserUpdate.onDomLoad.bind(HordeAdminUserUpdate));
