/**
 * jQuery Mobile UI application logic.
 *
 * Copyright 2005-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */
var ImpMobile = {

    // Vars used and defaulting to null/false:
    //
    // /**
    //  * Whether the current mailbox is read-only.
    //  */
    // readOnly,
    //
    // /**
    //  * UID of the currently displayed message.
    //  */
    // uid,
    //
    // /**
    //  * Whether the compose form is currently disable, e.g. being submitted.
    //  */
    // disabled,
    //
    // /**
    //  * Whether attachments are currently being uploaded.
    //  */
    // uploading,
    //
    // /**
    //  * One-time callback after the mailbox has been loaded.
    //  */
    // mailboxCallback,

    /**
     * The currently loaded list of message data, keys are UIDs, values are
     * the message information.
     */
    data: {},

    /**
     * The currently loaded list of messages, keys are UIDs, values are
     * position.
     */
    messages: {},

    /**
     * Converts an object to an IMP UID Range string.
     * See IMP::toRangeString().
     *
     * @param object ob  Mailbox name as keys, values are array of uids.
     */
    toRangeString: function(ob)
    {
        var str = '';

        $.each(ob, function(key, value) {
            if (!value.length) {
                return;
            }

            var u = (IMP.conf.pop3 ? value : value.numericSort()),
                first = u.shift(),
                last = first,
                out = [];

            $.each(u, function(n, k) {
                if (!IMP.conf.pop3 && (last + 1 == k)) {
                    last = k;
                } else {
                    out.push(first + (last == first ? '' : (':' + last)));
                    first = last = k;
                }
            });
            out.push(first + (last == first ? '' : (':' + last)));
            str += '{' + key.length + '}' + key + out.join(',');
        });

        return str;
    },

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
            match = /^#(mailbox|message|compose|confirm(ed)?)/.exec(url.hash);

        if (url.hash == ImpMobile.lastHash) {
            return;
        }

        if (match) {
            switch (match[1]) {
            case 'mailbox':
                ImpMobile.lastHash = url.hash;
                ImpMobile.toMailbox(url, data.options);
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
        var mailbox = url.hash.replace(/^#mailbox\?mbox=/, '');
        $('#imp-mailbox-header').text($('#imp-mailbox-' + mailbox).text());
        $('#imp-mailbox-list').empty();
        options.dataUrl = url.href;
        $.mobile.changePage($('#mailbox'), options);
        HordeMobile.doAction(
            'viewPort',
            {
                view: mailbox,
                slice: '1:25',
                requestid: 1,
                sortby: IMP.conf.sort.date.v,
                sortdir: 1,
            },
            ImpMobile.mailboxLoaded);
    },

    /**
     * Callback method after message list has been loaded.
     *
     * @param object r  The Ajax response object.
     */
    mailboxLoaded: function(r)
    {
        var list = $('#imp-mailbox-list'), c, l;
        if (r && r.ViewPort) {
            ImpMobile.data = r.ViewPort.data;
            ImpMobile.messages = r.ViewPort.rowlist;
            ImpMobile.readOnly = r.ViewPort.metadata.readonly;
            $.each(r.ViewPort.data, function(key, data) {
                c = 'imp-message';
                if (data.flag) {
                    $.each(data.flag, function(k, flag) {
                        c += ' imp-message-' + flag.substr(1);
                    });
                }
                list.prepend(
                    $('<li class="' + c + '">').append(
                        $('<h3>').append(
                            $('<a href="#message?view=' + data.mbox + '&uid=' + data.uid + '">').html(data.subject))).append(
                        $('<div class="ui-grid-a">').append(
                            $('<div class="ui-block-a">').append(
                                $('<p>').text(data.from))).append(
                            $('<div class="ui-block-b">').append(
                                $('<p align="right">').text(data.date)))));
            });
            l = list.children().length;
            if (r.ViewPort.totalrows > l) {
                list.append('<li id="imp-mailbox-more">' + IMP.text.more_messages.replace('%d', r.ViewPort.totalrows - l) + '</li>');
            }
            list.listview('refresh');
            $.mobile.fixedToolbars.show();
            if (ImpMobile.mailboxCallback) {
                ImpMobile.mailboxCallback.apply();
            }
        }
        delete ImpMobile.mailboxCallback;
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

        if ($.mobile.activePage.attr('id') == 'message') {
            // Need to update history manually, because jqm exits too early
            // if calling changePage() with the same page but different hash
            // parameters.
            $.mobile.urlHistory.ignoreNextHashChange = true;
            $.mobile.path.set(url.hash);
        } else {
            options.dataUrl = url.href;
            $.mobile.changePage($('#message'), options);
        }

        o[match[1]] = [ match[2] ];
        HordeMobile.doAction(
            'showMessage',
            {
                uid: this.toUIDString(o),
                view: match[1],
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
            dir = dir.type == 'swipeleft' ? 1 : -1;
        }
        var pos = ImpMobile.messages[ImpMobile.uid] + dir, newuid;
        $.each(ImpMobile.messages, function(uid, messagepos) {
            if (messagepos == pos) {
                newuid = uid;
                return;
            }
        });
        if (!newuid || !ImpMobile.data[newuid]) {
            return;
        }
        return [ ImpMobile.data[newuid].mbox, newuid ];
    },

    /**
     * Navigates to the next or previous message.
     *
     * @param integer|object dir  A swipe event or a jump length.
     */
    navigateMessage: function(dir)
    {
        var next = ImpMobile.nextMessage(dir);
        if (next) {
            $.mobile.changePage('#message?view=' + next[0] + '&uid=' + next[1]);
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
                headers = $('#imp-message-headers tbody');
            ImpMobile.uid = data.uid;
            $('#imp-message-title').html(data.title);
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
                    '#compose?type=reply_auto&mbox=' + data.mbox + '&uid=' + data.uid);
                $('#imp-message-forward').attr(
                    'href',
                    '#compose?type=forward_auto&mbox=' + data.mbox + '&uid=' + data.uid);
            }

            if (ImpMobile.readOnly) {
                $('#imp-message-delete').hide();
            } else {
                $('#imp-message-delete').show();
                $('#imp-message-delete').attr(
                    'href',
                    '#confirm?action=delete&mbox=' + data.mbox + '&uid=' + data.uid);
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
            func, o = {};
        o[mailbox] = [ uid ];

        switch (type) {
        case 'reply':
        case 'reply_all':
        case 'reply_auto':
        case 'reply_list':
            func = 'getReplyData';
            break;

        case 'forward_auto':
        case 'forward_attach':
        case 'forward_body':
        case 'forward_both':
            func = 'getForwardData';
            break;

        case 'forward_redirect':
            /*
            $('compose').hide();
            $('redirect').show();
            func = 'getRedirectData';
            break;
            */
        }

        options.dataUrl = url.href;
        HordeMobile.doAction(
            func,
            {
                type: type,
                imp_compose: $('#imp-compose-cache').val(),
                uid: ImpMobile.toRangeString(o)
            },
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
            $('#imp-compose-cache').val(r.imp_compose);
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

            //DimpCompose.fillForm(i.id[2] ? ("\n" + i.sig + r.body) : (r.body + "\n" + i.sig), r.header, r.opts);
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
            case 'autoSaveDraft':
            case 'saveDraft':
                break;
                //TODO
                ImpMobile.updateDraftsMailbox();

                if (d.action == 'saveDraft') {
                    if (!DIMP.conf_compose.qreply &&
                        ImpMobile.baseAvailable()) {
                        DimpCore.base.DimpCore.showNotifications(r.msgs);
                        r.msgs = [];
                    }
                    if (DIMP.conf_compose.close_draft) {
                        return ImpMobile.closeCompose();
                    }
                }
                break;

            case 'sendMessage':
                if (d.flag) {
                    //DimpCore.base.DimpBase.flagCallback(d);
                }

                if (d.mailbox) {
                    //DimpCore.base.DimpBase.mailboxCallback(r);
                }

                if (d.draft_delete) {
                    //DimpCore.base.DimpBase.poll();
                }

                if (d.log) {
                    //DimpCore.base.DimpBase.updateMsgLog(d.log, { uid: d.uid, mailbox: d.mbox });
                }

                return ImpMobile.closeCompose();

            case 'redirectMessage':
                if (d.log) {
                    //DimpCore.base.DimpBase.updateMsgLog(d.log, { uid: d.uid, mailbox: d.mbox });
                }
                return ImpMobile.closeCompose();

            case 'addAttachment':
                break;
                //TODO
                ImpMobile.uploading = false;
                if (d.success) {
                    ImpMobile.addAttach(d.atc);
                }

                $('upload_wait').hide();
                ImpMobile.initAttachList();
                ImpMobile.resizeMsgArea();
                break;
            }
        } else {
            /*
            if (!Object.isUndefined(d.identity)) {
                ImpMobile.old_identity = $F('identity');
                $('identity').setValue(d.identity);
                ImpMobile.changeIdentity();
                $('noticerow', 'identitychecknotice').invoke('show');
                ImpMobile.resizeMsgArea();
            }

            if (!Object.isUndefined(d.encryptjs)) {
                ImpMobile.old_action = d.action;
                eval(d.encryptjs.join(';'));
            }
            */
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
                {
                    uid: this.toUIDString(o),
                    view: mailbox,
                },
                function() {
                    ImpMobile.toMailbox(
                        $.mobile.path.parseUrl('#mailbox?mbox=' + mailbox),
                        {});
                });
            break;
        }

        $('#confirm').dialog('close');
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
                    ImpMobile.navigateMessage(id == 'imp-message-prev' ? -1 : 1);
                }
                return;

            case 'imp-compose-submit':
                if (!ImpMobile.disabled) {
                    ImpMobile.uniqueSubmit('sendMessage');
                }
                return;
            }

            elt = elt.parent();
        }
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
        $(document).bind('swipeleft', ImpMobile.navigateMessage);
        $(document).bind('swiperight', ImpMobile.navigateMessage);
        $(document).bind('pagebeforechange', ImpMobile.toPage);
        if (!IMP.conf.disable_compose) {
            $('#compose').live('pagehide', function() { $('#imp-compose-cache').val(''); });
        }
    }

};

// JQuery Mobile setup
$(ImpMobile.onDocumentReady);


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
