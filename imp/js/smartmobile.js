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
    // /* Whether the compose form is currently disabled (being submitted). */
    // disabled,
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
    // /* Search parameters for the viewPort Ajax request. */
    // search,
    //
    // /* UID of the currently displayed message. */
    // uid,
    //
    // /* Mailbox of the currently displayed message. */
    // uid_mbox,
    //
    // /* Whether attachments are currently being uploaded. */
    // uploading,

    // Mailbox data cache.
    cache: {},

    // Rows per mailbox page.
    mbox_rows: 25,


    /**
     * Event handler for the pagebeforechange event that implements loading of
     * deep-linked pages.
     *
     * @param object e     Event object.
     * @param object data  Event data.
     */
    toPage: function(e, data)
    {
        var url = HordeMobile.parseUrl(data.toPage);

        switch (url.view) {
        case 'compose':
            if (!IMP.conf.disable_compose) {
                ImpMobile.compose(url, data.options);
            }
            e.preventDefault();
            break;

        case 'confirm':
            ImpMobile.confirm(url, data.options);
            e.preventDefault();
            break;

        case 'confirmed':
            ImpMobile.confirmed(url, data.options);
            e.preventDefault();
            break;

        case 'folders':
            HordeMobile.doAction('poll');
            break;

        case 'mailbox':
            ImpMobile.toMailbox(url, { opts: data.options });
            e.preventDefault();
            break;

        case 'message':
            ImpMobile.toMessage(url, data.options);
            e.preventDefault();
            break;

        case 'target':
            if (IMP.conf.allow_folders) {
                ImpMobile.target(url, data.options);
            }
            e.preventDefault();
            break;
        }
    },

    /**
     * Switches to the mailbox view and loads a mailbox.
     *
     * @param object url      Parsed URL object.
     * @param object options  Page change options.
     */
    toMailbox: function(url, options)
    {
        var mailbox = url.params.mbox || 'SU5CT1g',
            title = $('#imp-mailbox-' + mailbox).text(),
            params = {}, ob;

        if (HordeMobile.currentPage('mailbox')) {
            HordeMobile.updateHash(url);
        } else {
            if (!options.opts) {
                options.opts = {};
            }
            options.opts.dataUrl = url.parsed.href;
            $.mobile.changePage($('#mailbox'), options.opts);
        }

        document.title = title;
        $('#imp-mailbox-header').text(title);
        if (ImpMobile.mailbox != mailbox) {
            $('#imp-mailbox-list').empty();
            $('#imp-mailbox-navtop,#imp-mailbox-navbottom').hide();
            ImpMobile.mailbox = mailbox;
        }

        if (mailbox != IMP.conf.qsearchid) {
            delete ImpMobile.search;
            $('#imp-search-input').val('');
        } else if (ImpMobile.search) {
            params = ImpMobile.search;
        }

        if (ob = ImpMobile.cache[mailbox]) {
            if (url.params.from) {
                ob.from = Number(url.params.from);
            } else {
                ImpMobile.refreshMailbox(ob);
                if (options.noajax) {
                    return;
                }
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
            if (r.metadata.readonly) {
                ob.readonly = r.metadata.readonly;
            }
            if (r.metadata.slabel) {
                ob.label = r.metadata.slabel;
            }
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

        if (HordeMobile.currentPage('mailbox')) {
            ImpMobile.refreshMailbox(ob);
        }
    },

    /**
     */
    refreshMailbox: function(ob)
    {
        var c, list, ob, url,
            cid = ImpMobile.mailbox + '|' + ob.cacheid + '|' + ob.from;

        if (cid == ImpMobile.mailboxCache) {
            return;
        }
        ImpMobile.mailboxCache = cid;

        if (ob.label) {
            document.title = ob.label;
            $('#imp-mailbox-header').text(ob.label);
        }

        list = $('#imp-mailbox-list');
        list.empty();

        $.each(ob.rows(), function(key, data) {
            c = '';
            url = '#message?view=' + data.mbox + '&uid=' + data.uid;

            if (data.flag) {
                $.each(data.flag, function(k, flag) {
                    switch (flag) {
                    case '\\draft':
                        url = '#compose?type=resume&mbox=' + data.mbox + '&uid=' + data.uid;
                        break;

                    case '\\seen':
                        c += ' imp-message-seen';
                        break;
                    }
                });
            }

            list.append(
                $('<li class="imp-message' + c + '">').append(
                    $('<h3>').append(
                        $('<a href="' + url + '">').html(data.subject))).append(
                    $('<div class="ui-grid-a">').append(
                        $('<div class="ui-block-a">').append(
                            $('<p>').text(data.from))).append(
                        $('<div class="ui-block-b">').append(
                            $('<p align="right">').text(data.date)))));
        });

        if (ob.totalrows > ImpMobile.mbox_rows) {
            var navtext = IMP.text.nav
                .replace(/%d/, ob.from)
                .replace(/%d/, Math.min(ob.from + ImpMobile.mbox_rows - 1, ob.totalrows))
                .replace(/%d/, ob.totalrows);
            $('#imp-mailbox-navtop,#imp-mailbox-navbottom').show();
            $('#imp-mailbox-navtop h2,#imp-mailbox-navbottom h2')
                .text(navtext);
            if (ob.from == 1) {
                $('#imp-mailbox-prev1,#imp-mailbox-prev2')
                    .addClass('ui-disabled')
                    .attr('aria-disabled', true);
            } else {
                $('#imp-mailbox-prev1,#imp-mailbox-prev2')
                    .removeClass('ui-disabled')
                    .attr('aria-disabled', false);
            }
            if ((ob.from + ImpMobile.mbox_rows - 1) >= ob.totalrows) {
                $('#imp-mailbox-next1,#imp-mailbox-next2')
                    .addClass('ui-disabled')
                    .attr('aria-disabled', true);
            } else {
                $('#imp-mailbox-next1,#imp-mailbox-next2')
                    .removeClass('ui-disabled')
                    .attr('aria-disabled', false);
            }
        } else {
            $('#imp-mailbox-navtop,#imp-mailbox-navbottom').hide();
        }

        list.listview('refresh');
    },

    /**
     * Switches to the message view and loads a message.
     *
     * @param object url      Parsed URL object.
     * @param object options  Page change options.
     */
    toMessage: function(url, options)
    {
        if (!ImpMobile.mailbox) {
            // Deep-linked message page. Load mailbox to allow navigation
            // between messages.
            HordeMobile.doAction(
                'viewPort',
                ImpMobile.addViewportParams({
                    // Make sure we have a big enough buffer to fit all
                    // messages on a page.
                    after: ImpMobile.mbox_rows,
                    before: ImpMobile.mbox_rows,
                    requestid: 1,
                    // Need to manually encode JSON here.
                    search: JSON.stringify({ uid: url.params.uid }),
                    view: url.params.view
                })
            );
            ImpMobile.mailbox = url.params.view;
        }

        if (HordeMobile.currentPage('message')) {
            HordeMobile.updateHash(url);
        } else {
            options.dataUrl = url.parsed.href;
            $.mobile.changePage($('#message'), options);
        }

        // Page is cached.
        if (ImpMobile.uid == url.params.uid &&
            ImpMobile.uid_mbox == url.params.view) {
            document.title = $('#imp-message-title').text();
            return;
        }

        $('#message').children().not('.ui-header').hide();
        $('#imp-message-title').text('');
        document.title = '';
        $.mobile.showPageLoadingMsg();

        HordeMobile.doAction(
            'showMessage',
            {
                uid: ImpMobile.toUIDStringSingle(url.params.view, [ url.params.uid ]),
                view: url.params.view
            },
            ImpMobile.messageLoaded
        );
    },

    /**
     * Navigates to the next or previous message or mailbox page.
     *
     * @param integer|object dir  A swipe event or a jump length.
     */
    navigate: function(dir)
    {
        if (typeof dir == 'object') {
            dir = (dir.type == 'swipeleft') ? 1 : -1;
        }

        var from, pos, uid,
            ob = ImpMobile.cache[ImpMobile.mailbox];

        if (HordeMobile.currentPage('message')) {
            pos = ob.rowlist[ImpMobile.uid] + dir;
            if (pos > 0 && pos <= ob.totalrows) {
                if (uid = ob.rowToUid(pos)) {
                    $.mobile.changePage('#message?view=' + ob.data[uid].mbox + '&uid=' + uid);
                } else {
                    // TODO: Load viewport slice
                }
            }
        } else if (HordeMobile.currentPage('mailbox')) {
            from = Math.min(ob.totalrows, Math.max(1, ob.from + (dir * ImpMobile.mbox_rows)));

            if (from != ob.from) {
                $.mobile.changePage('#mailbox?mbox=' + ImpMobile.mailbox + '&from=' + from);
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
        $.mobile.hidePageLoadingMsg();

        // TODO: Error handling.
        if (r.error || !ImpMobile.message || r.view != ImpMobile.mailbox) {
            return;
        }

        var cache = ImpMobile.cache[ImpMobile.mailbox],
            data = ImpMobile.message,
            headers = $('#imp-message-headers tbody'),
            args = '&mbox=' + data.mbox + '&uid=' + data.uid,
            innocent = 'show',
            spam = 'show',
            spambar, tmp;

        // TODO: Remove once we can pass viewport parameters directly to the
        // showMessage request.
        if (!cache) {
            window.setTimeout(function() { ImpMobile.messageLoaded(r); }, 0);
            return;
        }

        ImpMobile.uid = data.uid;
        ImpMobile.uid_mbox = data.mbox;

        $('#imp-message-title').text(data.title);
        document.title = $('#imp-message-title').text();
        $('#imp-message-subject').text(data.subject);

        if (data.from.raw) {
            $('#imp-message-from').text(data.from.raw);
        } else if (data.from.addr[0].g) {
            $('#imp-message-from').text(data.from.addr[0].g);
        } else if (data.from.addr[0].p) {
            $('#imp-message-from').text(data.from.addr[0].p);
        } else {
            $('#imp-message-from').text(data.from.addr[0].b);
        }

        $('#imp-message-body').html(data.msgtext);
        $('#imp-message-date').text('');

        data.headers.push({ name: 'Subject', value: $('#imp-message-subject').html() });

        headers.text('');
        $.each(data.headers, function(k, header) {
            if (header.value) {
                headers.append($('<tr>').append($('<td class="imp-header-label">').html(header.name + ':')).append($('<td>').html(header.value)));
            }
            if (header.id == 'Date') {
                $('#imp-message-date').text(header.value);
            }
        });

        $('#imp-message-back').attr('href', '#mailbox?mbox=' + data.mbox);
        $('#imp-message-back .ui-btn-text')
            .text($('#imp-mailbox-' + data.mbox).text());

        if (cache.rowlist[data.uid] != 1) {
            $('#imp-message-prev')
                .removeClass('ui-disabled')
                .attr('aria-disabled', false);
        } else {
            $('#imp-message-prev')
                .addClass('ui-disabled')
                .attr('aria-disabled', true);
        }
        if (cache.rowlist[data.uid] != cache.totalrows) {
            $('#imp-message-next')
                .removeClass('ui-disabled')
                .attr('aria-disabled', false);
        } else {
            $('#imp-message-next')
                .addClass('ui-disabled')
                .attr('aria-disabled', true);
        }

        if (!IMP.conf.disable_compose) {
            $('#imp-message-reply').attr(
                'href',
                '#compose?type=reply_auto' + args);
            $('#imp-message-forward').attr(
                'href',
                '#compose?type=forward_auto' + args);
            $('#imp-message-redirect').attr(
                'href',
                '#compose?type=forward_redirect' + args);
            $('#imp-message-resume').attr(
                'href',
                '#compose?type=editasnew' + args);
        }

        if (cache.readonly) {
            $('#imp-message-delete,#imp-message-move').hide();
        } else {
            $('#imp-message-delete,#imp-message-move').show();
            if (IMP.conf.allow_folders) {
                $('#imp-message-move').attr(
                    'href',
                    '#target?action=move' + args);
            }
        }
        if (IMP.conf.allow_folders) {
            $('#imp-message-copy').attr(
                'href',
                '#target?action=copy' + args);
        }
        if (ImpMobile.mailbox == IMP.conf.spam_mbox) {
            if (!IMP.conf.spam_spammbox) {
                spam = 'hide';
            }
        } else if (IMP.conf.innocent_spammbox) {
            innocent = 'hide';
        }

        if ($('#imp-message-innocent')) {
            $.fn[innocent].call($('#imp-message-innocent'));
            $('#imp-message-innocent').attr(
                'href',
                '#confirm?action=innocent' + args);
            spambar = $('#imp-message-innocent').parent();
        }
        if ($('#imp-message-spam')) {
            $.fn[spam].call($('#imp-message-spam'));
            $('#imp-message-spam').attr(
                'href',
                '#confirm?action=spam' + args);
            spambar = $('#imp-message-spam').parent();
        }
        if (spambar) {
            spambar.controlgroup('refresh');
        }

        if (data.js) {
            $.each(data.js, function(k, js) {
                $.globalEval(js);
            });
        }

        $('#message').children().show();
        tmp = $('#imp-message-header-toggle').children();
        tmp.eq(0).show();
        tmp.eq(1).hide();

        delete ImpMobile.message;
    },

    /**
     * Switches to the compose view and loads a message if replying or
     * forwarding.
     *
     * @param object url      Parsed URL object.
     * @param object options  Page change options.
     */
    compose: function(url, options)
    {
        var func, cache,
            params = {};

        $('#imp-compose-title').html(IMP.text.new_message);

        if (!url.params.mbox) {
            $.mobile.changePage($('#compose'));
            return;
        }

        $('#imp-compose-form').show();
        $('#imp-redirect-form').hide();

        switch (url.params.type) {
        case 'reply_auto':
            func = 'getReplyData';
            cache = '#imp-compose-cache';
            params.format = 'text';
            break;

        case 'forward_auto':
            func = 'getForwardData';
            cache = '#imp-compose-cache';
            break;

        case 'forward_redirect':
            $('#imp-compose-form').hide();
            $('#imp-redirect-form').show();
            func = 'getRedirectData';
            cache = '#imp-redirect-cache';
            break;

        case 'editasnew':
        case 'resume':
        case 'template':
            func = 'getResumeData';
            cache = '#imp-compose-cache';
            params.format = 'text';
            break;
        }

        options.dataUrl = url.parsed.href;
        HordeMobile.doAction(
            func,
            $.extend(params, {
                imp_compose: $(cache).val(),
                type: url.params.type,
                uid: ImpMobile.toUIDStringSingle(url.params.mbox, [ url.params.uid ])
            }),
            function(r) { ImpMobile.composeLoaded(r, options); });
    },

    /**
     * Callback method after the compose content has been loaded.
     *
     * @param object r        The Ajax response object.
     * @param object options  Page change options from compose().
     */
    composeLoaded: function(r, options)
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
            //i = ImpComposeBase.getIdentity(id, r.opts.show_editor);

            $('#imp-compose-identity').val(id);
            // The first selectmenu() call is necessary to actually create the
            // selectmenu if the compose window is opened for the first time,
            // the second call to update the menu in case the selected index
            // changed.
            $('#imp-compose-identity').selectmenu();
            $('#imp-compose-identity').selectmenu('refresh', true);
            $('#imp-compose-last-identity').val(id);

            $('#imp-compose-to').val(r.header.to);
            $('#imp-compose-subject').val(r.header.subject);
            $('#imp-compose-message').val(r.body);

            $('#imp-compose-' + (r.opts.focus || 'to').replace(/composeMessage/, 'message'))[0].focus();
            //this.fillFormHash();
        }

        HordeMobile.changePage($('#compose'), options);
    },

    uniqueSubmit: function(action)
    {
        var form = (action == 'redirectMessage')
            ? $('#imp-redirect-form')
            : $('#imp-compose-form');

        if (action == 'sendMessage' || action == 'saveDraft') {
            switch (action) {
            case 'sendMessage':
                if (($('#imp-compose-subject').val() == '') &&
                    !window.confirm(IMP.text.nosubject)) {
                    return;
                }
                break;
            }

            // Don't send/save until uploading is completed.
            if (ImpMobile.uploading) {
                window.setTimeout(function() {
                    if (ImpMobile.disabled) {
                        ImpMobile.uniqueSubmit(action);
                    }
                }, 250);
                return;
            }
        }

        if (action == 'addAttachment') {
            // We need a submit action here because browser security models
            // won't let us access files on user's filesystem otherwise.
            ImpMobile.uploading = true;
            form.submit();
        } else {
            // Use an AJAX submit here so that we can do javascript-y stuff
            // before having to close the window on success.
            HordeMobile.doAction(
                action,
                form.serializeArray(true),
                ImpMobile.uniqueSubmitCallback
            );

            // Can't disable until we send the message - or else nothing
            // will get POST'ed.
            if (action != 'autoSaveDraft') {
                ImpMobile.setDisabled(true);
            }
        }
    },

    uniqueSubmitCallback: function(d)
    {
        if (!d) {
            return;
        }

        if (d.imp_compose) {
            $('#imp-compose-cache').val(d.imp_compose);
        }

        if (d.success || d.action == 'addAttachment') {
            switch (d.action) {
            case 'redirectMessage':
            case 'sendMessage':
                return ImpMobile.closeCompose();
            }
        }

        ImpMobile.setDisabled(false);
    },

    closeCompose: function()
    {
        ImpMobile.setDisabled(false);
        $('#imp-compose-form')[0].reset();
        window.setTimeout(ImpMobile.delayedCloseCompose, 0);
    },

    delayedCloseCompose: function()
    {
        if (HordeMobile.currentPage('compose')) {
            window.history.back();
        } else if (HordeMobile.currentPage('notification')) {
            $.mobile.activePage.bind('pagehide', function (e) {
                $(e.currentTarget).unbind(e);
                window.setTimeout(ImpMobile.delayedCloseCompose, 0);
            });
        }
    },

    setDisabled: function(disable)
    {
        var redirect = $('#imp-redirect-form').filter(':visible');

        ImpMobile.disabled = disable;

        if (disable) {
            $.mobile.showPageLoadingMsg();
        } else {
            $.mobile.hidePageLoadingMsg();
        }

        if (redirect) {
            redirect.css({ cursor: disable ? 'wait': null });
        } else {
            $('#imp-compose-form').css({ cursor: disable ? 'wait' : null });
        }
    },

    /**
     * Opens a confirmation dialog.
     *
     * @param object url      Parsed URL object.
     * @param object options  Page change options.
     */
    confirm: function(url, options)
    {
        $.mobile.changePage($('#confirm'), options);

        $('#imp-confirm-text').html(IMP.text.confirm.text[url.params.action]);
        $('#imp-confirm-action')
            .attr('href', url.parsed.hash.replace(/confirm/, 'confirmed'));
        $('#imp-confirm-action .ui-btn-text')
            .text(IMP.text.confirm.action[url.params.action]);
    },

    /**
     * Executes confirmed actions.
     *
     * @param object url      Parsed URL object.
     * @param object options  Page change options.
     */
    confirmed: function(url, options)
    {
        if (!url.params.mbox) {
            url.params.mbox = url.params.view;
        }

        switch (url.params.action) {
        case 'innocent':
        case 'spam':
            HordeMobile.doAction(
                'reportSpam',
                $.extend(ImpMobile.addViewportParams({
                    checkcache: 1,
                    view: url.params.mbox
                }), {
                    spam: Number(url.params.action == 'spam'),
                    uid: ImpMobile.toUIDStringSingle(url.params.mbox, [ url.params.uid ]),
                }),
                function() {
                    HordeMobile.changePage('#mailbox?mbox=' + url.params.mbox);
                }
            );
            break;
        }

        $('#confirm').dialog('close');
    },

    /**
     * Opens a target mailbox dialog.
     *
     * @param object url      Parsed URL object.
     * @param object options  Page change options.
     */
    target: function(url, options)
    {
        $.mobile.changePage($('#target'), options);
        $('#imp-target-header').text(IMP.text[url.params.action]);
        $('#imp-target-mbox').val(url.params.mbox);
        $('#imp-target-uid').val(url.params.uid);
    },

    /**
     * Moves or copies a message to a selected target.
     *
     * @param object e  An event object.
     */
    targetSelected: function(e)
    {
        var source = $('#imp-target-mbox').val(),
            target = $(e.currentTarget).attr('id') == 'imp-target-list'
                ? $('#imp-target-list')
                : $('#imp-target-new'),
            value = target.val(),
            func;

        if (value === '') {
            $('#imp-target-newdiv').show();
            return;
        }

        func = ($('#imp-target-header').text() == IMP.text.copy)
            ? 'copyMessages'
            : 'moveMessages';

        HordeMobile.doAction(
            func,
            $.extend(ImpMobile.addViewportParams({
                checkcache: 1,
                view: source
            }), {
                mboxto: value,
                newmbox: $('#imp-target-new').val(),
                uid: ImpMobile.toUIDStringSingle(source, [ $('#imp-target-uid').val() ]),
            }),
            (IMP.conf.mailbox_return || func == 'moveMessages')
                ? function(r) {
                      ImpMobile.toMailbox(HordeMobile.parseUrl('#mailbox?mbox=' + source), { noajax: true });
                  }
                : null
        );

        $('#target').dialog('close');
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

        if (HordeMobile.currentPage('folders')) {
            $('#imp-folders-list').listview('refresh');
        }
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
     * Catch-all event handler for the click event.
     *
     * @param object e  An event object.
     */
    clickHandler: function(e)
    {
        var elt = $(e.target), id;

        while (elt && elt != window.document && elt.parent().length) {
            id = elt.attr('id');

            switch (id) {
            case 'imp-message-delete':
                HordeMobile.doAction(
                    'deleteMessages',
                    $.extend(ImpMobile.addViewportParams({
                        checkcache: 1,
                        view: ImpMobile.mailbox
                    }), {
                        uid: ImpMobile.toUIDStringSingle(ImpMobile.mailbox, [ ImpMobile.uid ]),
                    })
                );
                $.mobile.changePage('#mailbox?mbox=' + ImpMobile.mailbox);
                return;

            case 'imp-message-header-toggle':
                elt.children().toggle();
                return;

            case 'imp-message-prev':
            case 'imp-message-next':
                if (!elt.hasClass('ui-disabled')) {
                    ImpMobile.navigate(id == 'imp-message-prev' ? -1 : 1);
                }
                return;

            case 'imp-mailbox-prev1':
            case 'imp-mailbox-prev2':
                if (!elt.hasClass('ui-disabled')) {
                    ImpMobile.navigate(-1);
                }
                return;

            case 'imp-mailbox-next1':
            case 'imp-mailbox-next2':
                if (!elt.hasClass('ui-disabled')) {
                    ImpMobile.navigate(1);
                }
                return;

            case 'imp-compose-submit':
                if (!ImpMobile.disabled) {
                    var action = $('#imp-compose-form').is(':hidden')
                        ? 'redirectMessage'
                        : 'sendMessage';
                    ImpMobile.uniqueSubmit(action);
                }
                return;

            case 'imp-search-submit':
                ImpMobile.search = {
                    qsearch: $('#imp-search-input').val(),
                    qsearchfield: $('#imp-search-by').val(),
                    qsearchmbox: ImpMobile.mailbox
                };
                $.mobile.changePage('#mailbox?mbox=' + IMP.conf.qsearchid);
                return;

            case 'imp-folders-showall':
            case 'imp-folders-showpoll':
                HordeMobile.doAction(
                    'smartmobileFolderTree',
                    {
                        all: $('#imp-folders-showall').filter(':visible').size()
                    },
                    function(r) {
                        $('#imp-folders-list').html(r).listview('refresh');
                    }
                );
                $('#imp-folders-showall,#imp-folders-showpoll').toggle();
                return;
            }

            elt = elt.parent();
        }
    },

    runTasks: function(e, d)
    {
        $.each(d, function(key, value) {
            switch (key) {
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
     * Event handler for the document-ready event, responsible for the initial
     * setup.
     */
    onDocumentReady: function()
    {
        // Set up HordeMobile.
        $(document).bind('vclick', ImpMobile.clickHandler);
        $(document).bind('swipeleft', ImpMobile.navigate);
        $(document).bind('swiperight', ImpMobile.navigate);
        $(document).bind('pagebeforechange', ImpMobile.toPage);
        $(document).bind('HordeMobile:runTasks', ImpMobile.runTasks);

        if (!IMP.conf.disable_compose) {
            $('#compose').live('pagehide', function() { $('#imp-compose-cache').val(''); });
        }

        if (IMP.conf.allow_folders) {
            $('#imp-target-list').live('change', ImpMobile.targetSelected);
            $('#imp-target-new-submit').live('click', ImpMobile.targetSelected);
            $('#target').live('pagebeforeshow', function() {
                $('#imp-target')[0].reset();
                $('#imp-target-list').selectmenu('refresh', true);
                $('#imp-target-newdiv').hide();
            });
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
        var t = this;

        $.each(ids, function(key, value) {
            delete t.data[value];
            delete t.rowlist[value];
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
            window.setTimeout(function() { IMP_JS.iframeResize(id); }, 300);
        });

        d.open();
        d.write(data);
        d.close();

        id.show().prev().remove();

        IMP_JS.iframeResize(id);
    },

    iframeResize: function(id)
    {
        var lc = id.get(0).contentWindow.document.lastChild,
            body = id.get(0).contentWindow.document.body;

        lc = (lc.scrollHeight > body.scrollHeight) ? lc : body;

        // Try expanding IFRAME if we detect a scroll.
        if (lc.clientHeight != lc.scrollHeight ||
            id.get(0).clientHeight != lc.clientHeight) {
            id.css('height', lc.scrollHeight + 'px' );
            if (lc.clientHeight != lc.scrollHeight) {
                // Finally, brute force if it still isn't working.
                id.css('height', (lc.scrollHeight + 25) + 'px');
            }
        }
    }

};
