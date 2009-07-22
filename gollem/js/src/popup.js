/**
 * Gollem Popup JavaScript.
 *
 * Provides the javascript to open popup windows.
 * This file should be included via Horde::addScriptFile().
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

/**
 * Open a popup window.
 *
 * @param string $url      The URL to open in the popup window.
 * @param integer $width   The width of the popup window. (Default: 600 px)
 * @param integer $height  The height of the popup window. (Default: 500 px)
 * @param string $args     Any additional args to pass to the script.
 *                         (Default: no args)
 */
function popup_gollem(url, width, height, args)
{
    if (!width) {
        width = 600;
    }
    var screen_width = screen.width;
    if (width > (screen_width - 75)) {
        width = screen_width - 75;
    }

    if (!height) {
        height = 500;
    }
    var screen_width = screen.width;
    if (width > (screen_width - 75)) {
        width = screen_width - 75;
    }

    var now = new Date();
    var name = now.getTime();

    if (url.indexOf('?') == -1) {
        var glue = '?';
    } else {
        var glue = '&';
    }

    if (args != '') {
        url = url + glue + unescape(args) + '&uniq=' + name;
    } else {
        url = url + glue + 'uniq=' + name;
    }

    param = 'toolbar=no,location=no,status=yes,scrollbars=yes,resizable=yes,width=' + width + ',height=' + height + ',left=0,top=0';
    win = window.open(url, name, param);
    if (!win) {
        alert(GollemText.popup_block);
    } else {
        if (typeof win.name == 'undefined') {
            win.name = name;
        }
        if (typeof win.opener == 'undefined') {
            win.opener = self;
        }
        win.focus();
    }
}
