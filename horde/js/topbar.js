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
    updateDate: function() {
        $('horde-sub-date').update(Date.today().toString(this.format));
        this.updateDate.bind(this).delay(1);
    }
}
document.observe('dom:loaded', HordeTopbar.updateDate.bind(HordeTopbar));
