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
    //   DMenu, Growler, inAjaxCallback, is_init, is_logout
    //   onDoActionComplete
    alarms: [],
    base: null,
    growler_log: true,
    server_error: 0,

    doActionOpts: {
        onException: function(r, e) { DimpCore.debug('onException', e); },
        onFailure: function(t, o) {
            DimpCore.debug('onFailure', t);
            DimpCore.showNotifications([ { type: 'horde.error', message: DIMP.text.ajax_error } ]);
        },
        evalJS: false,
        evalJSON: true
    },

    debug: function(label, e)
    {
        if (!this.is_logout && window.console && window.console.error) {
            window.console.error(label, Prototype.Browser.Gecko ? e : $H(e).inspect());
        }
    },

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

    // 'opts' -> ajaxopts, callback, uids
    doAction: function(action, params, opts)
    {
        params = $H(params).clone();
        opts = opts || {};

        var ajaxopts = Object.extend(Object.clone(this.doActionOpts), opts.ajaxopts || {});

        if (opts.uids) {
            if (opts.uids.viewport_selection) {
                opts.uids = this.selectionToRange(opts.uids);
            }
            params.set('uid', this.toUIDString(opts.uids));
        }

        this.addRequestParams(params);
        ajaxopts.parameters = params;

        ajaxopts.onComplete = function(t, o) { this.doActionComplete(t, opts.callback); }.bind(this);

        new Ajax.Request(DIMP.conf.URI_AJAX + action, ajaxopts);
    },

    // 'opts' -> ajaxopts, callback
    submitForm: function(form, opts)
    {
        opts = opts || {};
        var ajaxopts = Object.extend(Object.clone(this.doActionOpts), opts.ajaxopts || {});
        ajaxopts.onComplete = function(t, o) { this.doActionComplete(t, opts.callback); }.bind(this);
        $(form).request(ajaxopts);
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

    // params: (Hash)
    addRequestParams: function(params)
    {
        if (DIMP.conf.SESSION_ID) {
            params.update(DIMP.conf.SESSION_ID.toQueryParams());
        }
    },

    doActionComplete: function(request, callback)
    {
        this.inAjaxCallback = true;

        if (!request.responseJSON) {
            if (++this.server_error == 3) {
                this.showNotifications([ { type: 'horde.error', message: DIMP.text.ajax_timeout } ]);
            }
            if (request.request) {
                request.request.options.onFailure(request, {});
            }
            this.inAjaxCallback = false;
            return;
        }

        var r = request.responseJSON;

        if (!r.msgs) {
            r.msgs = [];
        }

        if (r.response && Object.isFunction(callback)) {
            try {
                callback(r);
            } catch (e) {
                this.debug('doActionComplete', e);
            }
        }

        if (this.server_error >= 3) {
            r.msgs.push({ type: 'horde.success', message: DIMP.text.ajax_recover });
        }
        this.server_error = 0;

        this.showNotifications(r.msgs);

        if (r.response && this.onDoActionComplete) {
            this.onDoActionComplete(r.response);
        }

        this.inAjaxCallback = false;
    },

    showNotifications: function(msgs)
    {
        if (!msgs.size() || this.is_logout) {
            return;
        }

        msgs.find(function(m) {
            if (!Object.isString(m.message)) {
                return;
            }

            switch (m.type) {
            case 'horde.ajaxtimeout':
                this.logout(m.message);
                return true;

            case 'horde.alarm':
                var alarm = m.flags.alarm;
                // Only show one instance of an alarm growl.
                if (this.alarms.include(alarm.id)) {
                    break;
                }

                this.alarms.push(alarm.id);

                var message = alarm.title.escapeHTML();
                if (alarm.params && alarm.params.notify) {
                    if (alarm.params.notify.url) {
                        message = new Element('a', { href: alarm.params.notify.url })
                            .insert(message);
                    }
                    if (alarm.params.notify.sound) {
                        Sound.play(alarm.params.notify.sound);
                    }
                }
                message = new Element('div')
                    .insert(message);
                if (alarm.params && alarm.params.notify &&
                    alarm.params.notify.subtitle) {
                    message.insert(new Element('br')).insert(alarm.params.notify.subtitle);
                }
                if (alarm.user) {
                    var select = '<select>';
                    $H(DIMP.conf.snooze).each(function(snooze) {
                        select += '<option value="' + snooze.key + '">' + snooze.value + '</option>';
                    });
                    select += '</select>';
                    message.insert('<br /><br />' + DIMP.text.snooze.interpolate({ time: select, dismiss_start: '<input type="button" value="', dismiss_end: '" class="button ko" />' }));
                }
                var growl = this.Growler.growl(message, {
                    className: 'horde-alarm',
                    log: false,
                    sticky: true
                });
                growl.store('alarm', alarm.id);

                document.observe('Growler:destroyed', function(e) {
                    var id = e.element().retrieve('alarm');
                    if (id) {
                        this.alarms = this.alarms.without(id);
                    }
                }.bindAsEventListener(this));

                if (alarm.user) {
                    message.down('select').observe('change', function(e) {
                        if (e.element().getValue()) {
                            this.Growler.ungrowl(growl);
                            new Ajax.Request(
                                DIMP.conf.URI_SNOOZE,
                                { parameters: { alarm: alarm.id,
                                                snooze: e.element().getValue() } });
                        }
                    }.bindAsEventListener(this))
                    .observe('click', function(e) {
                        e.stop();
                    });
                    message.down('input[type=button]').observe('click', function(e) {
                        new Ajax.Request(
                            DIMP.conf.URI_SNOOZE,
                            { parameters: { alarm: alarm.id,
                                            snooze: -1 } });
                    }.bindAsEventListener(this));
                }
                break;

            case 'horde.error':
            case 'horde.message':
            case 'horde.success':
            case 'horde.warning':
                this.Growler.growl(
                    m.flags && m.flags.include('content.raw')
                        ? m.message.replace(new RegExp('<a href="([^"]+)"'), '<a href="#" onclick="(function(){var base=DimpCore.base?DimpCore.base.DimpBase:DimpBase;base.go(\'app\',{app:null,data:\'$1\'});})();return false;"')
                        : m.message.escapeHTML(),
                    {
                        className: m.type.replace('.', '-'),
                        life: (m.type == 'horde.error' ? 12 : 8),
                        log: 1
                    });
                break;

            case 'imp.reply':
            case 'imp.forward':
            case 'imp.redirect':
                this.Growler.growl(m.message.escapeHTML(), {
                    className: m.type.replace('.', '-'),
                    life: 8
                });
                break;
            }
        }, this);
    },

    compose: function(type, args)
    {
        var params = {};
        if (type) {
            params.type = type;
        }

        if (type.startsWith('forward') || !args || !args.uids) {
            if (type.startsWith('forward')) {
                params.uids = this.toUIDString(this.selectionToRange(args.uids));
            } else if (args) {
                if (args.to) {
                    params.to = args.to;
                }
                if (args.toname) {
                    params.toname = args.toname;
                }
            }
            this.popupWindow(this.addURLParam(DIMP.conf.URI_COMPOSE, params), 'compose' + new Date().getTime());
        } else {
            args.uids.get('dataob').each(function(d) {
                params.mailbox = d.mbox;
                params.uid = d.uid;
                this.popupWindow(this.addURLParam(DIMP.conf.URI_COMPOSE, params), 'compose' + new Date().getTime());
            }, this);
        }
    },

    popupWindow: function(url, name, onload)
    {
        var opts = {
            height: DIMP.conf.popup_height,
            name: name.gsub(/\W/, '_'),
            noalert: true,
            onload: onload,
            url: url,
            width: DIMP.conf.popup_width
        };

        if (!Horde.popup(opts)) {
            this.showNotifications([ { type: 'horde.warning', message: DIMP.text.popup_block } ]);
        }
    },

    closePopup: function()
    {
        // Mozilla bug/feature: it will not close a browser window
        // automatically if there is code remaining to be performed (or, at
        // least, not here) unless the mouse is moved or a keyboard event
        // is triggered after the callback is complete. (As of FF 2.0.0.3 and
        // 1.5.0.11).  So wait for the callback to complete before attempting
        // to close the window.
        if (this.inAjaxCallback) {
            this.closePopup.bind(this).defer();
        } else {
            window.close();
        }
    },

    logout: function(url)
    {
        this.is_logout = true;
        this.redirect(url || (DIMP.conf.URI_AJAX + 'logOut'));
    },

    // url = (string) URL to redirect to
    redirect: function(url)
    {
        window.location.assign(this.addURLParam(url));
    },

    loadingImg: function(elt, id, show)
    {
        elt = $(elt);

        if (show) {
            elt.clonePosition(id, { setHeight: false, setLeft: false, setWidth: false }).show();
        } else {
            elt.fade({ duration: 0.2 });
        }
    },

    toggleButtons: function(elts, disable)
    {
        elts.each(function(b) {
            var tmp;
            [ b.up() ].invoke(disable ? 'addClassName' : 'removeClassName', 'disabled');
            if (this.DMenu &&
                (tmp = b.next('.popdown'))) {
                this.DMenu.disable(tmp.identify(), true, disable);
            }
        }, this);
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
                id: p.identify(),
                left: true,
                offset: p.up(),
                type: t
            });
        }

        this.addContextMenu({
            disable: o.disabled,
            id: elt.identify(),
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
            this.DMenu.addElement(p.id, 'ctx_' + p.type, p);
        }
    },

    /* Add dropdown menus to addresses. */
    buildAddressLinks: function(alist, elt)
    {
        var base, tmp,
            cnt = alist.size();

        if (cnt > 15) {
            tmp = $('largeaddrspan').clone(true).addClassName('largeaddrspan_active');
            elt.insert(tmp);
            base = tmp.down('.dispaddrlist');
            tmp = tmp.down('.largeaddrlist');
            tmp.setText(tmp.getText().replace('%d', cnt));
        } else {
            base = elt;
        }

        alist.each(function(o, i) {
            var a;
            if (o.raw) {
                a = o.raw;
            } else {
                a = new Element('A', { className: 'address' }).store({ personal: o.personal, email: o.inner });
                if (o.personal) {
                    a.writeAttribute({ title: o.inner }).insert(o.personal.escapeHTML());
                } else if (o.inner) {
                    a.insert(o.inner.escapeHTML());
                }
                this.DMenu.addElement(a.identify(), 'ctx_contacts', { offset: a, left: true });
            }
            base.insert(a);
            if (i + 1 != cnt) {
                base.insert(', ');
            }
        }, this);

        return elt;
    },

    /* Add message log info to message view. */
    updateMsgLog: function(log)
    {
        var tmp = '';
        log.each(function(entry) {
            tmp += '<li><span class="iconImg imp-' + entry.t + '"></span>' + entry.m + '</li>';
        });
        $('msgloglist').down('UL').update(tmp);
    },

    /* Removes event handlers from address links. */
    removeAddressLinks: function(id)
    {
        id.select('.address').each(function(elt) {
            this.DMenu.removeElement(elt.identify());
        }, this);
    },

    addURLParam: function(url, params)
    {
        var q = url.indexOf('?');
        params = $H(params);

        if (DIMP.conf.SESSION_ID) {
            params.update(DIMP.conf.SESSION_ID.toQueryParams());
        }

        if (q != -1) {
            params.update(url.toQueryParams());
            url = url.substring(0, q);
        }

        return params.size() ? (url + '?' + params.toQueryString()) : url;
    },

    reloadMessage: function(params)
    {
        if (typeof DimpMessage != 'undefined') {
            window.location = this.addURLParam(document.location.href, params);
        } else {
            DimpBase.loadPreview(null, params);
        }
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
                tmp = elt.down();
                if (!tmp.next().visible() ||
                    e.element().hasClassName('largeaddrlist')) {
                    [ tmp.down(), tmp.down(1), tmp.next() ].invoke('toggle');
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
            if (e.element().retrieve('personal')) {
                $(e.memo)
                    .insert({ top: new Element('DIV', { className: 'sep' }) })
                    .insert({ top: new Element('DIV', { className: 'contactAddr' }).insert(e.element().retrieve('email').escapeHTML()) });
            }
            break;
        }
    },

    contextOnClick: function(e)
    {
        var baseelt = e.element();

        switch (e.memo.elt.readAttribute('id')) {
        case 'ctx_contacts_new':
            this.compose('new', { to: baseelt.retrieve('email'), toname: baseelt.retrieve('personal') });
            break;

        case 'ctx_contacts_add':
            this.doAction('addContact', { name: baseelt.retrieve('personal'), email: baseelt.retrieve('email') }, {}, true);
            break;
        }
    },

    /* DIMP initialization function. */
    init: function()
    {
        if (this.is_init) {
            return;
        }
        this.is_init = true;

        if (typeof ContextSensitive != 'undefined') {
            this.DMenu = new ContextSensitive();
            document.observe('ContextSensitive:click', this.contextOnClick.bindAsEventListener(this));
            document.observe('ContextSensitive:show', this.contextOnShow.bindAsEventListener(this));
        }

        /* Add Growler notification handler. */
        this.Growler = new Growler({
            location: 'br',
            log: this.growler_log,
            noalerts: DIMP.text.noalerts,
            info: DIMP.text.growlerinfo
        });

        if (this.growler_log) {
            this.Growler.growlerlog.observe('Growler:toggled', function(e) {
                $('alertsloglink')
                    .down('A')
                    .update(e.memo.visible ? DIMP.text.hidealog : DIMP.text.showalog);
            }.bindAsEventListener(this));
        }

        /* Add click handler. */
        document.observe('click', DimpCore.clickHandler.bindAsEventListener(DimpCore));

        /* Catch dialog actions. */
        document.observe('IMPDialog:success', function(e) {
            switch (e.memo) {
            case 'pgpPersonal':
            case 'pgpSymmetric':
            case 'smimePersonal':
                IMPDialog.noreload = true;
                this.reloadMessage({});
                break;
            }
        }.bindAsEventListener(this));

        /* Determine base window. Need a try/catch block here since, if the
         * page was loaded by an opener out of this current domain, this will
         * throw an exception. */
        try {
            if (parent.opener &&
                parent.opener.location.host == window.location.host &&
                parent.opener.DimpCore) {
                this.base = parent.opener.DimpCore.base || parent.opener;
            }
        } catch (e) {}
    }

};
