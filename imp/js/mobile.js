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
     * Switches to the mailbox view and loads a mailbox.
     *
     * @param string mailbox  A mailbox name.
     * @param string label    A mailbox label.
     */
    toMailbox: function(mailbox, label)
    {
        $('#imp-mailbox-header').text(label);
        $('#imp-mailbox-list').empty();
        $.mobile.changePage('#mailbox', 'slide', false, true);
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
            $.each(r.ViewPort.data, function(key, data) {
                c = 'imp-message';
                if (data.flag) {
                    $.each(data.flag, function(k, flag) {
                        c += ' imp-message-' + flag.substr(1);
                    });
                }
                list.prepend(
                    $('<li class="' + c + '" data-imp-mailbox="' + data.mbox + '" data-imp-uid="' + data.uid + '">').append(
                        $('<h3>').append(
                            $('<a href="#">').html(data.subject))).append(
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
        }
    },

    /**
     * Switches to the message view and loads a message.
     *
     * @param string mailbox  A mailbox name.
     * @param string uid      A message UID.
     */
    toMessage: function(mailbox, uid)
    {
        var o = {};
        o[mailbox] = [ uid ];
        $('#imp-message-title').html('&nbsp;');
        $('#imp-message-subject').text('');
        $('#imp-message-from').text('');
        $('#imp-message-body').text('');
        $('#imp-message-date').text('');
        $('#imp-message-more').parent().show();
        $('#imp-message-less').parent().hide();
        if ($.mobile.activePage.attr('id') != 'message') {
            $.mobile.changePage('#message', 'slide', false, true);
        }
        HordeMobile.doAction(
            'showMessage',
            {
                uid: ImpMobile.toRangeString(o),
                view: mailbox,
            },
            ImpMobile.messageLoaded);
    },

    /**
     * Navigates to the next or previous message.
     *
     * @param integer|object dir  A swipe event or a jump length.
     */
    navigateMessage: function(dir)
    {
        if (typeof dir == 'object') {
            dir = dir.type == 'swipeleft' ? 1 : -1;
        }
        var pos = ImpMobile.messages[ImpMobile.uid] + dir, newuid;
        $.each(ImpMobile.messages, function(uid, messagepos) {
            if (messagepos == pos) {
                newuid = uid;
                return false;
            }
        });
        if (!newuid || !ImpMobile.data[newuid]) {
            return;
        }
        ImpMobile.toMessage(ImpMobile.data[newuid].mbox, newuid);
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
            ImpMobile.uid = r.message.uid;
            $('#imp-message-title').html(data.title);
            $('#imp-message-subject').html(data.subject);
            $('#imp-message-from').text(data.from[0].personal);
            $('#imp-message-body').html(data.msgtext);
            headers.text('');
            $.each(data.headers, function(k, header) {
                if (header.value) {
                    headers.append($('<tr>').append($('<td class="imp-header-label">').html(header.name + ':')).append($('<td>').html(header.value)));
                }
                if (header.id == 'Date') {
                    $('#imp-message-date').text(header.value);
                }
            });
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
     * @param string mailbox  A mailbox name.
     * @param string uid      A message UID.
     */
    compose: function(mailbox, uid)
    {
        var o = {};
        o[mailbox] = [ uid ];
        $('#imp-compose-title').html(IMP.text.new_message);
        /*
        $('#imp-message-subject').text('');
        $('#imp-message-from').text('');
        $('#imp-message-body').text('');
        $('#imp-message-date').text('');
        $('#imp-message-more').parent().show();
        $('#imp-message-less').parent().hide();
        */
        if ($.mobile.activePage.attr('id') != 'compse') {
            $.mobile.changePage('#compose', 'slide', false, true);
        }
        /*
        HordeMobile.doAction(
            'showMessage',
            {
                uid: ImpMobile.toRangeString(o),
                view: mailbox,
            },
            ImpMobile.messageLoaded);
        */
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
            $('#imp-compose-cache').setValue(d.imp_compose);
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
        window.setTimeout(function () {
            if ($.mobile.activePage.attr('id') == 'compose') {
                window.history.back();
            } else if ($.mobile.activePage.attr('id') == 'notification') {
                $.mobile.activePage.bind('pagehide', function (e) {
                    $(e.currentTarget).unbind(e);
                    window.history.back();
                });
            }
        }, 0);
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
                ImpMobile.navigateMessage(id == 'imp-message-prev' ? -1 : 1);
                return;

            case 'imp-compose-submit':
                if (!ImpMobile.disabled) {
                    ImpMobile.uniqueSubmit('sendMessage');
                }
                return;
            }

            if (elt.hasClass('imp-folder')) {
                var link = elt.find('a[mailbox]');
                ImpMobile.toMailbox(link.attr('mailbox'), link.text());
                return;

            } else if (elt.hasClass('imp-message')) {
                ImpMobile.toMessage(elt.attr('data-imp-mailbox'), elt.attr('data-imp-uid'));
                return;
            } else if (elt.hasClass('imp-compose')) {
                ImpMobile.compose(elt.attr('data-imp-mailbox'), elt.attr('data-imp-uid'));
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
    }

};

// JQuery Mobile setup
$(ImpMobile.onDocumentReady);


var IMP_JS = {

    iframeInject: function(id, data)
    {
        id = $('#' + id);
        var d = id.get(0).contentWindow.document;

        d.open();
        d.write(data);
        d.close();

        id.show().prev().remove();
        this.iframeResize(id);
    },

    iframeResize: function(id)
    {
        id.css('height', id.get(0).contentWindow.document.lastChild.scrollHeight + 'px' );

        // For whatever reason, browsers will report different heights
        // after the initial height setting.
        window.setTimeout(function() { this.iframeResize2(id); }.bind(this), 300);
    },

    iframeResize2: function(id)
    {
        var lc = id.get(0).contentWindow.document.lastChild;

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
