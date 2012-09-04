/**
 * jQuery Mobile UI Turba application logic.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you did
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author Michael Slusarz <slusarz@horde.org>
 */
var TurbaMobile = {

    /**
     * Event handler for the pagebeforechange event that implements loading of
     * deep-linked pages.
     *
     * @param object e     Event object.
     * @param object data  Event data.
     */
    toPage: function(e, data)
    {
        switch (data.options.parsedUrl.view) {
        case 'entry':
            TurbaMobile.entry(data);
            e.preventDefault();
            break;
        }
    },

    /**
     * View an entry.
     *
     * @param object data  Page change data object.
     */
    entry: function(data)
    {
        var purl = data.options.parsedUrl;

        HordeMobile.changePage('entry', data);

        $('#turba-entry-dl').hide();
        HordeMobile.doAction(
            'smartmobileEntry',
            {
                key: purl.params.key,
                source: purl.params.source
            },
            TurbaMobile.entryLoaded
        );
    },

    /**
     * Callback method after an entry has been loaded.
     *
     * @param object r  The Ajax response object.
     */
    entryLoaded: function(r)
    {
        if (r.error) {
            HordeMobile.changePage('browse');
            return;
        }

        if (r.name) {
            $('#turba-entry-name-block').show();
            $('#turba-entry-name').text(r.name);
        } else {
            $('#turba-entry-name-block').hide();
        }

        if (r.email) {
            $('#turba-entry-email-block').show();
            if (r.email_link) {
                $('#turba-entry-email').hide();
                $('#turba-entry-email-list').html(
                    '<li><a data-ajax="false" href=' + r.email_link + '>' + r.email + '</a></li>'
                ).listview('refresh');
            } else {
                $('#turba-entry-email').text(r.email).show();
                $('#turba-entry-email-list').html('').listview('refresh');
            }
        } else {
            $('#turba-entry-email-block').hide();
        }

        $('#turba-entry-dl').show();
    },

    /**
     * Event handler for the document-ready event, responsible for the initial
     * setup.
     */
    onDocumentReady: function()
    {
        // Set up HordeMobile.
        $(document).bind('pagebeforechange', TurbaMobile.toPage);
    }

};

// JQuery Mobile setup
$(TurbaMobile.onDocumentReady);
