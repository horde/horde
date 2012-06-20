/**
 * Provides the javascript for the search.php script (advanced view).
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

var ImpSearch = {

    // The following variables are defined in search.php:
    //   data, i_criteria, i_mboxes, i_recent, text
    criteria: {},
    mboxes: $H(),
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
            case 'IMP_Search_Element_Attachment':
                this.insertFilter('attach', crit);
                break;

            case 'IMP_Search_Element_Bulk':
                this.insertFilter('bulk', crit);
                break;

            case 'IMP_Search_Element_Daterange':
                // JS Date() requires timestamp in ms; PHP value is in secs
                this.insertDate(crit.b ? new Date(crit.b * 1000) : 0, crit.e ? new Date(crit.e * 1000) : 0, crit.n);
                break;

            case 'IMP_Search_Element_Flag':
                this.insertFlag(encodeURIComponent(decodeURIComponent(crit.f)), !crit.s);
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
                this.insertSize(crit.l ? 'size_larger' : 'size_smaller', crit.s);
                break;

            case 'IMP_Search_Element_Text':
                this.insertText(crit.b ? 'body' : 'text', crit.t, crit.n);
                break;

            case 'IMP_Search_Element_Within':
                this.insertWithin(crit.o ? 'older' : 'younger', { l: this.data.constants.within.index(crit.t), v: crit.v });
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
        return $('search_criteria_add').down('[value="' + RegExp.escape(id) + '"]').textContent + ': ';
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
            new Element('SPAN', { className: 'notMatch' }).insert(new Element('INPUT', { className: 'checkbox', type: 'checkbox' }).setValue(not)).insert(this.text.not_match)
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
            new Element('SPAN').insert(new Element('INPUT', { className: 'checkbox', type: 'checkbox' }).setValue(not)).insert(this.text.not_match)
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

    insertDate: function(begin, end, not)
    {
        var elt1, elt2, tmp, tmp2;

        elt1 = new Element('SPAN').insert(
            new Element('SPAN')
        ).insert(
            new Element('A', { href: '#', className: 'dateReset', title: this.text.datereset }).insert(
                new Element('SPAN', { className: 'iconImg searchuiImg closeImg' })
            )
        ).insert(
            new Element('A', { href: '#', className: 'calendarPopup', title: this.text.dateselection }).insert(
                new Element('SPAN', { className: 'iconImg searchuiImg calendarImg' })
            )
        );
        elt2 = elt1.clone(true);

        tmp = [
            new Element('EM').insert(this.getCriteriaLabel('date_range')),
            elt1.addClassName('beginDate'),
            new Element('SPAN').insert(this.text.to),
            elt2.addClassName('endDate'),
            new Element('SPAN', { className: 'notMatch' }).insert(new Element('INPUT', { className: 'checkbox', type: 'checkbox' }).setValue(not)).insert(this.text.not_match)
        ];

        tmp2 = this.insertCriteria(tmp);
        this.updateDate(tmp2, elt1, begin);
        this.updateDate(tmp2, elt2, end);
    },

    updateDate: function(id, elt, data)
    {
        if (data) {
            elt.down('SPAN').update(this.data.months[data.getMonth()] + ' ' + data.getDate() + ', ' + data.getFullYear());
            elt.down('A.dateReset').show();

            // Convert Date object to a UTC object, since JSON outputs in UTC.
            data = new Date(Date.UTC(data.getFullYear(), data.getMonth(), data.getDate()));
        } else {
            elt.down('SPAN').update('-----');
            elt.down('A.dateReset').hide();
        }

        // Need to store date information at all times in criteria, since
        // there is no other way to track this information (there is no
        // form field for this type). Also, convert Date object to a UTC
        // object, since JSON outputs in UTC.
        if (!this.criteria[id]) {
            this.criteria[id] = { t: 'date_range' };
        }
        this.criteria[id][elt.hasClassName('beginDate') ? 'b' : 'e'] = data;
    },

    insertWithin: function(id, data)
    {
        data = data || { l: '', v: '' };

        var tmp = [
            new Element('EM').insert(this.getCriteriaLabel(id)),
            new Element('SPAN').insert(new Element('INPUT', { type: 'text', size: 8 }).setValue(data.v)).insert(' ').insert($($('within_criteria').clone(true)).writeAttribute({ id: null }).show().setValue(data.l))
        ];
        this.criteria[this.insertCriteria(tmp)] = { t: id };
        tmp[1].down().activate();
    },

    insertFilter: function(id, not)
    {
        var tmp = [
            new Element('EM').insert(this.getCriteriaLabel(id)),
            new Element('SPAN').insert(new Element('INPUT', { className: 'checkbox', type: 'checkbox' }).setValue(not)).insert(this.text.not_match)
        ];
        this.criteria[this.insertCriteria(tmp)] = { t: id };
    },

    insertFlag: function(id, not)
    {
        var tmp = [
            new Element('EM').insert(this.text.flag),
            new Element('SPAN', { className: 'searchFlag' }).insert(this.getCriteriaLabel(id).slice(0, -2)),
            new Element('SPAN', { className: 'notMatch' }).insert(new Element('INPUT', { className: 'checkbox', type: 'checkbox' }).setValue(not)).insert(this.text.not_match)
        ];
        this.criteria[this.insertCriteria(tmp)] = { t: id };
    },

    // Mailbox actions

    // mboxes = (object) m: mailboxes, s: subfolders
    updateMailboxes: function(mboxes)
    {
        this.resetMailboxes();
        mboxes.m.each(function(f) {
            this.insertMailbox(f, false);
        }, this);
        mboxes.s.each(function(f) {
            this.insertMailbox(f, true);
        }, this);
    },

    deleteMailbox: function(div)
    {
        var first, keys,
            id = div.identify()

        this.disableMailbox(false, this.mboxes.get(id));
        this.mboxes.unset(id);
        div.remove();

        keys = $('search_mboxes').childElements().pluck('id');

        if (keys.size()) {
            first = keys.first();
            if ($(first).down().hasClassName('join')) {
                $(first).down().remove();
            }
        }

        if (!keys.size()) {
            $('no_search_mboxes', 'search_mboxes').invoke('toggle');
            $('search_mboxes_add').up().show();
        }
    },

    resetMailboxes: function()
    {
        elts = $('search_mboxes').childElements();

        if (elts.size()) {
            this.mboxes.values().each(this.disableMailbox.bind(this, false));
            elts.invoke('remove');
            $('no_search_mboxes', 'search_mboxes').invoke('toggle');
            $('search_mboxes_add').clear().up().show();
            this.mboxes = $H();
        }
    },

    insertMailbox: function(mbox, checked)
    {
        var div = new Element('DIV', { className: 'searchId' }),
            div2 = new Element('DIV', { className: 'searchElement' });

        if (mbox == this.allsearch) {
            this.resetMailboxes();
            [ $('no_search_mboxes'), $('search_mboxes_add').up() ].invoke('hide');
            $('search_mboxes').show();
            div2.insert(
                new Element('EM').insert(ImpSearch.text.search_all.escapeHTML())
            ).insert(
                new Element('A', { href: '#', className: 'iconImg searchuiImg searchuiDelete' })
            );
        } else {
            if ($('search_mboxes').childElements().size()) {
                div.insert(new Element('EM', { className: 'join' }).insert(this.text.and));
            } else {
                $('no_search_mboxes', 'search_mboxes').invoke('toggle');
            }

            div2.insert(
                new Element('EM').insert(this.getMailboxLabel(mbox).escapeHTML())
            ).insert(
                new Element('SPAN', { className: 'subfolders' }).insert(new Element('INPUT', { className: 'checkbox', type: 'checkbox' }).setValue(checked)).insert(this.text.subfolder_search).setStyle(mbox == this.data.inbox ? { display: 'none' } : {})
            ).insert(
                new Element('A', { href: '#', className: 'iconImg searchuiImg searchuiDelete' })
            );

            this.disableMailbox(true, mbox);
            $('search_mboxes_add').clear();
        }

        div.insert(div2);
        $('search_mboxes').insert(div);

        this.mboxes.set(div.identify(), mbox);
    },

    getMailboxLabel: function(mbox)
    {
        return this.data.mbox_list[mbox];
    },

    disableMailbox: function(disable, mbox)
    {
        $('search_mboxes_add').down('[value="' + mbox + '"]').writeAttribute({ disabled: disable });
    },

    // Miscellaneous actions

    submit: function()
    {
        var criteria,
            data = [],
            f_out = { mbox: [], subfolder: [] },
            sflist = [],
            type = $F('search_type');

        if (type && !$('search_label').present()) {
            alert(this.text.need_label);
            return;
        }

        if (type != 'filter' && !this.mboxes.size()) {
            alert(this.text.need_mbox);
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
                if (!this.criteria[c].b && !this.criteria[c].e) {
                    alert(this.text.need_date);
                    return;
                }
                this.criteria[c].n = Number(Boolean($F($(c).down('INPUT[type=checkbox]'))));
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

        if ($('search_mboxes_add').up().visible()) {
            this.mboxes.each(function(f) {
                var type = $F($(f.key).down('INPUT[type=checkbox]'))
                    ? 'subfolder'
                    : 'mbox';
                f_out[type].push(f.value);
            });
        } else {
            f_out.mbox.push(this.allsearch);
        }
        $('mboxes_form').setValue(Object.toJSON(f_out));

        $('search_form').submit();
    },

    clickHandler: function(e)
    {
        var elt = e.element(), tmp;

        switch (elt.readAttribute('id')) {
        case 'search_submit':
            this.submit();
            e.memo.stop();
            break;

        case 'search_reset':
            this.resetCriteria();
            this.resetMailboxes();
            return;

        case 'search_dimp_return':
            e.memo.hordecore_stop = true;
            window.parent.DimpBase.go('mbox', this.data.searchmbox);
            break;

        case 'search_edit_query_cancel':
            e.memo.hordecore_stop = true;
            if (this.data.dimp) {
                window.parent.DimpBase.go();
            } else {
                document.location.href = this.prefsurl;
            }
            break;

        case 'show_unsub':
            new Ajax.Request(this.ajaxurl + 'searchMailboxList', {
                onSuccess: this.showUnsubCallback.bind(this),
                parameters: {
                    unsub: 1
                }
            });
            elt.remove();
            e.memo.stop();
            break;

        default:
            if (elt.hasClassName('searchuiDelete')) {
                if (elt.up('#search_criteria')) {
                    this.deleteCriteria(elt.up('DIV.searchId'));
                } else {
                    this.deleteMailbox(elt.up('DIV.searchId'));
                }
                e.memo.stop();
            } else if (elt.hasClassName('calendarImg')) {
                Horde_Calendar.open(elt.identify(), this.criteria[elt.up('DIV.searchId').identify()].v);
                e.memo.stop();
            } else if (elt.hasClassName('closeImg') &&
                       (elt.up('SPAN.beginDate') || elt.up('SPAN.endDate'))) {
                this.updateDate(
                    elt.up('DIV.searchId').identify(),
                    elt.up('SPAN'),
                    0
                );
                e.memo.stop();
            }
            break;
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
            this.updateMailboxes(tmp.f);
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
                this.insertDate();
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

        case 'search_mboxes_add':
            this.insertMailbox($F('search_mboxes_add'));
            break;
        }

        e.stop();
    },

    calendarSelectHandler: function(e)
    {
        this.updateDate(
            e.findElement('DIV.searchId').identify(),
            e.element().up('SPAN'),
            e.memo
        );
    },

    showUnsubCallback: function(r)
    {
        var resp, sfa, vals;

        if (r.responseJSON.response) {
            resp = r.responseJSON.response;
            this.data.mbox_list = resp.mbox_list;
            sfa = $('search_mboxes_add');
            vals = sfa.select('[disabled]').pluck('value');
            sfa.update(resp.tree);
            vals.each(function(v) {
                if (v.length) {
                    this.disableMailbox(true, v);
                }
            }, this);
        }
    },

    onDomLoad: function()
    {
        if (!this.data) {
            this.onDomLoad.bind(this).defer();
            return;
        }

        HordeCore.initHandler('click');

        if (Prototype.Browser.IE) {
            $('recent_searches', 'search_criteria_add', 'search_mboxes_add').compact().invoke('observe', 'change', ImpSearch.changeHandler.bindAsEventListener(ImpSearch));
        } else {
            document.observe('change', ImpSearch.changeHandler.bindAsEventListener(ImpSearch));
        }

        this.data.constants.within = $H(this.data.constants.within);

        if (this.i_recent) {
            this.updateRecentSearches(this.i_recent);
            delete this.i_recent;
        }

        if (this.i_criteria) {
            this.updateCriteria(this.i_criteria);
            delete this.i_criteria;
        }

        if (this.i_mboxes) {
            this.updateMailboxes(this.i_mboxes);
            delete this.i_mboxes;
        }
    }

};

document.observe('dom:loaded', ImpSearch.onDomLoad.bindAsEventListener(ImpSearch));
document.observe('HordeCore:click', ImpSearch.clickHandler.bindAsEventListener(ImpSearch));
document.observe('Horde_Calendar:select', ImpSearch.calendarSelectHandler.bindAsEventListener(ImpSearch));
