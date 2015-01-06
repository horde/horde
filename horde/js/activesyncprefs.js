/**
 * Provides the javascript for managing ActiveSync partner devices.
 *
 * @copyright  2014-2015 Horde LLC
 * @license    LGPL-2 (http://www.horde.org/licenses/lgpl)
 */

var HordeActiveSyncPrefs = {

    // Set in lib/Prefs/Ui.php: devices

    clickHandler: function(e)
    {
        var id = e.element().readAttribute('id');

        if (id.startsWith('wipe_')) {
            $('wipeid').setValue(this.devices[id.substr(5)].id);
            $('actionID').setValue('update_special');
            $('prefs').submit();
            e.stop();
        } else if (id.startsWith('cancel_')) {
            $('cancelwipe').setValue(this.devices[id.substr(7)].id);
            $('actionID').setValue('update_special');
            $('prefs').submit();
            e.stop();
        } else if (id.startsWith('remove_')) {
            $('removedevice').setValue(this.devices[id.substr(7)].id);
            $('actionID').setValue('update_special');
            $('prefs').submit();
            e.stop();
        }
    },

    onDomLoad: function()
    {
        $('prefs').observe('click', this.clickHandler.bindAsEventListener(this));
    }
};

document.observe('dom:loaded', HordeActiveSyncPrefs.onDomLoad.bind(HordeActiveSyncPrefs));
