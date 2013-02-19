/**
 * Provides the javascript for managing syncml sessions.
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 */
var HordeSyncMLPrefs = {

    removeAnchor: function(device, db) {
        $('removedevice').setValue(device);
        $('removedb').setValue(db);
        document.forms.prefs.actionID = 'update_special';
        document.forms.prefs.submit();
    }

};
