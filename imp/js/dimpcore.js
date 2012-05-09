/**
 * dimpcore.js - Dimp UI application logic.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

/* DimpCore object. */
var DimpCore = {

    // Vars used and defaulting to null/false:
    //   DMenu

    // Wrapper methods around HordeCore functions.

    // IMP specific 'opts': uids
    doAction: function(action, params, opts)
    {
        if (opts && opts.uids) {
            params = $H(params).clone();
            opts = opts || {};

            if (opts.uids.viewport_selection) {
                opts.uids = this.selectionToRange(opts.uids);
            }

            params.set('uid', this.toUIDString(opts.uids));
        }

        HordeCore.doAction(action, params, opts);
    },

    // Dimp specific methods.
    toUIDString: function(ob, opts)
    {
        if (DIMP.conf.pop3) {
            opts = opts || {};
            opts.pop3 = 1;
        }

        return ImpIndices.toUIDString(ob, opts);
    },

    parseUIDString: function(str)
    {
        return ImpIndices.parseUIDString(str, DIMP.conf.pop3 ? { pop3: 1 } : {});
    },

    selectionToRange: function(s)
    {
        var b = s.getBuffer(),
            tmp = {};

        if (b.getMetaData('search')) {
            s.get('dataob').each(function(r) {
                if (tmp[r.mbox]) {
                    tmp[r.mbox].push(r.uid);
                } else {
                    tmp[r.mbox] = [ r.uid ];
                }
            });
        } else {
            tmp[b.getView()] = s.get('uid');
        }

        return tmp;
    },

    // args: params, uids
    compose: function(type, args)
    {
        args = args || {};
        if (!args.params) {
            args.params = {};
        }
        if (type) {
            args.params.type = type;
        }

        if (type.startsWith('forward') || !args.uids) {
            if (type.startsWith('forward')) {
                args.params.uids = this.toUIDString(this.selectionToRange(args.uids));
            }
            HordeCore.popupWindow(DIMP.conf.URI_COMPOSE, args.params, {
                name: 'compose' + new Date().getTime()
            });
        } else {
            args.uids.get('dataob').each(function(d) {
                args.params.mailbox = d.mbox;
                args.params.uid = d.uid;
                HordeCore.popupWindow(DIMP.conf.URI_COMPOSE, args.params, {
                    name: 'compose' + new Date().getTime()
                });
            }, this);
        }
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
    //   - trigger: (boolean) Trigger popdown on button click?
    addPopdown: function(p, t, o)
    {
        o = o || {};

        var elt = new Element('SPAN', { className: 'iconImg popdownImg popdown' }),
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
            offset: elt.up(),
            type: t
        });
    },

    // See addPopdown() for documentation
    addPopdownButton: function(p, t, o)
    {
        this.addPopdown(p, t, o);
        $(p).next('SPAN.popdown').insert({ before: new Element('SPAN', { className: 'popdownSep' }) });
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

        alist.addr.each(function(o) {
            var a = new Element('A', { className: 'address' }).store({ email: o });
            df.appendChild(a);
            df.appendChild(document.createTextNode(', '));

            if (o.g) {
                a.insert(o.g.escapeHTML());
            } else if (o.p) {
                a.writeAttribute({ title: o.b }).insert(o.p.escapeHTML());
            } else if (o.b) {
                a.insert(o.b.escapeHTML());
            }

            this.DMenu.addElement(a.identify(), 'ctx_contacts', { offset: a, left: true });
        }, this);

        // Remove trailing comma
        df.removeChild(df.lastChild);

        if (alist.addr.size() > 15) {
            tmp = $('largeaddrspan').clone(true).addClassName('largeaddrspan_active');
            elt.insert(tmp);
            base = tmp.down('.dispaddrlist');
            tmp = tmp.down('.largeaddrlist');
            if (limit && alist.limit) {
                base.down('.largeaddrlistlimit').show();
                tmp.setText(tmp.textContent.replace('%d', alist.limit));
            } else {
                tmp.setText(tmp.textContent.replace('%d', alist.addr.size()));
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

    /* Add message log info to message view. */
    updateMsgLog: function(log)
    {
        var df, tmp;

        if (log) {
            df = document.createDocumentFragment();
            log.each(function(entry) {
                df.appendChild(new Element('LI').insert(new Element('SPAN', { className: 'iconImg imp-' + entry.t })).insert(entry.m));
            });

            tmp = $('msgloglist').down('UL');
            tmp.childElements().invoke('remove');
            tmp.appendChild(df);

            $('msgLogInfo').show();
        } else {
            $('msgLogInfo').hide();
        }
    },

    reloadMessage: function(params)
    {
        if (typeof DimpMessage != 'undefined') {
            window.location = HordeCore.addURLParam(document.location.href, params);
        } else {
            DimpBase.loadPreview(null, params);
        }
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
        if (e.isRightClick()) {
            return;
        }

        var elt = e.element(), id, tmp;

        while (Object.isElement(elt)) {
            // CSS class based matching
            if (elt.hasClassName('unblockImageLink')) {
                IMP_JS.unblockImages(e);
            } else if (elt.hasClassName('largeaddrspan_active')) {
                if (elt.hasClassName('largeaddrlistlimit')) {
                    elt.hide();
                    elt.up('TD').fire('DimpCore:updateAddressHeader');
                } else {
                    tmp = elt.down();
                    if (!tmp.next().visible() ||
                        elt.hasClassName('largeaddrlist')) {
                        [ tmp.down(), tmp.down(1), tmp.next() ].invoke('toggle');
                    }
                }
            } else if (elt.hasClassName('pgpVerifyMsg')) {
                elt.replace(DIMP.text.verify);
                DimpCore.reloadMessage({ pgp_verify_msg: 1 });
                e.stop();
                return;
            } else if (elt.hasClassName('smimeVerifyMsg')) {
                elt.replace(DIMP.text.verify);
                DimpCore.reloadMessage({ smime_verify_msg: 1 });
                e.stop();
                return;
            }

            elt = elt.up();
        }
    },

    contextOnClick: function(e)
    {
        var baseelt = e.element();

        switch (e.memo.elt.readAttribute('id')) {
        case 'ctx_contacts_new':
            this.compose('new', {
                params: {
                    to_json: Object.toJSON(baseelt.retrieve('email'))
                }
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
        if (!DIMP.context[e.memo]) {
            return;
        }

        var div = new Element('DIV', { className: 'context', id: e.memo }).hide();

        if (!Object.isArray(DIMP.context[e.memo])) {
            $H(DIMP.context[e.memo]).each(function(pair) {
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
            var elt = new Element('DIV').hide();
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

    /* DIMP initialization function. */
    onDomLoad: function()
    {
        if (typeof ContextSensitive != 'undefined') {
            this.DMenu = new ContextSensitive();
        }
    }

};

/* Initialize onload handler. */
document.observe('dom:loaded', DimpCore.onDomLoad.bind(DimpCore));

/* Browser native events. */
document.observe('click', DimpCore.clickHandler.bindAsEventListener(DimpCore));

/* ContextSensitive events. */
document.observe('ContextSensitive:click', DimpCore.contextOnClick.bindAsEventListener(DimpCore));
document.observe('ContextSensitive:show', DimpCore.contextOnShow.bindAsEventListener(DimpCore));
document.observe('ContextSensitive:trigger', DimpCore.contextOnTrigger.bindAsEventListener(DimpCore));

/* Dialog events. */
document.observe('HordeDialog:success', function(e) {
    switch (e.memo) {
    case 'pgpPersonal':
    case 'pgpSymmetric':
    case 'smimePersonal':
        HordeDialog.noreload = true;
        this.reloadMessage({});
        break;
    }
}.bindAsEventListener(DimpCore));

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

/* Catch image blocking actions. Put method call in function so that pages
 * don't load IMP_JS (i.e. compose page) won't break. */
document.observe('IMPImageUnblock:success', function(e) {
    IMP_JS.unblockImages(e);
});

/* Disable text selection for everything but compose/message body and FORM
 * inputs. */
document.observe(Prototype.Browser.IE ? 'selectstart' : 'mousedown', function(e) {
    if (!e.element().up('.messageBody') &&
        !e.element().match('TEXTAREA') &&
        !e.element().match('INPUT')) {
        e.stop();
    }
});
