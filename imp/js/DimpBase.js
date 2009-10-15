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
    //   cfolderaction, folder, folderswitch, offset, pollPE, pp, sfolder,
    //   uid, viewport
    // message_list_template set via js/mailbox-dimp.js
    bcache: $H(),
    cacheids: {},
    lastrow: -1,
    pivotrow: -1,
    ppcache: {},
    ppfifo: [],
    showunsub: 0,
    tcache: {},

    // Preview pane cache size is 20 entries. Given that a reasonable guess
    // of an average e-mail size is 10 KB (including headers), also make
    // an estimate that the JSON data size will be approx. 10 KB. 200 KB
    // should be a fairly safe caching value for any recent browser.
    ppcachesize: 20,

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
        if ($('qsearch')) {
            $('qsearch_input').blur();
        }

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

        /* If switching from options, we need to reload page to pick up any
         * prefs changes. */
        if (this.folder === null &&
            loc != 'options' &&
            $('appoptions') &&
            $('appoptions').hasClassName('on')) {
            $('dimpPage').hide();
            $('dimpLoading').show();
            return DimpCore.redirect(DIMP.conf.URI_DIMP + '#' + loc, true);
        }

        if (loc.startsWith('compose:')) {
            return;
        }

        if (loc.startsWith('msg:')) {
            separator = loc.indexOf(':', 4);
            f = loc.substring(4, separator);
            this.uid = parseInt(loc.substring(separator + 1), 10);
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
                if (!Object.isUndefined(this.folder) && !this.sfolder) {
                    this._addHistory(loc);
                }

                if (this.isSearch(f) && !this.sfolder) {
                    this._quicksearchDeactivate();
                }
            }
            this.loadMailbox(f);
            return;
        }

        f = this.folder;
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
        case 'search':
            // data: 'edit_query' = folder to edit; otherwise, loads search
            //       screen with current mailbox as default search mailbox
            if (!data) {
                data = { search_mailbox: f };
            }
            this.highlightSidebar();
            DimpCore.setTitle(DIMP.text.search);
            this.iframeContent(loc, DimpCore.addURLParam(DIMP.conf.URI_SEARCH, data));
            break;

        case 'portal':
            this.highlightSidebar('appportal');
            this._addHistory(loc);
            DimpCore.setTitle(DIMP.text.portal);
            DimpCore.doAction('ShowPortal', {}, null, this.bcache.get('portalC') || this.bcache.set('portalC', this._portalCallback.bind(this)));
            break;

        case 'options':
            this.highlightSidebar('appoptions');
            DimpCore.setTitle(DIMP.text.prefs);
            this.iframeContent(loc, DIMP.conf.URI_PREFS_IMP);
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

        var curr = $('sidebarPanel').down('.on'),
            elt = $(id);

        if (curr == elt) {
            return;
        }

        if (elt && !elt.match('LI')) {
            elt = elt.up();
            if (!elt) {
                return;
            }
        }

        if (curr) {
            curr.removeClassName('on');
        }

        if (elt) {
            elt.addClassName('on');
            this._toggleSubFolder(elt, 'exp');
        }
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
        container.insert(iframe);
    },

    // r = ViewPort row data
    msgWindow: function(r)
    {
        this.updateSeenUID(r, 1);
        var url = DIMP.conf.URI_MESSAGE;
        url += (url.include('?') ? '&' : '?') +
               $H({ folder: r.view,
                    uid: Number(r.imapuid) }).toQueryString();
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

    loadMailbox: function(f, opts)
    {
        opts = opts || {};

        if (!this.viewport) {
            this._createViewPort();
        }

        if (!opts.background) {
            this.resetSelected();
            this.quicksearchClear(true);

            if (this.folder != f) {
                $('folderName').update(DIMP.text.loading);
                $('msgHeader').update();
                this.folderswitch = true;
                this.folder = f;
            }
        }

        this.viewport.loadView(f, this.uid ? { imapuid: Number(this.uid), view: f } : null, opts.background);
    },

    _createViewPort: function()
    {
        this.viewport = new ViewPort({
            // Mandatory config
            ajax_url: DIMP.conf.URI_AJAX + '/ViewPort',
            content: 'msgList',
            template: this.message_list_template,

            // Optional config
            ajax_opts: DimpCore.doActionOpts,
            buffer_pages: DIMP.conf.buffer_pages,
            content_class: 'msglist',
            empty_msg: DIMP.text.vp_empty,
            limit_factor: DIMP.conf.limit_factor,
            page_size: DIMP.conf.splitbar_pos,
            show_split_pane: DIMP.conf.preview_pref,
            split_bar: 'splitBar',
            split_pane: 'previewPane',
            wait: DIMP.conf.viewport_wait,

            // Callbacks
            onAjaxRequest: function(id) {
                var p = this.isSearch(id, true) && $('qsearch_input').visible()
                    ? $H({
                        qsearch: $F('qsearch_input'),
                        qsearchmbox: this.sfolder
                    })
                    : $H();
                return DimpCore.addRequestParams(p);
            }.bind(this),
            onAjaxResponse: DimpCore.doActionComplete.bind(DimpCore),
            onCachedList: function(id) {
                var tmp, vs;
                if (!this.cacheids[id]) {
                    vs = this.viewport.getSelection(id);
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
            onCacheUpdate: function(id) {
                delete this.cacheids[id];
            }.bind(this),
            onClear: function(r) {
                r.each(this._removeMouseEvents.bind(this));
            }.bind(this),
            onContent: function(row) {
                var bg, re, u,
                    thread = $H(this.viewport.getMetaData('thread')),
                    tsort = (this.viewport.getMetaData('sortby') == DIMP.conf.sortthread);

                row.subjectdata = row.status = '';
                row.subjecttitle = row.subject;

                // Add thread graphics
                if (tsort) {
                    u = thread.get(row.imapuid);
                    if (u) {
                        $R(0, u.length, true).each(function(i) {
                            var c = u.charAt(i);
                            if (!this.tcache[c]) {
                                this.tcache[c] = '<span class="treeImg treeImg' + c + '"></span>';
                            }
                            row.subjectdata += this.tcache[c];
                        }, this);
                    }
                }

                /* Generate the status flags. */
                if (row.flag) {
                    row.flag.each(function(a) {
                        var ptr = DIMP.conf.flags[a];
                        if (ptr.p) {
                            if (!ptr.elt) {
                                /* Until text-overflow is supported on all
                                 * browsers, need to truncate label text
                                 * ourselves. */
                                ptr.elt = '<span class="' + ptr.c + '" title="' + ptr.l + '" style="background:' + ptr.b + ';color:' + ptr.f + '">' + ptr.l.truncate(10) + '</span>';
                            }
                            row.subjectdata += ptr.elt;
                        } else {
                            if (!ptr.elt) {
                                ptr.elt = '<div class="msgflags ' + ptr.c + '" title="' + ptr.l + '"></div>';
                            }
                            row.status += ptr.elt;

                            row.bg.push(ptr.c);

                            if (ptr.b) {
                                bg = ptr.b;
                            }
                        }
                    });
                }

                // Set bg
                if (bg) {
                    row.style = 'background:' + bg;
                }

                // Check for search strings
                if (this.isSearch(null, true)) {
                    re = new RegExp("(" + $F('qsearch_input') + ")", "i");
                    [ 'from', 'subject' ].each(function(h) {
                        row[h] = row[h].gsub(re, '<span class="qsearchMatch">#{1}</span>');
                    });
                }

                // If these fields are null, invalid string was scrubbed by
                // JSON encode.
                if (row.from === null) {
                    row.from = '[' + DIMP.text.badaddr + ']';
                }
                if (row.subject === null) {
                    row.subject = row.subjecttitle = '[' + DIMP.text.badsubject + ']';
                }
            }.bind(this),
            onContentComplete: function(rows) {
                var row, ssc, tmp,
                    l = this.viewport.getMetaData('label');

                this.setMessageListTitle();
                if (!this.isSearch()) {
                    this.setFolderLabel(this.folder, this.viewport.getMetaData('unseen') || 0);
                }
                this.updateTitle();

                rows.each(function(row) {
                    // Add context menu
                    this._addMouseEvents({ id: row.domid, type: row.menutype });
                    new Drag(row.domid, this._msgDragConfig);
                }, this);

                if (this.uid) {
                    row = this.viewport.getSelection().search({ imapuid: { equal: [ this.uid ] }, view: { equal: [ this.folder ] } });
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
                    if (this.isSearch(null, true)) {
                        l += ' (' + this.sfolder + ')';
                    }
                    $('folderName').update(l);
                }

                if (this.folderswitch) {
                    this.folderswitch = false;

                    tmp = $('applyfilterlink');
                    if (tmp) {
                        if (this.isSearch() ||
                            (!DIMP.conf.filter_any &&
                             this.folder.toUpperCase() != 'INBOX')) {
                            tmp.hide();
                        } else {
                            tmp.show();
                        }
                    }

                    if (this.folder == DIMP.conf.spam_mbox) {
                        if (!DIMP.conf.spam_spammbox && $('button_spam')) {
                            [ $('button_spam').up(), $('ctx_message_spam') ].invoke('hide');
                        }
                        if ($('button_ham')) {
                            [ $('button_ham').up(), $('ctx_message_ham') ].invoke('show');
                        }
                    } else {
                        if ($('button_spam')) {
                            [ $('button_spam').up(), $('ctx_message_spam') ].invoke('show');
                        }
                        if ($('button_ham')) {
                            [ $('button_ham').up(), $('ctx_message_ham') ].invoke(DIMP.conf.ham_spammbox ? 'hide' : 'show');
                        }
                    }

                    /* Read-only changes. 'oa_setflag' is handled
                     * elsewhere. */
                    tmp = [ $('button_deleted') ].compact().invoke('up', 'SPAN');
                    [ 'ctx_message_', 'ctx_draft_' ].each(function(c) {
                        tmp = tmp.concat($(c + 'deleted', c + 'setflag', c + 'undeleted'));
                    });

                    if (this.viewport.getMetaData('readonly')) {
                        tmp.compact().invoke('hide');
                        $('folderName').next().show();
                    } else {
                        tmp.compact().invoke('show');
                        $('folderName').next().hide();
                    }
                } else if (this.filtertoggle &&
                           this.viewport.getMetaData('sortby') == DIMP.conf.sortthread) {
                    ssc = DIMP.conf.sortdate;
                }

                this.setSortColumns(ssc);
            }.bind(this),
            onDeselect: this._deselect.bind(this),
            onEndFetch: this.loadingImg.bind(this, 'viewport', false),
            onFail: function() {
                if ($('dimpmain_folder').visible()) {
                    DimpCore.showNotifications([ { type: 'horde.error', message: DIMP.text.listmsg_timeout } ]);
                }
                this.loadingImg('viewport', false);
            }.bind(this),
            onFetch: this.loadingImg.bind(this, 'viewport', true),
            onSelect: this._select.bind(this),
            onSlide: this.setMessageListTitle.bind(this),
            onSplitBarChange: function() {
                this._updatePrefs('dimp_splitbar', this.viewport.getPageSize());
            }.bind(this),
            onSplitBarEnd: function() {
                $('msgBodyCover').hide();
            },
            onSplitBarStart: function() {
                $('msgBodyCover').clonePosition('msgBody').show();
            },
            onWait: function() {
                if ($('dimpmain_folder').visible()) {
                    DimpCore.showNotifications([ { type: 'horde.warning', message: DIMP.text.listmsg_wait } ]);
                }
            }
        });

        // If starting in no preview mode, need to set the no preview class
        if (!DIMP.conf.preview_pref) {
            $('msgList').addClassName('msglistNoPreview');
        }
    },

    _addMouseEvents: function(p, popdown)
    {
        if (popdown) {
            popdown.insert({ after: new Element('SPAN', { className: 'iconImg popdownImg popdown', id: p.id + '_img' }) });
            p.id += '_img';
            p.offset = popdown.up();
            p.left = true;
        }

        DimpCore.DMenu.addElement(p.id, 'ctx_' + p.type, p);
    },

    _removeMouseEvents: function(elt)
    {
        var d, id = $(elt).readAttribute('id');

        if (id) {
            if (d = DragDrop.Drags.getDrag(id)) {
                d.destroy();
            }

            DimpCore.DMenu.removeElement(id);
        }
    },

    contextOnClick: function(parentfunc, elt, baseelt, menu)
    {
        var flag, id = elt.readAttribute('id'), tmp;

        switch (id) {
        case 'ctx_folder_create':
            this.createSubFolder(baseelt);
            break;

        case 'ctx_container_rename':
        case 'ctx_folder_rename':
            this.renameFolder(baseelt);
            break;

        case 'ctx_folder_empty':
            tmp = baseelt.up('LI');
            if (window.confirm(DIMP.text.empty_folder.replace(/%s/, tmp.readAttribute('title')))) {
                DimpCore.doAction('EmptyFolder', { view: tmp.retrieve('mbox') }, null, this._emptyFolderCallback.bind(this));
            }
            break;

        case 'ctx_folder_delete':
        case 'ctx_vfolder_delete':
            tmp = baseelt.up('LI');
            if (window.confirm(DIMP.text.delete_folder.replace(/%s/, tmp.readAttribute('title')))) {
                DimpCore.doAction('DeleteFolder', { view: tmp.retrieve('mbox') }, null, this.bcache.get('folderC') || this.bcache.set('folderC', this._folderCallback.bind(this)));
            }
            break;

        case 'ctx_folder_seen':
        case 'ctx_folder_unseen':
            this.flagAll('\\seen', id == 'ctx_folder_seen', baseelt.up('LI').retrieve('mbox'));
            break;

        case 'ctx_folder_poll':
        case 'ctx_folder_nopoll':
            this.modifyPoll(baseelt.up('LI').retrieve('mbox'), id == 'ctx_folder_poll');
            break;

        case 'ctx_folder_sub':
        case 'ctx_folder_unsub':
            this.subscribeFolder(baseelt.up('LI').retrieve('mbox'), id == 'ctx_folder_sub');
            break;

        case 'ctx_container_create':
            this.createSubFolder(baseelt);
            break;

        case 'ctx_folderopts_new':
            this.createBaseFolder();
            break;

        case 'ctx_folderopts_sub':
        case 'ctx_folderopts_unsub':
            this.toggleSubscribed();
            break;

        case 'ctx_folderopts_expand':
        case 'ctx_folderopts_collapse':
            $('normalfolders').select('LI.folder').each(function(f) {
                this._toggleSubFolder(f, id == 'ctx_folderopts_expand' ? 'exp' : 'col', true);
            }.bind(this));
            break;

        case 'ctx_folderopts_reload':
            this._reloadFolders();
            break;

        case 'ctx_container_expand':
        case 'ctx_container_collapse':
        case 'ctx_folder_expand':
        case 'ctx_folder_collapse':
            tmp = baseelt.up('LI');
            [ tmp, $(this.getSubFolderId(tmp.readAttribute('id'))).select('LI.folder') ].flatten().each(function(f) {
                this._toggleSubFolder(f, (id == 'ctx_container_expand' || id == 'ctx_folder_expand') ? 'exp' : 'col', true);
            }.bind(this));
            break;

        case 'ctx_message_spam':
        case 'ctx_message_ham':
            this.reportSpam(id == 'ctx_message_spam');
            break;

        case 'ctx_message_blacklist':
        case 'ctx_message_whitelist':
            this.blacklist(id == 'ctx_message_blacklist');
            break;

        case 'ctx_draft_deleted':
        case 'ctx_message_deleted':
            this.deleteMsg();
            break;

        case 'ctx_message_forward':
            this.composeMailbox('forward');
            break;

        case 'ctx_draft_resume':
            this.composeMailbox('resume');
            break;

        case 'ctx_reply_reply':
        case 'ctx_reply_reply_all':
        case 'ctx_reply_reply_list':
            this.composeMailbox(id.substring(10));
            break;

        case 'previewtoggle':
            this.togglePreviewPane();
            break;

        case 'oa_blacklist':
        case 'oa_whitelist':
            this.blacklist(id == 'oa_blacklist');
            break;

        case 'ctx_draft_undeleted':
        case 'ctx_message_undeleted':
        case 'oa_undeleted':
            this.flag('\\deleted', false);
            break;

        case 'oa_selectall':
            this.selectAll();
            break;

        case 'oa_purge_deleted':
            this.purgeDeleted();
            break;

        case 'ctx_qsearchopts_basic':
            RedBox.overlay = true;
            RedBox.loading();
            new Ajax.Request(DIMP.conf.URI_SEARCH_BASIC, { parameters: DimpCore.addRequestParams($H({ search_mailbox: this.folder })), onComplete: function(r) { RedBox.showHtml(r.responseText); } });
            break;

        case 'ctx_vfolder_edit':
            tmp = { edit_query: baseelt.up('LI').retrieve('mbox') };
            // Fall through

        case 'ctx_qsearchopts_advanced':
            this.go('search', tmp);
            break;

        case 'ctx_qsearchopts_all':
        case 'ctx_qsearchopts_body':
        case 'ctx_qsearchopts_from':
        case 'ctx_qsearchopts_to':
        case 'ctx_qsearchopts_subject':
            DIMP.conf.qsearchfield = id.substring(16);
            this._updatePrefs('dimp_qsearch_field', DIMP.conf.qsearchfield);
            if (!$('qsearch').hasClassName('qsearchActive')) {
                this._setQsearchText(true);
            }
            break;

        default:
            if (menu.endsWith('_setflag') || menu.endsWith('_unsetflag')) {
                flag = elt.readAttribute('flag');
                this.flag(flag, this.convertFlag(flag, menu.endsWith('_setflag')));
            } else {
                parentfunc(elt, baseelt, menu);
            }
            break;
        }
    },

    contextOnShow: function(parentfunc, ctx_id, baseelt)
    {
        var elts, ob, sel, tmp;

        switch (ctx_id) {
        case 'ctx_folder':
            elts = $('ctx_folder_create', 'ctx_folder_rename', 'ctx_folder_delete');
            baseelt = baseelt.up('LI');

            if (baseelt.retrieve('mbox') == 'INBOX') {
                elts.invoke('hide');
                if ($('ctx_folder_sub')) {
                    $('ctx_folder_sub', 'ctx_folder_unsub').invoke('hide');
                }
            } else {
                if ($('ctx_folder_sub')) {
                    tmp = baseelt.hasClassName('unsubFolder');
                    [ $('ctx_folder_sub') ].invoke(tmp ? 'show' : 'hide');
                    [ $('ctx_folder_unsub') ].invoke(tmp ? 'hide' : 'show');
                }

                if (DIMP.conf.fixed_folders &&
                    DIMP.conf.fixed_folders.indexOf(baseelt.retrieve('mbox')) != -1) {
                    elts.shift();
                    elts.invoke('hide');
                } else {
                    elts.invoke('show');
                }
            }

            tmp = Object.isUndefined(baseelt.retrieve('u'));
            [ $('ctx_folder_poll') ].invoke(tmp ? 'show' : 'hide');
            [ $('ctx_folder_nopoll') ].invoke(tmp ? 'hide' : 'show');

            tmp = $(this.getSubFolderId(baseelt.readAttribute('id')));
            $('ctx_folder_collapse', 'ctx_folder_expand').invoke(tmp ? 'show' : 'hide');
            [ $('ctx_folder_expand').previous() ].invoke(tmp ? 'addClassName' : 'removeClassName', 'sep');
            break;

        case 'ctx_reply':
            sel = this.viewport.getSelected();
            if (sel.size() == 1) {
                ob = sel.get('dataob').first();
            }
            [ $('ctx_reply_reply_list') ].invoke(ob && ob.listmsg ? 'show' : 'hide');
            break;

        case 'ctx_otheractions':
            tmp = $('oa_blacklist', 'oa_whitelist', 'oa_undeleted');
            if (this.viewport.getMetaData('readonly')) {
                $('oa_setflag', 'oa_unsetflag').invoke('hide');
            } else {
                tmp = tmp.concat($('oa_setflag', 'oa_unsetflag'));
            }
            tmp.compact().invoke(this.viewport.getSelected().size() ? 'show' : 'hide');
            break;

        case 'ctx_qsearchopts':
            $(ctx_id).descendants().invoke('removeClassName', 'contextSelected');
            $(ctx_id + '_' + DIMP.conf.qsearchfield).addClassName('contextSelected');
            break;

        default:
            parentfunc(ctx_id, baseelt);
            break;
        }
    },

    updateTitle: function()
    {
        var elt, unseen,
            label = this.viewport.getMetaData('label');

        if (this.isSearch(null, true)) {
            label += ' (' + this.sfolder + ')';
        } else {
            elt = $(this.getFolderId(this.folder));
            if (elt) {
                unseen = elt.retrieve('u');
                if (unseen > 0) {
                    label += ' (' + unseen + ')';
                }
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
        if (this.viewport.getMetaData('special')) {
            tmp.hide().next().show();
        } else {
            tmp.show().next().hide();
        }

        tmp = m.down('div.msgSubject a');
        if (this.isSearch() ||
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
        this._updatePrefs('show_preview', Number(p));
        this.viewport.showSplitPane(p);
        if (p) {
            this.initPreviewPane();
        }
    },

    loadPreview: function(data, params)
    {
        var pp_uid;

        if (!$('previewPane').visible()) {
            return;
        }

        if (!params) {
            if (this.pp &&
                this.pp.imapuid == data.imapuid &&
                this.pp.view == data.view) {
                return;
            }
            this.pp = data;
            pp_uid = this._getPPId(data.imapuid, data.view);

            if (this.ppfifo.indexOf(pp_uid) != -1) {
                  // There is a chance that the message may have been marked
                  // as unseen since first being viewed. If so, we need to
                  // explicitly flag as seen here.
                if (!this.hasFlag('\\seen', data)) {
                    this.flag('\\seen', true);
                }
                return this._loadPreviewCallback(this.ppcache[pp_uid]);
            }
        }

        this.loadingImg('msg', true);

        DimpCore.doAction('ShowPreview', params || {}, this.viewport.createSelection('dataob', this.pp), this.bcache.get('loadPC') || this.bcache.set('loadPC', this._loadPreviewCallback.bind(this)));
    },

    _loadPreviewCallback: function(resp)
    {
        var ppuid, row, search, tmp,
            pm = $('previewMsg'),
            r = resp.response,
            t = $('msgHeadersContent').down('THEAD');

        if (!r.error) {
            search = this.viewport.getSelection().search({ imapuid: { equal: [ r.index ] }, view: { equal: [ r.mailbox ] } });
            if (search.size()) {
                row = search.get('dataob').first();
                this.updateSeenUID(row, 1);
            }
        }

        if (this.pp &&
            (this.pp.imapuid != r.index ||
             this.pp.view != r.mailbox)) {
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
        ppuid = this._getPPId(r.index, r.mailbox);
        this._expirePPCache([ ppuid ]);
        this.ppcache[ppuid] = resp;
        this.ppfifo.push(ppuid);

        DimpCore.removeAddressLinks(pm);

        // Add subject
        tmp = pm.select('.subject');
        tmp.invoke('update', r.subject === null ? '[' + DIMP.text.badsubject + ']' : r.subject);

        // Add date
        $('msgHeadersColl').select('.date').invoke('update', r.minidate);
        $('msgHeaderDate').select('.date').invoke('update', r.localdate);

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
        if (r.atc_label) {
            $('msgAtc').show();
            tmp = $('partlist');
            tmp.hide().previous().update(new Element('SPAN', { className: 'atcLabel' }).insert(r.atc_label)).insert(r.atc_download);
            if (r.atc_list) {
                $('partlist_col').show();
                $('partlist_exp').hide();
                tmp.down('TABLE').update(r.atc_list);
            }
        } else {
            $('msgAtc').hide();
        }

        // Add message information
        if (r.log) {
            this.updateMsgLog(r.log);
        } else {
            $('msgLogInfo').hide();
        }

        $('msgBody').update(r.msgtext);
        this.loadingImg('msg', false);
        $('previewInfo').hide();
        $('previewPane').scrollTop = 0;
        pm.show();

        if (r.js) {
            eval(r.js.join(';'));
        }

        this._addHistory('msg:' + row.view + ':' + row.imapuid);
    },

    // opts = index, mailbox
    updateMsgLog: function(log, opts)
    {
        var tmp;

        if (!opts ||
            (this.pp &&
             this.pp.imapuid == opts.index &&
             this.pp.view == opts.mailbox)) {
            $('msgLogInfo').show();

            if (opts) {
                $('msgloglist_col').show();
                $('msgloglist_exp').hide();
            }

            DimpCore.updateMsgLog(log);
        }

        if (opts) {
            tmp = this._getPPId(opts.index, opts.mailbox);
            if (this.ppcache[tmp]) {
                this.ppcache[tmp].response.log = log;
            }
        }
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
        this.loadingImg('msg', false);
        $('previewMsg').hide();
        $('previewPane').scrollTop = 0;
        $('previewInfo').show();
        this.pp = null;
    },

    _toggleHeaders: function(elt, update)
    {
        if (update) {
            DIMP.conf.toggle_pref = !DIMP.conf.toggle_pref;
            this._updatePrefs('dimp_toggle_headers', Number(elt.id == 'th_expand'));
        }
        [ elt.up().select('A'), $('msgHeadersColl', 'msgHeaders') ].flatten().invoke('toggle');
    },

    _expirePPCache: function(ids)
    {
        this.ppfifo = this.ppfifo.diff(ids);
        ids.each(function(i) {
            delete this.ppcache[i];
        }, this);

        if (this.ppfifo.size() > this.ppcachesize) {
            delete this.ppcache[this.ppfifo.shift()];
        }
    },

    _getPPId: function(index, mailbox)
    {
        return index + '|' + mailbox;
    },

    // Labeling functions
    updateSeenUID: function(r, setflag)
    {
        var isunseen = !this.hasFlag('\\seen', r),
            sel, unseen;

        if ((setflag && !isunseen) || (!setflag && isunseen)) {
            return false;
        }

        sel = this.viewport.createSelection('dataob', r);
        unseen = Number($(this.getFolderId(r.view)).retrieve('u'));

        unseen += setflag ? -1 : 1;
        this.updateFlag(sel, '\\seen', setflag);

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
        var fid = this.getFolderId(f),
            elt = $(fid);

        if (!elt ||
            Object.isUndefined(elt.retrieve('u')) ||
            elt.retrieve('u') == unseen) {
            return;
        }

        unseen = Number(unseen);
        elt.store('u', unseen);

        if (f == 'INBOX' && window.fluid) {
            window.fluid.setDockBadge(unseen ? unseen : '');
        }

        elt.down('A').update((unseen > 0) ?
            new Element('STRONG').insert(elt.retrieve('l')).insert('&nbsp;').insert(new Element('SPAN', { className: 'count', dir: 'ltr' }).insert('(' + unseen + ')')) :
            elt.retrieve('l'));
    },

    getFolderId: function(f)
    {
        return 'fld' + f.replace(/_/g,'__').replace(/\W/g, '_');
    },

    getSubFolderId: function(f)
    {
        if (f.endsWith('_special')) {
            f = f.slice(0, -8);
        }
        return 'sub_' + f;
    },

    /* Folder list updates. */
    poll: function()
    {
        var args = {};

        // Reset poll folder counter.
        this.setPoll();

        // Check for label info - it is possible that the mailbox may be
        // loading but not complete yet and sending this request will cause
        // duplicate info to be returned.
        if (this.folder &&
            $('dimpmain_folder').visible() &&
            this.viewport.getMetaData('label')) {
            args = this.viewport.addRequestParams({});
        }
        $('checkmaillink').down('A').update('[' + DIMP.text.check + ']');
        DimpCore.doAction('Poll', args, null, this.bcache.get('pollFC') || this.bcache.set('pollFC', this._pollCallback.bind(this)));
    },

    _pollCallback: function(r)
    {
        r = r.response;
        if (r.poll) {
            $H(r.poll).each(function(u) {
                this.updateUnseenStatus(u.key, u.value);
            }, this);
        }
        if (r.quota) {
            this._displayQuota(r.quota);
        }
        $('checkmaillink').down('A').update(DIMP.text.getmail);
    },

    _displayQuota: function(r)
    {
        var q = $('quota').cleanWhitespace();
        q.setText(r.m);
        q.down('SPAN.used IMG').writeAttribute('width', 99 - r.p);
    },

    setPoll: function()
    {
        if (DIMP.conf.refresh_time) {
            if (this.pollPE) {
                this.pollPE.stop();
            }
            // Don't cache - this code is only run once.
            this.pollPE = new PeriodicalExecuter(this.poll.bind(this), DIMP.conf.refresh_time);
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

    /* Search functions. */
    isSearch: function(id, qsearch)
    {
        id = id ? id : this.folder;
        return id && id.startsWith(DIMP.conf.searchprefix) && (!qsearch || this.sfolder);
    },

    _quicksearchOnBlur: function()
    {
        $('qsearch').removeClassName('qsearchFocus');
        if (!$F('qsearch_input')) {
            this._setQsearchText(true);
        }
    },

    quicksearchRun: function()
    {
        if (this.isSearch()) {
            this.viewport.reload();
        } else {
            this.sfolder = this.folder;
            this.loadMailbox(DIMP.conf.qsearchid);
        }
    },

    // 'noload' = (boolean) If true, don't load the mailbox
    quicksearchClear: function(noload)
    {
        var f = this.folder;

        if (!$('qsearch').hasClassName('qsearchFocus')) {
            this._setQsearchText(true);
        }

        if (this.isSearch()) {
            DimpCore.DMenu.disable('qsearch_icon', true, false);
            this.resetSelected();
            $('qsearch_input').show();
            if (!noload) {
                this.loadMailbox(this.sfolder || 'INBOX');
            }
            this.viewport.deleteView(f);
            this.sfolder = null;
        }
    },

    // d = (boolean) Deactivate quicksearch input?
    _setQsearchText: function(d)
    {
        $('qsearch_input').setValue(d ? DIMP.text.search + ' (' + $('ctx_qsearchopts_' + DIMP.conf.qsearchfield).getText() + ')' : '');
        [ $('qsearch') ].invoke(d ? 'removeClassName' : 'addClassName', 'qsearchActive');
        $('qsearch_close').hide();
    },

    _basicSearchCallback: function(r)
    {
        r = r.response;
        RedBox.close();
        this.sfolder = this.folder;
        this._searchDeactivate();
        this.go('folder:' + r.view);
    },

    _quicksearchDeactivate: function()
    {
        $('qsearch_close').show();
        $('qsearch_input').hide();
        DimpCore.DMenu.disable('qsearch_icon', true, true);
    },

    /* Enable/Disable DIMP action buttons as needed. */
    toggleButtons: function()
    {
        var disable = (this.selectedCount() == 0);
        $('dimpmain_folder_top').select('DIV.dimpActions A.noselectDisable').each(function(b) {
            [ b.up() ].invoke(disable ? 'addClassName' : 'removeClassName', 'disabled');
            DimpCore.DMenu.disable(b.readAttribute('id') + '_img', true, disable);
        });
    },

    /* Drag/Drop handler. */
    _folderDropHandler: function(drop, drag, e)
    {
        var dropbase, sel, uids,
            foldername = drop.retrieve('mbox'),
            ftype = drop.retrieve('ftype');

        if (drag.hasClassName('folder')) {
            dropbase = (drop == $('dropbase'));
            if (dropbase ||
                (ftype != 'special' && !this.isSubfolder(drag, drop))) {
                DimpCore.doAction('RenameFolder', { old_name: drag.retrieve('mbox'), new_parent: dropbase ? '' : foldername, new_name: drag.retrieve('l') }, null, this.bcache.get('folderC') || this.bcache.set('folderC', this._folderCallback.bind(this)));
            }
        } else if (ftype != 'container') {
            sel = this.viewport.getSelected();

            if (sel.size()) {
                // Dragging multiple selected messages.
                uids = sel;
            } else if (drag.retrieve('mbox') != foldername) {
                // Dragging a single unselected message.
                uids = this.viewport.createSelection('domid', drag.id);
            }

            if (uids.size()) {
                if (e.ctrlKey) {
                    DimpCore.doAction('CopyMessage', this.viewport.addRequestParams({ tofld: foldername }), uids, this.bcache.get('pollFC') || this.bcache.set('pollFC', this._pollCallback.bind(this)));
                } else if (this.folder != foldername) {
                    // Don't allow drag/drop to the current folder.
                    this.updateFlag(uids, '\\deleted', true);
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
    keydownHandler: function(e)
    {
        var co, form, h, pp, ps, r, row, rowoff, sel,
            elt = e.element(),
            kc = e.keyCode || e.charCode;

        // Only catch keyboard shortcuts in message list view.
        if (!$('dimpmain_folder').visible()) {
            return;
        }

        // Form catching - normally we will ignore, but certain cases we want
        // to catch.
        form = e.findElement('FORM');
        if (form) {
            switch (kc) {
            case Event.KEY_ESC:
            case Event.KEY_TAB:
                // Catch escapes in search box
                if (elt.readAttribute('id') == 'qsearch_input') {
                    if (kc == Event.KEY_ESC || !elt.getValue()) {
                        this.quicksearchClear();
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
                } else if (elt.readAttribute('id') == 'qsearch_input') {
                    if ($F('qsearch_input')) {
                        this.quicksearchRun();
                    } else {
                        this.quicksearchClear();
                    }
                    e.stop();
                }
                break;

            default:
                if (elt.readAttribute('id') == 'qsearch_input') {
                    $('qsearch_close').show();
                }
                break;
            }

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
            this.deleteMsg({ vs: sel });
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
            if (e.altKey) {
                pp = $('previewPane');
                h = pp.getHeight();
                if (h != pp.scrollHeight) {
                    switch (kc) {
                    case Event.KEY_PAGEUP:
                        pp.scrollTop = Math.max(pp.scrollTop - h, 0);
                        break;

                    case Event.KEY_PAGEDOWN:
                        pp.scrollTop = Math.min(pp.scrollTop + h, pp.scrollHeight - h + 1);
                        break;
                    }
                }
                e.stop();
            } else if (!e.ctrlKey && !e.shiftKey && !e.metaKey) {
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

    dblclickHandler: function(e)
    {
        if (e.isRightClick()) {
            return;
        }

        var elt = e.element(),
            tmp;

        if (!elt.hasClassName('vpRow')) {
            elt = elt.up('.vpRow');
        }

        if (elt) {
            tmp = this.viewport.createSelection('domid', elt.identify()).get('dataob').first();
            tmp.draft
                ? DimpCore.compose('resume', { folder: tmp.view, uid: tmp.imapuid })
                : this.msgWindow(tmp);
        }

        e.stop();
    },

    clickHandler: function(parentfunc, e)
    {
        if (e.isRightClick() || DimpCore.DMenu.operaCheck(e)) {
            return;
        }

        var elt = e.element(),
            id, tmp;

        while (Object.isElement(elt)) {
            id = elt.readAttribute('id');

            switch (id) {
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
                this.poll();
                e.stop();
                return;

            case 'applyfilterlink':
                if (this.viewport) {
                    this.viewport.reload({ applyfilter: 1 });
                }
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

            case 'folderopts':
                DimpCore.DMenu.trigger($('folderopts_img'), true);
                return;

            case 'button_forward':
            case 'button_reply':
                this.composeMailbox(id == 'button_reply' ? 'reply' : 'forward');
                break;

            case 'button_ham':
            case 'button_spam':
                this.reportSpam(id == 'button_spam');
                e.stop();
                return;

            case 'button_deleted':
                this.deleteMsg();
                e.stop();
                return;

            case 'button_other':
                DimpCore.DMenu.trigger(e.findElement('A').next(), true);
                e.stop();
                return;

            case 'msglistHeader':
                this.sort(e);
                e.stop();
                return;

            case 'th_expand':
            case 'th_collapse':
                this._toggleHeaders(elt, true);
                break;

            case 'msgloglist_toggle':
            case 'partlist_toggle':
                tmp = (id == 'partlist_toggle') ? 'partlist' : 'msgloglist';
                $(tmp + '_col', tmp + '_exp').invoke('toggle');
                Effect.toggle(tmp, 'blind', {
                    duration: 0.2,
                    queue: {
                        position: 'end',
                        scope: tmp,
                        limit: 2
                    }
                });
                break;

            case 'msg_newwin':
            case 'msg_newwin_options':
                this.msgWindow(this.viewport.getSelection().search({ imapuid: { equal: [ this.pp.imapuid ] } , view: { equal: [ this.pp.view ] } }).get('dataob').first());
                e.stop();
                return;

            case 'msg_view_source':
                DimpCore.popupWindow(DimpCore.addURLParam(DIMP.conf.URI_VIEW, { uid: this.pp.imapuid, mailbox: this.pp.view, actionID: 'view_source', id: 0 }, true), DIMP.conf.msg_index + '|' + DIMP.conf.msg_folder);
                break;

            case 'applicationfolders':
                tmp = e.element();
                if (!tmp.hasClassName('custom')) {
                    tmp = tmp.up('LI.custom');
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

            case 'qsearch':
                if (e.element().readAttribute('id') != 'qsearch_icon') {
                    elt.addClassName('qsearchFocus');
                    if (!elt.hasClassName('qsearchActive')) {
                        this._setQsearchText(false);
                    }
                    $('qsearch_input').focus();
                }
                break;

            case 'qsearch_close':
                this.quicksearchClear();
                e.stop();
                return;

            default:
                if (elt.hasClassName('RBFolderOk')) {
                    this.cfolderaction(e);
                    e.stop();
                    return;
                } else if (elt.hasClassName('RBFolderCancel')) {
                    this._closeRedBox();
                    e.stop();
                    return;
                } else if (elt.hasClassName('basicSearchCancel')) {
                    RedBox.close();
                    e.stop();
                    return;
                } else if (elt.hasClassName('basicSearchSubmit')) {
                    elt.disable();
                    DimpCore.doAction('BasicSearch', { query: $('RB_window').down().serialize() }, null, this._basicSearchCallback.bind(this));
                    e.stop();
                    return;
                }
            }

            elt = elt.up();
        }

        parentfunc(e);
    },

    mouseoverHandler: function(e)
    {
        if (DragDrop.Drags.drag) {
            var elt = e.element();
            if (elt.hasClassName('exp')) {
                this._toggleSubFolder(elt.up(), 'tog');
            }
        }
    },

    changeHandler: function(e)
    {
        var elt = e.element();

        if (elt.readAttribute('name') == 'search_criteria' &&
            elt.descendantOf('RB_window')) {
            [ elt.next() ].invoke($F(elt) ? 'show' : 'hide');
            RedBox.setWindowPosition();
        }
    },

    /* Handle rename folder actions. */
    renameFolder: function(folder)
    {
        if (Object.isUndefined(folder)) {
            return;
        }

        folder = $(folder);
        var n = this._createFolderForm(this._folderAction.bindAsEventListener(this, folder, 'rename'), DIMP.text.rename_prompt);
        n.down('input').setValue(folder.retrieve('l'));
    },

    /* Handle insert folder actions. */
    createBaseFolder: function()
    {
        this._createFolderForm(this._folderAction.bindAsEventListener(this, '', 'create'), DIMP.text.create_prompt);
    },

    createSubFolder: function(folder)
    {
        if (!Object.isUndefined(folder)) {
            this._createFolderForm(this._folderAction.bindAsEventListener(this, $(folder), 'createsub'), DIMP.text.createsub_prompt);
        }
    },

    _createFolderForm: function(action, text)
    {
        var n = $($('folderform').down().cloneNode(true)).writeAttribute('id', 'RB_folder');
        n.down('P').insert(text);

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

    _folderAction: function(e, folder, mode)
    {
        this._closeRedBox();

        var action, params, val,
            form = e.findElement('form');
        val = $F(form.down('input'));

        if (val) {
            switch (mode) {
            case 'rename':
                folder = folder.up('LI');
                if (folder.retrieve('l') != val) {
                    action = 'RenameFolder';
                    params = { old_name: folder.retrieve('mbox'),
                               new_parent: folder.up().hasClassName('folderlist') ? '' : folder.up(1).previous().retrieve('mbox'),
                               new_name: val };
                }
                break;

            case 'create':
            case 'createsub':
                action = 'CreateFolder';
                params = { view: val };
                if (mode == 'createsub') {
                    params.parent = folder.up('LI').retrieve('mbox');
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

        this.loadingImg('viewport', false);
        this._pollCallback(r);

        r = r.response;
        if (!r.uids || r.folder != this.folder) {
            return;
        }
        r.uids = DimpCore.parseRangeString(r.uids);

        // Need to convert uid list to listing of unique viewport IDs since
        // we may be dealing with multiple mailboxes (i.e. virtual folders)
        vs = this.viewport.getSelection(this.folder);
        if (vs.getBuffer().getMetaData('search')) {
            $H(r.uids).each(function(pair) {
                pair.value.each(function(v) {
                    uids.push(v + pair.key);
                });
            });

            search = this.viewport.getSelection().search({ vp_id: { equal: uids } });
        } else {
            r.uids = r.uids[this.folder];
            r.uids.each(function(f, u) {
                uids.push(u + f);
            }.curry(this.folder));
            search = this.viewport.createSelection('uid', r.uids);
        }

        if (search.size()) {
            if (r.remove) {
                // TODO: Don't use cacheid
                this.viewport.remove(search, { cacheid: r.cacheid, noupdate: r.ViewPort });
                this._expirePPCache(uids);
            } else {
                // Need this to catch spam deletions.
                this.updateFlag(search, '\\deleted', true);
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
        if (r.response) {
            if (r.response.mbox == this.folder) {
                r.response.flags.each(function(f) {
                    this.updateFlag(this.viewport.createSelection('rownum', $A($R(1, this.viewport.getMetaData('total_rows')))), f, r.response.set);
                }, this);
            }
            this._pollCallback(r);
        }
    },

    _folderLoadCallback: function(r)
    {
        this._folderCallback(r);

        var nf = $('normalfolders'),
            nfheight = nf.getStyle('max-height');

        if (this.folder) {
            this.highlightSidebar(this.getFolderId(this.folder));
        }

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
            li = elt.up('LI');

        if (!li) {
            return;
        }

        if (elt.hasClassName('exp') || elt.hasClassName('col')) {
            this._toggleSubFolder(li, 'tog');
        } else {
            switch (li.retrieve('ftype')) {
            case 'container':
            case 'scontainer':
                e.stop();
                break;

            case 'folder':
            case 'special':
            case 'virtual':
                e.stop();
                return this.go('folder:' + li.retrieve('mbox'));
            }
        }
    },

    _toggleSubFolder: function(base, mode, noeffect)
    {
        // Make sure all subfolders are expanded.
        // The last 2 elements of ancestors() are the BODY and HTML tags -
        // don't need to parse through them.
        var subs = (mode == 'exp')
            ? base.ancestors().slice(0, -2).reverse().findAll(function(n) { return n.hasClassName('subfolders'); })
            : [ base.next('.subfolders') ];

        subs.compact().each(function(s) {
            if (mode == 'tog' ||
                (mode == 'exp' && !s.visible()) ||
                (mode == 'col' && s.visible())) {
                s.previous().down().toggleClassName('exp').toggleClassName('col');

                if (noeffect) {
                    s.toggle();
                } else {
                    Effect.toggle(s, 'blind', {
                        duration: 0.2,
                        queue: {
                            position: 'end',
                            scope: 'subfolder'
                        }
                    });
                }
            }
        }.bind(this));
    },

    // Folder actions.
    // For format of the ob object, see IMP_Dimp::_createFolderElt().
    createFolder: function(ob)
    {
        var div, f_node, ftype, li, ll, parent_e, tmp,
            cname = 'container';
            fid = this.getFolderId(ob.m),
            label = ob.l || ob.m,
            mbox = ob.m,
            submboxid = this.getSubFolderId(fid),
            submbox = $(submboxid),
            title = ob.t || ob.m;

        if (ob.v) {
            ftype = ob.co ? 'scontainer' : 'virtual';
            title = label;
        } else if (ob.co) {
            if (ob.n) {
                ftype = 'scontainer';
                title = label;
            } else {
                ftype = 'container';
            }

            /* This is a dummy container element to display child elements of
             * a mailbox displayed in the 'specialfolders' section. */
            if (ob.dummy) {
                fid += '_special';
                cname += ' specialContainer';
            }
        } else {
            cname = 'folder';
            ftype = ob.s ? 'special' : 'folder';
        }

        if (ob.un) {
            cname += ' unsubFolder';
        }

        div = new Element('SPAN', { className: 'iconSpan' });
        if (ob.i) {
            div.setStyle({ backgroundImage: 'url("' + ob.i + '")' });
        }

        li = new Element('LI', { className: cname, id: fid, title: title }).store('l', label).store('mbox', mbox).insert(div).insert(new Element('A').insert(label));

        // Now walk through the parent <ul> to find the right place to
        // insert the new folder.
        if (submbox) {
            if (submbox.insert({ before: li }).visible()) {
                // If an expanded parent mailbox was deleted, we need to toggle
                // the icon accordingly.
                div.addClassName('col');
            }
        } else {
            div.addClassName(ob.ch ? 'exp' : (ob.cl || 'folderImg'));

            if (ob.s) {
                parent_e = $('specialfolders');

                /* Create a dummy container element in 'normalfolders'
                 * section. */
                if (ob.ch) {
                    div.removeClassName('exp').addClassName(ob.cl || 'folderImg');

                    tmp = Object.clone(ob);
                    tmp.co = tmp.dummy = true;
                    tmp.s = false;
                    this.createFolder(tmp);
                }
            } else {
                parent_e = ob.pa
                    ? $(this.getSubFolderId(this.getFolderId(ob.pa))).down()
                    : $('normalfolders');
            }

            /* Virtual folders are sorted on the server. */
            if (!ob.v) {
                ll = mbox.toLowerCase();
                f_node = parent_e.childElements().find(function(node) {
                    var nodembox = node.retrieve('mbox');
                    return nodembox &&
                           (!ob.s || nodembox != 'INBOX') &&
                           (ll < nodembox.toLowerCase());
                });
            }

            if (f_node) {
                f_node.insert({ before: li });
            } else {
                parent_e.insert(li);
            }

            // Make sure the sub<mbox> ul is created if necessary.
            if (!ob.s && ob.ch) {
                li.insert({ after: new Element('LI', { className: 'subfolders', id: submboxid }).insert(new Element('UL')).hide() });
            }
        }

        li.store('ftype', ftype);

        // Make the new folder a drop target.
        if (!ob.v) {
            new Drop(li, this._folderDropConfig);
        }

        // Check for unseen messages
        if (ob.po) {
            li.store('u', '');
            this.setFolderLabel(mbox, ob.u);
        }

        switch (ftype) {
        case 'special':
            // For purposes of the contextmenu, treat special folders
            // like regular folders.
            ftype = 'folder';
            // Fall through.

        case 'container':
        case 'folder':
            new Drag(li, this._folderDragConfig);
            this._addMouseEvents({ id: fid, type: ftype });
            break;

        case 'scontainer':
        case 'virtual':
            this._addMouseEvents({ id: fid, type: (ob.v == 2) ? 'vfolder' : 'noactions' });
            break;
        }
    },

    deleteFolder: function(folder)
    {
        if (this.folder == folder) {
            this.go('folder:INBOX');
        }
        this.deleteFolderElt(this.getFolderId(folder), true);
    },

    changeFolder: function(ob)
    {
        var fdiv, oldexpand,
            fid = this.getFolderId(ob.m);

        if ($(fid + '_special')) {
            // The case of children being added to a special folder is
            // handled by createFolder().
            if (!ob.ch) {
                this.deleteFolderElt(fid + '_special', true);
            }
            return;
        }

        fdiv = $(fid).down('DIV');
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
        if (!f) {
            return;
        }

        if (sub) {
            submbox = $(this.getSubFolderId(fid));
            if (submbox) {
                submbox.remove();
            }
        }
        [ DragDrop.Drags.getDrag(fid), DragDrop.Drops.getDrop(fid) ].compact().invoke('destroy');
        this._removeMouseEvents(f);
        if (this.viewport) {
            this.viewport.deleteView(fid);
        }
        f.remove();
    },

    _sizeFolderlist: function()
    {
        var nf = $('normalfolders');
        nf.setStyle({ height: (document.viewport.getHeight() - nf.cumulativeOffset()[1]) + 'px' });
    },

    toggleSubscribed: function()
    {
        this.showunsub = !this.showunsub;
        $('ctx_folderopts_sub', 'ctx_folderopts_unsub').invoke('toggle');
        this._reloadFolders();
    },

    _reloadFolders: function()
    {
        $('foldersLoading').show();
        $('foldersSidebar').hide();

        [ $('specialfolders').childElements(), $('dropbase').nextSiblings() ].flatten().each(function(elt) {
            this.deleteFolderElt(elt.readAttribute('id'), true);
        }, this);

        DimpCore.doAction('ListFolders', { unsub: Number(this.showunsub) }, null, this._folderLoadCallback.bind(this));
    },

    subscribeFolder: function(f, sub)
    {
        var fid = this.getFolderId(f);
        DimpCore.doAction('Subscribe', { view: f, sub: Number(sub) });

        if (this.showunsub) {
            [ $(fid) ].invoke(sub ? 'removeClassName' : 'addClassName', 'unsubFolder');
        } else if (!sub) {
            this.deleteFolderElt(fid);
        }
    },

    /* Flag actions for message list. */
    _getFlagSelection: function(opts)
    {
        var vs;

        if (opts.vs) {
            vs = opts.vs;
        } else if (opts.index) {
            if (opts.mailbox) {
                vs = this.viewport.getSelection().search({ imapuid: { equal: [ opts.index ] }, view: { equal: [ opts.mailbox ] } });
                if (!vs.size() && opts.mailbox != this.folder) {
                    vs = this.viewport.getSelection(opts.mailbox).search({ imapuid: { equal: [ opts.index ] } });
                }
            } else {
                vs = this.viewport.createSelection('dataob', opts.index);
            }
        } else {
            vs = this.viewport.getSelected();
        }

        return vs;
    },

    _doMsgAction: function(type, opts, args)
    {
        var vs = this._getFlagSelection(opts);

        if (vs.size()) {
            // This needs to be synchronous Ajax if we are calling from a
            // popup window because Mozilla will not correctly call the
            // callback function if the calling window has been closed.
            DimpCore.doAction(type, this.viewport.addRequestParams(args), vs, this.bcache.get('deleteC') || this.bcache.set('deleteC', this._deleteCallback.bind(this)), { asynchronous: !(opts.index && opts.mailbox) });
            return vs;
        }

        return false;
    },

    // spam = (boolean) True for spam, false for innocent
    // opts = 'index', 'mailbox'
    reportSpam: function(spam, opts)
    {
        opts = opts || {};
        if (this._doMsgAction('ReportSpam', opts, { spam: spam })) {
            // Indicate to the user that something is happening (since spam
            // reporting may not be instantaneous).
            this.loadingImg('viewport', true);
        }
    },

    // blacklist = (boolean) True for blacklist, false for whitelist
    // opts = 'index', 'mailbox'
    blacklist: function(blacklist, opts)
    {
        opts = opts || {};
        this._doMsgAction('Blacklist', opts, { blacklist: blacklist });
    },

    // opts = 'index', 'mailbox'
    deleteMsg: function(opts)
    {
        opts = opts || {};
        var vs = this._getFlagSelection(opts);

        // Make sure that any given row is not deleted more than once. Need to
        // explicitly mark here because message may already be flagged deleted
        // when we load page (i.e. switching to using trash folder).
        vs = vs.search({ isdel: { not: [ true ] } });
        if (!vs.size()) {
            return;
        }
        vs.set({ isdel: true });

        opts.vs = vs;

        this._doMsgAction('DeleteMessage', opts, {});
        this.updateFlag(vs, '\\deleted', true);
    },

    // flag = (string) IMAP flag name
    // set = (boolean) True to set flag
    // opts = (Object) 'index', 'mailbox', 'noserver'
    flag: function(flag, set, opts)
    {
        opts = opts || {};
        var flags = [ (set ? '' : '-') + flag ],
            vs = this._getFlagSelection(opts);

        if (!vs.size()) {
            return;
        }

        switch (flag) {
        case '\\answered':
            if (set) {
                this.updateFlag(vs, '\\flagged', false);
                flags.push('-\\flagged');
            }
            break;

        case '\\deleted':
            vs.set({ isdel: false });
            break;

        case '\\seen':
            vs.get('dataob').each(function(s) {
                this.updateSeenUID(s, set);
            }, this);
            break;
        }

        this.updateFlag(vs, flag, set);
        if (!opts.noserver) {
            DimpCore.doAction('FlagMessage', { flags: flags.toJSON(), view: this.folder }, vs);
        }
    },

    // type = (string) 'seen' or 'unseen'
    // mbox = (string) The mailbox to flag
    flagAll: function(type, set, mbox)
    {
        DimpCore.doAction('FlagAll', { flags: [ type ].toJSON(), set: Number(set), view: mbox }, null, this.bcache.get('flagAC') || this.bcache.set('flagAC', this._flagAllCallback.bind(this)));
    },

    hasFlag: function(f, r)
    {
        return r.flag && this.convertFlag(f, r.flag.include(f));
    },

    convertFlag: function(f, set)
    {
        /* For some flags, we need to do an inverse match (e.g. knowing a
         * message is SEEN is not as important as knowing the message lacks
         * the SEEN FLAG). This function will determine if, for a given flag,
         * the inverse action should be taken on it. */
        return DIMP.conf.flags[f].n ? !set : set;
    },

    updateFlag: function(vs, flag, add)
    {
        add = this.convertFlag(flag, add);

        vs.get('dataob').each(function(ob) {
            this._updateFlag(ob, flag, add);

            /* If this is a search mailbox, also need to update flag in base
             * view, if it is in the buffer. */
            if (this.isSearch()) {
                var tmp = this.viewport.getSelection(ob.view).search({ imapuid: { equal: [ ob.imapuid ] }, view: { equal: [ ob.view ] } });
                if (tmp.size()) {
                    this._updateFlag(tmp.get('dataob').first(), flag, add);
                }
            }
        }, this);
    },

    _updateFlag: function(ob, flag, add)
    {
        ob.flag = ob.flag
            ? ob.flag.without(flag)
            : [];

        if (add) {
            ob.flag.push(flag);
        }

        this.viewport.updateRow(ob);
    },

    /* Miscellaneous folder actions. */
    purgeDeleted: function()
    {
        DimpCore.doAction('PurgeDeleted', this.viewport.addRequestParams({}), null, this.bcache.get('deleteC') || this.bcache.set('deleteC', this._deleteCallback.bind(this)));
    },

    modifyPoll: function(folder, add)
    {
        DimpCore.doAction('ModifyPoll', { view: folder, add: Number(add) }, null, this.bcache.get('modifyPFC') || this.bcache.set('modifyPFC', this._modifyPollCallback.bind(this)));
    },

    _modifyPollCallback: function(r)
    {
        r = r.response;
        var f = r.folder, fid, p = { response: { poll: {} } };
        fid = $(this.getFolderId(f));

        if (r.add) {
            p.response.poll[f] = r.poll.u;
            fid.store('u', 0);
        } else {
            p.response.poll[f] = 0;
        }

        this._pollCallback(p);

        if (!r.add) {
            fid.store('u', null);
        }
    },

    loadingImg: function(id, show)
    {
        var c;

        if (show) {
            $(id + 'Loading').clonePosition(id == 'viewport' ? 'msgList' : 'splitBar', { setLeft: false, setTop: true, setHeight: false, setWidth: false }).show();
            c = 'progress';
        } else {
            $(id + 'Loading').fade({ duration: 0.2 });
            c = 'default';
        }
        $(document.body).setStyle({ cursor: c });
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
        new Ajax.Request(DimpCore.addURLParam(DIMP.conf.URI_PREFS), { parameters: { pref: pref, value: value } });
    },

    /* Onload function. */
    onDomLoad: function()
    {
        DimpCore.init();

        var DM = DimpCore.DMenu;

        /* Register global handlers now. */
        document.observe('keydown', this.keydownHandler.bindAsEventListener(this));
        document.observe('change', this.changeHandler.bindAsEventListener(this));

        /* Limit to folders sidebar only. */
        $('foldersSidebar').observe('mouseover', this.mouseoverHandler.bindAsEventListener(this));

        /* Limit to msgList only. */
        $('msgList').observe('dblclick', this.dblclickHandler.bindAsEventListener(this));

        $('dimpLoading').hide();
        $('dimpPage').show();

        /* Create the folder list. Any pending notifications will be caught
         * via the return from this call. */
        DimpCore.doAction('ListFolders', {}, null, this._folderLoadCallback.bind(this));

        /* Init quicksearch. These needs to occur before loading the message
         * list since it may be disabled if we are in a search mailbox. */
        if ($('qsearch')) {
            $('qsearch_input').observe('blur', this._quicksearchOnBlur.bind(this));
            this._addMouseEvents({ id: 'qsearch_icon', left: true, offset: 'qsearch', type: 'qsearchopts' });
        }

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
                this.loadMailbox('INBOX', { background: true });
            }
        }

        this._setQsearchText(true);

        /* Add popdown menus. Check for disabled compose at the same time. */
        this._addMouseEvents({ id: 'button_other', type: 'otheractions' }, $('button_other'));
        this._addMouseEvents({ id: 'folderopts', type: 'folderopts' }, $('folderopts').down(1));

        DM.addSubMenu('ctx_message_reply', 'ctx_reply');
        [ 'ctx_message_', 'oa_', 'ctx_draft_' ].each(function(i) {
            if ($(i + 'setflag')) {
                DM.addSubMenu(i + 'setflag', 'ctx_flag');
                DM.addSubMenu(i + 'unsetflag', 'ctx_flag');
            }
        });

        if (DIMP.conf.disable_compose) {
            $('button_reply', 'button_forward').compact().invoke('up', 'SPAN').concat($('button_compose', 'composelink', 'ctx_contacts_new')).compact().invoke('remove');
        } else {
            this._addMouseEvents({ id: 'button_reply', type: 'reply' }, $('button_reply'));
            DM.disable('button_reply_img', true, true);
        }

        new Drop('dropbase', this._folderDropConfig);

        if (DIMP.conf.toggle_pref) {
            this._toggleHeaders($('th_expand'));
        }

        this._resizeIE6();

        /* Remove unavailable menu items. */
        if (!$('GrowlerLog')) {
            $('alertsloglink').remove();
        }

        /* Check for new mail. */
        this.setPoll();

        if (DimpCore.is_ie6) {
            /* Disable text selection in preview pane for IE 6. */
            document.observe('selectstart', Event.stop);
            Event.observe(window, 'resize', this._resizeIE6.bind(this));

            /* Since IE 6 doesn't support hover over non-links, use javascript
             * events to replicate mouseover CSS behavior. */
            $('dimpbarActions', 'serviceActions', 'applicationfolders', 'specialfolders', 'normalfolders').compact().invoke('select', 'LI').flatten().compact().each(function(e) {
                e.observe('mouseover', e.addClassName.curry('over')).observe('mouseout', e.removeClassName.curry('over'));
            });

            /* These are links, but they have no href attribute. Hovering
             * requires something in href on IE6. */
            $$('.context A').each(function(e) {
                e.writeAttribute('href', '');
            });
        }
    },

    // IE 6 width fixes (See Bug #6793)
    _resizeIE6: function()
    {
        if (DimpCore.is_ie6) {
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
        if (DimpCore.is_ie6) {
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
        if (DimpCore.DMenu.operaCheck(e)) {
            if (!DimpBase.isSelected('domid', id)) {
                DimpBase.msgSelect(id, { right: true });
            }
        } else if (!args.right && (e.ctrlKey || e.metaKey)) {
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

        $('msgBodyCover').clonePosition('msgBody').show();
    },
    onEnd: function(d, e) {
        if (d.selectIfNoDrag && !d.wasDragged) {
            DimpBase.msgSelect(d.element.id, { right: e.isRightClick() });
        }

        $('msgBodyCover').hide();
    }
};

DimpBase._folderDragConfig = {
    ghosting: true,
    offset: { x: 15, y: 0 },
    scroll: 'normalfolders',
    threshold: 5,
    onStart: function(d, e) {
        if (DimpCore.DMenu.operaCheck(e)) {
            d.opera = true;
        } else {
            d.opera = false;
            $('msgBodyCover').clonePosition('msgBody').show();
        }
    },
    onDrag: function(d, e) {
        if (!d.opera && !d.wasDragged) {
            $('folderopts').hide();
            $('dropbase').show();
            d.ghost.removeClassName('on');
        }
    },
    onEnd: function(d, e) {
        if (!d.opera) {
            if (d.wasDragged) {
                $('folderopts').show();
                $('dropbase').hide();
            }
            $('msgBodyCover').hide();
        }
    }
};

DimpBase._folderDropConfig = {
    caption: function(drop, drag, e) {
        var m,
            d = drag.retrieve('l'),
            ftype = drop.retrieve('ftype'),
            l = drop.retrieve('l');

        if (drop == $('dropbase')) {
            return DIMP.text.moveto.replace(/%s/, d).replace(/%s/, DIMP.text.baselevel);
        } else {
            switch (e.type) {
            case 'mousemove':
                m = (e.ctrlKey) ? DIMP.text.copyto : DIMP.text.moveto;
                break;

            case 'keydown':
                /* Can't use ctrlKey here since different browsers handle
                 * the ctrlKey in different ways when it comes to firing
                 * keybaord events. */
                m = (e.keyCode == 17) ? DIMP.text.copyto : DIMP.text.moveto;
                break;

            case 'keyup':
                if (e.keyCode == 17) {
                    m = DIMP.text.moveto;
                } else {
                    m = (e.ctrlKey) ? DIMP.text.copyto : DIMP.text.moveto;
                }
                break;
            }
            if (drag.hasClassName('folder')) {
                return (ftype != 'special' && !DimpBase.isSubfolder(drag, drop)) ? m.replace(/%s/, d).replace(/%s/, l) : '';
            } else {
                return ftype != 'container' ? m.replace(/%s/, DimpBase.dragCaption()).replace(/%s/, l) : '';
            }
        }
    },
    keypress: true,
    onDrop: DimpBase._folderDropHandler.bind(DimpBase)
};

/* Need to register a callback function for doAction to catch viewport
 * information returned from the server. */
DimpCore.onDoActionComplete = function(r) {
    if (DimpBase.viewport) {
        DimpBase.viewport.parseJSONResponse(r);
    }
};

/* Click handler. */
DimpCore.clickHandler = DimpCore.clickHandler.wrap(DimpBase.clickHandler.bind(DimpBase));

/* ContextSensitive functions. */
DimpCore.contextOnClick = DimpCore.contextOnClick.wrap(DimpBase.contextOnClick.bind(DimpBase));
DimpCore.contextOnShow = DimpCore.contextOnShow.wrap(DimpBase.contextOnShow.bind(DimpBase));

/* Initialize onload handler. */
document.observe('dom:loaded', DimpBase.onDomLoad.bind(DimpBase));
