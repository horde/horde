/**
 * jQuery Mobile UI application logic.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */
var ImpMobile = {

    // Vars used and defaulting to null/false:
    //
    // /* Attachment data for the current message. */
    // atc,
    //
    // /* Header data for the current message. */
    // headers,
    //
    // /* The current mailbox. */
    // mailbox,
    //
    // /* Cache ID for the current mailbox view. */
    // mailboxCache,
    //
    // /* The current message data. */
    // message,
    //
    // /* Current row UID of the displayed message. */
    // rowid,
    //
    // /* Search parameters for the viewPort Ajax request. */
    // search,
    //
    // /* UID of the currently displayed message. */
    // uid,
    //
    // /* Mailbox of the currently displayed message. */
    // uid_mbox,

    // Mailbox data cache.
    cache: {},

    // Rows per mailbox page.
    mbox_rows: 40,

    // 'INBOX' base64url encoded
    INBOX: 'SU5CT1g',


    /**
     * Event handler for the pagebeforechange event that implements loading of
     * deep-linked pages.
     *
     * @param object e     Event object.
     * @param object data  Event data.
     */
    toPage: function(e, data)
    {
        var view = data.options.parsedUrl.view;

        switch (view) {
        case 'compose':
            if (!IMP.conf.disable_compose) {
                $('#imp-compose-cache').val('');
                ImpMobile.compose(data);
            }
            e.preventDefault();
            break;

        case 'compose-cancel':
            HordeMobile.doAction('cancelCompose', {
                imp_compose: $('#imp-compose-cache').val()
            });
            ImpMobile.closeCompose();
            e.preventDefault();
            break;

        case 'compose-submit':
            ImpMobile.uniqueSubmit(
                $('#imp-compose-form').is(':hidden') ? 'redirectMessage' : 'sendMessage'
            );
            e.preventDefault();
            break;

        case 'confirm':
            ImpMobile.confirm(data);
            e.preventDefault();
            break;

        case 'confirmed':
            ImpMobile.confirmed(data);
            e.preventDefault();
            break;

        case 'copymove':
            if (IMP.conf.allow_folders) {
                ImpMobile.copymove(data);
            }
            e.preventDefault();
            break;

        case 'copymove-new-submit':
            ImpMobile.copymoveSelected(e);
            e.preventDefault();
            break;

        case 'folders-refresh':
            // TODO: Bug(?)
            $('#folders :jqmData(role=footer) a[href$="refresh"]').removeClass($.mobile.activeBtnClass).blur();
            e.preventDefault();
            // Fall-through

        case 'folders':
            HordeMobile.doAction('poll', {
                poll: JSON.stringify([])
            });
            break;

        case 'folders-showall':
        case 'folders-showpoll':
            $('#folders :jqmData(role=footer) a[href*="folders-show"]').toggle();
            HordeMobile.doAction(
                'smartmobileFolderTree',
                { all: ImpMobile.showAllFolders() },
                function(r) {
                    $('#imp-folders-list').html(r).listview('refresh');
                }
            );
            e.preventDefault();
            break;

        case 'mailbox':
            ImpMobile.toMailbox(data);
            e.preventDefault();
            break;

        case 'mailbox-delete':
            ImpMobile.deleteMessage(
                data.options.data.jqmData('mbox'),
                data.options.data.jqmData('uid')
            );
            e.preventDefault();
            break;

        case 'mailbox-next':
        case 'mailbox-prev':
            ImpMobile.navigateMailbox(view.match(/next$/) ? 1 : -1);
            e.preventDefault();
            break;

        case 'mailbox-innocent':
        case 'mailbox-spam':
            ImpMobile.reportSpam(
                view.match(/spam$/) ? 'spam' : 'innocent',
                data.options.data.jqmData('mbox'),
                data.options.data.jqmData('uid')
            );
            e.preventDefault();
            break;

        case 'message':
            ImpMobile.toMessage(data);
            e.preventDefault();
            break;

        case 'message-delete':
            ImpMobile.deleteMessage(ImpMobile.uid_mbox, ImpMobile.uid);
            $.mobile.changePage(HordeMobile.createUrl('mailbox', {
                mbox: ImpMobile.mailbox
            }), {
                data: { noajax: true }
            });
            e.preventDefault();
            return;

        case 'message-forward':
            $.mobile.changePage(HordeMobile.createUrl('compose', {
                mbox: ImpMobile.uid_mbox,
                type: 'forward_auto',
                uid: ImpMobile.uid
            }));
            e.preventDefault();
            break;

        case 'message-more':
            $.each($('#imp-message-more').hide().siblings(), function(i, v) {
                v = $(v);
                if (v.jqmData('more') && !v.jqmData('morehide')) {
                    v.show();
                }
            });
            e.preventDefault();
            break;

        case 'message-next':
        case 'message-prev':
            ImpMobile.navigateMessage(view.match(/next$/) ? 1 : -1);
            e.preventDefault();
            break;

        case 'message-redirect':
            $.mobile.changePage(HordeMobile.createUrl('compose', {
                mbox: ImpMobile.uid_mbox,
                type: 'forward_redirect',
                uid: ImpMobile.uid
            }));
            e.preventDefault();
            break;

        case 'mailbox-refresh':
            $.mobile.changePage(HordeMobile.createUrl('mailbox', {
                mbox: ImpMobile.mailbox
            }));
            e.preventDefault();
            $('#mailbox :jqmData(role=footer) a[href$="refresh"]').removeClass($.mobile.activeBtnClass).blur();
            break;

        case 'message-reply':
            $.mobile.changePage(HordeMobile.createUrl('compose', {
                mbox: ImpMobile.uid_mbox,
                type: 'reply_auto',
                uid: ImpMobile.uid
            }));
            e.preventDefault();
            break;

        case 'search-submit':
            ImpMobile.search = {
                qsearch: $('#imp-search-input').val(),
                qsearchfield: $('#imp-search-by').val(),
                qsearchmbox: ImpMobile.mailbox
            };
            delete ImpMobile.cache[IMP.conf.qsearchid];
            $.mobile.changePage(HordeMobile.createUrl('mailbox', {
                mbox: IMP.conf.qsearchid
            }));
            break;
        }
    },

    /**
     */
    beforeShow: function(e, data)
    {
        switch (HordeMobile.currentPage()) {
        case 'copymove':
            $('#imp-copymove')[0].reset();
            $('#imp-copymove-action,#imp-copymove-list').selectmenu('refresh', true);
            $('#imp-copymove-action').selectmenu(ImpMobile.cache[ImpMobile.mailbox].readonly ? 'disable' : 'enable');
            $('#imp-copymove-newdiv').hide();
            break;
        }
    },

    /**
     */
    pageShow: function(e, opts)
    {
        var tmp;

        switch (HordeMobile.currentPage()) {
        case 'message':
            $('#imp-message-more').show().siblings(':jqmData(more)').hide();

            $('#imp-message-headers,#imp-message-atc').trigger('collapse');

            tmp = $('#message .smartmobile-back');
            if (ImpMobile.mailbox == IMP.conf.qsearchid) {
                tmp.attr('href', HordeMobile.createUrl('mailbox', {
                    mbox: IMP.conf.qsearchid
                }));
                tmp.find('.ui-btn-text').text(IMP.text.searchresults);
            } else {
                tmp.attr('href', HordeMobile.createUrl('mailbox', {
                    mbox: ImpMobile.mailbox
                }));
                tmp.find('.ui-btn-text').text(ImpMobile.cache[ImpMobile.mailbox].label);
            }
            break;
        }
    },

    /**
     * Switches to the mailbox view and loads a mailbox.
     *
     * @param object data  Page change data object.
     */
    toMailbox: function(data)
    {
        var purl = data.options.parsedUrl,
            mailbox = purl.params.mbox || ImpMobile.INBOX,
            title = $('#imp-mailbox-' + mailbox).text(),
            params = {}, ob;

        document.title = title;
        $('#mailbox .smartmobile-title').text(title);
        if (ImpMobile.mailbox != mailbox) {
            $('#imp-mailbox-list').empty();
            $('#imp-mailbox-navtop').hide();
            ImpMobile.mailbox = mailbox;
        }

        if (mailbox != IMP.conf.qsearchid) {
            delete ImpMobile.search;
            $('#imp-search-input').val('');
            $('#mailbox :jqmData(role=footer) a[href$="search"]').show();
        } else if (ImpMobile.search) {
            params = ImpMobile.search;
            $('#mailbox :jqmData(role=footer) a[href$="search"]').hide();
        }

        HordeMobile.changePage('mailbox', data);

        if (ob = ImpMobile.cache[mailbox]) {
            if (purl.params.from) {
                ob.from = Number(purl.params.from);
            } else if (data.options.data && data.options.data.noajax) {
                ImpMobile.refreshMailbox(ob);
                return;
            } else {
                ImpMobile.refreshMailbox(ob);
                params.checkcache = 1;
            }
        }

        HordeMobile.doAction(
            'viewPort',
            ImpMobile.addViewportParams($.extend(params, {
                requestid: 1,
                view: mailbox
            }))
        );
    },

    /**
     */
    addViewportParams: function(params)
    {
        params = params || {};

        var from = 1, ob;

        if (ob = ImpMobile.cache[ImpMobile.mailbox]) {
            params.cache = ImpMobile.toUIDStringSingle(ImpMobile.mailbox, ob.cachedIds());
            params.cacheid = ob.cacheid;
            from = ob.from;
        }

        if (!params.search) {
            params.slice = from + ':' + (from + ImpMobile.mbox_rows - 1);
        }

        return {
            view: params.view,
            viewport: JSON.stringify(params)
        };
    },

    /**
     * Callback method to update viewport information.
     *
     * @param object r  The Ajax response object.
     */
    viewport: function(r)
    {
        var ob;

        if (!(ob = ImpMobile.cache[r.view])) {
            ob = ImpMobile.cache[r.view] = $.extend(true, {}, ImpMobileMbox);
            if (r.metadata.readonly || r.metadata.nodelete) {
                ob.readonly = 1;
            }
            ob.label = r.metadata.slabel
                ? r.metadata.slabel
                : r.label;
        }
        ob.cacheid = r.cacheid;
        if (r.data_reset) {
            ob.data = {};
        }
        if (r.rowlist_reset) {
            ob.rowlist = {};
        }
        if (r.data) {
            ob.update(r.data, r.rowlist, r.totalrows);
        }
        if (r.disappear) {
            ob.disappear(r.disappear);
        }
        if (r.rownum) {
            ob.from = (Math.floor(r.rownum / ImpMobile.mbox_rows) * ImpMobile.mbox_rows) + 1;
        }

        if (HordeMobile.currentPage() == 'mailbox') {
            ImpMobile.refreshMailbox(ob);
        }
    },

    /**
     */
    refreshMailbox: function(ob)
    {
        var list, ob, tmp,
            cid = ImpMobile.mailbox + '|' + ob.cacheid + '|' + ob.from;

        if (cid == ImpMobile.mailboxCache) {
            return;
        }
        ImpMobile.mailboxCache = cid;

        document.title = ob.label;
        $('#mailbox .smartmobile-title').text(ob.label);

        list = $('#imp-mailbox-list');
        list.empty();

        $.each(ob.rows(), function(key, data) {
            var c = $('<li class="imp-message">')
                    .jqmData('mbox', data.mbox)
                    .jqmData('uid', data.uid),
                url = HordeMobile.createUrl('message', {
                    mbox: data.mbox,
                    uid: data.uid
                });

            if (data.flag) {
                $.each(data.flag, function(k, flag) {
                    switch (flag) {
                    case IMP.conf.flags.deleted:
                        c.addClass('imp-mailbox-deleted');
                        break;

                    case IMP.conf.flags.draft:
                        url = HordeMobile.createUrl('compose', {
                            mbox: data.mbox,
                            type: 'resume',
                            uid: data.uid
                        });
                        break;

                    case IMP.conf.flags.seen:
                        c.addClass('imp-mailbox-seen');
                        break;
                    }
                });
            }

            list.append(
                c.append(
                    $('<a href="' + url + '">').html(data.subject)).append(
                    $('<div class="imp-mailbox-secondrow">').append(
                        $('<span class="imp-mailbox-date">').text(
                            data.date)).append(
                        $('<span class="imp-mailbox-from">').text(
                            data.from))));
        });

        list.listview('refresh');

        if (ob.totalrows > ImpMobile.mbox_rows) {
            $('#imp-mailbox-navtext').text(IMP.text.nav
                .replace(/%d/, ob.from)
                .replace(/%d/, Math.min(ob.from + ImpMobile.mbox_rows - 1, ob.totalrows))
                .replace(/%d/, ob.totalrows)
            );

            tmp = $('#imp-mailbox-navtop').show();
            ImpMobile.disableButton(tmp.children('a[href$="prev"]'), ob.from == 1);
            ImpMobile.disableButton(tmp.children('a[href$="next"]'), (ob.from + ImpMobile.mbox_rows - 1) >= ob.totalrows);
        } else {
            $('#imp-mailbox-navtop').hide();
        }
    },

    /**
     * Switches to the message view and loads a message.
     *
     * @param object data  Page change data object.
     */
    toMessage: function(data)
    {
        var purl = data.options.parsedUrl,
            params = {};

        if (!ImpMobile.mailbox) {
            params = {
                // Make sure we have a big enough buffer to fit all
                // messages on a page.
                after: ImpMobile.mbox_rows,
                before: ImpMobile.mbox_rows,
                requestid: 1,
                // Need to manually encode JSON here.
                search: JSON.stringify({ uid: purl.params.uid })
            };
            ImpMobile.mailbox = purl.params.mbox;
        }

        HordeMobile.changePage('message', data);

        // Page is cached.
        if (ImpMobile.uid == purl.params.uid &&
            ImpMobile.uid_mbox == purl.params.mbox) {
            document.title = $('#message .smartmobile-title').text();
            return;
        }

        $('#message').children().not('.ui-header').hide();
        $('#message .smartmobile-title').text('');
        document.title = '';

        HordeMobile.doAction(
            'smartmobileShowMessage',
            $.extend(ImpMobile.addViewportParams($.extend(params, {
                force: 1,
                view: (ImpMobile.search ? IMP.conf.qsearchid : purl.params.mbox)
            })), {
                uid: ImpMobile.toUIDStringSingle(purl.params.mbox, [ purl.params.uid ]),
            }),
            ImpMobile.messageLoaded
        );
    },

    /**
     * Navigates to the next/previous mailbox page.
     *
     * @param integer dir  Jump length.
     */
    navigateMailbox: function(dir)
    {
        var ob = ImpMobile.cache[ImpMobile.mailbox],
            from = Math.min(ob.totalrows, Math.max(1, ob.from + (dir * ImpMobile.mbox_rows)));

        if (from != ob.from) {
            $.mobile.changePage(HordeMobile.createUrl('mailbox', {
                from: from,
                mbox: ImpMobile.mailbox
            }));
        }
    },

    /**
     * Navigates to the next/previous message page.
     *
     * @param integer dir  Jump length.
     */
    navigateMessage: function(dir)
    {
        var rid,
            ob = ImpMobile.cache[ImpMobile.mailbox],
            pos = ob.rowlist[ImpMobile.rowid] + dir;

        if (pos > 0 && pos <= ob.totalrows) {
            if (rid = ob.rowToUid(pos)) {
                $.mobile.changePage(HordeMobile.createUrl('message', {
                    mbox: ob.data[rid].mbox,
                    uid: ob.data[rid].uid
                }));
            } else {
                // TODO: Load viewport slice
            }
        }
    },

    /**
     * Callback method after the message has been loaded.
     *
     * @param object r  The Ajax response object.
     */
    messageLoaded: function(r)
    {
        // TODO: Error handling.
        if (r.error ||
            !ImpMobile.message ||
            (r.view != ImpMobile.mailbox &&
             ImpMobile.mailbox != IMP.conf.qsearchid)) {
            return;
        }

        var cache = ImpMobile.cache[ImpMobile.mailbox],
            data = ImpMobile.message,
            args = { mbox: data.mbox, uid: data.uid },
            rownum;

        // TODO: Remove once we can pass viewport parameters directly to the
        // showMessage request.
        if (!cache) {
            window.setTimeout(function() { ImpMobile.messageLoaded(r); }, 0);
            return;
        }

        ImpMobile.uid = data.uid;
        ImpMobile.uid_mbox = data.mbox;

        $('#message .smartmobile-title').text(data.title);
        document.title = $('#message .smartmobile-title').text();

        if (!data.from) {
            $('#imp-message-from').text(IMP.text.nofrom);
        } else if (data.from.raw) {
            $('#imp-message-from').text(data.from.raw);
        } else if (data.from.addr[0].g) {
            $('#imp-message-from').text(data.from.addr[0].g);
        } else if (data.from.addr[0].p) {
            $('#imp-message-from').text(data.from.addr[0].p);
        } else {
            $('#imp-message-from').text(data.from.addr[0].b);
        }

        if (data.atc_label) {
            $('#imp-message-atc').show();
            $('#imp-message-atclabel').text(data.atc_label);

            ImpMobile.atc = data.atc_list;
        } else {
            $('#imp-message-atc').hide();
            delete ImpMobile.atc;
        }

        $('#imp-message-body').html(data.msgtext);
        $('#imp-message-date').text('');

        $.each(data.headers, function(k, v) {
            if (v.id == 'Date') {
                $('#imp-message-date').text(v.value);
            }
        });

        data.headers.push({ name: IMP.text.subject, value: data.subject });
        ImpMobile.headers = data.headers;

        ImpMobile.rowid = (ImpMobile.mailbox == IMP.conf.qsearchid)
            ? r.suid
            : data.uid;

        $.fn[cache.readonly ? 'hide' : 'show'].call($('#imp-message-delete'));

        /* Need to manually set href parameters for dialog links, since there
         * is no way to programatically open one. */
        if (IMP.conf.allow_folders) {
            $('#imp-message-copymove').attr('href', HordeMobile.createUrl('copymove', args));
        }

        $.each([ 'innocent', 'spam' ], function(i, v) {
            var show, t = $('#imp-message-' + v);
            if (t) {
                switch (v) {
                case 'innocent':
                    show = (ImpMobile.mailbox == IMP.conf.spam_mbox || IMP.conf.spam_innocent_spammbox);
                break;

                case 'spam':
                    show = (ImpMobile.mailbox != IMP.conf.spam_mbox || IMP.conf.spam_spammbox);
                    break;
                }

                if (show) {
                    t.jqmRemoveData('morehide')
                        .attr('href', HordeMobile.createUrl('confirm', $.extend({
                            action: v
                        }, args)));
                } else {
                    t.jqmData('morehide', true);
                }
            }
        });

        rownum = cache.rowlist[ImpMobile.rowid];
        ImpMobile.disableButton($('#imp-message-prev'), rownum == 1);
        ImpMobile.disableButton($('#imp-message-next'), rownum == cache.totalrows);

        if (data.js) {
            $.each(data.js, function(k, js) {
                $.globalEval(js);
            });
        }

        $('#message').children().not('#imp-message-atc').show();

        $.each($('#imp-message-body IFRAME.htmlMsgData'), function(k, v) {
            IMP_JS.iframeResize($(v));
        });

        delete ImpMobile.message;
    },

    /**
     */
    fullHeaders: function()
    {
        if (!ImpMobile.headers) {
            return;
        }

        var h = $('#imp-message-headers-full tbody');

        h.children().remove();

        $.each(ImpMobile.headers, function(k, header) {
            if (header.value) {
                h.append($('<tr>')
                    .append($('<td class="imp-header-label">')
                        .html(header.name + ':'))
                    .append($('<td>').html(header.value)
                ));
            }
        });

        delete ImpMobile.headers;
    },

    /**
     */
    showAttachments: function()
    {
        if (!ImpMobile.atc) {
            return;
        }

        var list = $('#imp-message-atclist').empty();

        $.each(ImpMobile.atc, function(k, v) {
            list.append(
                $('<li class="imp-message-atc"></li>').append(
                    $('<a>').attr({
                        href: v.download_url,
                        target: 'download'
                    }).append(
                        $(v.icon).addClass('ui-li-icon')
                    ).append(
                        v.description_raw + ' (' + v.size + ')'
                    )
                )
            );
        });

        list.listview('refresh');

        delete ImpMobile.atc;

        // TODO: Workaround bug(?) in jQuery Mobile where inset style is not
        // applied until listview is visible.
        window.setTimeout(function() { list.listview('refresh') }, 0);
    },

    /**
     */
    deleteMessage: function(mbox, uid)
    {
        HordeMobile.doAction(
            'deleteMessages',
            $.extend(ImpMobile.addViewportParams({
                checkcache: 1,
                force: 1,
                view: ImpMobile.mailbox
            }), {
                uid: ImpMobile.toUIDStringSingle(mbox, [ uid ])
            })
        );
    },

    /**
     */
    reportSpam: function(action, mbox, uid)
    {
        HordeMobile.doAction(
            'reportSpam',
            $.extend(ImpMobile.addViewportParams({
                checkcache: 1,
                force: 1,
                view: ImpMobile.mailbox
            }), {
                spam: Number(action == 'spam'),
                uid: ImpMobile.toUIDStringSingle(mbox, [ uid ])
            })
        );
    },

    /**
     * Switches to the compose view and loads a message if replying or
     * forwarding.
     *
     * @param object data  Page change data object.
     */
    compose: function(data)
    {
        var cache, func,
            params = {},
            purl = data.options.parsedUrl;

        $('#compose .smartmobile-title').html(IMP.text.new_message);

        if (purl.params.to || purl.params.cc) {
            $('#imp-compose-to').val(purl.params.to);
            $('#imp-compose-cc').val(purl.params.cc);
            HordeMobile.changePage('compose', data);
            return;
        }

        $('#imp-compose-form').show();
        $('#imp-redirect-form').hide();

        switch (purl.params.type) {
        case 'reply_auto':
            func = 'getReplyData';
            cache = '#imp-compose-cache';
            params.format = 'text';
            break;

        case 'forward_auto':
            func = 'smartmobileGetForwardData';
            cache = '#imp-compose-cache';
            break;

        case 'forward_redirect':
            $('#imp-compose-form').hide();
            $('#imp-redirect-form').show();
            func = 'getRedirectData';
            cache = '#imp-redirect-cache';
            break;

        case 'resume':
        case 'template':
            func = 'getResumeData';
            cache = '#imp-compose-cache';
            params.format = 'text';
            break;

        default:
            HordeMobile.changePage('compose');
            return;
        }

        HordeMobile.doAction(
            func,
            $.extend(params, {
                imp_compose: $(cache).val(),
                type: purl.params.type,
                uid: ImpMobile.toUIDStringSingle(purl.params.mbox, [ purl.params.uid ])
            }),
            function(r) { ImpMobile.composeLoaded(r, data); }
        );
    },

    /**
     * Callback method after the compose content has been loaded.
     *
     * @param object r     The Ajax response object.
     * @param object data  Page change data object.
     */
    composeLoaded: function(r, data)
    {
        if (r.imp_compose) {
            var cache = r.type == 'forward_redirect'
                ? '#imp-redirect-cache'
                : '#imp-compose-cache';
            $(cache).val(r.imp_compose);
        }

        if (r.type != 'forward_redirect') {
            if (!r.opts) {
                r.opts = {};
            }
            r.opts.noupdate = true;

            var id = (r.identity === null)
                ? $('#imp-compose-identity').val()
                : r.identity;

            $('#imp-compose-identity,#imp-compose-last-identity').val(id);
            // The first selectmenu() call is necessary to actually create the
            // selectmenu if the compose window is opened for the first time,
            // the second call to update the menu in case the selected index
            // changed.
            $('#imp-compose-identity').selectmenu()
                .selectmenu('refresh', true);

            $('#imp-compose-to').val(r.header.to);
            $('#imp-compose-cc').val(r.header.cc);
            $('#imp-compose-subject').val(r.header.subject);
            $('#imp-compose-message').val(r.body);

            $('#imp-compose-' + (r.opts.focus || 'to').replace(/composeMessage/, 'message'))[0].focus();
        }

        HordeMobile.changePage('compose', data);
    },

    uniqueSubmit: function(action)
    {
        var form = (action == 'redirectMessage')
            ? $('#imp-redirect-form')
            : $('#imp-compose-form');

        if (action == 'sendMessage' &&
            ($('#imp-compose-subject').val() == '') &&
            !window.confirm(IMP.text.nosubject)) {
            return;
        }

        HordeMobile.doAction(
            action,
            HordeJquery.formToObject(form),
            ImpMobile.uniqueSubmitCallback
        );
    },

    uniqueSubmitCallback: function(d)
    {
        if (d) {
            if (d.success) {
                return ImpMobile.closeCompose();
            }

            if (d.imp_compose) {
                $('#imp-compose-cache').val(d.imp_compose);
            }
        }
    },

    closeCompose: function()
    {
        $('#imp-compose-form')[0].reset();
        window.history.back();
    },

    /**
     * Opens a confirmation dialog.
     *
     * @param object data  Page change data object.
     */
    confirm: function(data)
    {
        var purl = data.options.parsedUrl;

        HordeMobile.changePage('confirm');

        $('#imp-confirm-text').html(IMP.text.confirm.text[purl.params.action]);
        $('#imp-confirm-action')
            .attr('href', purl.parsed.hash.replace(/\#confirm/, '\#confirmed'))
            .find('.ui-btn-text')
            .text(IMP.text.confirm.action[purl.params.action]);
    },

    /**
     * Executes confirmed actions.
     *
     * @param object data  Page change data object.
     */
    confirmed: function(data)
    {
        var purl = data.options.parsedUrl;

        switch (purl.params.action) {
        case 'innocent':
        case 'spam':
            $.mobile.changePage(HordeMobile.createUrl('mailbox', {
                mbox: purl.params.mbox
            }), {
                data: { noajax: true }
            });

            ImpMobile.reportSpam(
                purl.params.action,
                purl.params.mbox,
                purl.params.uid
            );
            break;
        }
    },

    /**
     * Opens a copy/move message dialog.
     *
     * @param object data  Page change data object.
     */
    copymove: function(data)
    {
        var purl = data.options.parsedUrl;

        HordeMobile.changePage('copymove');

        $('#imp-copymove-mbox').val(purl.params.mbox);
        $('#imp-copymove-uid').val(purl.params.uid);
    },

    /**
     * Moves or copies a message to a selected mailbox.
     *
     * @param object e  An event object.
     */
    copymoveSelected: function(e)
    {
        var source = $('#imp-copymove-mbox').val(),
            value = $(e.currentTarget).attr('id') == 'imp-copymove-list'
                ? $('#imp-copymove-list').val()
                : $('#imp-copymove-new').val(),
            move = ($('#imp-copymove-action').val() == 'move');

        if (value === '') {
            $('#imp-copymove-newdiv').show();
            return;
        }

        $('#copymove').dialog('close');

        HordeMobile.doAction(
            move ? 'moveMessages' : 'copyMessages',
            $.extend(ImpMobile.addViewportParams({
                checkcache: 1,
                force: Number(move),
                view: source
            }), {
                mboxto: value,
                newmbox: $('#imp-copymove-new').val(),
                uid: ImpMobile.toUIDStringSingle(source, [ $('#imp-copymove-uid').val() ]),
            })
        );

        if (IMP.conf.mailbox_return || move) {
            $.mobile.changePage(HordeMobile.createUrl('mailbox', {
                mbox: source
            }), {
                data: { noajax: true }
            });
        }
    },

    /**
     * Update message flags.
     *
     * @param object r  The Ajax response object.
     */
    updateFlags: function(r)
    {
        $.each(r, function(k, v) {
            $.each(ImpIndices.parseUIDString(v.uids), function(k2, v2) {
                if (ImpMobile.cache[k2] && ImpMobile.cache[k2].data[v2]) {
                    var ob = ImpMobile.cache[k2].data[v2].flag, tmp = [];
                    if (v.add) {
                        $.merge(ob, v.add);
                        $.each(ob, function(i, v) {
                            if ($.inArray(v, tmp) === -1) {
                                tmp.push(v);
                            }
                        });
                        ob = tmp;
                    }
                    if (v.remove) {
                        ob = $.grep(ob, function(n, i) {
                            return $.inArray(n, v.remove) < 0;
                        });
                    }
                }
            });
        });
    },

    /**
     * Update unseen message count for folders.
     *
     * @param object r  The Ajax response object.
     */
    updateFolders: function(r)
    {
        $.each(r, function(key, value) {
            var elt = $('#imp-mailbox-' + key);
            if (value) {
                if (!elt.siblings('.ui-li-count').size()) {
                    elt.after('<span class="ui-li-count"></span>');
                }
                elt.siblings('.ui-li-count').text(value);
            } else if (!value) {
                elt.siblings('.ui-li-count').remove();
            }
        });

        if (HordeMobile.currentPage() == 'folders') {
            $('#imp-folders-list').listview('refresh');
        }
    },

    /**
     * Are all folders shown?
     *
     * @return integer  1 if all folders are shown.
     */
    showAllFolders: function()
    {
        return $('#folders :jqmData(role=footer) a[href$="folders-showpoll"]').filter(':visible').length;
    },

    /**
     * Converts an object to an IMP UID range string.
     *
     * @param object ob  Mailbox name as keys, values are array of uids.
     *
     * @return string  The UID range string.
     */
    toUIDString: function(ob)
    {
        var str = '';

        $.each(ob, function(key, value) {
            if (!value.length) {
                return;
            }

            if (IMP.conf.pop3) {
                $.each(value, function(pk, pv) {
                    str += '{P' + pv.length + '}' + pv;
                });
            } else {
                var u = value.numericSort(),
                    first = u.shift(),
                    last = first,
                    out = [];

                $.each(u, function(n, k) {
                    if (last + 1 == k) {
                        last = k;
                    } else {
                        out.push(first + (last == first ? '' : (':' + last)));
                        first = last = k;
                    }
                });
                out.push(first + (last == first ? '' : (':' + last)));
                str += '{' + key.length + '}' + key + out.join(',');
            }
        });

        return str;
    },

    /**
     */
    toUIDStringSingle: function(mbox, uid)
    {
        var o = {};
        o[mbox] = uid;
        return ImpMobile.toUIDString(o);
    },

    /**
     */
    disableButton: function(btn, disable)
    {
        if (disable) {
            btn.addClass('ui-disabled').attr('aria-disabled', true);
        } else {
            btn.removeClass('ui-disabled').attr('aria-disabled', false);
        }
    },

    /**
     */
    runTasks: function(e, d)
    {
        $.each(d, function(key, value) {
            switch (key) {
            case 'imp:flag':
                ImpMobile.updateFlags(value);
                break;

            case 'imp:message':
                ImpMobile.message = value.shift();
                break;

            case 'imp:poll':
                ImpMobile.updateFolders(value);
                break;

            case 'imp:viewport':
                ImpMobile.viewport(value);
                break;
            }
        });
    },

    /**
     */
    swipeButtons: function(e, ob)
    {
        $.each($('#imp-mailbox-buttons').children(), function(k, v) {
            var add = true;
            v = $(v);

            switch (v.jqmData('swipe')) {
            case 'delete':
                add = !ImpMobile.cache[ImpMobile.mailbox].readonly;
                break;

            case 'innocent':
                add = (ImpMobile.mailbox == IMP.conf.spam_mbox || IMP.conf.spam_innocent_spammbox);
                break;

            case 'spam':
                add = (ImpMobile.mailbox != IMP.conf.spam_mbox || IMP.conf.spam_spammbox);
                break;
            }

            if (add) {
                ob.buttons.push(v.clone(true));
            }
        });
    },

    /**
     * Event handler for the document-ready event, responsible for the initial
     * setup.
     */
    onDocumentReady: function()
    {
        $(document).bind('pagebeforechange', ImpMobile.toPage);
        $(document).bind('pagebeforeshow', ImpMobile.beforeShow);
        $(document).bind('pagechange', ImpMobile.pageShow);
        $(document).bind('HordeMobile:runTasks', ImpMobile.runTasks);

        $('#imp-mailbox-list').swipebutton()
            .on('swipebutton', 'li', ImpMobile.swipeButtons);

        $('#message').on('swipeleft', function() {
            $.mobile.changePage('#message-next');
        }).on('swiperight', function() {
            $.mobile.changePage('#message-prev');
        });

        $('#imp-message-headers').on('expand', ImpMobile.fullHeaders);
        $('#imp-message-atc').on('expand', ImpMobile.showAttachments);

        if (!IMP.conf.disable_compose) {
            $.each([ 'to', 'cc' ], function(undefined, v) {
                $('#imp-compose-' + v).autocomplete({
                    callback: function(e) {
                        $('#imp-compose-' + v).val($(e.currentTarget).text());
                    },
                    link: '#',
                    minLength: 3,
                    source: 'smartmobileAutocomplete',
                    target: $('#imp-compose-' + v + '-suggestions')
                });
            });
        }

        if (IMP.conf.allow_folders) {
            $('#imp-copymove-list').on('change', ImpMobile.copymoveSelected);
        }
    }

};

// JQuery Mobile setup
$(ImpMobile.onDocumentReady);


var ImpMobileMbox = {
    // Vars used: cacheid, label, readonly
    data: {},
    from: 1,
    rowlist: {},
    totalrows: 0,

    update: function(data, rowlist, totalrows)
    {
        if (data.length !== 0) {
            $.extend(this.data, data);
        }
        if (rowlist.length !== 0) {
            $.extend(this.rowlist, rowlist);
        }
        this.totalrows = totalrows;
    },

    cachedIds: function()
    {
        var ids = [];

        $.each(this.data, function(key, value) {
            ids.push(key);
        });

        return ids;
    },

    disappear: function(ids)
    {
        if (!ids.length) {
            return;
        }

        var t = this;

        $.each(ids, function(key, value) {
            delete t.data[value];
        });
    },

    rows: function(start)
    {
        start = start || this.from;

        var mbox_data = this.data,
            end = Math.min(start + ImpMobile.mbox_rows - 1, this.totalrows);

        return $.map($.map(this.rowlist, function(value, key) {
            return (value >= start && value <= end)
                ? { sort: value, uid: key }
                : null;
        }).sort(function(a, b) {
            return (a.sort < b.sort) ? -1 : 1;
        }), function(value, key) {
            return mbox_data[value.uid]
        });
    },

    rowToUid: function(row)
    {
        var uid = undefined;

        if (row >= 0 && row <= this.totalrows) {
            $.each(this.rowlist, function(u, p) {
                if (p == row) {
                    uid = u;
                    return;
                }
            });
        }

        return uid;
    }

};


var IMP_JS = {

    iframeInject: function(id, data)
    {
        id = $('#' + id);
        var d = id.get(0).contentWindow.document;

        id.bind('load', function() {
            id.unbind('load');
            IMP_JS.iframeResize(id);
        });

        d.open();
        d.write(data);
        d.close();

        id.show().prev().remove();
    },

    iframeResize: function(id)
    {
        $(id).height(Math.max(
            $(id.get(0).contentWindow.document.lastChild).height(),
            $(id.get(0).contentWindow.document.body).height()
        ) + 25);
    }

};
