/**
 * dimpcore.js - Dimp UI application logic.
 *
 * Copyright 2005-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

/* DimpCore object. */
var DimpCore = {

    // Vars used and defaulting to null/false:
    //   DMenu

    conf: {},
    context: {},
    text: {},

    // Wrapper methods around HordeCore functions.

    // IMP specific 'opts': uids
    doAction: function(action, params, opts)
    {
        if (opts && opts.uids) {
            params = $H(params).clone();
            opts = opts || {};

            if (opts.uids.viewport_selection) {
                opts.uids = opts.uids.get('uid');
            }

            params.set('buid', opts.uids.toViewportUidString());
        }

        return HordeCore.doAction(action, params, opts);
    },

    compose: function(type, params)
    {
        params = params || {};
        params.type = type;

        HordeCore.popupWindow(DimpCore.conf.URI_COMPOSE, params, {
            name: 'compose' + new Date().getTime()
        });
    },

    toggleButtons: function(elts, disable)
    {
        elts.each(function(b) {
            [ b.up() ].invoke(disable ? 'addClassName' : 'removeClassName', 'disabled');
        });
    },

    // p = (Element) Parent element
    // t = (string) Context menu type
    // o = (object) Options:
    //   - disabled: (boolean) Disabled?
    //   - insert: (string) Insertion position.
    //   - no_offset: (boolean) If true, offset from popdown graphic.
    //   - trigger: (boolean) Trigger popdown on button click?
    addPopdown: function(p, t, o)
    {
        o = o || {};

        var elt = new Element('SPAN', { className: 'horde-popdown' }),
            ins = {};
        p = $(p);

        ins[o.insert ? o.insert : 'after'] = elt;
        p.insert(ins);

        if (o.trigger) {
            this.addContextMenu({
                disable: o.disabled,
                elt: p,
                left: true,
                offset: p.up(),
                type: t
            });
        }

        this.addContextMenu({
            disable: o.disabled,
            elt: elt,
            left: true,
            offset: (o.no_offset ? elt : elt.up()),
            type: t
        });
    },

    // See addPopdown() for documentation
    addPopdownButton: function(p, t, o)
    {
        this.addPopdown(p, t, o);
    },

    addContextMenu: function(p)
    {
        if (this.DMenu) {
            this.DMenu.addElement(p.elt.identify(), 'ctx_' + p.type, p);
        }
    },

    /* Add dropdown menus to addresses. */
    buildAddressLinks: function(alist, elt, limit)
    {
        var base, tmp,
            df = document.createDocumentFragment();

        if (alist.raw) {
            elt.insert(alist.raw);
            return elt;
        }

        this._buildAddressLinks(alist.addr, df);

        if (alist.addr.size() > 15) {
            tmp = $('largeaddrspan').clone(true).addClassName('largeaddrspan_active').writeAttribute({ id: null });
            elt.insert(tmp);
            base = tmp.down('.dispaddrlist');
            tmp = tmp.down('.largeaddrlist');
            if (limit && alist.limit) {
                base.down('.largeaddrlistlimit').show();
                tmp.setText((tmp.textContent || tmp.innerText).replace('%d', alist.limit));
            } else {
                tmp.setText((tmp.textContent || tmp.innerText).replace('%d', alist.addr.size()));
            }
        } else {
            base = elt;
        }

        base.appendChild(df);
        if (limit && alist.limit) {
            base.insert(', [...]');
        }

        return elt;
    },

    _buildAddressLinks: function(alist, df)
    {
        alist.each(function(o) {
            var tmp,
                a = new Element('A', { className: 'horde-button' }).store({ email: o });

            if (o.g) {
                a.insert(o.g.escapeHTML() + ':').addClassName('addrgroup-name');

                tmp = new Element('DIV', { className: 'addrgroup-div' });
                tmp.insert(a);
                df.appendChild(tmp);

                this._buildAddressLinks(o.a, tmp);
            } else if (o.p) {
                a.writeAttribute({ title: o.b }).insert(o.p.escapeHTML());
                df.appendChild(a);
            } else if (o.b) {
                a.insert(o.b.escapeHTML());
                df.appendChild(a);
            }

            this.DMenu.addElement(a.identify(), 'ctx_contacts', { offset: a, left: true });
        }, this);
    },

    /* Add message log info to message view. */
    updateMsgLog: function(log)
    {
        var df = document.createDocumentFragment(),
            tmp = $('msgloglist').down('UL');

        log.each(function(entry) {
            df.appendChild(new Element('LI').insert(new Element('SPAN', { className: 'iconImg imp-' + entry.t })).insert(entry.m));
        });

        tmp.childElements().invoke('remove');
        tmp.appendChild(df);
    },

    // Abstract: define in any pages that need reloadMessage().
    reloadMessage: function(params)
    {
    },

    toggleCheck: function(elt, on)
    {
        if (on === null) {
            elt.hide();
            return;
        }

        var a, r;

        if (on) {
            a = 'msCheckOn';
            r = 'msCheck';
        } else {
            a = 'msCheck';
            r = 'msCheckOn';
        }

        elt.removeClassName(r).addClassName(a).show();
    },

    /* Mouse click handler. */
    clickHandler: function(e)
    {
        var elt = e.element(), tmp;

        if (elt.hasClassName('unblockImageLink')) {
            IMP_JS.unblockImages(e.memo);
        } else if (elt.hasClassName('largeaddrspan_active') &&
                   !e.memo.element().hasClassName('horde-button')) {
            if (e.memo.element().hasClassName('largeaddrlistlimit')) {
                e.memo.element().hide();
                elt.up('TD').fire('DimpCore:updateAddressHeader');
            } else {
                tmp = elt.down();
                [ tmp.down(), tmp.down(1), tmp.next() ].invoke('toggle');
            }
        } else if (elt.hasClassName('pgpVerifyMsg')) {
            elt.replace(DimpCore.text.verify);
            DimpCore.reloadMessage({ pgp_verify_msg: 1 });
            e.memo.stop();
        } else if (elt.hasClassName('smimeVerifyMsg')) {
            elt.replace(DimpCore.text.verify);
            DimpCore.reloadMessage({ smime_verify_msg: 1 });
            e.memo.stop();
        }
    },

    contextOnClick: function(e)
    {
        var baseelt = e.element();

        switch (e.memo.elt.readAttribute('id')) {
        case 'ctx_contacts_new':
            this.compose('new', {
                to_json: Object.toJSON(baseelt.retrieve('email'))
            });
            break;

        case 'ctx_contacts_add':
            this.doAction('addContact', {
                addr: Object.toJSON(baseelt.retrieve('email'))
            });
            break;
        }
    },

    contextOnShow: function(e)
    {
        var tmp;

        switch (e.memo) {
        case 'ctx_contacts':
            tmp = $(e.memo).down('DIV.contactAddr');
            if (tmp) {
                tmp.next().remove();
                tmp.remove();
            }

            // Add e-mail info to context menu if personal name is shown on
            // page.
            if ((tmp = e.element().retrieve('email')) && !tmp.g && tmp.p) {
                $(e.memo)
                    .insert({ top: new Element('DIV', { className: 'sep' }) })
                    .insert({ top: new Element('DIV', { className: 'contactAddr' }).insert(tmp.b.escapeHTML()) });
            }
            break;
        }
    },

    contextOnTrigger: function(e)
    {
        if (!DimpCore.context[e.memo]) {
            return;
        }

        var div = new Element('DIV', { className: 'context', id: e.memo }).hide();

        if (!Object.isArray(DimpCore.context[e.memo])) {
            $H(DimpCore.context[e.memo]).each(function(pair) {
                div.insert(this._contextOnTrigger(pair, e.memo));
            }, this);
        }

        $(document.body).insert(div);
    },

    _contextOnTrigger: function(pair, ctx)
    {
        var elt;

        if (pair.key.startsWith('_sep')) {
            return new Element('DIV', { className: 'sep' });
        }
        if (pair.key.startsWith('_mbox')) {
            return new Element('DIV', { className: 'mboxName' }).insert(pair.value.escapeHTML());
        }
        if (pair.key.startsWith('_sub')) {
            elt = new Element('DIV').hide();
            $H(pair.value).each(function(v) {
                elt.insert(this._contextOnTrigger(v, ctx));
            }, this);
            return elt;
        }

        elt = new Element('A');
        if (pair.key.startsWith('*')) {
            pair.key = pair.key.substring(1);
        } else {
            elt.insert(new Element('SPAN', { className: 'iconImg' }));
        }
        elt.writeAttribute('id', ctx + '_' + pair.key);
        elt.insert(pair.value.escapeHTML());

        return elt;
    },

    onDomLoad: function()
    {
        HordeCore.initHandler('click');

        if (typeof ContextSensitive != 'undefined') {
            this.DMenu = new ContextSensitive();
        }
    }

};

/* Initialize onload handler. */
document.observe('dom:loaded', DimpCore.onDomLoad.bind(DimpCore));

/* Browser native events. */
document.observe('HordeCore:click', DimpCore.clickHandler.bindAsEventListener(DimpCore));

/* ContextSensitive events. */
document.observe('ContextSensitive:click', DimpCore.contextOnClick.bindAsEventListener(DimpCore));
document.observe('ContextSensitive:show', DimpCore.contextOnShow.bindAsEventListener(DimpCore));
document.observe('ContextSensitive:trigger', DimpCore.contextOnTrigger.bindAsEventListener(DimpCore));

/* Dialog events. Since reloadMessage() can be extended, don't immediately
 * bind function call now. */
document.observe('ImpPassphraseDialog:success', function() {
    DimpCore.reloadMessage({});
});

/* Notification events. */
document.observe('HordeCore:showNotifications', function(e) {
    switch (e.memo.type) {
    case 'imp.reply':
    case 'imp.forward':
    case 'imp.redirect':
        HordeBase.Growler.growl(m.message.escapeHTML(), {
            className: m.type.replace('.', '-'),
            life: 8
        });
        break;
    }
});

/* Disable text selection for everything but compose/message body and FORM
 * inputs. */
document.observe(Prototype.Browser.IE ? 'selectstart' : 'mousedown', function(e) {
    if (!e.findElement('.allowTextSelection') &&
        !e.element().match('SELECT') &&
        !e.element().match('TEXTAREA') &&
        !e.element().match('INPUT')) {
        e.stop();

        if (document.activeElement) {
            var ae = $(document.activeElement);
            if (ae.match('TEXTAREA') || ae.match('INPUT')) {
                ae.blur();
            }
        }
    }
});
