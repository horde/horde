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

    onDomLoad: function()
    {
        if ($('horde-search-input')) {
            this.searchGhost = new FormGhost('horde-search-input');
        }

        this.updateDate();
    }
}

document.observe('dom:loaded', HordeTopbar.onDomLoad.bind(HordeTopbar));
