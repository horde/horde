/**
 * Provides the javascript for the manager.php script.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

var Gollem = {

    getChecked: function()
    {
        return this.getElements().findAll(function(e) {
            return e.checked;
        });
    },

    getElements: function()
    {
        return $('manager').getInputs(null, 'items[]');
    },

    getSelected: function()
    {
        return this.getChecked().pluck('value').join("\n");
    },

    toggleSelection: function()
    {
        var e = this.getElements(),
            checked = (this.getChecked().size() != e.length);
        e.each(function(f) {
            f.checked = checked;
        });
    },

    getItemsArray: function()
    {
        var i = 0,
            it = $('manager').getInputs(null, 'itemTypes[]');

        return this.getElements().collect(function(m) {
            return { c: m.checked, v: m.value, t: it[i++].value };
        });
    },

    getSelectedFoldersList: function()
    {
        return this.getItemsArray().collect(function(i) {
            return (i.c && i.t == '**dir') ? i.v : null;
        }).compact().join("\n");
    },

    chooseAction: function(i)
    {
        var action = $F('action' + i);

        switch (action) {
        case 'paste_items':
            $('actionID').setValue('paste_items');
            $('manager').submit();
            break;

        default:
            if (!this.getChecked().size()) {
                alert(GollemText.select_item);
                break;
            }
            switch (action) {
            case 'rename_items':
                this.renameItems();
                break;

            case 'delete_items':
                this.deleteItems();
                break;

            case 'chmod_modify':
                HordeDialog.display({
                    form: $('attributes').clone(true).show(),
                    form_id: 'chmodfrm',
                    form_opts: { action: GollemVar.actionUrl },
                    header: GollemText.permissions
                });
                break;

            case 'cut_items':
                $('actionID').setValue('cut_items');
                $('manager').submit();
                break;

            case 'copy_items':
                $('actionID').setValue('copy_items');
                $('manager').submit();
                break;
            }
            break;
        }
    },

    _clearChecks: function()
    {
        this.getChecked().each(function(e) {
            e.checked = false;
        });
    },

    renameItems: function()
    {
        var c = this.getChecked();
        if (c.size()) {
            c[0].checked = false;
            $('renamefrm_oldname').setValue(c[0].value);
            HordeDialog.display({
                form_id: 'renamefrm',
                input_val: c[0].value,
                text: GollemText.rename
            });
        }
    },

    deleteItems: function()
    {
        var cont = true, sf;

        if (window.confirm(GollemText.delete_confirm_1 + '\n' + this.getSelected() + '\n' + GollemText.delete_confirm_2)) {
            if (warn_recursive) {
                sf = this.getSelectedFoldersList();
                if (!sf.empty() &&
                    !window.confirm(GollemText.delete_recurs_1 + '\n' + sf + '\n' + GollemText.delete_recurs_2)) {
                    cont = false;
                }
            }
        } else {
            cont = false;
        }

        if (cont) {
            $('actionID').setValue('delete_items');
            $('manager').submit();
        }
    },

    createFolderOK: function()
    {
        if ($F('dialog_input')) {
            $('new_folder').setValue($F('dialog_input'));
            $('actionID').setValue('create_folder');
            $('manager').submit();
        }
    },

    chmodOK: function()
    {
        var all = group = owner = 0;

        $('chmodfrm').getElements().each(function(e) {
            if (e.name == 'owner[]' && e.checked) {
                owner |= e.value;
            } else if (e.name == 'group[]' && e.checked) {
                group |= e.value;
            } else if (e.name == 'all[]' && e.checked) {
                all |= e.value;
            }
        });

        $('chmod').setValue('0' + owner + '' + group + '' + all);
        $('actionID').setValue('chmod_modify');
        $('manager').submit();
    },

    renameOK: function()
    {
        var c = this.getChecked(),
            newname = $F('dialog_input'),
            newNames = $F('new_names'),
            oldname = $F('renamefrm_oldname'),
            oldNames = $F('old_names');

        if (newname && newname != oldname) {
            newNames += '|' + newname;
            oldNames += '|' + oldname;
        }

        if (newNames.startsWith('|')) {
            newNames = newNames.substring(1);
        }
        if (oldNames.startsWith('|')) {
            oldNames = oldNames.substring(1);
        }

        $('new_names').setValue(newNames);
        $('old_names').setValue(oldNames);

        if (c.size()) {
            this.renameItems.defer();
        } else {
            $('actionID').setValue('rename_items');
            $('manager').submit();
        }
    },

    changeDirectoryOK: function()
    {
        if ($F('dialog_input')) {
            $('dir').setValue($F('dialog_input'));
            $('manager').submit();
        }
    },

    applyFilter: function()
    {
        $('manager').submit();
    },

    clearFilter: function()
    {
        $('filter').setValue('');
        this.applyFilter();
    },

    uploadFields: function()
    {
        return $('manager').getInputs('file').collect(function(m) {
            return (m.name.substr(0, 12) == 'file_upload_') ? m : null;
        }).compact();
    },

    uploadFile: function()
    {
        if (this.uploadsExist()) {
            $('actionID').setValue('upload_file');
            $('manager').submit();
        }
    },

    uploadsExist: function()
    {
        if (GollemVar.empty_input ||
            this.uploadFields().find(function(f) { return $F(f); })) {
            return true;
        }
        alert(GollemText.specify_upload);
        $('file_upload_1').focus();
        return false;
    },

    uploadChanged: function()
    {
        if (GollemVar.empty_input) {
            return;
        }

        var file, lastRow,
            fields = this.uploadFields(),
            usedFields = fields.findAll(function(f) { return $F(f).length; }).length;

        if (usedFields == fields.length) {
            lastRow = $('upload_row_' + usedFields);
            if (lastRow) {
                file = new Element('INPUT', { type: 'file', name: 'file_upload_' + (usedFields + 1), size: 25 });
                lastRow.insert({ after:
                    new Element('DIV', { id: 'upload_row_' + (usedFields + 1) }).insert(
                        new Element('STRONG').insert(GollemText.file + ' ' + (usedFields + 1) + ':')
                    ).insert(' ').insert(file)
                });
                file.observe('change', this.uploadChanged.bind(this));
            }
        }
    },

    clickHandler: function(e)
    {
        if (e.isRightClick()) {
            return;
        }

        var id, tmp,
            elt = e.element();

        while (Object.isElement(elt)) {
            id = elt.readAttribute('id');

            switch (id) {
            case 'changefolder':
                this._clearChecks();
                HordeDialog.display({
                    form_id: 'cdfrm',
                    text: GollemText.change_directory
                });
                e.stop();
                return;

            case 'checkall':
                this.toggleSelection();
                break;

            case 'createfolder':
                this._clearChecks();
                HordeDialog.display({
                    form_id: 'createfrm',
                    text: GollemText.create_folder
                });
                e.stop();
                return;

            case 'filterapply':
                this.applyFilter();
                break;

            case 'filterclear':
                this.clearFilter();
                break;

            case 'uploadfile':
                this.uploadFile();
                break;
            }

            elt = elt.up();
        }
    },

    okHandler: function(e)
    {
        switch (e.element().identify()) {
        case 'cdfrm':
            Gollem.changeDirectoryOK();
            break;

        case 'chmodfrm':
            Gollem.chmodOK();
            break;

        case 'createfrm':
            Gollem.createFolderOK();
            break;

        case 'renamefrm':
            Gollem.renameOK();
            break;
        }
    },

    closeHandler: function(e)
    {
        $('new_names', 'old_names').invoke('setValue', '');
    },

    onDomLoad: function()
    {
        var tmp;

        // Observe actual event since IE does not bubble change events.
        if (tmp = $('action1')) {
            tmp.observe('change', function() {
                this.chooseAction(1);
                $('action1').selectedIndex = 0;
            }.bind(this));
        }

        if (tmp = $('file_upload_1')) {
            tmp.observe('change', this.uploadChanged.bind(this));
        }
    }

};

document.observe('dom:loaded', Gollem.onDomLoad.bind(Gollem));
document.observe('click', Gollem.clickHandler.bindAsEventListener(Gollem));
document.observe('HordeDialog:onClick', Gollem.okHandler.bindAsEventListener(Gollem));
document.observe('HordeDialog:close', Gollem.closeHandler.bindAsEventListener(Gollem));
