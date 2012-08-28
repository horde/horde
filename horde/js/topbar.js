/**
 * Scripts for the Horde topbar.
 */
var HordeTopbar = {
    // Vars used and defaulting to null/false:
    //   searchGhost

    /**
     * Date format.
     *
     * @var string
     */
    format: '',

    /**
     * Updates the date in the sub bar.
     */
    updateDate: function()
    {
        var d = $('horde-sub-date');

        if (d) {
            d.update(Date.today().toString(this.format));
            this.updateDate.bind(this).delay(10);
        }
    },

    /*
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
    */

    onDomLoad: function()
    {
        if ($('horde-search-input')) {
            this.searchGhost = new FormGhost('horde-search-input');
        }

        this.updateDate();
    }
}

document.observe('dom:loaded', HordeTopbar.onDomLoad.bind(HordeTopbar));
