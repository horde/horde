/**
 * Provides the javascript for managing activesync partner devices.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */
var HordeActiveSyncAdmin = {

    requestRemoteWipe: function(device) {
        document.forms.activesyncadmin.deviceID.value = device;
        document.forms.activesyncadmin.actionID.value = 'wipe';
        document.forms.activesyncadmin.submit();
    },

    cancelRemoteWipe: function(device) {
        document.forms.activesyncadmin.deviceID.value = device;
        document.forms.activesyncadmin.actionID.value = 'cancelwipe';
        document.forms.activesyncadmin.submit();
    },

    removeDevice: function(device, user) {
        document.forms.activesyncadmin.deviceID.value = device;
        document.forms.activesyncadmin.uid.value = user;
        document.forms.activesyncadmin.actionID.value = 'delete';
        document.forms.activesyncadmin.submit();
    },

    reprovision: function() {
        document.forms.activesyncadmin.actionID.value = 'reset';
        document.forms.activesyncadmin.submit();
    }

}