/**
 * Provides the javascript for the manager.php script.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

var Gollem = {

    toggleRow: function()
    {
        $$('table.striped tr').each(function(tr) {
            var td = tr.select('TD');
            tr.observe('mouseover', td.invoke.bind(td, 'addClassName', 'selected'));
            tr.observe('mouseout', td.invoke.bind(td, 'removeClassName', 'selected'));
        });
    },

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
                $('attributes').show();
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

    changeDirectory: function(elt)
    {
        this._prepPopup('changeDirectory', elt);
        $('cdfrm_fname').focus();
    },

    createFolder: function(elt)
    {
        this._prepPopup('createFolder', elt);
        $('createfrm_fname').focus();
    },

    _prepPopup: function(elt, elt2)
    {
        this.getChecked().each(function(e) {
            e.checked = false;
        });

        $(elt).clonePosition(elt2, { setWidth: false, setHeight: false, offsetTop: elt2.getHeight() }).show();
    },

    renameItems: function()
    {
        var c = this.getChecked();
        if (c.size()) {
            c[0].checked = false;
            $('rename').show();
            $('renamefrm_oldname').setValue(c[0].value);
            $('renamefrm_newname').setValue(c[0].value).focus();
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

    toggleSelection: function()
    {
        var e = this.getElements(),
            checked = (this.getChecked().size() != e.length);
        e.each(function(f) {
            f.checked = checked;
        });
    },

    createFolderOK: function()
    {
        $('createFolder').hide();
        if ($F('createfrm_fname')) {
            $('new_folder').setValue($F('createfrm_fname'));
            $('actionID').setValue('create_folder');
            $('manager').submit();
        }
    },

    createFolderKeyCheck: function(e)
    {
        switch (e.keyCode) {
        case Event.KEY_ESC:
            this.createFolderCancel();
            e.stop();
            break;

        case EVENT.KEY_RETURN:
            this.createFolderOK();
            e.stop();
            break;
        }
    },

    createFolderCancel: function()
    {
        $('createFolder').hide();
        $('createfrm').reset();
    },

    chmodCancel: function()
    {
        $('attributes').hide();
        $('chmodfrm').reset();
    },

    chmodSave: function()
    {
        var all = group = owner = 0;

        $('chmodfrm').getElements().each(function(e) {
            if (e.name == "owner[]" && e.checked) {
                owner |= e.value;
            } else if (e.name == "group[]" && e.checked) {
                group |= e.value;
            } else if (e.name == "all[]" && e.checked) {
                all |= e.value;
            }
        });

        $('attributes').hide();

        $('chmod').setValue("0" + owner + "" + group + "" + all);
        $('actionID').setValue('chmod_modify');
        $('manager').submit();
    },

    renameOK: function()
    {
        var c = this.getChecked(),
            newname = $F('renamefrm_newname'),
            newNames = $F('new_names'),
            oldname = $F('renamefrm_oldname'),
            oldNames = $F('old_names');

        if (newname && newname != oldname) {
            newNames += "|" + newname;
            oldNames += "|" + oldname;
        }

        if (newNames.startsWith("|")) {
            newNames = newNames.substring(1);
        }
        if (oldNames.startsWith("|")) {
            oldNames = oldNames.substring(1);
        }

        $('new_names').setValue(newNames);
        $('old_names').setValue(oldNames);

        if (c.size()) {
            c[0].checked = false;
            found = true;
            $('rename').show();
            $F(c[0]).focus();
        } else {
            $('actionID').setValue('rename_items');
            $('manager').submit();
        }

        return false;
    },

    renameCancel: function()
    {
        $('new_names', 'old_names').invoke('setValue', '');
        $('rename').hide();
    },

    renameKeyCheck: function(e)
    {
        switch (e.keyCode) {
        case Event.KEY_ESC:
            this.renameCancel();
            e.stop();
            break;

        case EVENT.KEY_RETURN:
            this.renameOK();
            e.stop();
            break;
        }
    },

    changeDirectoryOK: function()
    {
        $('changeDirectory').hide();
        if ($F('cdfrm_fname')) {
            $('dir').setValue($F('cdfrm_fname'));
            $('manager').submit();
        }
    },

    changeDirectoryKeyCheck: function(e)
    {
        switch (e.keyCode) {
        case Event.KEY_ESC:
            this.changeDirectoryCancel();
            e.stop();
            break;

        case EVENT.KEY_RETURN:
            this.changeDirectoryOK();
            e.stop();
            break;
        }
    },

    changeDirectoryCancel: function()
    {
        $('changeDirectory').hide();
        $('cdfrm').reset();
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

    applyFilter: function()
    {
        $('manager').submit();
    },

    clearFilter: function()
    {
        $('filter').setValue('');
        this.applyFilter();
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

    doPrefsUpdate: function(column, sortDown)
    {
        try {
            new Ajax.Request(GollemVar.URI_AJAX + 'setPrefValue', { parameters: { pref: 'sortby', value: column.substring(1) } });
            new Ajax.Request(GollemVar.URI_AJAX + 'setPrefValue', { parameters: { pref: 'sortdir', value: sortDown } });
        } catch (e) {}
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
            case 'cdfrmcancel':
                this.changeDirectoryCancel();
                return;

            case 'cdfrmok':
                this.changeDirectoryOK();
                return;

            case 'changefolder':
                this.changeDirectory(elt);
                e.stop();
                return;

            case 'checkall':
                this.toggleSelection();
                break;

            case 'chmodcancel':
                this.chmodCancel();
                break;

            case 'chmodsave':
                this.chmodSave();
                break;

            case 'createcancel':
                this.createFolderCancel();
                break;

            case 'createfolder':
                this.createFolder(elt);
                e.stop();
                return;

            case 'createok':
                this.createFolderOK();
                break;

            case 'filterapply':
                this.applyFilter();
                break;

            case 'filterclear':
                this.clearFilter();
                break;

            case 'renamecancel':
                this.renameCancel();
                break;

            case 'renamesave':
                this.renameOK();
                break;

            case 'uploadfile':
                this.uploadFile();
                break;
            }

            elt = elt.up();
        }
    },

    onDomLoad: function()
    {
        var tmp;

        this.toggleRow()

        if (tmp = $('renamefrm_newname')) {
            tmp.observe('keypress', this.renameKeyCheck.bindAsEventListener(this));
        }

        $('createfrm_fname').observe('keypress', this.createFolderKeyCheck.bindAsEventListener(this));
        $('cdfrm_fname').observe('keypress', this.changeDirectoryKeyCheck.bindAsEventListener(this));

        $('createfrm', 'cdfrm').invoke('observe', 'submit', Event.stop);

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

function table_sortCallback(tableId, column, sortDown)
{
    if (Gollem.prefs_update_timeout) {
        window.clearTimeout(Gollem.prefs_update_timeout);
    }
    Gollem.prefs_update_timeout = Gollem.doPrefsUpdate.bind(this, column, sortDown).delay(0.3);
}

document.observe('dom:loaded', Gollem.onDomLoad.bind(Gollem));
document.observe('click', Gollem.clickHandler.bindAsEventListener(Gollem));
