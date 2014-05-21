/**
 * Provides the javascript for managing syncml sessions.
 *
 * @copyright  2014 Horde LLC
 * @license    LGPL-2 (http://www.horde.org/licenses/lgpl)
 */

var HordeSyncMLPrefs = {

    removeAnchor: function(device, db) {
        $('removedevice').setValue(device);
        $('removedb').setValue(db);
        document.forms.prefs.actionID = 'update_special';
        document.forms.prefs.submit();
    }

};
