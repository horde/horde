/**
 * Horde sidebar javascript.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

var HordeSidebar = {
    // Variables set in services/sidebar.php:
    // domain, path, refresh, tree, url, width

    toggleSidebar: function()
    {
        var expanded = $('expandedSidebar').visible(),
            expires = new Date();

        if (!expanded) {
            this.initLoadSidebar();
        }

        $('expandedSidebar', 'hiddenSidebar').invoke('toggle');
        if ($('themelogo')) {
            $('themelogo').toggle();
        }

        this.setMargin(!expanded);

        // Expire in one year.
        expires.setTime(expires.getTime() + 31536000000);
        document.cookie = 'horde_sidebar_expanded=' + Number(!expanded) + ';DOMAIN=' + this.domain + ';PATH=' + this.path + ';expires=' + expires.toGMTString();
    },

    refreshSidebar: function()
    {
        new PeriodicalExecuter(this.loadSidebar.bind(this), this.refresh);
    },

    initLoadSidebar: function()
    {
        if (!this.loaded) {
            $('sidebarLoading').show();
            this.loadSidebar();
            if (this.refresh) {
                this.refreshSidebar.bind(this).delay(this.refresh);
            }
            this.loaded = true;
        }
    },

    loadSidebar: function()
    {
        new Ajax.Request(this.url, {
            onComplete: this.onUpdateSidebar.bind(this)
        });
    },

    onUpdateSidebar: function(response)
    {
        var layout, r;

        $('sidebarLoading').hide();

        if (response.responseJSON) {
            $(HordeSidebar.tree.opts.target).update();

            r = response.responseJSON.response;
            this.tree.renderTree(r.nodes, r.root_nodes, r.is_static);
            r.files.each(function(file) {
                $$('head')[0].insert(new Element('script', { src: file }));
            });
        }
    },

    setMargin: function(expanded)
    {
        var hb = $('horde_body'),
            margin = expanded
            ? this.width
            : $('hiddenSidebar').getLayout().get('margin-box-width');

        switch ($(document.body).getStyle('direction')) {
        case 'ltr':
            hb.setStyle({ marginLeft: margin + 'px' });
            break;

        case 'rtl':
            hb.setStyle({ marginRight: margin + 'px' });
            break;
        }
    },

    onDomLoad: function()
    {
        if ($('hiddenSidebar').visible()) {
            this.setMargin(false);
        } else {
            this.initLoadSidebar();
        }

        $('expandButton', 'hiddenSidebar').invoke('observe', 'click', this.toggleSidebar.bind(this));
    }

};

document.observe('dom:loaded', HordeSidebar.onDomLoad.bind(HordeSidebar));
