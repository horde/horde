/**
 * Horde Popup JavaScript.
 *
 * Provides the javascript to open popup windows.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

window.Horde = window.Horde || {};

/**
 * Open a popup window.
 *
 * Parameters:
 * 'height' - The height of the popup window. (Default: 650 px)
 * 'menu' - Show the browser menu in the popup? (Default: no)
 * 'name' - The name for the window. (Default: none)
 * 'noalert' - Don't show alert if window could not open. (Default: false)
 * 'onload' - A function to call when the window is loaded. (Default: none)
 * 'params' - Any additional params to pass to the script. (Default: no args)
 * 'width' - The width of the popup window. (Default: 700 px)
 * 'url' - The URL to open in the popup window.
 *
 * Returns true if window was opened, false otherwise.
 */
Horde.popup = function(opts)
{
    if (Object.isString(opts)) {
        opts = decodeURIComponent(opts).evalJSON(true);
    }

    var name, q, win,
        height = Math.min(screen.height - 75, opts.height || 650),
        menu = (opts.menu ? 'yes' : 'no'),
        params = $H(),
        uniq = new Date().getTime(),
        url = opts.url,
        width = Math.min(screen.width - 75, opts.width || 700);

    q = url.indexOf('?');
    if (q != -1) {
        params = $H(url.toQueryParams());
        url = url.substring(0, q);
    }

    if (opts.params) {
        $H(opts.params.toQueryParams()).each(function(a) {
            params.set(a.key, unescape(a.value));
        });
    }
    params.set('uniq', uniq);

    win = window.open(url + '?' + params.toQueryString(), opts.name || uniq, 'menubar=' + menu + ',toolbar=no,location=no,status=yes,scrollbars=yes,resizable=yes,width=' + width + ',height=' + height + ',left=0,top=0');

    if (!win) {
        if (!opts.noalert) {
            alert(Horde.popup_block_text);
        }
        return false;
    }

    if (opts.onload) {
        opts.onload = eval(opts.onload);
        win.onload = opts.onload.curry(win);
    }

    if (Object.isUndefined(win.name)) {
        win.name = name;
    }

    if (Object.isUndefined(win.opener)) {
        win.opener = self;
    }

    win.focus();

    return true;
}
