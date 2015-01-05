/**
 * Provides the javascript for administering ActiveSync partner devices.
 *
 * @copyright  2014-2015 Horde LLC
 * @license    LGPL-2 (http://www.horde.org/licenses/lgpl)
 */

var HordeActiveSyncAdmin = {

    // Set in admin/activesync.php: devices

    clickHandler: function(e)
    {
        var id = e.element().readAttribute('id');

        switch (id) {
        case 'reset':
            $('actionID').setValue('reset');
            $('activesyncadmin').submit();
            e.stop();
            break;

        case 'search':
            $('actionID').setValue('search');
            $('activesyncadmin').submit();
            e.stop();
            break;

        default:
            // Save any existing search data.
            if (id) {
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
                } else if (id.startsWith('block_')) {
                    $('deviceID').setValue(this.devices[id.substr(6)].id);
                    $('actionID').setValue('block');
                    $('activesyncadmin').submit();
                    e.stop();
                } else if (id.startsWith('unblock_')) {
                    $('deviceID').setValue(this.devices[id.substr(8)].id);
                    $('actionID').setValue('unblock');
                    $('activesyncadmin').submit();
                    e.stop();
                }
            }
            break;
        }
    },

    onDomLoad: function()
    {
        $('activesyncadmin').observe('click', this.clickHandler.bindAsEventListener(this));
    }
};

document.observe('dom:loaded', HordeActiveSyncAdmin.onDomLoad.bind(HordeActiveSyncAdmin));
