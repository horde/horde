/**
 * Managing saved searches.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2014-2015 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */

var ImpSearchesPrefs = {

    // Variables set by PHP script: confirm_delete_filter,
    //     confirm_delete_vfolder, mailboxids

    clickHandler: function(e)
    {
        var elt = e.element(),
            cnames = $w(elt.className);

        if (cnames.indexOf('filterdelete') !== -1) {
            if (window.confirm(this.confirm_delete_filter)) {
                this._sendData('delete', elt.up().previous('.enabled').down('INPUT').readAttribute('name'));
            }
            e.memo.stop();
        } else if (cnames.indexOf('vfolderdelete') !== -1) {
            if (window.confirm(this.confirm_delete_vfolder)) {
                this._sendData('delete', elt.up().previous('.enabled').down('INPUT').readAttribute('name'));
            }
            e.memo.stop();
        }
    },

    _sendData: function(a, d)
    {
        $('searches_action').setValue(a);
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
