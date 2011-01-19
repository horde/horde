/**
 * Provides the javascript for the search.php script (advanced view).
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

var ImpSearch = {
    // The following variables are defined in search.php:
    //   data, i_criteria, i_folders, i_recent, text
    criteria: {},
    folders: $H(),
    saved_searches: {},

    updateRecentSearches: function(searches)
    {
        var fragment = document.createDocumentFragment(),
            node = new Element('OPTION');

        $('recent_searches_div').show().next().show();

        this.saved_searches = $H(searches);
        this.saved_searches.each(function(s) {
            fragment.appendChild($(node.clone(false)).writeAttribute({ value: s.key.escapeHTML() }).update(s.value.l.escapeHTML()));
        }, this);

        $('recent_searches').appendChild(fragment);
    },

    // Criteria actions

    showOr: function(show)
    {
        var or = $('search_criteria_add').down('[value="or"]');
        if (show) {
            or.show().next().show();
        } else {
            or.hide().next().hide();
        }
    },

    updateCriteria: function(criteria)
    {
        this.resetCriteria();

        criteria.each(function(c) {
            var crit = c.criteria;

            switch (c.element) {
            case 'IMP_Search_Element_Bulk':
                this.insertFilter('bulk', crit);
                break;

            case 'IMP_Search_Element_Date':
                this.insertDate(this.data.constants.index(crit.t), new Date(crit.d));
                break;

            case 'IMP_Search_Element_Flag':
                this.insertFlag(crit.f, !crit.s);
                break;

            case 'IMP_Search_Element_Header':
                switch (crit.h) {
                case 'from':
                case 'to':
                case 'cc':
                case 'bcc':
                case 'subject':
                    this.insertText(crit.h, crit.t, crit.n);
                    break;

                default:
                    this.insertCustomHdr({ h: crit.h.capitalize(), s: crit.t }, crit.n);
                    break;
                }
                break;

            case 'IMP_Search_Element_Mailinglist':
                this.insertFilter('mailinglist', crit);
                break;

            case 'IMP_Search_Element_Or':
                this.insertOr();
                break;

            case 'IMP_Search_Element_Personal':
                this.insertFilter('personal', crit);
                break;

            case 'IMP_Search_Element_Recipient':
                this.insertText('recip', crit.t, crit.n);
                break;

            case 'IMP_Search_Element_Size':
                this.insertSize(crit.s ? 'size_larger' : 'size_smaller', crit.l);
                break;

            case 'IMP_Search_Element_Text':
                this.insertText(crit.b ? 'body' : 'text', crit.t, crit.n);
                break;

            case 'IMP_Search_Element_Within':
                this.insertWithin(crit.o ? 'older' : 'younger', { l: this.data.constants.index(crit.t), v: crit.v });
                break;
            }
        }, this);

        if ($('search_criteria').childElements().size()) {
            $('no_search_criteria', 'search_criteria').invoke('toggle');
            this.showOr(true);
        }
    },

    getCriteriaLabel: function(id)
    {
        return $('search_criteria_add').down('[value="' + RegExp.escape(id) + '"]').getText() + ': ';
    },

    deleteCriteria: function(div)
    {
        var first, keys;

        delete this.criteria[div.identify()];
        div.remove();

        keys = $('search_criteria').childElements().pluck('id');
        if (keys.size()) {
            first = keys.first();

            if (this.criteria[first].t && this.criteria[first].t == 'or') {
                $(first).remove();
                delete this.criteria[first];
                keys = [];
            } else if ($(first).down().hasClassName('join')) {
                $(first).down().remove();
            }
        }

        if (!keys.size()) {
            this.showOr(false);
            $('no_search_criteria', 'search_criteria').invoke('toggle');
        }
    },

    resetCriteria: function()
    {
        var elts = $('search_criteria').childElements();
        if (elts) {
            elts.invoke('remove');
            $('no_search_criteria', 'search_criteria').invoke('toggle');
            this.criteria = {};
            this.showOr(false);
        }
    },

    insertCriteria: function(tds)
    {
        var div = new Element('DIV', { className: 'searchId' }),
            div2 = new Element('DIV', { className: 'searchElement' });

        if ($('search_criteria').childElements().size()) {
            if (this.criteria[$('search_criteria').childElements().last().readAttribute('id')].t != 'or') {
                div.insert(new Element('EM', { className: 'join' }).insert(this.text.and));
            }
        } else {
            $('no_search_criteria', 'search_criteria').invoke('toggle');
            this.showOr(true);
        }

        div.insert(div2);

        tds.each(function(node) {
            div2.insert(node);
        });

        div2.insert(new Element('A', { href: '#', className: 'iconImg searchuiImg searchuiDelete' }));

        $('search_criteria_add').clear();
        $('search_criteria').insert(div);

        return div.identify();
    },

    insertOr: function()
    {
        var div = new Element('DIV').insert(new Element('EM', { className: 'join joinOr' }).insert('--&nbsp;' + this.text.or + '&nbsp;--'));
        $('search_criteria_add').clear();
        $('search_criteria').insert(div);
        this.criteria[div.identify()] = { t: 'or' };
    },

    insertText: function(id, text, not)
    {
        var tmp = [
            new Element('EM').insert(this.getCriteriaLabel(id)),
            new Element('INPUT', { type: 'text', size: 25 }).setValue(text),
            new Element('SPAN', { className: 'notMatch' }).insert(new Element('INPUT', { checked: Boolean(not), className: 'checkbox', type: 'checkbox' })).insert(this.text.not_match)
        ];
        this.criteria[this.insertCriteria(tmp)] = { t: id };
        tmp[1].activate();
    },

    insertCustomHdr: function(text, not)
    {
        text = text || { h: '', s: '' };

        var tmp = [
            new Element('EM').insert(this.text.customhdr),
            new Element('INPUT', { type: 'text', size: 25 }).setValue(text.h),
            new Element('SPAN').insert(new Element('EM').insert(this.text.search_term + ' ')).insert(new Element('INPUT', { type: 'text', size: 25 }).setValue(text.s)),
            new Element('SPAN').insert(new Element('INPUT', { checked: Boolean(not), className: 'checkbox', type: 'checkbox' })).insert(this.text.not_match)
        ];
        this.criteria[this.insertCriteria(tmp)] = { t: 'customhdr' };
        tmp[1].activate();
    },

    insertSize: function(id, size)
    {
        var tmp = [
            new Element('EM').insert(this.getCriteriaLabel(id)),
            // Convert from bytes to KB
            new Element('INPUT', { type: 'text', size: 10 }).setValue(Object.isNumber(size) ? Math.round(size / 1024) : '')
        ];
        this.criteria[this.insertCriteria(tmp)] = { t: id };
        tmp[1].activate();
    },

    insertDate: function(id, data)
    {
        if (!data) {
            data = new Date();
        }

        var tmp = [
                new Element('EM').insert(this.getCriteriaLabel(id)),
                new Element('SPAN').insert(new Element('SPAN')).insert(new Element('A', { href: '#', className: 'calendarPopup', title: this.text.dateselection }).insert(new Element('SPAN', { className: 'iconImg searchuiImg searchuiCalendar' })))
            ];
        this.replaceDate(this.insertCriteria(tmp), id, data);
    },

    replaceDate: function(id, type, d)
    {
        $(id).down('SPAN SPAN').update(this.data.months[d.getMonth()] + ' ' + d.getDate() + ', ' + (d.getYear() + 1900));
        // Need to store date information at all times in criteria, since we
        // have no other way to track this information (there is not form
        // field for this type).
        this.criteria[id] = { t: type, v: d };
    },

    insertWithin: function(id, data)
    {
        data = data || { l: '', v: '' };

        var tmp = [
            new Element('EM').insert(this.getCriteriaLabel(id)),
            new Element('SPAN').insert(new Element('INPUT', { type: 'text', size: 8 }).setValue(data.v)).insert(' ').insert($($('within_criteria').clone(true)).writeAttribute({ id: null }).show().setValue(data.l))
        ];
        this.criteria[this.insertCriteria(tmp)] = { t: id };
        tmp[1].activate();
    },

    insertFilter: function(id, not)
    {
        var tmp = [
            new Element('EM').insert(this.getCriteriaLabel(id)),
            new Element('SPAN').insert(new Element('INPUT', { checked: Boolean(not), className: 'checkbox', type: 'checkbox' })).insert(this.text.not_match)
        ];
        this.criteria[this.insertCriteria(tmp)] = { t: id };
    },

    insertFlag: function(id, not)
    {
        var tmp = [
            new Element('EM').insert(this.text.flag),
            this.getCriteriaLabel(id).slice(0, -2),
            new Element('SPAN').insert(new Element('INPUT', { checked: Boolean(not), className: 'checkbox', type: 'checkbox' })).insert(this.text.not_match)
        ];
        this.criteria[this.insertCriteria(tmp)] = { t: id };
    },

    // Folder actions

    // folders = (object) m: mailboxes, s: subfolders
    updateFolders: function(folders)
    {
        this.resetFolders();
        folders.m.each(function(f) {
            this.insertFolder(f, false);
        }, this);
        folders.s.each(function(f) {
            this.insertFolder(f, true);
        }, this);
    },

    deleteFolder: function(div)
    {
        var first, keys,
            id = div.identify()

        this.disableFolder(false, this.folders.get(id));
        this.folders.unset(id);
        div.remove();

        keys = $('search_folders').childElements().pluck('id');

        if (keys.size()) {
            first = keys.first();
            if ($(first).down().hasClassName('join')) {
                $(first).down().remove();
            }
        }

        if (!keys.size()) {
            $('no_search_folders', 'search_folders').invoke('toggle');
        }
    },

    resetFolders: function()
    {
        elts = $('search_folders').childElements();

        if (elts.size()) {
            this.folders.values().each(this.disableFolder.bind(this, false));
            elts.invoke('remove');
            $('no_search_folders', 'search_folders').invoke('toggle');
            this.folders = $H();
        }
    },

    insertFolder: function(folder, checked)
    {
        var id,
            div = new Element('DIV', { className: 'searchId' }),
            div2 = new Element('DIV', { className: 'searchElement' });

        if ($('search_folders').childElements().size()) {
            div.insert(new Element('EM', { className: 'join' }).insert(this.text.and));
        } else {
            $('no_search_folders', 'search_folders').invoke('toggle');
        }

        div.insert(div2);

        div2.insert(
            new Element('EM').insert(this.getFolderLabel(folder).escapeHTML())
        ).insert(
            new Element('SPAN', { className: 'subfolders' }).insert(new Element('INPUT', { checked: checked, className: 'checkbox', type: 'checkbox' })).insert(this.text.subfolder_search)
        ).insert(
            new Element('A', { href: '#', className: 'iconImg searchuiImg searchuiDelete' })
        );


        this.disableFolder(true, folder);
        $('search_folders_add').clear();
        $('search_folders').insert(div);

        id = div.identify();
        this.folders.set(id, folder);

        return id;
    },

    getFolderLabel: function(folder)
    {
        return this.data.folder_list[folder];
    },

    disableFolder: function(disable, folder)
    {
        $('search_folders_add').down('[value="' + escape(folder) + '"]').writeAttribute({ disabled: disable });
    },

    // Miscellaneous actions

    submit: function()
    {
        var criteria,
            data = [],
            f_out = { mbox: [], subfolder: [] },
            sflist = [];

        if ($F('search_type') && !$('search_label').present()) {
            alert(this.text.need_label);
            return;
        }

        if (!this.folders.size()) {
            alert(this.text.need_folder);
            return;
        }

        criteria = $('search_criteria').childElements().pluck('id');
        if (!criteria.size()) {
            alert(this.text.need_criteria);
            return;
        }

        criteria.each(function(c) {
            var tmp;

            if (this.criteria[c].t == 'or') {
                data.push(this.criteria[c]);
                return;
            }

            switch (this.data.types[this.criteria[c].t]) {
            case 'header':
            case 'text':
                this.criteria[c].n = Number(Boolean($F($(c).down('INPUT[type=checkbox]'))));
                this.criteria[c].v = $F($(c).down('INPUT[type=text]'));
                data.push(this.criteria[c]);
                break;

            case 'customhdr':
                this.criteria[c].v = { h: $F($(c).down('INPUT')), s: $F($(c).down('INPUT', 1)) };
                data.push(this.criteria[c]);
                break;

            case 'size':
                tmp2 = Number($F($(c).down('INPUT')));
                if (!isNaN(tmp2)) {
                    // Convert KB to bytes
                    this.criteria[c].v = tmp2 * 1024;
                    data.push(this.criteria[c]);
                }
                break;

            case 'date':
                data.push(this.criteria[c]);
                break;

            case 'within':
                this.criteria[c].v = { l: $F($(c).down('SELECT')), v: parseInt($F($(c).down('INPUT')), 10) };
                data.push(this.criteria[c]);
                break;

            case 'filter':
                this.criteria[c].n = Number(Boolean($F($(c).down('INPUT[type=checkbox]'))));
                data.push(this.criteria[c]);
                break;

            case 'flag':
                this.criteria[c].n = Number(Boolean($F($(c).down('INPUT[type=checkbox]'))));
                data.push({
                    n: this.criteria[c].n,
                    t: 'flag',
                    v: this.criteria[c].t
                });
                break;
            }
        }, this);

        $('criteria_form').setValue(Object.toJSON(data));

        this.folders.each(function(f) {
            var type = $F($(f.key).down('INPUT[type=checkbox]'))
                ? 'subfolder'
                : 'mbox';
            f_out[type].push(f.value);
        });
        $('folders_form').setValue(Object.toJSON(f_out));

        $('search_form').submit();
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
            case 'search_submit':
                this.submit();
                e.stop();
                return;

            case 'search_reset':
                this.resetCriteria();
                this.resetFolders();
                return;

            case 'search_dimp_return':
                e.stop();
                window.parent.DimpBase.go('mbox', this.data.searchmbox);
                return;

            case 'search_edit_query_cancel':
                e.stop();
                if (this.data.dimp) {
                    window.parent.DimpBase.go();
                } else {
                    document.location.href = this.prefsurl;
                }
                return;

            default:
                if (elt.hasClassName('searchuiDelete')) {
                    if (elt.up('#search_criteria')) {
                        this.deleteCriteria(elt.up('DIV.searchId'));
                    } else {
                        this.deleteFolder(elt.up('DIV.searchId'));
                    }
                    e.stop();
                    return;
                } else if (elt.hasClassName('searchuiCalendar')) {
                    Horde_Calendar.open(elt.identify(), this.criteria[elt.up('DIV.searchId').identify()].v);
                    e.stop();
                    return;
                }
                break;
            }

            elt = elt.up();
        }
    },

    changeHandler: function(e)
    {
        var tmp,
            elt = e.element(),
            val = $F(elt);

        switch (elt.readAttribute('id')) {
        case 'recent_searches':
            tmp = this.saved_searches.get($F(elt));
            this.updateCriteria(tmp.c);
            this.updateFolders(tmp.f);
            elt.clear();
            break;

        case 'search_criteria_add':
            if (val == 'or') {
                this.insertOr();
                break;
            }

            switch (this.data.types[val]) {
            case 'header':
            case 'text':
                this.insertText(val);
                break;

            case 'customhdr':
                this.insertCustomHdr();
                break;

            case 'size':
                this.insertSize(val);
                break;

            case 'date':
                this.insertDate(val);
                break;

            case 'within':
                this.insertWithin(val);
                break;

            case 'filter':
                this.insertFilter(val);
                break;

            case 'flag':
                this.insertFlag(val);
                break;
            }
            break;

        case 'search_folders_add':
            this.insertFolder(unescape($F('search_folders_add')));
            break;
        }

        e.stop();
    },


    calendarSelectHandler: function(e)
    {
        var id = e.findElement('DIV.searchId').identify();
        this.replaceDate(id, this.criteria[id].t, e.memo);
    },

    onDomLoad: function()
    {
        if (!this.data) {
            this.onDomLoad.bind(this).defer();
            return;
        }

        this.data.constants.date = $H(this.data.constants.date);
        this.data.constants.within = $H(this.data.constants.within);

        if (this.i_recent) {
            this.updateRecentSearches(this.i_recent);
            this.i_recent = null;
        }

        if (this.i_criteria) {
            this.updateCriteria(this.i_criteria);
            this.i_criteria = null;
        }

        if (this.i_folders) {
            this.updateFolders(this.i_folders);
            this.i_folders = null;
        }
    }

};

document.observe('change', ImpSearch.changeHandler.bindAsEventListener(ImpSearch));
document.observe('click', ImpSearch.clickHandler.bindAsEventListener(ImpSearch));
document.observe('dom:loaded', ImpSearch.onDomLoad.bindAsEventListener(ImpSearch));
document.observe('Horde_Calendar:select', ImpSearch.calendarSelectHandler.bindAsEventListener(ImpSearch));
