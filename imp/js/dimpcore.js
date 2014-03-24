/**
 * dimpcore.js - Dimp UI application logic.
 *
 * Copyright 2005-2014 Horde LLC (http://www.horde.org/)
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

    // Preferences variables
    prefs: {
        preview: 'horiz',
        qsearch_field: 'all',
        splitbar_horiz: 0,
        splitbar_vert: 0,
        toggle_hdrs: 0
    },
    prefs_special: function(n) {
        switch (n) {
        case 'preview_old':
            return this.getPref('preview');

        case 'splitbar_side':
            return this.conf.sidebar_width;
        }
    },

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

        HordeCore.popupWindow(this.conf.URI_COMPOSE, params, {
            name: 'compose' + new Date().getTime()
        });
    },

    toggleButtons: function(elts, disable)
    {
        elts.compact().invoke('up').invoke(
            disable ? 'addClassName' : 'removeClassName',
            'disabled'
        );
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

        if (limit && alist.limit) {
            tmp = $('largeaddrspan').clone(true).addClassName('largeaddrspan_active').writeAttribute({ id: null });
            elt.insert(tmp);
            base = tmp.down('.dispaddrlist');
            tmp = tmp.down('.largeaddrlist');
            base.down('.largeaddrlistlimit').show();
            tmp.setText((tmp.textContent || tmp.innerText).replace('%d', alist.limit));
        } else {
            base = elt;
        }

        base.appendChild(df);

        if (limit && alist.limit) {
            tmp = (base.down('.addrgroup-div') || base).insert('[...]');
        }
        return elt;
    },

    _buildAddressLinks: function(alist, df)
    {
        alist.each(function(o) {
            var tmp,
                a = new Element('A', { className: 'horde-button address' }).store({ email: o });

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
            df.appendChild(new Element('LI').insert(new Element('SPAN', { className: 'iconImg imp-' + entry.t })).insert(entry.m.escapeHTML()));
        });

        tmp.childElements().invoke('remove');
        tmp.appendChild(df);
    },

    /* Browser-side preferences. */

    getPref: function(k)
    {
        return $.jStorage.get(
            this.pref_prefix + k,
            $.jStorage.get(
                /* Fallback to non-prefixed storage. */
                k,
                this.prefs[k] ? this.prefs[k] : this.prefs_special(k)
            )
        );
    },

    setPref: function(k, v)
    {
        if (v === null) {
            $.jStorage.deleteKey(this.pref_prefix + k);
            $.jStorage.deleteKey(k);
        } else {
            $.jStorage.set(this.pref_prefix + k, v);
        }
    },

    // Abstract: define in any pages that need reloadMessage().
    reloadMessage: function(params)
    {
    },

    toggleCheck: function(elt, on)
    {
        if (on === null) {
            if (elt) {
                elt.hide();
            }
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
            elt.replace(this.text.verify);
            this.reloadMessage({ pgp_verify_msg: 1 });
            e.memo.stop();
        } else if (elt.hasClassName('smimeVerifyMsg')) {
            elt.replace(this.text.verify);
            this.reloadMessage({ smime_verify_msg: 1 });
            e.memo.stop();
        }
    },

    contextOnClick: function(e)
    {
        var baseelt = e.element();

        switch (e.memo.elt.readAttribute('id')) {
        case 'ctx_contacts_add':
            this.doAction('addContact', {
                addr: Object.toJSON(baseelt.retrieve('email'))
            });
            break;

        case 'ctx_contacts_addfilter':
            this.doAction('newFilter', {
                addr: Object.toJSON(baseelt.retrieve('email'))
            });
            break;

        case 'ctx_contacts_copy':
            window.prompt(this.text.emailcopy, baseelt.retrieve('email').b);
            break;

        case 'ctx_contacts_new':
            this.compose('new', {
                to_json: Object.toJSON(baseelt.retrieve('email'))
            });
            break;
        }
    },

    contextOnShow: function(e)
    {
        var tmp, tmp2;

        switch (e.memo) {
        case 'ctx_contacts':
            tmp = $(e.memo).down('DIV');
            tmp.hide().childElements().invoke('remove');

            // Add e-mail info to context menu if personal name is shown on
            // page.
            if (tmp2 = e.element().retrieve('email')) {
                this.doAction('getContactsImage', {
                    addr: tmp2.b
                }, {
                    callback: function (r) {
                        if (r.flag) {
                            tmp.show().insert({
                                top: new Element('DIV')
                                    .addClassName('flagimg')
                                    .insert(new Element('IMG', { title: r.flagname, src: r.flag }))
                                    .insert(r.flagname.escapeHTML())
                            });
                        }
                        if (r.avatar) {
                            tmp.show().insert({
                                top: new Element('IMG', { src: r.avatar }).addClassName('contactimg')
                            });
                        }
                    }
                });

                if (!tmp2.g && tmp2.p) {
                    tmp.show().insert({ top: new Element('DIV', { className: 'sep' }) })
                        .insert({ top: new Element('DIV', { className: 'contactAddr' }).insert(tmp2.b.escapeHTML()) });
                }
            }
            break;
        }
    },

    contextOnTrigger: function(e)
    {
        if (!this.context[e.memo]) {
            return;
        }

        var div = new Element('DIV', { className: 'context', id: e.memo }).hide();

        if (!Object.isArray(this.context[e.memo])) {
            $H(this.context[e.memo]).each(function(pair) {
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

        /* Set popup height. */
        HordeCore.conf.popup_height = screen.availHeight - 50 -
            ((window.outerHeight && window.innerHeight) ? (window.outerHeight - window.innerHeight) : 150);
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
