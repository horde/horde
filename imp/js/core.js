/**
 * Core dynamic view logic.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2005-2015 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */

/* ImpCore object. */
var ImpCore = {

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

            if (opts.uids.viewport_selection) {
                if (!params.get('view')) {
                    params.set('view', opts.uids.getBuffer().getView());
                }
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
            height: Math.min(1000, HordeCore.conf.popup_height),
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
        if (!p) {
            return;
        }

        elt.writeAttribute({ title: p.readAttribute('title') });

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
                if (o.a.size()) {
                    a.insert(o.g.escapeHTML() + ':').addClassName('addrgroup-name');

                    tmp = new Element('DIV', { className: 'addrgroup-div' });
                    tmp.insert(a);
                    df.appendChild(tmp);

                    this._buildAddressLinks(o.a, tmp);
                } else {
                    df.appendChild(new Element('DIV').insert(o.g.escapeHTML()));
                }
            } else if (o.p) {
                a.writeAttribute({ title: o.b }).insert(o.p.escapeHTML());
                df.appendChild(a);
            } else if (o.b) {
                a.insert(o.b.escapeHTML());
                df.appendChild(a);
            }

            this.addContextMenu({
                elt: a,
                left: true,
                offset: a,
                type: 'contacts'
            });
        }, this);
    },

    msgMetadata: function(md)
    {
        $A(md).each(function(a) {
            switch (a[0]) {
            case 'html':
                IMP_JS.iframeInject(a[1], a[2]);
                break;

            case 'image':
                new Ajax.Request(a[2], {
                    method: 'get',
                    onFailure: function(r) {
                        loadImage(a[2], function(img) {
                            $(a[1]).replace(img);
                        });
                    },
                    onSuccess: function(r) {
                        var blob, i,
                            d = Base64.atob(r.responseText),
                            b = new Uint8Array(d.length);
                        for (i = 0; i < d.length; ++i) {
                            b[i] = d.charCodeAt(i);
                        }
                        blob = new Blob(
                            [ b ],
                            { type: r.getResponseHeader('content-type') }
                        );
                        loadImage.parseMetaData(blob, function(data) {
                            loadImage(blob, function(img) {
                                $(a[1]).replace(img);
                            }, {
                                orientation: (data.exif && data.exif.get('Orientation')) || 1
                            });
                        });
                    },
                    parameters: {
                        imp_img_base64: 1
                    }
                });
                break;
            }
        }, this);
    },

    updateAtcList: function(atc)
    {
        var df = document.createDocumentFragment(),
            p = $('partlist');

        p.hide().down('UL').childElements().invoke('remove');

        if (!atc) {
            return;
        }

        if (atc.download) {
            p.down('.partlistDownloadAll').show().update(
                new Element('A', { href: atc.download }).insert(
                    this.text.atc_downloadall.sub('%s', atc.label)
                )
            );
        } else {
            p.down('.partlistDownloadAll').hide();
        }

        atc.list.each(function(a) {
            df.appendChild(new Element('LI').insert([
                a.icon,
                a.description,
                '(' + a.size + ')',
                a.download
            ].join(' ')));
        });

        p.show().down('UL').show().appendChild(df);
    },

    updateMsgLog: function(log)
    {
        var df = document.createDocumentFragment(),
            tmp = $('msgloglist');

        log.each(function(entry) {
            df.appendChild(
                new Element('LI')
                    .insert(
                        new Element('SPAN', {
                            className: 'iconImg imp-' + entry.t
                        })
                    ).insert(
                        entry.m.escapeHTML()
                    )
            );
        });

        tmp.childElements().invoke('remove');

        if (log.size()) {
            tmp.show().appendChild(df);
        } else {
            tmp.hide();
        }
    },

    /* Browser-side preferences. */

    getPref: function(k)
    {
        var p = $.jStorage.get(this.conf.pref_prefix + k);

        if (p === null) {
            /* Bug in IMP < 6.2.3 resulted in prefix being "undefined". */
            p = $.jStorage.get('undefined' + k);

            if (p === null) {
                p = $.jStorage.get(
                    /* Fallback to non-prefixed storage. */
                    k,
                    this.prefs[k] ? this.prefs[k] : this.prefs_special(k)
                );
            } else {
                $.jStorage.deleteKey('undefined' + k);
                this.setPref(k, p);
            }
        }

        return p;
    },

    setPref: function(k, v)
    {
        if (v === null) {
            $.jStorage.deleteKey(this.conf.pref_prefix + k);
            $.jStorage.deleteKey(k);
        } else {
            $.jStorage.set(this.conf.pref_prefix + k, v);
        }
    },

    // Abstract: define in any pages that need reloadMessage().
    // One argument: params
    reloadMessage: Prototype.emptyFunction,

    // Abstract: define in any pages that need reloadPart().
    // Two arguments: mimeid, params
    reloadPart: Prototype.emptyFunction,

    toggleCheck: function(elt, on)
    {
        if (Object.isArray(elt)) {
            elt.each(function(e) {
                this.toggleCheck(e, on);
            }, this);
            return;
        }

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

    baseAvailable: function()
    {
        var base = HordeCore.baseWindow();

        if (base != window &&
            !base.closed &&
            !Object.isUndefined(base.ImpBase)) {
            return base;
        }

        return null;
    },

    /* Mouse click handler. */
    clickHandler: function(e)
    {
        var args, tmp, text,
            elt = e.element();

        if (!elt.match('A')) {
            return;
        }

        switch (elt.readAttribute('mimevieweraction')) {
        case 'pgpVerifyMsg':
            args = { pgp_verify_msg: 1 };
            text = this.text.verify;
            break;

        case 'showRenderIssues':
            if ((tmp = $(elt.readAttribute('domid')))) {
                tmp.show();
            }
            elt.up('DIV').remove();
            e.memo.stop();
            return;

        case 'smimeVerifyMsg':
            args = { smime_verify_msg: 1 };
            text = this.text.verify;
            break;

        case 'tgzViewContents':
            args = { tgz_contents: 1 };
            text = this.text.loading;
            break;

        case 'unblockImageLink':
            IMP_JS.unblockImages(e.memo);
            return;

        case 'zipViewContents':
            args = { zip_contents: 1 };
            text = this.text.loading;
            break;
        }

        if (text) {
            this.reloadPart(
                elt.up('DIV[impcontentsmimeid]').readAttribute('impcontentsmimeid'),
                args
            );
            elt.replace(text);
            e.memo.stop();
            return;
        }

        if (elt.hasClassName('largeaddrspan_active') &&
            !e.memo.element().hasClassName('horde-button')) {
            if (e.memo.element().hasClassName('largeaddrlistlimit')) {
                e.memo.element().hide();
                elt.up('TD').fire('ImpCore:updateAddressHeader');
            } else {
                tmp = elt.down();
                [ tmp.down(), tmp.down(1), tmp.next() ].invoke('toggle');
            }
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
            window.prompt(this.text.emailcopy, baseelt.retrieve('email').v);
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
            tmp2 = e.element().retrieve('email');
            tmp.hide().childElements().invoke('remove');

            if (!tmp2) {
                break;
            }

            // Add e-mail info to context menu if personal name is shown on
            // page.
            if (tmp2.g) {
                $('ctx_contacts_addfilter').hide();
            } else {
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

                $('ctx_contacts_addfilter').show();

                if (tmp2.p) {
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
        HordeCore.conf.popup_height = screen.availHeight - 25 -
            ((window.outerHeight && window.innerHeight) ? (window.outerHeight - window.innerHeight) : 150);
    }

};

/* Initialize onload handler. */
document.observe('dom:loaded', ImpCore.onDomLoad.bind(ImpCore));

/* Browser native events. */
document.observe('HordeCore:click', ImpCore.clickHandler.bindAsEventListener(ImpCore));

/* ContextSensitive events. */
document.observe('ContextSensitive:click', ImpCore.contextOnClick.bindAsEventListener(ImpCore));
document.observe('ContextSensitive:show', ImpCore.contextOnShow.bindAsEventListener(ImpCore));
document.observe('ContextSensitive:trigger', ImpCore.contextOnTrigger.bindAsEventListener(ImpCore));

/* HTML IFRAME events. */
document.observe('IMP_JS:htmliframe_click', function() {
    if (this.DMenu) {
        this.DMenu.close();
    }
}.bind(ImpCore));

/* Dialog events. Since reloadMessage() can be extended, don't immediately
 * bind function call now. */
document.observe('ImpPassphraseDialog:success', function() {
    ImpCore.reloadMessage({});
});

/* Disable text selection for everything but compose/message body and FORM
 * inputs. */
document.observe(Prototype.Browser.IE ? 'selectstart' : 'mousedown', function(e) {
    if (!e.findElement('.allowTextSelection') &&
        e.element() &&
        !e.element().match('SELECT') &&
        !e.element().match('TEXTAREA') &&
        !e.element().match('INPUT')) {
        e.stop();

        if (document.activeElement) {
            var ae = $(document.activeElement);
            try {
                if (ae.match('TEXTAREA') || ae.match('INPUT')) {
                    ae.blur();
                }
            } catch (ex) {
                // Chrome 32: reports that ae can be an Object - ignore.
            }
        }
    }
});
