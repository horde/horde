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
    // /* One-time callback after the mailbox has been loaded. */
    // mailboxCallback,
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


    /**
     * Safe wrapper that makes sure that no dialog is still open before calling
     * a function.
     *
     * @param function func    A function to execute after the current dialog
     *                         has been closed
     * @param array whitelist  A list of page IDs that should not be waited for.
     */
    onDialogClose: function(func, whitelist)
    {
        whitelist = whitelist || [];
        if ($.mobile.activePage.jqmData('role') == 'dialog' &&
            $.inArray($.mobile.activePage.attr('id'), whitelist) == -1) {
            $.mobile.activePage.bind('pagehide', function(e) {
                $(e.currentTarget).unbind(e);
                window.setTimeout(function () { ImpMobile.onDialogClose(func, whitelist); }, 0);
            });
            return;
        }
        func();
    },

    /**
     * Safe wrapper around $.mobile.changePage() that makes sure that no dialog
     * is still open before changing to the new page.
     *
     * @param string|object page  The page to navigate to.
     */
    changePage: function(page)
    {
        ImpMobile.onDialogClose(function() { $.mobile.changePage(page); });
    },

    /**
     * Event handler for the pagebeforechange event that implements loading of
     * deep-linked pages.
     *
     * @param object e     Event object.
     * @param object data  Event data.
     */
    toPage: function(e, data)
    {
        if (typeof data.toPage != 'string') {
            return;
        }

        var url = $.mobile.path.parseUrl(data.toPage),
            match = /^#(mailbox|message|compose|confirm(ed)?|target)/.exec(url.hash);

        if (url.hash == ImpMobile.lastHash) {
            return;
        }

        if (match) {
            switch (match[1]) {
            case 'mailbox':
                ImpMobile.lastHash = url.hash;
                ImpMobile.toMailbox(url, { opts: data.options });
                break;

            case 'message':
                ImpMobile.lastHash = url.hash;
                ImpMobile.toMessage(url, data.options);
                break;

            case 'compose':
                if (!IMP.conf.disable_compose) {
                    ImpMobile.compose(url, data.options);
                }
                break;

            case 'confirm':
                ImpMobile.confirm(url, data.options);
                break;

            case 'confirmed':
                ImpMobile.confirmed(url, data.options);
                break;

            case 'target':
                if (IMP.conf.allow_folders) {
                    ImpMobile.target(url, data.options);
                }
                break;
            }
            e.preventDefault();
        }
    },

    /**
     * Switches to the mailbox view and loads a mailbox.
     *
     * @param object url      Page URL from $.mobile.path.parseUrl().
     * @param object options  Page change options.
     */
    toMailbox: function(url, options)
    {
        var match = /\?mbox=(.*)&from=(.*)/.exec(url.hash) ||
                    /\?mbox=(.*)/.exec(url.hash),
            mailbox = match[1],
            title = $('#imp-mailbox-' + mailbox).text(),
            params = {}, ob;

        if ($.mobile.activePage &&
            $.mobile.activePage.attr('id') == 'mailbox') {
            // Need to update history manually, because jqm exits too early
            // if calling changePage() with the same page but different hash
            // parameters.
            $.mobile.urlHistory.ignoreNextHashChange = true;
            $.mobile.path.set(url.hash);
        } else {
            if (!options.opts) {
                options.opts = {};
            }
            options.opts.dataUrl = url.href;
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
            if (match[2]) {
                ob.from = match[2];
            }

            ImpMobile.refreshMailbox(ob);

            if (options.noajax) {
                return;
            }
        }

        HordeMobile.doAction(
            'viewPort',
            $.extend(ImpMobile.addMboxParams(params), {
                requestid: 1,
                view: mailbox
            })
        );
    },

    /**
     */
    addMboxParams: function(params)
    {
        params = params || {};

        var from = 1, o = {}, ob;

        if (ob = ImpMobile.cache[ImpMobile.mailbox]) {
            o[ImpMobile.mailbox] = ob.cachedIds();
            params.cache = ImpMobile.toUIDString(o);
            params.cacheid = ob.cacheid;
            params.checkcache = 1;
            from = ob.from;
        }

        params.slice = from + ':' + (from + 24);

        return params;
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
        ob.update(r.data, r.rowlist, r.totalrows);
        if (r.disappear) {
            ob.disappear(r.disappear);
        }

        if ($.mobile.activePage &&
            $.mobile.activePage.attr('id') == 'mailbox') {
            ImpMobile.refreshMailbox(ob);
        }
    },

    /**
     */
    refreshMailbox: function(ob)
    {
        if (ob.oldcacheid == ob.cacheid) {
            return;
        }

        var list = $('#imp-mailbox-list'), c, ob, url;

        if (ob.label) {
            document.title = ob.label;
            $('#imp-mailbox-header').text(ob.label);
        }

        list.empty();

        $.each(ob.data || [], function(key, data) {
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

        if (ob.totalrows > list.children().length) {
            var navtext = IMP.text.nav
                .replace(/%d/, ob.from)
                .replace(/%d/, Math.min(ob.from + 24, ob.totalrows))
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
            if (ob.from + 24 >= ob.totalrows) {
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
        $.mobile.fixedToolbars.show();

        if (ImpMobile.mailboxCallback) {
            ImpMobile.mailboxCallback.apply();
            delete ImpMobile.mailboxCallback;
        }

        ob.oldcacheid = ob.cacheid;
    },

    /**
     * Switches to the message view and loads a message.
     *
     * @param object url      Page URL from $.mobile.path.parseUrl().
     * @param object options  Page change options.
     */
    toMessage: function(url, options)
    {
        var match = /\?view=(.*?)&uid=(.*)/.exec(url.hash),
            o = {};

        if (!$.mobile.activePage) {
            // Deep-linked message page. Load mailbox first to allow navigation
            // between messages.
            ImpMobile.mailboxCallback = function() {
                ImpMobile.lastHash = url.hash;
                options.changeHash = true;
                ImpMobile.toMessage(url, options);
            };
            $.mobile.changePage('#mailbox?mbox=' + match[1]);
            return;
        }

        if ($.mobile.activePage &&
            $.mobile.activePage.attr('id') == 'message') {
            // Need to update history manually, because jqm exits too early
            // if calling changePage() with the same page but different hash
            // parameters.
            $.mobile.urlHistory.ignoreNextHashChange = true;
            $.mobile.path.set(url.hash);
        } else {
            options.dataUrl = url.href;
            $.mobile.changePage($('#message'), options);
        }

        if (ImpMobile.uid == match[2] && ImpMobile.uid_mbox == match[1]) {
            return;
        }

        $('#imp-message-body').text('');

        o[match[1]] = [ match[2] ];

        HordeMobile.doAction(
            'showMessage',
            {
                uid: ImpMobile.toUIDString(o),
                view: match[1]
            },
            ImpMobile.messageLoaded,
            {
                success: function(d) {
                    HordeMobile.doActionComplete(d, ImpMobile.messageLoaded);
                    if (!d.response) {
                        ImpMobile.changePage('#mailbox?mbox=' + match[1]);
                    }
                }
            });
    },

    /**
     * Returns the mailbox and uid of the next or previous message.
     *
     * @param integer|object dir  A swipe event or a jump length.
     *
     * @return array  The mailbox and uid of the next message, if it exists.
     */
    nextMessage: function(dir)
    {
        if (typeof dir == 'object') {
            dir = (dir.type == 'swipeleft') ? 1 : -1;
        }

        var ob = ImpMobile.cache[ImpMobile.mailbox],
            pos = ob.rowlist[ImpMobile.uid] + dir,
            newuid;

        if (pos < 0 || pos > ob.totalrows) {
            return;
        }

        $.each(ob.rowlist, function(uid, mpos) {
            if (mpos == pos) {
                newuid = [ ob.data[uid].mbox, uid ];
                return;
            }
        });

        return newuid;
    },

    /**
     * Navigates to the next or previous message or mailbox page.
     *
     * @param integer|object dir  A swipe event or a jump length.
     */
    navigate: function(dir)
    {
        switch ($.mobile.activePage.attr('id')) {
        case 'message':
            var next = ImpMobile.nextMessage(dir);
            if (next) {
                $.mobile.changePage('#message?view=' + next[0] + '&uid=' + next[1]);
            }
            break;

        case 'mailbox':
            if (typeof dir == 'object') {
                dir = dir.type == 'swipeleft' ? 1 : -1;
            }

            var ob = ImpMobile.cache[ImpMobile.mailbox],
                     page = Math.min(Math.max(ob.from, ob.totalrows - 24),
                                     Math.max(1, ob.from + dir * 25));
            if (page != ob.from) {
                $.mobile.changePage('#mailbox?mbox=' + ImpMobile.mailbox
                                    + '&from=' + page);
            }
            break;
        }
    },

    /**
     * Callback method after the message has been loaded.
     *
     * @param object r  The Ajax response object.
     */
    messageLoaded: function(r)
    {
        if (r && r.message && !r.message.error) {
            var data = r.message,
                headers = $('#imp-message-headers tbody'),
                args = '&mbox=' + data.mbox + '&uid=' + data.uid,
                ham = spam = 'show', spambar;

            ImpMobile.uid = data.uid;
            ImpMobile.uid_mbox = data.mbox;

            $('#imp-message-title').html(data.title);
            document.title = $('#imp-message-title').text();
            $('#imp-message-subject').html(data.subject);
            $('#imp-message-from').text(data.from[0].personal || data.from[0].inner);
            $('#imp-message-body').html(data.msgtext);
            $('#imp-message-date').text('');
            $('#imp-message-more').parent().show();
            $('#imp-message-less').parent().hide();
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

            if (ImpMobile.nextMessage(-1)) {
                $('#imp-message-prev')
                    .removeClass('ui-disabled')
                    .attr('aria-disabled', false);
            } else {
                $('#imp-message-prev')
                    .addClass('ui-disabled')
                    .attr('aria-disabled', true);
            }
            if (ImpMobile.nextMessage(1)) {
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

            if (ImpMobile.cache[ImpMobile.mailbox].readonly) {
                $('#imp-message-delete,#imp-message-move').hide();
            } else {
                $('#imp-message-delete,#imp-message-move').show();
                $('#imp-message-delete').attr(
                    'href',
                    '#confirm?action=delete' + args);
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
            } else if (IMP.conf.ham_spammbox) {
                ham = 'hide';
            }

            if ($('#imp-message-ham')) {
                $.fn[ham].call($('#imp-message-ham'));
                $('#imp-message-ham').attr(
                    'href',
                    '#confirm?action=ham' + args);
                spambar = $('#imp-message-ham').parent();
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
        }
    },

    /**
     * Switches to the compose view and loads a message if replying or
     * forwarding.
     *
     * @param object url      Page URL from $.mobile.path.parseUrl().
     * @param object options  Page change options.
     */
    compose: function(url, options)
    {
        var match = /\?type=(.*?)&mbox=(.*?)&uid=(.*)/.exec(url.hash);

        $('#imp-compose-title').html(IMP.text.new_message);

        if (!match) {
            $.mobile.changePage($('#compose'));
            return;
        }

        var type = match[1], mailbox = match[2], uid = match[3],
            func, cache, o = {}, params = {};
        o[mailbox] = [ uid ];

        $('#imp-compose-form').show();
        $('#imp-redirect-form').hide();

        switch (type) {
        case 'reply':
        case 'reply_all':
        case 'reply_auto':
        case 'reply_list':
            func = 'getReplyData';
            cache = '#imp-compose-cache';
            break;

        case 'forward_auto':
        case 'forward_attach':
        case 'forward_body':
        case 'forward_both':
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
        case 'template_edit':
            func = 'getResumeData';
            cache = '#imp-compose-cache';
            params.type = type;
            break;
        }

        options.dataUrl = url.href;
        HordeMobile.doAction(
            func,
            $.extend(params, {
                type: type,
                imp_compose: $(cache).val(),
                uid: ImpMobile.toUIDString(o)
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
        ImpMobile.changePage($('#compose'), options);
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
            HordeMobile.doAction(action,
                                 form.serializeArray(true),
                                 ImpMobile.uniqueSubmitCallback);

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
        if ($.mobile.activePage.attr('id') == 'compose') {
            window.history.back();
        } else if ($.mobile.activePage.attr('id') == 'notification') {
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
     * @param object url      Page URL from $.mobile.path.parseUrl().
     * @param object options  Page change options.
     */
    confirm: function(url, options)
    {
        var match = /\?action=(.*?)&(.*)/.exec(url.hash);

        $.mobile.changePage($('#confirm'), options);

        $('#imp-confirm-text').html(IMP.text.confirm.text[match[1]]);
        $('#imp-confirm-action')
            .attr('href', url.hash.replace(/confirm/, 'confirmed'));
        $('#imp-confirm-action .ui-btn-text')
            .text(IMP.text.confirm.action[match[1]]);
    },

    /**
     * Executes confirmed actions.
     *
     * @param object url      Page URL from $.mobile.path.parseUrl().
     * @param object options  Page change options.
     */
    confirmed: function(url, options)
    {
        var match, mailbox, uid, o = {};

        match = /\?action=(.*?)&(?:(?:view|mbox)=(.*?)&uid=(.*)|(.*))/
            .exec(url.hash);

        if (match[2]) {
            mailbox = match[2];
            uid = match[3];
            o[mailbox] = [ uid ];
        }

        switch (match[1]) {
        case 'delete':
            HordeMobile.doAction(
                'deleteMessages',
                ImpMobile.addMboxParams({
                    uid: ImpMobile.toUIDString(o),
                    view: mailbox
                }),
                function() {
                    ImpMobile.toMailbox($.mobile.path.parseUrl('#mailbox?mbox=' + mailbox), { noajax: true });
                }
            );
            break;

        case 'spam':
        case 'ham':
            HordeMobile.doAction(
                'reportSpam',
                ImpMobile.addMboxParams({
                    spam: Number(match[1] == 'spam'),
                    uid: ImpMobile.toUIDString(o),
                    view: mailbox
                }),
                function() {
                    ImpMobile.toMailbox($.mobile.path.parseUrl('#mailbox?mbox=' + mailbox), { noajax: true });
                }
            );
            break;
        }

        $('#confirm').dialog('close');
    },

    /**
     * Opens a target mailbox dialog.
     *
     * @param object url      Page URL from $.mobile.path.parseUrl().
     * @param object options  Page change options.
     */
    target: function(url, options)
    {
        var match = /\?action=(.*?)&mbox=(.*?)&uid=(.*)/.exec(url.hash);
        $.mobile.changePage($('#target'), options);
        $('#imp-target-header').text(IMP.text[match[1]]);
        $('#imp-target-mbox').val(match[2]);
        $('#imp-target-uid').val(match[3]);
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
            func, o = {};

        if (value === '') {
            $('#imp-target-newdiv').show();
            return;
        }

        if ($('#imp-target-header').text() == IMP.text.copy) {
            func = 'copyMessages';
        } else {
            func = 'moveMessages';
        }
        o[source] = [ $('#imp-target-uid').val() ];
        HordeMobile.doAction(
            func,
            {
                uid: ImpMobile.toUIDString(o),
                mboxto: value,
                newmbox: $('#imp-target-new').val(),
                view: source
            },
            null,
            {
                success: function(d) {
                    HordeMobile.doActionComplete(d);
                    if (d.response) {
                        ImpMobile.onDialogClose(function() {
                            $('#target').dialog('close');
                            if (IMP.conf.mailbox_return) {
                                ImpMobile.changePage('#mailbox?mbox=' + source);
                            }
                        },
                        [ 'target' ]);
                    }
                }
            });
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
            case 'imp-message-more':
                elt.parent().hide();
                elt.parent().next().show();
                return;

            case 'imp-message-less':
                elt.parent().hide();
                elt.parent().prev().show();
                return;

            case 'imp-message-prev':
            case 'imp-message-next':
                if (!elt.hasClass('ui-disabled')) {
                    ImpMobile.navigate(id == 'imp-message-prev' ? -1 : 1);
                }
                return;

            case 'imp-message-spam':
            case 'imp-message-ham':
                ImpMobile.reportSpam(id == 'imp-message-spam');
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
            }

            elt = elt.parent();
        }
    },

    runTasks: function(e, d)
    {
        $.each(d, function(key, value) {
            switch (key) {
            case 'imp:viewport':
                ImpMobile.viewport(value);
                break;
            }
        });
    },

    /**
     * Event handlder for the document-ready event, responsible for the inital
     * setup.
     */
    onDocumentReady: function()
    {
        // Set up HordeMobile.
        HordeMobile.urls.ajax = IMP.conf.URI_AJAX;
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
    // Vars used: cacheid, label, oldcacheid, readonly
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
            lc.style.setProperty('overflow-x', 'hidden', '');
        }
    }

};
