/**
 * jQuery Mobile UI application logic.
 *
 * Copyright 2005-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */
var ImpMobile = {

    // Vars used and defaulting to null/false:
    // /**
    //  * UID of the currently displayed message.
    //  */
    // uid,

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
                list.append(
                    $('<li class="' + c + '" data-imp-mailbox="' + data.view + '" data-imp-uid="' + data.imapuid + '">').append(
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
        ImpMobile.toMessage(ImpMobile.data[newuid].view, newuid);
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
            }

            if (elt.hasClass('imp-folder')) {
                var link = elt.find('a[mailbox]');
                ImpMobile.toMailbox(link.attr('mailbox'), link.text());
                return;

            } else if (elt.hasClass('imp-message')) {
                ImpMobile.toMessage(elt.attr('data-imp-mailbox'), elt.attr('data-imp-uid'));
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

        IMP.iframeInject = function(id, data)
        {
            id = $('#' + id);
            var d = id.get(0).contentWindow.document;

            d.open();
            d.write(data);
            d.close();

            id.show().prev().remove();
            IMP.iframeResize(id);
        };

        IMP.iframeResize = function(id)
        {
            id.css('height', id.get(0).contentWindow.document.lastChild.scrollHeight + 'px' );

            // For whatever reason, browsers will report different heights
            // after the initial height setting.
            window.setTimeout(function() { IMP.iframeResize2(id); }, 300);
        };

        IMP.iframeResize2 = function(id)
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
        };

        $(document).click(ImpMobile.clickHandler);
        $(document).bind('swipeleft', ImpMobile.navigateMessage);
        $(document).bind('swiperight', ImpMobile.navigateMessage);
    }

};

// JQuery Mobile setup
$(ImpMobile.onDocumentReady);
