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
        $("#dayview [data-role=content] ul").detach();
        $(".kronolithDayDate").html(KronolithMobile.currentDate.toString(Kronolith.conf.date_format));

        var list = $('<ul>').attr({'data-role': 'listview'});
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
        } else {
            list.append($('<li>').append(Kronolith.text.noevents));
        }
        list.listview();
        $("#dayview [data-role=content]").append(list);
    },

    loadEvent: function(cal, id, d)
    {
        $.post(Kronolith.conf.URI_AJAX + 'getEvent',
               {'cal': cal, 'id': id, 'date': d.toString('yyyyMMdd')},
                KronolithMobile.loadEventCallback,
               'json');
    },

    loadEventCallback: function(data)
    {
         $('#eventview [data-role=content] ul').detach();
         var event = data.response.event;
         var list = $('<ul>').attr({'data-role': 'listview', 'data-inset': true});

         // @TODO: Use css classes

         // Title and location
         var text = '<strong>' + event.t + '</strong>'
          + '<div style="color:grey">' + event.l + '</div>';

         // Time
         if (event.r) {
             // Recurrence still TODO
             text = text + '<div>Recurrence</div>';
         } else if (event.al) {
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
         $('#eventview [data-role=content]').append(list);
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
    },

    showPrevMonth: function()
    {
        var d = KronolithMobile.parseDate($('#kronolithMinicalDate').data('date'));
        KronolithMobile.buildCal(d.addMonths(-1));
    },

    showNextMonth: function()
    {
        var d = KronolithMobile.parseDate($('#kronolithMinicalDate').data('date'));
        KronolithMobile.buildCal(d.addMonths(1));
    },

    /**
     * Calculates first and last days being displayed.
     *
     * @var Date date    The date of the view.
     * @var string view  A view name.
     *
     * @return array  Array with first and last day of the view.
     */
    viewDates: function(date, view)
    {
        var start = date.clone(), end = date.clone();

        switch (view) {
        case 'week':
            start.moveToBeginOfWeek(Kronolith.conf.week_start);
            end.moveToEndOfWeek(Kronolith.conf.week_start);
            break;
        case 'month':
            start.setDate(1);
            start.moveToBeginOfWeek(Kronolith.conf.week_start);
            end.moveToLastDayOfMonth();
            end.moveToEndOfWeek(Kronolith.conf.week_start);
            break;
        case 'year':
            start.setDate(1);
            start.setMonth(0);
            end.setMonth(11);
            end.moveToLastDayOfMonth();
            break;
        case 'agenda':
            end.add(6).days();
            break;
        }

        return [start, end];
    },
    /**
     * Creates a mini calendar suitable for the navigation calendar and the
     * year view.
     *
     * @param Element tbody    The table body to add the days to.
     * @param Date date        The date to show in the calendar.
     * @param string view      The view that's displayed, determines which days
     *                         in the mini calendar are highlighted.
     * @param string idPrefix  If present, each day will get a DOM ID with this
     *                         prefix
     */
    buildCal: function(date)
    {
        var tbody = $('#kronolithMinical table tbody');
        var dates = this.viewDates(date, 'month'), day = dates[0].clone(),
        today = Date.today(), dateString, td, tr, i;

        // Remove old calendar rows.
        tbody.children().remove();

        // Update title
        $('#kronolithMinicalDate')
            .data('date', date.toString('yyyyMMdd'))
            .html(date.toString('MMMM yyyy'));
        for (i = 0; i < 42; i++) {
            dateString = day.toString('yyyyMMdd');
            // Create calendar row and insert week number.
            if (day.getDay() == Kronolith.conf.week_start) {
                tr = $('<tr>');
                tbody.append(tr);
            }

            // Insert day cell.
            td = $('<td>').data('date', dateString);
            if (day.getMonth() != date.getMonth()) {
                td.addClass('kronolithMinicalEmpty');
            }
            // Highlight today.
            if (day.equals(today)) {
                td.addClass('kronolithToday');
            }
            td.html(day.getDate());
            tr.append(td);
            day.next().day();
        }
    },

    /**
     * Parses a date attribute string into a Date object.
     *
     * For other strings use Date.parse().
     *
     * @param string date  A yyyyMMdd date string.
     *
     * @return Date  A date object.
     */
    parseDate: function(date)
    {
        var d = new Date(date.substr(0, 4), date.substr(4, 2) - 1, date.substr(6, 2));
        if (date.length == 12) {
            d.setHours(date.substr(8, 2));
            d.setMinutes(date.substr(10, 2));
        }
        return d;
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
    $('#dayview').bind('pageshow', function(event, ui) {
        $('body').bind('swipeleft', KronolithMobile.showNextDay);
        $('body').bind('swiperight', KronolithMobile.showPrevDay);
    });
    $('#dayview').bind('pagebeforehide', function(event, ui) {
        $('body').unbind('swipeleft', KronolithMobile.showNextDay);
        $('body').unbind('swiperight', KronolithMobile.showPrevDay);
    });
    $('#prevDay').bind('click', KronolithMobile.showPrevDay);
    $('#nextDay').bind('click', KronolithMobile.showNextDay);

    // Load today
    KronolithMobile.doAction('listEvents',
                             {'start': KronolithMobile.currentDate.toString("yyyyMMdd"), 'end': KronolithMobile.currentDate.toString("yyyyMMdd"), 'cal': Kronolith.conf.default_calendar},
                             KronolithMobile.listEventsCallback
    );

    // Set up the month view
    // Build the first month, should due this on first page show, but the
    // pagecreate event doesn't seem to fire for the internal page? Not sure how
    // else to do it, so just build the first month outright.
    var date = KronolithMobile.currentDate;
    KronolithMobile.buildCal(date);
    $('#kronolithMinicalPrev').bind('click', KronolithMobile.showPrevMonth);
    $('#kronolithMinicalNext').bind('click', KronolithMobile.showNextMonth);

    $('#monthview').bind('pageshow', function(event, ui) {
        $('body').bind('swipeleft', KronolithMobile.showNextMonth);
        $('body').bind('swiperight', KronolithMobile.showPrevMonth);
    });
    $('#monthview').bind('pagebeforehide', function(event, ui) {
        $('body').unbind('swipeleft', KronolithMobile.showNextMonth);
        $('body').unbind('swiperight', KronolithMobile.showPrevMonth);
    });
});

// Some Date extensions from horde.js that can't be included because of it's
// use of prototype.js
Date.prototype.getRealWeek = function()
{
    var monday = this;
    if (monday.getDay() < 1) {
        monday = monday.clone().next().monday();
    }
    return monday.getWeek();
};

/**
 * Moves a date to the end of the corresponding week.
 *
 * @return Date  The same Date object, now pointing to the end of the week.
 */
Date.prototype.moveToEndOfWeek = function(weekStart)
{
    var weekEndDay = weekStart + 6;
    if (weekEndDay > 6) {
        weekEndDay -= 7;
    }
    if (this.getDay() != weekEndDay) {
        this.moveToDayOfWeek(weekEndDay, 1);
    }
    return this;
};

/**
 * Moves a date to the begin of the corresponding week.
 *
 * @return Date  The same Date object, now pointing to the begin of the
 *               week.
 */
Date.prototype.moveToBeginOfWeek = function(weekStart)
{
    if (this.getDay() != weekStart) {
        this.moveToDayOfWeek(weekStart, -1);
    }
    return this;
};
