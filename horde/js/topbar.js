/**
 * Scripts for the Horde topbar.
 */
var HordeTopbar = {

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
    }
}

document.observe('dom:loaded', HordeTopbar.updateDate.bind(HordeTopbar));
