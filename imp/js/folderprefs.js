/**
 * Provides the javascript for managing folders.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

var ImpFolderPrefs = {

    // Variables defined by other code: folders, origtext, sentmail

    newFolderName: function(e)
    {
        var folder, tmp,
            f = e.element(),
            id = f.identify(),
            txt = this.folders.get(id),
            newfolder = $(id + '_new'),
            sel = $(f[f.selectedIndex]);

        if (sel.hasClassName('flistCreate') && !newfolder) {
            folder = window.prompt(txt, '');
            if (!folder.empty()) {
                if (!newfolder) {
                    newfolder = new Element('INPUT', { id: id + '_new', name: id + '_new', type: 'hidden' });
                    f.insert({ after: newfolder });
                }
                newfolder.setValue(folder);
                this.origtext = sel.text;
                sel.update(sel.text + ' [' + folder + ']');
            }
        }
    },

    changeIdentity: function(e)
    {
        switch (e.memo.pref) {
        case 'sentmailselect':
            $('sent_mail_folder').setValue(this.sentmail[e.memo.i]);
            if (this.origtext) {
                $('sent_mail_folder_new').remove();
                $('sent_mail_folder').down('.flistCreate').update(this.origtext);
                this.origtext = null;
            }
            break;
        }
    },

    onDomLoad: function()
    {
        this.folders = $H(this.folders);

        this.folders.keys().each(function(f) {
            $(f).observe('change', this.newFolderName.bindAsEventListener(this));
        }, this);

        document.observe('HordeIdentitySelect:change', this.changeIdentity.bindAsEventListener(this));
    }

};

document.observe('dom:loaded', ImpFolderPrefs.onDomLoad.bind(ImpFolderPrefs));
