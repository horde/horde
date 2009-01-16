/**
 * DimpCore.js - Dimp UI application logic.
 * NOTE: ContextSensitive.js must be loaded before this file.
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

/* Trick some Horde js into thinking this is the parent Horde window. */
var frames = { horde_main: true },

/* DimpCore object. */
DimpCore = {
    // Vars used and defaulting to null/false:
    //   DMenu, inAjaxCallback, is_logout, onDoActionComplete, window_load
    acount: 0,
    remove_gc: [],
    server_error: 0,

    buttons: [ 'button_reply', 'button_forward', 'button_spam', 'button_ham', 'button_deleted' ],

    debug: function(label, e)
    {
        if (!this.is_logout && DIMP.conf.debug) {
            alert(label + ': ' + (e instanceof Error ? e.name + '-' + e.message : Object.inspect(e)) + (e.lineNumber ? ' (Line #' + e.lineNumber + ')' : ''));
        }
    },

    // Convert object to an IMP UID Range string. See IMP::toRangeString()
    // ob = (object) mailbox name as keys, values are array of uids.
    toRangeString: function(ob)
    {
        var str = '';

        $H(ob).each(function(o) {
            if (!o.value.size()) {
                return;
            }

            var u = o.value.numericSort(),
                first = last = u.shift(),
                out = [];

            u.each(function(k) {
                if (last + 1 == k) {
                    last = k;
                } else {
                    out.push(first + (last == first ? '' : (':' + last)));
                    first = last = k;
                }
            });
            out.push(first + (last == first ? '' : (':' + last)));
            str += '{' + o.key.length + '}' + o.key + out.join(',');
        });

        return str;
    },

    // Parses an IMP UID Range string. See IMP::parseRangeString()
    // str = (string) An IMP UID range string.
    parseRangeString: function(str)
    {
        var count, end, i, mbox,
            mlist = {},
            uids = [];
        str = str.strip();

        while (!str.blank()) {
            if (!str.startsWith('{')) {
                break;
            }
            i = str.indexOf('}');
            count = Number(str.substr(1, i - 1));
            mbox = str.substr(i + 1, count);
            i += count + 1;
            end = str.indexOf('{', i);
            if (end == -1) {
                uidstr = str.substr(i);
                str = '';
            } else {
                uidstr = str.substr(i, end - i);
                str = str.substr(end);
            }

            uidstr.split(',').each(function(e) {
                var r = e.split(':');
                if (r.size() == 1) {
                    uids.push(Number(e));
                } else {
                    uids = uids.concat($A($R(Number(r[0]), Number(r[1]))));
                }
            });

            mlist[mbox] = uids;
        }

        return mlist;
    },

    /* 'action' -> if action begins with a '*', the exact string will be used
     *  instead of sending the action to the IMP handler. */
    doAction: function(action, params, uids, callback, opts)
    {
        var b, tmp = {};

        if (!this.doActionOpts) {
            this.doActionOpts = {
                onException: function(r, e) {
                    this.debug('onException', e);
                }.bind(this),
                onFailure: function(t, o) {
                    this.debug('onFailure', t);
                }.bind(this),
                evalJS: false,
                evalJSON: true
            };
        };

        opts = Object.extend(this.doActionOpts, opts || {});
        params = $H(params);
        action = action.startsWith('*')
            ? action.substring(1)
            : DIMP.conf.URI_IMP + '/' + action;
        if (uids) {
            if (uids.viewport_selection) {
                b = uids.getBuffer();
                if (b.getMetaData('search')) {
                    uids.get('dataob').each(function(r) {
                        if (!tmp[r.view]) {
                            tmp[r.view] = [];
                        }
                        tmp[r.view].push(r.imapuid);
                    });
                } else {
                    tmp[b.getView()] = uids.get('uid');
                }
                uids = tmp;
            }
            params.set('uid', this.toRangeString(uids));
        }
        if (DIMP.conf.SESSION_ID) {
            params.update(DIMP.conf.SESSION_ID.toQueryParams());
        }
        opts.parameters = params.toQueryString();
        opts.onComplete = function(t, o) { this.doActionComplete(t, callback); }.bind(this);
        new Ajax.Request(action, opts);
    },

    doActionComplete: function(request, callback)
    {
        this.inAjaxCallback = true;
        var r;

        if (!request.responseJSON) {
            if (++this.server_error == 3) {
                this.showNotifications([ { type: 'horde.error', message: DIMP.text.ajax_timeout } ]);
            }
            this.inAjaxCallback = false;
            return;
        }

        r = request.responseJSON;

        if (!r.msgs) {
            r.msgs = [];
        }

        if (r.response && Object.isFunction(callback)) {
            if (DIMP.conf.debug) {
                callback(r);
            } else {
                try {
                    callback(r);
                } catch (e) {}
            }
        }

        if (this.server_error >= 3) {
            r.msgs.push({ type: 'horde.success', message: DIMP.text.ajax_recover });
        }
        this.server_error = 0;

        if (!r.msgs_noauto) {
            this.showNotifications(r.msgs);
        }

        if (this.onDoActionComplete) {
            this.onDoActionComplete(r);
        }

        this.inAjaxCallback = false;
    },

    setTitle: function(title)
    {
        document.title = DIMP.conf.name + ' :: ' + title;
    },

    showNotifications: function(msgs)
    {
        if (!msgs.size() || this.is_logout) {
            return;
        }

        msgs.find(function(m) {
            switch (m.type) {
            case 'dimp.timeout':
                this.logout(DIMP.conf.timeout_url);
                return true;

            case 'horde.error':
            case 'horde.message':
            case 'horde.success':
            case 'horde.warning':
            case 'imp.reply':
            case 'imp.forward':
            case 'imp.redirect':
            case 'dimp.request':
            case 'dimp.sticky':
                var clickdiv, fadeeffect, iefix, log, requestfunc, tmp,
                    alerts = $('alerts'),
                    div = new Element('DIV', { className: m.type.replace('.', '-') }),
                    msg = m.message;;

                if (!alerts) {
                    alerts = new Element('DIV', { id: 'alerts' });
                    $(document.body).insert(alerts);
                }

                if ($w('dimp.request dimp.sticky').indexOf(m.type) == -1) {
                    msg = msg.unescapeHTML().unescapeHTML();
                }
                alerts.insert(div.update(msg));

                // IE6 has a bug that does not allow the body of a div to be
                // clicked to trigger an onclick event for that div (it only
                // seems to be an issue if the div is overlaying an element
                // that itself contains an image).  However, the alert box
                // normally displays over the message list, and we use several
                // graphics in the default message list layout, so we see this
                // buggy behavior 99% of the time.  The workaround is to
                // overlay the div with a like sized div containing a clear
                // gif, which tricks IE into the correct behavior.
                if (DIMP.conf.is_ie6) {
                    iefix = new Element('DIV', { className: 'ie6alertsfix' }).clonePosition(div, { setLeft: false, setTop: false });
                    clickdiv = iefix;
                    iefix.insert(div.remove());
                    alerts.insert(iefix);
                } else {
                    clickdiv = div;
                }

                fadeeffect = Effect.Fade.bind(this, div, { duration: 1.5, afterFinish: this.removeAlert.bind(this) });

                clickdiv.observe('click', fadeeffect);

                if ($w('horde.error dimp.request dimp.sticky').indexOf(m.type) == -1) {
                    fadeeffect.delay(m.type == 'horde.warning' ? 10 : 3);
                }

                if (m.type == 'dimp.request') {
                    requestfunc = function() {
                        fadeeffect();
                        document.stopObserving('click', requestfunc)
                    };
                    document.observe('click', requestfunc);
                }

                if (tmp = $('alertslog')) {
                    switch (m.type) {
                    case 'horde.error':
                        log = DIMP.text.alog_error;
                        break;

                    case 'horde.message':
                        log = DIMP.text.alog_message;
                        break;

                    case 'horde.success':
                        log = DIMP.text.alog_success;
                        break;

                    case 'horde.warning':
                        log = DIMP.text.alog_warning;
                        break;
                    }

                    if (log) {
                        tmp = tmp.down('DIV UL');
                        if (tmp.down().hasClassName('noalerts')) {
                            tmp.down().remove();
                        }
                        tmp.insert(new Element('LI').insert(new Element('P', { className: 'label' }).insert(log)).insert(new Element('P', { className: 'indent' }).insert(msg).insert(new Element('SPAN', { className: 'alertdate'}).insert('[' + (new Date).toLocaleString() + ']'))));
                    }
                }
            }
        }, this);
    },

    toggleAlertsLog: function()
    {
        var alink = $('alertsloglink').down('A'),
            div = $('alertslog').down('DIV'),
            opts = { duration: 0.5 };
        if (div.visible()) {
            Effect.BlindUp(div, opts);
            alink.update(DIMP.text.showalog);
        } else {
            Effect.BlindDown(div, opts);
            alink.update(DIMP.text.hidealog);
        }
    },

    removeAlert: function(effect)
    {
        try {
            var elt = $(effect.element),
                parent = elt.up();
            // We may have already removed this element from the DOM tree
            // (if the user clicked on the notification), so check parentNode
            // here - will return null if node is not part of DOM tree.
            if (parent && parent.parentNode) {
                this.addGC(elt.remove());
                if (!parent.childElements().size() &&
                    parent.hasClassName('ie6alertsfix')) {
                    this.addGC(parent.remove());
                }
            }
        } catch (e) {
            this.debug('removeAlert', e);
        }
    },

    compose: function(type, args)
    {
        var url = DIMP.conf.compose_url;
        args = args || {};
        if (type) {
            args.type = type;
        }
        this.popupWindow(this.addURLParam(url, args), 'compose' + new Date().getTime());
    },

    popupWindow: function(url, name)
    {
        if (!(window.open(url, name.replace(/\W/g, '_'), 'width=' + DIMP.conf.popup_width + ',height=' + DIMP.conf.popup_height + ',status=1,scrollbars=yes,resizable=yes'))) {
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
        this.redirect(url || (DIMP.conf.URI_IMP + '/LogOut'));
    },

    redirect: function(url)
    {
        url = this.addURLParam(url);
        if (parent.frames.horde_main) {
            parent.location = url;
        } else {
            window.location = url;
        }
    },

    /* Add/remove mouse events on the fly.
     * Parameter: object with the following names - id, type, offset
     *   (optional), left (optional), onShow (optional)
     * Valid types:
     *   'message', 'draft'  --  Message list rows
     *   'container', 'special', 'folder'  --  Folders
     *   'reply', 'forward', 'otheractions'  --  Message list buttons
     *   'contacts'  --  Linked e-mail addresses */
    addMouseEvents: function(p)
    {
        this.DMenu.addElement(p.id, 'ctx_' + p.type, p);
    },

    /* elt = DOM element */
    removeMouseEvents: function(elt)
    {
        this.DMenu.removeElement($(elt).readAttribute('id'));
        this.addGC(elt);
    },

    /* Add a popdown menu to a dimpactions button. */
    addPopdown: function(bid, ctx)
    {
        var bidelt = $(bid);
        bidelt.insert({ after: $($('popdown_img').cloneNode(false)).writeAttribute('id', bid + '_img').show() });
        this.addMouseEvents({ id: bid + '_img', type: ctx, offset: bidelt.up(), left: true });
    },

    /* Add dropdown menus to addresses. */
    buildAddressLinks: function(alist, elt)
    {
        var base, tmp,
            cnt = alist.size();

        if (cnt > 15) {
            tmp = $('largeaddrspan').cloneNode(true);
            elt.insert(tmp);
            base = tmp.down('.dispaddrlist');
            tmp = tmp.down();
            this.clickObserveHandler({ d: tmp, f: function(d) { [ d.down(), d.down(1), d.next() ].invoke('toggle'); }.curry(tmp) });
            tmp = tmp.down();
            tmp.setText(tmp.getText().replace('%d', cnt));
        } else {
            base = elt;
        }

        alist.each(function(o, i) {
            var a, t;
            if (o.raw) {
                a = o.raw;
            } else {
                t = o.personal ? (o.personal + ' <' + o.inner + '>') : o.inner;
                a = new Element('A', { className: 'address', id: 'addr' + this.acount++, personal: o.personal, email: o.inner, address: t }).insert(t.escapeHTML());
                a.observe('mouseover', function() { a.stopObserving('mouseover'); this.addMouseEvents({ id: a.id, type: 'contacts', offset: a, left: true }); }.bind(this));
            }
            base.insert(a);
            if (i + 1 != cnt) {
                base.insert(', ');
            }
        }, this);

        return elt;
    },

    /* Removes event handlers from address links. */
    removeAddressLinks: function(id)
    {
        [ id.select('.address'), id.select('.largeaddrtoggle') ].flatten().compact().each(this.removeMouseEvents.bind(this));
    },

    /* Utility functions. */
    addGC: function(elt)
    {
        this.remove_gc = this.remove_gc.concat(elt);
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
        if (typeof DimpFullmessage != 'undefined') {
            window.location = this.addURLParam(document.location.href, params);
        } else {
            DimpBase.loadPreview(null, params);
        }
    },

    /* Mouse click handler. */
    _clickHandler: function(e)
    {
        if (e.isRightClick()) {
            return;
        }

        var elt = e.element(), id, tmp;

        while (Object.isElement(elt)) {
            id = elt.readAttribute('id');

            switch (id) {
            case 'partlist_toggle':
                tmp = $('partlist');
                $('partlist_col', 'partlist_exp').invoke('toggle');
                if (tmp.visible()) {
                    Effect.BlindUp(tmp, { duration: 0.2 });
                } else {
                    Effect.BlindDown(tmp, { duration: 0.2 });
                }
                e.stop();
                return;

            case 'msg_print':
                window.print();
                e.stop();
                return;

            case 'msg_view_source':
                this.popupWindow(this.addURLParam(DIMP.conf.URI_VIEW, { index: DIMP.conf.msg_index, mailbox: DIMP.conf.msg_folder, actionID: 'view_source', id: 0 }, true), DIMP.conf.msg_index + '|' + DIMP.conf.msg_folder);
                break;

            case 'ctx_contacts_new':
                this.compose('new', { to: this.DMenu.element().readAttribute('address') });
                break;

            case 'ctx_contacts_add':
                this.doAction('AddContact', { name: this.DMenu.element().readAttribute('personal'), email: this.DMenu.element().readAttribute('email') }, null, true);
                break;

            case 'alertsloglink':
                this.toggleAlertsLog();
                break;
            }

            elt = elt.up();
        }
    }
};

// Initialize DMenu now.  Need to init here because IE doesn't load dom:loaded
// in a predictable order.
if (typeof ContextSensitive != 'undefined') {
    DimpCore.DMenu = new ContextSensitive();
}

document.observe('dom:loaded', function() {
    /* Don't do additional onload stuff if we are in a popup. We need a
     * try/catch block here since, if the page was loaded by an opener
     * out of this current domain, this will throw an exception. */
    try {
        if (parent.opener &&
            parent.opener.location.host == window.location.host &&
            parent.opener.DimpCore) {
            DIMP.baseWindow = parent.opener.DIMP.baseWindow || parent.opener;
        }
    } catch (e) {}

    /* Remove unneeded buttons. */
    if (!DIMP.conf.spam_reporting) {
        DimpCore.buttons = DimpCore.buttons.without('button_spam');
    }
    if (!DIMP.conf.ham_reporting) {
        DimpCore.buttons = DimpCore.buttons.without('button_ham');
    }

    /* Init garbage collection function - runs every 10 seconds. */
    new PeriodicalExecuter(function() {
        if (DimpCore.remove_gc.size()) {
            try {
                $A(DimpCore.remove_gc.splice(0, 75)).compact().invoke('stopObserving');
            } catch (e) {
                DimpCore.debug('remove_gc[].stopObserving', e);
            }
        }
    }, 10);

    /* Add click handler. */
    document.observe('click', DimpCore._clickHandler.bindAsEventListener(DimpCore));
});

Event.observe(window, 'load', function() {
    DimpCore.window_load = true;
});

/* Helper methods for setting/getting element text without mucking
 * around with multiple TextNodes. */
Element.addMethods({
    setText: function(element, text)
    {
        var t = 0;
        $A(element.childNodes).each(function(node) {
            if (node.nodeType == 3) {
                if (t++) {
                    Element.remove(node);
                } else {
                    node.nodeValue = text;
                }
            }
        });

        if (!t) {
            $(element).insert(text);
        }
    },

    getText: function(element, recursive)
    {
        var text = '';
        $A(element.childNodes).each(function(node) {
            if (node.nodeType == 3) {
                text += node.nodeValue;
            } else if (recursive && node.hasChildNodes()) {
                text += $(node).getText(true);
            }
        });
        return text;
    }
});

/* Create some utility functions. */
Object.extend(Array.prototype, {
    numericSort: function()
    {
        return this.collect(Number).sort(function(a, b) {
            if (a > b) {
                return 1;
            } else if (a < b) {
                return -1;
            }
            return 0;
        });
    }
});

Object.extend(String.prototype, {
    // We define our own version of evalScripts() to make sure that all
    // scripts are running in the same scope and that all functions are
    // defined in the global scope. This is not the case when using
    // prototype's evalScripts().
    evalScripts: function()
    {
        var re = /function\s+([^\s(]+)/g;
        this.extractScripts().each(function(s) {
            var func;
            eval(s);
            while (func = re.exec(s)) {
                window[func[1]] = eval(func[1]);
            }
        });
    }
});
