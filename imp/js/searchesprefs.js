/**
 * Provides the javascript for managing saved searches.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

var ImpSearchesPrefs = {
    // Variables set by PHP script: confirm_delete_filter,
    //     confirm_delete_vfolder, mailboxids

    clickHandler: function(e)
    {
        if (e.isRightClick()) {
            return;
        }

        var elt = e.element();

        while (Object.isElement(elt)) {
            if (elt.hasClassName('filterdelete')) {
                if (window.confirm(this.confirm_delete_filter)) {
                    this._sendData('delete', elt.up().previous('.enabled').down('INPUT').readAttribute('name'));
                }
                e.stop();
                return;
            } else if (elt.hasClassName('vfolderdelete')) {
                if (window.confirm(this.confirm_delete_vfolder)) {
                    this._sendData('delete', elt.up().previous('.enabled').down('INPUT').readAttribute('name'));
                }
                e.stop();
                return;
            } else if (elt.match('SPAN.vfolderenabled')) {
                e.stop();
                window.parent.DimpBase.go('mbox', this.mailboxids[elt.up().next('.enabled').down('INPUT').readAttribute('name')]);
                return;
            }

            elt = elt.up();
        }
    },

    _sendData: function(a, d)
    {
        $('searches_action').setValue(a)
        $('searches_data').setValue(d);
        $('prefs').submit();
    }

};

document.observe('click', ImpSearchesPrefs.clickHandler.bindAsEventListener(ImpSearchesPrefs));
