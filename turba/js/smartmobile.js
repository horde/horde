/**
 * jQuery Mobile UI Turba application logic.
 *
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
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

        var tmp, tmp2,
            ted = $('#turba-entry-data');

        $.each(r.entry, function(k, v) {
            tmp = $('<div></div>');
            tmp2 = $('<div data-role="collapsible"></div>').append(
                $('<h3></h3>').text(k)
            ).append(tmp);
            ted.append(tmp2);

            $.each(v, function(k2, v2) {
                tmp.append(
                    $('<div class="turba-entry-label"></div>').text(v2.l)
                );
                if (v2.u) {
                    tmp.append(
                        $('<ul data-role="listview" data-inset="true"></ul>').append(
                            $('<li></li>').append(
                                $('<a data-ajax="false"></a>')
                                    .attr('href', v2.u)
                                    .text(v2.v)
                            )
                        )
                    );
                } else {
                    tmp.append(
                        $('<div class="turba-entry-value"></div>').text(v2.v)
                    );
                }
            });
        });

        ted.collapsibleset('refresh')
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
