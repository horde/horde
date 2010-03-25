/**
 * Provides the javascript for managing remote servers.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

var HordeRpcPrefs = {

    // Variables defaulting to null: servers

    serverChoice: function(idx)
    {
        switch (idx) {
        case '-1':
            $('rpc_create').show();
            $('rpc_change', 'rpc_delete').invoke('hide');
            $('url', 'user', 'passwd').invoke('setValue', '');
            break;

        default:
            $('rpc_create').hide();
            $('rpc_change', 'rpc_delete').invoke('show');
            $('url').setValue(this.servers[idx][0]);
            $('user').setValue(this.servers[idx][1]);
            $('passwd').setValue('');
            break;
        }
    },

    onDomLoad: function()
    {
        $('server').observe('change', function() {
            this.serverChoice($('server').selectedIndex);
        }.bind(this));
        $('rpc_reset').observe('click', function(e) {
            this.serverChoice('-1');
            e.stop();
        }.bindAsEventListener(this));

        if (!this.servers.size()) {
            $('server').up().hide();
        }

        this.serverChoice('-1');
    }

};

document.observe('dom:loaded', HordeRpcPrefs.onDomLoad.bind(HordeRpcPrefs));
