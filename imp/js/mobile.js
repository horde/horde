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
        $.mobile.changePage('#mailbox', 'slide', false, true);
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
