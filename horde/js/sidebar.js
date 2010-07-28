/**
 * Horde sidebar javascript.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

var HordeSidebar = {
    // Variables set in services/sidebar.php:
    // domain, path, refresh, rtl, tree, url, width

    toggleSidebar: function()
    {
        var expanded = $('expandedSidebar').visible(),
            expires = new Date();

        $('expandedSidebar', 'hiddenSidebar').invoke('toggle');
        if ($('themelogo')) {
            $('themelogo').toggle();
        }

        this.setMargin(!expanded);

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
    },

    setMargin: function(expanded)
    {
        var margin = expanded
            ? this.width
            : $('hiddenSidebar').down().getWidth();

        if (this.rtl) {
            $('horde_body').setStyle({ marginRight: margin + 'px' });
        } else {
            $('horde_body').setStyle({ marginLeft: margin + 'px' });
        }
    },

    onDomLoad: function()
    {
        if ($('hiddenSidebar').visible()) {
            this.setMargin(false);
        }

        if (this.refresh) {
            this.updateSidebar.bind(this).delay(this.refresh);
        }

        $('expandButton', 'hiddenSidebar').invoke('observe', 'click', this.toggleSidebar.bind(this));
    }

};

document.observe('dom:loaded', HordeSidebar.onDomLoad.bind(HordeSidebar));
