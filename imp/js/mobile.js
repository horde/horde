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
            },
            ImpMobile.messagesLoaded);
    },

    /**
     * Callback method after message list has been loaded.
     *
     * @param object r  The Ajax response object.
     */
    messagesLoaded: function(r)
    {
        var list = $('#imp-mailbox-list');
        $.mobile.pageLoading(true);
        if (r.response && r.response.ViewPort) {
            $.each(r.response.ViewPort.data, function(key, data) {
                list.append(
                    $('<li>').append(
                        $('<h3>').append(
                            $('<a href="#">').text(data.subject))).append(
                        $('<p class="ui-li-aside">').text(data.date)).append(
                        $('<p>').text(data.from)));
            });
            list.listview('refresh');
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

        $(document).click(ImpMobile.clickHandler);
    }

};

// JQuery Mobile setup
$(ImpMobile.onDocumentReady);
