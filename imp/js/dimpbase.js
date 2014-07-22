/**
 * Base dynamic page.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2005-2014 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */

var DimpBase = {

    // colorpicker,
    flags: {},
    flags_o: [],
    // flist,
    INBOX: 'SU5CT1g', // 'INBOX' base64url encoded
    // init,
    mboxDragConfig: {
        classname: 'mboxdrag',
        ghosting: true,
        offset: { x: 15, y: 0 },
        scroll: 'imp-normalmboxes',
        threshold: 5
    },
    mboxDropConfig: (function() {
        return {
            caption: function(drop, drag, e) {
                return DimpBase.mboxDropCaption(drop, drag, e);
            },
            keypress: true
        };
    })(),
    msgDragConfig: (function() {
        return {
            caption: function() {
                return DimpBase.messageCountText(DimpBase.selectedCount());
            },
            classname: 'msgdrag',
            rightclick: true,
            scroll: 'imp-normalmboxes',
            threshold: 5
        };
    })(),
    // pollPE,
    // pp,
    ppcache: {},
    ppfifo: [],
    // resize,
    // rownum,
    // search,
    // searchbar_time,
    // searchbar_time_mins,
    // splitbar,
    // showunsub,
    // sort_init,
    // template,
    // uid,
    // view,
    // viewaction,
    // viewport,
    // viewswitch,

    // Preview pane cache size is 20 entries. Given that a reasonable guess
    // of an average e-mail size is 10 KB (including headers), also make
    // an estimate that the JSON data size will be approx. 10 KB. 200 KB
    // should be a fairly safe caching value for any recent browser.
    ppcachesize: 20,

    // Message selection functions

    // id = (string) DOM ID
    // opts = (Object) Boolean options [ctrl, right, shift]
    msgSelect: function(id, opts)
    {
        var bounds,
            row = this.viewport.createSelection('domid', id),
            sel = this.isSelected('domid', id),
            selcount = this.selectedCount();

        this.viewport.setMetaData({
            curr_row: row,
            last_row: (selcount ? this.viewport.getSelected() : null)
        });

        this.resetSelectAll();

        if (opts.shift) {
            if (selcount) {
                if (!sel || selcount != 1) {
                    bounds = [ row.get('rownum').first(), this.viewport.getMetaData('pivot_row').get('rownum').first() ];
                    this.viewport.select($A($R(bounds.min(), bounds.max())));
                }
                return;
            }
        } else if (opts.ctrl) {
            this.viewport.setMetaData({ pivot_row: row });
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

    resetSelected: function(noviewport)
    {
        if (!noviewport && this.viewport) {
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
            if (num === 0) {
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
            if (!curr_row || row_data.VP_id != curr_row.VP_id) {
                this.viewport.scrollTo(row_data.VP_rownum, { bottom: bottom });
                this.viewport.select(row, { delay: 0.5 });
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
    //     'msg' - (string) Mailbox [;] Compressed UID string
    //     'prefs' - (object) Extra parameters to add to prefs URL
    //     'search' - (object)
    //         'edit_query' = If 1, mailbox will be edited
    //         'mailbox' = mailboxes to search
    //         'subfolder' = do subfolder search
    //         If not set, loads search screen with current mailbox as
    //         default search mailbox
    go: function(type, data)
    {
        var tmp;

        if (!type) {
            type = 'mbox';
        }

        if (type == 'compose') {
            return;
        }

        if (type == 'msg') {
            type = 'mbox';
            tmp = data.split(';');
            data = tmp[0];
            this.uid = tmp[1].parseViewportUidString().first();
            // Fall through to the 'mbox' check below.
        }

        if (type == 'mbox' || Object.isUndefined(this.view)) {
            if (Object.isUndefined(data) || data.empty()) {
                data = Object.isUndefined(this.view)
                    ? this.INBOX
                    : this.view;
            }

            if (this.view != data || !$('dimpmain_folder').visible()) {
                this.highlightSidebar(data);
                if ($('dimpmain_iframe').visible()) {
                    tmp = $('dimpmain_iframe').hide().down();
                    tmp.blur();
                }
                $('dimpmain_folder').show();
            }

            this.loadMailbox(data);

            if (Object.isElement(tmp)) {
                tmp.remove();
            }

            if (type == 'mbox') {
                return;
            }
        }

        $('dimpmain_folder').hide();
        $('dimpmain_iframe').show();

        switch (type) {
        case 'search':
            if (!data) {
                data = { mailbox: this.view };
            } else if (Object.isString(data) && data.isJSON()) {
                data = data.evalJSON(true);
            }

            if (Object.isString(data)) {
                data = { mailbox: data };
            }

            this.highlightSidebar();
            this.setTitle(DimpCore.text.search);
            $('dimpmain_iframe').insert(
                new Element('IFRAME', {
                    src: HordeCore.addURLParam(DimpCore.conf.URI_SEARCH, data)
                }).setStyle({
                    height: $('horde-page').getHeight() + 'px'
                })
            );
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
        var vs = this.viewport.getSelection(),
            view = vs.getBuffer().getView();

        if (this.isQSearch()) {
            // Quicksearch is not saved after page reload.
            this.setHash('mbox', this.search.mbox);
        } else if (vs.size()) {
            this.setHash('msg', view + ';' + vs.get('uid').toViewportUidString());
        } else {
            this.setHash('mbox', view);
        }
    },

    setTitle: function(title, unseen)
    {
        var opts = {};

        document.title = DimpCore.conf.name + ' :: ' + title;

        if (unseen > 99 & unseen < 1000) {
            opts.font = '8px Arial';
            opts.offset = 1;
            opts.width = 1;
        }

        Tinycon.setOptions(opts);
        Tinycon.setBubble(unseen);
    },

    // id: (string) Either the ID of a sidebar element, or the name of a
    //     mailbox
    highlightSidebar: function(id)
    {
        // Folder bar may not be fully loaded yet.
        if ($('foldersLoading').visible()) {
            this.highlightSidebar.bind(this, id).delay(0.1);
        } else {
            this.flist.highlight(id);
        }
    },

    setSidebarWidth: function()
    {
        var tmp = $('horde-sidebar');

        tmp.setStyle({
            width: DimpCore.getPref('splitbar_side') + 'px'
        });
        this.splitbar.setStyle({
            left: tmp.clientWidth + 'px'
        });
        $('horde-page').setStyle({
            left: (tmp.clientWidth) + 'px'
        });
    },

    // r = ViewPort row data
    msgWindow: function(r)
    {
        HordeCore.popupWindow(DimpCore.conf.URI_MESSAGE, {
            buid: r.VP_id,
            mailbox: r.VP_view
        }, {
            name: 'msgview' + r.VP_view + r.VP_id
        });
    },

    composeMailbox: function(type)
    {
        var sel = this.viewport.getSelected();
        if (sel.size()) {
            DimpCore.compose(type, {
                buid: sel.get('uid').toViewportUidString(),
                mailbox: this.view
            });
        }
    },

    loadMailbox: function(f)
    {
        var need_delete,
            opts = {};

        if (!this.viewport) {
            this._createViewPort();
        }

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

            if (!this.viewport.bufferLoaded(f)) {
                this.resetSelected(true);
            }

            this.view = f;
        }

        if (this.uid) {
            opts.search = { buid: this.uid };
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
            horiz: new Template(DimpCore.conf.msglist_template_horiz),
            vert: new Template(DimpCore.conf.msglist_template_vert)
        };

        this.viewport = new ViewPort({
            // Mandatory config
            ajax: function(params) {
                /* Store the requestid locally, so we don't need to
                 * round-trip to the server. We'll re-add it later. */
                var action, r_id = params.unset('requestid');
                if (this.init) {
                    action = 'viewPort';
                } else {
                    action = 'dynamicInit';
                    if (this.uid) {
                        params.set('msgload', this.uid);
                    }
                    this.init = true;
                }
                DimpCore.doAction(action, params, {
                    loading: 'viewport'
                }).rid = r_id;
            }.bind(this),
            container: container,
            onContent: function(r, mode) {
                var bg, escapeAttr, u,
                    thread = $H(this.viewport.getMetaData('thread')),
                    tsort = this.isThreadSort();

                escapeAttr = function(s) {
                    return s.escapeHTML().replace(/"/g, '&quot;');
                };
                r.status = r.subjectdata = '';

                // Add thread graphics
                if (tsort && mode != 'vert') {
                    u = thread.get(r.VP_id);
                    if (u) {
                        $R(0, u.length, true).each(function(i) {
                            r.subjectdata += '<span class="horde-tree-image horde-tree-image-' + u.charAt(i) + '"></span>';
                        });
                    }
                }

                /* Generate the subject/status flags. */
                if (r.flag && this.flags_o.size()) {
                    r.flag.each(function(a) {
                        var ptr = this.flags[a];
                        if (ptr.u) {
                            if (!ptr.elt) {
                                ptr.elt = '<span class="' + escapeAttr(ptr.c) + '" title="' + escapeAttr(ptr.l) + '" style="' + ((ptr.b) ? ('background:' + escapeAttr(ptr.b) + ';') : '') + ';color:' + escapeAttr(ptr.f) + '">' + ptr.l.truncate(10).escapeHTML() + '</span>';
                            }
                            r.subjectdata += ptr.elt;
                        } else {
                            if (ptr.c) {
                                if (!ptr.elt) {
                                    ptr.elt = '<div class="iconImg msgflags ' + escapeAttr(ptr.c) + '" title="' + escapeAttr(ptr.l) + '"></div>';
                                }
                                r.status += ptr.elt;
                                r.VP_bg.push(ptr.c);
                            }

                            if (ptr.b) {
                                bg = ptr.b;
                            }
                        }
                    }, this);
                }

                // Set bg
                if (bg) {
                    r.style = 'background:' + bg;
                }

                [ 'date', 'from', 'size', 'subject' ].each(function(h) {
                    if (r[h] === null) {
                        // If these fields are null, invalid string was
                        // scrubbed by JSON encode.
                        switch (h) {
                        case 'from':
                            r.from = '[' + DimpCore.text.badaddr + ']';
                            break;

                        case 'subject':
                            r.subject = r.subjecttitle = '[' + DimpCore.text.badsubject + ']';
                            break;
                        }
                    } else if (!Object.isUndefined(r[h])) {
                        switch (h) {
                        case 'from':
                            r.fromtitle = escapeAttr(r.fromaddr || r.from);
                            r.from = r.from.escapeHTML();
                            if (DimpCore.conf.from_link) {
                                r.from = '<a>' + r.from + '</a>';
                            }
                            break;

                        case 'subject':
                            r.subjecttitle = escapeAttr(r[h]);
                            r.subject = r.subject.escapeHTML();
                            break;

                        default:
                            r[h] = r[h].escapeHTML();
                            break;
                        }

                        if (this.isQSearch() &&
                            DimpCore.getPref('qsearch_field') == h) {
                            r[h] = r[h].gsub(
                                new RegExp("(" + $F('horde-search-input').escapeHTML() + ")", "i"),
                                '<span class="qsearchMatch">#{1}</span>'
                            );
                        }
                    }
                }, this);

                r.VP_bg.push('vpRow');

                switch (mode) {
                case 'vert':
                    r.VP_bg.unshift('vpRowVert');
                    r.className = r.VP_bg.join(' ');
                    return this.template.vert.evaluate(r);
                }

                r.VP_bg.unshift('vpRowHoriz');
                r.className = r.VP_bg.join(' ');

                return this.template.horiz.evaluate(r);
            }.bind(this),

            // Optional config
            empty_msg: this.emptyMsg.bind(this),
            list_class: 'msglist',
            list_header: $('msglistHeaderContainer').remove(),
            page_size: DimpCore.getPref('splitbar_horiz'),
            pane_data: 'previewPane',
            pane_mode: DimpCore.getPref('preview'),
            pane_width: DimpCore.getPref('splitbar_vert'),
            split_bar_class: {
                horiz: 'horde-splitbar-horiz',
                vert: 'horde-splitbar-vert'
            },
            split_bar_handle_class: {
                horiz: 'horde-splitbar-horiz-handle',
                vert: 'horde-splitbar-vert-handle'
            },

            // Callbacks
            onAjaxRequest: function(params) {
                var r_id = params.unset('requestid'),
                    view = params.get('view');

                if (this.viewswitch &&
                    (this.isQSearch(view) || this.isFSearch(view))) {
                    params.update({
                        qsearchfield: DimpCore.getPref('qsearch_field'),
                        qsearchmbox: this.search.mbox
                    });
                    if (this.search.filter) {
                        params.set('qsearchfilter', this.search.filter);
                    } else if (this.search.flag) {
                        params.update({
                            qsearchflag: this.search.flag,
                            qsearchflagnot: ~~(!!this.search.not)
                        });
                    } else {
                        params.set('qsearch', $F('horde-search-input'));
                    }
                }

                params = $H({
                    viewport: Object.toJSON(params),
                    view: view
                });
                if (r_id) {
                    params.set('requestid', r_id);
                }
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
            }.bind(this)
        });

        /* Custom ViewPort events. */
        container.observe('ViewPort:add', function(e) {
            DimpCore.addContextMenu({
                elt: e.memo,
                type: 'message'
            });
            new Drag(e.memo, this.msgDragConfig);
        }.bindAsEventListener(this));

        container.observe('ViewPort:clear', function(e) {
            $(e.memo).fire('DimpBase:removeElt');
        }.bindAsEventListener(this));

        container.observe('ViewPort:contentComplete', function() {
            var ssc, tmp;

            this.setMessageListTitle();
            this.setMsgHash();

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
                    $('search_label').writeAttribute({ title: tmp });
                    if (tmp.length > 250) {
                        tmp = tmp.truncate(250);
                    }
                    $('search_label').update(tmp.escapeHTML());
                }
                [ $('search_edit') ].invoke(this.search || this.viewport.getMetaData('noedit') ? 'hide' : 'show');
                this.showSearchbar(true);
            } else {
                this.setMboxLabel(this.view);
                this.showSearchbar(false);
            }

            if (this.rownum) {
                this.viewport.select([ this.rownum ]);
                delete this.rownum;
            }

            this.updateTitle();

            if (this.viewswitch) {
                if (this.selectedCount()) {
                    if (DimpCore.getPref('preview')) {
                        this.initPreviewPane();
                    }
                    this.toggleButtons();
                } else {
                    this.resetSelected();
                }

                tmp = $('filter');
                if (this.isSearch()) {
                    tmp.hide();
                    if (!this.search || !this.search.qsearch) {
                        $('horde-search').hide();
                    }
                } else if (tmp)  {
                    tmp.show();
                }

                if (this.viewport.getMetaData('drafts')) {
                    $('button_resume').up().show();
                    $('button_template', 'button_reply', 'button_forward', 'button_spam', 'button_innocent').compact().invoke('up').invoke('hide');
                } else if (this.viewport.getMetaData('templates')) {
                    $('button_template').up().show();
                    $('button_resume', 'button_reply', 'button_forward', 'button_spam', 'button_innocent').compact().invoke('up').invoke('hide');
                } else {
                    $('button_resume', 'button_template').compact().invoke('up').invoke('hide');
                    $('button_reply', 'button_forward').compact().invoke('up').invoke('show');
                    [ $('button_innocent') ].compact().invoke('up').invoke(this.viewport.getMetaData('innocent_show') ? 'show' : 'hide');
                    [ $('button_spam') ].compact().invoke('up').invoke(this.viewport.getMetaData('spam_show') ? 'show' : 'hide');
                }

                /* Read-only changes. */
                [ $('mailboxName').next('SPAN.readonlyImg') ].invoke(this.viewport.getMetaData('readonly') ? 'show' : 'hide');

                /* ACL changes. */
                if ((tmp = $('button_delete'))) {
                    [ tmp.up() ].invoke(this.viewport.getMetaData('nodelete') ? 'hide' : 'show');
                }

                // Reset variable at end of block, since other code may be
                // conditional depending on whether we just switched to the
                // mailbox.
                this.viewswitch = false;
            } else if (this.filtertoggle && this.isThreadSort()) {
                ssc = DimpCore.conf.sort.get('date').v;
            }

            this.setSortColumns(ssc);
        }.bindAsEventListener(this));

        container.observe('ViewPort:deselect', function(e) {
            var sel = this.viewport.getSelected(),
                count = sel.size();
            if (!count) {
                this.viewport.setMetaData({
                    curr_row: null,
                    last_row: null,
                    pivot_row: null
                });
            }

            this.toggleButtons();
            if (e.memo.opts.right || !count) {
                this.clearPreviewPane();
            } else if ((count == 1) && DimpCore.getPref('preview')) {
                this.loadPreview(sel.get('dataob').first());
            }

            this.resetSelectAll();
            this.setMsgHash();
        }.bindAsEventListener(this));

        container.observe('ViewPort:fetch', function() {
            if (!this.isSearch()) {
                this.showSearchbar(false);
            }
        }.bind(this));

        container.observe('ViewPort:remove', function(e) {
            e.memo.get('dataob').each(function(d) {
                this._expirePPCache([ this._getPPId(d.VP_id, d.VP_view) ]);
            }, this);
        }.bindAsEventListener(this));

        container.observe('ViewPort:resize', function() {
            switch (this.viewport.pane_mode) {
            case 'vert':
                $('msglistHeaderHoriz').hide();
                $('msglistHeaderVert').show();
                break;

            case 'horiz':
            default:
                $('msglistHeaderVert').hide();
                $('msglistHeaderHoriz').show();
                break;
            }
        }.bind(this));

        container.observe('ViewPort:select', function(e) {
            var d = e.memo.vs.get('rownum');
            if (d.size() == 1) {
                this.viewport.setMetaData({
                    curr_row: e.memo.vs,
                    pivot_row: e.memo.vs
                });
            }

            this.setMsgHash();

            this.toggleButtons();

            if (DimpCore.getPref('preview')) {
                if (e.memo.opts.right) {
                    this.clearPreviewPane();
                    $('previewInfo').highlight({
                        duration: 2.0,
                        keepBackgroundImage: true,
                        queue: {
                            limit: 1,
                            scope: 'previewInfo'
                        }
                    });
                } else if (e.memo.opts.delay) {
                    this.initPreviewPane.bind(this).delay(e.memo.opts.delay);
                } else {
                    this.initPreviewPane();
                }
            }
        }.bindAsEventListener(this));

        container.observe('ViewPort:sliderEnd', function() {
            $('slider_count').hide();
        });

        container.observe('ViewPort:sliderSlide', this.updateSliderCount.bind(this));

        container.observe('ViewPort:sliderStart', function() {
            var sc = $('slider_count'),
                sb = $('msgSplitPane').down('.vpScroll'),
                s = sb.viewportOffset();

            if (!sc) {
                sc = new Element('DIV', { id: 'slider_count' });
                $(document.body).insert(sc);
            }

            this.updateSliderCount();

            sc.setStyle({
                top: (s.top + sb.getHeight() - sc.getHeight()) + 'px',
                right: (document.viewport.getWidth() - s.left) + 'px'
            }).show();
        }.bind(this));

        container.observe('ViewPort:splitBarChange', function(e) {
            switch (e.memo) {
            case 'horiz':
                DimpCore.setPref('splitbar_horiz', this.viewport.getPageSize());
                break;

            case 'vert':
                DimpCore.setPref('splitbar_vert', this.viewport.getVertWidth());
                break;
            }
        }.bindAsEventListener(this));

        container.observe('ViewPort:wait', function() {
            if ($('dimpmain_folder').visible()) {
                HordeCore.notify(DimpCore.text.listmsg_wait, 'horde.warning');
            }
        });
    },

    addViewportParams: function(vs)
    {
        return this.viewport.addRequestParams({}, {
            view: (vs ? vs.getBuffer().getView() : null)
        });
    },

    emptyMsg: function()
    {
        return (this.isQSearch() || this.isFSearch())
            ? DimpCore.text.vp_empty_search
            : DimpCore.text.vp_empty;
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
        case 'ctx_remoteauth_create':
            tmp = this.flist.getMbox(e);
            RedBox.loading();
            DimpCore.doAction('createMailboxPrepare', {
                mbox: tmp.value()
            },{
                callback: this._mailboxPromptCallback.bind(this, 'create', tmp)
            });
            break;

        case 'ctx_container_rename':
        case 'ctx_mbox_rename':
            tmp = this.flist.getMbox(e);
            RedBox.loading();
            DimpCore.doAction('deleteMailboxPrepare', {
                mbox: tmp.value(),
                type: 'rename'
            },{
                callback: this._mailboxPromptCallback.bind(this, 'rename', tmp)
            });
            break;

        case 'ctx_mbox_empty':
            tmp = this.flist.getMbox(e);
            RedBox.loading();
            DimpCore.doAction('emptyMailboxPrepare', {
                mbox: tmp.value()
            },{
                callback: this._mailboxPromptCallback.bind(this, 'empty', tmp)
            });
            break;

        case 'ctx_container_delete':
            this._mailboxPromptCallback('delete', this.flist.getMbox(e), {
                result: true
            });
            break;

        case 'ctx_mbox_delete':
        case 'ctx_vfolder_delete':
            tmp = this.flist.getMbox(e);
            RedBox.loading();
            DimpCore.doAction('deleteMailboxPrepare', {
                mbox: tmp.value(),
                type: 'delete'
            }, {
                callback: this._mailboxPromptCallback.bind(this, 'delete', tmp)
            });
            break;

        case 'ctx_mbox_export':
            this.viewaction = function(e2, e) {
                HordeCore.download('', {
                    actionID: 'download_mbox',
                    mbox_list: Object.toJSON([ this.flist.getMbox(e).value() ]),
                    type: e2.memo.findElement('FORM').down('[name=download_type]').getValue()
                });
            }.bindAsEventListener(this, e);

            tmp = new Element('SELECT', { name: 'download_type' });
            $H(DimpCore.conf.download_types).each(function(d) {
                tmp.insert(new Element('OPTION', { value: d.key }).insert(d.value));
            });
            HordeDialog.display({
                form: tmp,
                form_id: 'dimpbase_confirm',
                text: DimpCore.text.download_mbox
            });
            break;

        case 'ctx_mbox_import':
            HordeDialog.display({
                form: new Element('DIV').insert(
                          new Element('INPUT', { name: 'import_file', type: 'file' })
                      ).insert(
                          new Element('INPUT', { name: 'MAX_FILE_SIZE', value: DimpCore.conf.MAX_FILE_SIZE }).hide()
                      ).insert(
                          new Element('INPUT', { name: 'import_mbox', value: this.flist.getMbox(e).value() }).hide()
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
                add: ~~(id == 'ctx_mbox_flag_seen'),
                flags: Object.toJSON([ DimpCore.conf.FLAG_SEEN ]),
                mbox: this.flist.getMbox(e).value()
            });
            break;

        case 'ctx_mbox_poll':
        case 'ctx_mbox_nopoll':
            this.modifyPoll(this.flist.getMbox(e).value(), id == 'ctx_mbox_poll');
            break;

        case 'ctx_mbox_sub':
            this._mailboxPromptCallback('subscribe', this.flist.getMbox(e));
            break;

        case 'ctx_mbox_unsub':
            this._mailboxPromptCallback('unsubscribe', this.flist.getMbox(e));
            break;

        case 'ctx_mbox_size':
            tmp = this.flist.getMbox(e);
            RedBox.loading();
            DimpCore.doAction('mailboxSize', {
                mbox: tmp.value()
            }, {
                callback: function(r) {
                    HordeDialog.display({
                        noform: true,
                        text: DimpCore.text.mboxsize.sub('%s', tmp.fullMboxDisplay()).sub('%s', r.size.escapeHTML())
                    });
                }.bind(this)
            });
            break;

        case 'ctx_mbox_acl':
            HordeCore.redirect(HordeCore.addURLParam(
                DimpCore.conf.URI_PREFS_IMP,
                {
                    group: 'acl',
                    mbox: this.flist.getMbox(e).value()
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
            this._toggleSubFolder(
                $('imp-normalmboxes'),
                id == 'ctx_folderopts_expand' ? 'expall' : 'colall',
                { noeffect: true }
            );
            break;

        case 'ctx_folderopts_reload':
            this._reloadFolders();
            break;

        case 'ctx_container_expand':
        case 'ctx_container_collapse':
        case 'ctx_mbox_expand':
        case 'ctx_mbox_collapse':
            this._toggleSubFolder(
                this.flist.getMbox(e).subElement(),
                (id == 'ctx_container_expand' || id == 'ctx_mbox_expand') ? 'expall' : 'colall',
                { noeffect: true }
            );
            break;

        case 'ctx_container_search':
        case 'ctx_mbox_search':
            this.go('search', {
                mailbox: this.flist.getMbox(e).value()
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
        case 'ctx_message_template':
        case 'ctx_message_template_edit':
            this.composeMailbox(id.substring(12));
            break;

        case 'ctx_message_source':
            this.viewport.getSelected().get('dataob').each(function(v) {
                HordeCore.popupWindow(DimpCore.conf.URI_VIEW, {
                    actionID: 'view_source',
                    buid: v.VP_id,
                    id: 0,
                    mailbox: v.VP_view
                }, {
                    name: v.VP_id + '|' + v.VP_view
                });
            }, this);
            break;

        case 'ctx_message_resume':
            this.composeMailbox('resume');
            break;

        case 'ctx_message_view':
            this.viewport.getSelected().get('dataob').each(this.msgWindow.bind(this));
            break;

        case 'ctx_message_addfilter':
            DimpCore.doAction('newFilter', {
                mailbox: this.view
            }, {
                uids: this.viewport.getSelected()
            });
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
            DimpCore.setPref('preview_old', DimpCore.getPref('preview', 'horiz'));
            this.togglePreviewPane('');
            break;

        case 'ctx_oa_preview_show':
            this.togglePreviewPane(DimpCore.getPref('preview_old'));
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
            this.viewport.reload({ delhide: ~~(id == 'ctx_oa_hide_deleted') });
            break;

        case 'ctx_oa_clear_sort':
            this.sort(DimpCore.conf.sort.get('sequence').v);
            break;

        case 'ctx_sortopts_date':
        case 'ctx_sortopts_from':
        case 'ctx_sortopts_msgarrival':
        case 'ctx_sortopts_msgdate':
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

        case 'ctx_datesort_msgarrival':
        case 'ctx_datesort_msgdate':
            tmp = DimpCore.conf.sort.get(id.substring(13)).v;
            if (tmp != this.viewport.getMetaData('sortby')) {
                this.sort(tmp);
            }
            break;

        case 'ctx_vfolder_edit':
            this.go('search', {
                edit_query: 1,
                mailbox: this.flist.getMbox(e).value()
            });
            break;

        case 'ctx_vcontainer_edit':
            HordeCore.redirect(HordeCore.addURLParam(
                DimpCore.conf.URI_PREFS_IMP,
                {
                    group: 'searches'
                }
            ));
            break;

        case 'ctx_qsearchopts_all':
        case 'ctx_qsearchopts_body':
        case 'ctx_qsearchopts_from':
        case 'ctx_qsearchopts_recip':
        case 'ctx_qsearchopts_subject':
            DimpCore.setPref('qsearch_field', id.substring(16));
            this._setQsearchText();
            if (this.isQSearch()) {
                this.viewswitch = true;
                this.quicksearchRun();
            } else {
                $('horde-search-input').focus();
            }
            break;

        case 'ctx_qsearchopts_advanced':
            this.go('search', tmp);
            break;

        case 'ctx_filteropts_applyfilters':
            if (this.viewport) {
                this.viewport.reload({ applyfilter: 1 });
            }
            break;

       case 'ctx_flag_new':
            this.displayFlagNew();
            break;

       case 'ctx_flag_edit':
            HordeCore.redirect(HordeCore.addURLParam(
                DimpCore.conf.URI_PREFS_IMP,
                {
                    group: 'flags'
                }
            ));
            break;

        case 'ctx_rcontainer_prefs':
            HordeCore.redirect(HordeCore.addURLParam(
                DimpCore.conf.URI_PREFS_IMP,
                { group: 'remote' }
            ));
            break;

        case 'ctx_remoteauth_logout':
            DimpCore.doAction('remoteLogout', {
                remoteid: this.flist.getMbox(e).value()
            });
            break;

        default:
            if (menu == 'ctx_filteropts_filter') {
                this.search = {
                    filter: elt.identify().substring('ctx_filter_'.length),
                    label: this.viewport.getMetaData('label'),
                    mbox: this.view
                };
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
            baseelt = this.flist.getMbox(e);

            if (baseelt.value() == this.INBOX) {
                elts.invoke('hide');
                if ($('ctx_mbox_sub')) {
                    $('ctx_mbox_sub', 'ctx_mbox_unsub').invoke('hide');
                }
            } else {
                if ($('ctx_mbox_sub')) {
                    tmp = baseelt.unsubscribed();
                    [ $('ctx_mbox_sub') ].invoke(tmp ? 'show' : 'hide');
                    [ $('ctx_mbox_unsub') ].invoke(tmp ? 'hide' : 'show');
                }

                if (Object.isUndefined(baseelt.fixed())) {
                    DimpCore.doAction('isFixedMbox', {
                        mbox: baseelt.value()
                     }, {
                        ajaxopts: {
                            asynchronous: false
                        },
                        callback: function(r) {
                            baseelt.fixed(r.fixed);
                        }
                     });
                 }

                if (baseelt.fixed()) {
                    elts.shift();
                    elts.invoke('hide');
                } else {
                    elts.invoke('show');
                }
            }

            if (baseelt.nc()) {
                $('ctx_mbox_create').hide();
            }

            if (DimpCore.conf.poll_alter) {
                [ $('ctx_mbox_poll') ].compact().invoke(baseelt.polled() ? 'hide' : 'show');
                [ $('ctx_mbox_nopoll') ].compact().invoke(baseelt.polled() ? 'show' : 'hide');
            } else {
                $('ctx_mbox_poll', 'ctx_mbox_nopoll').compact().invoke('hide');
            }

            [ $('ctx_mbox_expand').up() ].invoke(baseelt.subElement() ? 'show' : 'hide');

            [ $('ctx_mbox_acl').up() ].invoke(DimpCore.conf.acl ? 'show' : 'hide');
            // Fall-through

        case 'ctx_container':
        case 'ctx_noactions':
        case 'ctx_remoteauth':
        case 'ctx_vfolder':
            $(ctx_id).down('DIV.mboxName').update(this.flist.getMbox(e).fullMboxDisplay());
            break;

        case 'ctx_reply':
            sel = this.viewport.getSelected();
            if (sel.size() == 1) {
                ob = sel.get('dataob').first();
            }
            [ $('ctx_reply_reply_list') ].invoke(ob && ob.listmsg ? 'show' : 'hide');
            break;

        case 'ctx_oa':
            switch (DimpCore.getPref('preview')) {
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
                if (this.viewport.getMetaData('readonly') ||
                    this.viewport.getMetaData('pop3')) {
                    $('ctx_oa_setflag').up().hide();
                } else {
                    tmp.push($('ctx_oa_setflag').up());
                    [ $('ctx_oa_unsetflag') ].invoke((sel.size() > 1) ? 'show' : 'hide');
                }
            }

            tmp.compact().invoke(sel.size() ? 'show' : 'hide');

            if ((tmp = $('ctx_oa_purge_deleted'))) {
                if (this.viewport.getMetaData('pop3')) {
                    tmp.up().hide();
                } else {
                    tmp.up().show();
                    if (this.viewport.getMetaData('noexpunge')) {
                        tmp.hide();
                    } else {
                        tmp.show();
                        [ tmp.up() ].invoke(tmp.up().select('> a').any(Element.visible) ? 'show' : 'hide');
                    }
                }
            }

            if ((tmp = $('ctx_oa_hide_deleted'))) {
                if (this.isThreadSort() || this.viewport.getMetaData('pop3')) {
                    $(tmp, 'ctx_oa_show_deleted').invoke('hide');
                } else if (this.viewport.getMetaData('delhide')) {
                    tmp.hide();
                    $('ctx_oa_show_deleted').show();
                } else {
                    tmp.show();
                    $('ctx_oa_show_deleted').hide();
                }
            }

            if ((tmp = $('ctx_oa_clear_sort'))) {
                [ tmp.up() ].invoke(this.viewport.getMetaData('sortby') == DimpCore.conf.sort.get('sequence').v ? 'hide' : 'show');
            }
            break;

        case 'ctx_sortopts':
            elts = $(ctx_id).select('a span.iconImg');
            tmp = this.viewport.getMetaData('sortby');

            elts.invoke('removeClassName', 'sortdown')
                .invoke('removeClassName', 'sortup');

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

        case 'ctx_qsearchopts':
            $(ctx_id).descendants().invoke('removeClassName', 'contextSelected');
            $(ctx_id + '_' + DimpCore.getPref('qsearch_field')).addClassName('contextSelected');
            break;

        case 'ctx_message':
            [ $('ctx_message_source').up() ].invoke(DimpCore.getPref('preview') ? 'hide' : 'show');
            [ $('ctx_message_delete') ].compact().invoke(this.viewport.getMetaData('nodelete') ? 'hide' : 'show');
            [ $('ctx_message_undelete') ].compact().invoke(this.viewport.getMetaData('nodelete') || this.viewport.getMetaData('pop3') ? 'hide' : 'show');

            [ $('ctx_message_setflag').up() ].invoke((!this.viewport.getMetaData('flags').size() && this.viewport.getMetaData('readonly')) || this.viewport.getMetaData('pop3') ? 'hide' : 'show');

            if (this.viewport.getMetaData('drafts') ||
                this.viewport.getMetaData('templates')) {
                $('ctx_message_innocent', 'ctx_message_spam').compact().invoke('hide');
            } else {
                [ $('ctx_message_innocent') ].compact().invoke(this.viewport.getMetaData('innocent_show') ? 'show' : 'hide');
                [ $('ctx_message_spam') ].compact().invoke(this.viewport.getMetaData('spam_show') ? 'show' : 'hide');
            }

            sel = this.viewport.getSelected();
            if (sel.size() == 1) {
                if (this.viewport.getMetaData('templates')) {
                    $('ctx_message_resume').hide().up().show();
                    $('ctx_message_template', 'ctx_message_template_edit').invoke('show');
                } else if (this.isDraft(sel)) {
                    $('ctx_message_template', 'ctx_message_template_edit').invoke('hide');
                    $('ctx_message_resume').show().up('DIV').show();
                } else {
                    $('ctx_message_resume').up('DIV').hide();
                }
                [ $('ctx_message_addfilter') ].compact().invoke('show');
                [ $('ctx_message_unsetflag') ].compact().invoke('hide');
            } else {
                [ $('ctx_message_resume').up('DIV'), $('ctx_message_addfilter') ].compact().invoke('hide');
                [ $('ctx_message_unsetflag') ].compact().invoke('show');
            }
            break;

        case 'ctx_flag':
        case 'ctx_flagunset':
            flags = this.viewport.getMetaData('flags');
            tmp = $(ctx_id).select('.ctxFlagRow');
            if (flags.size()) {
                tmp.each(function(c) {
                    [ c ].invoke(flags.include(c.retrieve('flag')) ? 'show' : 'hide');
                });
            } else {
                tmp.invoke('show');
            }

            sel = this.viewport.getSelected();
            flags = (sel.size() == 1)
                ? sel.get('dataob').first().flag
                : null;

            tmp.each(function(elt) {
                DimpCore.toggleCheck(elt.down('DIV'), (flags === null) ? null : flags.include(elt.retrieve('flag')));
            });
            break;

        case 'ctx_datesort':
            tmp = this.viewport.getMetaData('sortby');
            $(ctx_id).descendants().invoke('removeClassName', 'contextSelected').find(function(n) {
                if (DimpCore.conf.sort.get(n.identify().substring(13)).v == tmp) {
                    n.addClassName('contextSelected');
                    return true;
                }
            });
            break;

        case 'ctx_subjectsort':
            DimpCore.toggleCheck($('ctx_subjectsort_thread').down('.iconImg'), this.isThreadSort());
            break;

        case 'ctx_preview':
            [ $('ctx_preview_allparts') ].invoke(this.pp.hide_all ? 'hide' : 'show');
            [ $('ctx_preview_thread') ].invoke(this.viewport.getMetaData('nothread') ? 'hide' : 'show');
            [ $('ctx_preview_listinfo') ].invoke(this.viewport.getSelected().get('dataob').first().listmsg ? 'show' : 'hide');
            break;

        case 'ctx_template':
            [ $('ctx_template_edit') ].invoke(this.viewport.getSelected().size() == 1 ? 'show' : 'hide');
            break;

        case 'ctx_filteropts':
            tmp = $('ctx_filteropts_applyfilters');
            if (tmp) {
                [ tmp.up('DIV') ].invoke(this.isSearch() || (!DimpCore.conf.filter_any && this.view != this.INBOX) ? 'hide' : 'show');
            }
            break;
        }
    },

    contextOnTrigger: function(e)
    {
        switch (e.memo) {
        case 'ctx_flag':
        case 'ctx_flagunset':
            this.flags_o.each(function(f) {
                if (f.a) {
                    this.contextAddFlag(f.id, f, e.memo);
                }
            }, this);

            if (e.memo == 'ctx_flag') {
                $(e.memo).insert(new Element('DIV', { className: 'sep' }))
                    .insert(
                        new Element('A', { id: 'ctx_flag_new' }).insert(
                            new Element('DIV', { className: 'iconImg' })
                        ) .insert(
                            DimpCore.text.newflag
                        )
                    ).insert(
                        new Element('A', { id: 'ctx_flag_edit' }).insert(
                            new Element('DIV', { className: 'iconImg' })
                        ) .insert(
                            DimpCore.text.editflag
                        )
                    );
            }
            break;

        case 'ctx_flag_search':
            this.flags_o.each(function(f) {
                if (f.s) {
                    this.contextAddFlag(f.id, f, e.memo);
                }
            }, this);
            break;

        case 'ctx_folderopts':
            [ $('ctx_folderopts_sub') ].compact().invoke('hide');
            break;
        }
    },

    contextAddFlag: function(flag, f, id)
    {
        var a = new Element('A', { className: 'ctxFlagRow' }),
            style = {};

        if (id == 'ctx_flag') {
            a.insert(new Element('DIV', { className: 'iconImg' }));
        }

        if (f.b && f.u) {
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
        var elt,
            label = this.viewport.getMetaData('label'),
            unseen = 0;

        // 'label' will not be set if there has been an error retrieving data
        // from the server.
        if (!label || !$('dimpmain_folder').visible()) {
            return;
        }

        if (this.isSearch()) {
            if (this.isQSearch()) {
                label += ' (' + this.search.label + ')';
            }
        } else if ((elt = this.flist.getMbox(this.view))) {
            unseen = elt.unseen();
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
                    var elt = new Element('A').insert(s.value.t);
                    if (s.value.ec) {
                        elt.addClassName(s.value.ec);
                    }
                    m.down('.' + s.value.c).store('sortby', s.value.v).insert({
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
        tmp2.adjacent('a').invoke(tmp ? 'hide' : 'show');

        [ m.down('.msgSubject .horde-popdown'), m.down('.msgDate .horde-popdown') ].invoke(this.viewport.getMetaData('sortbylock') ? 'hide' : 'show');

        ptr.find(function(s) {
            if (sortby != s.value.v) {
                return false;
            }
            if ((elt = m.down('.' + s.value.c))) {
                elt.addClassName(this.viewport.getMetaData('sortdir') ? 'sortup' : 'sortdown').store('sortby', s.value.v);
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
        var old = DimpCore.getPref('preview');
        if (mode != old) {
            DimpCore.setPref('preview', mode);
            this.viewport.showSplitPane(mode);
            if (!old) {
                this.initPreviewPane();
            }
        }
    },

    loadPreview: function(data, params)
    {
        var curr, last, p, peek, pp_uid, rows, tmp,
            msgload = {};

        if (!DimpCore.getPref('preview')) {
            return;
        }

        // If single message is loaded, and this mailbox is polled, try to
        // preload next unseen message that exists in current buffer.
        if (data &&
            (tmp = this.flist.getMbox(data.VP_view)) &&
            tmp.polled()) {
            curr = this.viewport.getSelected().get('rownum').first();
            rows = this.viewport.createSelectionBuffer().search({
                flag: { notinclude: DimpCore.conf.FLAG_SEEN }
            }).get('rownum').diff([ curr ]).numericSort();

            if (rows.size()) {
                p = rows.partition(function(r) {
                    return (r > curr);
                });

                last = this.viewport.getMetaData('last_row');
                p[1].reverse();

                /* Search for next cached message based on direction the
                 * selection row moved. */
                this.viewport.createSelection('rownum', (last && last.get('rownum').first() > curr) ? p[1].concat(p[0]) : p[0].concat(p[1])).get('uid').detect(function(u) {
                    if (this.ppfifo.indexOf(this._getPPId(u, data.VP_view)) === -1) {
                        msgload = { msgload: u };
                        return true;
                    }
                }, this);
            }
        }

        peek = this.viewswitch && (this.viewport.bufferCount() > 1);

        if (!params) {
            if (!data ||
                (this.pp &&
                 this.pp.VP_id == data.VP_id &&
                 this.pp.VP_view == data.VP_view)) {
                return;
            }
            this.pp = {
                VP_id: data.VP_id,
                VP_view: data.VP_view
            };
            pp_uid = this._getPPId(data.VP_id, data.VP_view);

            if (this.ppfifo.indexOf(pp_uid) !== -1) {
                if (!peek) {
                    this.flag(DimpCore.conf.FLAG_SEEN, true, {
                        buid: data.VP_id,
                        mailbox: data.VP_view,
                        params: msgload
                    });
                }
                return this._loadPreview(data.VP_id, data.VP_view);
            }

            params = {};
        }

        params = Object.extend(params, msgload);
        if (peek) {
            params.peek = 1;
        }
        params.preview = 1;

        DimpCore.doAction('showMessage', this.addViewportParams().merge(params), {
            callback: function(r) {
                if (!r) {
                    this.clearPreviewPane();
                } else if (this.pp &&
                           this.pp.VP_id == r.buid &&
                           this.pp.VP_view == r.view) {
                    if (r.error) {
                        HordeCore.notify(r.error, r.errortype);
                        this.clearPreviewPane();
                    } else {
                        this._loadPreview(r.buid, r.view);
                    }
                }
            }.bind(this),
            loading: 'msg',
            uids: this.viewport.createSelection('dataob', this.pp)
        });
    },

    _loadPreview: function(uid, mbox)
    {
        var tmp,
            pm = $('previewMsg'),
            r = this.ppcache[this._getPPId(uid, mbox)];

        pm.down('.msgHeaders').select('.address').invoke('fire', 'DimpBase:removeElt');

        // Add subject. Subject was already html encoded on server (subject
        // may include links).
        tmp = pm.select('.subject');
        tmp.invoke('update', r.subject === null ? '[' + DimpCore.text.badsubject + ']' : (r.subjectlink || r.subject));

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

        [ 'msgloglist', 'partlist' ].each(function(a) {
            if ($(a + '_exp').visible()) {
                $(a, a + '_col', a + '_exp').invoke('toggle');
            }
        });

        // Add attachment information
        if (r.atc_label) {
            $('msgAtc').show();
            tmp = $('partlist');
            tmp.previous().update(new Element('SPAN', { className: 'atcLabel' }).insert(r.atc_label)).insert(r.atc_download);
            if (r.atc_list) {
                tmp.update(new Element('TABLE'));
                tmp = tmp.down();

                r.atc_list.each(function(a) {
                    tmp.insert(
                        new Element('TR').insert(
                            new Element('TD').insert(a.icon)
                        ).insert(
                            new Element('TD').insert(a.description + ' (' + a.size + ')')
                        ).insert(
                            new Element('TD').insert(a.download)
                        )
                    );
                    if (a.download_zip) {
                        tmp.down('TD:last').insert(a.download_zip);
                    }
                });
            }
        } else {
            $('msgAtc').hide();
        }

        // Add message log information
        if (r.log) {
            this.updateMsgLog(r.log);
        } else {
            $('msgLogInfo').hide();
        }

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
            var ppuid = this._getPPId(msg.buid, msg.mbox);
            this._expirePPCache([ ppuid ]);
            this.ppcache[ppuid] = msg.data;
            this.ppfifo.push(ppuid);
        }, this);
    },

    _dragAtc: function(e)
    {
        // As of now, only WebKit supports DownloadURL
        if (!Prototype.Browser.Webkit) {
            e.stop();
            return;
        }

        var base = e.element().up();

        e.dataTransfer.setData(
            'DownloadURL',
            base.down('IMG').readAttribute('title') + ':' +
            // IE8 doesn't use this code so textContent is OK to use
            base.down('SPAN.mimePartInfoDescrip A').textContent.gsub(':', '-') + ':' +
            window.location.origin + e.element().readAttribute('href')
        );
    },

    updateAddressHeader: function(e)
    {
        DimpCore.doAction('addressHeader', {
            header: $w(e.element().className).first(),
            view: this.view
        }, {
            callback: this._updateAddressHeaderCallback.bind(this),
            loading: 'msg',
            uids: this.viewport.createSelection('dataob', this.pp)
        });
    },

    _updateAddressHeaderCallback: function(r)
    {
        $H(r.hdr_data).each(function(d) {
            this.updateHeader(d.key, d.value);
        }, this);
    },

    updateHeader: function(hdr, data, limit)
    {
        (hdr == 'from' ? $('previewMsg').select('.' + hdr) : [ $('msgHeaders').down('THEAD').down('.' + hdr) ]).each(function(elt) {
            elt.replace(DimpCore.buildAddressLinks(data, elt.clone(false), limit));
        });
    },

    updateMsgLog: function(log)
    {
        DimpCore.updateMsgLog(log);
        $('msgLogInfo').show();
    },

    _mimeTreeCallback: function(r)
    {
        this.pp.hide_all = true;

        $('partlist').update(r.tree).previous().update(new Element('SPAN', { className: 'atcLabel' }).insert(DimpCore.text.allparts_label));
        $('partlist_col').show();
        $('partlist_exp').hide();
        $('msgAtc').show();
    },

    _sendMdnCallback: function(r)
    {
        this._expirePPCache([ this._getPPId(r.buid, r.mbox) ]);

        if (this.pp &&
            this.pp.VP_id == r.buid &&
            this.pp.VP_view == r.mbox) {
            $('sendMdnMessage').up(1).fade({ duration: 0.2 });
        }
    },

    maillogCallback: function(r)
    {
        r.each(function(l) {
            var tmp = this._getPPId(l.buid, l.mbox);
            if (this.ppcache[tmp]) {
                this.ppcache[tmp].log = l.log;
                if (l.log &&
                    this.pp &&
                    this.pp.VP_id == l.buid &&
                    this.pp.VP_view == l.mbox) {
                    this.updateMsgLog(l.log);
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
        var pm = $('previewMsg');

        if (pm.visible()) {
            pm.hide().down('.msgHeaders').select('.address').invoke('fire', 'DimpBase:removeElt');
        }

        this.loadingImg('msg', false);
        $('previewPane').scrollTop = 0;

        $('previewInfo').update(DimpCore.text.selected.sub('%s', this.messageCountText(this.selectedCount()))).show();

        delete this.pp;
    },

    _toggleHeaders: function(elt, update)
    {
        if (update) {
            DimpCore.setPref('toggle_hdrs', ~~(!DimpCore.getPref('toggle_hdrs')));
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

    // mbox: (string) Mailbox name.
    // unseen: (integer) The updated value.
    updateUnseenStatus: function(mbox, unseen)
    {
        if (!(mbox = this.flist.getMbox(mbox))) {
            return;
        }

        mbox.unseen(unseen);

        this.setMboxLabel(mbox);

        if (this.view == mbox.value()) {
            this.updateTitle();
        }
    },

    setMessageListTitle: function()
    {
        var rows = this.viewport.getMetaData('total_rows'),
            text = this.viewport.getMetaData('label');

        // 'label' will not be set if there has been an error retrieving data
        // from the server.
        if (text) {
            if (rows) {
                text += ' (' + this.messageCountText(rows) + ')';
            }

            $('mailboxName').update(text.escapeHTML());
        }
    },

    // m = (string|Element) Mailbox element.
    setMboxLabel: function(m)
    {
        var unseen;

        if ((m = this.flist.getMbox(m))) {
            unseen = m.unseen();
            m.labelElement().update((unseen > 0) ?
                new Element('STRONG').insert(m.label()).insert('&nbsp;').insert(new Element('SPAN', { className: 'count', dir: 'ltr' }).insert('(' + unseen + ')')) :
                m.label());
        }
    },

    /* Folder list updates. */

    // search = (boolean) If true, update search results as well.
    poll: function(search)
    {
        var args = $H(),
            opts = {};

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
            args.set('poll', Object.toJSON(Object.values(this.flist.mboxes).findAll(function(m) {
                return m.polled();
            }).invoke('value')));
        } else {
            args.set('poll', Object.toJSON([]));
        }

        if (search) {
            args.set('forceUpdate', 1);
        } else {
            opts.loading = 'viewport';
        }

        DimpCore.doAction('poll', args, opts);
    },

    pollCallback: function(r)
    {
        /* Don't update polled status until the sidebar is visible. Otherwise,
         * preview callbacks may not correctly update unseen status. */
        if (!$('foldersSidebar').visible()) {
            return this.pollCallback.bind(this, r).delay(0.1);
        }

        $H(r).each(function(u) {
            this.updateUnseenStatus(u.key, u.value);
        }, this);
    },

    quotaCallback: function(r)
    {
        var quota = $('quota-text');
        quota.removeClassName('quotaalert').
            removeClassName('quotawarn').
            setText(r.m);

        switch (r.l) {
        case 'alert':
        case 'warn':
            quota.addClassName('quota' + r.l);
            break;
        }
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

    searchReset: function(e)
    {
        this.quicksearchClear();
    },

    searchSubmit: function(e)
    {
        if ($F('horde-search-input')) {
            this.quicksearchRun();
        } else {
            this.quicksearchClear();
        }
    },

    quicksearchRun: function()
    {
        var q = $('horde-search-input');

        q.blur();

        if (this.isSearch()) {
            /* Search text has changed. */
            if (this.search.query != $F(q)) {
                this.viewswitch = true;
                this.search.query = $F(q);
            }
            this.resetSelected();
            this.viewport.reload();
        } else {
            this.search = {
                label: this.viewport.getMetaData('label'),
                mbox: this.view,
                qsearch: true,
                query: $F(q)
            };
            this.go('mbox', DimpCore.conf.qsearchid);
        }
    },

    // 'noload' = (boolean) If true, don't load the mailbox
    quicksearchClear: function(noload)
    {
        var qs = $('horde-search');

        if (qs && this.isSearch()) {
            $(qs, 'horde-search-dropdown', 'horde-search-input').invoke('show');
            if (!noload) {
                this.go('mbox', (this.search ? this.search.mbox : this.INBOX));
            }
            delete this.search;

            $('horde-search-input').clear();
            if (HordeTopbar.searchGhost) {
                HordeTopbar.searchGhost.reset();
            }
        }
    },

    /* Set quicksearch text. */
    _setQsearchText: function()
    {
        $('horde-search-input').writeAttribute('title', DimpCore.text.search_input.sub('%s', DimpCore.context.ctx_qsearchopts['*' + DimpCore.getPref('qsearch_field')]));
        if (HordeTopbar.searchGhost) {
            HordeTopbar.searchGhost.refresh();
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

    /* Enable/Disable action buttons as needed. */
    toggleButtons: function()
    {
        var bf = $('button_forward'),
            sc = this.selectedCount();

        DimpCore.toggleButtons(
            $('dimpmain_folder_top').select('DIV.horde-buttonbar A.noselectDisable'),
            sc === 0
        );

        if (sc > 1) {
            DimpCore.toggleButtons([ $('button_reply') ], true);
        }

        if (bf) {
            [ bf.next('.horde-popdown') ].compact().invoke(sc > 1 ? 'hide' : 'show');
        }
    },

    /* Drag/Drop handler. */
    mboxDropHandler: function(e)
    {
        var dropbase, sel, uids,
            drag = e.memo.element,
            drag_m = this.flist.getMbox(drag),
            drop = this.flist.getMbox(e.element());

        if (drag_m) {
            dropbase = this.isDropbase(e.element());
            if (dropbase ||
                (drop.ftype() != 'special' &&
                 !this.flist.isSubfolder(drag_m, drop))) {
                DimpCore.doAction('renameMailbox', {
                    new_name: drag_m.label().unescapeHTML(),
                    new_parent: dropbase ? '' : drop.value(),
                    old_name: drag_m.value()
                });
            }
        } else if (drop.ftype() != 'container') {
            sel = this.viewport.getSelected();

            if (sel.size()) {
                // Dragging multiple selected messages.
                uids = sel;
            } else if (drag.retrieve('mbox') != drop.value()) {
                // Dragging a single unselected message.
                uids = this.viewport.createSelection('domid', drag.id);
            }

            if (uids.size()) {
                if (e.memo.dragevent.ctrlKey) {
                    DimpCore.doAction('copyMessages', this.addViewportParams().merge({
                        mboxto: drop.value()
                    }), {
                        uids: uids
                    });
                } else if (this.view != drop.value()) {
                    // Don't allow drag/drop to the current mailbox.
                    this.updateFlag(uids, DimpCore.conf.FLAG_DELETED, true);
                    DimpCore.doAction('moveMessages', this.addViewportParams().merge({
                        mboxto: drop.value()
                    }), {
                        uids: uids
                    });
                }
            }
        }
    },

    dropbase: function()
    {
        var tmp = $('dropbase');
        return tmp ? tmp.up('DIV') : null;
    },

    isDropbase: function(elt)
    {
        var tmp = this.dropbase();
        return (tmp === elt || elt.descendantOf(tmp));
    },

    mboxDropCaption: function(drop, drag, e)
    {
        var m,
            orig_drop = drop;

        drag = this.flist.getMbox(drag);
        drop = this.flist.getMbox(drop);

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

        if (drag) {
            if (drop) {
                return (drop.ftype() != 'special' && !this.flist.isSubfolder(drag, drop))
                    ? m.sub('%s', drag.label()).sub('%s', drop.label())
                    : '';
            }

            return this.isDropbase(orig_drop)
                ? DimpCore.text.moveto.sub('%s', drag.label()).sub('%s', DimpCore.text.baselevel)
                : '';
        }

        return (drop.ftype() != 'container')
            ? m.sub('%s', this.messageCountText(this.selectedCount())).sub('%s', drop.label())
            : '';
    },

    messageCountText: function(cnt)
    {
        switch (cnt) {
        case 0:
            return DimpCore.text.message_0;

        case 1:
            return DimpCore.text.message_1;

        default:
            return DimpCore.text.message_2.sub('%d', cnt);
        }
    },

    onDragMouseDown: function(e)
    {
        var args, tmp,
            elt = e.element(),
            id = elt.identify(),
            d = DragDrop.Drags.getDrag(id);

        if (elt.hasClassName('vpRow')) {
            args = { right: e.memo.isRightClick() };
            d.fromClick = d.selectIfNoDrag = false;

            if (DimpCore.conf.from_link && e.memo.findElement('.msgFrom a')) {
                d.fromClick = !args.right;
                return;
            }

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
        } else if ((tmp = this.flist.getMbox(elt)) && tmp.isMbox()) {
            d.opera = DimpCore.DMenu.operaCheck(e);
        }
    },

    onDragStart: function(e)
    {
        if (e.element().hasClassName('horde-subnavi')) {
            var d = e.memo;
            if (!d.opera && !d.wasDragged) {
                $('folderopts_link').up().hide();
                this.dropbase().show();
            }
        }
    },

    onDragEnd: function(e)
    {
        var elt = e.element(),
            id = elt.identify(),
            d = DragDrop.Drags.getDrag(id);

        if (id == 'horde-slideleft') {
            DimpCore.setPref('splitbar_side', d.lastCoord[0]);
            this.setSidebarWidth();
        } else if (elt.hasClassName('horde-subnavi')) {
            if (!d.opera) {
                $('folderopts_link').up().show();
                this.dropbase().hide();
            }
        }
    },

    onDragMouseUp: function(e)
    {
        var elt = e.element(),
            id = elt.identify();

        if (elt.hasClassName('vpRow')) {
            d = DragDrop.Drags.getDrag(id);
            if (d.fromClick) {
                DimpCore.compose('new_to', {
                    buid: this.viewport.createSelection('div', elt).get('uid').toViewportUidString(),
                    mailbox: this.view
                });
            } else if (d.selectIfNoDrag) {
                this.msgSelect(id, { right: e.memo.isRightClick() });
            }
        }
    },

    /* Keydown event handler */
    keydownHandler: function(e)
    {
        var all, cnt, co, h, need, pp, ps, r, row, rownum, rowoff, sel,
            tmp, vsel, prev, noelt,
            kc = e.keyCode || e.charCode;

        // Only catch keyboard shortcuts in message list view.
        if (!$('dimpmain_folder').visible()) {
            return;
        }

        if (!Object.isFunction(e.element)) {
            // Inside IFRAME. Wrap in prototypejs Event object.

            // Ignore if IE 8
            if (Prototype.Browser.IE && !document.addEventListener) {
                return;
            }

            e = new Event(e);
            e.preventDefault();
            noelt = true;
            if (!(kc === 17 || kc === 67)) { //CTRL + C
                $$('IFRAME').invoke('blur');
            }
        } else if (e.findElement('FORM')) {
            // Inside form, so ignore.
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
            tmp = this.viewport.getMetaData('curr_row');
            if (e.altKey) {
                pp = $('previewPane');
                pp.scrollTop = prev
                    ? Math.max(pp.scrollTop - 10, 0)
                    : Math.min(pp.scrollTop + 10, pp.getHeight());
            } else if (e.shiftKey && tmp) {
                row = this.viewport.createSelection('rownum', tmp.get('rownum').first() + ((prev) ? -1 : 1));
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
            if ((noelt || !e.element().match('INPUT')) && sel.size() == 1) {
                // Popup message window if single message is selected.
                this.msgWindow(sel.get('dataob').first());
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
                tmp = this.flist.getMbox(this.view);
                cnt = tmp.unseen();
                if (cnt || !tmp.polled()) {
                    vsel = this.viewport.createSelectionBuffer();
                    row = vsel.search({ flag: { notinclude: DimpCore.conf.FLAG_SEEN } }).get('rownum');
                    all = (vsel.size() == this.viewport.getMetaData('total_rows'));

                    if (all || ((row.size() == cnt) && tmp.polled())) {
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
            tmp;

        if (elt.hasClassName('splitBarVertSidebar')) {
            DimpCore.setPref('splitbar_side', null);
            this.setSidebarWidth();
            e.memo.stop();
            return;
        } else if (elt.hasClassName('vpRow')) {
            tmp = this.viewport.createSelection('domid', elt.identify());

            if (this.viewport.getMetaData('templates')) {
                DimpCore.compose('template', {
                    buid: tmp.get('uid').toViewportUidString(),
                    mailbox: this.view
                });
            } else if (this.isDraft(tmp)) {
                DimpCore.compose('resume', {
                    buid: tmp.get('uid').toViewportUidString(),
                    mailbox: this.view
                });
            } else {
                this.msgWindow(tmp.get('dataob').first());
            }
            e.memo.stop();
        }
    },

    clickHandler: function(e)
    {
        var tmp,
            elt = e.element(),
            id = elt.readAttribute('id');

        if (DimpCore.DMenu.operaCheck(e.memo)) {
            return;
        }

        switch (id) {
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

        case 'appprefs':
            this.go('prefs');
            e.memo.stop();
            break;

        case 'applogout':
            elt.down('A').update('[' + DimpCore.text.onlogout + ']');
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
            tmp = e.memo.findElement('DIV');
            if (tmp.hasClassName('msCheckAll')) {
                this.selectAll();
            } else if (!e.memo.element().hasClassName('horde-popdown')) {
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

        case 'msg_newwin_options':
        case 'ppane_view_error':
            this.msgWindow(this.viewport.getSelection(this.pp.VP_view).search({
                VP_id: {
                    equal: [ this.pp.VP_id ]
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
                buid: this.pp.VP_id,
                id: 0,
                mailbox: this.pp.VP_view
            }, {
                name: this.pp.VP_id + '|' + this.pp.VP_view
            });
            break;

        case 'ctx_preview_listinfo':
        case 'ctx_preview_thread':
            HordeCore.popupWindow((id == 'ctx_preview_listinfo') ? DimpCore.conf.URI_LISTINFO : DimpCore.conf.URI_THREAD, {
                buid: this.pp.VP_id,
                mailbox: this.pp.VP_view
            }, {
                name: this.pp.VP_id + '|' + this.pp.VP_view
            });
            break;

        case 'ctx_preview_allparts':
            DimpCore.doAction('messageMimeTree', {
                preview: 1,
                view: this.pp.VP_view
            }, {
                callback: this._mimeTreeCallback.bind(this),
                loading: 'msg',
                uids: [ this.pp.VP_id ]
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

        case 'send_mdn_link':
            DimpCore.doAction('sendMDN', {
                view: this.pp.VP_view
            }, {
                callback: this._sendMdnCallback.bind(this),
                uids: [ this.pp.VP_id ]
            });
            e.memo.stop();
            break;

        default:
            if (elt.hasClassName('printAtc')) {
                HordeCore.popupWindow(DimpCore.conf.URI_VIEW, {
                    actionID: 'print_attach',
                    buid: this.pp.VP_id,
                    id: elt.readAttribute('mimeid'),
                    mailbox: this.pp.VP_view
                }, {
                    name: this.pp.VP_id + '|' + this.pp.VP_view + '|print',
                    onload: IMP_JS.printWindow
                });
                e.memo.stop();
            } else if (elt.hasClassName('stripAtc')) {
                if (window.confirm(DimpCore.text.strip_warn)) {
                    DimpCore.doAction('stripAttachment', this.addViewportParams().merge({
                        id: elt.readAttribute('mimeid')
                    }), {
                        callback: function(r) {
                            if (!this.pp) {
                                this.viewport.select(this.viewport.createSelectionBuffer(r.newmbox).search({
                                    VP_id: {
                                        equal: [ r.newbuid ]
                                    }
                                }).get('rownum'));
                            }
                        }.bind(this),
                        loading: 'msg',
                        uids: [ this.pp.VP_id ]
                    });
                }
                e.memo.stop();
            } else if (elt.hasClassName('flagcolorpicker')) {
                tmp = elt.previous('INPUT');
                this.colorpicker = new ColorPicker({
                    color: $F(tmp) || '#fff',
                    draggable: true,
                    offsetParent: elt,
                    resizable: true,
                    update: [
                        [ tmp, 'value' ],
                        [ tmp, 'background' ]
                    ]
                });
                e.memo.stop();
            } else if (elt.hasClassName('imp-sidebar-remote')) {
                elt = this.flist.getMbox(elt);
                HordeDialog.display({
                    form: new Element('DIV').insert(
                              new Element('INPUT', { name: 'remote_password', type: 'password' })
                          ).insert(
                              new Element('INPUT', { name: 'remote_id', value: elt.value() }).hide()
                          ),
                    form_id: 'remote_login',
                    text: DimpCore.text.remote_password.sub('%s', elt.fullMboxDisplay())
                });
                e.memo.stop();
            }
            break;
        }
    },

    loadingStartHandler: function(e)
    {
        this.loadingImg(e.memo, true);
    },

    loadingEndHandler: function(e)
    {
        this.loadingImg(e.memo, false);
    },

    dialogClickHandler: function(e)
    {
        var elt = e.element();

        switch (elt.identify()) {
        case 'dimpbase_confirm':
            this.viewaction(e);
            HordeDialog.close();
            break;

        case 'flag_new':
            DimpCore.doAction('createFlag', this.addViewportParams().merge({
                flagcolor: $F(elt.down('INPUT[name="flagcolor"]')),
                flagname: $F(elt.down('INPUT[name="flagname"]'))
            }), {
                callback: function(r) {
                    if (r.success) {
                        HordeDialog.close();
                    } else {
                        this.displayFlagNew();
                    }
                }.bind(this),
                uids: this.viewport.getSelected()
            });
            elt.update(DimpCore.text.newflag_wait);
            break;

        case 'mbox_import':
            HordeCore.submit(elt, {
                callback: function(r) {
                    HordeDialog.close();
                    if (r.action == 'importMailbox' &&
                        r.mbox == this.view) {
                        this.viewport.reload();
                    }
                }.bind(this)
            });
            elt.update(DimpCore.text.import_mbox_loading);
            break;

        case 'remote_login':
            DimpCore.doAction('remoteLogin', {
                // Base64 encode just to keep password data from being
                // plaintext. A trivial obfuscation, but will prevent
                // passwords from leaking in the event of some sort of data
                // dump.
                password: Base64.encode($F(elt.down('INPUT[name="remote_password"]'))),
                password_base64: true,
                remoteid: $F(elt.down('INPUT[name="remote_id"]')),
                unsub: ~~(!!this.showunsub)
            }, {
                callback: function(r) {
                    if (r.success) {
                        this.flist.getMbox($F(elt.down('INPUT[name="remote_id"]'))).element()
                            .removeClassName('imp-sidebar-remote')
                            .addClassName('imp-sidebar-container');
                        HordeDialog.close();
                    } else {
                        elt.enable().down('INPUT[name="remote_password"]').clear().focus();
                    }
                }.bind(this)
            });
            elt.disable();
            break;
        }
    },

    dialogCloseHandler: function()
    {
        if (this.colorpicker) {
            this.colorpicker.hide();
        }
        delete this.colorpicker;
    },

    removeEltHandler: function(e)
    {
        var id = e.element().readAttribute('id');

        if (id) {
            [ DragDrop.Drags.getDrag(id), DragDrop.Drops.getDrop(id) ].compact().invoke('destroy');
            DimpCore.DMenu.removeElement(id);
        }
    },

    updateSliderCount: function()
    {
        var range = this.viewport.currentViewableRange();

        $('slider_count').update(DimpCore.text.slidertext.sub('%d', range.first).sub('%d', range.last));
    },

    // elt: [Object:IMP_Flist_Mbox]
    _mailboxPromptCallback: function(type, elt, r)
    {
        switch (type) {
        case 'create':
            if (r.result) {
                this._createMboxForm(elt, 'createsub', DimpCore.text.createsub_prompt.sub('%s', elt.fullMboxDisplay()));
            } else {
                RedBox.close();
            }
            break;

        case 'delete':
            if (r.result) {
                this.viewaction = function(e) {
                    DimpCore.doAction('deleteMailbox', {
                        container: ~~elt.isContainer(),
                        mbox: elt.value(),
                        subfolders: e.element().down('[name=delete_subfolders]').getValue()
                    });
                };
                HordeDialog.display({
                    form: new Element('DIV').insert(
                        new Element('INPUT', { name: 'delete_subfolders', type: 'checkbox' })
                    ).insert(
                        DimpCore.text.delete_mbox_subfolders.sub('%s', elt.fullMboxDisplay())
                    ),
                    form_id: 'dimpbase_confirm',
                    text: elt.isContainer() ? null : DimpCore.text.delete_mbox.sub('%s', elt.fullMboxDisplay())
                });
            } else {
                RedBox.close();
            }
            break;

        case 'empty':
            if (r.result) {
                this.viewaction = function() {
                    DimpCore.doAction('emptyMailbox', {
                        mbox: elt.value()
                    });
                };
                HordeDialog.display({
                    form_id: 'dimpbase_confirm',
                    noinput: true,
                    text: DimpCore.text.empty_mbox.sub('%s', elt.fullMboxDisplay()).sub('%d', r.result)
                });
            } else {
                RedBox.close();
            }
            break;

        case 'rename':
            if (r.result) {
                this._createMboxForm(elt, 'rename', DimpCore.text.rename_prompt.sub('%s', elt.fullMboxDisplay()), elt.label().unescapeHTML());
            } else {
                RedBox.close();
            }
            break;

        case 'subscribe':
            this.viewaction = function(e) {
                DimpCore.doAction('subscribe', {
                    mbox: elt.value(),
                    sub: 1,
                    subfolders: e.element().down('[name=subscribe_subfolders]').getValue()
                });
            };

            HordeDialog.display({
                form: new Element('DIV').insert(
                    new Element('INPUT', { name: 'subscribe_subfolders', type: 'checkbox' })
                ).insert(
                    DimpCore.text.subscribe_mbox_subfolders.sub('%s', elt.fullMboxDisplay())
                ),
                form_id: 'dimpbase_confirm',
                text: elt.isContainer() ? null : DimpCore.text.subscribe_mbox.sub('%s', elt.fullMboxDisplay())
            });
            break;

        case 'unsubscribe':
            this.viewaction = function(e) {
                DimpCore.doAction('subscribe', {
                    mbox: elt.value(),
                    sub: 0,
                    subfolders: e.element().down('[name=unsubscribe_subfolders]').getValue()
                });
            };

            HordeDialog.display({
                form: new Element('DIV').insert(
                    new Element('INPUT', { name: 'unsubscribe_subfolders', type: 'checkbox' })
                ).insert(
                    DimpCore.text.unsubscribe_mbox_subfolders.sub('%s', elt.fullMboxDisplay())
                ),
                form_id: 'dimpbase_confirm',
                text: elt.isContainer() ? null : DimpCore.text.unsubscribe_mbox.sub('%s', elt.fullMboxDisplay())
            });
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

    // mbox: (Object:IMP_Flist_Mbox)
    _mboxAction: function(e, mbox, mode)
    {
        var action, params, val,
            form = e.findElement('form');
        val = $F(form.down('input'));

        if (val) {
            switch (mode) {
            case 'rename':
                if (mbox.label() != val) {
                    action = 'renameMailbox';
                    params = {
                        old_name: mbox.value(),
                        new_name: val
                    };
                }
                break;

            case 'create':
            case 'createsub':
                action = 'createMailbox';
                params = { mbox: val };
                if (mode == 'createsub') {
                    params.parent = mbox.value();
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
        var nm = $('imp-normalmboxes');

        if (r.d) {
            r.d.each(function(m) {
                if (this.view == m) {
                    this.go('mbox', r['switch'] || this.INBOX);
                }
                this.flist.deleteMbox(m, { sub: true });
                this.viewport.deleteView(m);
            }, this);
        }

        if (r.c) {
            r.c.each(function(m) {
                if (!this.showunsub && m.un) {
                    this.flist.deleteMbox(m.m);
                    this.viewport.deleteView(m.m);
                } else {
                    this.flist.changeMbox(m);
                }
            }, this);
        }

        if (r.a) {
            r.a.each(this.flist.createMbox.bind(this.flist));
        }

        if (r.expand) {
            this.flist.subElements(r.base).each(function(sub) {
                this._toggleSubFolder(sub, 'exp', {
                    noeffect: true,
                    noexpand: true,
                    noparents: r.base
                });
            }, this);
        }

        if (r.all) {
            this._toggleSubFolder(nm, 'expall', {
                noeffect: true,
                noexpand: true
            });
        }

        if ($('foldersLoading').visible()) {
            $('foldersLoading').hide();
            $('foldersSidebar').show();
        }

        if (this.view) {
            this.highlightSidebar(this.view);
        }

        if (nm && nm.getStyle('max-height') !== null) {
            this._sizeFolderlist();
        }
    },

    flagConfigCallback: function(r)
    {
        $('ctx_flag', 'ctx_flagunset', 'ctx_flag_search').compact().invoke('remove');
        this.flags = {};
        this.flags_o = r;

        r.each(function(f) {
            this.flags[f.id] = f;
        }, this);
    },

    flagCallback: function(r)
    {
        Object.values(r).each(function(entry) {
            $H(entry.buids).each(function(m) {
                var s = this.viewport.createSelectionBuffer(m.key).search({
                    VP_id: { equal: m.value.parseViewportUidString() }
                });

                if (entry.replace) {
                    s.get('dataob').each(function(d) {
                        d.flag = [];
                        this.viewport.updateRow(d);
                    }, this);
                    entry.add = entry.replace;
                }

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

    displayFlagNew: function()
    {
        HordeDialog.display({
            form: $('flagnewContainer').down().clone(true),
            form_id: 'flag_new',
            text: DimpCore.text.newflag_name
        });
    },

    _handleMboxMouseClick: function(e)
    {
        var mbox = this.flist.getMbox(e);

        if (!mbox) {
            return;
        }

        if ((mbox.expand() !== null) && (e.element() == mbox.iconElement())) {
            this._toggleSubFolder(mbox.element(), 'tog');
        } else {
            switch (mbox.ftype()) {
            case 'container':
            case 'rcontainer':
            case 'remote':
            case 'remoteauth':
            case 'scontainer':
            case 'vcontainer':
                e.stop();
                break;

            case 'mbox':
            case 'special':
            case 'vfolder':
                e.stop();
                if (mbox.value() != this.view ||
                    !$('dimpmain_folder').visible()) {
                    this.go('mbox', mbox.value());
                }
                break;
            }
        }
    },

    _toggleSubFolder: function(base, mode, opts)
    {
        var collapse = [], expand = [], subs = [],
            need = $H();
        opts = opts || {};

        if (mode == 'expall' || mode == 'colall') {
            if (base.hasClassName('horde-subnavi-sub')) {
                subs.push(base);
            }
            subs = subs.concat(base.select('.horde-subnavi-sub'));
        } else if (mode == 'exp') {
            // If we are explicitly expanding ('exp'), make sure all parent
            // subfolders are expanded.
            // The last 2 elements of ancestors() are the BODY and HTML tags -
            // don't need to parse through them.
            subs = opts.noparents
                ? [ base ]
                : [ base, base.ancestors() ].flatten().slice(0, -2).reverse().findAll(function(n) { return n.hasClassName('horde-subnavi-sub'); });
        } else {
            if (!base.hasClassName('horde-subnavi')) {
                base = base.up();
            }
            subs = [ base.next('.horde-subnavi-sub') ];
        }

        subs = subs.compact();
        if (!subs) {
            return;
        }

        if (mode == 'tog' || mode == 'expall') {
            subs.each(function(s) {
                if (!s.visible() && !s.childElements().size()) {
                    need.set(this.flist.getMbox(s.previous()).value(), 1);
                }
            }, this);

            if (need.size() && mode == 'expall') {
                this._listMboxes({
                    all: 1,
                    base: base,
                    expall: 1,
                    mboxes: need.keys()
                });
                return;
            }
        }

        subs.each(function(s) {
            if (mode == 'tog' ||
                ((mode == 'exp' || mode == 'expall') && !s.visible()) ||
                ((mode == 'col' || mode == 'colall') && s.visible())) {
                var mbox = this.flist.getMbox(s.previous());

                if (mode == 'col' ||
                    ((mode == 'tog') && s.visible())) {
                    collapse.push(mbox.value());
                } else if (!opts.noexpand &&
                           !need.get(mbox.value()) &&
                           (mode == 'exp' ||
                            ((mode == 'tog') && !s.visible()))) {
                    expand.push(mbox.value());
                }

                if (opts.noeffect && !need.get(mbox.value())) {
                    s.toggle();
                    mbox.expand(!mbox.expand());
                } else {
                    Effect.toggle(s, 'blind', {
                        beforeStart: (need.get(mbox.value()) ? this._loadMboxes.bind(this, mbox, base) : Prototype.emptyFunction),
                        afterFinish: function() {
                            mbox.expand(!mbox.expand());
                        },
                        duration: opts.noeffect ? 0 : 0.2,
                        queue: {
                            limit: 1,
                            scope: 'subfolder' + mbox.value()
                        }
                    });
                }
            }
        }, this);

        if (DimpCore.conf.mbox_expand) {
            if (collapse.size()) {
                DimpCore.doAction('toggleMailboxes', {
                    action: 'collapse',
                    mboxes: Object.toJSON(collapse)
                });
            } else if (expand.size()) {
                DimpCore.doAction('toggleMailboxes', {
                    action: 'expand',
                    mboxes: Object.toJSON(expand)
                });
            } else if (mode == 'colall' ||
                       (!opts.noexpand && mode == 'expall')) {
                DimpCore.doAction('toggleMailboxes', {
                    action: (mode == 'colall' ? 'collapse' : 'expand'),
                    all: 1
                });
            }
        }
    },

    _loadMboxes: function(m, base, e)
    {
        var ed;

        m.labelElement().update(
            new Element('SPAN')
                .addClassName('imp-sidebar-mbox-loading')
                .update('[' + DimpCore.text.loading + ']')
        );

        this._listMboxes({
            base: base,
            mboxes: [ m.value() ]
        }, true);

        this.setMboxLabel(m);

        /* Need to update sizing since it is calculated at instantiation
         * before the submailbox DIV contained anything. */
        ed = m.subElement().getDimensions();
        e.options.scaleMode = {
            originalHeight: ed.height,
            originalWidth: ed.width
        };
    },

    _listMboxes: function(params, sync)
    {
        var m;

        params = params || {};
        params.unsub = ~~(!!this.showunsub);
        if (!Object.isArray(params.mboxes)) {
            params.mboxes = [ params.mboxes ];
        }
        if ((m = this.flist.getMbox(params.base))) {
            params.base = m.value();
        }
        params.mboxes = Object.toJSON(params.mboxes);

        DimpCore.doAction('listMailboxes', params, {
            ajaxopts: { asynchronous: !sync }
        });
    },

    createMboxHandler: function(e)
    {
        var ftype = e.memo.ftype();

        // Make the new mailbox a drop target.
        if (!e.memo.virtual()) {
            new Drop(e.memo.element(), this.mboxDropConfig);
        }

        if (this.showunsub && e.memo.unsubscribed()) {
            e.memo.element().addClassName('imp-sidebar-unsubmbox');
        }

        switch (ftype) {
        case 'special':
            // For purposes of the contextmenu, treat special mailboxes
            // like regular mailboxes.
            ftype = 'mbox';
            // Fall through.

        case 'container':
        case 'mbox':
            new Drag(e.memo.element(), this.mboxDragConfig);
            break;

        case 'remote':
        case 'scontainer':
            ftype = 'noactions';
            break;

        case 'remoteauth':
            ftype = 'remoteauth';
            break;

        case 'vfolder':
            if (e.memo.virtual()) {
                ftype = 'noactions';
            }
            break;
        }

        DimpCore.addContextMenu({
            elt: e.memo.element(),
            type: ftype
        });
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

        this.flist.reload();
        this._listMboxes({ reload: 1, mboxes: this.view });
    },

    _getSelection: function(opts)
    {
        var vs;

        if (opts.vs) {
            vs = opts.vs;
        } else if (opts.uid) {
            vs = this.viewport.createSelection('uid', [ opts.uid ], opts.mailbox ? opts.mailbox : this.view);
        } else {
            vs = this.viewport.getSelected();
        }

        return vs;
    },

    // type = (string) AJAX action type
    // opts = (Object) loading, mailbox, uid
    // args = (Object) Parameters to pass to AJAX call
    _doMsgAction: function(type, opts, args)
    {
        var vs = this._getSelection(opts);

        if (vs.size()) {
            // This needs to be synchronous Ajax if we are calling from a
            // popup window because Mozilla will not correctly call the
            // callback function if the calling window has been closed.
            DimpCore.doAction(type, this.addViewportParams(vs).merge(args), {
                ajaxopts: { asynchronous: !(opts.uid && opts.mailbox) },
                loading: opts,
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
        opts.loading = 'viewport';
        opts.vs = this._getSelection(opts);

        if (this._doMsgAction('reportSpam', opts, { spam: ~~(!!spam) })) {
            this.updateFlag(opts.vs, spam ? DimpCore.conf.FLAG_SPAM : DimpCore.conf.FLAG_INNOCENT, true);
        }
    },

    // blacklist = (boolean) True for blacklist, false for whitelist
    // opts = 'mailbox', 'uid'
    blacklist: function(blacklist, opts)
    {
        this._doMsgAction('blacklist', opts || {}, { blacklist: ~~(!!blacklist) });
    },

    // opts = 'mailbox', 'uid'
    deleteMsg: function(opts)
    {
        opts = opts || {};
        opts.vs = this._getSelection(opts);

        if (this._doMsgAction('deleteMessages', opts, {})) {
            this.updateFlag(opts.vs, DimpCore.conf.FLAG_DELETED, true);
        }
    },

    // flag = (string) IMAP flag name
    // add = (boolean) True to add flag
    // opts = (Object) 'mailbox', 'params', 'uid'
    flag: function(flag, add, opts)
    {
        opts = opts || {};

        var need,
            vs = this._getSelection(opts);

        need = !vs.getBuffer().getMetaData('readonly') &&
            vs.get('dataob').any(function(ob) {
                return add
                    ? (!ob.flag || !ob.flag.include(flag))
                    : (ob.flag && ob.flag.include(flag));
            });

        if (need) {
            DimpCore.doAction('flagMessages', this.addViewportParams(vs).merge(opts.params).merge({
                add: ~~(!!add),
                flags: Object.toJSON([ flag ])
            }), {
                uids: vs
            });
        }
    },

    updateFlag: function(vs, flag, add)
    {
        vs.get('dataob').each(function(ob) {
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
        }, this);
    },

    isDraft: function(vs)
    {
        var d;

        return this.viewport.getMetaData('drafts') ||
               ((d = vs.get('dataob').first()) &&
                d.flag.include(DimpCore.conf.FLAG_DRAFT));
    },

    /* Miscellaneous mailbox actions. */

    purgeDeleted: function()
    {
        DimpCore.doAction('purgeDeleted', this.addViewportParams());
    },

    modifyPoll: function(mbox, add)
    {
        DimpCore.doAction('modifyPoll', {
            add: ~~(!!add),
            mbox: mbox
        }, {
            callback: this._modifyPollCallback.bind(this)
        });
    },

    _modifyPollCallback: function(r)
    {
        this.flist.getMbox(r.mbox).polled(r.add);
        if (!r.add) {
            this.updateUnseenStatus(r.mbox, 0);
        }
    },

    loadingImg: function(id, show)
    {
        if (id == 'viewport') {
            if (show) {
                $('checkmaillink').addClassName('imp-loading');
            } else {
                $('checkmaillink').removeClassName('imp-loading');
            }
            return;
        }
        HordeCore.loadingImg(id + 'Loading', 'previewPane', show);
    },

    /* AJAX tasks handler. */
    tasksHandler: function(e)
    {
        var t = e.tasks || {};

        if (t['imp:flag-config']) {
            this.flagConfigCallback(t['imp:flag-config']);
        }

        if (t['imp:message']) {
            this.messageCallback(t['imp:message']);
        }

        if (this.viewport && t['imp:viewport']) {
            t['imp:viewport'].requestid = e.response.request.rid;
            this.viewport.parseJSONResponse(t['imp:viewport']);
        }

        if (t['imp:mailbox']) {
            this.mailboxCallback(t['imp:mailbox']);
        }

        if (t['imp:flag']) {
            this.flagCallback(t['imp:flag']);
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
        var DM = DimpCore.DMenu, tmp, tmp2;

        /* Register global handlers now. */
        HordeCore.initHandler('click');
        HordeCore.initHandler('dblclick');

        /* Initialize variables. */
        DimpCore.conf.sort = $H(DimpCore.conf.sort);
        this.flist = new IMP_Flist();

        /* Limit to folders sidebar only. */
        $('foldersSidebar').on('mouseover', '.exp', function(e, elt) {
            if (DragDrop.Drags.drag) {
                this._toggleSubFolder(elt.up(), 'tog');
            }
        }.bind(this));

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

            DimpCore.addPopdown('button_filter', 'filteropts', {
                trigger: true
            });
            DM.addSubMenu('ctx_filteropts_filter', 'ctx_filter');
            DM.addSubMenu('ctx_filteropts_flag', 'ctx_flag_search');
            DM.addSubMenu('ctx_filteropts_flagnot', 'ctx_flag_search');

            /* Don't submit FORM. Really only needed for Opera (Bug #9730)
             * but shouldn't hurt otherwise. */
            $('horde-search-input').up('FORM').observe('submit', Event.stop);
        }

        /* Initialize the starting page. The initial call to viewPort will
         * return the mailbox list and pending notifications. */
        tmp = decodeURIComponent(location.hash);
        if (!tmp.empty() && tmp.startsWith('#')) {
            tmp = (tmp.length == 1) ? "" : tmp.substring(1);
        }

        if (!tmp.empty()) {
            tmp2 = tmp.indexOf(':');
            if (tmp2 == -1) {
                this.go(tmp);
            } else {
                this.go(tmp.substring(0, tmp2), tmp.substring(tmp2 + 1));
            }
        } else if (DimpCore.conf.initial_page) {
            this.go('mbox', DimpCore.conf.initial_page);
        } else {
            this.go();
        }

        /* Add popdown menus. */
        DimpCore.addPopdown('button_template', 'template');
        DimpCore.addPopdown('button_other', 'oa', {
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
        DM.addSubMenu('ctx_message_unsetflag', 'ctx_flagunset');
        DM.addSubMenu('ctx_oa_setflag', 'ctx_flag');
        DM.addSubMenu('ctx_oa_unsetflag', 'ctx_flagunset');
        DM.addSubMenu('ctx_mbox_setflag', 'ctx_mbox_flag');

        DimpCore.addPopdown($('msglistHeaderHoriz').down('.msgSubject').identify(), 'subjectsort', {
            insert: 'bottom'
        });
        DimpCore.addPopdown($('msglistHeaderHoriz').down('.msgDate').identify(), 'datesort', {
            insert: 'bottom'
        });

        DimpCore.addPopdown($('preview_other_opts').down('A'), 'preview', {
            trigger: true
        });
        DimpCore.addContextMenu({
            elt: $('preview_other'),
            left: true,
            offset: $('preview_other').down('SPAN'),
            type: 'preview'
        });

        if (DimpCore.conf.disable_compose) {
            $('button_reply', 'button_forward').compact().invoke('up').concat($('button_compose', 'horde-new-link', 'ctx_contacts_new')).compact().invoke('remove');
        } else {
            DimpCore.addPopdown('button_reply', 'reply');
            DimpCore.addPopdown('button_forward', 'forward');
        }

        if (tmp = this.dropbase()) {
            new Drop(tmp, this.mboxDropConfig);
        }

        // See: http://www.thecssninja.com/javascript/gmail-dragout
        $('messageBody').on('dragstart', 'DIV.mimePartInfo A.downloadAtc', this._dragAtc.bind(this));

        if (DimpCore.getPref('toggle_hdrs')) {
            this._toggleHeaders($('th_expand'));
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
        if (!$('dimpLoading').visible()) {
            this._sizeFolderlist();
            this.splitbar.setStyle({
                height: document.viewport.getHeight() + 'px'
            });
            if ($('dimpmain_iframe').visible()) {
                $('dimpmain_iframe').down('IFRAME').setStyle({
                    height: $('horde-page').getHeight() + 'px'
                });
            }
        }
    },

    /* AJAX exception handling. */
    onAjaxException: function()
    {
        HordeCore.notify(HordeCore.text.ajax_error, 'horde.error');
    },

    onAjaxFailure: function(r)
    {
        if (r.memo[0].request) {
            switch (r.memo[0].request.action) {
            case 'createMailboxPrepare':
            case 'deleteMailboxPrepare':
            case 'emptyMailboxPrepare':
            case 'mailboxSize':
                RedBox.close();
                break;
            }
        }
    }

};

var IMP_Flist = Class.create({

    initialize: function()
    {
        this.active = null;
        this.mboxes = {};
    },

    getMbox: function(id)
    {
        var ob;

        if (Object.isUndefined(id)) {
            return null;
        } else if (Object.isString(id)) {
            return this.mboxes[id];
        } else if (Object.isElement(id)) {
            ob = this.mboxes[id.retrieve('mbox')];
            if (ob) {
                if (ob.element() == id) {
                    return ob;
                }
                ob = ob.fake();
                if (ob && ob.element() == id) {
                    return ob;
                }
            }
        } else if (Object.isFunction(id.value)) {
            return id;
        } else if (Object.isFunction(id.findElement)) {
            return this.getMbox(id.findElement('DIV.horde-subnavi'));
        }

        return null;
    },

    // m: (string) Mailbox ID
    // opts: (object) [sub]
    deleteMbox: function(m, opts)
    {
        if ((m = this.getMbox(m))) {
            m.remove(opts && opts.sub);
            delete this.mboxes[m.value()];
        }
    },

    changeMbox: function(ob)
    {
        if (this.mboxes[ob.m]) {
            this.mboxes[ob.m].build(this, ob);
        } else {
            this.createMbox(ob);
        }
    },

    createMbox: function(ob)
    {
        var f;

        if (!this.mboxes[ob.m]) {
            f = new IMP_Flist_Mbox(this, ob);
            if (f.element()) {
                this.mboxes[ob.m] = f;
            }
        }
    },

    // p = (IMP_Flist_Mbox) Parent
    // c = (IMP_Flist_Mbox) Child
    isSubfolder: function(p, c)
    {
        var sf = p.subElement();
        return sf && c.element().descendantOf(sf);
    },

    reload: function()
    {
        Object.values(this.mboxes).each(function(elt) {
            this.deleteMbox(elt, { sub: true });
        }, this);
    },

    highlight: function(id)
    {
        var elt = this.getMbox(id);

        if (this.active === elt) {
            return;
        }

        if (this.active) {
            this.active.element().removeClassName('horde-subnavi-active');
        }

        if (elt) {
            elt.element().addClassName('horde-subnavi-active');
            this.active = elt;
        }
    },

    subElements: function(base)
    {
        var elts = [], tmp;

        if (base) {
            tmp = this.getMbox(base);
            if (tmp) {
                tmp = tmp.subElement();
                if (tmp) {
                    elts = tmp.select('DIV.horde-subnavi').collect(
                        this.getMbox.bind(this)
                    ).compact();
                }
            }
        } else {
            elts = Object.values(this.mboxes);
        }

        return elts.invoke('subElement').compact().findAll(function(e) {
            return e.down();
        });
    }

});

var IMP_Flist_Mbox = Class.create({

    // For format of the ob object, see IMP_Queue#_ftreeElt().
    // Extra fields: fake, elt, fixed, unseen
    initialize: function(flist, ob)
    {
        this.build(flist, ob);
    },

    build: function(flist, ob)
    {
        var div, f_node, ll, parent_c, parent_e, tmp,
            cname = 'imp-sidebar-container',
            mbox = (!ob.isfake && flist.getMbox(ob.m)),
            title = ob.t || ob.l;

        if (ob.s) {
            /* Create a "fake" container element in normal mailboxes section
             * if special mailbox has children. */
            if (ob.ch) {
                ob.fake = new IMP_Flist_Mbox(flist, Object.extend(Object.clone(ob), {
                    co: true,
                    isfake: true,
                    l: title,
                    ns: false,
                    s: false
                }));
            } else if (mbox && mbox.fake()) {
                mbox.fake().remove();
            }
        }

        if (ob.v) {
            if (!ob.co) {
                cname = 'imp-sidebar-mbox';
            }
        } else if (ob.r) {
            if (ob.r == 2) {
                cname = 'imp-sidebar-remote';
            }
        } else if (ob.co) {
            if (ob.n) {
                title = ob.l;
            }
        } else {
            cname = 'imp-sidebar-mbox';
        }

        div = new Element('DIV', { className: 'horde-subnavi-icon' });
        if (ob.ch && !ob.s) {
            ob.expand = true;
            div.addClassName('exp');
        } else {
            ob.expand = null;
            div.addClassName(ob.cl || 'folderImg');
        }
        if (ob.i) {
            div.setStyle({ backgroundImage: 'url("' + ob.i + '")' });
        }

        ob.elt = new Element('DIV', { className: 'horde-subnavi', title: title })
            .addClassName(cname)
            .store('mbox', ob.m)
            .insert(div)
            .insert(new Element('DIV', { className: 'horde-subnavi-point' })
                    .insert(new Element('A').insert(ob.l)));

        if (mbox) {
            mbox.element().fire('IMP_Flist_Mbox:delete', mbox);
            mbox.element().replace(ob.elt);
        } else {
            if (ob.s) {
                parent_e = $('imp-specialmboxes');
            } else if (ob.pa) {
                /* Check for existence of parent object. May not exist if,
                 * e.g., mailbox is created in collapsed subfolder. */
                if (!(tmp = flist.getMbox(ob.pa))) {
                    return;
                }
                parent_e = tmp.subElement();
            } else {
                parent_e = $('imp-normalmboxes');
            }

            /* Insert into correct place in level. */
            parent_c = parent_e.childElements();
            if (!ob.ns) {
                ll = ob.l.toLowerCase();
                f_node = parent_c.find(function(node) {
                    var m = flist.getMbox(node);
                    return (m && !m.fs() && (ll < m.label().toLowerCase()));
                });
            }

            tmp = f_node
                ? f_node.previous()
                : parent_c.last();
            if (tmp &&
                tmp.hasClassName('horde-subnavi-sub') &&
                tmp.retrieve('m') == ob.m) {
                tmp.insert({ before: ob.elt });
            } else if (f_node) {
                f_node.insert({ before: ob.elt });
            } else {
                parent_e.insert(ob.elt);
            }
        }

        this.data = ob;

        if (!ob.s && ob.ch && !this.subElement()) {
            ob.elt.insert({
                after: new Element('DIV', { className: 'horde-subnavi-sub' }).store('m', ob.m).hide()
            });
        }

        ob.elt.fire('IMP_Flist_Mbox:create', this);
    },

    remove: function(sub)
    {
        var smbox;

        if (sub && (smbox = this.subElement())) {
            smbox.remove();
        }

        if (this.data.fake) {
            this.data.fake.remove(sub);
        }

        this.data.elt.fire('IMP_Flist_Mbox:delete', this);
        this.data.elt.remove();
    },

    /* Read/write. */

    fixed: function(f)
    {
        if (!Object.isUndefined(f)) {
            this.data.fixed = !!f;
        }

        return this.data.fixed;
    },

    polled: function(p)
    {
        if (!Object.isUndefined(p)) {
            this.data.po = p;
        }

        return !!this.data.po;
    },

    unseen: function(u)
    {
        if (!Object.isUndefined(u)) {
            this.data.unseen = u;
        }

        return ~~this.data.unseen;
    },

    expand: function(e)
    {
        var elt;

        if (this.data.fake) {
            return this.data.fake.expand(e);
        } else if (!Object.isUndefined(e)) {
            elt = this.iconElement();
            [ 'col', 'exp', 'folderImg' ].each(elt.removeClassName.bind(elt));

            if (e === null) {
                elt.addClassName('folderImg');
            } else {
                e = !!e;
                [ elt ].invoke('addClassName', e ? 'exp' : 'col');
            }

            this.data.expand = e;
        }

        return this.data.expand;
    },

    /* Read-only. */

    element: function()
    {
        return this.data.elt;
    },

    labelElement: function()
    {
        return this.data.elt.down('A');
    },

    subElement: function()
    {
        var m_elt;

        if (this.data.fake) {
            return this.data.fake.subElement();
        }

        m_elt = this.data.elt.next();
        return (m_elt && m_elt.hasClassName('horde-subnavi-sub'))
            ? m_elt
            : null;
    },

    parentElement: function()
    {
        var elt = this.data.elt.up('DIV.horde-subnavi-sub');
        return elt ? elt.previous() : null;
    },

    iconElement: function()
    {
        return this.data.elt.down('DIV.horde-subnavi-icon');
    },

    value: function()
    {
        return this.data.m;
    },

    fake: function()
    {
        return this.data.fake;
    },

    isContainer: function()
    {
        return this.data.elt.hasClassName('imp-sidebar-container');
    },

    isMbox: function()
    {
        return this.data.elt.hasClassName('imp-sidebar-mbox');
    },

    ftype: function()
    {
        if (this.data.v) {
            return this.data.co ? 'vcontainer' : 'vfolder';
        } else if (this.data.r) {
            switch (this.data.r) {
            case 1:
                return 'rcontainer';

            case 2:
                return 'remote';

            case 3:
                return 'remoteauth';
            }
        } else if (this.data.co) {
            return this.data.n ? 'scontainer' : 'container';
        } else if (this.data.s) {
            return 'special';
        }

        return 'mbox';
    },

    nc: function()
    {
        return !!this.data.nc;
    },

    label: function()
    {
        return this.data.l;
    },

    fs: function()
    {
        return !!this.data.fs;
    },

    virtual: function()
    {
        return !!(~~this.data.v);
    },

    unsubscribed: function()
    {
        return !!this.data.un;
    },

    fullMboxDisplay: function()
    {
        return this.data.elt.readAttribute('title').escapeHTML();
    }

});

/* Initialize onload handler. */
document.observe('dom:loaded', function() {
    if (Prototype.Browser.IE && !document.addEventListener) {
        // For IE 8
        DimpBase.onDomLoad.bind(DimpBase).delay(0.1);
    } else {
        DimpBase.onDomLoad();
    }
});

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
document.observe('HordeDialog:onClick', DimpBase.dialogClickHandler.bindAsEventListener(DimpBase));
document.observe('HordeDialog:close', DimpBase.dialogCloseHandler.bind(DimpBase));

/* AJAX related events. */
document.observe('HordeCore:ajaxException', DimpBase.onAjaxException.bind(DimpBase));
document.observe('HordeCore:ajaxFailure', DimpBase.onAjaxFailure.bind(DimpBase));
document.observe('HordeCore:runTasks', function(e) {
    DimpBase.tasksHandler(e.memo);
});

/* Click handlers. */
document.observe('HordeCore:click', DimpBase.clickHandler.bindAsEventListener(DimpBase));
document.observe('HordeCore:dblclick', DimpBase.dblclickHandler.bindAsEventListener(DimpBase));

/* AJAX loading handlers. */
document.observe('HordeCore:loadingStart', DimpBase.loadingStartHandler.bindAsEventListener(DimpBase));
document.observe('HordeCore:loadingEnd', DimpBase.loadingEndHandler.bindAsEventListener(DimpBase));

/* ContextSensitive handlers. */
document.observe('ContextSensitive:click', DimpBase.contextOnClick.bindAsEventListener(DimpBase));
document.observe('ContextSensitive:show', DimpBase.contextOnShow.bindAsEventListener(DimpBase));
document.observe('ContextSensitive:trigger', DimpBase.contextOnTrigger.bindAsEventListener(DimpBase));

/* Search handlers. */
document.observe('FormGhost:reset', DimpBase.searchReset.bindAsEventListener(DimpBase));
document.observe('FormGhost:submit', DimpBase.searchSubmit.bindAsEventListener(DimpBase));

/* DimpBase handlers. */
document.observe('DimpBase:removeElt', DimpBase.removeEltHandler.bindAsEventListener(DimpBase));

/* DimpCore handlers. */
document.observe('DimpCore:updateAddressHeader', DimpBase.updateAddressHeader.bindAsEventListener(DimpBase));

/* Folder list element handlers. */
document.observe('IMP_Flist_Mbox:create', DimpBase.createMboxHandler.bind(DimpBase));
document.observe('IMP_Flist_Mbox:delete', function(e) {
    e.memo.element().fire('DimpBase:removeElt');
});

/* HTML IFRAME handlers. */
document.observe('IMP_JS:htmliframe_keydown', function(e) {
    DimpBase.keydownHandler(e.memo);
});

/* Define reloadMessage() method for this page. */
DimpCore.reloadMessage = function(params) {
    DimpBase.loadPreview(null, params);
};
