/**
 * Provides the javascript for managing saved searches.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

var ImpSearchesPrefs = {
    // Variables set by PHP script: confirm_delete_filter,
    //     confirm_delete_vfolder, mailboxids

    clickHandler: function(e)
    {
        var elt = e.element();

        if (elt.hasClassName('filterdelete')) {
            if (window.confirm(this.confirm_delete_filter)) {
                this._sendData('delete', elt.up().previous('.enabled').down('INPUT').readAttribute('name'));
            }
            e.memo.stop();
        } else if (elt.hasClassName('vfolderdelete')) {
            if (window.confirm(this.confirm_delete_vfolder)) {
                this._sendData('delete', elt.up().previous('.enabled').down('INPUT').readAttribute('name'));
            }
            e.memo.stop();
        } else if (elt.match('SPAN.vfolderenabled')) {
            e.memo.hordecore_stop = true;
            window.parent.DimpBase.go('mbox', this.mailboxids[elt.up().next('.enabled').down('INPUT').readAttribute('name')]);
        }
    },

    _sendData: function(a, d)
    {
        $('searches_action').setValue(a)
        $('searches_data').setValue(d);
        $('prefs').submit();
    },

    onDomLoad: function()
    {
        HordeCore.initHandler('click');
    }

};

document.observe('dom:loaded', ImpSearchesPrefs.onDomLoad.bindAsEventListener(ImpSearchesPrefs));
document.observe('HordeCore:click', ImpSearchesPrefs.clickHandler.bindAsEventListener(ImpSearchesPrefs));
