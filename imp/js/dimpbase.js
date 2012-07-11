/**
 * dimpbase.js - Javascript used in the base DIMP page.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

var DimpBase = {
    // Vars used and defaulting to null/false:
    //   expandmbox, pollPE, pp, qsearch_ghost, resize, rownum, search,
    //   searchbar_time, searchbar_time_mins, splitbar, sort_init, template,
    //   uid, view, viewaction, viewport, viewswitch
    // msglist_template_horiz and msglist_template_vert set via
    //   js/mailbox-dimp.js

    INBOX: 'SU5CT1g', // 'INBOX' base64url encoded
    lastrow: -1,
    mboxes: {},
    pivotrow: -1,
    ppcache: {},
    ppfifo: [],
    showunsub: 0,
    smboxes: {},
    tcache: {},

    // Preview pane cache size is 20 entries. Given that a reasonable guess
    // of an average e-mail size is 10 KB (including headers), also make
    // an estimate that the JSON data size will be approx. 10 KB. 200 KB
    // should be a fairly safe caching value for any recent browser.
    ppcachesize: 20,

    // List of internal preferences
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
            return this._getPref('preview');

        case 'splitbar_side':
            return DimpCore.conf.sidebar_width;
        }
    },

    // Message selection functions

    // id = (string) DOM ID
    // opts = (Object) Boolean options [ctrl, right, shift]
    msgSelect: function(id, opts)
    {
        var bounds,
            row = this.viewport.createSelection('domid', id),
            sel = this.isSelected('domid', id),
            selcount = this.selectedCount();

        this.lastrow = row;

        // Some browsers need to stop the mousedown event before it propogates
        // down to the browser level in order to prevent text selection on
        // drag/drop actions.  Clicking on a message should always lose focus
        // from the search input, because the user may immediately start
        // keyboard navigation after that. Thus, we need to ensure that a
        // message click loses focus on the search input.
        if ($('horde-search')) {
            $('horde-search-input').blur();
        }

        this.resetSelectAll();

        if (opts.shift) {
            if (selcount) {
                if (!sel || selcount != 1) {
                    bounds = [ row.get('rownum').first(), this.pivotrow.get('rownum').first() ];
                    this.viewport.select($A($R(bounds.min(), bounds.max())));
                }
                return;
            }
        } else if (opts.ctrl) {
            this.pivotrow = row;
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
        var tmp = $('msglistHeaderContainer').down('DIV.msCheckAll');
        if (tmp.hasClassName('msCheckOn')) {
            this.resetSelected();
        } else {
            this.viewport.select($A($R(1, this.viewport.getMetaData('total_rows'))), { right: true });
            DimpCore.toggleCheck(tmp, true);
            $('previewInfo').highlight({ queue: 'end', keepBackgroundImage: true, duration: 2.0 })
        }
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
        this.resetSelectAll();
        this.toggleButtons();
        this.clearPreviewPane();
    },

    resetSelectAll: function()
    {
        DimpCore.toggleCheck($('msglistHeaderContainer').down('DIV.msCheckAll'), false);
    },

    // num = (integer) See absolute.
    // absolute = (boolean) Is num an absolute row number - from 1 ->
    //            page_size (true) - or a relative change from the current
    //            selected value (false)
    //            If no current selected value, the first message in the
    //            current viewport is selected.
    // bottom = (boolean) Make selected appear at bottom?
    moveSelected: function(num, absolute, bottom)
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
                curr = curr_row.VP_rownum + num;
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
            if (!curr_row || row_data.uid != curr_row.uid) {
                this.viewport.scrollTo(row_data.VP_rownum, { bottom: bottom });
                this.viewport.select(row, { delay: 0.3 });
            }
        } else if (curr) {
            this.rownum = curr;
            this.viewport.requestContentRefresh(curr - 1);
        }
    },
    // End message selection functions

    // type = (string) app, compose, mbox, menu, msg, portal, prefs, search
    //        DEFAULT: View INBOX
    // data = (mixed)
    //     'app' - (object) [app, data]
    //     'mbox' - (string) Mailbox to display
    //     'menu' - (string) Menu item to display
    //     'msg' - (string) IMAP sequence string
    //     'prefs' - (object) Extra parameters to add to prefs URL
    //     'search' - (object)
    //         'edit_query' = If 1, mailbox will be edited
    //         'mailbox' = mailboxes to search
    //         'subfolder' = do subfolder search
    //         If not set, loads search screen with current mailbox as
    //         default search mailbox
    go: function(type, data)
    {
        var msg;

        if (!type) {
            type = 'mbox';
        }

        if (type == 'compose') {
            return;
        }

        if (type == 'msg') {
            type = 'mbox';
            msg = DimpCore.parseUIDString(data);
            data = Object.keys(msg).first();
            this.uid = msg[data].first();
            // Fall through to the 'mbox' check below.
        }

        if (type == 'mbox') {
            if (Object.isUndefined(data) || data.empty()) {
                data = Object.isUndefined(this.view)
                    ? this.INBOX
                    : this.view;
            }

            if (this.view != data || !$('dimpmain_folder').visible()) {
                this.highlightSidebar(data);
                if (!$('dimpmain_folder').visible()) {
                    $('dimpmain_iframe').hide();
                    $('dimpmain_folder').show();
                }
            }

            this.loadMailbox(data);
            return;
        }

        $('dimpmain_folder').hide();
        $('dimpmain_iframe').update(DimpCore.text.loading).show();

        switch (type) {
        case 'search':
            if (!data) {
                data = { mailbox: this.view };
            }
            this.highlightSidebar();
            this.setTitle(DimpCore.text.search);
            this.iframeContent(type, HordeCore.addURLParam(DimpCore.conf.URI_SEARCH, data));
            break;
        }
    },

    setHash: function(type, data)
    {
        var h;

        if (type) {
            h = type;
            if (data) {
                h += ':' + data;
            }
        }

        window.location.hash = h;
    },

    setMsgHash: function()
    {
        var msg,
            vs = this.viewport.getSelection(),
            view = vs.getBuffer().getView();

        if (this.isQSearch()) {
            // Quicksearch is not saved after page reload.
            this.setHash('mbox', this.search.mbox);
        } else if (vs.size()) {
            if (this.isSearch()) {
                msg = {};
                msg[this.view] = vs.get('uid');
            } else {
                msg = DimpCore.selectionToRange(vs);
            }
            this.setHash('msg', DimpCore.toUIDString(msg, { raw: this.isSearch() }));
        } else {
            this.setHash('mbox', view);
        }
    },

    setTitle: function(title, unread)
    {
        document.title = (unread ? '(' + unread + ') ' : '') + DimpCore.conf.name + ' :: ' + title;
    },

    // id: (string) Either the ID of a sidebar element, or the name of a
    //     mailbox
    highlightSidebar: function(id)
    {
        // Folder bar may not be fully loaded yet.
        if ($('foldersLoading').visible()) {
            this.highlightSidebar.bind(this, id).defer();
            return;
        }

        var curr = $('foldersSidebar').down('.horde-subnavi-active'),
            elt = $(id);

        if (curr === elt) {
            return;
        }

        if (curr) {
            curr.removeClassName('horde-subnavi-active');
            curr.addClassName('horde-subnavi');
        }

        if (!elt) {
            elt = this.getMboxElt(id);
        }

        if (elt) {
            elt.addClassName('horde-subnavi-active');
            this._toggleSubFolder(elt, 'exp');
        }
    },

    setSidebarWidth: function()
    {
        var tmp = $('horde-sidebar');

        tmp.setStyle({
            width: this._getPref('splitbar_side') + 'px'
        });
        this.splitbar.setStyle({
            left: tmp.clientWidth + 'px'
        });
        $('horde-body').setStyle({
            left: (tmp.clientWidth) + 'px'
        });
    },

    iframeContent: function(name, loc)
    {
        var container = $('dimpmain_iframe'), iframe;
        if (!container) {
            HordeCore.notify('Bad portal!', 'horde.error');
            return;
        }

        iframe = new Element('IFRAME', { id: 'iframe' + (name === null ? loc : name), className: 'iframe', frameBorder: 0, src: loc }).setStyle({ height: document.viewport.getHeight() + 'px' });
        container.insert(iframe);
    },

    // r = ViewPort row data
    msgWindow: function(r)
    {
        HordeCore.popupWindow(DimpCore.conf.URI_MESSAGE, {
            mailbox: r.mbox,
            uid: r.uid
        }, {
            name: 'msgview' + r.mbox + r.uid
        });
    },

    composeMailbox: function(type)
    {
        var sel = this.viewport.getSelected();
        if (sel.size()) {
            DimpCore.compose(type, { uids: sel });
        }
    },

    loadMailbox: function(f)
    {
        var need_delete,
            opts = {};

        if (!this.viewport) {
            this._createViewPort();
        }

        this.resetSelected();

        if (!this.isSearch(f)) {
            this.quicksearchClear(true);
        }

        if (this.view != f) {
            $('mailboxName').update(DimpCore.text.loading);
            this.viewswitch = true;

            /* Don't cache results of search mailboxes - since we will need to
             * grab new copy if we ever return to it. */
            if (this.isSearch()) {
                need_delete = this.view;
            }

            this.view = f;
        }

        if (this.uid) {
            opts.search = { uid: this.uid.uid };
        }

        this.viewport.loadView(f, opts);

        if (need_delete) {
            this.viewport.deleteView(need_delete);
        }
    },

    _createViewPort: function()
    {
        var container = $('msgSplitPane');

        this.template = {
            horiz: new Template(this.msglist_template_horiz),
            vert: new Template(this.msglist_template_vert)
        };

        this.viewport = new ViewPort({
            // Mandatory config
            ajax: DimpCore.doAction.bind(DimpCore, 'viewPort'),
            container: container,
            onContent: function(r, mode) {
                var bg, re, u,
                    thread = $H(this.viewport.getMetaData('thread')),
                    tsort = this.isThreadSort();

                r.subjectdata = r.status = '';
                r.subjecttitle = r.subject;

                /* HTML escape the date, from, and size entries. */
                [ 'date', 'from', 'size' ].each(function(i) {
                    if (r[i]) {
                        r[i] = r[i].escapeHTML();
                    }
                });

                // Add thread graphics
                if (tsort && mode != 'vert') {
                    u = thread.get(r.uid);
                    if (u) {
                        $R(0, u.length, true).each(function(i) {
                            var c = u.charAt(i);
                            if (!this.tcache[c]) {
                                this.tcache[c] = '<span class="treeImg treeImg' + c + '"></span>';
                            }
                            r.subjectdata += this.tcache[c];
                        }, this);
                    }
                }

                /* Generate the status flags. */
                if (!DimpCore.conf.pop3 && r.flag) {
                    r.flag.each(function(a) {
                        var ptr = DimpCore.conf.flags[a];
                        if (ptr.u) {
                            if (!ptr.elt) {
                                /* Until text-overflow is supported on all
                                 * browsers, need to truncate label text
                                 * ourselves. */
                                ptr.elt = '<span class="' + ptr.c + '" title="' + ptr.l + '" style="background:' + ptr.b + ';color:' + ptr.f + '">' + ptr.l.truncate(10) + '</span>';
                            }
                            r.subjectdata += ptr.elt;
                        } else {
                            if (ptr.c) {
                                if (!ptr.elt) {
                                    ptr.elt = '<div class="iconImg msgflags ' + ptr.c + '" title="' + ptr.l + '"></div>';
                                }
                                r.status += ptr.elt;

                                r.VP_bg.push(ptr.c);
                            }

                            if (ptr.b) {
                                bg = ptr.b;
                            }
                        }
                    });
                }

                // Set bg
                if (bg) {
                    r.style = 'background:' + bg;
                }

                // Check for search strings
                if (this.isQSearch()) {
                    re = new RegExp("(" + $F('horde-search-input') + ")", "i");
                    [ 'from', 'subject' ].each(function(h) {
                        if (r[h] !== null) {
                            r[h] = r[h].gsub(re, '<span class="qsearchMatch">#{1}</span>');
                        }
                    });
                }

                // If these fields are null, invalid string was scrubbed by
                // JSON encode.
                if (r.from === null) {
                    r.from = '[' + DimpCore.text.badaddr + ']';
                }
                if (r.subject === null) {
                    r.subject = r.subjecttitle = '[' + DimpCore.text.badsubject + ']';
                }

                r.VP_bg.push('vpRow');

                switch (mode) {
                case 'vert':
                    $('msglistHeaderHoriz').hide();
                    $('msglistHeaderVert').show();
                    r.VP_bg.unshift('vpRowVert');
                    r.className = r.VP_bg.join(' ');
                    return this.template.vert.evaluate(r);

                default:
                    $('msglistHeaderVert').hide();
                    $('msglistHeaderHoriz').show();
                    r.VP_bg.unshift('vpRowHoriz');
                    r.className = r.VP_bg.join(' ');
                    return this.template.horiz.evaluate(r);
                }
            }.bind(this),

            // Optional config
            empty_msg: this.emptyMsg.bind(this),
            list_class: 'msglist',
            list_header: $('msglistHeaderContainer').remove(),
            page_size: this._getPref('splitbar_horiz'),
            pane_data: 'previewPane',
            pane_mode: this._getPref('preview'),
            pane_width: this._getPref('splitbar_vert'),
            split_bar_class: { horiz: 'horde-splitbar-horiz', vert: 'horde-splitbar-vert' },
            split_bar_handle_class: { horiz: 'horde-splitbar-horiz-handle', vert: 'horde-splitbar-vert-handle' },

            // Callbacks
            onAjaxRequest: function(params) {
                var tmp = params.get('cache'),
                    view = params.get('view');

                if (this.viewswitch &&
                    (this.isQSearch(view) || this.isFSearch(view))) {
                    params.update({
                        qsearchfield: this._getPref('qsearch_field'),
                        qsearchmbox: this.search.mbox
                    });
                    if (this.search.filter) {
                        params.set('qsearchfilter', this.search.filter);
                    } else if (this.search.flag) {
                        params.update({
                            qsearchflag: this.search.flag,
                            qsearchflagnot: Number(this.search.not)
                        });
                    } else {
                        params.set('qsearch', $F('horde-search-input'));
                    }
                }

                if (tmp) {
                    params.set('cache', DimpCore.toUIDString(DimpCore.selectionToRange(this.viewport.createSelection('uid', tmp.evalJSON(tmp), view))));
                }

                params = $H({
                    viewport: Object.toJSON(params),
                    view: view
                });
                HordeCore.addRequestParams(params);

                return params;
            }.bind(this),
            onContentOffset: function(offset) {
                if (this.uid) {
                    this.rownum = this.viewport.createSelectionBuffer().search({ VP_id: { equal: [ this.uid ] } }).get('rownum').first();
                    delete this.uid;
                }

                if (this.rownum) {
                    this.viewport.scrollTo(this.rownum, {
                        noupdate: true,
                        top: true
                    });
                    offset = this.viewport.currentOffset();
                }

                return offset;
            }.bind(this),
            onSlide: this.setMessageListTitle.bind(this)
        });

        /* Custom ViewPort events. */
        container.observe('ViewPort:add', function(e) {
            DimpCore.addContextMenu({
                elt: e.memo,
                type: 'message'
            });
            new Drag(e.memo, this._msgDragConfig);
        }.bindAsEventListener(this));

        container.observe('ViewPort:clear', function(e) {
            this._removeMouseEvents(e.memo);
        }.bindAsEventListener(this));

        container.observe('ViewPort:contentComplete', function() {
            var flags, ssc, tmp,
                innocent = 'show',
                spam = 'show';

            this.setMessageListTitle();
            this.setMsgHash();
            this.loadingImg('viewport', false);

            if (this.isSearch()) {
                tmp = this.viewport.getMetaData('slabel');
                if (this.viewport.getMetaData('vfolder')) {
                    $('search_close').hide();
                    if (tmp) {
                        tmp = DimpCore.text.vfolder.sub('%s', tmp);
                    }
                } else {
                    $('search_close').show();
                }

                if (tmp) {
                    tmp = tmp.stripTags();
                    $('search_label').writeAttribute({ title: tmp.escapeHTML() });
                    if (tmp.length > 250) {
                        tmp = tmp.truncate(250);
                    }
                    $('search_label').update(tmp.escapeHTML());
                }
                [ $('search_edit') ].invoke(this.search || this.viewport.getMetaData('noedit') ? 'hide' : 'show');
            } else {
                this.setMboxLabel(this.view);
            }

            if (this.rownum) {
                this.viewport.select([ this.rownum ]);
                delete this.rownum;
            }

            this.updateTitle();

            if (this.viewswitch) {
                this.viewswitch = false;

                if (this.isSearch()) {
                    $('filter').hide();
                    if (!this.search || !this.search.qsearch) {
                        $('horde-search').hide();
                    }
                    this.showSearchbar(true);
                } else {
                    $('filter').show();
                    this.showSearchbar(false);
                }

                tmp = $('applyfilterlink');
                if (tmp) {
                    if (this.isSearch() ||
                        (!DimpCore.conf.filter_any && this.view != this.INBOX)) {
                        tmp.hide();
                    } else {
                        tmp.show();
                    }

                    this._sizeFolderlist();
                }

                if (this.viewport.getMetaData('drafts')) {
                    $('button_resume').up().up().show();
                    $('button_template', 'button_reply', 'button_forward', 'button_spam', 'button_innocent').compact().invoke('up').invoke('up').invoke('hide');
                } else if (this.viewport.getMetaData('templates')) {
                    $('button_template').up().up().show();
                    $('button_resume', 'button_reply', 'button_forward', 'button_spam', 'button_innocent').compact().invoke('up').invoke('up').invoke('hide');
                } else {
                    $('button_resume', 'button_template').compact().invoke('up').invoke('up').invoke('hide');
                    $('button_reply', 'button_forward').compact().invoke('up').invoke('up').invoke('show');

                    if (this.viewport.getMetaData('spam')) {
                        if (!DimpCore.conf.spam_spammbox) {
                            spam = 'hide';
                        }
                    } else if (DimpCore.conf.innocent_spammbox) {
                        innocent = 'hide';
                    }

                    if (tmp = $('button_innocent')) {
                        [ tmp.up().up() ].invoke(innocent);
                    }
                    if (tmp = $('button_spam')) {
                        [ tmp.up().up() ].invoke(spam);
                    }
                }

                /* Read-only changes. */
                [ $('mailboxName').next('SPAN.readonlyImg') ].invoke(this.viewport.getMetaData('readonly') ? 'show' : 'hide');

                /* ACL changes. */
                if (tmp = $('button_delete')) {
                    [ tmp.up().up() ].invoke(this.viewport.getMetaData('nodelete') ? 'hide' : 'show');
                }
            } else if (this.filtertoggle && this.isThreadSort()) {
                ssc = DimpCore.conf.sort.get('date').v;
            }

            this.setSortColumns(ssc);
        }.bindAsEventListener(this));

        container.observe('ViewPort:deselect', function(e) {
            var sel = this.viewport.getSelected(),
                count = sel.size();
            if (!count) {
                this.lastrow = this.pivotrow = -1;
            }

            this.toggleButtons();
            if (e.memo.opts.right || !count) {
                this.clearPreviewPane();
            } else if ((count == 1) && this._getPref('preview')) {
                this.loadPreview(sel.get('dataob').first());
            }

            this.resetSelectAll();
            this.setMsgHash();
        }.bindAsEventListener(this));

        container.observe('ViewPort:endRangeFetch', function(e) {
            if (this.view == e.memo) {
                this.loadingImg('viewport', false);
            }
        }.bindAsEventListener(this));

        container.observe('ViewPort:fetch', function(e) {
            if (!this.isSearch()) {
                this.showSearchbar(false);
            }
            this.loadingImg('viewport', true);
        }.bindAsEventListener(this));

        container.observe('ViewPort:remove', function(e) {
            var v = e.memo.getBuffer().getView();

            if (this.view == v) {
                this.loadingImg('viewport', false);
            }

            e.memo.get('dataob').each(function(d) {
                this._expirePPCache([ this._getPPId(d.uid, d.mbox) ]);
                if (this.isSearch(v)) {
                    this.viewport.remove(this.viewport.createSelectionBuffer(d.mbox).search({ uid: { equal: [ d.uid ] }, mbox: { equal: [ d.mbox ] } }));
                }
            }, this);
        }.bindAsEventListener(this));

        container.observe('ViewPort:select', function(e) {
            var d = e.memo.vs.get('rownum');
            if (d.size() == 1) {
                this.lastrow = this.pivotrow = e.memo.vs;
            }

            this.setMsgHash();

            this.toggleButtons();

            if (this._getPref('preview')) {
                if (e.memo.opts.right) {
                    this.clearPreviewPane();
                } else if (e.memo.opts.delay) {
                    this.initPreviewPane.bind(this).delay(e.memo.opts.delay);
                } else {
                    this.initPreviewPane();
                }
            }
        }.bindAsEventListener(this));

        container.observe('ViewPort:splitBarChange', function(e) {
            switch (e.memo) {
            case 'horiz':
                this._setPref('splitbar_horiz', this.viewport.getPageSize());
                break;

            case 'vert':
                this._setPref('splitbar_vert', this.viewport.getVertWidth());
                break;
            }
        }.bindAsEventListener(this));

        container.observe('ViewPort:wait', function() {
            if ($('dimpmain_folder').visible()) {
                HordeCore.notify(DimpCore.text.listmsg_wait, 'horde.warning');
            }
        });
    },

    addViewportParams: function(params)
    {
        var tmp = this.viewport.addRequestParams();
        if (params) {
            tmp.update(params);
        }
        return tmp;
    },

    emptyMsg: function()
    {
        return (this.isQSearch() || this.isFSearch())
            ? DimpCore.text.vp_empty_search
            : DimpCore.text.vp_empty;
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

    contextOnClick: function(e)
    {
        var tmp,
            elt = e.memo.elt,
            id = elt.readAttribute('id'),
            menu = e.memo.trigger;

        switch (id) {
        case 'ctx_container_create':
        case 'ctx_mbox_create':
            tmp = e.findElement('DIV.horde-subnavi');
            DimpCore.doAction('createMailboxPrepare', {
                mbox: tmp.retrieve('mbox')
            },{
                callback: this._mailboxPromptCallback.bind(this, 'create', tmp)
            });
            break;

        case 'ctx_container_rename':
        case 'ctx_mbox_rename':
            tmp = e.findElement('DIV.horde-subnavi');
            DimpCore.doAction('deleteMailboxPrepare', {
                mbox: tmp.retrieve('mbox'),
                type: 'rename'
            },{
                callback: this._mailboxPromptCallback.bind(this, 'rename', tmp)
            });
            break;

        case 'ctx_mbox_empty':
            tmp = e.findElement('DIV.horde-subnavi');
            DimpCore.doAction('emptyMailboxPrepare', {
                mbox: tmp.retrieve('mbox')
            },{
                callback: this._mailboxPromptCallback.bind(this, 'empty', tmp)
            });
            break;

        case 'ctx_container_delete':
            this._mailboxPromptCallback('delete', e.findElement('DIV.horde-subnavi'));
            break;

        case 'ctx_mbox_delete':
        case 'ctx_vfolder_delete':
            tmp = e.findElement('DIV.horde-subnavi');
            DimpCore.doAction('deleteMailboxPrepare', {
                mbox: tmp.retrieve('mbox'),
                type: 'delete'
            }, {
                callback: this._mailboxPromptCallback.bind(this, 'delete', tmp)
            });
            break;

        case 'ctx_mbox_exportopts_mbox':
        case 'ctx_mbox_exportopts_zip':
            tmp = e.findElement('DIV.horde-subnavi');

            this.viewaction = function(e) {
                HordeCore.download('', {
                    actionID: 'download_mbox',
                    mbox_list: Object.toJSON([ tmp.retrieve('mbox') ]),
                    zip: Number(id == 'ctx_mbox_exportopts_zip')
                });
            };

            HordeDialog.display({
                form_id: 'dimpbase_confirm',
                noinput: true,
                text: DimpCore.text.download_mbox
            });
            break;

        case 'ctx_mbox_import':
            tmp = e.findElement('DIV.horde-subnavi').retrieve('mbox');

            HordeDialog.display({
                form: new Element('DIV').insert(
                          new Element('INPUT', { name: 'import_file', type: 'file' })
                      ).insert(
                          new Element('INPUT', { name: 'import_mbox', value: tmp }).hide()
                      ),
                form_id: 'mbox_import',
                form_opts: {
                    action: HordeCore.conf.URI_AJAX + 'importMailbox',
                    className: 'RB_Form',
                    enctype: 'multipart/form-data',
                    method: 'post'
                },
                text: DimpCore.text.import_mbox
            });
            break;

        case 'ctx_mbox_flag_seen':
        case 'ctx_mbox_flag_unseen':
            DimpCore.doAction('flagAll', {
                add: Number(id == 'ctx_mbox_flag_seen'),
                flags: Object.toJSON([ DimpCore.conf.FLAG_SEEN ]),
                mbox: e.findElement('DIV.horde-subnavi').retrieve('mbox')
            });
            break;

        case 'ctx_mbox_poll':
        case 'ctx_mbox_nopoll':
            this.modifyPoll(e.findElement('DIV.horde-subnavi').retrieve('mbox'), id == 'ctx_mbox_poll');
            break;

        case 'ctx_mbox_sub':
        case 'ctx_mbox_unsub':
            this.subscribeMbox(e.findElement('DIV.horde-subnavi').retrieve('mbox'), id == 'ctx_mbox_sub');
            break;

        case 'ctx_mbox_acl':
            HordeCore.redirect(HordeCore.addURLParam(
                DimpCore.conf.URI_PREFS_IMP,
                {
                    group: 'acl',
                    mbox: e.findElement('DIV.horde-subnavi').retrieve('mbox')
                }
            ));
            break;

        case 'ctx_folderopts_new':
            this._createMboxForm('', 'create', DimpCore.text.create_prompt);
            break;

        case 'ctx_folderopts_sub':
        case 'ctx_folderopts_unsub':
            this.toggleSubscribed();
            break;

        case 'ctx_folderopts_expand':
        case 'ctx_folderopts_collapse':
            this._toggleSubFolder($('imp-normalmboxes'), id == 'ctx_folderopts_expand' ? 'expall' : 'colall', true);
            break;

        case 'ctx_folderopts_reload':
            this._reloadFolders();
            break;

        case 'ctx_container_expand':
        case 'ctx_container_collapse':
        case 'ctx_mbox_expand':
        case 'ctx_mbox_collapse':
            this._toggleSubFolder(e.findElement('LI').next(), (id == 'ctx_container_expand' || id == 'ctx_mbox_expand') ? 'expall' : 'colall', true);
            break;

        case 'ctx_container_search':
        case 'ctx_container_searchsub':
        case 'ctx_mbox_search':
        case 'ctx_mbox_searchsub':
            this.go('search', {
                mailbox: e.findElement('LI').retrieve('mbox'),
                subfolder: Number(id.endsWith('searchsub'))
            });
            break;

        case 'ctx_message_innocent':
        case 'ctx_message_spam':
            this.reportSpam(id == 'ctx_message_spam');
            break;

        case 'ctx_message_blacklist':
        case 'ctx_message_whitelist':
            this.blacklist(id == 'ctx_message_blacklist');
            break;

        case 'ctx_message_delete':
            this.deleteMsg();
            break;

        case 'ctx_message_forward':
        case 'ctx_message_reply':
            this.composeMailbox(id == 'ctx_message_forward' ? 'forward_auto' : 'reply_auto');
            break;

        case 'ctx_forward_editasnew':
        case 'ctx_message_editasnew':
        case 'ctx_message_template':
        case 'ctx_message_template_edit':
            this.composeMailbox(id.substring(12));
            break;

        case 'ctx_message_source':
            this.viewport.getSelected().get('dataob').each(function(v) {
                HordeCore.popupWindow(DimpCore.conf.URI_VIEW, {
                    actionID: 'view_source',
                    id: 0,
                    mailbox: v.mbox,
                    uid: v.uid
                }, {
                    name: v.uid + '|' + v.view
                });
            }, this);
            break;

        case 'ctx_message_resume':
            this.composeMailbox('resume');
            break;

        case 'ctx_message_view':
            this.viewport.getSelected().get('dataob').each(this.msgWindow.bind(this));
            break;

        case 'ctx_reply_reply':
        case 'ctx_reply_reply_all':
        case 'ctx_reply_reply_list':
            this.composeMailbox(id.substring(10));
            break;

        case 'ctx_forward_attach':
        case 'ctx_forward_body':
        case 'ctx_forward_both':
        case 'ctx_forward_redirect':
            this.composeMailbox(id.substring(4));
            break;

        case 'ctx_oa_preview_hide':
            this._setPref('preview_old', this._getPref('preview', 'horiz'));
            this.togglePreviewPane('');
            break;

        case 'ctx_oa_preview_show':
            this.togglePreviewPane(this._getPref('preview_old'));
            break;

        case 'ctx_oa_layout_horiz':
        case 'ctx_oa_layout_vert':
            this.togglePreviewPane(id.substring(14));
            break;

        case 'ctx_oa_blacklist':
        case 'ctx_oa_whitelist':
            this.blacklist(id == 'ctx_oa_blacklist');
            break;

        case 'ctx_message_undelete':
        case 'ctx_oa_undelete':
            this.flag(DimpCore.conf.FLAG_DELETED, false);
            break;

        case 'ctx_oa_purge_deleted':
            this.purgeDeleted();
            break;

        case 'ctx_oa_hide_deleted':
        case 'ctx_oa_show_deleted':
            this.viewport.reload({ delhide: Number(id == 'ctx_oa_hide_deleted') });
            break;

        case 'ctx_oa_help':
            this.toggleHelp();
            break;

        case 'ctx_sortopts_date':
        case 'ctx_sortopts_from':
        case 'ctx_sortopts_to':
        case 'ctx_sortopts_sequence':
        case 'ctx_sortopts_size':
        case 'ctx_sortopts_subject':
        case 'ctx_sortopts_thread':
            this.sort(DimpCore.conf.sort.get(id.substring(13)).v);
            break;

        case 'ctx_template_edit':
            this.composeMailbox('template_edit');
            break;

        case 'ctx_template_new':
            DimpCore.compose('template_new');
            break;

        case 'ctx_subjectsort_thread':
            this.sort(DimpCore.conf.sort.get(this.isThreadSort() ? 'subject' : 'thread').v);
            break;

        case 'ctx_datesort_date':
        case 'ctx_datesort_sequence':
            tmp = DimpCore.conf.sort.get(id.substring(13)).v;
            if (tmp != this.viewport.getMetaData('sortby')) {
                this.sort(tmp);
            }
            break;

        case 'ctx_vfolder_edit':
            tmp = {
                edit_query: 1,
                mailbox: e.findElement('LI').retrieve('mbox')
            };
            // Fall through

        case 'ctx_qsearchopts_advanced':
            this.go('search', tmp);
            break;

        case 'ctx_vcontainer_edit':
            this.go('prefs', { group: 'searches' });
            break;

        case 'ctx_qsearchby_all':
        case 'ctx_qsearchby_body':
        case 'ctx_qsearchby_from':
        case 'ctx_qsearchby_recip':
        case 'ctx_qsearchby_subject':
            this._setPref('qsearch_field', id.substring(14));
            this._setQsearchText();
            if (this.isQSearch()) {
                this.viewswitch = true;
                this.quicksearchRun();
            }
            break;

        default:
            if (menu == 'ctx_filteropts_filter') {
                this.search = {
                    filter: elt.identify().substring('ctx_filter_'.length),
                    label: this.viewport.getMetaData('label'),
                    mbox: this.view
                }
                this.go('mbox', DimpCore.conf.fsearchid);
            } else if (menu.endsWith('_setflag')) {
                tmp = elt.down('DIV');
                this.flag(elt.retrieve('flag'), !tmp.visible() || tmp.hasClassName('msCheck'));
            } else if (menu.endsWith('_unsetflag')) {
                this.flag(elt.retrieve('flag'), false);
            } else if (menu.endsWith('_flag') || menu.endsWith('_flagnot')) {
                this.search = {
                    flag: elt.retrieve('flag'),
                    label: this.viewport.getMetaData('label'),
                    mbox: this.view,
                    not: menu.endsWith('_flagnot')
                };
                this.go('mbox', DimpCore.conf.fsearchid);
            }
            break;
        }
    },

    contextOnShow: function(e)
    {
        var baseelt, elts, flags, ob, sel, tmp,
            ctx_id = e.memo;

        switch (ctx_id) {
        case 'ctx_mbox':
            elts = $('ctx_mbox_create', 'ctx_mbox_rename', 'ctx_mbox_delete');
            baseelt = e.findElement('DIV.horde-subnavi');

            if (baseelt.retrieve('mbox') == this.INBOX) {
                elts.invoke('hide');
                if ($('ctx_mbox_sub')) {
                    $('ctx_mbox_sub', 'ctx_mbox_unsub').invoke('hide');
                }
            } else {
                if ($('ctx_mbox_sub')) {
                    tmp = baseelt.hasClassName('unsubMbox');
                    [ $('ctx_mbox_sub') ].invoke(tmp ? 'show' : 'hide');
                    [ $('ctx_mbox_unsub') ].invoke(tmp ? 'hide' : 'show');
                }

                if (DimpCore.conf.fixed_mboxes &&
                    DimpCore.conf.fixed_mboxes.indexOf(baseelt.retrieve('mbox')) != -1) {
                    elts.shift();
                    elts.invoke('hide');
                } else {
                    elts.invoke('show');
                }
            }

            tmp = Object.isUndefined(baseelt.retrieve('u'));
            if (DimpCore.conf.poll_alter) {
                [ $('ctx_mbox_poll') ].invoke(tmp ? 'show' : 'hide');
                [ $('ctx_mbox_nopoll') ].invoke(tmp ? 'hide' : 'show');
            } else {
                $('ctx_mbox_poll', 'ctx_mbox_nopoll').invoke('hide');
            }

            [ $('ctx_mbox_expand').up() ].invoke(this.getSubMboxElt(baseelt) ? 'show' : 'hide');

            [ $('ctx_mbox_acl').up() ].invoke(DimpCore.conf.acl ? 'show' : 'hide');

            // Fall-through

        case 'ctx_container':
        case 'ctx_noactions':
        case 'ctx_vfolder':
            baseelt = e.findElement('DIV.horde-subnavi');
            $(ctx_id).down('DIV.mboxName').update(this.fullMboxDisplay(baseelt));
            break;

        case 'ctx_reply':
            sel = this.viewport.getSelected();
            if (sel.size() == 1) {
                ob = sel.get('dataob').first();
            }
            [ $('ctx_reply_reply_list') ].invoke(ob && ob.listmsg ? 'show' : 'hide');
            break;

        case 'ctx_oa':
            switch (this._getPref('preview')) {
            case 'vert':
                $('ctx_oa_preview_hide', 'ctx_oa_layout_horiz').invoke('show');
                $('ctx_oa_preview_show', 'ctx_oa_layout_vert').invoke('hide');
                break;

            case 'horiz':
                $('ctx_oa_preview_hide', 'ctx_oa_layout_vert').invoke('show');
                $('ctx_oa_preview_show', 'ctx_oa_layout_horiz').invoke('hide');
                break;

            default:
                $('ctx_oa_preview_hide', 'ctx_oa_layout_horiz', 'ctx_oa_layout_vert').invoke('hide');
                $('ctx_oa_preview_show').show();
                break;
            }

            tmp = $('ctx_oa_undeleted', 'ctx_oa_blacklist', 'ctx_oa_whitelist');
            sel = this.viewport.getSelected();

            if ($('ctx_oa_setflag')) {
                if (this.viewport.getMetaData('readonly')) {
                    $('ctx_oa_setflag').up().hide();
                } else {
                    tmp.push($('ctx_oa_setflag').up());
                    [ $('ctx_oa_unsetflag') ].invoke((sel.size() > 1) ? 'show' : 'hide');
                }
            }

            tmp.compact().invoke(sel.size() ? 'show' : 'hide');

            if (tmp = $('ctx_oa_purge_deleted')) {
                if (this.viewport.getMetaData('noexpunge')) {
                    tmp.hide();
                } else {
                    tmp.show();
                    [ tmp.up() ].invoke(tmp.up().select('> a').any(Element.visible) ? 'show' : 'hide');
                }
            }

            if (tmp = $('ctx_oa_hide_deleted')) {
                if (this.isThreadSort()) {
                    $(tmp, 'ctx_oa_show_deleted').invoke('hide');
                } else if (this.viewport.getMetaData('delhide')) {
                    tmp.hide();
                    $('ctx_oa_show_deleted').show();
                } else {
                    tmp.show();
                    $('ctx_oa_show_deleted').hide();
                }
            }
            break;

        case 'ctx_sortopts':
            elts = $(ctx_id).select('a span.iconImg');
            tmp = this.viewport.getMetaData('sortby');

            elts.each(function(e) {
                e.removeClassName('sortdown').removeClassName('sortup');
            });

            DimpCore.conf.sort.detect(function(s) {
                if (s.value.v == tmp) {
                    $('ctx_sortopts_' + s.key).down('.iconImg').addClassName(this.viewport.getMetaData('sortdir') ? 'sortup' : 'sortdown');
                    return true;
                }
            }, this);

            tmp = this.viewport.getMetaData('special');
            [ $('ctx_sortopts_from') ].invoke(tmp ? 'hide' : 'show');
            [ $('ctx_sortopts_to') ].invoke(tmp ? 'show' : 'hide');
            break;

        case 'ctx_qsearchby':
            $(ctx_id).descendants().invoke('removeClassName', 'contextSelected');
            $(ctx_id + '_' + this._getPref('qsearch_field')).addClassName('contextSelected');
            break;

        case 'ctx_message':
            [ $('ctx_message_source').up() ].invoke(this._getPref('preview') ? 'hide' : 'show');
            $('ctx_message_delete', 'ctx_message_undelete').compact().invoke(this.viewport.getMetaData('nodelete') ? 'hide' : 'show');

            [ $('ctx_message_setflag').up() ].invoke(this.viewport.getMetaData('flags').size() & this.viewport.getMetaData('readonly') ? 'hide' : 'show');

            sel = this.viewport.getSelected();
            if (sel.size() == 1) {
                if (this.viewport.getMetaData('templates')) {
                    $('ctx_message_resume').hide().up().show();
                    $('ctx_message_editasnew').hide();
                    $('ctx_message_template', 'ctx_message_template_edit').invoke('show');
                } else if (this.isDraft(sel)) {
                    $('ctx_message_template', 'ctx_message_template_edit').invoke('hide');
                    $('ctx_message_editasnew').show();
                    $('ctx_message_resume').show().up('DIV').show();
                } else {
                    $('ctx_message_editasnew').show();
                    $('ctx_message_resume').up('DIV').hide();
                }
                [ $('ctx_message_unsetflag') ].compact().invoke('hide');
            } else {
                $('ctx_message_resume').up('DIV').hide();
                $('ctx_message_editasnew', 'ctx_message_unsetflag').compact().invoke('show');
            }
            break;

        case 'ctx_flag':
            flags = this.viewport.getMetaData('flags');
            if (flags.size()) {
                $(ctx_id).childElements().each(function(c) {
                    [ c ].invoke(flags.include(c.retrieve('flag')) ? 'show' : 'hide');
                });
            } else {
                $(ctx_id).childElements().invoke('show');
            }

            sel = this.viewport.getSelected();
            flags = (sel.size() == 1)
                ? sel.get('dataob').first().flag
                : null;

            $(ctx_id).childElements().each(function(elt) {
                DimpCore.toggleCheck(elt.down('DIV'), (flags === null) ? null : flags.include(elt.retrieve('flag')));
            });
            break;

        case 'ctx_datesort':
            $(ctx_id).descendants().invoke('removeClassName', 'contextSelected');
            tmp = this.viewport.getMetaData('sortby');
            [ 'date', 'sequence' ].find(function(n) {
                if (DimpCore.conf.sort.get(n).v == tmp) {
                    $('ctx_datesort_' + n).addClassName('contextSelected');
                    return true;
                }
            });
            break;

        case 'ctx_subjectsort':
            DimpCore.toggleCheck($('ctx_subjectsort_thread').down('.iconImg'), this.isThreadSort());
            break;

        case 'ctx_preview':
            [ $('ctx_preview_allparts') ].invoke(this.pp.hide_all ? 'hide' : 'show');
            break;

        case 'ctx_template':
            [ $('ctx_template_edit') ].invoke(this.viewport.getSelected().size() == 1 ? 'show' : 'hide');
            break;
        }
    },

    contextOnTrigger: function(e)
    {
        switch (e.memo) {
        case 'ctx_flag':
        case 'ctx_flag_search':
            DimpCore.conf.flags_o.each(function(f) {
                if ((DimpCore.conf.flags[f].a && (e.memo == 'ctx_flag')) ||
                    (DimpCore.conf.flags[f].s && (e.memo == 'ctx_flag_search'))) {
                    this.contextAddFlag(f, DimpCore.conf.flags[f], e.memo);
                }
            }, this);
            break;

        case 'ctx_folderopts':
            $('ctx_folderopts_sub').hide();
            break;
        }
    },

    contextAddFlag: function(flag, f, id)
    {
        var a = new Element('A'),
            style = {};

        if (id == 'ctx_flag') {
            a.insert(new Element('DIV', { className: 'iconImg' }));
        }

        if (f.u) {
            style.backgroundColor = f.b.escapeHTML();
        }

        $(id).insert(
            a.insert(
                new Element('SPAN', { className: 'iconImg' }).addClassName(f.i ? f.i.escapeHTML() : f.c.escapeHTML()).setStyle(style)
            ).insert(
                f.l.escapeHTML()
            )
        );

        a.store('flag', flag);
    },

    updateTitle: function()
    {
        var elt, unseen,
            label = this.viewport.getMetaData('label');

        // 'label' will not be set if there has been an error
        // retrieving data from the server.
        if (!label || !$('dimpmain_folder').visible()) {
            return;
        }

        if (this.isSearch()) {
            if (this.isQSearch()) {
                label += ' (' + this.search.label + ')';
            }
        } else if (elt = this.getMboxElt(this.view)) {
            unseen = elt.retrieve('u');
        }

        this.setTitle(label, unseen);
    },

    sort: function(sortby)
    {
        var s;

        if (Object.isUndefined(sortby)) {
            return;
        }

        sortby = Number(sortby);
        if (sortby == this.viewport.getMetaData('sortby')) {
            if (this.viewport.getMetaData('sortdirlock')) {
                return;
            }
            s = { sortdir: (this.viewport.getMetaData('sortdir') ? 0 : 1) };
            this.viewport.setMetaData({ sortdir: s.sortdir });
        } else {
            if (this.viewport.getMetaData('sortbylock')) {
                return;
            }
            s = { sortby: sortby };
            this.viewport.setMetaData({ sortby: s.sortby });
        }

        this.setSortColumns(sortby);
        this.viewport.reload(s);
    },

    setSortColumns: function(sortby)
    {
        var elt, tmp, tmp2,
            ptr = DimpCore.conf.sort,
            m = $('msglistHeaderHoriz');

        if (Object.isUndefined(sortby)) {
            sortby = this.viewport.getMetaData('sortby');
        }

        /* Init once per load. */
        if (this.sort_init) {
            [ m.down('.sortup') ].compact().invoke('removeClassName', 'sortup');
            [ m.down('.sortdown') ].compact().invoke('removeClassName', 'sortdown');
        } else {
            ptr.each(function(s) {
                if (s.value.t) {
                    var elt = new Element('A').insert(s.value.t).store('sortby', s.value.v);
                    if (s.value.ec) {
                        elt.addClassName(s.value.ec);
                    }
                    m.down('.' + s.value.c).insert({
                        top: elt
                    });
                }
            });
            this.sort_init = true;
        }

        /* Toggle between From/To header. */
        tmp = this.viewport.getMetaData('special');
        tmp2 = m.down('a.msgFromTo');
        [ tmp2 ].invoke(tmp ? 'show' : 'hide');
        tmp2.siblings().invoke(tmp ? 'hide' : 'show');

        [ m.down('.msgSubject .horde-subnavi-arrow'), m.down('.msgDate .horde-subnavi-arrow') ].invoke(this.viewport.getMetaData('sortbylock') ? 'hide' : 'show');

        ptr.find(function(s) {
            if (sortby != s.value.v) {
                return false;
            }
            if (elt = m.down('.' + s.value.c)) {
                elt.addClassName(this.viewport.getMetaData('sortdir') ? 'sortup' : 'sortdown');
                elt.down('A').store('sortby', s.value.v);
            }
            return true;
        }, this);
    },

    isThreadSort: function()
    {
        return (this.viewport.getMetaData('sortby') == DimpCore.conf.sort.get('thread').v);
    },

    // Preview pane functions
    // mode = (string) Either 'horiz', 'vert', or empty
    togglePreviewPane: function(mode)
    {
        var old = this._getPref('preview');
        if (mode != old) {
            this._setPref('preview', mode);
            this.viewport.showSplitPane(mode);
            if (!old) {
                this.initPreviewPane();
            }
        }
    },

    loadPreview: function(data, params)
    {
        var curr, msgload, row, rows, pp_uid;

        if (!this._getPref('preview')) {
            return;
        }

        // If single message is loaded, and this is the INBOX, try to preload
        // next unseen message that exists in current buffer.
        if (data && data.mbox == this.INBOX) {
            curr = this.viewport.getSelected().get('rownum').first();
            rows = this.viewport.createSelectionBuffer().search({
                flag: { notinclude: DimpCore.conf.FLAG_SEEN }
            }).get('rownum').diff([ curr ]).numericSort();

            if (rows.size()) {
                row = rows.detect(function(r) {
                    return (r > curr);
                });
                if (!row) {
                    row = rows.last();
                }

                msgload = DimpCore.toUIDString(DimpCore.selectionToRange(this.viewport.createSelection('rownum', row)));
            }
        }

        if (!params) {
            if (this.pp &&
                this.pp.uid == data.uid &&
                this.pp.mbox == data.mbox) {
                return;
            }
            this.pp = data;
            pp_uid = this._getPPId(data.uid, data.mbox);

            if (this.ppfifo.indexOf(pp_uid) != -1) {
                params = {
                    mailbox: data.mbox,
                    uid: data.uid
                };

                if (msgload) {
                    params.params = { msgload: msgload };
                }

                this.flag('\\seen', true, params);

                return this._loadPreview(data.uid, data.mbox);
            }

            params = {};
        }

        if (msgload) {
            params.msgload = msgload;
        }
        params.preview = 1;

        this.loadingImg('msg', true);

        DimpCore.doAction('showMessage', this.addViewportParams(params), {
            callback: function(r) {
                if (!r || r.error) {
                    if (r) {
                        HordeCore.notify(r.error, r.errortype);
                    }
                    this.clearPreviewPane();
                } else if (this.view == r.view &&
                           this.pp &&
                           this.pp.uid == r.uid &&
                           this.pp.mbox == r.mbox) {
                    this._loadPreview(r.uid, r.mbox);
                }
            }.bind(this),
            uids: this.viewport.createSelection('dataob', this.pp)
        });
    },

    _loadPreview: function(uid, mbox)
    {
        var tmp,
            pm = $('previewMsg'),
            r = this.ppcache[this._getPPId(uid, mbox)];

        pm.select('.address').each(function(elt) {
            DimpCore.DMenu.removeElement(elt.identify());
        });

        // Add subject. Subject was already html encoded on server (subject
        // may include links).
        tmp = pm.select('.subject');
        tmp.invoke('update', r.subject === null ? '[' + DimpCore.text.badsubject + ']' : r.subject);

        // Add date
        [ $('msgHeaderDate') ].flatten().invoke(r.localdate ? 'show' : 'hide');
        [ $('msgHeadersColl').select('.date'), $('msgHeaderDate').select('.date') ].flatten().invoke('update', r.localdate);

        // Add from/to/cc/bcc headers
        [ 'from', 'to', 'cc', 'bcc' ].each(function(h) {
            if (r[h]) {
                this.updateHeader(h, r[h], true);
                $('msgHeader' + h.capitalize()).show();
            } else {
                $('msgHeader' + h.capitalize()).hide();
            }
        }, this);

        // Add attachment information
        if (r.atc_label) {
            $('msgAtc').show();
            tmp = $('partlist');
            tmp.previous().update(new Element('SPAN', { className: 'atcLabel' }).insert(r.atc_label)).insert(r.atc_download);
            if (r.atc_list) {
                tmp.update(new Element('TABLE').insert(r.atc_list));
            }
        } else {
            $('msgAtc').hide();
        }

        // Add message log information
        DimpCore.updateMsgLog(r.log);

        // Toggle resume link
        if (this.viewport.getMetaData('templates')) {
            $('msg_resume_draft').up().hide();
            $('msg_template').up().show();
        } else {
            $('msg_template').up().hide();
            [ $('msg_resume_draft').up() ].invoke(this.isDraft(this.viewport.getSelection()) ? 'show' : 'hide');
        }

        this.pp.hide_all = r.onepart;
        this.pp.save_as = r.save_as;

        $('messageBody').update(
            (r.msgtext === null)
                ? $('messageBodyError').down().clone(true).show().writeAttribute('id', 'ppane_view_error')
                : r.msgtext
        );

        // See: http://www.thecssninja.com/javascript/gmail-dragout
        if (Prototype.Browser.WebKit) {
            $('messageBody').select('DIV.mimePartInfo A.downloadAtc').invoke('observe', 'dragstart', this._dragAtc);
        }

        this.loadingImg('msg', false);
        $('previewInfo').hide();
        $('previewPane').scrollTop = 0;
        pm.show();

        if (r.js) {
            eval(r.js.join(';'));
        }
    },

    messageCallback: function(r)
    {
        // Store messages in cache.
        r.each(function(msg) {
            var ppuid = this._getPPId(msg.uid, msg.mbox);
            this._expirePPCache([ ppuid ]);
            this.ppcache[ppuid] = msg;
            this.ppfifo.push(ppuid);
        }, this);
    },

    _dragAtc: function(e)
    {
        var base = e.element().up();

        e.dataTransfer.setData(
            'DownloadURL',
            base.down('IMG').readAttribute('title') + ':' +
            base.down('SPAN.mimePartInfoDescrip A').textContent.gsub(':', '-') + ':' +
            window.location.origin + e.element().readAttribute('href')
        );
    },

    updateAddressHeader: function(e)
    {
        this.loadingImg('msg', true);
        DimpCore.doAction('addressHeader', {
            header: $w(e.element().className).first()
        }, {
            callback: this._updateAddressHeaderCallback.bind(this),
            uids: this.viewport.createSelection('dataob', this.pp)
        });
    },

    _updateAddressHeaderCallback: function(r)
    {
        this.loadingImg('msg', false);
        $H(r.hdr_data).each(function(d) {
            this.updateHeader(d.key, d.value);
        }, this);
    },

    updateHeader: function(hdr, data, limit)
    {
        (hdr == 'from' ? $('previewMsg').select('.' + hdr) : [ $('msgHeadersContent').down('THEAD').down('.' + hdr) ]).each(function(elt) {
            elt.replace(DimpCore.buildAddressLinks(data, elt.clone(false), limit));
        });
    },

    _mimeTreeCallback: function(r)
    {
        this.pp.hide_all = true;

        $('partlist').update(r.tree).previous().update(new Element('SPAN', { className: 'atcLabel' }).insert(DimpCore.text.allparts_label));
        $('partlist_col').show();
        $('partlist_exp').hide();
        $('msgAtc').show();

        this.loadingImg('msg', false);
    },

    _sendMdnCallback: function(r)
    {
        this._expirePPCache([ this._getPPId(r.uid, r.mbox) ]);

        if (this.pp &&
            this.pp.uid == r.uid &&
            this.pp.mbox == r.mbox) {
            this.loadingImg('msg', false);
            $('sendMdnMessage').up(1).fade({ duration: 0.2 });
        }
    },

    maillogCallback: function(r)
    {
        r.each(function(l) {
            var tmp = this._getPPId(l.uid, l.mbox);
            if (this.ppcache[tmp]) {
                this.ppcache[tmp].log = l.log;
                if (this.pp &&
                    this.pp.uid == l.uid &&
                    this.pp.mbox == l.mbox) {
                    DimpCore.updateMsgLog(l.log);
                }
            }
        }, this);
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
        var sel, txt;

        this.loadingImg('msg', false);
        $('previewMsg').hide();
        $('previewPane').scrollTop = 0;

        sel = this.selectedCount();
        switch (sel) {
        case 0:
            txt = DimpCore.text.nomessages;
            break;

        case 1:
            txt = 1 + ' ' + DimpCore.text.message;
            break;

        default:
            txt = sel + ' ' + DimpCore.text.messages;
            break;
        }
        $('previewInfo').update(txt + ' ' + DimpCore.text.selected + '.').show();

        delete this.pp;
    },

    _toggleHeaders: function(elt, update)
    {
        if (update) {
            this._setPref('toggle_hdrs', Number(!this._getPref('toggle_hdrs')));
        }
        [ $('msgHeadersColl', 'msgHeaders') ].flatten().invoke('toggle');
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

    _getPPId: function(uid, mailbox)
    {
        return uid + '|' + mailbox;
    },

    // mbox = (string|Element) The mailbox to query.
    // Return: Number or undefined
    getUnseenCount: function(mbox)
    {
        var elt = this.getMboxElt(mbox);

        if (elt) {
            elt = elt.retrieve('u');
            if (!Object.isUndefined(elt)) {
                return Number(elt);
            }
        }

        return elt;
    },

    // mbox: (string) Mailbox name.
    // unseen: (integer) The updated value.
    updateUnseenStatus: function(mbox, unseen)
    {
        this.setMboxLabel(mbox, unseen);

        if (this.view == mbox) {
            this.updateTitle();
        }
    },

    setMessageListTitle: function()
    {
        var range,
            rows = this.viewport.getMetaData('total_rows'),
            text = this.viewport.getMetaData('label');

        if (rows) {
            range = this.viewport.currentViewableRange();
            text += ' (' + (
                (rows == 1)
                    ? 1 + ' ' + DimpCore.text.message :
                    rows + ' ' + DimpCore.text.messages
            ) + ')';
        }

        $('mailboxName').update(text);
    },

    // m = (string|Element) Mailbox element.
    setMboxLabel: function(m, unseen)
    {
        var elt = this.getMboxElt(m);

        if (!elt) {
            return;
        }

        if (Object.isUndefined(unseen)) {
            unseen = this.getUnseenCount(elt.retrieve('mbox'));
        } else {
            if (Object.isUndefined(elt.retrieve('u')) ||
                elt.retrieve('u') == unseen) {
                return;
            }

            unseen = Number(unseen);
            elt.store('u', unseen);
        }

        if (window.fluid && elt.retrieve('mbox') == this.INBOX) {
            window.fluid.setDockBadge(unseen ? unseen : '');
        }

        elt.down('A').update((unseen > 0) ?
            new Element('STRONG').insert(elt.retrieve('l')).insert('&nbsp;').insert(new Element('SPAN', { className: 'count', dir: 'ltr' }).insert('(' + unseen + ')')) :
            elt.retrieve('l'));
    },

    getMboxElt: function(id)
    {
        return Object.isElement(id)
            ? id
            : this.mboxes[id];
    },

    getSubMboxElt: function(id)
    {
        var m_elt = Object.isElement(id)
            ? id
            : (this.smboxes[id] || this.mboxes[id]);

        if (!m_elt) {
            return null;
        }

        m_elt = m_elt.next();
        return (m_elt && m_elt.hasClassName('subfolders'))
            ? m_elt
            : null;
    },

    fullMboxDisplay: function(elt)
    {
        return elt.readAttribute('title').escapeHTML();
    },

    /* Folder list updates. */

    // search = (boolean) If true, update search results as well.
    poll: function(search)
    {
        var args = $H();

        // Reset poll counter.
        this.setPoll();

        // Check for label info - it is possible that the mailbox may be
        // loading but not complete yet and sending this request will cause
        // duplicate info to be returned.
        if (this.view &&
            $('dimpmain_folder').visible() &&
            this.viewport.getMetaData('label')) {
            args = this.addViewportParams();

            // Possible further optimization: only poll VISIBLE mailboxes.
            // Issue: it is quite expensive to determine this, since the
            // mailbox elements themselves aren't hidden - it is one of the
            // parent containers. Probably not worth the effort.
            args.set('poll', Object.toJSON($('foldersSidebar').select('.mbox').findAll(function(elt) {
                return !Object.isUndefined(elt.retrieve('u')) && elt.visible();
            }).invoke('retrieve', 'mbox')));
        } else {
            args.set('poll', Object.toJSON([]));
        }

        if (search) {
            args.set('forceUpdate', 1);
        }

        //$('checkmaillink').down('A').update('[' + DimpCore.text.check + ']');
        DimpCore.doAction('poll', args);
    },

    pollCallback: function(r)
    {
        /* Don't update polled status until the sidebar is visible. Otherwise,
         * preview callbacks may not correctly update unseen status. */
        if (!$('foldersSidebar').visible()) {
            return this.pollCallback.bind(this, r).defer();
        }

        $H(r).each(function(u) {
            this.updateUnseenStatus(u.key, u.value);
        }, this);

        //$('checkmaillink').down('A').update(DimpCore.text.getmail);
    },

    quotaCallback: function(r)
    {
        $('quota-text').setText(r.m);
    },

    setPoll: function()
    {
        if (DimpCore.conf.refresh_time) {
            if (this.pollPE) {
                this.pollPE.stop();
            }
            // Run in anonymous function, or else PeriodicalExecuter passes
            // in itself as first ('force') parameter to poll().
            this.pollPE = new PeriodicalExecuter(function() { this.poll(); }.bind(this), DimpCore.conf.refresh_time);
        }
    },

    /* Search functions. */
    isSearch: function(id)
    {
        return this.viewport.getMetaData('search', id);
    },

    isFSearch: function(id)
    {
        return ((id ? id : this.view) == DimpCore.conf.fsearchid);
    },

    isQSearch: function(id)
    {
        return ((id ? id : this.view) == DimpCore.conf.qsearchid);
    },

    quicksearchRun: function()
    {
        var q = $F('horde-search-input');

        if (this.isSearch()) {
            /* Search text has changed. */
            if (this.search.query != q) {
                this.viewswitch = true;
                this.search.query = q;
            }
            this.resetSelected();
            this.viewport.reload();
        } else {
            this.search = {
                label: this.viewport.getMetaData('label'),
                mbox: this.view,
                qsearch: true,
                query: q
            };
            this.go('mbox', DimpCore.conf.qsearchid);
        }
    },

    // 'noload' = (boolean) If true, don't load the mailbox
    quicksearchClear: function(noload)
    {
        var f = this.view,
            qs = $('horde-search');

        if (!qs) {
            return;
        }

        if (this.isSearch()) {
            $(qs, 'horde-search-dropdown', 'horde-search-input').invoke('show');
            if (!noload) {
                this.go('mbox', (this.search ? this.search.mbox : this.INBOX));
            }
            delete this.search;

            $('horde-search-input').clear();
            if (this.qsearch_ghost) {
                // Needed because there is no reset method in ghost JS (as of
                // H4).
                this.qsearch_ghost.unghost();
                this.qsearch_ghost.ghost();
            }
        }
    },

    /* Set quicksearch text. */
    _setQsearchText: function()
    {
        $('horde-search-input').writeAttribute('title', DimpCore.text.search + ' (' + DimpCore.context.ctx_qsearchby['*' + this._getPref('qsearch_field')] + ')');
        if (this.qsearch_ghost) {
            this.qsearch_ghost.refresh();
        }
    },

    /* Handle searchbar. */
    showSearchbar: function(show)
    {
        if ($('searchbar').visible()) {
            if (!show) {
                $('searchbar').hide();
                this.viewport.onResize(true);
                this.searchbarTimeReset(false);
            }
        } else if (show) {
            $('searchbar').show();
            this.viewport.onResize(true);
            this.searchbarTimeReset(true);
        }
    },

    searchbarTimeReset: function(restart)
    {
        if (this.searchbar_time) {
            this.searchbar_time.stop();
            delete this.searchbar_time;
            $('search_time_elapsed').hide();
        }

        if (restart) {
            this.searchbar_time_mins = 0;
            this.searchbar_time = new PeriodicalExecuter(function() {
                if (++this.searchbar_time_mins > 5) {
                    $('search_time_elapsed').update(DimpCore.text.search_time.sub('%d', this.searchbar_time_mins).escapeHTML()).show();
                }
            }.bind(this), 60);
        }
    },

    /* Enable/Disable DIMP action buttons as needed. */
    toggleButtons: function()
    {
        DimpCore.toggleButtons($('dimpmain_folder_top').select('DIV.horde-buttonbar DIV.noselectDisable'), this.selectedCount() == 0);
    },

    /* Drag/Drop handler. */
    mboxDropHandler: function(e)
    {
        var dropbase, sel, uids,
            drag = e.memo.element,
            drop = e.element(),
            mboxname = drop.retrieve('mbox'),
            ftype = drop.retrieve('ftype');

        if (drag.hasClassName('mbox')) {
            dropbase = (drop == $('dropbase'));
            if (dropbase ||
                (ftype != 'special' && !this.isSubfolder(drag, drop))) {
                DimpCore.doAction('renameMailbox', { old_name: drag.retrieve('mbox'), new_parent: dropbase ? '' : mboxname, new_name: drag.retrieve('l') });
            }
        } else if (ftype != 'container') {
            sel = this.viewport.getSelected();

            if (sel.size()) {
                // Dragging multiple selected messages.
                uids = sel;
            } else if (drag.retrieve('mbox') != mboxname) {
                // Dragging a single unselected message.
                uids = this.viewport.createSelection('domid', drag.id);
            }

            if (uids.size()) {
                if (e.memo.dragevent.ctrlKey) {
                    DimpCore.doAction('copyMessages', this.addViewportParams({ mboxto: mboxname }), { uids: uids });
                } else if (this.view != mboxname) {
                    // Don't allow drag/drop to the current mailbox.
                    this.updateFlag(uids, DimpCore.conf.FLAG_DELETED, true);
                    DimpCore.doAction('moveMessages', this.addViewportParams({ mboxto: mboxname }), { uids: uids });
                }
            }
        }
    },

    dragCaption: function()
    {
        var cnt = this.selectedCount();
        return cnt + ' ' + (cnt == 1 ? DimpCore.text.message : DimpCore.text.messages);
    },

    onDragMouseDown: function(e)
    {
        var args,
            elt = e.element(),
            id = elt.identify(),
            d = DragDrop.Drags.getDrag(id);

        if (elt.hasClassName('vpRow')) {
            args = { right: e.memo.isRightClick() };
            d.selectIfNoDrag = false;

            // Handle selection first.
            if (DimpCore.DMenu.operaCheck(e)) {
                if (!this.isSelected('domid', id)) {
                    this.msgSelect(id, { right: true });
                }
            } else if (!args.right && (e.memo.ctrlKey || e.memo.metaKey)) {
                this.msgSelect(id, $H({ ctrl: true }).merge(args).toObject());
            } else if (e.memo.shiftKey) {
                this.msgSelect(id, $H({ shift: true }).merge(args).toObject());
            } else if (this.isSelected('domid', id)) {
                if (!args.right) {
                    if (e.memo.element().hasClassName('msCheck')) {
                        this.msgSelect(id, { ctrl: true, right: true });
                    } else {
                        d.selectIfNoDrag = true;
                    }
                }
            } else if (e.memo.element().hasClassName('msCheck')) {
                this.msgSelect(id, { ctrl: true, right: true });
            } else {
                this.msgSelect(id, args);
            }
        } else if (elt.hasClassName('mbox')) {
            d.opera = DimpCore.DMenu.operaCheck(e);
        }
    },

    onDragStart: function(e)
    {
        if (e.element().hasClassName('horde-subnavi')) {
            var d = e.memo;
            if (!d.opera && !d.wasDragged) {
                $('folderopts_link').up().hide();
                $('dropbase').up().show();
                d.ghost.removeClassName('on');
            }
        }
    },

    onDragEnd: function(e)
    {
        var elt = e.element(),
            id = elt.identify(),
            d = DragDrop.Drags.getDrag(id);

        if (id == 'horde-slideleft') {
            this._setPref('splitbar_side', d.lastCoord[0]);
            this.setSidebarWidth();
        } else if (elt.hasClassName('horde-subnavi')) {
            if (!d.opera) {
                $('folderopts_link').up().show();
                $('dropbase').up().hide();
            }
        }
    },

    onDragMouseUp: function(e)
    {
        var elt = e.element(),
            id = elt.identify();

        if (elt.hasClassName('vpRow') &&
            DragDrop.Drags.getDrag(id).selectIfNoDrag) {
            this.msgSelect(id, { right: e.memo.isRightClick() });
        }
    },

    /* Keydown event handler */
    keydownHandler: function(e)
    {
        var all, cnt, co, form, h, need, pp, ps, r, row, rownum, rowoff, sel,
            tmp, vsel, prev,
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
                if (elt.readAttribute('id') == 'horde-search-input') {
                    if (kc == Event.KEY_ESC || !elt.getValue()) {
                        this.quicksearchClear();
                    }
                    elt.blur();
                    e.stop();
                }
                break;

            case Event.KEY_RETURN:
                if (elt.readAttribute('id') == 'horde-search-input') {
                    if ($F('horde-search-input')) {
                        this.quicksearchRun();
                    } else {
                        this.quicksearchClear();
                    }
                    e.stop();
                }
                break;
            }

            return;
        }

        sel = this.viewport.getSelected();

        switch (kc) {
        case Event.KEY_DELETE:
        case Event.KEY_BACKSPACE:
            if (!this.viewport.getMetaData('nodelete')) {
                r = sel.get('dataob');
                if (e.shiftKey) {
                    this.moveSelected((r.last().VP_rownum == this.viewport.getMetaData('total_rows')) ? (r.first().VP_rownum - 1) : (r.last().VP_rownum + 1), true);
                }
                this.deleteMsg({ vs: sel });
            }
            e.stop();
            break;

        case Event.KEY_UP:
        case Event.KEY_DOWN:
        case Event.KEY_LEFT:
        case Event.KEY_RIGHT:
            prev = kc == Event.KEY_UP || kc == Event.KEY_LEFT;
            if (e.shiftKey && this.lastrow != -1) {
                row = this.viewport.createSelection('rownum', this.lastrow.get('rownum').first() + ((prev) ? -1 : 1));
                if (row.size()) {
                    row = row.get('dataob').first();
                    this.viewport.scrollTo(row.VP_rownum, { bottom: true });
                    this.msgSelect(row.VP_domid, { shift: true });
                }
            } else {
                this.moveSelected(prev ? -1 : 1, false, !prev);
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
                        pp.scrollTop = Math.max(pp.scrollTop - h + 20, 0);
                        break;

                    case Event.KEY_PAGEDOWN:
                        pp.scrollTop = Math.min(pp.scrollTop + h - 20, pp.scrollHeight - h + 1);
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
                this.moveSelected(move, false, kc == Event.KEY_PAGEDOWN);
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

        case 78: // N
        case 110: // n
            if (e.shiftKey && !this.isSearch()) {
                cnt = this.getUnseenCount(this.view);
                if (Object.isUndefined(cnt) || cnt) {
                    vsel = this.viewport.createSelectionBuffer();
                    row = vsel.search({ flag: { notinclude: DimpCore.conf.FLAG_SEEN } }).get('rownum');
                    all = (vsel.size() == this.viewport.getMetaData('total_rows'));

                    if (all ||
                        (!Object.isUndefined(cnt) && row.size() == cnt)) {
                        // Here we either have the entire mailbox in buffer,
                        // or all unseen messages are in the buffer.
                        if (sel.size()) {
                            tmp = sel.get('rownum').last();
                            if (tmp) {
                                rownum = row.detect(function(r) {
                                    return tmp < r;
                                });
                            }
                        } else {
                            rownum = tmp = row.first();
                        }
                    } else {
                        // Here there is no guarantee that the next unseen
                        // message will appear in the current buffer. Need to
                        // determine if any gaps are between last selected
                        // message and next unseen message in buffer.
                        vsel = vsel.get('rownum');

                        if (sel.size()) {
                            // We know that the selected rows are in the
                            // buffer.
                            tmp = sel.get('rownum').last();
                        } else if (vsel.include(1)) {
                            // If no selected rows, start searching from the
                            // first entry.
                            tmp = 0;
                        } else {
                            // First message is not in current buffer.
                            need = true;
                        }

                        if (!need) {
                            rownum = vsel.detect(function(r) {
                                if (r > tmp) {
                                    if (++tmp != r) {
                                        // We have found a gap.
                                        need = true;
                                        throw $break;
                                    }
                                    return row.include(tmp);
                                }
                            });

                            if (!need && !rownum) {
                                need = (tmp !== this.viewport.getMetaData('total_rows'));
                            }
                        }

                        if (need) {
                            this.viewport.select(null, { search: { unseen: 1 } });
                        }
                    }

                    if (rownum) {
                        this.moveSelected(rownum, true);
                    }
                }
                e.stop();
            }
            break;
        }
    },

    dblclickHandler: function(e)
    {
        var elt = e.element(),
            tmp, tmp2;

        if (elt.hasClassName('splitBarVertSidebar')) {
            this._setPref('splitbar_side', null);
            this.setSidebarWidth();
            e.memo.stop();
            return;
        } else if (elt.hasClassName('vpRow')) {
            tmp = this.viewport.createSelection('domid', elt.identify());
            tmp2 = tmp.get('dataob').first();

            if (this.viewport.getMetaData('templates')) {
                DimpCore.compose('template', { uids: tmp });
            } else if (this.isDraft(tmp)) {
                DimpCore.compose('resume', { uids: tmp });
            } else {
                this.msgWindow(tmp2);
            }
            e.memo.stop();
        }
    },

    clickHandler: function(e)
    {
        var tmp;

        if (DimpCore.DMenu.operaCheck(e.memo)) {
            return;
        }

        switch (e.element().readAttribute('id')) {
        case 'imp-normalmboxes':
        case 'imp-specialmboxes':
            this._handleMboxMouseClick(e.memo);
            break;

        case 'appportal':
        case 'hometab':
        case 'logolink':
            this.go('portal');
            e.memo.stop();
            break;

        case 'button_compose':
        case 'composelink':
            DimpCore.compose('new');
            e.memo.stop();
            break;

        case 'search_refresh':
            this.loadingImg('viewport', true);
            this.searchbarTimeReset(true);
            this.poll(true);
            e.memo.stop();
            break;

        case 'checkmaillink':
            this.poll(false);
            e.memo.stop();
            break;

        case 'search_edit':
            this.go('search', {
                edit_query: 1,
                mailbox: this.view
            });
            e.memo.stop();
            break;

        case 'alertsloglink':
            HordeCore.Growler.toggleLog();
            break;

        case 'applyfilterlink':
            if (this.viewport) {
                this.viewport.reload({ applyfilter: 1 });
            }
            e.memo.stop();
            break;

        case 'appprefs':
            this.go('prefs');
            e.memo.stop();
            break;

        case 'applogout':
            e.element().down('A').update('[' + DimpCore.text.onlogout + ']');
            HordeCore.logout();
            e.memo.stop();
            break;

        case 'button_forward':
            this.composeMailbox('forward_auto');
            break;

        case 'button_reply':
            this.composeMailbox('reply_auto');
            break;

        case 'button_resume':
            this.composeMailbox('resume');
            e.memo.stop();
            break;

        case 'button_template':
            if (this.viewport.getSelection().size()) {
                this.composeMailbox('template');
            }
            e.memo.stop();
            break;

        case 'button_innocent':
            this.reportSpam(false);
            e.memo.stop();
            break;

        case 'button_spam':
            this.reportSpam(true);
            e.memo.stop();
            break;

        case 'button_delete':
            this.deleteMsg();
            e.memo.stop();
            break;

        case 'msglistHeaderHoriz':
            tmp = e.memo.element();
            if (tmp.hasClassName('msCheckAll')) {
                this.selectAll();
            } else {
                this.sort(tmp.retrieve('sortby'));
            }
            e.memo.stop();
            break;

        case 'msglistHeaderVert':
            tmp = e.memo.element();
            if (tmp.hasClassName('msCheckAll')) {
                this.selectAll();
            }
            e.memo.stop();
            break;

        case 'th_expand':
        case 'th_collapse':
            this._toggleHeaders(e.element(), true);
            break;

        case 'msgloglist_toggle':
        case 'partlist_toggle':
            tmp = (e.element().readAttribute('id') == 'partlist_toggle') ? 'partlist' : 'msgloglist';
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
        case 'ppane_view_error':
            this.msgWindow(this.viewport.getSelection().search({
                mbox: {
                    equal: [ this.pp.mbox ]
                },
                uid: {
                    equal: [ this.pp.uid ]
                }
            }).get('dataob').first());
            e.memo.stop();
            break;

        case 'ctx_preview_save':
            HordeCore.redirect(this.pp.save_as);
            break;

        case 'ctx_preview_viewsource':
            HordeCore.popupWindow(DimpCore.conf.URI_VIEW, {
                actionID: 'view_source',
                id: 0,
                mailbox: this.pp.mbox,
                uid: this.pp.uid
            }, {
                name: this.pp.uid + '|' + this.pp.mbox
            });
            break;

        case 'ctx_preview_allparts':
            this.loadingImg('msg', true);
            DimpCore.doAction('messageMimeTree', {
                preview: 1
            }, {
                callback: this._mimeTreeCallback.bind(this),
                uids: this.viewport.createSelection('dataob', this.pp)
            });
            break;

        case 'msg_resume_draft':
            this.composeMailbox('resume');
            break;

        case 'msg_template':
            this.composeMailbox('template');
            break;

        case 'search_close':
            this.quicksearchClear();
            e.memo.stop();
            break;

        case 'helptext_close':
            this.toggleHelp();
            e.memo.stop();
            break;

        case 'send_mdn_link':
            this.loadingImg('msg', true);
            tmp = {};
            tmp[this.pp.mbox] = [ this.pp.uid ];
            DimpCore.doAction('sendMDN', {
                uid: DimpCore.toUIDString(tmp)
            }, {
                callback: this._sendMdnCallback.bind(this)
            });
            e.memo.stop();
            break;

        default:
            if (e.element().hasClassName('printAtc')) {
                HordeCore.popupWindow(DimpCore.conf.URI_VIEW, {
                    actionID: 'print_attach',
                    id: e.element().readAttribute('mimeid'),
                    mailbox: this.pp.mbox,
                    uid: this.pp.uid
                }, {
                    name: this.pp.uid + '|' + this.pp.mbox + '|print',
                    onload: IMP_JS.printWindow
                });
                e.memo.stop();
            } else if (e.element().hasClassName('stripAtc')) {
                if (window.confirm(DimpCore.text.strip_warn)) {
                    this.loadingImg('msg', true);
                    DimpCore.doAction('stripAttachment', this.addViewportParams({
                        id: e.element().readAttribute('mimeid')
                    }), {
                        callback: function(r) {
                            if (!this.pp) {
                                this.viewport.select(this.viewport.createSelectionBuffer().search({
                                    mbox: {
                                        equal: [ r.newmbox ]
                                    },
                                    uid: {
                                        equal: [ r.newuid ]
                                    }
                                }).get('rownum'));
                            }
                        }.bind(this),
                        uids: this.viewport.createSelection('dataob', this.pp)
                    });
                }
                e.memo.stop();
            }
        }
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

    toggleHelp: function()
    {
        Effect.toggle($('helptext').down('DIV'), 'blind', {
            duration: 0.75,
            queue: {
                position: 'end',
                scope: 'DimpHelp',
                limit: 2
            }
        });
    },

    _mailboxPromptCallback: function(type, elt, r)
    {
        switch (type) {
        case 'create':
            this._createMboxForm(elt, 'createsub', DimpCore.text.createsub_prompt.sub('%s', this.fullMboxDisplay(elt)));
            break;

        case 'delete':
            this.viewaction = function(e) {
                DimpCore.doAction('deleteMailbox', {
                    container: elt.hasClassName('container'),
                    mbox: elt.retrieve('mbox'),
                    subfolders: e.element().down('[name=delete_subfolders]').getValue()
                });
            };
            HordeDialog.display({
                form: new Element('DIV').insert(
                    new Element('INPUT', { name: 'delete_subfolders', type: 'checkbox' })
                ).insert(
                    DimpCore.text.delete_mbox_subfolders.sub('%s', this.fullMboxDisplay(elt))
                ),
                form_id: 'dimpbase_confirm',
                text: elt.hasClassName('container') ? null : DimpCore.text.delete_mbox.sub('%s', this.fullMboxDisplay(elt))
            });
            break;

        case 'empty':
            this.viewaction = function(e) {
                DimpCore.doAction('emptyMailbox', {
                    mbox: elt.retrieve('mbox')
                });
            };
            HordeDialog.display({
                form_id: 'dimpbase_confirm',
                noinput: true,
                text: DimpCore.text.empty_mbox.sub('%s', this.fullMboxDisplay(elt)).sub('%d', r)
            });
            break;

        case 'rename':
            this._createMboxForm(elt, 'rename', DimpCore.text.rename_prompt.sub('%s', this.fullMboxDisplay(elt)), elt.retrieve('l').unescapeHTML());
            break;
        }
    },

    /* Handle create mailbox actions. */
    _createMboxForm: function(mbox, mode, text, val)
    {
        this.viewaction = function(e) {
            this._mboxAction(e, mbox, mode);
        }.bind(this);

        HordeDialog.display({
            form_id: 'dimpbase_confirm',
            input_val: val,
            text: text
        });
    },

    _mboxAction: function(e, mbox, mode)
    {
        var action, params, tmp, val,
            form = e.findElement('form');
        val = $F(form.down('input'));

        if (val) {
            switch (mode) {
            case 'rename':
                if (mbox.retrieve('l') != val) {
                    action = 'renameMailbox';
                    params = {
                        old_name: mbox.retrieve('mbox'),
                        new_parent: mbox.up().hasClassName('mboxlist') ? '' : mbox.up(1).previous().retrieve('mbox'),
                        new_name: val
                    };
                }
                break;

            case 'create':
            case 'createsub':
                action = 'createMailbox';
                params = { mbox: val };
                if (mode == 'createsub') {
                    params.parent = mbox.retrieve('mbox');
                    tmp = this.getSubMboxElt(params.parent);
                    if (!tmp || !tmp.childElements().size()) {
                        params.noexpand = 1;
                    }
                }
                break;
            }

            if (action) {
                DimpCore.doAction(action, params);
            }
        }
    },

    /* Mailbox action callback functions. */
    mailboxCallback: function(r)
    {
        var base,
            nm = $('imp-normalmboxes');

        if (r.base) {
            // Need to find the submailbox and look to parent to ensure we get
            // the non-special container.
            base = this.getSubMboxElt(r.base).previous();
        }

        if (r.expand) {
            this.expandmbox = base ? base : true;
        }

        if (r.d) {
            r.d.each(this.deleteMbox.bind(this));
        }
        if (r.c) {
            r.c.each(this.changeMbox.bind(this));
        }
        if (r.a && !r.noexpand) {
            r.a.each(this.createMbox.bind(this));
        }

        this.expandmbox = false;

        if (base) {
            this._toggleSubFolder(base, r.all ? 'expall' : 'tog', false, true);
        }

        if (this.view) {
            this.highlightSidebar(this.view);
        }

        if ($('foldersLoading').visible()) {
            $('foldersLoading').hide();
            $('foldersSidebar').show();
        }

        if (nm && nm.getStyle('max-height') !== null) {
            this._sizeFolderlist();
        }
    },

    flagCallback: function(r)
    {
        r.each(function(entry) {
            $H(DimpCore.parseUIDString(entry.uids)).each(function(m) {
                var s = this.viewport.createSelectionBuffer(m.key).search({
                    uid: { equal: m.value },
                    mbox: { equal: [ m.key ] }
                });

                if (entry.add) {
                    entry.add.each(function(f) {
                        this.updateFlag(s, f, true);
                    }, this);
                }

                if (entry.remove) {
                    entry.remove.each(function(f) {
                        this.updateFlag(s, f, false);
                    }, this);
                }
            }, this);
        }, this);
    },

    _handleMboxMouseClick: function(e)
    {
        var elt = e.element(),
            li = elt.match('DIV') ? elt : elt.up('DIV.horde-subnavi');

        if (!li) {
            return;
        }

        if (elt.hasClassName('exp') || elt.hasClassName('col')) {
            this._toggleSubFolder(li, 'tog');
        } else {
            switch (li.retrieve('ftype')) {
            case 'container':
            case 'scontainer':
            case 'vcontainer':
                e.stop();
                break;

            case 'mbox':
            case 'special':
            case 'vfolder':
                e.stop();
                return this.go('mbox', li.retrieve('mbox'));
            }
        }
    },

    _toggleSubFolder: function(base, mode, noeffect, noexpand)
    {
        var collapse = [], expand = [], need = [], subs = [];

        if (mode == 'expall' || mode == 'colall') {
            if (base.hasClassName('subfolders')) {
                subs.push(base);
            }
            subs = subs.concat(base.select('.subfolders'));
        } else if (mode == 'exp') {
            // If we are explicitly expanding ('exp'), make sure all parent
            // subfolders are expanded.
            // The last 2 elements of ancestors() are the BODY and HTML tags -
            // don't need to parse through them.
            subs = base.ancestors().slice(0, -2).reverse().findAll(function(n) { return n.hasClassName('subfolders'); });
        } else {
            if (!base.hasClassName('horde-subnavi')) {
                base = base.up();
            }
            subs = [  base.next('.subfolders') ];
        }

        if (!subs) {
            return;
        }

        if (mode == 'tog' || mode == 'expall') {
            subs.compact().each(function(s) {
                if (!s.visible() && !s.childElements().size()) {
                    need.push(s.previous().retrieve('mbox'));
                }
            });

            if (need.size()) {
                if (mode == 'tog') {
                    //base.down('A').update(DimpCore.text.loading);
                }
                this._listMboxes({
                    all: Number(mode == 'expall'),
                    base: base,
                    mboxes: need
                });
                return;
            } else if (mode == 'tog') {
                // Need to pass element here, since we might be working
                // with 'special' mailboxes.
                this.setMboxLabel(base);
            }
        }

        subs.each(function(s) {
            if (mode == 'tog' ||
                ((mode == 'exp' || mode == 'expall') && !s.visible()) ||
                ((mode == 'col' || mode == 'colall') && s.visible())) {
                s.previous().down().toggleClassName('exp').toggleClassName('col');

                if (mode == 'col' ||
                    ((mode == 'tog') && s.visible())) {
                    collapse.push(s.previous().retrieve('mbox'));
                } else if (!noexpand &&
                           (mode == 'exp' ||
                            ((mode == 'tog') && !s.visible()))) {
                    expand.push(s.previous().retrieve('mbox'));
                }

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
        });

        if (DimpCore.conf.mbox_expand) {
            if (collapse.size()) {
                DimpCore.doAction('collapseMailboxes', { mboxes: Object.toJSON(collapse) });
            } else if (mode == 'colall') {
                DimpCore.doAction('collapseMailboxes', { all: 1 });
            } else if (expand.size()) {
                DimpCore.doAction('expandMailboxes', { mboxes: Object.toJSON(expand) });
            }
        }
    },

    _listMboxes: function(params)
    {
        params = params || {};
        params.unsub = Number(this.showunsub);
        if (!Object.isArray(params.mboxes)) {
            params.mboxes = [ params.mboxes ];
        }
        if (Object.isElement(params.base)) {
            params.base = params.base.retrieve('mbox');
        }
        params.mboxes = Object.toJSON(params.mboxes);

        DimpCore.doAction('listMailboxes', params);
    },

    // For format of the ob object, see
    // IMP_Ajax_Application#_createMailboxElt().
    // If this.expandmbox is set, expand folder list on initial display.
    createMbox: function(ob)
    {
        var div, f_node, ftype, li, ll, parent_e, tmp, tmp2,
            cname = 'container',
            label = ob.l || ob.m,
            title = ob.t || ob.m;

        if (this.mboxes[ob.m]) {
            return;
        }

        if (ob.v) {
            ftype = ob.co ? 'vcontainer' : 'vfolder';
            title = label;
        } else if (ob.co) {
            if (ob.n) {
                ftype = 'scontainer';
                title = label;
            } else {
                ftype = 'container';
            }

            /* This is a dummy container element to display child elements of
             * a mailbox displayed in the 'imp-specialmboxes' section. */
            if (ob.dummy) {
                cname += ' specialContainer';
            }
        } else {
            cname = 'mbox';
            ftype = ob.s ? 'special' : 'mbox';
        }

        if (ob.un && this.showunsub) {
            cname += ' unsubMbox';
        }

        //div = new Element('SPAN', { className: 'iconImgSidebar' });
        div = new Element('DIV', { className: 'horde-subnavi-icon-1' });
        if (ob.i) {
            div.setStyle({ backgroundImage: 'url("' + ob.i + '")' });
        }

        //li = new Element('LI', { className: cname, title: title }).store('l', label).store('mbox', ob.m).insert(div).insert(new Element('A').insert(label));
        li = new Element('DIV', { className: 'horde-subnavi', title: title })
            .store('l', label)
            .store('mbox', ob.m)
            .insert(div)
            .insert(new Element('DIV', { className: 'horde-subnavi-point' })
                        .insert(new Element('A').insert(label)))
            .insert(new Element('DIV', { className: 'clear' }));

        // Now walk through the parent <ul> to find the right place to
        // insert the new mailbox.
        if (ob.s) {
            div.addClassName(ob.cl || 'folderImg');
            parent_e = $('imp-specialmboxes');

            /* Create a dummy container element in 'imp-normalmboxes' section. */
            if (ob.ch) {
                div.removeClassName('exp').addClassName(ob.cl || 'folderImg');

                tmp = Object.clone(ob);
                tmp.co = tmp.dummy = true;
                tmp.s = false;
                this.createMbox(tmp);
            }
        } else {
            div.addClassName(ob.ch ? 'exp' : (ob.cl || 'folderImg'));
            parent_e = ob.pa
                ? this.getSubMboxElt(ob.pa)
                : $('imp-normalmboxes');
        }

        /* Virtual folders and special mailboxes are sorted on the server. */
        if (!ob.v && !ob.s) {
            ll = label.toLowerCase();
            f_node = parent_e.childElements().find(function(node) {
                var l = node.retrieve('l');
                return (l && (ll < l.toLowerCase()));
            });
        }

        if (f_node) {
            f_node.insert({ before: li });
        } else {
            parent_e.insert(li);
            if (this.expandmbox &&
                !parent_e.hasClassName('horde-sidebar-folder') &&
                !parent_e.hasClassName('horde-sidebar-subfolder')) {
                tmp2 = parent_e.previous();
                if (!Object.isElement(this.expandmbox) ||
                    this.expandmbox != tmp2) {
                    tmp2.next().show();
                    tmp2.down().removeClassName('exp').addClassName('col');
                }
            }
        }

        // Make sure the sub<mbox> ul is created if necessary.
        if (!ob.s && ob.ch) {
            //li.insert({ after: new Element('LI', { className: 'subfolders' }).insert(new Element('UL')).hide() });
            li.insert({ after: new Element('DIV', { className: 'subfolders' }).hide() });
            if (tmp) {
                li.insert({ after: tmp });
            }
        }

        li.store('ftype', ftype);

        // Make the new mailbox a drop target.
        if (!ob.v) {
            new Drop(li, this._mboxDropConfig);
        }

        // Check for unseen messages
        if (ob.po) {
            li.store('u', '');
        }

        switch (ftype) {
        case 'special':
            // For purposes of the contextmenu, treat special mailboxes
            // like regular mailboxes.
            ftype = 'mbox';
            // Fall through.

        case 'container':
        case 'mbox':
            new Drag(li, this._mboxDragConfig);
            break;

        case 'scontainer':
            ftype = 'noactions';
            break;

        case 'vfolder':
            if (ob.v == 1) {
                ftype = 'noactions';
            }
            break;
        }

        DimpCore.addContextMenu({
            elt: li,
            type: ftype
        });

        this.mboxes[ob.m] = li;
        if (ob.dummy) {
            this.smboxes[ob.m] = li;
        }
    },

    deleteMbox: function(mbox)
    {
        if (this.view == mbox) {
            this.go('mbox', this.INBOX);
        }
        this.deleteMboxElt(mbox, true);
    },

    changeMbox: function(ob)
    {
        var tmp;

        if (this.smboxes[ob.m]) {
            // The case of children being added to a special mailbox is
            // handled by createMbox().
            if (!ob.ch) {
                this.deleteMboxElt(ob.m, true);
            }
            return;
        }

        tmp = this.getMboxElt(ob.m).down('SPAN');

        this.deleteMboxElt(ob.m, !ob.ch);
        if (ob.co && this.view == ob.m) {
            this.go();
        }
        this.createMbox(ob);
        if (ob.ch && tmp && tmp.hasClassName('col')) {
            this.getMboxElt(ob.m).down('SPAN').removeClassName('exp').addClassName('col');
        }
    },

    // m: (string) Mailbox ID
    deleteMboxElt: function(m, sub)
    {
        var m_elt = this.getMboxElt(m), submbox;
        if (!m_elt) {
            return;
        }

        if (sub &&
            (submbox = this.getSubMboxElt(m_elt))) {
            delete this.smboxes[submbox.retrieve('mbox')];
            submbox.remove();
        }
        [ DragDrop.Drags.getDrag(m), DragDrop.Drops.getDrop(m) ].compact().invoke('destroy');
        this._removeMouseEvents(m_elt);
        if (this.viewport) {
            this.viewport.deleteView(m_elt.retrieve('mbox'));
        }
        delete this.mboxes[m_elt.retrieve('mbox')];
        m_elt.remove();
    },

    _sizeFolderlist: function()
    {
        var nf = $('imp-normalmboxes');
        if (nf) {
            nf.setStyle({ height: Math.max(document.viewport.getHeight() - nf.cumulativeOffset()[1], 0) + 'px' });
        }
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

        [ Object.values(this.mboxes), Object.values(this.smboxes) ].flatten().compact().each(function(elt) {
            this.deleteMboxElt(elt, true);
        }, this);

        this._listMboxes({ reload: 1, mboxes: this.view });
    },

    subscribeMbox: function(m, sub)
    {
        var m_elt = this.getMboxElt(m);
        DimpCore.doAction('subscribe', { mbox: m, sub: Number(sub) });

        if (this.showunsub) {
            [ m_elt ].invoke(sub ? 'removeClassName' : 'addClassName', 'unsubMbox');
        } else if (!sub) {
            if (!this.showunsub &&
                !m_elt.siblings().size() &&
                m_elt.up('LI.subfolders')) {
                m_elt.up('LI').previous().down('SPAN.iconImgSidebar').removeClassName('exp').removeClassName('col').addClassName('folderImg');
            }
            this.deleteMboxElt(m);
        }
    },

    /* Flag actions for message list. */
    _getFlagSelection: function(opts)
    {
        var vs;

        if (opts.vs) {
            vs = opts.vs;
        } else if (opts.uid) {
            vs = opts.mailbox
                ? this.viewport.createSelectionBuffer().search({ uid: { equal: [ opts.uid ] }, mbox: { equal: [ opts.mailbox ] } })
                : this.viewport.createSelection('dataob', opts.uid);
        } else {
            vs = this.viewport.getSelected();
        }

        return vs;
    },

    // type = (string) AJAX action type
    // opts = (Object) callback, mailbox, uid
    // args = (Object) Parameters to pass to AJAX call
    _doMsgAction: function(type, opts, args)
    {
        var vs = this._getFlagSelection(opts);

        if (vs.size()) {
            // This needs to be synchronous Ajax if we are calling from a
            // popup window because Mozilla will not correctly call the
            // callback function if the calling window has been closed.
            DimpCore.doAction(type, this.addViewportParams(args), {
                ajaxopts: { asynchronous: !(opts.uid && opts.mailbox) },
                callback: opts.callback,
                uids: vs
            });
            return vs;
        }

        return false;
    },

    // spam = (boolean) True for spam, false for innocent
    // opts = 'mailbox', 'uid'
    reportSpam: function(spam, opts)
    {
        opts = opts || {};
        opts.callback = this.loadingImg.bind(this, 'viewport', false);

        if (this._doMsgAction('reportSpam', opts, { spam: Number(spam) })) {
            // Indicate to the user that something is happening (since spam
            // reporting may not be instantaneous).
            this.loadingImg('viewport', true);
        }
    },

    // blacklist = (boolean) True for blacklist, false for whitelist
    // opts = 'mailbox', 'uid'
    blacklist: function(blacklist, opts)
    {
        opts = opts || {};
        this._doMsgAction('blacklist', opts, { blacklist: Number(blacklist) });
    },

    // opts = 'mailbox', 'uid'
    deleteMsg: function(opts)
    {
        opts = opts || {};
        opts.vs = this._getFlagSelection(opts);

        this._doMsgAction('deleteMessages', opts, {});
        this.updateFlag(opts.vs, DimpCore.conf.FLAG_DELETED, true);
    },

    // flag = (string) IMAP flag name
    // add = (boolean) True to add flag
    // opts = (Object) 'mailbox', 'params', 'uid'
    flag: function(flag, add, opts)
    {
        opts = opts || {};

        var need,
            params = $H(opts.params),
            vs = this._getFlagSelection(opts);

        need = vs.get('dataob').any(function(ob) {
            return add
                ? (!ob.flag || !ob.flag.include(flag))
                : (ob.flag && ob.flag.include(flag));
        });

        if (need) {
            DimpCore.doAction('flagMessages', this.addViewportParams(params.merge({
                add: Number(add),
                flags: Object.toJSON([ flag ])
            })), {
                uids: vs
            });
        }
    },

    updateFlag: function(vs, flag, add)
    {
        var s = {};

        vs.get('dataob').each(function(ob) {
            this._updateFlag(ob, flag, add);

            if (this.isSearch()) {
                if (!s[ob.mbox]) {
                    s[ob.mbox] = [];
                }
                s[ob.mbox].push(ob.uid);
            }
        }, this);

        /* If this is a search mailbox, also need to update flag in base view,
         * if it is in the buffer. */
        $H(s).each(function(m) {
            var tmp = this.viewport.createSelectionBuffer(m.key).search({ uid: { equal: m.value }, mbox: { equal: [ m.key ] } });
            if (tmp.size()) {
                this._updateFlag(tmp.get('dataob').first(), flag, add);
            }
        }, this);
    },

    _updateFlag: function(ob, flag, add)
    {
        var hasflag;

        if (!ob.flag) {
            ob.flag = [];
        } else {
            hasflag = ob.flag.include(flag);
        }

        if (add && !hasflag) {
            ob.flag.push(flag);
            this.viewport.updateRow(ob);
        } else if (!add && hasflag) {
            ob.flag = ob.flag.without(flag);
            this.viewport.updateRow(ob);
        }
    },

    isDraft: function(vs)
    {
        return this.viewport.getMetaData('drafts') ||
               vs.get('dataob').first().flag.include(DimpCore.conf.FLAG_DRAFT);
    },

    /* Miscellaneous mailbox actions. */

    purgeDeleted: function()
    {
        DimpCore.doAction('purgeDeleted', this.addViewportParams());
    },

    modifyPoll: function(mbox, add)
    {
        DimpCore.doAction('modifyPoll', {
            add: Number(add),
            mbox: mbox
        }, {
            callback: this._modifyPollCallback.bind(this)
        });
    },

    _modifyPollCallback: function(r)
    {
        if (r.add) {
            this.getMboxElt(r.mbox).store('u', 0);
        } else {
            this.getMboxElt(r.mbox).store('u', undefined);
            this.updateUnseenStatus(r.mbox, 0);
        }
    },

    loadingImg: function(id, show)
    {
        HordeCore.loadingImg(
            id + 'Loading',
            (id == 'viewport') ? $('msgSplitPane').down('DIV.msglist') : 'previewPane',
            show
        );
    },

    // p = (element) Parent element
    // c = (element) Child element
    isSubfolder: function(p, c)
    {
        var sf = this.getSubMboxElt(p);
        return sf && c.descendantOf(sf);
    },

    /* Internal preferences. */

    _getPref: function(k)
    {
        return $.jStorage.get(k, this.prefs[k] ? this.prefs[k] : this.prefs_special(k));
    },

    _setPref: function(k, v)
    {
        if (v === null) {
            $.jStorage.deleteKey(k);
        } else {
            $.jStorage.set(k, v);
        }
    },

    /* AJAX tasks handler. */
    tasksHandler: function(t)
    {
        if (this.viewport && t['imp:viewport']) {
            this.viewport.parseJSONResponse(t['imp:viewport']);
        }

        if (t['imp:mailbox']) {
            this.mailboxCallback(t['imp:mailbox']);
        }

        if (t['imp:flag']) {
            this.flagCallback(t['imp:flag']);
        }

        if (t['imp:message']) {
            this.messageCallback(t['imp:message']);
        }

        if (t['imp:maillog']) {
            this.maillogCallback(t['imp:maillog']);
        }

        if (t['imp:poll']) {
            this.pollCallback(t['imp:poll']);
        }

        if (t['imp:quota']) {
            this.quotaCallback(t['imp:quota']);
        }
    },

    /* Onload function. */
    onDomLoad: function()
    {
        var DM = DimpCore.DMenu, tmp;

        /* Register global handlers now. */
        IMP_JS.keydownhandler = this.keydownHandler.bind(this);
        HordeCore.initHandler('click');
        HordeCore.initHandler('dblclick');

        /* Initialize variables. */
        DimpCore.conf.sort = $H(DimpCore.conf.sort);

        /* Limit to folders sidebar only. */
        $('foldersSidebar').observe('mouseover', this.mouseoverHandler.bindAsEventListener(this));

        /* Create splitbar for sidebar. */
        this.splitbar = $('horde-slideleft');
        new Drag(this.splitbar, {
            constraint: 'horizontal',
            ghosting: true,
            nodrop: true
        });

        /* Show page now. */
        $('dimpLoading').hide();
        $('horde-page').show();
        this.setSidebarWidth();

        /* Init quicksearch. These needs to occur before loading the message
         * list since it may be disabled if we are in a search mailbox. */
        if ($('horde-search')) {
            this._setQsearchText();
            this.qsearch_ghost = new FormGhost('horde-search-input');

            DimpCore.addContextMenu({
                elt: $('horde-search-dropdown'),
                left: true,
                offset: $$('#horde-search .horde-fake-input')[0],
                type: 'qsearchopts'
            });
            DimpCore.addContextMenu({
                elt: $('horde-search-dropdown'),
                left: false,
                offset: $$('#horde-search .horde-fake-input')[0],
                type: 'qsearchopts'
            });
            DM.addSubMenu('ctx_qsearchopts_by', 'ctx_qsearchby');

            DimpCore.addPopdownButton('button_filter', 'filteropts', {
                trigger: true
            });
            DM.addSubMenu('ctx_filteropts_filter', 'ctx_filter');
            DM.addSubMenu('ctx_filteropts_flag', 'ctx_flag_search');
            DM.addSubMenu('ctx_filteropts_flagnot', 'ctx_flag_search');

            /* Don't submit FORM. Really only needed for Opera (Bug #9730)
             * but shouldn't hurt otherwise. */
            $('horde-search-input').up('FORM').observe('submit', Event.stop);
        }

        /* Store these text strings for updating purposes. */
        //DimpCore.text.getmail = $('checkmaillink').down('A').innerHTML;
        //DimpCore.text.showalog = $('alertsloglink').down('A').innerHTML;

        /* Initialize the starting page. */
        tmp = decodeURIComponent(location.hash);
        if (!tmp.empty() && tmp.startsWith('#')) {
            tmp = (tmp.length == 1) ? "" : tmp.substring(1);
        }

        if (!tmp.empty()) {
            tmp = tmp.split(':', 2);
            this.go(tmp[0], tmp[1]);
        } else if (DimpCore.conf.initial_page) {
            this.go('mbox', DimpCore.conf.initial_page);
        } else {
            this.go();
        }

        /* Create the folder list. Any pending notifications will be caught
         * via the return from this call. */
        this._listMboxes({ initial: 1, mboxes: this.view });

        /* Add popdown menus. */
        DimpCore.addPopdownButton('button_template', 'template');
        DimpCore.addPopdownButton('button_other', 'oa', {
            trigger: true
        });
        DimpCore.addPopdown('folderopts_link', 'folderopts', {
            trigger: true
        });
        DimpCore.addPopdown('vertical_sort', 'sortopts', {
            trigger: true
        });

        DM.addSubMenu('ctx_message_reply', 'ctx_reply');
        DM.addSubMenu('ctx_message_forward', 'ctx_forward');
        DM.addSubMenu('ctx_message_setflag', 'ctx_flag');
        DM.addSubMenu('ctx_message_unsetflag', 'ctx_flag');
        DM.addSubMenu('ctx_oa_setflag', 'ctx_flag');
        DM.addSubMenu('ctx_oa_unsetflag', 'ctx_flag');
        DM.addSubMenu('ctx_mbox_setflag', 'ctx_mbox_flag');
        DM.addSubMenu('ctx_mbox_export', 'ctx_mbox_exportopts');

        DimpCore.addPopdown($('msglistHeaderHoriz').down('.msgSubject').identify(), 'subjectsort', {
            insert: 'bottom'
        });
        DimpCore.addPopdown($('msglistHeaderHoriz').down('.msgDate').identify(), 'datesort', {
            insert: 'bottom'
        });

        DimpCore.addPopdown($('preview_other_opts').down('A'), 'preview', {
            trigger: true
        });

        if (DimpCore.conf.disable_compose) {
            $('button_reply', 'button_forward').compact().invoke('up').invoke('up').concat($('button_compose', 'horde-new-link', 'ctx_contacts_new')).compact().invoke('remove');
        } else {
            DimpCore.addPopdownButton('button_reply', 'reply');
            DimpCore.addPopdownButton('button_forward', 'forward');
        }

        new Drop('dropbase', this._mboxDropConfig);

        if (this._getPref('toggle_hdrs')) {
            this._toggleHeaders($('th_expand'));
        }

        if (!$('GrowlerLog')) {
            $('alertsloglink').remove();
        }

        /* Check for new mail. */
        this.setPoll();
    },

    /* Resize function. */
    onResize: function()
    {
        if (this.resize) {
            clearTimeout(this.resize);
        }

        this.resize = this._onResize.bind(this).delay(0.1);
    },

    _onResize: function()
    {
        this._sizeFolderlist();
        this.splitbar.setStyle({ height: document.viewport.getHeight() + 'px' });
    },

    /* AJAX exception handling. */
    onAjaxException: function()
    {
        /* Make sure loading images are closed. */
        this.loadingImg('msg', false);
        this.loadingImg('viewport', false);
        HordeCore.notify(HordeCore.text.ajax_error, 'horde.error');
    }

};

/* Need to add after DimpBase is defined. */
DimpBase._msgDragConfig = {
    classname: 'msgdrag',
    scroll: 'imp-normalmboxes',
    threshold: 5,
    caption: DimpBase.dragCaption.bind(DimpBase)
};

DimpBase._mboxDragConfig = {
    classname: 'mboxdrag',
    ghosting: true,
    offset: { x: 15, y: 0 },
    scroll: 'imp-normalmboxes',
    threshold: 5
};

DimpBase._mboxDropConfig = {
    caption: function(drop, drag, e) {
        var m,
            d = drag.retrieve('l'),
            ftype = drop.retrieve('ftype'),
            l = drop.retrieve('l');

        if (drop == $('dropbase')) {
            return DimpCore.text.moveto.sub('%s', d).sub('%s', DimpCore.text.baselevel);
        }

        switch (e.type) {
        case 'mousemove':
            m = (e.ctrlKey) ? DimpCore.text.copyto : DimpCore.text.moveto;
            break;

        case 'keydown':
            /* Can't use ctrlKey here since different browsers handle the
             * ctrlKey in different ways when it comes to firing keyboard
             * events. */
            m = (e.keyCode == 17) ? DimpCore.text.copyto : DimpCore.text.moveto;
            break;

        case 'keyup':
            m = (e.keyCode == 17)
                ? DimpCore.text.moveto
                : (e.ctrlKey) ? DimpCore.text.copyto : DimpCore.text.moveto;
            break;
        }

        if (drag.hasClassName('mbox')) {
            return (ftype != 'special' && !DimpBase.isSubfolder(drag, drop)) ? m.sub('%s', d).sub('%s', l) : '';
        }

        return ftype != 'container' ? m.sub('%s', DimpBase.dragCaption()).sub('%s', l) : '';
    },
    keypress: true
};

/* Basic event handlers. */
document.observe('keydown', DimpBase.keydownHandler.bindAsEventListener(DimpBase));
Event.observe(window, 'resize', DimpBase.onResize.bind(DimpBase));

/* Drag/drop listeners. */
document.observe('DragDrop2:start', DimpBase.onDragStart.bindAsEventListener(DimpBase));
document.observe('DragDrop2:drop', DimpBase.mboxDropHandler.bindAsEventListener(DimpBase));
document.observe('DragDrop2:end', DimpBase.onDragEnd.bindAsEventListener(DimpBase));
document.observe('DragDrop2:mousedown', DimpBase.onDragMouseDown.bindAsEventListener(DimpBase));
document.observe('DragDrop2:mouseup', DimpBase.onDragMouseUp.bindAsEventListener(DimpBase));

/* HordeDialog listener. */
document.observe('HordeDialog:onClick', function(e) {
    switch (e.element().identify()) {
    case 'dimpbase_confirm':
        this.viewaction(e);
        HordeDialog.close();
        break;

    case 'mbox_import':
        HordeCore.submit(e.element(), {
            callback: function(r) {
                if (r.action == 'importMailbox') {
                    this.viewport.reload();
                }
            }.bind(this)
        });
        HordeDialog.close();
        break;
    }
}.bindAsEventListener(DimpBase));

/* AJAX related events. */
document.observe('HordeCore:ajaxException', DimpBase.onAjaxException.bind(DimpBase));
document.observe('HordeCore:runTasks', function(e) {
    this.tasksHandler(e.memo);
}.bindAsEventListener(DimpBase));

/* Click handlers. */
document.observe('HordeCore:click', DimpBase.clickHandler.bindAsEventListener(DimpBase));
document.observe('HordeCore:dblclick', DimpBase.dblclickHandler.bindAsEventListener(DimpBase));

/* ContextSensitive handlers. */
document.observe('ContextSensitive:click', DimpBase.contextOnClick.bindAsEventListener(DimpBase));
document.observe('ContextSensitive:show', DimpBase.contextOnShow.bindAsEventListener(DimpBase));
document.observe('ContextSensitive:trigger', DimpBase.contextOnTrigger.bindAsEventListener(DimpBase));

/* Initialize onload handler. */
document.observe('dom:loaded', DimpBase.onDomLoad.bind(DimpBase));

/* DimpCore handlers. */
document.observe('DimpCore:updateAddressHeader', DimpBase.updateAddressHeader.bindAsEventListener(DimpBase));

/* Define reloadMessage() method for this page. */
DimpCore.reloadMessage = function(params) {
    DimpBase.loadPreview(null, params);
};

/* Growler handlers. */
document.observe('Growler:toggled', function(e) {
    $('alertsloglink').down('A').update(e.memo.visible ? DimpCore.text.hidealog : DimpCore.text.showalog);
});
