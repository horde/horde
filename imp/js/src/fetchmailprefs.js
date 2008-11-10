/**
 * Provides the javascript for the fetchmailprefs.php script.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

var fmprefs_loading = false;

function accountSubmit(isnew)
{
    if (!fmprefs_loading &&
        ((isnew != null) || !$F('account').empty())) {
        fmprefs_loading = true;
        $('fm_switch').submit();
    }
}

function driverSubmit()
{
    if (!fmprefs_loading && $F('fm_driver')) {
        fmprefs_loading = true;
        $('fm_driver_form').submit();
    }
}
