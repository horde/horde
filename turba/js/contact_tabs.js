var TurbaTabs = {
    // Properties: current, tabs

    showTab: function(tab)
    {
        var next, id;

        this.tabs.each(function(item) {
            id = item.id.substring(3);
            if (id == tab) {
                item.addClassName('horde-active');
                $(id).show();
                next = id;
            } else {
                item.removeClassName('horde-active');
                if ($(id).visible() &&
                    !Object.isUndefined(window['sections_Turba_View_' + id])) {
                    this.current = window['sections_Turba_View_' + id]._get();
                }
                $(id).hide();
            }
        }, this);

        if (this.current &&
            !Object.isUndefined(window['sections_Turba_View_' + next])) {
            window['sections_Turba_View_' + next].toggle(this.current);
        }

        return false;
    },

    onDomLoad: function() {
        this.tabs = $('page').select('.horde-buttonbar')[0].down().select('li');
    }
};

document.observe('dom:loaded', TurbaTabs.onDomLoad.bind(TurbaTabs));
