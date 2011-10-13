/**
 * Provides the javascript for managing syncml sessions.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */
var HordeSyncMLPrefs = {

    removeAnchor: function(device, db) {
        $('removedevice').setValue(device);
        $('removedb').setValue(db);
        document.forms.prefs.actionID = 'update_special';
        document.forms.prefs.submit();
    }
}