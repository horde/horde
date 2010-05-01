/**
 * Provides the javascript for managing activesync partner devices.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */
var HordeActiveSyncPrefs = {

    requestRemoteWipe: function(device) {
        $('wipeid').setValue(device);
        document.forms.prefs.actionID = 'update_special';
        document.forms.prefs.submit();
    },

    cancelRemoteWipe: function(device) {
        $('cancelwipe').setValue(device);
        document.forms.prefs.actionID = 'update_special';
        document.forms.prefs.submit();
    },

    removeDevice: function(device) {
        $('removedevice').setValue(device);
        document.forms.prefs.actionID = 'update_special';
        document.forms.prefs.submit();
    }
}