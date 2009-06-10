/**
 * Provides basic IMP javascript functions.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

var IMP = window.IMP || {};

IMP.menuFolderSubmit = function(clear)
{
    var mf = $('menuform');

    if ((!this.menufolder_load || clear) &&
        $F(mf.down('SELECT[name="mailbox"]'))) {
        this.menufolder_load = true;
        mf.submit();
    }
};

/**
 * Open a popup window.
 *
 * @param string $url      The URL to open in the popup window.
 * @param integer $width   The width of the popup window. (Default: 600 px)
 * @param integer $height  The height of the popup window. (Default: 500 px)
 * @param string $args     Any additional args to pass to the script.
 *                         (Default: no args)
 */
IMP.popup = function(url, width, height, args)
{
    var q, win,
        params = $H(),
        name = new Date().getTime();

    height = Math.min(screen.height - 75, height || 500);
    width = Math.min(screen.width - 75, width || 600);

    q = url.indexOf('?');
    if (q != -1) {
        params = $H(url.toQueryParams());
        url = url.substring(0, q);
    }

    if (args) {
        $H(args.toQueryParams()).each(function(a) {
            params.set(a.key, unescape(a.value));
        });
    }
    params.set('uniq', name);

    win = window.open(url + '?' + params.toQueryString(), name, 'toolbar=no,location=no,status=yes,scrollbars=yes,resizable=yes,width=' + width + ',height=' + height + ',left=0,top=0');
    if (!win) {
        alert(IMP.text.popup_block);
    } else {
        if (Object.isUndefined(win.name)) {
            win.name = name;
        }
        if (Object.isUndefined(win.opener)) {
            win.opener = self;
        }
        win.focus();
    }
};

/**
 * Use DOM manipulation to un-block images.
 */
IMP.unblockImages = function(e)
{
    var elt = e.element().up('TABLE.mimeStatusMessage');

    elt.next('.htmlMessage').select('[blocked]').each(function(e) {
        var src = decodeURIComponent(e.readAttribute('blocked'));
        if (e.hasAttribute('src')) {
            e.writeAttribute('src', src);
        } else if (e.hasAttribute('background')) {
            e.writeAttribute('background', src);
        } else if (e.style.backgroundImage) {
            e.setStyle({ backgroundImage: 'url(' + src + ')' });
        }
    });

    Effect.Fade(elt, { duration: 0.6, afterFinish: function() { elt.remove(); } });

    e.stop();
};

document.observe('dom:loaded', function() {
    // If menu is present, attach event handlers to folder switcher.
    var tmp = $('openfoldericon');
    if (tmp) {
        $('menuform').observe('change', IMP.menuFolderSubmit.bind(IMP));
        tmp.down().observe('click', IMP.menuFolderSubmit.bind(IMP, true));
    }
});
