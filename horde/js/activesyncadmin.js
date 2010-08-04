/**
 * Provides the javascript for administering ActiveSync partner devices.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */
var HordeActiveSyncAdmin = {

    // Set in admin/activesync.php: devices

    clickHandler: function(e)
    {
        var elt = e.element(),
            id = elt.readAttribute('id') || '';

        switch (id) {
        case 'reset':
            $('actionID').setValue('reset');
            $('activesyncadmin').submit();
            e.stop();
            break;

        default:
            if (id.startsWith('wipe_')) {
                $('deviceID').setValue(this.devices[id.substr(5)].id);
                $('actionID').setValue('wipe');
                $('activesyncadmin').submit();
                e.stop();
            } else if (id.startsWith('cancel_')) {
                $('deviceID').setValue(this.devices[id.substr(7)].id);
                $('actionID').setValue('cancelwipe');
                $('activesyncadmin').submit();
                e.stop();
            } else if (id.startsWith('remove_')) {
                $('deviceID').setValue(this.devices[id.substr(7)].id);
                $('actionID').setValue('delete');
                $('uid').setValue(this.devices[id.substr(7)].user);
                $('activesyncadmin').submit();
                e.stop();
            }
            break;
        }
    },

    onDomLoad: function()
    {
        $('activesyncadmin').observe('click', this.clickHandler.bindAsEventListener(this));
    }
}

document.observe('dom:loaded', HordeActiveSyncAdmin.onDomLoad.bind(HordeActiveSyncAdmin));
