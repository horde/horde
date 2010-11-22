/**
 * mobile.js - Base mobile application logic.
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
 var KronolithMobile = {

    /**
     * List of calendars we are displaying
     */
    calendars:  [],

    /**
     * List of calendars that are currently loaded for the current view
     */
    loadedCalendars: [],

    /**
     * Event cache
     */
    ecache: {},
    cacheStart: null,
    cacheEnd: null,

    /**
     * Temporary hack for displaying the first month view
     */
    monthIsLoaded: null,

    /**
     * The currently displayed view
     */
    view: 'day',

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
     * Load all events between start and end time.
     *
     * @param Date start
     * @param Date end
     * @param string view  The view we are loading for (month, day)
     */
    loadEvents: function(firstDay, lastDay, view)
    {
        // Clear out the loaded cal cache
        KronolithMobile.loadedCalendars = [];

        $.each(KronolithMobile.calendars, function(key, cal) {
            var startDay = firstDay.clone() , endDay = lastDay.clone(),
            cals = KronolithMobile.ecache[cal[0]];
            if (typeof cals != 'undefined' &&
                typeof cals[cal[1]] != 'undefined') {

                cals = cals[cal[1]];
                c = cals[startDay.dateString()];
                while (typeof c != 'undefined' && startDay.isBefore(endDay)) {
                    KronolithMobile.loadedCalendars.push(cal.join('|'));
                    if (KronolithMobile.view != 'month') {
                        KronolithMobile.insertEvents([startDay, startDay], view, cal.join('|'));
                    }
                    startDay.add(1).day();
                    c = cals[startDay.dateString()];
                }

                c = cals[endDay.dateString()];
                while (typeof c != 'undefined' && !startDay.isAfter(endDay)) {
                    KronolithMobile.loadedCalendars.push(cal);
                    if (KronolithMobile.view != 'month') {
                        KronolithMobile.insertEvents([endDay, endDay], view, cal.join('|'));
                    }
                    endDay.add(-1).day();
                    c = cals[endDay.dateString()];
                }
                if (startDay.compareTo(endDay) > 0) {
                    return;
                }
            }

            var start = startDay.dateString(), end = endDay.dateString();
            KronolithMobile.doAction('listEvents',
                                     {
                                       'start': start,
                                       'end': end,
                                       'cal': cal.join('|'),
                                       'view': view,
                                       'sig': start + end + (Math.random() + '').slice(2)
                                     },
                                     KronolithMobile.loadEventsCallback
            );
        });
    },

    /**
     * Sort a collection of events as returned from the ecache
     */
    sortEvents: function(events)
    {
        var e = [];

        // Need a native array to sort.
        $.each(events, function(id, event) {
            e.push(event);
        });
        return  e.sort(function(a, b) {
           sortA = a.sort;
           sortB = b.sort;
           return (sortA < sortB) ? -1 : (sortA > sortB) ? 1 : 0;
         });
    },

    /**
     * Callback for the loadEvents AJAX request. For now, assume we are in
     * day view, wait for all calendar responses to be received and then build
     * the event elements in the listview.
     *
     * @TODO: Event caching/view signature checking
     *
     * @param object data  The ajax response.
     */
    loadEventsCallback: function(data)
    {
        var start = KronolithMobile.parseDate(data.response.sig.substr(0, 8)),
            end = KronolithMobile.parseDate(data.response.sig.substr(8, 8)),
            dates = [start, end], view = data.response.view, list, events;

        data = data.response;
        KronolithMobile.storeCache(data.events, data.cal, dates, true);
        KronolithMobile.loadedCalendars.push(data.cal);
        KronolithMobile.insertEvents(dates, view, data.cal);
    },

    /**
     * Inserts events into current view.
     * For Day view, builds a new listview and attaches to the DOM.
     * For Month view, hightlights dates with events.
     */
    insertEvents: function(dates, view, cal)
    {
        var date, events, list;
        switch (view) {
            case 'day':
                // Make sure all calendars are loaded before rendering the view.
                // @TODO: Implement LIFO queue as in kronolith.js
                if (KronolithMobile.loadedCalendars.length != KronolithMobile.calendars.length
                    || KronolithMobile.view != view) {

                    if (KronolithMobile.timeoutId) {
                        window.clearTimeout(KronolithMobile.timeoutId);
                    }
                    KronolithMobile.timeoutId = window.setTimeout(function() {KronolithMobile.insertEvents(dates, view);}, 0);
                    return;
                }

                if (KronolithMobile.timeoutId) {
                    window.clearTimeout(KronolithMobile.timeoutId);
                    KronolithMobile.timoutId = false;
                }
                date = dates[0].clone();
                events = KronolithMobile.getCacheForDate(date.dateString());
                events = KronolithMobile.sortEvents(events);
                list = $('<ul>').attr({'data-role': 'listview'});
                $.each(events, function(index, event) {
                    list.append(KronolithMobile.buildDayEvent(event));
                });
                if (!list.children().length) {
                    list.append($('<li>').text(Kronolith.text.noevents));
                }
                list.listview();
                $("#dayview [data-role=content]").append(list);
                break;

            case 'month':
                var day = dates[0].clone();
                while (!day.isAfter(dates[1])) {
                    date = day.dateString();
                    events = KronolithMobile.getCacheForDate(date, cal);
                    $.each(events, function(key, event) {
                        $('#kronolithMonth' + date).addClass('kronolithContainsEvents');
                    });
                    day.next().day();
                }
                // Select current date.
                $('#kronolithMonth'+ $('kronolithMinicalDate').data('date')).addClass('kronolithSelected');
                KronolithMobile.selectMonthDay($('#kronolithMinicalDate').data('date'));
                break;
        }
    },

    /**
     * Build the dom element for an event to insert into the day view.
     *
     * @param string cal    The calendar name returned from the ajax request.
     * @param object event  The event object returned from the ajax request.
     * @param string id     The event identifier
     */
    buildDayEvent: function(event)
    {
        var id;
        if ($.isEmptyObject(event)) {
          return;
        }

        var cal = event.calendar, type = cal.split('|')[0], c = cal.split('|')[1],
        d = $('<div>').attr({'style': 'color:' + Kronolith.conf.calendars[type][c].bg}),
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
        a = $('<a>').attr({'href': '#eventview'}).click(function(ev) {
            $('#eventview [data-role=content] ul').detach();
            KronolithMobile.loadEvent(cal, event.id, Date.parse(event.e));
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
                                 {'cal': cal, 'id': id, 'date': d.toString('yyyyMMdd')},
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
         if (!data.response.event) {
             // @TODO: Error handling.
             return;
         }

         var event = data.response.event;
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
         var list = $('<ul>').addClass('kronolithEventDetail').attr({'data-role': 'listview', 'data-inset': true});
         var loc = false, t, recur;

         // Title and location
         var title = $('<div>').addClass('kronolithEventDetailTitle').append($('<h2>').text(e.t));
         var calendar = $('<p>').addClass('kronolithEventDetailCalendar').text(Kronolith.conf.calendars[e.ty][e.c]['name']);

         // Time
         t = $('<li>');
         if (e.r) {
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
                 var s = Date.parse(e.s);
                 recur = recur.replace('#{date}', s.toString('dS'));
                 recur = recur.replace('#{interval}', e.r.i);
                 t.append($('<div>').addClass('kronolithEventDetailRecurring').append(recur));
                 break;
             case 4:
                 // Monthly_Day
                 recur = Kronolith.text.recur.desc[e.r.t][(e.r.i > 1) ? 1 : 0];
                 recur = recur.replace('#{interval}', e.r.i);
                 break;
             case 5:
             case 6:
             case 7:
             default:
                 t.text('TODO');
             }
             t.append($('<div>').addClass('kronolithEventDetailRecurring').text(Kronolith.text.recur[e.r.t]));
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
                .append($('<a>').attr({'data-style': 'b', 'href': 'http://maps.google.com?q=' + encodeURIComponent(e.gl.lat + ',' + e.gl.lon)}).text(e.l));
         } else if (e.l) {
             loc = $('<div>').addClass('kronolithEventDetailLocation')
                .append($('<a>').attr({'href': 'http://maps.google.com?q=' + encodeURIComponent(e.l)}).text(e.l));
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
           list.append($('<li>').append($('<a>').attr({'rel': 'external', 'href': e.u}).text(e.u)));
         }

         list.listview();

         return list;
    },

    clearView: function(view)
    {
        switch (view) {
        case 'month':
            $('.kronolithDayDetail ul').detach();
            break;
        case 'day':
            $('#dayview [data-role=content] ul').detach();
        }
    },

    /**
     * Advance the day view by one day
     */
    showNextDay: function()
    {
        KronolithMobile.clearView('day');
        var d = $('.kronolithDayDate').data('date');
        d.addDays(1);
        $('.kronolithDayDate').text(d.toString('ddd') + ' ' + d.toString('d'));
        $('.kronolithDayDate').data('date', d);
        KronolithMobile.loadEvents(d, d, 'day');
    },

    /**
     * Move the day view back by one day
     */
    showPrevDay: function()
    {
        KronolithMobile.clearView('day');
        var d = $('.kronolithDayDate').data('date');
        d.addDays(-1);
        $('.kronolithDayDate').text(d.toString('ddd') + ' ' + d.toString('d'));
        KronolithMobile.loadEvents(d, d, 'day');
    },

    /**
     * Advance the month view ahead one month.
     * Note that we manually call insertEvents here
     */
    showPrevMonth: function()
    {
        KronolithMobile.clearView('month');
        var d = KronolithMobile.parseDate($('#kronolithMinicalDate').data('date'));
        d.addMonths(-1);
        var dates = KronolithMobile.viewDates(d, 'month');
        KronolithMobile.loadEvents(dates[0], dates[1], 'month');
        KronolithMobile.buildCal(d);
        KronolithMobile.insertEvents(dates, 'month');
    },

    showNextMonth: function()
    {
        KronolithMobile.clearView('month');
        var d = KronolithMobile.parseDate($('#kronolithMinicalDate').data('date'));
        d.addMonths(1);
        var dates = KronolithMobile.viewDates(d, 'month');
        KronolithMobile.loadEvents(dates[0], dates[1], 'month');
        KronolithMobile.buildCal(d);
        KronolithMobile.insertEvents(dates, 'month');
    },

    /**
     * Selects a day in the month view, and displays any events it may contain.
     *
     * @param string date  A date string in the form of yyyyMMdd.
     */
    selectMonthDay: function(date)
    {
        var ul = $('<ul>').attr({ 'data-role': 'listview '}),
        d = KronolithMobile.parseDate(date), today = new Date(),
        text;
        $('.kronolithDayDetail ul').detach();
        if (today.dateString() == d.dateString()) {
          text = Kronolith.text.today;
        } else if (today.clone().addDays(-1).dateString() == d.dateString()) {
          text = Kronolith.text.yesterday;
        } else if (today.clone().addDays(1).dateString() == d.dateString()) {
          text = Kronolith.text.tomorrow;
        } else {
          text = d.toString('ddd') + ' ' + d.toString('d')
        }
        $('.kronolithDayDetail h4').text(text);
        $('.kronolithSelected').removeClass('kronolithSelected');
        $('#kronolithMonth' + date).addClass('kronolithSelected');
        if ($('#kronolithMonth' + date).hasClass('kronolithContainsEvents')) {
            var events = KronolithMobile.getCacheForDate(date);
            events = KronolithMobile.sortEvents(events);
            $.each(events, function(k, e) {
                ul.append(KronolithMobile.buildDayEvent(e));
            });
        }
        ul.listview();
        $('.kronolithDayDetail').append(ul);
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
        case 'month':
            start.setDate(1);
            start.moveToBeginOfWeek(Kronolith.conf.week_start);
            end.moveToLastDayOfMonth();
            end.moveToEndOfWeek(Kronolith.conf.week_start);
            break;
        case 'summary':
            end.add(6).days();
            break;
        }

        return [start, end];
    },

    /**
     * Creates the month view calendar.
     *
     * @param Date date        The date to show in the calendar.
     */
    buildCal: function(date)
    {
        var tbody = $('#kronolithMinical table tbody');
        var dates = KronolithMobile.viewDates(date, 'month'), day = dates[0].clone(),
        today = Date.today(), dateString, td, tr, i;

        // Remove old calendar rows.
        tbody.children().remove();

        // Update title
        $('#kronolithMinicalDate')
            .data('date', date.toString('yyyyMMdd'))
            .html(date.toString('MMMM yyyy'));

        for (i = 0; i < 42; i++) {
            dateString = day.toString('yyyyMMdd');

            // Create calendar row .
            if (day.getDay() == Kronolith.conf.week_start) {
                tr = $('<tr>');
                tbody.append(tr);
            }

            // Insert day cell.
            td = $('<td>').attr({'id': 'kronolithMonth' + dateString}).data('date', dateString);
            if (day.getMonth() != date.getMonth()) {
                td.addClass('kronolithMinicalEmpty');
            }

            // Highlight today.
            if (day.dateString() == today.dateString()) {
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

    storeCache: function(events, calendar, dates, createCache)
    {
        events = events || {};

        //calendar[0] == type, calendar[1] == calendar name
        calendar = calendar.split('|');
        if (!KronolithMobile.ecache[calendar[0]]) {
            if (!createCache) {
                return;
            }
            KronolithMobile.ecache[calendar[0]] = {};
        }
        if (!KronolithMobile.ecache[calendar[0]][calendar[1]]) {
            if (!createCache) {
                return;
            }
            KronolithMobile.ecache[calendar[0]][calendar[1]] = {};
        }
        var calHash = KronolithMobile.ecache[calendar[0]][calendar[1]];

        // Create empty cache entries for all dates.
        if (!!dates) {
            var day = dates[0].clone(), date;
            while (!day.isAfter(dates[1])) {
                date = day.dateString();
                if (!calHash[date]) {
                    if (!createCache) {
                        return;
                    }
                    if (!KronolithMobile.cacheStart || KronolithMobile.cacheStart.isAfter(day)) {
                        KronolithMobile.cacheStart = day.clone();
                    }
                    if (!KronolithMobile.cacheEnd || KronolithMobile.cacheEnd.isBefore(day)) {
                        KronolithMobile.cacheEnd = day.clone();
                    }
                    calHash[date] = {};
                }
                day.add(1).day();
            }
        }

        var cal = calendar.join('|');
        $.each(events, function(key, date) {
            // We might not have a cache for this date if the event lasts
            // longer than the current view
            if (typeof calHash[key] == 'undefined') {
                return;
            }

            // Store useful information in event objects.
            $.each(date, function(k, event) {
                event.calendar = cal;
                event.start = Date.parse(event.s);
                event.end = Date.parse(event.e);
                event.sort = event.start.toString('HHmmss')
                    + (240000 - parseInt(event.end.toString('HHmmss'), 10)).toPaddedString(6);
                event.id = k;
            });

            // Store events in cache.
            $.extend(calHash[key], date);
        });
    },

    /**
     * Return all events for a single day from all displayed calendars merged
     * into a single hash.
     *
     * @param string date  A yyyymmdd date string.
     *
     * @return Hash  An event hash which event ids as keys and event objects as
     *               values.
     */
    getCacheForDate: function(date, calendar)
    {
        if (calendar) {
            var cals = calendar.split('|');
            return KronolithMobile.ecache[cals[0]][cals[1]][date];
        }

        var events = {};
        $.each(KronolithMobile.ecache, function(key, type) {
            $.each(type, function(id, cal) {
                if (!Kronolith.conf.calendars[key][id].show) {
                    return;
                }
                if (typeof cal[date] != 'undefined') {
                    $.extend(events, cal[date]);
                }
           });
        });

        return events;
    },

    /**
     * Returns the currently displayed view, based on the visible page.
     *
     */
    currentPageView: function()
    {
        switch($.mobile.activePage) {
        case 'dayview':
            return 'day';
        case 'monthview':
            return 'month';
        }
    },

    /**
     * Handle swipe events for the current view.
     */
    handleSwipe: function(map)
    {
        switch (KronolithMobile.view) {
        case 'day':
            if (map.type == 'swipeleft') {
                KronolithMobile.showNextDay();
            } else {
                KronolithMobile.showPrevDay();
            }
            break;

        case 'month':
            if (map.type == 'swipeleft') {
                KronolithMobile.showNextMonth();
            } else {
                KronolithMobile.showPrevMonth();
            }
        }
    },

    onDocumentReady: function()
    {
        // Build list of calendars we want.
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

        // Day View
        $('.kronolithDayHeader .kronolithPrevDay').bind('click', KronolithMobile.showPrevDay);
        $('.kronolithDayHeader .kronolithNextDay').bind('click', KronolithMobile.showNextDay);
        $('#dayview').bind('pageshow', function(event, ui) {
            KronolithMobile.view = 'day';
        });

        // Event view
        $('#eventview').bind('pageshow', function(event, ui) {
            KronolithMobile.view = 'event';
        });

        // Set up the month view
        $('#kronolithMinicalPrev').bind('click', KronolithMobile.showPrevMonth);
        $('#kronolithMinicalNext').bind('click', KronolithMobile.showNextMonth);
        $('#monthview').bind('pageshow', function(event, ui) {
            KronolithMobile.view = 'month';
            if (!KronolithMobile.monthIsLoaded) {
                var d = KronolithMobile.viewDates($('.kronolithDayDate').data('date'), 'month');
                KronolithMobile.loadEvents(d[0], d[1], 'month');
                KronolithMobile.buildCal($('.kronolithDayDate').data('date'));
                KronolithMobile.insertEvents(d, 'month');
                KronolithMobile.selectMonthDay($('.kronolithDayDate').data('date').dateString());
                KronolithMobile.monthIsLoaded = true;
            }
        });

        $('td').live('click', function(e) {
            KronolithMobile.selectMonthDay($(this).data('date'));
        });

        // Load today's events.
        // @TODO once https://github.com/jquery/jquery-mobile/issues/issue/508
        // is fixed, move this to #dayview's pageshow event, as well as
        // fix monthview initialization.
        var currentDate = new Date();
        $(".kronolithDayDate").html(currentDate.toString('ddd') + ' ' + currentDate.toString('d'));
        $('.kronolithDayDate').data('date', currentDate);
        KronolithMobile.loadEvents(currentDate, currentDate, 'day');

        $('body').bind('swipeleft', KronolithMobile.handleSwipe);
        $('body').bind('swiperight', KronolithMobile.handleSwipe);
    }
};
$(KronolithMobile.onDocumentReady);