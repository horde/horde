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

        $('#entry :jqmData(role="content")').hide();
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

        var html = [];

        $.each(r.entry, function(k, v) {
            html.push('<div data-role="collapsible"><h3>' + k + '</h3><div>');
            $.each(v, function(k2, v2) {
                html.push('<div class="turba-entry-label">' + v2.l + '</div>');
                if (v2.u) {
                    html.push('<ul data-role="listview" data-inset="true">' +
                        '<li><a data-ajax="false" href=' + v2.u + '>' + v2.v +
                        '</a></li></ul>');
                } else {
                    html.push('<div class="turba-entry-value">' + v2.v + '</div>');
                }
            });
            html.push('</div></div>');
        });

        $('#turba-entry-data')
            .html(html.join(''))
            .collapsibleset('refresh')
            .find(':jqmData(role="listview")').listview();
        $('#entry :jqmData(role="content")').show();
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
