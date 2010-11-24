/**
 * jQuery Mobile UI application logic.
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

/* ImpMobile object. */
var ImpMobile = {

    /**
     * Perform an Ajax action
     *
     * @param string action      The AJAX request
     * @param object params      The parameter hash
     * @param function callback  The callback function
     */
    doAction: function(action, params, callback)
    {
        $.post(IMP.conf.URI_AJAX + action, params, callback, 'json');
    },

    // Convert object to an IMP UID Range string. See IMP::toRangeString()
    // ob = (object) mailbox name as keys, values are array of uids.
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
        $.mobile.pageLoading();
        ImpMobile.doAction(
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
        var list = $('#imp-mailbox-list');
        $.mobile.pageLoading(true);
        if (r.response && r.response.ViewPort) {
            $.each(r.response.ViewPort.data, function(key, data) {
                list.append(
                    $('<li class="imp-message" data-imp-mailbox="' + data.view + '" data-imp-uid="' + data.imapuid + '">').append(
                        $('<h3>').append(
                            $('<a href="#">').html(data.subject))).append(
                        $('<p class="ui-li-aside">').text(data.date)).append(
                        $('<p>').text(data.from)));
            });
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
        $.mobile.changePage('#message', 'slide', false, true);
        $.mobile.pageLoading();
        ImpMobile.doAction(
            'showMessage',
            {
                uid: ImpMobile.toRangeString(o),
                view: mailbox,
            },
            ImpMobile.messageLoaded);
    },

    /**
     * Callback method after the message has been loaded.
     *
     * @param object r  The Ajax response object.
     */
    messageLoaded: function(r)
    {
        $.mobile.pageLoading(true);
        if (r.response && r.response.message && !r.response.message.error) {
            var data = r.response.message;
            $('#imp-message-title').html(data.title);
            $('#imp-message-subject').html(data.subject);
            $('#imp-message-from').text(data.from[0].personal);
            $('#imp-message-body').html(data.msgtext);
            $.each(data.headers, function(k, header) {
                if (header.id == 'Date') {
                    $('#imp-message-date').text(header.value);
                }
            });
            $.each(data.js, function(k, js) {
                $.globalEval(js);
            });
        }
    },

    /**
     * Catch-all event handler for the click event.
     *
     * @param object e  An event object.
     */
    clickHandler: function(e)
    {
        var elt = $(e.target),
            orig = $(e.target),
            id;

        while (elt) {
            id = elt.attr('id');

            switch (id) {
            }

            if (elt.hasClass('imp-folder')) {
                var link = elt.find('a[mailbox]');
                ImpMobile.toMailbox(link.attr('mailbox'), link.text());
                break;

            } else if (elt.hasClass('imp-message')) {
                ImpMobile.toMessage(elt.attr('data-imp-mailbox'), elt.attr('data-imp-uid'));
                break;
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
        // Global ajax options.
        $.ajaxSetup({
            dataFilter: function(data, type)
            {
                // Remove json security token
                filter = /^\/\*-secure-([\s\S]*)\*\/s*$/;
                return data.replace(filter, "$1");
            }
        });

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
    }

};

// JQuery Mobile setup
$(ImpMobile.onDocumentReady);
