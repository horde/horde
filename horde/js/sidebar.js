/**
 * Horde sidebar javascript.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

var HordeSidebar = {

    getCookie: function(name, deflt)
    {
        var cookie = document.cookie.toQueryParams(';');
        return cookie[name]
            ? unescape(cookie[name])
            : deflt;
    },

    toggleMenuFrame: function()
    {
        if (!parent || !parent.document.getElementById('hf')) {
            return;
        }

        var cols,
            expires = new Date(),
            rtl = horde_sidebar_rtl;
        if ($('expandedSidebar').visible()) {
            cols = rtl ? '*,20' : '20,*';
        } else {
            cols = (rtl ? '*,' : '') + horde_sidebar_cols + (rtl ? '' : ',*');
        }
        parent.document.getElementById('hf').setAttribute('cols', cols);
        $('expandedSidebar', 'hiddenSidebar').invoke('toggle');
        if ($('themelogo')) {
            $('themelogo').toggle();
        }

        // Expire in one year.
        expires.setTime(expires.getTime() + 31536000000);
        document.cookie = 'horde_sidebar_expanded=' + $('expandedSidebar').visible() + ';DOMAIN=' + horde_sidebar_domain + ';PATH=' + horde_sidebar_path + ';expires=' + expires.toGMTString();
    },

    updateSidebar: function()
    {
        new Ajax.PeriodicalUpdater(
            'horde_menu',
            horde_sidebar_url,
            {
                parameters: { httpclient: 1 },
                method: 'get',
                evalScripts: true,
                frequency: horde_sidebar_refresh,
                onSuccess: function ()
                {
                    var layout = $('horde_menu').getLayout();
                    $('horde_menu').setStyle({
                        width: layout.get('width') + 'px',
                        height: layout.get('height') + 'px'
                    });
                }
            }
        );
    }

};

document.observe('dom:loaded', function() {
    $('hiddenSidebar').hide();
    if (HordeSidebar.getCookie('horde_sidebar_expanded', true).toString() != $('expandedSidebar').visible().toString()) {
        HordeSidebar.toggleMenuFrame();
    }
    if (horde_sidebar_refresh) {
        HordeSidebar.updateSidebar.delay(horde_sidebar_refresh);
    }
});
