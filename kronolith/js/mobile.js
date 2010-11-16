/**
 * kronolithmobile.js - Base mobile application logic.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package Kronolith
 */
 KronolithMobile = {

    calendars:  [],
    loadedCalendars: [],
    events: [],

    /**
     * Perform an Ajax action
     *
     * @param string action      The AJAX request
     * @param object params      The parameter hash
     * @param function callback  The callback function
     */
    doAction: function(action, params, callback)
    {
        $.post(Kronolith.conf.URI_AJAX + action, params, callback, 'json');
    },

    /**
     * Load all events between start and end time. Returns short version of
     * event info.
     *
     * @param Date start
     * @param Date end
     */
    loadEvents: function(start, end)
    {
        KronolithMobile.loadedCalendars = [];
        KronolithMobile.events = [];
        for (cal in KronolithMobile.calendars) {
            cal = KronolithMobile.calendars[cal];
            KronolithMobile.doAction('listEvents',
                                     {
                                       'start': start.toString('yyyyMMdd'),
                                       'end': end.toString("yyyyMMdd"),
                                       'cal': cal[0] + '|' + cal[1]
                                     },
                                     KronolithMobile.listEventsCallback
            );
        }
    },

    /**
     * Used as sorting funtion to Array.sort for sorting events by start time
     */
    sortEvents: function(events)
    {
        return  events.sort(function(a, b) {
           sortA = a.e.s;
           sortB = b.e.s;
           return (sortA < sortB) ? -1 : (sortA > sortB) ? 1 : 0;
         });
    },

    /**
     * Callback for the listEvents AJAX request. For now, assume we are in
     * day view, wait for all calendar responses to be received and then build
     * the event elements in the listview.
     *
     * @param object data  The ajax response.
     */
    listEventsCallback: function(data)
    {
        var list;

        data = data.response;
        KronolithMobile.loadedCalendars.push(data.cal);
        if (data.events) {
            $.each(data.events, function(datestring, events) {
                $.each(events, function(index, event) {
                    KronolithMobile.events.push({ 'e': event, 'id': index, 'cal': data.cal });
                });
            });
        }

        if (KronolithMobile.loadedCalendars.length == KronolithMobile.calendars.length) {
            var events = KronolithMobile.sortEvents(KronolithMobile.events);
            list = $('<ul>').attr({'data-role': 'listview'});
            $.each(events, function(index, event) {
                list.append(KronolithMobile.buildDayEvent(event.cal, event.e, event.id));
            });
            if (!list.children().length) {
                list.append($('<li>').text(Kronolith.text.noevents));
            }
            list.listview();
            $("#dayview [data-role=content]").append(list);
        }
    },

    /**
     * Build the dom element for an event to insert into the day view.
     *
     * @param string cal    The calendar name returned from the ajax request.
     * @param object event  The event object returned from the ajax request.
     * @param string id     The event identifier
     */
    buildDayEvent: function(cal, event, id)
    {
        var type = cal.split('|')[0], c = cal.split('|')[1],
        d = $('<div>').attr({ 'style': 'color:' + Kronolith.conf.calendars[type][c].bg }),
        item = $('<li>'), a;

        // Time
        var timeWrapper = $('<div>').addClass('kronolithTimeWrapper');
        if (event.al) {
            timeWrapper.append(Kronolith.text.allday).html();
        } else {
            var startTime = Date.parse(event.s).toString(Kronolith.conf.time_format);
            var endTime = '- ' + Date.parse(event.e).toString(Kronolith.conf.time_format);
            timeWrapper
              .append($('<div>').addClass('kronolithStartTime').append(startTime))
              .append($('<div>').addClass('kronolithEndTime').append(endTime));
        }

        e = $('<h2>').text(event.t);
        l = $('<p>').addClass('kronolithDayLocation').text(event.l);
        d.append(timeWrapper).append(e).append(l);

        // Add the link to view the event detail.
        a = $('<a>').attr({'href': '#eventview'}).click(function(e) {
            KronolithMobile.loadEvent(cal, id, Date.parse(event.e));
        }).append(d);

        return item.append(a);
    },

    /**
     * Retrieve a single event from the server.
     *
     * @param string cal  The calendar identifier.
     * @param string id   The event identifier.
     * @param Date   d    The date the event occurs.
     */
    loadEvent: function(cal, id, d)
    {
        KronolithMobile.doAction('getEvent',
                                 { 'cal': cal, 'id': id, 'date': d.toString('yyyyMMdd') },
                                 KronolithMobile.loadEventCallback);
    },

    /**
     * Callback for loadEvent call.  Assume we are in Event view for now, build
     * the event view structure and attach to DOM.
     *
     * @param object data  The ajax response.
     */
    loadEventCallback: function(data)
    {
         var event, list, text;

         $('#eventview [data-role=content] ul').detach();
         if (!data.response.event) {
             // @TODO: Error handling.
             return;
         }

         event = data.response.event;

         var ul = KronolithMobile.buildEventView(event);
         $('#eventview [data-role=content]').append(ul);
    },

    /**
     * Build event view DOM structure and return the top event element.
     *
     * @param object e  The event structure returned from the ajax call.
     */
    buildEventView: function(e)
    {
      console.log(Kronolith);
         var list = $('<ul>').addClass('kronolithEventDetail').attr({ 'data-role': 'listview', 'data-inset': true });
         var loc = false, t;

         // Title and location
         var title = $('<div>').addClass('kronolithEventDetailTitle').append($('<h2>').text(e.t));
         var calendar = $('<p>').addClass('kronolithEventDetailCalendar').text(Kronolith.conf.calendars[e.ty][e.c]['name']);

         // Time
         t = $('<li>');
         if (e.r) {
             // Recurrence still TODO
             switch (e.r.t) {
             case 1:
                 // Daily
                 t.append($('<div>').addClass('kronolithEventDetailRecurring').text(Kronolith.text.recur[e.r.t]));
                 break;
             case 2:
                 // Weekly
                 recur = Kronolith.text.recur.desc[e.r.t][(e.r.i > 1) ? 1 : 0];
                 recur = recur.replace('#{weekday}', Kronolith.text.weekday[e.r.d - 1]);
                 recur = recur.replace('#{interval}', e.r.i);
                 t.append($('<div>').addClass('kronolithEventDetailRecurring').append(recur));
                 break;
             case 3:
                 // Monthly_Date
                 recur = Kronolith.text.recur.desc[e.r.t][(e.r.i > 1) ? 1 : 0];
                 s = Date.parse(e.s);
                 recur = recur.replace('#{date}', s.toString('dS'));
                 recur = recur.replace('#{interval}', e.r.i);
                 t.append($('<div>').addClass('kronolithEventDetailRecurring').append(recur));
                 break;
             case 4:
                 // Monthly
             case 5:
             case 6:
             case 7:
                 // Yearly
             }
             //t.append($('<div>').addClass('kronolithEventDetailRecurring').text(Kronolith.text.recur[e.r.t]));
         } else if (e.al) {
             t.append($('<div>').addClass('kronolithEventDetailAllDay').text(Kronolith.text.allday));
         } else {
             t.append($('<div>')
                .append($('<div>').addClass('kronolithEventDetailDate').text(Date.parse(e.s).toString('D'))
                .append($('<div>').addClass('kronolithEventDetailTime').text(Date.parse(e.s).toString(Kronolith.conf.time_format) + ' - ' + Date.parse(e.e).toString(Kronolith.conf.time_format))))
             );
         }
         list.append($('<li>').append(title).append(calendar).append(t));

         // Location
         if (e.gl) {
             loc = $('<div>').addClass('kronolithEventDetailLocation')
                .append($('<a>').attr({ 'data-style': 'b', 'href': 'http://maps.google.com?q=' + encodeURIComponent(e.gl.lat + ',' + e.gl.lon) }).text(e.l));
         } else if (e.l) {
             loc = $('<div>').addClass('kronolithEventDetailLocation')
                .append($('<a>').attr({ 'href': 'http://maps.google.com?q=' + encodeURIComponent(e.l) }).text(e.l));
         }
         if (loc) {
             list.append($('<li>').append(loc));
         }

         // Description
         if (e.d) {
           list.append($('<li>').append($('<div>').addClass('kronolithEventDetailDesc').text(e.d)));
         }

         // url
         if (e.u) {
           list.append($('<li>').append($('<a>').attr({ 'rel': 'external', 'href': e.u }).text(e.u)));
         }

         list.listview();

         return list;
    },

    showNextDay: function()
    {
        $("#dayview [data-role=content] ul").detach();
        KronolithMobile.currentDate.addDays(1);
        $(".kronolithDayDate").html(KronolithMobile.currentDate.toString(Kronolith.conf.date_format));
        KronolithMobile.loadEvents(KronolithMobile.currentDate, KronolithMobile.currentDate);
    },

    showPrevDay: function()
    {
        $("#dayview [data-role=content] ul").detach();
        KronolithMobile.currentDate.addDays(-1);
        $(".kronolithDayDate").html(KronolithMobile.currentDate.toString(Kronolith.conf.date_format));
        KronolithMobile.loadEvents(KronolithMobile.currentDate, KronolithMobile.currentDate);
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
    },

    onDocumentReady: function()
    {
        // Build list of calendars we want.
        KronolithMobile.calendars = [];
        $.each(Kronolith.conf.calendars, function(key, value) {
            $.each(value, function(cal, info) {
                if (info.show) {
                    KronolithMobile.calendars.push([key, cal]);
                }
            });
        });

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

        // Next and Prev day links for the day view.
        $('.kronolithDayHeader .kronolithPrevDay').bind('click', KronolithMobile.showPrevDay);
        $('.kronolithDayHeader .kronolithNextDay').bind('click', KronolithMobile.showNextDay);

        // Load today's events
        $(".kronolithDayDate").html(KronolithMobile.currentDate.toString(Kronolith.conf.date_format));
        KronolithMobile.loadEvents(KronolithMobile.currentDate, KronolithMobile.currentDate);

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
    }
};

// JQuery Mobile setup
$(KronolithMobile.onDocumentReady);

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
