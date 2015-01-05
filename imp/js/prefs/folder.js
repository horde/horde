/**
 * Managing the folder view preferences.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2014-2015 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */

var ImpFolderPrefs = {

    // Variables defined by other code: origtext, sentmail
    mboxes: {},

    newMboxName: function(e, f)
    {
        var mbox,
            id = f.identify(),
            txt = this.mboxes.get(id),
            newmbox = $(id + '_new'),
            sel = $(f[f.selectedIndex]);

        if (sel.hasClassName('flistCreate') && !newmbox) {
            mbox = window.prompt(txt, '');
            if (mbox && !mbox.empty()) {
                if (!newmbox) {
                    newmbox = new Element('INPUT', { id: id + '_new', name: id + '_new', type: 'hidden' });
                    f.insert({ after: newmbox });
                }
                newmbox.setValue(mbox);
                this.origtext = sel.text;
                sel.update(sel.text + ' [' + mbox + ']');
            }
        }
    },

    changeIdentity: function(e)
    {
        switch (e.memo.pref) {
        case 'sentmailselect':
            $('sent_mail').setValue(this.sentmail[e.memo.i]);
            if (this.origtext) {
                $('sent_mail_new').remove();
                $('sent_mail').down('.flistCreate').update(this.origtext);
                delete this.origtext;
            }
            break;
        }
    },

    onDomLoad: function()
    {
        /* Disable selection of containers. */
        $('prefs')
            .down('SELECT.folderPrefSelect')
            .select('OPTION[value=""]:not(.flistCreate)')
            .invoke('writeAttribute', { disabled: true });
    }

};

document.observe('dom:loaded', ImpFolderPrefs.onDomLoad.bind(ImpFolderPrefs));
document.observe('HordeIdentitySelect:change', ImpFolderPrefs.changeIdentity.bindAsEventListener(ImpFolderPrefs));
document.on('select', '.folderPrefSelect', ImpFolderPrefs.newMboxName.bind(ImpFolderPrefs));
