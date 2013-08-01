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
        var purl = data.options.parsedUrl,
            tmp = $('#entry .smartmobile-back');

        /* We may have reached here from a group contact link. */
        if (HordeMobile.currentPage() == 'entry') {
            HordeMobile.updateHash(purl);

            if (tmp.attr('data-rel')) {
                tmp.removeClass($.mobile.activeBtnClass).blur();
                tmp.removeAttr('data-rel')
                    .find('.ui-btn-text').text(Turba.text.browse);
            } else {
                tmp.attr('data-rel', 'back')
                    .find('.ui-btn-text').text(Turba.text.group);
            }
        }

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

        var tmp,
            ted = $('#turba-entry-data').empty();

        $.each(r.entry, function(k, v) {
            var tmp2 = $('<div></div>');

            ted.append($('<div data-role="collapsible"></div>').append(
                $('<h3></h3>').text(k)
            ).append(tmp2));

            $.each(v, function(k2, v2) {
                tmp2.append(
                    $('<div class="turba-entry-label"></div>').text(v2.l)
                );
                if (v2.u) {
                    tmp2.append(
                        $('<ul data-role="listview" data-inset="true"></ul>').append(
                            $('<li></li>').append(
                                $('<a data-ajax="false"></a>')
                                    .attr('href', v2.u)
                                    .text(v2.v)
                            )
                        )
                    );
                } else {
                    tmp2.append(
                        $('<div class="turba-entry-value"></div>').text(v2.v)
                    );
                }
            });
        });

        if (r.group) {
            tmp = $('<ul></ul>')
                .attr('data-role', 'listview')
                .attr('data-inset', 'true');

            $.each(r.group.m, function(k, v) {
                tmp.append(
                    $('<li></li>').append(
                        $('<a></a>').attr('href', v.u).text(v.n)
                    )
                );
            });

            ted.append(
                $('<div></div>')
                    .attr('data-role', 'collapsible')
                    .append(
                        $('<h3></h3>').text(r.group.l)
                    ).append(
                        $('<div></div>').append(tmp)
                    )
            );
        }

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
