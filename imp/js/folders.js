/**
 * Provides the javascript for the compose.php script (standard view).
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

var ImpFolders = {

    // The following variables are defined in folders.php:
    //   displayNames, folders_url

    getChecked: function()
    {
        return this.getFolders().findAll(function(e) {
            return e.checked;
        });
    },

    getFolders: function()
    {
        return $('fmanager').getInputs(null, 'folder_list[]');
    },

    selectedFoldersDisplay: function()
    {
        var folder = 0, sel = "";

        this.getFolders().each(function(e) {
            if (e.checked) {
                sel += this.displayNames[folder] + "\n";
            }
            ++folder;
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
        case 'create_folder':
            this.createMailbox();
            break;

        case 'rebuild_tree':
            this.submitAction(action);
            break;

        default:
            if (!this.getChecked().size()) {
                if (action != '') {
                    alert(IMP.text.folders_select);
                }
                break;
            }

            switch (action) {
            case 'rename_folder':
                this.renameMailbox();
                break;

            case 'download_folder':
            case 'download_folder_zip':
                this.downloadMailbox(action);
                break;

            case 'import_mbox':
                if (this.getChecked().length > 1) {
                    alert(IMP.text.folders_oneselect);
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
            window.alert(IMP.text.folders_oneselect);
            return;
        }

        mbox = (count == 1)
            ? window.prompt(IMP.text.folders_subfolder1 + ' ' + this.selectedFoldersDisplay() + ".\n" + IMP.text.folders_subfolder2 + "\n", '')
            : window.prompt(IMP.text.folders_toplevel, '');

        if (mbox) {
            $('new_mailbox').setValue(mbox);
            this.submitAction('create_folder');
        }
    },

    downloadMailbox: function(actionid)
    {
        if (window.confirm(IMP.text.folders_download1 + "\n" + this.selectedFoldersDisplay() + "\n" + IMP.text.folders_download2)) {
            this.submitAction(actionid);
        }
    },

    renameMailbox: function()
    {
        var newnames = '', oldnames = '', j = 0;

        this.getFolders().each(function(f) {
            if (f.checked) {
                if (IMP.conf.fixed_folders.indexOf(this.displayNames[j]) != -1) {
                    window.alert(IMP.text.folders_no_rename + ' ' + this.displayNames[j]);
                } else {
                    var tmp = window.prompt(IMP.text.folders_rename1 + ' ' + this.displayNames[j] + "\n" + IMP.text.folders_rename2, this.fullNames[j] ? this.fullNames[j] : this.displayNames[j]);
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
            this.submitAction('rename_folder');
        }
    },

    toggleSelection: function()
    {
        var count = this.getChecked().size(), folders = this.getFolders(),
            checked = (count != folders.size());
        folders.each(function(f) {
            f.checked = checked;
        });
    },

    toggleSubfolder: function(e, type)
    {
        new Ajax.Request(this.ajax + type + 'Mailboxes', { parameters: { encoded: 1, mboxes: Object.toJSON([ e.memo ]) } });
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
        if (e.isRightClick()) {
            return;
        }

        var elt = e.element();

        while (Object.isElement(elt)) {
            switch (elt.readAttribute('id')) {
            case 'btn_import':
                this.submitAction('import_mbox');
                break;

            case 'btn_return':
                document.location.href = this.folders_url;
                break;

            case 'checkAll0':
            case 'checkAll1':
                this.toggleSelection();
                break;
            }

            elt = elt.up();
        }
    },

    onDomLoad: function()
    {
        if (this.mbox_expand) {
            $('fmanager').observe('Horde_Tree:collapse', this.toggleSubfolder.bindAsEventListener(this, 'collapse'));
            $('fmanager').observe('Horde_Tree:expand', this.toggleSubfolder.bindAsEventListener(this, 'expand'));
        }
    }

};

document.observe('change', ImpFolders.changeHandler.bind(ImpFolders));
document.observe('click', ImpFolders.clickHandler.bind(ImpFolders));
document.observe('dom:loaded', ImpFolders.onDomLoad.bind(ImpFolders));
