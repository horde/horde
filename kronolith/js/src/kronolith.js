/**
 * kronolith.js - Base application logic.
 * NOTE: ContextSensitive.js must be loaded before this file.
 *
 * $Horde$
 *
 * Copyright 2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Jan Schneider <jan@horde.org>
 */

/* Trick some Horde js into thinking this is the parent Horde window. */
var frames = { horde_main: true },

/* Kronolith object. */
KronolithCore = {

    view: '',
    remove_gc: [],

    debug: function(label, e)
    {
        if (!this.is_logout && Kronolith.conf.debug) {
            alert(label + ': ' + (e instanceof Error ? e.name + '-' + e.message : Object.inspect(e)));
        }
    },

    setTitle: function(title)
    {
        document.title = Kronolith.conf.name + ' :: ' + title;
    },

    showNotifications: function(msgs)
    {
        if (!msgs.size() || this.is_logout) {
            return;
        }

        msgs.find(function(m) {
            switch (m.type) {
            case 'kronolith.timeout':
                this.logout(Kronolith.conf.timeout_url);
                return true;

            case 'horde.error':
            case 'horde.message':
            case 'horde.success':
            case 'horde.warning':
            case 'kronolith.request':
            case 'kronolith.sticky':
                var clickdiv, fadeeffect, iefix, log, requestfunc, tmp,
                    alerts = $('alerts'),
                    div = new Element('DIV', { className: m.type.replace('.', '-') }),
                    msg = m.message;;

                if (!alerts) {
                    alerts = new Element('DIV', { id: 'alerts' });
                    $(document.body).insert(alerts);
                }

                if ($w('kronolith.request kronolith.sticky').indexOf(m.type) == -1) {
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
                if (Kronolith.conf.is_ie6) {
                    iefix = new Element('DIV', { className: 'ie6alertsfix' }).clonePosition(div, { setLeft: false, setTop: false });
                    clickdiv = iefix;
                    iefix.insert(div.remove());
                    alerts.insert(iefix);
                } else {
                    clickdiv = div;
                }

                fadeeffect = Effect.Fade.bind(this, div, { duration: 1.5, afterFinish: this.removeAlert.bind(this) });

                clickdiv.observe('click', fadeeffect);

                if ($w('horde.error kronolith.request kronolith.sticky').indexOf(m.type) == -1) {
                    fadeeffect.delay(m.type == 'horde.warning' ? 10 : 3);
                }

                if (m.type == 'kronolith.request') {
                    requestfunc = function() {
                        fadeeffect();
                        document.stopObserving('click', requestfunc)
                    };
                    document.observe('click', requestfunc);
                }

                if (tmp = $('alertslog')) {
                    switch (m.type) {
                    case 'horde.error':
                        log = Kronolith.text.alog_error;
                        break;

                    case 'horde.message':
                        log = Kronolith.text.alog_message;
                        break;

                    case 'horde.success':
                        log = Kronolith.text.alog_success;
                        break;

                    case 'horde.warning':
                        log = Kronolith.text.alog_warning;
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
            alink.update(Kronolith.text.showalog);
        } else {
            Effect.BlindDown(div, opts);
            alink.update(Kronolith.text.hidealog);
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

    logout: function(url)
    {
        this.is_logout = true;
        this.redirect(url || (Kronolith.conf.URI_IMP + '/LogOut'));
    },

    redirect: function(url)
    {
        url = this.addSID(url);
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

    /* Add a popdown menu to an actions button. */
    addPopdown: function(bid, ctx)
    {
        var bidelt = $(bid);
        bidelt.insert({ after: $($('popdown_img').cloneNode(false)).writeAttribute('id', bid + '_img').show() });
        this.addMouseEvents({ id: bid + '_img', type: ctx, offset: bidelt.up(), left: true });
    },

    /* Utility functions. */
    addGC: function(elt)
    {
        this.remove_gc = this.remove_gc.concat(elt);
    },

    // o: (object) Contains the following items:
    //    'd'  - (required) The DOM element
    //    'f'  - (required) The function to bind to the click event
    //    'ns' - (optional) If set, don't stop the event's propogation
    //    'p'  - (optional) If set, passes in the event object to the called
    //                      function
    clickObserveHandler: function(o)
    {
        return o.d.observe('click', KronolithCore._clickFunc.curry(o));
    },

    _clickFunc: function(o, e)
    {
        o.p ? o.f(e) : o.f();
        if (!o.ns) {
            e.stop();
        }
    },

    addSID: function(url)
    {
        if (!Kronolith.conf.SESSION_ID) {
            return url;
        }
        return this.addURLParam(url, Kronolith.conf.SESSION_ID.toQueryParams());
    },

    addURLParam: function(url, params)
    {
        var q = url.indexOf('?');

        if (q != -1) {
            params = $H(url.toQueryParams()).merge(params).toObject();
            url = url.substring(0, q);
        }
        return url + '?' + Object.toQueryString(params);
    },

    go: function(fullloc, data)
    {
        var app, f, separator;

        if (fullloc.startsWith('compose:')) {
            return;
        }

        /*
        $('dimpmain_portal').update(Kronolith.text.loading).show();

        if (loc.startsWith('app:')) {
            app = loc.substr(4);
            if (app == 'imp' || app == 'dimp') {
                this.go('folder:INBOX');
                return;
            }
            this.highlightSidebar('app' + app);
            this._addHistory(loc, data);
            if (data) {
                this.iframeContent(loc, data);
            } else if (Kronolith.conf.app_urls[app]) {
                this.iframeContent(loc, Kronolith.conf.app_urls[app]);
            }
            return;
        }
        */
        var locParts = fullloc.split(':');
        var loc = locParts.shift();

        switch (loc) {
        case 'day':
        case 'week':
        case 'month':
        case 'year':
        case 'agenda':
        case 'tasks':
            if (this.view == loc) {
                break;
            }

            var locCap = loc.capitalize();
            this._addHistory(fullloc);

            [ 'Day', 'Week', 'Month', 'Year', 'Tasks', 'Agenda' ].each(function(a) {
                $('kronolithNav' + a).removeClassName('on');
            });
            if (this.view) {
                $('kronolithView' + this.view.capitalize()).fade();
            }
            if ($('kronolithView' + locCap)) {
                $('kronolithView' + locCap).appear();
            }
            $('kronolithNav' + locCap).addClassName('on');

            switch (loc) {
            case 'day':
            case 'week':
            case 'month':
            case 'year':
                $('kronolithMinical').select('td').each(function(td) {
                    td.removeClassName('kronolithSelected');
                    if (locParts.length) {
                        if (!td.hasClassName('kronolithMinicalWeek') &&
                            (loc == 'month' ||
                             (loc == 'day' &&
                              td.readAttribute('date') == locParts[0]))) {
                            td.addClassName('kronolithSelected');
                        }
                    }
                });
                if (loc == 'week' && locParts.length) {
                    $('kronolithMinical').select('td').each(function(td) {
                        if (td.readAttribute('date') == locParts[0]) {
                            var tds = td.parentNode.childNodes;
                            for (i = 0; i < tds.length; i++) {
                                if (tds.item(i) != td &&
                                    tds.item(i).tagName == 'TD') {
                                    $(tds.item(i)).addClassName('kronolithSelected');
                                }
                            }
                            throw $break;
                        }
                    });
                }
                $('kronolithBody').select('div.kronolithEvent').each(function(s) {
                    KronolithCore.clickObserveHandler({ d: s, f: $('kronolithEventForm').appear.bind($('kronolithEventForm')) });
                    s.observe('mouseover', s.addClassName.curry('kronolithSelected'));
                    s.observe('mouseout', s.removeClassName.curry('kronolithSelected'));
                });

                break;
            }

            this.view = loc;
            break;

        case 'options':
            //this.highlightSidebar('appoptions');
            this._addHistory(loc);
            KronolithCore.setTitle(Kronolith.text.prefs);
            this.iframeContent(loc, Kronolith.conf.prefs_url);
            break;
        }
    },

    _addHistory: function(loc, data)
    {
        if (Horde.dhtmlHistory.getCurrentLocation() != loc) {
            Horde.dhtmlHistory.add(loc, data);
        }
    },

    iframeContent: function(name, loc)
    {
        if (name === null) {
            name = loc;
        }

        var container = $('dimpmain_portal'), iframe;
        if (!container) {
            KronolithCore.showNotifications([ { type: 'horde.error', message: 'Bad portal!' } ]);
            return;
        }

        iframe = new Element('IFRAME', { id: 'iframe' + name, className: 'iframe', frameBorder: 0, src: loc });
        this._resizeIE6Iframe(iframe);

        // Hide menu in prefs pages.
        if (name == 'options') {
            iframe.observe('load', function() { $('iframeoptions').contentWindow.document.getElementById('menu').style.display = 'none'; });
        }

        container.insert(iframe);
    },
    _onMenuShow: function(ctx)
    {
        var elts, folder, ob, sel;

        switch (ctx.ctx) {
        case 'ctx_folder':
            elts = $('ctx_folder_create', 'ctx_folder_rename', 'ctx_folder_delete');
            folder = KronolithCore.DMenu.element();
            if (folder.readAttribute('mbox') == 'INBOX') {
                elts.invoke('hide');
            } else if (Kronolith.conf.fixed_folders.indexOf(folder.readAttribute('mbox')) != -1) {
                elts.shift();
                elts.invoke('hide');
            } else {
                elts.invoke('show');
            }

            if (folder.hasAttribute('u')) {
                $('ctx_folder_poll').hide();
                $('ctx_folder_nopoll').show();
            } else {
                $('ctx_folder_poll').show();
                $('ctx_folder_nopoll').hide();
            }
            break;

        case 'ctx_message':
            [ $('ctx_message_reply_list') ].invoke(this.viewport.createSelection('domid', ctx.id).get('dataob').first().listmsg ? 'show' : 'hide');
            break;

        case 'ctx_reply':
            sel = this.viewport.getSelected();
            if (sel.size() == 1) {
                ob = sel.get('dataob').first();
            }
            [ $('ctx_reply_reply_list') ].invoke(ob && ob.listmsg ? 'show' : 'hide');
            break;

        case 'ctx_otheractions':
            $('oa_seen', 'oa_unseen', 'oa_flagged', 'oa_clear', 'oa_sep1', 'oa_blacklist', 'oa_whitelist', 'oa_sep2').compact().invoke(this.viewport.getSelected().size() ? 'show' : 'hide');
            break;
        }
        return true;
    },

    _onResize: function(noupdate, nowait)
    {
        if (this.viewport) {
            this.viewport.onResize(noupdate, nowait);
        }
        this._resizeIE6();
    },

    updateTitle: function()
    {
        var elt, label, unseen;
        if (this.viewport.isFiltering()) {
            label = Kronolith.text.search + ' :: ' + this.viewport.getMetaData('total_rows') + ' ' + Kronolith.text.resfound;
        } else {
            elt = $(this.getFolderId(this.folder));
            if (elt) {
                unseen = elt.readAttribute('u');
                label = elt.readAttribute('l');
                if (unseen > 0) {
                    label += ' (' + unseen + ')';
                }
            } else {
                label = this.viewport.getMetaData('label');
            }
        }
        KronolithCore.setTitle(label);
    },

    /* Keydown event handler */
    _keydownHandler: function(e)
    {
        // Only catch keyboard shortcuts in message list view. Disable catching
        // when in form elements or the RedBox overlay is visible.
        if (e.findElement('FORM') ||
            RedBox.overlayVisible()) {
            return;
        }

        var co, ps, r, row, rowoff,
            kc = e.keyCode || e.charCode;

        switch (kc) {
        case Event.KEY_ESC:
            $('kronolithEventForm').fade({ duration: 0.5 });
            break;

        case Event.KEY_DELETE:
        case Event.KEY_BACKSPACE:
            if (sel.size() == 1) {
                r = sel.get('dataob').first();
                if (e.shiftKey) {
                    this.moveSelected(r.rownum + ((r.rownum == this.viewport.getMetaData('total_rows')) ? -1 : 1), true);
                }
                this.flag('deleted', r);
            } else {
                this.flag('deleted');
            }
            e.stop();
            break;

        case Event.KEY_UP:
        case Event.KEY_DOWN:
            if (e.shiftKey && this.lastrow != -1) {
                row = this.viewport.createSelection('rownum', this.lastrow + ((kc == Event.KEY_UP) ? -1 : 1));
                if (row.size()) {
                    row = row.get('dataob').first();
                    this.viewport.scrollTo(row.rownum);
                    this.msgSelect(row.domid, { shift: true });
                }
            } else {
                this.moveSelected(kc == Event.KEY_UP ? -1 : 1);
            }
            e.stop();
            break;

        case Event.KEY_PAGEUP:
        case Event.KEY_PAGEDOWN:
            if (!e.ctrlKey && !e.shiftKey && !e.altKey && !e.metaKey) {
                ps = this.viewport.getPageSize() - 1;
                move = ps * (kc == Event.KEY_PAGEUP ? -1 : 1);
                if (sel.size() == 1) {
                    co = this.viewport.currentOffset();
                    rowoff = sel.get('rownum').first() - 1;
                    switch (kc) {
                    case Event.KEY_PAGEUP:
                        if (co != rowoff) {
                            move = co - rowoff;
                        }
                        break;

                    case Event.KEY_PAGEDOWN:
                        if ((co + ps) != rowoff) {
                            move = co + ps - rowoff;
                        }
                        break;
                    }
                }
                this.moveSelected(move);
                e.stop();
            }
            break;

        case Event.KEY_HOME:
        case Event.KEY_END:
            this.moveSelected(kc == Event.KEY_HOME ? 1 : this.viewport.getMetaData('total_rows'), true);
            e.stop();
            break;

        case Event.KEY_RETURN:
            if (!e.element().match('input')) {
                // Popup message window if single message is selected.
                if (sel.size() == 1) {
                    this.msgWindow(sel.get('dataob').first());
                }
            }
            e.stop();
            break;

        case 65: // A
        case 97: // a
            if (e.ctrlKey) {
                this.selectAll();
                e.stop();
            }
            break;
        }
    },

    _closeRedBox: function()
    {
        var c = RedBox.getWindowContents();
        KronolithCore.addGC([ c, c.descendants() ].flatten());
        RedBox.close();
    },

    /* Onload function. */
    _onLoad: function() {
        var tmp,
             C = KronolithCore.clickObserveHandler,
             dmenu = KronolithCore.DMenu;

        if (Horde.dhtmlHistory.initialize()) {
            Horde.dhtmlHistory.addListener(this.go.bind(this));
        }

        /* Initialize the starting page if necessary. addListener() will have
         * already fired if there is a current location so only do a go()
         * call if there is no current location. */
        if (!Horde.dhtmlHistory.getCurrentLocation()) {
            this.go(Kronolith.conf.login_view);
        }

        /* Add popdown menus. */
        /*
        KronolithCore.addPopdown('button_reply', 'reply');
        dmenu.disable('button_reply_img', true, true);
        KronolithCore.addPopdown('button_forward', 'forward');
        dmenu.disable('button_forward_img', true, true);
        KronolithCore.addPopdown('button_other', 'otheractions');
        */

        /* Set up click event observers for elements on main page. */
        tmp = $('kronolithLogo');
        if (tmp.visible()) {
            C({ d: tmp.down('a'), f: this.go.bind(this, 'portal') });
        }

        C({ d: $('id_fullday'), f: function() { $('kronolithEventForm').select('.edit_at').each(Element.toggle); } });

        C({ d: $('kronolithNewEvent'), f: KronolithCore.editEvent });

        $('kronolithEventActions').select('input.button').each(function(s) {
	    C({ d: s, f: function() { Effect.Fade('kronolithEventForm');} });
        });

        $('kronolithEventForm').select('div.kronolithTags span').each(function(s) {
	    $('id_tags').value = $F('id_tags') + s.getText() + ', ';
        });

        [ 'Day', 'Week', 'Month', 'Year', 'Tasks', 'Agenda' ].each(function(a) {
            C({ d: $('kronolithNav' + a), f: KronolithCore.go.bind(KronolithCore, a.toLowerCase()) });
        });

        $('kronolithMenu').select('div.kronolithCalendars div').each(function(s) {
            C({ d: s, f: KronolithCore.toggleCalendar.bind(KronolithCore, s) });
            s.observe('mouseover', s.addClassName.curry('kronolithCalOver'));
            s.observe('mouseout', s.removeClassName.curry('kronolithCalOver'));
        });

        C({ d: $('kronolithMinicalDate'), f: KronolithCore.go.bind(KronolithCore, 'month:' + $('kronolithMinicalDate').readAttribute('date')) });
        $('kronolithMinical').select('td').each(function(td) {
            C({ d: td, f: function() {
                if (td.hasClassName('kronolithMinicalWeek')) {
		    KronolithCore.go('week:' + td.readAttribute('date'));
		} else if (!td.hasClassName('empty')) {
		    KronolithCore.go('day:' + td.readAttribute('date'));
		}
            }});
        });

        /* Set up click event observers for elements on month view. */
        $('kronolithViewMonth').select('.kronolithFirstCol').each(function(l) {
            C({ d: l, f: KronolithCore.go.bind(KronolithCore, 'week') });
        });
        $('kronolithViewMonth').select('.kronolithDay').each(function(l) {
            C({ d: l, f: KronolithCore.go.bind(KronolithCore, 'day') });
        });

        /*
        C({ d: $('composelink'), f: KronolithCore.compose.bind(KronolithCore, 'new') });
        C({ d: $('checkmaillink'), f: this.pollFolders.bind(this) });

        [ 'portal', 'options' ].each(function(a) {
            var d = $('app' + a);
            if (d) {
                C({ d: d, f: this.go.bind(this, a) });
            }
        }, this);
        tmp = $('applogout');
        if (tmp) {
            C({ d: tmp, f: function() { $('applogout').down('A').update('[' + KronolithText.onlogout + ']'); KronolithCore.logout(); } });
        }

        tmp = $('applicationfolders');
        if (tmp) {
            tmp.select('li.custom a').each(function(s) {
                C({ d: s, f: this.go.bind(this, 'app:' + s.readAttribute('app')) });
            }, this);
        }

        C({ d: $('newfolder'), f: this.createBaseFolder.bind(this) });
        new Drop('dropbase', this._folderDropConfig);
        tmp = $('hometab');
        if (tmp) {
            C({ d: tmp, f: this.go.bind(this, 'portal') });
        }
        $('tabbar').select('a.applicationtab').each(function(a) {
            C({ d: a, f: this.go.bind(this, 'app:' + a.readAttribute('app')) });
        }, this);
        C({ d: $('button_reply'), f: this.composeMailbox.bind(this, 'reply'), ns: true });
        C({ d: $('button_forward'), f: this.composeMailbox.bind(this, Kronolith.conf.forward_default), ns: true });
        [ 'spam', 'ham', 'deleted' ].each(function(a) {
            var d = $('button_' + a);
            if (d) {
                C({ d: d, f: this.flag.bind(this, a) });
            }
        }, this);
        C({ d: $('button_compose').down('A'), f: KronolithCore.compose.bind(KronolithCore, 'new') });
        C({ d: $('button_other'), f: function(e) { dmenu.trigger(e.findElement('A').next(), true); }, p: true });
        C({ d: $('qoptions').down('.qclose a'), f: this.searchfilterClear.bind(this, false) });
        [ 'all', 'current' ].each(function(a) {
            var d = $('sf_' + a);
            if (d) {
                C({ d: d, f: this.updateSearchfilter.bind(this, a, 'folder') });
            }
        }, this);
        [ 'msgall', 'from', 'to', 'subject' ].each(function(a) {
            C({ d: $('sf_' + a), f: this.updateSearchfilter.bind(this, a, 'msg') });
        }, this);
        C({ d: $('msglistHeader'), f: this.sort.bind(this), p: true });
        C({ d: $('ctx_folder_create'), f: function() { this.createSubFolder(dmenu.element()); }.bind(this), ns: true });
        C({ d: $('ctx_folder_rename'), f: function() { this.renameFolder(dmenu.element()); }.bind(this), ns: true });
        C({ d: $('ctx_folder_empty'), f: function() { var mbox = dmenu.element().readAttribute('mbox'); dmenu.close(true); if (window.confirm(Kronolith.text.empty_folder)) { KronolithCore.doAction('EmptyFolder', { folder: mbox }, null, this._emptyFolderCallback.bind(this)); } }.bind(this), ns: true });
        C({ d: $('ctx_folder_delete'), f: function() { var mbox = dmenu.element().readAttribute('mbox'); dmenu.close(true); if (window.confirm(Kronolith.text.delete_folder)) { KronolithCore.doAction('DeleteFolder', { folder: mbox }, null, this.bcache.get('folderC') || this.bcache.set('folderC', this._folderCallback.bind(this))); } }.bind(this), ns: true });
        [ 'ctx_folder_seen', 'ctx_folder_unseen' ].each(function(a) {
            C({ d: $(a), f: function(type) { this.flag(type, null, dmenu.element().readAttribute('mbox')); }.bind(this, a == 'ctx_folder_seen' ? 'allSeen' : 'allUnseen'), ns: true });
        }, this);
        [ 'ctx_folder_poll', 'ctx_folder_nopoll' ].each(function(a) {
            C({ d: $(a), f: function(modify) { this.modifyPollFolder(dmenu.element().readAttribute('mbox'), modify); }.bind(this, a == 'ctx_folder_poll'), ns: true });
        }, this);
        C({ d: $('ctx_container_create'), f: function() { this.createSubFolder(dmenu.element()); }.bind(this), ns: true });
        C({ d: $('ctx_container_rename'), f: function() { this.renameFolder(dmenu.element()); }.bind(this), ns: true });
        [ 'reply', 'reply_all', 'reply_list', 'forward_all', 'forward_body', 'forward_attachments' ].each(function(a) {
            C({ d: $('ctx_message_' + a), f: this.composeMailbox.bind(this, a), ns: true });
        }, this);
        [ 'seen', 'unseen', 'flagged', 'clear', 'spam', 'ham', 'blacklist', 'whitelist', 'deleted', 'undeleted' ].each(function(a) {
            var d = $('ctx_message_' + a);
            if (d) {
                C({ d: d, f: this.flag.bind(this, a), ns: true });
            }
        }, this);
        C({ d: $('ctx_draft_resume'), f: this.composeMailbox.bind(this, 'resume') });
        [ 'flagged', 'clear', 'deleted', 'undeleted' ].each(function(a) {
            var d = $('ctx_draft_' + a);
            if (d) {
                C({ d: d, f: this.flag.bind(this, a), ns: true });
            }
        }, this);
        [ 'reply', 'reply_all', 'reply_list' ].each(function(a) {
            C({ d: $('ctx_reply_' + a), f: this.composeMailbox.bind(this, a), ns: true });
        }, this);
        [ 'forward_all', 'forward_body', 'forward_attachments' ].each(function(a) {
            C({ d: $('ctx_forward_' + a), f: this.composeMailbox.bind(this, a), ns: true });
        }, this);
        C({ d: $('previewtoggle'), f: this.togglePreviewPane.bind(this), ns: true });
        [ 'seen', 'unseen', 'flagged', 'clear', 'blacklist', 'whitelist' ].each(function(a) {
            var d = $('oa_' + a);
            if (d) {
                C({ d: d, f: this.flag.bind(this, a), ns: true });
            }
        }, this);
        C({ d: $('oa_selectall'), f: this.selectAll.bind(this), ns: true });

        tmp = $('oa_purge_deleted');
        if (tmp) {
            C({ d: tmp, f: this.purgeDeleted.bind(this), ns: true });
        }

        $('toggleHeaders').select('A').each(function(a) {
            C({ d: a, f: function() { [ a.up().select('A'), $('msgHeadersColl', 'msgHeaders') ].flatten().invoke('toggle'); }, ns: true });
        });
        $('msg_newwin', 'msg_newwin_options').compact().each(function(a) {
            C({ d: a, f: function() { this.msgWindow(this.viewport.getViewportSelection().search({ imapuid: { equal: [ Kronolith.conf.msg_index ] } , view: { equal: [ Kronolith.conf.msg_folder ] } }).get('dataob').first()); }.bind(this) });
        }, this);
        */

        this._resizeIE6();
    },

    // IE 6 width fixes (See Bug #6793)
    _resizeIE6: function()
    {
        // One width to rule them all:
        // 20 label, 2 label border, 2 label margin, 16 scrollbar,
        // 7 cols, 2 col border, 2 col margin
        var col_width = (($('kronolithViewMonth').getWidth()-20-2-2-16)/7)-2-2;
        $('kronolithViewMonth').select('.kronolithCol').invoke('setStyle', { width: col_width + 'px' });

        // Set month dimensions.
        // 6 rows, 2 row border, 2 row margin
        var col_height = (($('kronolithViewMonth').getHeight()-25)/6)-2-2;
        $('kronolithViewMonth').select('.kronolithViewBody .kronolithCol').invoke('setStyle', { height: col_height + 'px' });
        $('kronolithViewMonth').select('.kronolithViewBody .kronolithFirstCol').invoke('setStyle', { height: col_height + 'px' });

        // Set week dimensions.
        $('kronolithViewWeek').select('.kronolithCol').invoke('setStyle', { width: (col_width - 1) + 'px' });

        // Set day dimensions.
        // 20 label, 2 label border, 2 label margin, 16 scrollbar, 2 col border
        var head_col_width = $('kronolithViewDay').getWidth()-20-2-2-16-3;
        // 20 label, 2 label border, 2 label margin, 16 scrollbar, 2 col border
        // 7 cols
        var col_width = ((head_col_width+7)/7)-1;
        $('kronolithViewDay').select('.kronolithViewHead .kronolithCol').invoke('setStyle', { width: head_col_width + 'px' });
        $('kronolithViewDay').select('.kronolithViewBody .kronolithCol').invoke('setStyle', { width: col_width + 'px' });
        $('kronolithViewDay').select('.kronolithViewBody .kronolithAllDay .kronolithCol').invoke('setStyle', { width: head_col_width + 'px' });

        /*
        if (Kronolith.conf.is_ie6) {
            var tmp = parseInt($('sidebarPanel').getStyle('width'), 10),
                tmp1 = document.viewport.getWidth() - tmp - 30;
            $('normalfolders').setStyle({ width: tmp + 'px' });
            $('kronlithmain').setStyle({ width: tmp1 + 'px' });
            $('msglist').setStyle({ width: (tmp1 - 5) + 'px' });
            $('msgBody').setStyle({ width: (tmp1 - 25) + 'px' });
            tmp = $('dimpmain_portal').down('IFRAME');
            if (tmp) {
                this._resizeIE6Iframe(tmp);
            }
        }
        */
    },

    _resizeIE6Iframe: function(iframe)
    {
        if (Kronolith.conf.is_ie6) {
            iframe.setStyle({ width: $('kronolithmain').getStyle('width'), height: (document.viewport.getHeight() - 20) + 'px' });
        }
    },

    editEvent: function()
    {
        $('kronolithEventForm').appear({ duration: 0.5 });
    },

    toggleCalendar: function(elm)
    {
        if (elm.hasClassName('on')) {
            elm.removeClassName('on');
        } else {
            elm.addClassName('on');
        }
    }

};

// Initialize DMenu now.  Need to init here because IE doesn't load dom:loaded
// in a predictable order.
if (typeof ContextSensitive != 'undefined') {
    KronolithCore.DMenu = new ContextSensitive();
}

document.observe('dom:loaded', function() {
    /* Don't do additional onload stuff if we are in a popup. We need a
     * try/catch block here since, if the page was loaded by an opener
     * out of this current domain, this will throw an exception. */
    try {
        if (parent.opener &&
            parent.opener.location.host == window.location.host &&
            parent.opener.KronolithCore) {
            Kronolith.baseWindow = parent.opener.Kronolith.baseWindow || parent.opener;
        }
    } catch (e) {}

    /* Init garbage collection function - runs every 10 seconds. */
    new PeriodicalExecuter(function() {
        if (KronolithCore.remove_gc.size()) {
            try {
                $A(KronolithCore.remove_gc.splice(0, 75)).compact().invoke('stopObserving');
            } catch (e) {
                KronolithCore.debug('remove_gc[].stopObserving', e);
            }
        }
    }, 10);

    //$('kronolithLoading').hide();
    //$('kronolithPage').show();

    /* Start message list loading as soon as possible. */
    KronolithCore._onLoad();

    /* Bind key shortcuts. */
    document.observe('keydown', KronolithCore._keydownHandler.bind(KronolithCore));

    /* Resize elements on window size change. */
    Event.observe(window, 'resize', KronolithCore._onResize.bind(KronolithCore));

    if (Kronolith.conf.is_ie6) {
        /* Disable text selection in preview pane for IE 6. */
        document.observe('selectstart', Event.stop);

        /* Since IE 6 doesn't support hover over non-links, use javascript
         * events to replicate mouseover CSS behavior. */
        $('foobar').compact().invoke('select', 'LI').flatten().compact().each(function(e) {
            e.observe('mouseover', e.addClassName.curry('over')).observe('mouseout', e.removeClassName.curry('over'));
        });
    }
});

Event.observe(window, 'load', function() {
    KronolithCore.window_load = true;
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
        return this.sort(function(a, b) {
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
