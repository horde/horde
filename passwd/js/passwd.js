/**
 * Provides javascript support for the main passwd page.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

document.observe('dom:loaded', function() {
    $('passwd-submit').observe('click', function(e) {
        if (!$F('passwd-oldpassword')) {
            alert(Passwd.current_pass);
            $('passwd-oldpassword').focus();
            e.stop();
            return;
        }
        if (!$F('passwd-newpassword0')) {
            alert(Passwd.new_pass);
            $('passwd-newpassword0').focus();
            e.stop();
            return;
        }
        if (!$F('passwd-newpassword1')) {
            alert(Passwd.verify_pass);
            $('passwd-newpassword1').focus();
            e.stop();
            return;
        }
        if ($F('passwd-newpassword0') != $F('passwd-newpassword1')) {
            alert(Passwd.no_match);
            $('passwd-newpassword0').focus();
            e.stop();
            return;
        }
    });

    $('passwd').focusFirstElement();
});
