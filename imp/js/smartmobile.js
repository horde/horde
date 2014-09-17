/**
 * jQuery Mobile UI application logic.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2005-2014 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */
var ImpMobile = {

    // Vars used and defaulting to null/false:
    //
    // /* Attachment data for the current message. */
    // atc,
    //
    // /* BUID of the currently displayed message. */
    // buid,
    //
    // /* The current list of compose attachments. */
    // composeatc,
    //
    // /* The current compose type. */
    // composetype,
    //
    // /* Flag information. */
    // flags,
    //
    // /* Has the folders list been loaded? */
    // foldersLoaded,
    //
    // /* Header data for the current message. */
    // headers,
    //
    // /* Is the current message from a list? */
    // listmsg,
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
    // /* Current handle for the refresh interval action. */
    // refresh,
    //
    // /* Current row UID of the displayed message. */
    // rowid,
    //
    // /* Search parameters for the viewPort Ajax request. */
    // search,

    // Mailbox data cache.
    cache: {},

    // The scroll location of the current mailbox.
    mailboxTop: 0,

    // Rows per mailbox slice.
    mbox_slice: 30,

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
        var tmp, view = data.options.parsedUrl.view;

        if ($.type(data.toPage) !== "string") {
            switch (HordeMobile.currentPage()) {
            case 'folders':
                ImpMobile.mailboxTop = 0;
                break;

            case 'mailbox':
                ImpMobile.mailboxTop = $(window).scrollTop();
                break;
            }

            return;
        }

        switch (view) {
        case 'compose':
            $('#imp-compose-to-addr,#imp-compose-cc-addr').empty();
            if (!IMP.conf.disable_compose) {
                $('#imp-compose-cache,#imp-compose-hmac').val('');
                ImpMobile.compose(data);
            }
            e.preventDefault();
            break;

        case 'compose-attach':
            ImpMobile.composeAttachRefresh();
            $('#imp-compose-attach').popup('open');
            e.preventDefault();
            break;

        case 'compose-cancel':
        case 'compose-discard':
            HordeMobile.doAction('cancelCompose', {
                discard: ~~(view == 'compose-discard'),
                imp_compose: $('#imp-compose-cache').val()
            });
            ImpMobile.closeCompose();
            e.preventDefault();
            break;

        case 'compose-delete-addr':
            data.options.link.next().remove();
            data.options.link.remove();
            e.preventDefault();
            break;

        case 'compose-draft':
        case 'compose-submit':
            ImpMobile.uniqueSubmit(
                (view == 'compose-draft')
                    ? 'saveDraft'
                    : ($('#imp-compose-form').is(':hidden') ? 'redirectMessage' : 'smartmobileSendMessage')
            );
            e.preventDefault();
            break;

        case 'copymove':
            if (IMP.conf.allow_folders) {
                ImpMobile.copymove(data);
            }
            e.preventDefault();
            break;

        case 'copymove-submit':
            ImpMobile.copymoveSelected();
            e.preventDefault();
            break;

        case 'folders-refresh':
            $('#folders :jqmData(role=footer) a[href$="refresh"]').removeClass($.mobile.activeBtnClass).blur();
            e.preventDefault();
            // Fall-through

        case 'folders':
            if (ImpMobile.foldersLoaded) {
                HordeMobile.doAction('poll', {
                    poll: JSON.stringify([])
                });
            }
            break;

        case 'folders-showall':
        case 'folders-showpoll':
            $('#folders-more :jqmData(role=listview) a[href*="folders-show"]').closest('li').toggle();
            // Fall-through

        case 'folders-rebuild':
            ImpMobile.loadFolders();
            e.preventDefault();
            break;

        case 'mailbox':
            ImpMobile.toMailbox(data);
            e.preventDefault();
            break;

        case 'mailbox-delete':
            ImpMobile.deleteMessage(
                (data.options.data || data.options.link).jqmData('buid')
            );
            e.preventDefault();
            break;

        case 'mailbox-innocent':
        case 'mailbox-spam':
            ImpMobile.reportSpam(
                view.match(/spam$/) ? 'spam' : 'innocent',
                (data.options.data || data.options.link).jqmData('buid')
            );
            e.preventDefault();
            break;

        case 'mailbox-more':
            ImpMobile.cache[ImpMobile.mailbox].slice += ImpMobile.mbox_slice;
            $.mobile.changePage(HordeMobile.createUrl('mailbox', {
                mbox: ImpMobile.mailbox
            }), {
                data: { force: true }
            });
            e.preventDefault();
            break;

        case 'mailbox-refresh':
            ImpMobile.refreshMailbox();
            e.preventDefault();
            $('#mailbox :jqmData(role=footer) a[href$="refresh"]').removeClass($.mobile.activeBtnClass).blur();
            break;

        case 'message':
            ImpMobile.toMessage(data);
            e.preventDefault();
            break;

        case 'message-delete':
            ImpMobile.deleteMessage(ImpMobile.buid);
            $.mobile.changePage(HordeMobile.createUrl('mailbox', {
                mbox: ImpMobile.mailbox
            }), {
                data: { noajax: true }
            });
            e.preventDefault();
            return;

        case 'message-forward':
            $.mobile.changePage(HordeMobile.createUrl('compose', {
                buid: ImpMobile.buid,
                mbox: ImpMobile.mailbox,
                type: 'forward_auto'
            }));
            e.preventDefault();
            break;

        case 'message-next':
        case 'message-prev':
            ImpMobile.navigateMessage(view.match(/next$/) ? 1 : -1);
            e.preventDefault();
            break;

        case 'message-redirect':
            $.mobile.changePage(HordeMobile.createUrl('compose', {
                buid: ImpMobile.buid,
                mbox: ImpMobile.mailbox,
                type: 'forward_redirect'
            }));
            e.preventDefault();
            break;

        case 'message-reply-all':
        case 'message-reply-auto':
        case 'message-reply-list':
        case 'message-reply-sender':
            tmp = 'reply';
            switch (view) {
            case 'message-reply-all':
                tmp += '_all';
                break;

            case 'message-reply-auto':
                tmp += '_auto';
                break;

            case 'message-reply-list':
                tmp += '_list';
                break;
            }

            $.mobile.changePage(HordeMobile.createUrl('compose', {
                buid: ImpMobile.buid,
                mbox: ImpMobile.mailbox,
                type: tmp
            }));
            e.preventDefault();
            break;

        case 'message-innocent':
        case 'message-spam':
            $(view.match(/innocent/) ? '#imp-innocent-confirm' : '#imp-spam-confirm').popup('open');
            e.preventDefault();
            break;

        case 'message-innocent-confirm':
        case 'message-spam-confirm':
            $.mobile.changePage(HordeMobile.createUrl('mailbox', {
                mbox: ImpMobile.mailbox
            }), {
                data: { noajax: true }
            });

            ImpMobile.reportSpam(
                (view.match(/innocent/) ? 'innocent' : 'spam'),
                ImpMobile.buid
            );
            e.preventDefault();
            break;

        case 'search-exit':
            $('#mailbox .smartmobile-back').removeClass($.mobile.activeBtnClass).blur();
            $.mobile.changePage(HordeMobile.createUrl('mailbox', {
                mbox: data.options.parsedUrl.params.mbox
            }));
            e.preventDefault();
            break;

        case 'search-submit':
            ImpMobile.search = {
                qsearch: $('#imp-search-input').val(),
                qsearchfield: $('#imp-search-by').val(),
                qsearchmbox: (ImpMobile.search ? ImpMobile.search.qsearchmbox : ImpMobile.mailbox)
            };
            delete ImpMobile.mailboxCache;
            delete ImpMobile.cache[IMP.conf.qsearchid];
            $.mobile.changePage(HordeMobile.createUrl('mailbox', {
                mbox: IMP.conf.qsearchid
            }));
            e.preventDefault();
            break;

        case 'unblock-image':
            IMP_JS.unblockImages(data.options.link.closest('DIV.mimePartBase').find('IFRAME.htmlMsgData'));
            data.options.link.remove();
            e.preventDefault();
            break;
        }
    },

    /**
     */
    beforeShow: function(undefined, data)
    {
        switch (HordeMobile.currentPage()) {
        case 'compose':
            if (!$('#compose-more ul').children().size()) {
                $('#compose :jqmData(role=footer) a[href$="compose-more"]').hide();
            }
            break;

        case 'copymove':
            if (!$('#imp-copymove-list').children().size()) {
                HordeMobile.doAction(
                    'copyMoveMailboxList',
                    {},
                    function(r) {
                        $('#imp-copymove-list')
                            .html(r)
                            .change(function() {
                                $.fn[$(this[this.selectedIndex]).hasClass('flistCreate') ? 'show' : 'hide'].call($('#imp-copymove-newdiv'));
                            })
                            .find('.flistMailboxes OPTION[value=""]').attr('disabled', true);
                    },
                    { async: false }
                );
            }

            $('#imp-copymove')[0].reset();
            $('#imp-copymove-action,#imp-copymove-list').selectmenu('refresh', true);
            $('#imp-copymove-action').selectmenu(ImpMobile.cache[ImpMobile.mailbox].readonly ? 'disable' : 'enable');
            $('#imp-copymove-newdiv').hide();
            break;

        case 'search':
            if (!ImpMobile.search) {
                $('#imp-search-form').trigger('reset').find('select').selectmenu('refresh');
            }
            break;
        }
    },

    /**
     */
    pageShow: function()
    {
        switch (HordeMobile.currentPage()) {
        case 'folders':
            if (!ImpMobile.foldersLoaded) {
                ImpMobile.loadFolders();
            }
            break;

        case 'mailbox':
            $.mobile.silentScroll(ImpMobile.mailboxTop);

            // Need to do here since Exit Search does not trigger beforeShow.
            $.fn[ImpMobile.search ? 'hide' : 'show'].call($('#imp-mailbox-search'));
            $.fn[ImpMobile.search ? 'show' : 'hide'].call($('#imp-mailbox-searchedit'));
            break;

        case 'message':
            $('#imp-message-headers,#imp-message-atc').trigger('collapse');
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
            noajax = purl.params.noajax || (data.options.data && data.options.data.noajax),
            title = $('#imp-mailbox-' + mailbox).text(),
            params, ob;

        document.title = title;
        $('#mailbox .smartmobile-title').text(title);

        if (ImpMobile.mailbox != mailbox) {
            $('#imp-mailbox-list').empty();

            if (mailbox == IMP.conf.qsearchid) {
                if (!ImpMobile.search) {
                    $.mobile.changePage(HordeMobile.createUrl('mailbox', {
                        mbox: ImpMobile.INBOX
                    }));
                    return;
                }

                $('#mailbox .smartmobile-back').attr(
                    'href',
                    HordeMobile.createUrl('search-exit', {
                        mbox: ImpMobile.search.qsearchmbox
                    })
                ).find('.ui-btn-text').text(IMP.text.exitsearch);
            } else if (ImpMobile.search) {
                delete ImpMobile.search;
                $('#mailbox .smartmobile-back').attr('href', '#folders')
                    .find('.ui-btn-text').text(IMP.text.folders);
                HordeMobile.updateHash(purl);
            }

            ImpMobile.mailbox = mailbox;
        }

        params = ImpMobile.search || {};

        HordeMobile.changePage('mailbox', data);

        if ((!data.options.data || !data.options.data.force) &&
            (ob = ImpMobile.cache[mailbox])) {
            ImpMobile.refreshMailbox(ob);
            if (noajax) {
                return;
            }
            params.checkcache = 1;
        }

        HordeMobile.doAction(
            'viewport',
            ImpMobile.addViewportParams($.extend(params, {
                view: mailbox
            }))
        );
    },

    /**
     */
    addViewportParams: function(params)
    {
        params = params || {};

        var p, slice,
            ob = ImpMobile.cache[ImpMobile.mailbox];

        if (ob) {
            params.cache = ImpMobile.toUidString(ob.cachedIds());
            params.cacheid = ob.cacheid;
            slice = ob.slice;
        } else {
            params.initial = 1;
            params.force = 1;
            slice = ImpMobile.mbox_slice;
        }

        if (!params.search) {
            params.slice = '1:' + slice;
        }

        p = {
            view: params.view,
            viewport: JSON.stringify(params)
        };

        if (!ImpMobile.flags) {
            p.flag_config = 1;
        }

        return p;
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
            ob.slice = ImpMobile.mbox_slice;
            if (r.metadata.readonly || r.metadata.nodelete) {
                ob.readonly = 1;
            }
            ob.innocent = r.metadata.innocent_show;
            ob.label = r.metadata.slabel
                ? r.metadata.slabel
                : r.label;
            ob.spam = r.metadata.spam_show;
        }
        ob.cacheid = r.cacheid;
        if (r.data_reset) {
            ob.data = {};
        }
        if (r.rowlist_reset) {
            ob.rowlist = {};
            ob.totalrows = 0;
        }
        if (r.data) {
            ob.update(r.data, r.rowlist, r.totalrows);
        }
        if (r.disappear) {
            ob.disappear(r.disappear);
        }

        ImpMobile.resetRefresh();
        ImpMobile.refreshMailbox(ob);
    },

    /**
     * Refresh the current mailbox.
     *
     * @param object ob  If ob is not present, triggers an AJAX refresh.
     *                   Otherwise, rebuilds mailbox using ob.
     */
    refreshMailbox: function(ob)
    {
        if (!ob) {
            if (HordeMobile.currentPage() != 'folders') {
                HordeMobile.doAction(
                    'viewPort',
                    ImpMobile.addViewportParams({
                        checkcache: 1,
                        view: ImpMobile.mailbox
                    })
                );
            }
            return;
        }

        if (HordeMobile.currentPage() != 'mailbox') {
            return;
        }

        var list, tmp,
            cid = ImpMobile.mailbox + '|' + ob.cacheid + '|' + ob.slice;

        if (cid == ImpMobile.mailboxCache) {
            return;
        }
        ImpMobile.mailboxCache = cid;

        document.title = ob.label;
        $('#mailbox .smartmobile-title').text(ob.label);

        list = $('#imp-mailbox-list')
            .empty()
            .append(tmp = $('<li data-role="list-divider"></li>'));

        switch (ob.totalrows) {
        case 0:
            tmp.text(IMP.text.message_0);
            break;

        case 1:
            tmp.text(IMP.text.message_1);
            break;

        default:
            tmp.text(IMP.text.message_2.replace('%d', ob.totalrows));
            break;
        }

        $.each(ob.rows(), function(key, val) {
            var c = $('<li class="imp-message"></li>')
                    .jqmData('buid', val.buid),
                labels = [],
                tmp,
                url = HordeMobile.createUrl('message', {
                    buid: val.buid,
                    mbox: ImpMobile.mailbox
                });

            if (val.data.flag) {
                $.each(val.data.flag, function(k, flag) {
                    switch (flag) {
                    case IMP.conf.flags.deleted:
                        c.addClass('imp-mailbox-deleted');
                        break;

                    case IMP.conf.flags.draft:
                        url = HordeMobile.createUrl('compose', {
                            buid: val.buid,
                            mbox: ImpMobile.mailbox,
                            type: 'resume'
                        });
                        break;

                    case IMP.conf.flags.seen:
                        c.addClass('imp-mailbox-seen');
                        break;

                    default:
                        /* All other flags are added as labels. */
                        if (ImpMobile.flags[flag]) {
                            tmp = ImpMobile.flags[flag];
                            if (tmp.s) {
                                labels.push(
                                    $('<span></span>')
                                        .addClass('imp-mailbox-label')
                                        .css({
                                            background: (tmp.b ? tmp.b : ''),
                                            color: tmp.f
                                        })
                                        .text(tmp.l)
                                );
                            }
                        }
                        break;
                    }
                });
            }

            list.append(
                c.append(
                    $('<a href="' + url + '"></a>').text(val.data.subject)
                ).append(
                    $('<div class="imp-mailbox-secondrow"></div>').append(
                        $('<span class="imp-mailbox-date"></span>').text(
                            val.data.date
                        )
                    ).append(
                        $('<span class="imp-mailbox-from"></span>').text(
                            val.data.from
                        ).append(labels)
                    )
                )
            );
        });

        if (ob.totalrows > ob.slice) {
            list.append($('<li class="imp-mailbox-more"></li>').append(
                $('<a href="#mailbox-more"></a>').text(
                    IMP.text.more_msgs)));
        }

        list.listview('refresh');
    },

    /**
     * Resets the current mailbox refresh interval.
     */
    resetRefresh: function()
    {
        if (IMP.conf.refresh_time) {
            window.clearInterval(ImpMobile.refresh);
            ImpMobile.refresh = window.setInterval(
                ImpMobile.refreshMailbox,
                IMP.conf.refresh_time * 1000
            );
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
            ImpMobile.mailbox = purl.params.mbox;
        }

        HordeMobile.changePage('message', data);

        // Page is cached.
        if (ImpMobile.buid == purl.params.buid &&
            ImpMobile.mailbox == purl.params.mbox) {
            document.title = $('#message .smartmobile-title').text();
            return;
        }

        $('#message :jqmData(role=content)').hide();
        $('#message .smartmobile-title').text('');
        document.title = '';

        HordeMobile.doAction(
            'showMessage',
            $.extend(ImpMobile.addViewportParams($.extend(params, {
                view: purl.params.mbox
            })), {
                buid: purl.params.buid
            }),
            ImpMobile.messageLoaded
        );
    },

    /**
     * Navigates to the next/previous message page.
     *
     * @param integer dir  Jump length.
     */
    navigateMessage: function(dir)
    {
        var buid,
            ob = ImpMobile.cache[ImpMobile.mailbox],
            pos = ob.rowlist[ImpMobile.rowid] + dir;

        if (pos > 0 &&
            pos <= ob.totalrows &&
            (buid = ob.rowToBuid(pos))) {
            $.mobile.changePage(HordeMobile.createUrl('message', {
                buid: buid,
                mbox: ImpMobile.mailbox
            }));
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
            (r.view != ImpMobile.mailbox)) {
            return;
        }

        var cache = ImpMobile.cache[ImpMobile.mailbox],
            data = ImpMobile.message,
            tmp;

        // TODO: Remove once we can pass viewport parameters directly to the
        // showMessage request.
        if (!cache) {
            window.setTimeout(function() { ImpMobile.messageLoaded(r); }, 0);
            return;
        }

        ImpMobile.buid = r.buid;

        document.title = data.title;
        $('#message .smartmobile-title').text(data.title);

        tmp = (ImpMobile.mailbox == IMP.conf.qsearchid);
        $('#message .smartmobile-back').attr(
            'href',
            HordeMobile.createUrl('mailbox', {
                mbox: (tmp ? IMP.conf.qsearchid : ImpMobile.mailbox),
                noajax: 1
            })
        ).find('.ui-btn-text').text(
            tmp ? IMP.text.searchresults : cache.label
        );

        $('#imp-message-from').text(
            data.from
                ? (data.from.raw || data.from.addr[0].g || data.from.addr[0].p || data.from.addr[0].b)
                : IMP.text.nofrom
        );

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
        ImpMobile.headers = {
            Cc: data.cc,
            From: data.from,
            headers: data.headers,
            To: data.to
        };
        ImpMobile.listmsg = (data.list_info && data.list_info.exists);
        ImpMobile.rowid = r.buid;

        $.fn[cache.readonly ? 'hide' : 'show'].call($('#imp-message-delete'));

        if (data.md) {
            $.each(data.md, function(undefined, md) {
                switch (md[0]) {
                case 'html':
                    IMP_JS.iframeInject(md[1], md[2]);
                    $("#imp-message-body a[href='#unblock-image']").button();
                    break;
                }
            });
        }

        if (data.js) {
            $.each(data.js, function(k, js) {
                $.globalEval(js);
            });
        }

        $('#message :jqmData(role=content)').show();

        tmp = $('#imp-message-headers');
        $.mobile.silentScroll(parseInt(tmp.position().top, 10) + parseInt(tmp.height(), 10) - $('#message > :jqmData(role=header)').height());

        delete ImpMobile.message;
    },

    /**
     */
    messageMorePopup: function(list)
    {
        var cache = ImpMobile.cache[ImpMobile.mailbox],
            row = cache.rowlist[ImpMobile.rowid];

        list.children().each(function() {
            var elt = $(this),
                a = elt.find('a:first'),
                id = a.attr('id');

            if (id) {
                switch (id) {
                case 'imp-message-copymove':
                    /* Need to manually set href parameters for dialog links,
                     * since there is no way to programatically open one. */
                    a.attr('href', HordeMobile.createUrl('copymove', {
                        buid: ImpMobile.buid,
                        mbox: ImpMobile.mailbox
                    }));
                    break;

                case 'imp-message-innocent':
                    $.fn[!cache.innocent ? 'hide' : 'show'].call(elt);
                    break;

                case 'imp-message-next':
                    $.fn[(row == cache.maxRow()) ? 'hide' : 'show'].call(elt);
                    break;

                case 'imp-message-prev':
                    $.fn[(row == 1) ? 'hide' : 'show'].call(elt);
                    break;

                case 'imp-message-spam':
                    $.fn[!cache.spam ? 'hide' : 'show'].call(elt);
                    break;
                }
            }
        });
    },

    /**
     */
    messageReplyPopup: function(list)
    {
        $.fn[ImpMobile.listmsg ? 'show' : 'hide'].call(list.find('a[href$="message-reply-list"]').parents('li'));
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

        $.each(ImpMobile.headers.headers, function(k, header) {
            var td, tmp;

            if (header.value) {
                td = $('<td class="imp-header-label-value"></td>');

                switch (header.id) {
                case 'Cc':
                case 'From':
                case 'To':
                    tmp = ImpMobile.headers[header.id];

                    if (tmp.raw) {
                        td.html(tmp.raw);
                    } else {
                        $.each(tmp.addr, function(k2, addr) {
                            if (addr.g) {
                            } else if (addr.p || addr.b) {
                                td.append(
                                    $('<a></a>')
                                        .attr('href', HordeMobile.createUrl('compose', { to: addr.b }))
                                        .attr('data-role', 'button')
                                        .attr('data-theme', 'b')
                                        .text(addr.p ? (addr.p + ' <' + addr.b + '>') : addr.b)
                                        .button()
                                );
                            }
                        });
                    }
                    break;

                default:
                    td.html(header.value);
                    break;
                }

                h.append($('<tr></tr>')
                    .append($('<td class="imp-header-label"></td>')
                        .html(header.name + ':'))
                    .append(td)
                );
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
                    $('<a></a>').attr({
                        href: v.download_url,
                        rel: 'external'
                    }).append(
                        $(v.icon).addClass('ui-li-icon')
                    ).append(
                        document.createTextNode(v.description_raw + ' (' + v.size + ')')
                    )
                )
            );
        });

        list.listview('refresh');

        delete ImpMobile.atc;

        // TODO: Workaround bug(?) in jQuery Mobile where inset style is not
        // applied until listview is visible.
        window.setTimeout(function() { list.listview('refresh'); }, 0);
    },

    /**
     */
    deleteMessage: function(buid)
    {
        HordeMobile.doAction(
            'deleteMessages',
            $.extend(ImpMobile.addViewportParams({
                checkcache: 1,
                force: 1,
                view: ImpMobile.mailbox
            }), {
                buid: buid
            })
        );
    },

    /**
     */
    reportSpam: function(action, buid)
    {
        HordeMobile.doAction(
            'reportSpam',
            $.extend(ImpMobile.addViewportParams({
                checkcache: 1,
                force: 1,
                view: ImpMobile.mailbox
            }), {
                buid: buid,
                spam: ~~(action == 'spam')
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
        var func,
            params = {},
            purl = data.options.parsedUrl;

        $('#compose .smartmobile-title').html(IMP.text.new_message);

        if (purl.params.to || purl.params.cc) {
            ImpMobile.addAddress('to', purl.params.to);
            ImpMobile.addAddress('cc', purl.params.cc);
        }

        $('#imp-compose-form').show();
        $('#imp-redirect-form').hide();

        ImpMobile.composeatc = [];
        ImpMobile.composetype = purl.params.type;

        switch (purl.params.type) {
        case 'reply':
        case 'reply_all':
        case 'reply_auto':
        case 'reply_list':
            func = 'getReplyData';
            params.format = 'text';
            break;

        case 'forward_auto':
            func = 'smartmobileGetForwardData';
            params.format = 'text';
            break;

        case 'forward_redirect':
            $('#imp-compose-form').hide();
            $('#imp-redirect-form').show();
            func = 'getRedirectData';
            break;

        case 'resume':
        case 'template':
            func = 'getResumeData';
            params.format = 'text';
            break;

        default:
            HordeMobile.changePage('compose', data);
            return;
        }

        HordeMobile.doAction(
            func,
            $.extend(params, {
                buid: purl.params.buid,
                type: purl.params.type,
                view: purl.params.mbox
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

            if (r.addr) {
                $.each(r.addr.to, function(k, v) {
                    ImpMobile.addAddress('to', v);
                });
                $.each(r.addr.cc, function(k, v) {
                    ImpMobile.addAddress('cc', v);
                });
            }

            $('#imp-compose-subject').val(r.subject);
            $('#imp-compose-message').val(r.body);

            $('#imp-compose-' + (r.opts.focus || 'to').replace(/composeMessage/, 'message'))[0].focus();
        }

        HordeMobile.changePage('compose', data);
    },

    /**
     */
    composeMorePopup: function(list)
    {
        list.children().each(function() {
            var elt = $(this),
                id = elt.find('a:first').attr('id');

            switch (id) {
            case 'imp-compose-discard':
                $.fn[(ImpMobile.composetype != 'resume') ? 'hide' : 'show'].call(elt);
                break;
            }
        });
    },

    /**
     */
    composeAttachRefresh: function()
    {
        var list = $('#imp-compose-attach :jqmData(role=listview)').empty();

        $.each(ImpMobile.composeatc, function(k, v) {
            list.append(
                $('<li class="imp-compose-atc"></li>').append(
                    $('<img></img>')
                        .addClass('ui-li-icon')
                        .attr('src', v.icon)
                ).append(
                    v.name + ' (' + v.size + ')'
                )
            );
        });

        list.listview('refresh').siblings('FORM')[0].reset();
    },

    /**
     */
    addAddress: function(f, addr)
    {
        if (addr) {
            $('#imp-compose-' + f + '-addr').prepend(
                $('<input></input>')
                    .attr('name', f + '[]')
                    .attr('type', 'hidden')
                    .val(addr)
            ).prepend(
                $('<a></a>')
                    .attr('href', '#compose-delete-addr')
                    .attr('data-role', 'button')
                    .attr('data-icon', 'delete')
                    .attr('data-iconpos', 'right')
                    .attr('data-theme', 'b')
                    .text(addr)
                    .button()
            );
        }
    },

    /**
     */
    processComposeAddress: function(e, prev)
    {
        var chr, pos = 0, tmp,
            val = $(this).val();

        if (val.length > prev.length) {
            // Optimization when only one character was added
            if (val.length - prev.length == 1) {
                tmp = val.charAt(val.length - 1);
                if (tmp != ',' && tmp != ';') {
                    return;
                }
            }

            chr = val.charAt(pos);
            while (chr !== "") {
                switch (chr) {
                case ',':
                case ';':
                    if (!pos) {
                        val = val.substr(1);
                    } else if (val.charAt(pos - 1) != '\\') {
                        ImpMobile.addAddress($(this).attr('id') == 'imp-compose-to' ? 'to' : 'cc', $.trim(val.substr(0, chr == ';' ? pos + 1 : pos)));
                        val = val.substr(pos + 2);
                        pos = 0;
                    }
                    break;

                default:
                    ++pos;
                    break;
                }

                chr = val.charAt(pos);
            }

            $(this).val($.trim(val));
        }
    },

    /**
     * Load the folders list.
     */
    loadFolders: function()
    {
        ImpMobile.foldersLoaded = false;

        HordeMobile.doAction(
            'smartmobileFolderTree',
            { all: ImpMobile.showAllFolders() },
            function(r) {
                $('#imp-folders-list').html(r).listview('refresh');
                ImpMobile.foldersLoaded = true;
            }
        );
    },

    uniqueSubmit: function(action)
    {
        var form = (action == 'redirectMessage')
            ? $('#imp-redirect-form')
            : $('#imp-compose-form');

        if (action == 'sendMessage' &&
            $('#imp-compose-subject').val().empty() &&
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
        if (d && d.success) {
            ImpMobile.closeCompose();
        }
    },

    closeCompose: function()
    {
        $('#imp-compose-form')[0].reset();
        $.each($('#imp-compose-to,#imp-compose-cc'), function(undefined, v) {
            $(v).autocomplete('clear');
        });
        window.history.back();
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

        $('#imp-copymove-buid').val(purl.params.buid);
        $('#imp-copymove-mbox').val(purl.params.mbox);
    },

    /**
     * Moves or copies a message to a selected mailbox.
     */
    copymoveSelected: function()
    {
        var opts = {},
            cmlist = $('#imp-copymove-list'),
            source = $('#imp-copymove-mbox').val(),
            move = ($('#imp-copymove-action').val() == 'move');

        if (cmlist.find(':selected').hasClass('flistCreate')) {
            opts.newmbox = $.trim($('#imp-copymove-new').val());
            if (opts.newmbox === "") {
                window.alert(IMP.text.move_nombox);
                return;
            }
        } else {
            opts.mboxto = cmlist.val();
        }

        HordeMobile.doAction(
            move ? 'moveMessages' : 'copyMessages',
            $.extend(ImpMobile.addViewportParams({
                checkcache: 1,
                force: ~~(move),
                view: source
            }), opts, {
                buid: $('#imp-copymove-buid').val()
            })
        );

        if (IMP.conf.mailbox_return || move) {
            $.mobile.changePage(HordeMobile.createUrl('mailbox', {
                mbox: source
            }), {
                data: { noajax: true }
            });
        } else {
            $('#copymove').dialog('close');
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
            $.each(v.buids, function(k2, v2) {
                var c = ImpMobile.cache[k2];

                if (c) {
                    $.each(ImpMobile.fromUidString(v2), function(k3, v3) {
                        if (c.data[v3]) {
                            var ob = c.data[v3].flag, tmp = [];
                            if (v.add) {
                                $.merge(ob, v.add);
                                $.each(ob, function(i, v4) {
                                    if ($.inArray(v4, tmp) === -1) {
                                        tmp.push(v4);
                                    }
                                });
                                ob = tmp;
                            }
                            if (v.remove) {
                                ob = $.grep(ob, function(n) {
                                    return $.inArray(n, v.remove) < 0;
                                });
                            }
                        }
                    });
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
        /* If redrawing the folders via smartmobileFolderTree action, this
         * task will be executed before the list is refreshed. */
        if (!ImpMobile.foldersLoaded &&
            (HordeMobile.currentPage() == 'folders')) {
            window.setTimeout(function() { ImpMobile.updateFolders(r); }, 100);
        }

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
        return $('#folders-more :jqmData(role=listview) a[href$="folders-showpoll"]').closest('li').filter(':visible').length;
    },

    /**
     * Converts an array to a UID range string.
     *
     * @param array uids  Array of UIDs.
     *
     * @return string  The UID range string.
     */
    toUidString: function(uids)
    {
        var u = uids.numericSort(),
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

        return out.join(',');
    },

    /**
     * Converts a UID range string to an array.
     *
     * @param string str  UID range string.
     *
     * @return array  UID array.
     */
    fromUidString: function(str)
    {
        var out = [];

        $.each($.trim(str).split(','), function(n, e) {
            var i, r = e.split(':');
            if (r.length == 1) {
                out.push(Number(e));
            } else {
                for (i = Number(r[0]); i <= Number(r[1]); ++i) {
                    out.push(Number(i));
                }
            }
        });

        return out;
    },

    /**
     */
    runTasks: function(e, d)
    {
        var v;

        if ((v = d['imp:compose'])) {
            $.fn[v.atclimit ? 'hide' : 'show'].call($('#imp-compose-attach-form'));
            if (v.cacheid) {
                if ($('#imp-redirect-form:visible').length) {
                    $('#imp-redirect-cache').val(v.cacheid);
                } else {
                    $('#imp-compose-cache').val(v.cacheid);
                    $('#imp-compose-hmac').val(v.hmac);
                }
            }
        }

        if ((v = d['imp:compose-atc'])) {
            ImpMobile.composeatc = ImpMobile.composeatc.concat(v);
        }

        if ((v = d['imp:flag'])) {
            ImpMobile.updateFlags(v);
            // Force a viewport update.
            ImpMobile.mailboxCache = null;
        }

        if ((v = d['imp:flag-config'])) {
            ImpMobile.flags = {};
            $.each(v, function(undefined, value) {
                ImpMobile.flags[value.id] = value;
            });
        }

        if ((v = d['imp:message'])) {
            ImpMobile.message = v.shift().data;
        }

        if (d['imp:mailbox']) {
             ImpMobile.foldersLoaded = false;
        }

        if ((v = d['imp:poll'])) {
            ImpMobile.updateFolders(v);
        }

        if ((v = d['imp:viewport'])) {
            ImpMobile.viewport(v);
        }
    },

    /**
     */
    swipeButtons: function(e, ob)
    {
        $.each($($('#imp-mailbox-buttons').children()), function(k, v) {
            v = $(v);
            if (ImpMobile.mailboxAction(v)) {
                ob.buttons.push(v.clone(true));
            }
        });
    },

    /**
     */
    mailboxAction: function(v)
    {
        switch (v.attr('href')) {
        case '#mailbox-delete':
            return !ImpMobile.cache[ImpMobile.mailbox].readonly;

        case '#mailbox-innocent':
            return ImpMobile.cache[ImpMobile.mailbox].innocent;

        case '#mailbox-spam':
            return ImpMobile.cache[ImpMobile.mailbox].spam;

        default:
            return true;
        }
    },

    /**
     */
    mailboxTaphold: function(e)
    {
        $.each($('#imp-mailbox-taphold li'), function(k, v) {
            v = $(v);
            if (ImpMobile.mailboxAction(v.find('a'))) {
                v.show().find('a').jqmData('buid', $(e.currentTarget).jqmData('buid'));
            } else {
                v.hide();
            }
        });
        $('#imp-mailbox-taphold ul').listview('refresh');
    },

    /**
     */
    morePopup: function(id, callback)
    {
        var list = $('#' + id + ' :jqmData(role=listview)');
        callback(list);
        list.listview('refresh');
    },

    /**
     * Event handler for the document-ready event, responsible for the initial
     * setup.
     */
    onDocumentReady: function()
    {
        $('#imp-mailbox-list')
            .swipebutton()
                .on('swipebutton', 'li.imp-message', ImpMobile.swipeButtons)
            .listviewtaphold({ popupElt: $('#imp-mailbox-taphold') })
                .on('listviewtaphold', 'li.imp-message', ImpMobile.mailboxTaphold);

        ImpMobile.resetRefresh();

        $('#message').on('swipeleft', function() {
            $.mobile.changePage('#message-next');
        }).on('swiperight', function() {
            $.mobile.changePage('#message-prev');
        }).on('popupbeforeposition', function(r) {
            switch ($(r.target).attr('id')) {
            case 'message-more':
                ImpMobile.morePopup('message-more', ImpMobile.messageMorePopup);
                break;

            case 'message-reply':
                ImpMobile.morePopup('message-reply', ImpMobile.messageReplyPopup);
                break;
            }
        });
        $('#imp-message-headers').on('expand', ImpMobile.fullHeaders);
        $('#imp-message-atc').on('expand', ImpMobile.showAttachments);

        $('#compose').on('popupbeforeposition', function() {
            switch ($(r.target).attr('id')) {
            case 'compose-more':
                ImpMobile.morePopup('compose-more', ImpMobile.composeMorePopup);
                break;
            }
        });

        $('#folders').on('popupbeforeposition', function(r) {
            switch ($(r.target).attr('id')) {
            case 'folders-more':
                ImpMobile.morePopup('folders-more', function() {});
                break;
            }
        });

        if (!IMP.conf.disable_compose) {
            $('#imp-compose-upload-container a').on('click', function(e) {
                $('#imp-compose-upload-container input').trigger('click');
                e.preventDefault();
            });
            $('#imp-compose-upload-container input').on('change', function() {
                HordeMobile.doAction('addAttachment', {
                    composeCache: $('#imp-compose-cache').val(),
                    json_return: true
                }, ImpMobile.composeAttachRefresh, {
                    submit: $(this).closest('FORM')
                });
            });

            $.each([ 'to', 'cc' ], function(undefined, v) {
                $('#imp-compose-' + v)
                    .autocomplete({
                        callback: function(e) {
                            ImpMobile.addAddress(v, $.trim($(e.currentTarget).text()));
                            $('#imp-compose-' + v).val('');
                        },
                        link: '#',
                        minLength: 3,
                        source: 'smartmobileAutocomplete',
                        target: $('#imp-compose-' + v + '-suggestions')
                    })
                    // textchange plugin requires bind()
                    .bind('textchange', ImpMobile.processComposeAddress);
            });
        }
    }

};

// JQuery Mobile setup
$(ImpMobile.onDocumentReady);
$(document).on('pagebeforechange', ImpMobile.toPage)
    .on('pagebeforeshow', ImpMobile.beforeShow)
    .on('pagechange', ImpMobile.pageShow)
    .on('HordeMobile:runTasks', ImpMobile.runTasks);

var ImpMobileMbox = {
    // Vars used: cacheid, label, readonly
    data: {},
    rowlist: {},
    slice: 0,
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

        $.each(this.data, function(key) {
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

    rows: function()
    {
        var mbox_data = this.data;

        return $.map($.map(this.rowlist, function(value, key) {
            return { sort: value, buid: key };
        }).sort(function(a, b) {
            return (a.sort < b.sort) ? -1 : 1;
        }), function(value) {
            return {
                buid: value.buid,
                data: mbox_data[value.buid]
            };
        });
    },

    rowToBuid: function(row)
    {
        var buid;

        $.each(this.rowlist, function(b, p) {
            if (p == row) {
                buid = b;
                return;
            }
        });

        return buid;
    },

    maxRow: function()
    {
        return Math.min(this.slice, this.totalrows);
    }

};


var IMP_JS = {

    iframeInject: function(id, data)
    {
        id = $('#' + id);
        var d = id.get(0).contentWindow.document;

        id.one('load', function() {
            window.setTimeout(function() {
                IMP_JS.iframeResize(id);
            }, 0);
        });

        d.open();
        d.write(data);
        d.close();

        /* Catch compose links within HTML IFRAME data. */
        $(d).on('click', 'a', function(e) {
            var href = $(e.target).attr('href');
            if (href.match(/\#compose[\?$]/)) {
                $.mobile.changePage(href);
                e.preventDefault();
            }
        });

        id.show().prev().remove();
    },

    lazyLoad: function(id)
    {
        var d = $(id.get(0).contentWindow.document),
            h = id.closest('BODY').height();

        d.find('IMG[data-src]').unveil(parseInt(h * 0.1, 10), function() {
            $(this).removeAttr('data-src');
        });
    },

    iframeResize: function(id)
    {
        id = $(id);
        id.height(id.contents().height());
        IMP_JS.lazyLoad(id);
    },

    /**
     * Use DOM manipulation to un-block images.
     */
    unblockImages: function(iframe)
    {
        var doc = $(iframe.get(0).contentWindow.document);

        $.each(doc.find('[htmlimgblocked]'), function(k, v) {
            v = $(v);
            var src = v.attr('htmlimgblocked');
            v.removeAttr('htmlimgblocked');

            if (v.attr('src')) {
                v.attr('data-src', src);
            } else {
                if (v.attr('background')) {
                    v.attr('background', src);
                }
                if (v.css('background-image')) {
                    v.css('background-image', 'url(' + src + ')');
                }
            }
        });

        $.each(doc.find('[htmlimgblocked_srcset]'), function(k, v) {
            v = $(v);
            v.attr('srcset', v.attr('htmlimgblocked_srcset'));
            v.removeAttr('htmlimgblocked_srcset');
        });

        $.each(doc.find('[htmlcssblocked]'), function(k, v) {
            v = $(v);
            v.attr('href', v.attr('htmlcssblocked'));
            v.removeAttr('htmlcssblocked');
        });

        doc.find('STYLE[type="text/x-imp-cssblocked"]')
            .attr('type', 'text/css');

        IMP_JS.iframeResize(iframe);
    }

};
