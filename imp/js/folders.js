/**
 * Provides the javascript for the folders tree view (standard view).
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

var ImpFolders = {

    // The following variables are defined in folders.php:
    //   displayNames, folders_url, text

    getChecked: function()
    {
        return this.getMboxes().findAll(function(e) {
            return e.checked;
        });
    },

    getMboxes: function()
    {
        return $('fmanager').getInputs(null, 'mbox_list[]');
    },

    selectedMboxesDisplay: function()
    {
        var mbox = 0, sel = "";

        this.getMboxes().each(function(e) {
            if (e.checked) {
                sel += this.displayNames[mbox] + "\n";
            }
            ++mbox;
        }, this);

        return sel.strip();
    },

    chooseAction: function(e)
    {
        var id = (e.element().readAttribute('id') == 'action_choose0') ? 0 : 1,
            a = $('action_choose' + id),
            action = $F(a);
        a.selectedIndex = 0;

        switch (action) {
        case 'create_mbox':
            this.createMailbox();
            break;

        case 'rebuild_tree':
            this.submitAction(action);
            break;

        default:
            if (!this.getChecked().size()) {
                if (action != '') {
                    alert(this.text.select);
                }
                break;
            }

            switch (action) {
            case 'rename_mbox':
                this.renameMailbox();
                break;

            case 'download_mbox':
            case 'download_mbox_zip':
                this.downloadMailbox(action);
                break;

            case 'import_mbox':
                if (this.getChecked().length > 1) {
                    alert(this.text.oneselect);
                } else {
                    this.submitAction(action);
                }
                break;

            default:
                this.submitAction(action);
                break;
            }
            break;
        }
    },

    submitAction: function(a)
    {
        $('actionID').setValue(a);
        $('fmanager').submit();
    },

    createMailbox: function()
    {
        var count = this.getChecked().size(), mbox;
        if (count > 1) {
            window.alert(this.text.oneselect);
            return;
        }

        mbox = (count == 1)
            ? window.prompt(this.text.subfolder1 + ' ' + this.selectedMboxesDisplay() + ".\n" + this.text.subfolder2 + "\n", '')
            : window.prompt(this.text.toplevel, '');

        if (mbox) {
            $('new_mailbox').setValue(mbox);
            this.submitAction('create_mbox');
        }
    },

    downloadMailbox: function(actionid)
    {
        if (window.confirm(this.text.download1 + "\n" + this.selectedMboxesDisplay() + "\n" + this.text.download2)) {
            this.submitAction(actionid);
        }
    },

    renameMailbox: function()
    {
        var newnames = '', oldnames = '', j = 0;

        this.getMboxes().each(function(f) {
            if (f.checked) {
                if (IMP.conf.fixed_mboxes.indexOf(this.displayNames[j]) != -1) {
                    window.alert(this.text.no_rename + ' ' + this.displayNames[j]);
                } else {
                    var tmp = window.prompt(this.text.rename1 + ' ' + this.displayNames[j] + "\n" + this.text.rename2, this.fullNames[j] ? this.fullNames[j] : this.displayNames[j]);
                    if (tmp) {
                        newnames += tmp + "\n";
                        oldnames += f.value + "\n";
                    }
                }
            }
            ++j;
        }, this);

        if (newnames) {
            $('new_names').setValue(newnames.strip());
            $('old_names').setValue(oldnames.strip());
            this.submitAction('rename_mbox');
        }
    },

    toggleSelection: function()
    {
        var count = this.getChecked().size(),
            mboxes = this.getMboxes(),
            checked = (count != mboxes.size());
        mboxes.each(function(f) {
            f.checked = checked;
        });
    },

    toggleSubfolder: function(e, type)
    {
        new Ajax.Request(this.ajax + type + 'Mailboxes', { parameters: { mboxes: Object.toJSON([ e.memo ]) } });
    },

    changeHandler: function(e)
    {
        switch (e.element().readAttribute('id')) {
        case 'action_choose0':
        case 'action_choose1':
            this.chooseAction(e);
            break;
        }
    },

    clickHandler: function(e)
    {
        switch (e.element().readAttribute('id')) {
        case 'btn_import':
            this.submitAction('import_mbox');
            break;

        case 'btn_return':
            document.location.href = this.folders_url;
            e.memo.hordecore_stop = true;
            break;

        case 'checkAll0':
        case 'checkAll1':
            this.toggleSelection();
            break;
        }
    },

    onDomLoad: function()
    {
        HordeCore.initHandler('click');

        // Observe actual form element since IE does not bubble change events.
        $('action_choose0', 'action_choose1').compact().invoke('observe', 'change', this.changeHandler.bindAsEventListener(this));

        if (this.mbox_expand) {
            $('fmanager').observe('Horde_Tree:collapse', this.toggleSubfolder.bindAsEventListener(this, 'collapse'));
            $('fmanager').observe('Horde_Tree:expand', this.toggleSubfolder.bindAsEventListener(this, 'expand'));
        }
    }

};

document.observe('dom:loaded', ImpFolders.onDomLoad.bind(ImpFolders));
document.observe('HordeCore:click', ImpFolders.clickHandler.bind(ImpFolders));
