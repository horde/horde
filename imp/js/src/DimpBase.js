/**
 * DimpBase.js - Javascript used in the base DIMP page.
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

var DimpBase = {
    // Vars used and defaulting to null/false:
    //   cfolderaction, filter_on, filtertoggle, fl_visible, folder,
    //   folderswitch, fspecial, isvisible, message_list_template, offset,
    //   pollPE, pp, searchobserve, uid, viewport
    bcache: $H(),
    cacheids: {},
    lastrow: -1,
    pivotrow: -1,
    ppcache: {},
    ppfifo: [],

    sfiltersfolder: $H({
        sf_all: 'all',
        sf_current: 'current'
    }),

    sfilters: $H({
        sf_msgall: 'msgall',
        sf_from: 'from',
        sf_to: 'to',
        sf_subject: 'subject'
    }),

    flags: $H({
        unseen: 'Unseen',
        flagged: 'Flagged',
        deletedmsg: 'Deleted',
        unimportant: 'LowPriority',
        important: 'HighPriority',
        answered: 'Answered',
        forwarded: 'Forwarded',
        draft: 'Draft'
    }),

    // Message selection functions

    // vs = (ViewPort_Selection) A ViewPort_Selection object.
    // opts = (object) Boolean options [delay, right]
    _select: function(vs, opts)
    {
        var d = vs.get('rownum');
        if (d.size() == 1) {
            this.lastrow = this.pivotrow = d.first();
        }

        this.toggleButtons();

        if ($('previewPane').visible()) {
            if (opts.right) {
                this.clearPreviewPane();
            } else {
                if (opts.delay) {
                    (this.bcache.get('initPP') || this.bcache.set('initPP', this.initPreviewPane.bind(this))).delay(opts.delay);
                } else {
                    this.initPreviewPane();
                }
            }
        }
    },

    // vs = (ViewPort_Selection) A ViewPort_Selection object.
    // opts = (object) Boolean options [right]
    _deselect: function(vs, opts)
    {
        var sel = this.viewport.getSelected(),
            count = sel.size();
        if (!count) {
            this.lastrow = this.pivotrow = -1;
        }

        this.toggleButtons();
        if (opts.right || !count) {
            this.clearPreviewPane();
        } else if ((count == 1) && $('previewPane').visible()) {
            this.loadPreview(sel.get('dataob').first());
        }
    },

    // id = (string) DOM ID
    // opts = (Object) Boolean options [ctrl, right, shift]
    msgSelect: function(id, opts)
    {
        var bounds,
            row = this.viewport.createSelection('domid', id),
            rownum = row.get('rownum').first(),
            sel = this.isSelected('domid', id),
            selcount = this.selectedCount();

        this.lastrow = rownum;

        // Some browsers need to stop the mousedown event before it propogates
        // down to the browser level in order to prevent text selection on
        // drag/drop actions.  Clicking on a message should always lose focus
        // from the search input, because the user may immediately start
        // keyboard navigation after that. Thus, we need to ensure that a
        // message click loses focus on the search input.
        $('msgList_filter').blur();

        if (opts.shift) {
            if (selcount) {
                if (!sel || selcount != 1) {
                    bounds = [ rownum, this.pivotrow ];
                    this.viewport.select($A($R(bounds.min(), bounds.max())), { range: true });
                }
                return;
            }
        } else if (opts.ctrl) {
            this.pivotrow = rownum;
            if (sel) {
                this.viewport.deselect(row, { right: opts.right });
                return;
            } else if (opts.right || selcount) {
                this.viewport.select(row, { add: true, right: opts.right });
                return;
            }
        }

        this.viewport.select(row, { right: opts.right });
    },

    selectAll: function()
    {
        this.viewport.select($A($R(1, this.viewport.getMetaData('total_rows'))), { range: true });
    },

    isSelected: function(format, data)
    {
        return this.viewport.getSelected().contains(format, data);
    },

    selectedCount: function()
    {
        return (this.viewport) ? this.viewport.getSelected().size() : 0;
    },

    resetSelected: function()
    {
        if (this.viewport) {
            this.viewport.deselect(this.viewport.getSelected(), { clearall: true });
        }
        this.toggleButtons();
        this.clearPreviewPane();
    },

    // num = (integer) See absolute.
    // absolute = Is num an absolute row number - from 1 -> page_size (true) -
    //            or a relative change from the current selected value (false)
    //            If no current selected value, the first message in the
    //            current viewport is selected.
    moveSelected: function(num, absolute)
    {
        var curr, curr_row, row, row_data, sel;

        if (absolute) {
            if (!this.viewport.getMetaData('total_rows')) {
                return;
            }
            curr = num;
        } else {
            if (num == 0) {
                return;
            }

            sel = this.viewport.getSelected();
            switch (sel.size()) {
            case 0:
                curr = this.viewport.currentOffset();
                curr += (num > 0) ? 1 : this.viewport.getPageSize('current');
                break;

            case 1:
                curr_row = sel.get('dataob').first();
                curr = curr_row.rownum + num;
                break;

            default:
                sel = sel.get('rownum');
                curr = (num > 0 ? sel.max() : sel.min()) + num;
                break;
            }
            curr = (num > 0) ? Math.min(curr, this.viewport.getMetaData('total_rows')) : Math.max(curr, 1);
        }

        row = this.viewport.createSelection('rownum', curr);
        if (row.size()) {
            row_data = row.get('dataob').first();
            if (!curr_row || row_data.imapuid != curr_row.imapuid) {
                this.viewport.scrollTo(row_data.rownum);
                this.viewport.select(row, { delay: 0.3 });
            }
        } else {
            this.offset = curr;
            this.viewport.requestContentRefresh(curr - 1);
        }
    },
    // End message selection functions

    go: function(loc, data)
    {
        var app, f, separator;

        if (loc.startsWith('compose:')) {
            return;
        }

        if (loc.startsWith('msg:')) {
            separator = loc.indexOf(':', 4);
            f = loc.substring(4, separator);
            this.uid = loc.substring(separator + 1);
            loc = 'folder:' + f;
            // Now fall through to the 'folder:' check below.
        }

        if (loc.startsWith('folder:')) {
            f = loc.substring(7);
            if (this.folder != f || !$('dimpmain_folder').visible()) {
                this.highlightSidebar(this.getFolderId(f));
                if (!$('dimpmain_folder').visible()) {
                    $('dimpmain_portal').hide();
                    $('dimpmain_folder').show();
                }
                // This catches the refresh case - no need to re-add to history
                if (!Object.isUndefined(this.folder)) {
                    this._addHistory(loc);
                }
            }
            this.loadFolder(f);
            return;
        }

        this.folder = null;
        $('dimpmain_folder').hide();
        $('dimpmain_portal').update(DIMP.text.loading).show();

        if (loc.startsWith('app:')) {
            app = loc.substr(4);
            if (app == 'imp') {
                this.go('folder:INBOX');
                return;
            }
            this.highlightSidebar('app' + app);
            this._addHistory(loc, data);
            if (data) {
                this.iframeContent(loc, data);
            } else if (DIMP.conf.app_urls[app]) {
                this.iframeContent(loc, DIMP.conf.app_urls[app]);
            }
            return;
        }

        switch (loc) {
        case 'portal':
            this.highlightSidebar('appportal');
            this._addHistory(loc);
            DimpCore.setTitle(DIMP.text.portal);
            DimpCore.doAction('ShowPortal', {}, null, this.bcache.get('portalC') || this.bcache.set('portalC', this._portalCallback.bind(this)));
            break;

        case 'options':
            this.highlightSidebar('appoptions');
            this._addHistory(loc);
            DimpCore.setTitle(DIMP.text.prefs);
            this.iframeContent(loc, DIMP.conf.prefs_url);
            break;
        }
    },

    _addHistory: function(loc, data)
    {
        if (Horde.dhtmlHistory.getCurrentLocation() != loc) {
            Horde.dhtmlHistory.add(loc, data);
        }
    },

    highlightSidebar: function(id)
    {
        // Folder bar may not be fully loaded yet.
        if ($('foldersLoading').visible()) {
            this.highlightSidebar.bind(this, id).defer();
            return;
        }

        $('sidebarPanel').select('.on').invoke('removeClassName', 'on');

        var elt = $(id);
        if (!elt) {
            return;
        }
        if (!elt.match('LI')) {
            elt = elt.up();
            if (!elt) {
                return;
            }
        }
        elt.addClassName('on');

        // Make sure all subfolders are expanded
        // The last 2 elements of ancestors() are the BODY and HTML tags -
        // don't need to parse through them.
        elt.ancestors().slice(0, -2).find(function(n) {
            if (n.hasClassName('subfolders')) {
                this._toggleSubFolder(n.id.substring(3), 'exp');
            } else {
                return (n.id == 'foldersSidebar');
            }
        }, this);
    },

    iframeContent: function(name, loc)
    {
        if (name === null) {
            name = loc;
        }

        var container = $('dimpmain_portal'), iframe;
        if (!container) {
            DimpCore.showNotifications([ { type: 'horde.error', message: 'Bad portal!' } ]);
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

    // r = ViewPort row data
    msgWindow: function(r)
    {
        this.updateUnseenUID(r, 0);
        var url = DIMP.conf.message_url;
        url += (url.include('?') ? '&' : '?') +
               $H({ folder: r.view,
                    uid: r.imapuid }).toQueryString();
        DimpCore.popupWindow(url, 'msgview' + r.view + r.imapuid);
    },

    composeMailbox: function(type)
    {
        var sel = this.viewport.getSelected();
        if (!sel.size()) {
            return;
        }
        sel.get('dataob').each(function(s) {
            DimpCore.compose(type, { folder: s.view, uid: s.imapuid });
        });
    },

    loadFolder: function(f, background)
    {
        if (!this.viewport) {
            this._createViewPort();
        }

        if (!background) {
            this.resetSelected();

            if (this.folder == f) {
                this.searchfilterClear(false);
                return;
            }

            this.searchfilterClear(true);
            $('folderName').update(DIMP.text.loading);
            $('msgHeader').update();
            this.folderswitch = true;
            this.folder = f;
        }

        this.viewport.loadView(f, this.uid ? { imapuid: this.uid, view: f } : null, background);
    },

    _createViewPort: function()
    {
        var mf = $('msgList_filter'),
            // No need to cache - this function only called once.
            settitle = this.setMessageListTitle.bind(this);

        this.viewport = new ViewPort({
            content_container: 'msgList',
            empty_container: 'msgList_empty',
            error_container: 'msgList_error',
            fetch_action: 'ListMessages',
            template: this.message_list_template,
            buffer_pages: DIMP.conf.buffer_pages,
            limit_factor: DIMP.conf.limit_factor,
            viewport_wait: DIMP.conf.viewport_wait,
            show_split_pane: DIMP.conf.preview_pref,
            split_pane: 'previewPane',
            splitbar: 'splitBar',
            content_class: 'msglist',
            row_class: 'msgRow',
            selected_class: 'selectedRow',
            ajaxRequest: DimpCore.doAction.bind(DimpCore),
            norows: true,
            onScrollIdle: settitle,
            onSlide: settitle,
            onContent: function(rows) {
                var mf, search,
                    thread = ((this.viewport.getMetaData('sortby') == DIMP.conf.sortthread) && this.viewport.getMetaData('thread'));
                if (this.viewport.isFiltering()) {
                    search = this.sfilters.get(this._getSearchfilterField()).capitalize();
                    mf = new RegExp("(" + $F('msgList_filter') + ")", "i");
                }
                rows.get('dataob').each(function(row) {
                    var elt, tmp, u,
                        r = $(row.domid);

                    // Add thread graphics
                    if (thread && thread.get(row.imapuid)) {
                        elt = r.down('.msgSubject');
                        tmp = elt.cloneNode(false);
                        u = thread.get(row.imapuid);
                        $R(0, u.length, true).each(function(i) {
                            tmp.insert($($('thread_img_' + u.charAt(i)).cloneNode(false)).writeAttribute('id', ''));
                        });
                        elt.replace(tmp.insert(elt.getText().escapeHTML()));
                    }

                    // Add context menu
                    this._addMouseEvents({ id: row.domid, type: row.menutype });
                    new Drag(r, this._msgDragConfig);

                    // Highlight search terms
                    if (search == 'From' || search == 'Subject') {
                        elt = r.down('.msg' + search);
                        elt.update(elt.getText().escapeHTML().gsub(mf, '<span class="searchMatch">#{1}</span>'));
                    }
                }, this);
                this.setMessageListTitle();
            }.bind(this),
            onComplete: function() {
                var row, ssc,
                    l = this.viewport.getMetaData('label');

                if (this.uid) {
                    row = this.viewport.getViewportSelection().search({ imapuid: { equal: [ this.uid ] }, view: { equal: [ this.folder ] } });
                    if (row.size()) {
                        this.viewport.scrollTo(row.get('rownum').first());
                        this.viewport.select(row);
                    }
                } else if (this.offset) {
                    this.viewport.select(this.viewport.createSelection('rownum', this.offset));
                }
                this.offset = this.uid = null;

                // 'label' will not be set if there has been an error
                // retrieving data from the server.
                l = this.viewport.getMetaData('label');
                if (l) {
                    $('folderName').update(l);
                }

                if (this.folderswitch) {
                    this.folderswitch = false;
                    if (this.folder == DIMP.conf.spam_folder) {
                        if (!DIMP.conf.spam_spamfolder &&
                            DimpCore.buttons.indexOf('button_spam') != -1) {
                            [ $('button_spam').up(), $('ctx_message_spam') ].invoke('hide');
                        }
                        if (DimpCore.buttons.indexOf('button_ham') != -1) {
                            [ $('button_ham').up(), $('ctx_message_ham') ].invoke('show');
                        }
                    } else {
                        if (DimpCore.buttons.indexOf('button_spam') != -1) {
                            [ $('button_spam').up(), $('ctx_message_spam') ].invoke('show');
                        }
                        if (DimpCore.buttons.indexOf('button_ham') != -1) {
                            if (DIMP.conf.ham_spamfolder) {
                                [ $('button_ham').up(), $('ctx_message_ham') ].invoke('hide');
                            } else {
                                [ $('button_ham').up(), $('ctx_message_ham') ].invoke('show');
                            }
                        }
                    }
                } else if (this.filtertoggle) {
                    if (this.filtertoggle == 1 &&
                        this.viewport.getMetaData('sortby') == DIMP.conf.sortthread) {
                        ssc = DIMP.conf.sortdate;
                    }
                    this.filtertoggle = 0;
                }

                this.setSortColumns(ssc);

                if (this.viewport.isFiltering()) {
                    this.resetSelected();
                } else {
                    this.setFolderLabel(this.folder, this.viewport.getMetaData('unseen') || 0);
                }
                this.updateTitle();
            }.bind(this),
            onFetch: this.msgListLoading.bind(this, true),
            onEndFetch: this.msgListLoading.bind(this, false),
            onCacheUpdate: function(id) {
                delete this.cacheids[id];
            }.bind(this),
            onWait: function() {
                if ($('dimpmain_folder').visible()) {
                    DimpCore.showNotifications([ { type: 'horde.warning', message: DIMP.text.listmsg_wait } ]);
                }
            },
            onFail: function() {
                if ($('dimpmain_folder').visible()) {
                    DimpCore.showNotifications([ { type: 'horde.error', message: DIMP.text.listmsg_timeout } ]);
                }
                this.msgListLoading(false);
            }.bind(this),
            onFirstContent: function() {
                this.clearPreviewPane();
            }.bind(this),
            onClearRows: function(r) {
                r.each(function(row) {
                    if (row.id) {
                        this._removeMouseEvents(row);
                    }
                }, this);
            }.bind(this),
            onBeforeResize: function() {
                var sel = this.viewport.getSelected();
                this.isvisible = (sel.size() == 1) && (this.viewport.isVisible(sel.get('rownum').first()) == 0);
            }.bind(this),
            onAfterResize: function() {
                if (this.isvisible) {
                    this.viewport.scrollTo(this.viewport.getSelected().get('rownum').first());
                }
            }.bind(this),
            onCachedList: function(id) {
                var tmp, vs;
                if (!this.cacheids[id]) {
                    vs = this.viewport.getViewportSelection(id, true);
                    if (!vs.size()) {
                        return '';
                    }

                    if (vs.getBuffer().getMetaData('search')) {
                        this.cacheids[id] = vs.get('uid').toJSON();
                    } else {
                        tmp = {};
                        tmp[id] = vs.get('uid').clone();
                        this.cacheids[id] = DimpCore.toRangeString(tmp);
                    }
                }
                return this.cacheids[id];
            }.bind(this),
            onUpdateClass: function(row) {
                this.updateStatusFlags(row);
            }.bind(this),
            selectCallback: this._select.bind(this),
            deselectCallback: this._deselect.bind(this)
        });

        // If starting in no preview mode, need to set the no preview class
        if (!DIMP.conf.preview_pref) {
            $('msgList').addClassName('msglistNoPreview');
        }

        // Set up viewport filter events.
        this.viewport.addFilter('ListMessages', this._addSearchfilterParams.bind(this));
        mf.observe('focus', this._searchfilterOnFocus.bind(this));
        mf.observe('blur', this._searchfilterOnBlur.bind(this));
        mf.addClassName('msgFilterDefault');
    },

    _addMouseEvents: function(p, popdown)
    {
        if (popdown) {
            var bidelt = $(p.id);
            bidelt.insert({ after: $($('popdown_img').cloneNode(false)).writeAttribute('id', p.id + '_img').show() });
            p.id += '_img';
            p.offset = bidelt.up();
            p.left = true;
        }

        DimpCore.DMenu.addElement(p.id, 'ctx_' + p.type, p);
    },

    _removeMouseEvents: function(elt)
    {
        var d, id = $(elt).readAttribute('id');
        if (id && (d = DragDrop.Drags.get_drag(id))) {
            d.destroy();
        }

        DimpCore.DMenu.removeElement($(elt).identify());
    },

    _onMenuShow: function(ctx_id, ctx)
    {
        var elts, folder, ob, sel;

        switch (ctx_id) {
        case 'ctx_folder':
            elts = $('ctx_folder_create', 'ctx_folder_rename', 'ctx_folder_delete');
            folder = DimpCore.DMenu.element();
            if (folder.readAttribute('mbox') == 'INBOX') {
                elts.invoke('hide');
            } else if (DIMP.conf.fixed_folders.indexOf(folder.readAttribute('mbox')) != -1) {
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
            DimpCore.DMenu.addSubMenu('ctx_message_reply', 'ctx_reply');
            DimpCore.DMenu.addSubMenu('ctx_message_forward', 'ctx_forward');
            DimpCore.DMenu.addSubMenu('ctx_message_setflag', 'ctx_flag');
            break;

        case 'ctx_reply':
            sel = this.viewport.getSelected();
            if (sel.size() == 1) {
                ob = sel.get('dataob').first();
            }
            [ $('ctx_reply_reply_list') ].invoke(ob && ob.listmsg ? 'show' : 'hide');
            break;

        case 'ctx_otheractions':
            $('oa_setflag', 'oa_sep1', 'oa_blacklist', 'oa_whitelist', 'oa_sep2', 'oa_undeleted').compact().invoke(this.viewport.getSelected().size() ? 'show' : 'hide');
            DimpCore.DMenu.addSubMenu('oa_setflag', 'ctx_flag');
            break;

        case 'ctx_draft':
            DimpCore.DMenu.addSubMenu('ctx_draft_setflag', 'ctx_flag');
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
            label = DIMP.text.search + ' :: ' + (this.viewport.getMetaData('total_rows') || 0) + ' ' + DIMP.text.resfound;
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
        DimpCore.setTitle(label);
    },

    sort: function(e)
    {
        // Don't change sort if we are past the sortlimit
        if (this.viewport.getMetaData('sortlimit')) {
            return;
        }

        var s, sortby,
            elt = e.element();
        if (!elt.hasAttribute('sortby')) {
            elt = elt.up('[sortby]');
            if (!elt) {
                return;
            }
        }
        sortby = Number(elt.readAttribute('sortby'));

        if (sortby == this.viewport.getMetaData('sortby')) {
            s = { sortdir: (this.viewport.getMetaData('sortdir') ? 0 : 1) };
            this.viewport.setMetaData({ sortdir: s.sortdir });
        } else {
            s = { sortby: sortby };
            this.viewport.setMetaData({ sortby: s.sortby });
        }
        this.setSortColumns(sortby);
        this.viewport.reload(s);
    },

    setSortColumns: function(sortby)
    {
        var tmp,
            m = $('msglistHeader');

        if (Object.isUndefined(sortby)) {
            sortby = this.viewport.getMetaData('sortby');
        }

        tmp = m.down('small[sortby=' + sortby + ']');
        if (tmp && tmp.up().visible()) {
           tmp.up(1).childElements().invoke('toggle');
        }

        tmp = m.down('div.msgFrom a');
        if ((this.viewport.isFiltering() && this.fspecial) ||
            this.viewport.getMetaData('special')) {
            tmp.hide().next().show();
        } else {
            tmp.show().next().hide();
        }

        tmp = m.down('div.msgSubject a');
        if (this.viewport.isFiltering() ||
            this.viewport.getMetaData('nothread') ||
            this.viewport.getMetaData('sortlimit')) {
            tmp.show().next().hide();
            tmp.down().hide();
        } else {
            tmp.down().show();
        }

        m.childElements().invoke('removeClassName', 'sortup').invoke('removeClassName', 'sortdown');

        tmp = m.down('div a[sortby=' + sortby + ']');
        if (tmp) {
            tmp.up().addClassName(this.viewport.getMetaData('sortdir') ? 'sortup' : 'sortdown');
        }
    },

    // Preview pane functions
    togglePreviewPane: function()
    {
        var p = DIMP.conf.preview_pref = !DIMP.conf.preview_pref;
        $('previewtoggle').setText(p ? DIMP.text.hide_preview : DIMP.text.show_preview);
        [ $('msgList') ].invoke(p ? 'removeClassName' : 'addClassName', 'msglistNoPreview');
        this._updatePrefs('show_preview', p ? 1 : 0);
        this.viewport.showSplitPane(p);
        if (p) {
            this.initPreviewPane();
        }
    },

    loadPreview: function(data, params)
    {
        var pp = $('previewPane'), pp_offset, pp_uid;
        if (!pp.visible()) {
            return;
        }

        if (!params) {
            if (this.pp &&
                this.pp.imapuid == data.imapuid &&
                this.pp.view == data.view) {
                return;
            }
            this.pp = data;
            pp_uid = data.imapuid + data.view;

            if (this.ppfifo.indexOf(pp_uid) != -1) {
                return this._loadPreviewCallback(this.ppcache[pp_uid]);
            }
        }

        pp_offset = pp.positionedOffset();
        $('msgLoading').setStyle({ position: 'absolute', top: (pp_offset.top + 10) + 'px', left: (pp_offset.left + 10) + 'px' }).show();

        DimpCore.doAction('ShowPreview', params || {}, this.viewport.createSelection('dataob', this.pp), this.bcache.get('loadPC') || this.bcache.set('loadPC', this._loadPreviewCallback.bind(this)));
    },

    _loadPreviewCallback: function(resp)
    {
        var ppuid, row, search, tmp, tmp2,
            pm = $('previewMsg'),
            r = resp.response,
            t = $('msgHeadersContent').down('THEAD');

        if (!r.error) {
            search = this.viewport.getViewportSelection().search({ imapuid: { equal: [ r.index ] }, view: { equal: [ r.folder ] } });
            if (search.size()) {
                row = search.get('dataob').first();
                this.updateUnseenUID(row, 0);
            }
        }

        if (this.pp &&
            (this.pp.imapuid != r.index ||
             this.pp.view != r.folder)) {
            return;
        }

        if (r.error || this.viewport.getSelected().size() != 1) {
            if (r.error) {
                DimpCore.showNotifications([ { type: r.errortype, message: r.error } ]);
            }
            this.clearPreviewPane();
            return;
        }

        // Store in cache.
        ppuid = r.index + r.folder;
        this._expirePPCache([ ppuid ]);
        this.ppcache[ppuid] = resp;
        this.ppfifo.push(ppuid);

        DimpCore.removeAddressLinks(pm);

        DIMP.conf.msg_index = r.index;
        DIMP.conf.msg_folder = r.folder;

        // Add subject/priority
        tmp = pm.select('.subject');
        tmp.invoke('update', r.subject);
        switch (r.priority) {
        case 'high':
        case 'low':
            tmp.invoke('insert', { top: $($(r.priority + '_priority_img').cloneNode(false)).writeAttribute('id', false) });
            break;
        }

        // Add date
        $('msgHeadersColl').select('.date').invoke('update', r.minidate);
        $('msgHeaderDate').select('.date').invoke('update', r.fulldate);

        // Add from/to/cc headers
        [ 'from', 'to', 'cc' ].each(function(a) {
            if (r[a]) {
                (a == 'from' ? pm.select('.' + a) : [ t.down('.' + a) ]).each(function(elt) {
                    elt.replace(DimpCore.buildAddressLinks(r[a], elt.cloneNode(false)));
                });
            }
            [ $('msgHeader' + a.capitalize()) ].invoke(r[a] ? 'show' : 'hide');
        });

        // Add attachment information
        $('toggleHeaders').select('.attachmentImage').invoke(r.atc_label ? 'show' : 'hide');
        if (r.atc_label) {
            tmp = $('msgAtc').show().down('.label');
            tmp2 = $('partlist');
            tmp2.hide().previous().update(new Element('SPAN', { className: 'atcLabel'}).insert(r.atc_label)).insert(r.atc_download);
            if (r.atc_list) {
                $('partlist_col').show();
                $('partlist_exp').hide();
                tmp.down().hide().next().show();
                tmp2.down('TABLE').update(r.atc_list);
            } else {
                tmp.down().show().next().hide();
            }
        } else {
            $('msgAtc').hide();
        }

        $('msgBody').update(r.msgtext);
        $('msgLoading', 'previewInfo').invoke('hide');
        $('previewPane').scrollTop = 0;
        pm.show();

        if (r.js) {
            eval(r.js.join(';'));
        }
        this._addHistory('msg:' + row.view + ':' + row.imapuid);
    },

    initPreviewPane: function()
    {
        var sel = this.viewport.getSelected();
        if (sel.size() != 1) {
            this.clearPreviewPane();
        } else {
            this.loadPreview(sel.get('dataob').first());
        }
    },

    clearPreviewPane: function()
    {
        $('msgLoading', 'previewMsg').invoke('hide');
        $('previewInfo').show();
        this.pp = null;
    },

    _toggleHeaders: function(elt, update)
    {
        if (update) {
            DIMP.conf.toggle_pref = !DIMP.conf.toggle_pref;
            this._updatePrefs('dimp_toggle_headers', elt.id == 'th_expand' ? 1 : 0);
        }
        [ elt.up().select('A'), $('msgHeadersColl', 'msgHeaders') ].flatten().invoke('toggle');
    },

    _expirePPCache: function(ids)
    {
        this.ppfifo = this.ppfifo.diff(ids);
        ids.each(function(i) {
            delete this.ppcache[i];
        }, this);
        // Preview pane cache size is 20 entries. Given that a reasonable guess
        // of an average e-mail size is 10 KB (including headers), also make
        // an estimate that the JSON data size will be approx. 10 KB. 200 KB
        // should be a fairly safe caching value for any recent browser.
        if (this.ppfifo.size() > 20) {
            delete this.ppcache[this.ppfifo.shift()];
        }
    },

    // Labeling functions
    updateUnseenUID: function(r, setflag)
    {
        var sel, unseen, unseenset;
        if (!r.bg) {
            return false;
        }
        unseenset = r.bg.indexOf('unseen') != -1;
        if ((setflag && unseenset) || (!setflag && !unseenset)) {
            return false;
        }

        sel = this.viewport.createSelection('dataob', r);
        unseen = Number($(this.getFolderId(r.view)).readAttribute('u'));
        if (setflag) {
            this.viewport.updateFlag(sel, 'unseen', true);
            ++unseen;
        } else {
            this.viewport.updateFlag(sel, 'unseen', false);
            --unseen;
        }

        this.updateUnseenStatus(r.view, unseen);
    },

    updateUnseenStatus: function(mbox, unseen)
    {
        if (this.viewport) {
            this.viewport.setMetaData({ unseen: unseen }, mbox);
        }
        this.setFolderLabel(mbox, unseen);

        if (this.folder == mbox) {
            this.updateTitle();
        }
    },

    setMessageListTitle: function()
    {
        var offset,
            rows = this.viewport.getMetaData('total_rows');
        if (rows > 0) {
            offset = this.viewport.currentOffset();
            $('msgHeader').update(DIMP.text.messages + ' ' + (offset + 1) + ' - ' + (Math.min(offset + this.viewport.getPageSize(), rows)) + ' ' + DIMP.text.of + ' ' + rows);
        } else {
            $('msgHeader').update(DIMP.text.nomessages);
        }
    },

    setFolderLabel: function(f, unseen)
    {
        var elt, fid = this.getFolderId(f);
        elt = $(fid);
        if (!elt || !elt.hasAttribute('u')) {
            return;
        }

        unseen = Number(unseen);
        elt.writeAttribute('u', unseen);

        if (f == 'INBOX' && window.fluid) {
            window.fluid.setDockBadge(unseen ? unseen : '');
        }

        $(fid + '_label').update((unseen > 0) ?
            new Element('STRONG').insert(elt.readAttribute('l')).insert('&nbsp;').insert(new Element('SPAN', { className: 'count', dir: 'ltr' }).insert('(' + unseen + ')')) :
            elt.readAttribute('l'));
    },

    getFolderId: function(f)
    {
        return 'fld' + decodeURIComponent(f).replace(/_/g,'__').replace(/\W/g, '_');
    },

    getSubFolderId: function(f)
    {
        return 'sub' + f;
    },

    /* Folder list updates. */
    pollFolders: function()
    {
        // Reset poll folder counter.
        this.setPollFolders();
        var args = {};

        // Check for label info - it is possible that the mailbox may be
        // loading but not complete yet and sending this request will cause
        // duplicate info to be returned.
        if (this.folder &&
            $('dimpmain_folder').visible() &&
            this.viewport.getMetaData('label')) {
            args = this.viewport.addRequestParams({});
        }
        $('checkmaillink').down('A').update('[' + DIMP.text.check + ']');
        DimpCore.doAction('PollFolders', args, null, this.bcache.get('pollFC') || this.bcache.set('pollFC', this._pollFoldersCallback.bind(this)));
    },

    _pollFoldersCallback: function(r)
    {
        var that;
        r = r.response;
        if (r.poll) {
            that = this;
            $H(r.poll).each(function(u) {
                that.updateUnseenStatus(u.key, u.value);
            });
        }
        if (r.quota) {
            this._displayQuota(r.quota);
        }
        $('checkmaillink').down('A').update(DIMP.text.getmail);
    },

    _displayQuota: function(r)
    {
        q = $('quota').cleanWhitespace();
        q.setText(r.m);
        q.down('SPAN.used IMG').writeAttribute({ width: 99 - r.p });
    },

    setPollFolders: function()
    {
        if (DIMP.conf.refresh_time) {
            if (this.pollPE) {
                this.pollPE.stop();
            }
            // Don't cache - this code is only run once.
            this.pollPE = new PeriodicalExecuter(this.pollFolders.bind(this), DIMP.conf.refresh_time);
        }
    },

    _portalCallback: function(r)
    {
        if (r.response.linkTags) {
            var head = $(document.documentElement).down('HEAD');
            r.response.linkTags.each(function(newLink) {
                var link = new Element('LINK', { type: 'text/css', rel: 'stylesheet', href: newLink.href });
                if (newLink.media) {
                    link.media = newLink.media;
                }
                head.insert(link);
            });
        }
        $('dimpmain_portal').update(r.response.portal);
    },

    /* Search filter functions. */
    searchfilterRun: function()
    {
        if (!this.viewport.isFiltering()) {
            this.filtertoggle = 1;
            this.fspecial = this.viewport.getMetaData('special');
        }
        this.viewport.runFilter($F('msgList_filter'));
    },

    _searchfilterOnFocus: function()
    {
        var q = $('qoptions').up();

        if ($('msgList_filter').hasClassName('msgFilterDefault')) {
            this._setFilterText(false);
        }

        if (!this.filter_on) {
            this.filter_on = true;
            $('sf_current').update(this.viewport.getMetaData('label'));
            this._setSearchfilterParams(this.viewport.getMetaData('special') ? 'to' : 'from', 'msg');
            this._setSearchfilterParams('current', 'folder');
            $(document.documentElement).setStyle({ overflowY: 'hidden' });
            Effect.SlideDown(q, { duration: 0.5, afterFinish: function() { this._onResize(false, true); $(document.documentElement).setStyle({ overflowY: 'auto' }); }.bind(this) });
        }
    },

    _searchfilterOnBlur: function()
    {
        if (!$F('msgList_filter')) {
            this._setFilterText(true);
        }
    },

    // reset = (boolean) TODO
    searchfilterClear: function(reset)
    {
        if (!this.filter_on) {
            return;
        }

        this.filter_on = false;
        if (this.searchobserve) {
            clearTimeout(this.searchobserve);
            this.searchobserve = null;
        }
        this._setFilterText(true);
        Effect.SlideUp($('qoptions').up(), { duration: 0.5, afterFinish: this._onResize.bind(this, reset) });
        this.filtertoggle = 2;
        this.resetSelected();
        this.viewport.stopFilter(reset);
    },

    // d = (boolean) Deactivate filter input?
    _setFilterText: function(d)
    {
        var mf = $('msgList_filter');
        if (d) {
            mf.setValue(DIMP.text.search);
            mf.addClassName('msgFilterDefault');
        } else {
            mf.setValue('');
            mf.removeClassName('msgFilterDefault');
        }
    },

    // type = 'folder' or 'msg'
    _setSearchfilterParams: function(id, type)
    {
        var c = (type == 'folder') ? this.sfiltersfolder : this.sfilters;
        c.keys().each(function(i) {
            $(i).writeAttribute('className', (id == c.get(i)) ? 'qselected' : '');
        });
    },

    updateSearchfilter: function(id, type)
    {
        this._setSearchfilterParams(id, type);
        if ($F('msgList_filter')) {
            this.viewport.runFilter();
        }
    },

    _addSearchfilterParams: function()
    {
        var sf = this.sfiltersfolder.keys().find(function(s) {
            return $(s).hasClassName('qselected');
        });
        return $H({ searchfolder: this.sfiltersfolder.get(sf), searchmsg: this.sfilters.get(this._getSearchfilterField()) });
    },

    _getSearchfilterField: function()
    {
        return this.sfilters.keys().find(function(s) {
            return $(s).hasClassName('qselected');
        });
    },

    /* Enable/Disable DIMP action buttons as needed. */
    toggleButtons: function()
    {
        var disable = (this.selectedCount() == 0);
        DimpCore.buttons.each(function(b) {
            var elt = $(b);
            if (elt) {
                [ elt.up() ].invoke(disable ? 'addClassName' : 'removeClassName', 'disabled');
                DimpCore.DMenu.disable(b + '_img', true, disable);
            }
        });
    },

    /* Drag/Drop handler. */
    _folderDropHandler: function(drop, drag, e)
    {
        var dropbase, sel, uids,
            foldername = drop.readAttribute('mbox'),
            ftype = drop.readAttribute('ftype');

        if (drag.hasClassName('folder')) {
            dropbase = (drop == $('dropbase'));
            if (dropbase ||
                (ftype != 'special' && !this.isSubfolder(drag, drop))) {
                DimpCore.doAction('RenameFolder', { old_name: drag.readAttribute('mbox'), new_parent: dropbase ? '' : foldername, new_name: drag.readAttribute('l') }, null, this.bcache.get('folderC') || this.bcache.set('folderC', this._folderCallback.bind(this)));
            }
        } else if (ftype != 'container') {
            sel = this.viewport.getSelected();

            if (sel.size()) {
                // Dragging multiple selected messages.
                uids = sel;
            } else if (drag.readAttribute('mbox') != foldername) {
                // Dragging a single unselected message.
                uids = this.viewport.createSelection('domid', drag.id);
            }

            if (uids.size()) {
                if (e.ctrlKey) {
                    DimpCore.doAction('CopyMessage', this.viewport.addRequestParams({ tofld: foldername }), uids, this.bcache.get('pollFC') || this.bcache.set('pollFC', this._pollFoldersCallback.bind(this)));
                } else if (this.folder != foldername) {
                    // Don't allow drag/drop to the current folder.
                    this.viewport.updateFlag(uids, 'deletedmsg', true);
                    DimpCore.doAction('MoveMessage', this.viewport.addRequestParams({ tofld: foldername }), uids, this.bcache.get('deleteC') || this.bcache.set('deleteC', this._deleteCallback.bind(this)));
                }
            }
        }
    },

    dragCaption: function()
    {
        var cnt = this.selectedCount();
        return cnt + ' ' + (cnt == 1 ? DIMP.text.message : DIMP.text.messages);
    },

    /* Keydown event handler */
    _keydownHandler: function(e)
    {
        var co, form, ps, r, row, rowoff, sel,
            elt = e.element(),
            kc = e.keyCode || e.charCode;

        // Form catching - normally we will ignore, but certain cases we want
        // to catch.
        form = e.findElement('FORM');
        if (form) {
            switch (kc) {
            case Event.KEY_ESC:
            case Event.KEY_TAB:
                // Catch escapes in search box
                if (elt.readAttribute('id') == 'msgList_filter') {
                    if (kc == Event.KEY_ESC || !elt.getValue()) {
                        this.searchfilterClear(false);
                    }
                    elt.blur();
                    e.stop();
                }
                break;

            case Event.KEY_RETURN:
                // Catch returns in RedBox
                if (form.readAttribute('id') == 'RB_folder') {
                    this.cfolderaction(e);
                    e.stop();
                }
                break;
            }

            return;
        }

        // Only catch keyboard shortcuts in message list view.
        if (!$('dimpmain_folder').visible()) {
            return;
        }

        sel = this.viewport.getSelected();

        switch (kc) {
        case Event.KEY_DELETE:
        case Event.KEY_BACKSPACE:
            r = sel.get('dataob');
            if (e.shiftKey) {
                this.moveSelected((r.last().rownum == this.viewport.getMetaData('total_rows')) ? (r.first().rownum - 1) : (r.last().rownum + 1), true);
            }
            this.flag('deleted', { index: r });
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
            if (!elt.match('input')) {
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

    _keyupHandler: function(e)
    {
        if (e.element().readAttribute('id') == 'msgList_filter') {
            if (this.searchobserve) {
                clearTimeout(this.searchobserve);
            }
            if (this.filter_on) {
                this.searchobserve = (this.bcache.get('searchfilterR') || this.bcache.set('searchfilterR', this.searchfilterRun.bind(this))).delay(0.5);
            }
        }
    },

    _clickHandler: function(e, dblclick)
    {
        if (e.isRightClick()) {
            return;
        }

        var elt = e.element(),
            orig = e.element(),
            id, mbox, tmp;

        while (Object.isElement(elt)) {
            id = elt.readAttribute('id');

            switch (id) {
            case 'msgList':
                if (dblclick) {
                    if (!orig.hasClassName('msgRow')) {
                        orig = orig.up('.msgRow');
                    }
                    tmp = this.viewport.createSelection('domid', orig.identify()).get('dataob').first();
                    tmp.draft ? DimpCore.compose('resume', { folder: tmp.view, uid: tmp.imapuid }) : this.msgWindow(tmp);
                    e.stop();
                    return;
                }
                break;

            case 'RB_Folder_ok':
                this.cfolderaction(e);
                e.stop();
                return;

            case 'RB_Folder_cancel':
                this._closeRedBox();
                e.stop();
                return;

            case 'normalfolders':
            case 'specialfolders':
                this._handleFolderMouseClick(e);
                break;

            case 'hometab':
            case 'logolink':
                this.go('portal');
                e.stop();
                return;

            case 'button_compose':
            case 'composelink':
                DimpCore.compose('new');
                e.stop();
                return;

            case 'checkmaillink':
                this.pollFolders();
                e.stop();
                return;

            case 'fetchmaillink':
                IMPDialog.display({ dialog_load: DIMP.conf.URI_IMP + '/FetchmailDialog' });
                e.stop();
                return;

            case 'appportal':
            case 'appoptions':
                this.go(id.substring(3));
                e.stop();
                return;

            case 'applogout':
                elt.down('A').update('[' + DIMP.text.onlogout + ']');
                DimpCore.logout();
                e.stop();
                return;

            case 'newfolder':
                this.createBaseFolder();
                e.stop();
                return;

            case 'button_forward':
            case 'button_reply':
                this.composeMailbox(id == 'button_reply' ? 'reply' : DIMP.conf.forward_default);
                break;

            case 'button_deleted':
            case 'button_ham':
            case 'button_spam':
                this.flag(id.substring(7));
                e.stop();
                return;

            case 'button_other':
                DimpCore.DMenu.trigger(e.findElement('A').next(), true);
                e.stop();
                return;

            case 'sf_all':
            case 'sf_current':
                this.updateSearchfilter(id.substring(3), 'folder');
                e.stop();
                return;

            case 'sf_msgall':
            case 'sf_from':
            case 'sf_to':
            case 'sf_subject':
                this.updateSearchfilter(id.substring(3), 'msg');
                e.stop();
                return;

            case 'msglistHeader':
                this.sort(e);
                e.stop();
                return;

            case 'ctx_folder_create':
                this.createSubFolder(DimpCore.DMenu.element());
                break;

            case 'ctx_folder_rename':
                this.renameFolder(DimpCore.DMenu.element());
                break;

            case 'ctx_folder_empty':
                mbox = DimpCore.DMenu.element().readAttribute('mbox');
                DimpCore.DMenu.close(true);
                if (window.confirm(DIMP.text.empty_folder)) {
                    DimpCore.doAction('EmptyFolder', { view: mbox }, null, this._emptyFolderCallback.bind(this));
                }
                break;

            case 'ctx_folder_delete':
                mbox = DimpCore.DMenu.element().readAttribute('mbox');
                DimpCore.DMenu.close(true);
                if (window.confirm(DIMP.text.delete_folder)) {
                    DimpCore.doAction('DeleteFolder', { view: mbox }, null, this.bcache.get('folderC') || this.bcache.set('folderC', this._folderCallback.bind(this)));
                }
                break;

            case 'ctx_folder_seen':
            case 'ctx_folder_unseen':
                this.flag(id == 'ctx_folder_seen' ? 'allSeen' : 'allUnseen', { mailbox: DimpCore.DMenu.element().readAttribute('mbox') });
                break;

            case 'ctx_folder_poll':
            case 'ctx_folder_nopoll':
                this.modifyPollFolder(DimpCore.DMenu.element().readAttribute('mbox'), id == 'ctx_folder_poll');
                break;

            case 'ctx_container_create':
                this.createSubFolder(DimpCore.DMenu.element());
                break;

            case 'ctx_container_rename':
                this.renameFolder(DimpCore.DMenu.element());
                break;

            case 'ctx_message_spam':
            case 'ctx_message_ham':
            case 'ctx_message_blacklist':
            case 'ctx_message_whitelist':
            case 'ctx_message_deleted':
            case 'ctx_message_undeleted':
                this.flag(id.substring(12));
                break;

            case 'ctx_draft_resume':
                this.composeMailbox('resume');
                e.stop();
                return;

            case 'ctx_draft_flagged':
            case 'ctx_draft_clear':
            case 'ctx_draft_deleted':
            case 'ctx_draft_undeleted':
                this.flag(id.substring(10));
                break;

            case 'ctx_reply_reply':
            case 'ctx_reply_reply_all':
            case 'ctx_reply_reply_list':
                this.composeMailbox(id.substring(10));
                break;

            case 'ctx_forward_forward_all':
            case 'ctx_forward_forward_body':
            case 'ctx_forward_forward_attachments':
                this.composeMailbox(id.substring(12));
                break;

            case 'previewtoggle':
                this.togglePreviewPane();
                break;

            case 'flag_seen':
            case 'flag_unseen':
            case 'flag_flagged':
            case 'flag_clear':
            case 'flag_answered':
            case 'flag_unanswered':
            case 'flag_draft':
            case 'flag_notdraft':
                this.flag(id.substring(5));
                break;

            case 'oa_blacklist':
            case 'oa_whitelist':
            case 'oa_undeleted':
                this.flag(id.substring(3));
                break;

            case 'oa_selectall':
                this.selectAll();
                break;

            case 'oa_purge_deleted':
                this.purgeDeleted();
                break;

            case 'th_expand':
            case 'th_collapse':
                this._toggleHeaders(elt, true);
                break;

            case 'msg_newwin':
            case 'msg_newwin_options':
                this.msgWindow(this.viewport.getViewportSelection().search({ imapuid: { equal: [ DIMP.conf.msg_index ] } , view: { equal: [ DIMP.conf.msg_folder ] } }).get('dataob').first());
                e.stop();
                return;

            case 'qclose':
                this.searchfilterClear(false);
                e.stop();
                return;

            case 'applicationfolders':
                tmp = e.element();
                if (!tmp.hasClassName('custom')) {
                    tmp.up('LI.custom');
                }
                if (tmp) {
                    this.go('app:' + tmp.down('A').identify().substring(3));
                    e.stop();
                    return;
                }
                break;

            case 'tabbar':
                if (e.element().hasClassName('applicationtab')) {
                    this.go('app:' + e.element().identify().substring(6));
                    e.stop();
                    return;
                }
                break;

            case 'dimpmain_portal':
                if (e.element().match('H1.header a')) {
                    this.go('app:' + e.element().readAttribute('app'));
                    e.stop();
                    return;
                }
                break;
            }

            elt = elt.up();
        }
    },

    _mouseHandler: function(e, type)
    {
        var elt = e.element();

        switch (type) {
        case 'over':
            if (DragDrop.Drags.drag && elt.hasClassName('exp')) {
                this._toggleSubFolder(elt.up(), 'exp');
            }
            break;
        }
    },

    /* Handle rename folder actions. */
    renameFolder: function(folder)
    {
        if (Object.isUndefined(folder)) {
            return;
        }

        folder = $(folder);
        var n = this._createFolderForm(function(e) { this._folderAction(folder, e, 'rename'); return false; }.bindAsEventListener(this), DIMP.text.rename_prompt);
        n.down('input').setValue(folder.readAttribute('l'));
    },

    /* Handle insert folder actions. */
    createBaseFolder: function()
    {
        this._createFolderForm(function(e) { this._folderAction('', e, 'create'); }.bindAsEventListener(this), DIMP.text.create_prompt);
    },

    createSubFolder: function(folder)
    {
        if (Object.isUndefined(folder)) {
            return;
        }

        this._createFolderForm(function(e) { this._folderAction($(folder), e, 'createsub'); }.bindAsEventListener(this), DIMP.text.createsub_prompt);
    },

    _createFolderForm: function(action, text)
    {
        var n = new Element('FORM', { action: '#', id: 'RB_folder' }).insert(
                new Element('P').insert(text)
            ).insert(
                new Element('INPUT', { type: 'text', size: 15 })
            ).insert(
                new Element('INPUT', { type: 'button', id: 'RB_Folder_ok', className: 'button', value: DIMP.text.ok })
            ).insert(
                new Element('INPUT', { type: 'button', id: 'RB_Folder_cancel', className: 'button', value: DIMP.text.cancel })
            );

        this.cfolderaction = action;

        RedBox.overlay = true;
        RedBox.onDisplay = Form.focusFirstElement.curry(n);
        RedBox.showHtml(n);
        return n;
    },

    _closeRedBox: function()
    {
        RedBox.close();
        this.cfolderaction = null;
    },

    _folderAction: function(folder, e, mode)
    {
        this._closeRedBox();

        var action, params, val,
            form = e.findElement('form');
        val = $F(form.down('input'));

        if (val) {
            switch (mode) {
            case 'rename':
                if (folder.readAttribute('l') != val) {
                    action = 'RenameFolder';
                    params = { old_name: folder.readAttribute('mbox'),
                               new_parent: folder.up().hasClassName('folderlist') ? '' : folder.up(1).previous().readAttribute('mbox'),
                               new_name: val };
                }
                break;

            case 'create':
            case 'createsub':
                action = 'CreateFolder';
                params = { view: val };
                if (mode == 'createsub') {
                    params.parent = folder.readAttribute('mbox');
                }
                break;
            }
            if (action) {
                DimpCore.doAction(action, params, null, this.bcache.get('folderC') || this.bcache.set('folderC', this._folderCallback.bind(this)));
            }
        }
    },

    /* Folder action callback functions. */
    _folderCallback: function(r)
    {
        r = r.response;
        if (r.d) {
            r.d.each(this.bcache.get('deleteFolder') || this.bcache.set('deleteFolder', this.deleteFolder.bind(this)));
        }
        if (r.c) {
            r.c.each(this.bcache.get('changeFolder') || this.bcache.set('changeFolder', this.changeFolder.bind(this)));
        }
        if (r.a) {
            r.a.each(this.bcache.get('createFolder') || this.bcache.set('createFolder', this.createFolder.bind(this)));
        }
    },

    _deleteCallback: function(r)
    {
        var search = null, uids = [], vs;

        this.msgListLoading(false);
        this._pollFoldersCallback(r);

        r = r.response;
        if (!r.uids || r.folder != this.folder) {
            return;
        }
        r.uids = DimpCore.parseRangeString(r.uids);

        // Need to convert uid list to listing of unique viewport IDs since
        // we may be dealing with multiple mailboxes (i.e. virtual folders)
        vs = this.viewport.getViewportSelection(this.folder);
        if (vs.getBuffer().getMetaData('search')) {
            $H(r.uids).each(function(pair) {
                pair.value.each(function(v) {
                    uids.push(v + pair.key);
                });
            });

            search = this.viewport.getViewportSelection().search({ vp_id: { equal: uids } });
        } else {
            r.uids = r.uids[this.folder];
            r.uids.each(function(f, u) {
                uids.push(u + f);
            }.curry(this.folder));
            search = this.viewport.createSelection('uid', r.uids);
        }

        if (search.size()) {
            if (r.remove) {
                this.viewport.remove(search, { cacheid: r.cacheid, noupdate: r.viewport });
                this._expirePPCache(uids);
            } else {
                // Need this to catch spam deletions.
                this.viewport.updateFlag(search, 'deletedmsg', true);
            }
        }
    },

    _emptyFolderCallback: function(r)
    {
        if (r.response.mbox) {
            if (this.folder == r.response.mbox) {
                this.viewport.reload();
                this.clearPreviewPane();
            }
            this.setFolderLabel(r.response.mbox, 0);
        }
    },

    _flagAllCallback: function(r)
    {
        if (r.response.mbox) {
            this.setFolderLabel(r.response.mbox, r.response.u);
        }
    },

    _folderLoadCallback: function(r)
    {
        this._folderCallback(r);

        var nf = $('normalfolders'),
            nfheight = nf.getStyle('max-height');

        $('foldersLoading').hide();
        $('foldersSidebar').show();

        // Fix for IE6 - which doesn't support max-height.  We need to search
        // for height: 0px instead (comment in IE 6 CSS explains this is
        // needed for auto sizing).
        if (nfheight !== null ||
            (Prototype.Browser.IE &&
             Object.isUndefined(nfheight) &&
             (nf.getStyle('height') == '0px'))) {
            this._sizeFolderlist();
            Event.observe(window, 'resize', this._sizeFolderlist.bind(this));
        }

        if (r.response.quota) {
            this._displayQuota(r.response.quota);
        }
    },

    _handleFolderMouseClick: function(e)
    {
        var elt = e.element(),
            li = elt.up('.folder') || elt.up('.custom');

        if (!li) {
            return;
        }

        if (elt.hasClassName('exp') || elt.hasClassName('col')) {
            this._toggleSubFolder(li.id, 'tog');
        } else {
            switch (li.readAttribute('ftype')) {
            case 'container':
            case 'vcontainer':
                e.stop();
                break;

            case 'folder':
            case 'special':
            case 'virtual':
                e.stop();
                return this.go('folder:' + li.readAttribute('mbox'));
                break;
            }
        }
    },

    _toggleSubFolder: function(base, mode)
    {
        base = $(base);
        var opts = { duration: 0.2, queue: { position: 'end', scope: 'subfolder', limit: 2 } },
            s = $(this.getSubFolderId(base.id));
        if (s &&
            (mode == 'tog' ||
             (mode == 'exp' && !s.visible()) ||
             (mode == 'col' && s.visible()))) {
            if (base.descendantOf('specialfolders')) {
                opts.afterFinish = this._sizeFolderlist;
            }
            base.firstDescendant().writeAttribute({ className: s.visible() ? 'exp' : 'col' });
            if (s.visible()) {
                Effect.BlindUp(s, opts);
            } else {
                Effect.BlindDown(s, opts);
            }
        }
    },

    // Folder actions.
    // For format of the ob object, see DIMP::_createFolderElt().
    createFolder: function(ob)
    {
        var div, f_node, li, ll, parent_e,
            fid = this.getFolderId(ob.m),
            mbox = decodeURIComponent(ob.m),
            submboxid = this.getSubFolderId(fid),
            submbox = $(submboxid),
            ftype = ob.v ? (ob.co ? 'vcontainer' : 'virtual') : (ob.co ? 'container' : (ob.s ? 'special' : 'folder'));

        li = new Element('LI', { className: 'folder', id: fid, l: ob.l, mbox: mbox, ftype: ftype });

        div = new Element('DIV', { className: ob.cl || 'base', id: fid + '_div' });
        if (ob.i) {
            div.setStyle({ backgroundImage: 'url("' + ob.i + '")' });
        }
        if (ob.ch) {
            div.writeAttribute({ className: 'exp' });
        }

        li.insert(div).insert(new Element('A', { id: fid + '_label', title: ob.l }).insert(ob.l));

        // Now walk through the parent <ul> to find the right place to
        // insert the new folder.
        if (submbox) {
            if (submbox.insert({ before: li }).visible()) {
                // If an expanded parent mailbox was deleted, we need to toggle
                // the icon accordingly.
                div.removeClassName('exp').addClassName('col');
            }
        } else {
            if (ob.s) {
                parent_e = $('specialfolders');
            } else {
                parent_e = $(this.getSubFolderId(this.getFolderId(ob.pa)));
                parent_e = (parent_e) ? parent_e.down() : $('normalfolders');
            }

            ll = mbox.toLowerCase();
            f_node = parent_e.childElements().find(function(node) {
                var nodembox = node.readAttribute('mbox');
                return nodembox &&
                       (!ob.s || nodembox != 'INBOX') &&
                       (ll < nodembox.toLowerCase());
            });

            if (f_node) {
                f_node.insert({ before: li });
            } else {
                parent_e.insert(li);
            }

            // Make sure the sub<mbox> ul is created if necessary.
            if (ob.ch) {
                li.insert({ after: new Element('LI', { className: 'subfolders', id: submboxid }).insert(new Element('UL')).hide() });
            }
        }

        // Make the new folder a drop target.
        if (!ob.v) {
            new Drop(li, this._folderDropConfig);
        }

        // Check for unseen messages
        if (ob.po) {
            li.writeAttribute('u', '');
            this.setFolderLabel(mbox, ob.u);
        }

        switch (ftype) {
        case 'container':
        case 'folder':
            new Drag(li, this._folderDragConfig);
            break;

        case 'special':
            // For purposes of the contextmenu, treat special folders
            // like regular folders.
            ftype = 'folder';
            break;

        case 'vcontainer':
        case 'virtual':
            li.observe('contextmenu', Event.stop);
            break;
        }

        this._addMouseEvents({ id: fid, type: ftype });
    },

    deleteFolder: function(folder)
    {
        var f = decodeURIComponent(folder), fid;
        if (this.folder == f) {
            this.go('folder:INBOX');
        }

        fid = this.getFolderId(folder);
        this.deleteFolderElt(fid, true);
    },

    changeFolder: function(ob)
    {
        var fid = this.getFolderId(ob.m),
            fdiv = $(fid + '_div'),
            oldexpand = fdiv && fdiv.hasClassName('col');
        this.deleteFolderElt(fid, !ob.ch);
        if (ob.co && this.folder == ob.m) {
            this.go('folder:INBOX');
        }
        this.createFolder(ob);
        if (ob.ch && oldexpand) {
            fdiv.removeClassName('exp').addClassName('col');
        }
    },

    deleteFolderElt: function(fid, sub)
    {
        var f = $(fid), submbox;
        if (sub) {
            submbox = $(this.getSubFolderId(fid));
            if (submbox) {
                submbox.remove();
            }
        }
        [ DragDrop.Drags.get_drag(fid), DragDrop.Drops.get_drop(fid) ].compact().invoke('destroy');
        this._removeMouseEvents(f);
        if (this.viewport) {
            this.viewport.deleteView(fid);
        }
        f.remove();
    },

    _sizeFolderlist: function()
    {
        var nf = $('normalfolders');
        nf.setStyle({ height: (document.viewport.getHeight() - nf.cumulativeOffset()[1] - 10) + 'px' });
    },

    /* Flag actions for message list. */
    // opts = 'index', 'mailbox', 'noserver' (only for answered/unanswered)
    flag: function(action, opts)
    {
        var actionCall, args, vs,
            obs = [],
            unseenstatus = 1;
        opts = opts || {};

        if (opts.index) {
            if (opts.mailbox) {
                vs = this.viewport.getViewportSelection().search({ imapuid: { equal: [ opts.index ] }, view: { equal: [ opts.mailbox ] } });
                if (!vs.size() && opts.mailbox != this.folder) {
                    vs = this.viewport.getViewportSelection(opts.mailbox).search({ imapuid: { equal: [ opts.index ] } });
                }
            } else {
                vs = this.viewport.createSelection('dataob', opts.index);
            }
        } else {
            vs = this.viewport.getSelected();
        }

        switch (action) {
        case 'allUnseen':
        case 'allSeen':
            DimpCore.doAction((action == 'allUnseen') ? 'MarkFolderUnseen' : 'MarkFolderSeen', { view: opts.mailbox }, null, this.bcache.get('flagAC') || this.bcache.set('flagAC', this._flagAllCallback.bind(this)));
            if (opts.mailbox == this.folder) {
                this.viewport.updateFlag(this.createSelection('rownum', $A($R(1, this.viewport.getMetaData('total_rows')))), 'unseen', action == 'allUnseen');
            }
            break;

        case 'deleted':
        case 'undeleted':
        case 'spam':
        case 'ham':
        case 'blacklist':
        case 'whitelist':
            if (!vs.size()) {
                break;
            }

            // Make sure that any given row is not deleted more than once.
            // Need to explicitly mark here because message may already be
            // flagged deleted when we load page (i.e. switching to using
            // trash folder).
            if (action == 'deleted') {
                vs = vs.search({ isdel: { not: [ true ] } });
                if (!vs.size()) {
                    break;
                }
                vs.set({ isdel: true });
            }

            args = this.viewport.addRequestParams({});
            if (action == 'deleted' || action == 'undeleted') {
                this.viewport.updateFlag(vs, 'deletedmsg', action == 'deleted');            }

            if (action == 'undeleted') {
                DimpCore.doAction('UndeleteMessage', args, vs);
            } else {
                actionCall = { deleted: 'DeleteMessage', spam: 'ReportSpam', ham: 'ReportHam', blacklist: 'Blacklist', whitelist: 'Whitelist' };
                // This needs to be synchronous Ajax if we are calling from a
                // popup window because Mozilla will not correctly call the
                // callback function if the calling window has been closed.
                DimpCore.doAction(actionCall[action], args, vs, this.bcache.get('deleteC') || this.bcache.set('deleteC', this._deleteCallback.bind(this)), { asynchronous: !(opts.index && opts.mailbox) });

                // If reporting spam, to indicate to the user that something is
                // happening (since spam reporting may not be instantaneous).
                if (action == 'spam' || action == 'ham') {
                    this.msgListLoading(true);
                }
            }
            break;

        case 'unseen':
        case 'seen':
            if (!vs.size()) {
                break;
            }
            args = { view: this.folder, flags: [ '-\\seen' ].toJSON() };
            if (action == 'seen') {
                unseenstatus = 0;
                args.flags = [ '\\seen' ];
            }
            obs = vs.get('dataob');
            if (obs.size()) {
                obs.each(function(s) {
                    this.updateUnseenUID(s, unseenstatus);
                }, this);
                DimpCore.doAction('MarkMessage', args, this.viewport.createSelection('dataob', obs));
            }
            break;

        case 'flagged':
        case 'clear':
            if (!vs.size()) {
                break;
            }
            args = {
                view: this.folder,
                flags: [ ((action == 'flagged') ? '' : '-') + '\\flagged' ].toJSON()
            };
            this.viewport.updateFlag(vs, 'flagged', action == 'flagged');
            DimpCore.doAction('MarkMessage', args, vs);
            break;

        case 'answered':
            if (!vs.size()) {
                break;
            }
            this.viewport.updateFlag(vs, 'answered', true);
            this.viewport.updateFlag(vs, 'flagged', false);
            if (!opts.noserver) {
                args = {
                    view: this.folder,
                    flags: [ '\\answered', '-\\flagged' ].toJSON()
                };
                DimpCore.doAction('MarkMessage', args, vs);
            }
            break;

        case 'unanswered':
            if (!vs.size()) {
                break;
            }
            this.viewport.updateFlag(vs, 'answered', false);
            if (!opts.noserver) {
                args = {
                    view: this.folder,
                    flags: [ '-\\answered' ].toJSON()
                };
                DimpCore.doAction('MarkMessage', args, vs);
            }
            break;

        case 'draft':
        case 'notdraft':
            if (!vs.size()) {
                break;
            }
            args = {
                view: this.folder,
                flags: [ ((action == 'draft') ? '' : '-') + '\\draft' ].toJSON()
            };
            this.viewport.updateFlag(vs, 'draft', action == 'draft');
            DimpCore.doAction('MarkMessage', args, vs);
            break;

        case 'forwarded':
            this.viewport.updateFlag(vs, 'forwarded', true);
            break;
        }
    },

    updateStatusFlags: function(row)
    {
        var elt = new Element('DIV'),
            r = $(row.domid),
            s = r.down('.msgStatus'),
            tmp;

        // Add attachment graphic
        if (row.atc) {
            tmp = 'status' + row.atc.capitalize();
            if (!s.down('.' + tmp)) {
                s.insert($(elt.cloneNode(false)).writeAttribute({ className: tmp, title: DIMP.conf.atc_list[row.atc] || null }));
            }
        }

        this.flags.each(function(c) {
            tmp = 'status' + c.value;
            var d = s.down('.' + tmp);
            if (r.hasClassName(c.key)) {
                if (!d) {
                    s.insert($(elt.cloneNode(false)).addClassName(tmp));
                }
            } else if (d) {
                d.remove();
            }
        });
    },

    /* Miscellaneous folder actions. */
    purgeDeleted: function()
    {
        DimpCore.doAction('PurgeDeleted', this.viewport.addRequestParams({}), null, this.bcache.get('deleteC') || this.bcache.set('deleteC', this._deleteCallback.bind(this)));
    },

    modifyPollFolder: function(folder, add)
    {
        DimpCore.doAction('ModifyPollFolder', { view: folder, add: (add) ? 1 : 0 }, null, this.bcache.get('modifyPFC') || this.bcache.set('modifyPFC', this._modifyPollFolderCallback.bind(this)));
    },

    _modifyPollFolderCallback: function(r)
    {
        r = r.response;
        var f = r.folder, fid, p = { response: { poll: {} } };
        fid = $(this.getFolderId(f));

        if (r.add) {
            p.response.poll[f] = r.poll.u;
            fid.writeAttribute('u', 0);
        } else {
            p.response.poll[f] = 0;
        }

        this._pollFoldersCallback(p);

        if (!r.add) {
            fid.removeAttribute('u');
        }
    },

    msgListLoading: function(show)
    {
        var ml_offset;

        if (this.fl_visible != show) {
            this.fl_visible = show;
            if (show) {
                ml_offset = $('msgList').positionedOffset();
                $('folderLoading').setStyle({ position: 'absolute', top: (ml_offset.top + 10) + 'px', left: (ml_offset.left + 10) + 'px' });
                Effect.Appear('folderLoading', { duration: 0.2 });
                $(document.body).setStyle({ cursor: 'progress' });
            } else {
                Effect.Fade('folderLoading', { duration: 0.2 });
                $(document.body).setStyle({ cursor: 'default' });
            }
        }
    },

    // p = (element) Parent element
    // c = (element) Child element
    isSubfolder: function(p, c)
    {
        var sf = $(this.getSubFolderId(p.identify()));
        return sf && c.descendantOf(sf);
    },

    /* Pref updating function. */
    _updatePrefs: function(pref, value)
    {
        new Ajax.Request(DimpCore.addURLParam(DIMP.conf.URI_PREFS), { parameters: { app: 'imp', pref: pref, value: value } });
    },

    /* Onload function. */
    onDomLoad: function()
    {
        var DC = DimpCore;

        $('dimpLoading').hide();
        $('dimpPage').show();

        /* Create the folder list. Any pending notifications will be caught
         * via the return from this call. */
        DC.doAction('ListFolders', {}, null, this._folderLoadCallback.bind(this));

        /* Start message list loading as soon as possible. */
        if (Horde.dhtmlHistory.initialize()) {
            Horde.dhtmlHistory.addListener(this.go.bind(this));
        }

        /* Initialize the starting page if necessary. addListener() will have
         * already fired if there is a current location so only do a go()
         * call if there is no current location. */
        if (!Horde.dhtmlHistory.getCurrentLocation()) {
            if (DIMP.conf.login_view == 'inbox') {
                this.go('folder:INBOX');
            } else {
                this.go('portal');
                if (DIMP.conf.background_inbox) {
                    this.loadFolder('INBOX', true);
                }
            }
        }

        this._setFilterText(true);

        /* Add popdown menus. */
        DC.DMenu.setOnShow(this._onMenuShow.bind(this));
        this._addMouseEvents({ id: 'button_reply', type: 'reply' }, true);
        DC.DMenu.disable('button_reply_img', true, true);
        this._addMouseEvents({ id: 'button_forward', type: 'forward' }, true);
        DC.DMenu.disable('button_forward_img', true, true);
        this._addMouseEvents({ id: 'button_other', type: 'otheractions' }, true);

        new Drop('dropbase', this._folderDropConfig);

        if (DIMP.conf.toggle_pref) {
            this._toggleHeaders($('th_expand'));
        }

        this._resizeIE6();

        /* Remove unneeded search folders. */
        if (!DIMP.conf.search_all) {
            this.sfiltersfolder.unset('sf_all');
        }

        /* Check for new mail. */
        this.setPollFolders();

        /* Bind key shortcuts. */
        document.observe('keydown', this._keydownHandler.bindAsEventListener(this));
        document.observe('keyup', this._keyupHandler.bindAsEventListener(this));

        /* Bind mouse clicks. */
        document.observe('click', this._clickHandler.bindAsEventListener(this));
        document.observe('dblclick', this._clickHandler.bindAsEventListener(this, true));
        document.observe('mouseover', this._mouseHandler.bindAsEventListener(this, 'over'));

        /* Resize elements on window size change. */
        Event.observe(window, 'resize', this._onResize.bind(this));

        if (DIMP.conf.is_ie6) {
            /* Disable text selection in preview pane for IE 6. */
            document.observe('selectstart', Event.stop);

            /* Since IE 6 doesn't support hover over non-links, use javascript
             * events to replicate mouseover CSS behavior. */
            $('dimpbarActions', 'serviceActions', 'applicationfolders', 'specialfolders', 'normalfolders').compact().invoke('select', 'LI').flatten().compact().each(function(e) {
                e.observe('mouseover', e.addClassName.curry('over')).observe('mouseout', e.removeClassName.curry('over'));
            });
        }
    },

    // IE 6 width fixes (See Bug #6793)
    _resizeIE6: function()
    {
        if (DIMP.conf.is_ie6) {
            var tmp = parseInt($('sidebarPanel').getStyle('width'), 10),
                tmp1 = document.viewport.getWidth() - tmp - 30;
            $('normalfolders').setStyle({ width: tmp + 'px' });
            $('dimpmain').setStyle({ width: tmp1 + 'px' });
            $('msglist').setStyle({ width: (tmp1 - 5) + 'px' });
            $('msgBody').setStyle({ width: (tmp1 - 25) + 'px' });
            tmp = $('dimpmain_portal').down('IFRAME');
            if (tmp) {
                this._resizeIE6Iframe(tmp);
            }
        }
    },

    _resizeIE6Iframe: function(iframe)
    {
        if (DIMP.conf.is_ie6) {
            iframe.setStyle({ width: $('dimpmain').getStyle('width'), height: (document.viewport.getHeight() - 20) + 'px' });
        }
    }
};

/* Need to add after DimpBase is defined. */
DimpBase._msgDragConfig = {
    scroll: 'normalfolders',
    threshold: 5,
    caption: DimpBase.dragCaption.bind(DimpBase),
    onStart: function(d, e) {
        var args = { right: e.isRightClick() },
            id = d.element.id;

        d.selectIfNoDrag = false;

        // Handle selection first.
        if (!args.right && (e.ctrlKey || e.metaKey)) {
            DimpBase.msgSelect(id, $H({ ctrl: true }).merge(args).toObject());
        } else if (e.shiftKey) {
            DimpBase.msgSelect(id, $H({ shift: true }).merge(args).toObject());
        } else if (e.element().hasClassName('msCheck')) {
            DimpBase.msgSelect(id, { ctrl: true, right: true });
        } else if (DimpBase.isSelected('domid', id)) {
            if (!args.right && DimpBase.selectedCount()) {
                d.selectIfNoDrag = true;
            }
        } else {
            DimpBase.msgSelect(id, args);
        }
    },
    onEnd: function(d, e) {
        if (d.selectIfNoDrag && !d.wasDragged) {
            DimpBase.msgSelect(d.element.id, { right: e.isRightClick() });
        }
    }
};

DimpBase._folderDragConfig = {
    ghosting: true,
    offset: { x: 5, y: 5 },
    scroll: 'normalfolders',
    threshold: 5,
    onDrag: function(d, e) {
        if (!d.wasDragged) {
            $('newfolder').hide();
            $('dropbase').show();
            d.ghost.removeClassName('on');
        }
    },
    onEnd: function(d, e) {
        if (d.wasDragged) {
            $('newfolder').show();
            $('dropbase').hide();
        }
    }
};

DimpBase._folderDropConfig = {
    hoverclass: 'dragdrop',
    caption: function(drop, drag, e) {
        var m,
            d = drag.readAttribute('l'),
            ftype = drop.readAttribute('ftype'),
            l = drop.readAttribute('l');

        if (drop == $('dropbase')) {
            return DIMP.text.moveto.replace(/%s/, d).replace(/%s/, DIMP.text.baselevel);
        } else {
            m = (e.ctrlKey) ? DIMP.text.copyto : DIMP.text.moveto;
            if (drag.hasClassName('folder')) {
                return (ftype != 'special' && !DimpBase.isSubfolder(drag, drop)) ? m.replace(/%s/, d).replace(/%s/, l) : '';
            } else {
                return ftype != 'container' ? m.replace(/%s/, DimpBase.dragCaption()).replace(/%s/, l) : '';
            }
        }
    },
    onDrop: DimpBase._folderDropHandler.bind(DimpBase)
};

/* Need to register a callback function for doAction to catch viewport
 * information returned from the server. */
DimpCore.onDoActionComplete = function(r) {
    if (DimpBase.viewport && r.response.viewport) {
        DimpBase.viewport.ajaxResponse(r.response.viewport);
    }
};

/* Stuff to do immediately when page is ready. */
document.observe('dom:loaded', DimpBase.onDomLoad.bind(DimpBase));

