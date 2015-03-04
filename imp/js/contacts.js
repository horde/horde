/**
 * Contacts page.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2014-2015 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */

var ImpContacts = {

    // initial,
    // searchGhost,
    // text,

    addAddress: function(f)
    {
        var df = document.createDocumentFragment(),
            sa = $('selected_addresses').select('[value]'),
            sr = $('search_results'),
            sel = $F(sr);

        if (!sel.size()) {
            alert(this.text.select);
            return;
        }

        sel.each(function(s) {
            if (!sa.any(function(j) {
                return (j.readAttribute('value') == s) &&
                       (j.retrieve('header') == f);
            })) {
                df.appendChild(
                    new Element('OPTION', { value: s })
                        .store('header', f)
                        .insert(
                            new Element('EM').insert(this.text.rcpt[f] + ': ')
                        )
                        .insert(s.escapeHTML())
                );
            }
        }, this);

        $('selected_addresses').appendChild(df);
    },

    updateMessage: function()
    {
        var addr = {};

        if (!parent.opener) {
            alert(this.text.closed);
            window.close();
            return;
        }

        $('selected_addresses').select('[value]').each(function(s) {
            var field = s.retrieve('header');

            if (Object.isUndefined(addr[field])) {
                addr[field] = [];
            }

            addr[field].push(s.readAttribute('value'));
        });

        $(parent.opener.document).fire('ImpContacts:update', addr);
        window.close();
    },

    removeAddress: function()
    {
        $('selected_addresses').childElements().each(function(o) {
            if (o.selected) {
                o.remove();
            }
        });
    },

    contactsSearch: function()
    {
        var sr = $('search_results');

        sr.select('[value]').invoke('remove');
        sr.childElements().invoke('hide');
        sr.appendChild(
            new Element('OPTION', { disabled: true, value: "" })
                .insert(this.text.searching)
        );

        HordeCore.doAction('contactsSearch', {
            search: this.searchGhost.hasinput ? $F('search') : '',
            source: $F('source')
        }, {
            callback: function(r) {
                sr.select(':not([value])').invoke('show');
                this.updateResults(r.results);
            }.bind(this)
        });
    },

    updateResults: function(r)
    {
        var df = document.createDocumentFragment(),
            sr = $('search_results');

        r.each(function(addr) {
            df.appendChild(
                new Element('OPTION', { value: addr })
                    .insert(addr.escapeHTML())
            );
        });

        sr.select('[value]').invoke('remove');

        if (r.size()) {
            sr.appendChild(df);
        }
    },

    resize: function()
    {
        window.resizeBy(
            0,
            Math.max(0, document.body.clientHeight - document.viewport.getHeight())
        );
    },

    onDomLoad: function()
    {
        HordeCore.initHandler('click');
        HordeCore.initHandler('dblclick');

        $('contacts').observe('FormGhost:submit', function(e) {
            if (this.searchGhost.hasinput) {
                this.contactsSearch();
            }
        }.bind(this));

        if (this.initial) {
            this.updateResults(this.initial);
            delete this.initial;
        }

        this.searchGhost = new FormGhost('search');

        this.resize.bind(this).delay(0.1);
    },

    clickHandler: function(e)
    {
        switch (e.element().readAttribute('id')) {
        case 'btn_add_bcc':
            this.addAddress('bcc');
            break;

        case 'btn_add_cc':
            this.addAddress('cc');
            break;

        case 'btn_add_to':
            this.addAddress('to');
            break;

        case 'btn_cancel':
            window.close();
            e.memo.hordecore_stop = true;
            break;

        case 'btn_clear':
            this.searchGhost.reset();
            break;

        case 'btn_delete':
            this.removeAddress();
            break;

        case 'btn_search_all':
            $('search').value = '';
            this.contactsSearch();
            break;

        case 'btn_update':
            this.updateMessage();
            break;
        }
    },

    dblclickHandler: function(e)
    {
        switch (e.element().readAttribute('id')) {
        case 'search_results':
            this.addAddress('to');
            break;

        case 'selected_addresses':
            this.removeAddress();
            break;
        }
    }

};

document.observe('dom:loaded', ImpContacts.onDomLoad.bind(ImpContacts));
document.observe('HordeCore:click', ImpContacts.clickHandler.bindAsEventListener(ImpContacts));
document.observe('HordeCore:dblclick', ImpContacts.dblclickHandler.bindAsEventListener(ImpContacts));
