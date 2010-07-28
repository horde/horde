/**
 * Horde sidebar javascript.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

var HordeSidebar = {
    // Variables set in services/portal/sidebar.php:
    // domain, path, refresh, rtl, tree, url, width

    getCookie: function(name, deflt)
    {
        var cookie = document.cookie.toQueryParams('; ');
        if (!cookie) {
            cookie = document.cookie.toQueryParams(';');
        }

        return cookie[name]
            ? unescape(cookie[name])
            : deflt;
    },

    toggleSidebar: function()
    {
        var expanded = $('expandedSidebar').visible(),
            expires = new Date(),
            margin;

        $('expandedSidebar', 'hiddenSidebar').invoke('toggle');
        if ($('themelogo')) {
            $('themelogo').toggle();
        }

        margin = expanded
            ? $('hiddenSidebar').down().getWidth()
            : this.width;
        if (this.rtl) {
            $('horde_body').setStyle({ marginRight: margin + 'px' });
        } else {
            $('horde_body').setStyle({ marginLeft: margin + 'px' });
        }

        // Expire in one year.
        expires.setTime(expires.getTime() + 31536000000);
        document.cookie = 'horde_sidebar_expanded=' + Number(!expanded) + ';DOMAIN=' + this.domain + ';PATH=' + this.path + ';expires=' + expires.toGMTString();
    },

    updateSidebar: function()
    {
        new PeriodicalExecuter(function() {
            new Ajax.Request(this.url, {
                onComplete: this.onUpdateSidebar.bind(this)
            });
        }.bind(this), this.refresh);
    },

    onUpdateSidebar: function(response)
    {
        var layout, r;

        if (request.responseJSON) {
            $('HordeSidebar.tree').update();

            r = request.responseJSON;
            this.tree.renderTree(r.nodes, r.root_nodes, r.is_static);

            this.resizeSidebar();
        }
    }

};

document.observe('dom:loaded', function() {
    $('hiddenSidebar').hide();
    if (HordeSidebar.getCookie('horde_sidebar_expanded', 1) != Number($('expandedSidebar').visible())) {
        HordeSidebar.toggleSidebar();
    }
    if (HordeSidebar.refresh) {
        HordeSidebar.updateSidebar.bind(HordeSidebar).delay(HordeSidebar.refresh);
    }

    $('expandButton', 'hiddenSidebar').invoke('observe', 'click', HordeSidebar.toggleSidebar.bind(HordeSidebar));
});
