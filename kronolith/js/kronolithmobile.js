/**
 * kronolithmobile.js - Base mobile application logic.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */
 KronolithMobile = {

    doAction: function(action, params, callback)
    {
        $.post(Kronolith.conf.URI_AJAX + action, params, callback, 'json');
    },

    listEventsCallback: function(data)
    {
        data = data.response;
        $("#daycontent ul").detach();
        $("#todayheader").html(KronolithMobile.currentDate.toString(Kronolith.conf.date_format));
        var list = $('<ul>').attr({'data-role': 'listview'});
        var type = data.cal.split('|')[0], cal = data.cal.split('|')[1];
        if (data.events) {
            $.each(data.events, function(datestring, events) {
                $.each(events, function(index, event) {
                    // set .text() first, then .html() to escape
                    var d = $('<div style="color:' + Kronolith.conf.calendars[type][cal].bg + '">');
                    var item = $('<li>').append();
                    d.text(Date.parse(event.s).toString(Kronolith.conf.time_format)
                        + ' - '
                        + Date.parse(event.e).toString(Kronolith.conf.time_format)
                        + ' '
                        + event.t).html();
                    var a = $('<a>').attr({'href': '#eventview'}).click(function(e) {
                        KronolithMobile.loadEvent(data.cal, index, Date.parse(event.e));
                    }).append(d);
                    list.append(item.append(a));
                });
            });
            list.listview();
            $("#daycontent").append(list);
        }
    },

    loadEvent: function(cal, idy, d)
    {
        $.post(Kronolith.conf.URI_AJAX + 'getEvent',
               {'cal': cal, 'id': idy, 'date': d.toString('yyyyMMdd')},
               function(data)
               {
                   $("#eventcontent").text(data.response.event.t);
               },
               'json');
    }
};

// JQuery Mobile setup
$(function() {
    // Global ajax options.
    $.ajaxSetup({
        dataFilter: function(data, type)
        {
            // Remove json security token
            filter = /^\/\*-secure-([\s\S]*)\*\/s*$/;
            return data.replace(filter, "$1");
        }
    });

    // For now, start at today's day view
    KronolithMobile.currentDate = new Date();

    $('body').bind('swipeleft', function(e) {
        KronolithMobile.currentDate.addDays(1);
        KronolithMobile.doAction('listEvents',
                                 {'start': KronolithMobile.currentDate.toString("yyyyMMdd"), 'end': KronolithMobile.currentDate.toString("yyyyMMdd"), 'cal': Kronolith.conf.default_calendar},
                                 KronolithMobile.listEventsCallback
        );
    });

    $('body').bind('swiperight', function(e) {
            KronolithMobile.currentDate.addDays(-1);
            KronolithMobile.doAction('listEvents',
                                     {'start': KronolithMobile.currentDate.toString("yyyyMMdd"), 'end': KronolithMobile.currentDate.toString("yyyyMMdd"), 'cal': Kronolith.conf.default_calendar},
                                     KronolithMobile.listEventsCallback
            );
    });

    // Load today
    KronolithMobile.doAction('listEvents',
                             {'start': KronolithMobile.currentDate.toString("yyyyMMdd"), 'end': KronolithMobile.currentDate.toString("yyyyMMdd"), 'cal': Kronolith.conf.default_calendar},
                             KronolithMobile.listEventsCallback
    );
});