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
        var list = $('<ul>').attr({ 'data-role': 'listview' });
        var type = data.cal.split('|')[0], cal = data.cal.split('|')[1];
        if (data.events) {
            $.each(data.events, function(datestring, events) {
                $.each(events, function(index, event) {
                    // set .text() first, then .html() to escape
                    var d = $('<div style="color:' + Kronolith.conf.calendars[type][cal].bg + '">');
                    var item = $('<li>');
                    if (event.al) {
                        var timetext = Kronolith.text.allday;
                    } else {
                        var timetext = Date.parse(event.s).toString(Kronolith.conf.time_format) + ' - ' + Date.parse(event.e).toString(Kronolith.conf.time_format);
                    }
                    d.text(timetext + ' ' + event.t).html();
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
                    $('#eventcontent ul').detach();
                    var event = data.response.event;
                    console.log(event);

                    var list = $('<ul>').attr({ 'data-role': 'listview', 'data-inset': true });

                    // @TODO: Use css classes
                    var text = '<strong>' + event.t + '</strong>'
                     + '<div style="color:grey">' + event.l + '</div>';

                    if (event.al) {
                        text = text + '<div>' + Kronolith.text.allday + '</div>';
                    } else {
                        text = text + '<div>' + Date.parse(event.s).toString('D') + '</div>'
                            + '<div>' + Date.parse(event.s).toString(Kronolith.conf.time_format) + ' - ' + Date.parse(event.e).toString(Kronolith.conf.time_format);
                    }
                    var item = $('<li>').append(text);
                    list.append(item);

                    text = event.d;
                    list.append($('<li>').append(text));
                    list.listview();
                    $('#eventcontent').append(list);
               },
               'json');
    },

    showNextDay: function()
    {
        KronolithMobile.currentDate.addDays(1);
        KronolithMobile.doAction('listEvents',
                                 {'start': KronolithMobile.currentDate.toString("yyyyMMdd"), 'end': KronolithMobile.currentDate.toString("yyyyMMdd"), 'cal': Kronolith.conf.default_calendar},
                                 KronolithMobile.listEventsCallback
        );
    },

    showPrevDay: function()
    {
        KronolithMobile.currentDate.addDays(-1);
        KronolithMobile.doAction('listEvents',
                                 {'start': KronolithMobile.currentDate.toString("yyyyMMdd"), 'end': KronolithMobile.currentDate.toString("yyyyMMdd"), 'cal': Kronolith.conf.default_calendar},
                                 KronolithMobile.listEventsCallback
        );
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

    $('body').bind('swipeleft', KronolithMobile.showNextDay);
    $('body').bind('swiperight', KronolithMobile.showPrevDay);
    $('#prevDay').bind('click', KronolithMobile.showPrevDay);
    $('#nextDay').bind('click', KronolithMobile.showNextDay);

    // Load today
    KronolithMobile.doAction('listEvents',
                             {'start': KronolithMobile.currentDate.toString("yyyyMMdd"), 'end': KronolithMobile.currentDate.toString("yyyyMMdd"), 'cal': Kronolith.conf.default_calendar},
                             KronolithMobile.listEventsCallback
    );
});