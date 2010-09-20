/**
 * Provides the javascript for the search.php script (advanced view).
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

var ImpSearch = {
    // The following variables are defined in search.php:
    //   data, i_criteria, recent, selected, text
    criteria: {},
    saved_searches: {},

    _getAll: function()
    {
        return $('search_form').getInputs(null, 'folder_list[]');
    },

    selectFolders: function(checked)
    {
        this._getAll().each(function(e) {
            if (!e.disabled) {
                e.checked = Boolean(checked);
            }
        });
    },

    updateRecentSearches: function(searches)
    {
        var fragment = document.createDocumentFragment(),
            node = new Element('OPTION');

        $('recent_searches_div').show();

        $H(searches).each(function(s) {
            fragment.appendChild($(node.clone(false)).writeAttribute({ value: s.value.v.escapeHTML() }).update(s.value.l.escapeHTML()));
            this.saved_searches[s.key] = s.value.c;
        }, this);

        $('recent_searches').appendChild(fragment);
    },

    updateSearchCriteria: function(criteria)
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
                    this.insertText(crit.h.capitalize(), crit.t, crit.n);
                    break;

                default:
                    this.insertCustomHdr({ h: crit.h.capitalize(), s: crit.t }, crit.n);
                    break;
                }
                break;

            case 'IMP_Search_Element_Or':
                this.insertOr();
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
    },

    updateSelectedFolders: function(folders)
    {
        var tmp = $('search_folders_hdr').next();
        this.selectFolders(false);
        folders.each(function(f) {
            var i = tmp.down('INPUT[value=' + f + ']');
            if (i) {
                i.checked = true;
            }
        });
    },

    changeHandler: function(e)
    {
        var elt = e.element(), val = $F(elt);

        switch (elt.readAttribute('id')) {
        case 'recent_searches':
            this.updateSearchCriteria(this.saved_searches[$F(elt)]);
            if (!$('search_criteria_table').up().visible()) {
                this._toggleHeader($('search_criteria_table').up().previous());
            }
            elt.clear();
            break;

        case 'search_criteria':
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
        }

        e.stop();
    },

    getLabel: function(id)
    {
        return $('search_criteria').down('[value="' + RegExp.escape(id) + '"]').getText() + ': ';
    },

    deleteCriteria: function(tr)
    {
        delete this.criteria[tr.identify()];
        tr.remove();
        if ($('search_criteria_table').childElements().size()) {
            $('search_criteria_table').down('TR TD').update('');
        } else {
            $('search_criteria').down('[value="or"]').hide().next().hide();
        }
    },

    resetCriteria: function()
    {
        $('search_criteria_table').childElements().each(this.deleteCriteria.bind(this));
    },

    insertCriteria: function(tds, or)
    {
        var tr = new Element('TR'),
            td = new Element('TD');

        if (!or &&
            $('search_criteria_table').childElements().size() &&
            this.criteria[$('search_criteria_table').childElements().last().readAttribute('id')].t != 'or') {
            tds.unshift(new Element('EM', { className: 'join' }).insert(this.text.and));
        } else {
            tds.unshift('');
            if (!or) {
                $('search_criteria').down('[value="or"]').show().next().show();
            }
        }

        tds.each(function(node) {
            tr.insert(td.clone(false).insert(node));
        });

        tds.shift();

        tr.childElements().last().insert(new Element('A', { href: '#', className: 'searchuiImg searchuiDelete' }));
        $('search_criteria').clear();
        $('search_criteria_table').insert(tr);
        return tr.identify();
    },

    insertOr: function()
    {
        this.criteria[this.insertCriteria([ new Element('EM', { className: 'join' }).insert(this.text.or + ' ') ], true)] = { t: 'or' };
    },

    insertText: function(id, text, not)
    {
        var tmp = [
            new Element('EM').insert(this.getLabel(id)),
            new Element('INPUT', { type: 'text', size: 25 }).setValue(text),
            new Element('SPAN').insert(new Element('INPUT', { checked: Boolean(not), className: 'checkbox', type: 'checkbox' })).insert(this.text.not_match)
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
            new Element('EM').insert(this.getLabel(id)),
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
                new Element('EM').insert(this.getLabel(id)),
                new Element('SPAN').insert(new Element('SPAN')).insert(new Element('A', { href: '#', className: 'calendarPopup', title: this.text.dateselection }).insert(new Element('SPAN', { className: 'searchuiImg searchuiCalendar' })))
            ];
        this.replaceDate(this.insertCriteria(tmp), id, data);
    },

    replaceDate: function(id, type, d)
    {
        $(id).down('TD SPAN SPAN').update(this.data.months[d.getMonth()] + ' ' + d.getDate() + ', ' + (d.getYear() + 1900));
        // Need to store date information at all times in criteria, since we
        // have no other way to track this information (there is not form
        // field for this type).
        this.criteria[id] = { t: type, v: d };
    },

    insertWithin: function(id, data)
    {
        data = data || { l: '', v: '' };

        var tmp = [
            new Element('EM').insert(this.getLabel(id)),
            new Element('SPAN').insert(new Element('INPUT', { type: 'text', size: 8 }).setValue(data.v)).insert(' ').insert($($('within_criteria').clone(true)).writeAttribute({ id: null }).show().setValue(data.l))
        ];
        this.criteria[this.insertCriteria(tmp)] = { t: id };
        tmp[1].activate();
    },

    insertFilter: function(id, not)
    {
        var tmp = [
            new Element('EM').insert(this.getLabel(id)),
            new Element('SPAN').insert(new Element('INPUT', { checked: Boolean(not), className: 'checkbox', type: 'checkbox' })).insert(this.text.not_match)
        ];
        this.criteria[this.insertCriteria(tmp)] = { t: id };
    },

    insertFlag: function(id, not)
    {
        var tmp = [
            new Element('EM').insert(this.text.flag),
            this.getLabel(id).slice(0, -2),
            new Element('SPAN').insert(new Element('INPUT', { checked: Boolean(not), className: 'checkbox', type: 'checkbox' })).insert(this.text.not_match)
        ];
        this.criteria[this.insertCriteria(tmp)] = { t: id };
    },

    _submit: function()
    {
        var data = [], tmp;

        if (!this._getAll().findAll(function(i) { return i.checked; }).size()) {
            alert(this.text.need_folder);
        } else if ($F('search_save') && !$('search_label').present()) {
            alert(this.text.need_label);
        } else {
            tmp = $('search_criteria_table').childElements().pluck('id');
            if (tmp.size()) {
                tmp.each(function(c) {
                    var tmp2;

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
                $('search_form').submit();
            } else {
                alert(this.text.need_criteria);
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
            case 'search_submit':
                this._submit();
                e.stop();
                return;

            case 'search_reset':
                this.resetCriteria();
                this.selectFolders(false);
                return;

            case 'search_dimp_return':
                e.stop();
                window.parent.DimpBase.go('folder:' + this.data.searchmbox);
                return;

            case 'link_sel_all':
            case 'link_sel_none':
                this.selectFolders(id == 'link_sel_all');
                e.stop();
                return;

            case 'search_edit_query_cancel':
                e.stop();
                if (this.data.dimp) {
                    window.parent.DimpBase.go('folder:');
                } else {
                    document.location.href = this.prefsurl;
                }
                return;

            default:
                if (elt.hasClassName('arrowExpanded') ||
                    elt.hasClassName('arrowCollapsed')) {
                    this._toggleHeader(elt.up());
                } else if (elt.hasClassName('searchuiDelete')) {
                    this.deleteCriteria(elt.up('TR'));
                    e.stop();
                    return;
                } else if (elt.hasClassName('searchuiCalendar')) {
                    Horde_Calendar.open(elt.identify(), this.criteria[elt.up('TR').identify()].v);
                    e.stop();
                    return;
                }
                break;
            }

            elt = elt.up();
        }
    },

    _toggleHeader: function(elt)
    {
        elt.down().toggle().next().toggle().up().next().toggle();
        if (elt.readAttribute('id') == 'search_folders_hdr') {
            elt.down('SPAN.searchuiFoldersActions').toggle();
            if (window.imp_search && elt.next().visible()) {
                window.imp_search.stripe();
            }
        }
    },

    calendarSelectHandler: function(e)
    {
        var id = e.findElement('TR').identify();
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

        if (this.recent) {
            this.updateRecentSearches(this.recent);
            this.recent = null;
        }

        if (this.selected) {
            this.updateSelectedFolders(this.selected);
            this.selected = null;
        }

        if (this.i_criteria) {
            this.updateSearchCriteria(this.i_criteria);
            this.i_criteria = null;
        }
    }

};

document.observe('change', ImpSearch.changeHandler.bindAsEventListener(ImpSearch));
document.observe('click', ImpSearch.clickHandler.bindAsEventListener(ImpSearch));
document.observe('dom:loaded', ImpSearch.onDomLoad.bindAsEventListener(ImpSearch));
document.observe('Horde_Calendar:select', ImpSearch.calendarSelectHandler.bindAsEventListener(ImpSearch));
