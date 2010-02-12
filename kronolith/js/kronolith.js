/**
 * kronolith.js - Base application logic.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Jan Schneider <jan@horde.org>
 */

/* Trick some Horde js into thinking this is the parent Horde window. */
var frames = { horde_main: true },

/* Kronolith object. */
KronolithCore = {
    // Vars used and defaulting to null/false:
    //   DMenu, Growler, inAjaxCallback, is_logout, onDoActionComplete,
    //   daySizes, viewLoading, freeBusy

    view: '',
    ecache: $H(),
    holidays: [],
    tcache: $H(),
    efifo: {},
    eventsLoading: {},
    loading: 0,
    fbLoading: 0,
    date: Date.today(),
    tasktype: 'incomplete',
    growls: 0,
    alarms: [],
    wrongFormat: $H(),
    mapMarker: null,
    map: null,
    mapInitialized: false,

    doActionOpts: {
        onException: function(parentfunc, r, e)
        {
            /* Make sure loading images are closed. */
            this.loading--;
            if (!this.loading) {
                    $('kronolithLoading').hide();
            }
            this._closeRedBox();
            this.showNotifications([ { type: 'horde.error', message: Kronolith.text.ajax_error } ]);
            this.debug('onException', e);
        }.bind(this),
        onFailure: function(t, o) { KronolithCore.debug('onFailure', t); },
        evalJS: false,
        evalJSON: true
    },

    debug: function(label, e)
    {
        if (!this.is_logout && window.console && window.console.error) {
            window.console.error(label, Prototype.Browser.Gecko ? e : $H(e).inspect());
        }
    },

    /* 'action' -> if action begins with a '*', the exact string will be used
     *  instead of sending the action to the ajax handler. */
    doAction: function(action, params, callback, opts)
    {
        opts = Object.extend(this.doActionOpts, opts || {});
        params = $H(params);
        action = action.startsWith('*')
            ? action.substring(1)
            : Kronolith.conf.URI_AJAX + action;
        if (Kronolith.conf.SESSION_ID) {
            params.update(Kronolith.conf.SESSION_ID.toQueryParams());
        }
        opts.parameters = params.toQueryString();
        opts.onComplete = function(t, o) { this.doActionComplete(t, callback); }.bind(this);
        new Ajax.Request(action, opts);
    },

    doActionComplete: function(request, callback)
    {
        this.inAjaxCallback = true;

        if (!request.responseJSON) {
            if (++this.server_error == 3) {
                this.showNotifications([ { type: 'horde.error', message: Kronolith.text.ajax_timeout } ]);
            }
            this.inAjaxCallback = false;
            return;
        }

        var r = request.responseJSON;

        if (!r.msgs) {
            r.msgs = [];
        }

        if (r.response && Object.isFunction(callback)) {
            try {
                callback(r);
            } catch (e) {
                this.debug('doActionComplete', e);
            }
        }

        if (this.server_error >= 3) {
            r.msgs.push({ type: 'horde.success', message: Kronolith.text.ajax_recover });
        }
        this.server_error = 0;

        this.showNotifications(r.msgs);

        if (this.onDoActionComplete) {
            this.onDoActionComplete(r);
        }

        this.inAjaxCallback = false;
    },

    setTitle: function(title)
    {
        document.title = Kronolith.conf.name + ' :: ' + title;
        return title;
    },

    showNotifications: function(msgs)
    {
        if (!msgs.size() || this.is_logout) {
            return;
        }

        msgs.find(function(m) {
            switch (m.type) {
            case 'horde.ajaxtimeout':
                this.logout(Kronolith.conf.timeout_url);
                return true;

            case 'horde.alarm':
                // Only show one instance of an alarm growl.
                if (this.alarms.indexOf(m.alarm.id) != -1) {
                    break;
                }
                if (m.type == 'horde.alarm') {
                    this.alarms.push(m.alarm.id);
                }

                message = m.alarm.title.escapeHTML();
                if (!Object.isUndefined(m.alarm.ajax)) {
                    message = new Element('a')
                        .insert(message)
                        .observe('click', function() { this.go(m.alarm.ajax); }.bind(this));
                } else if (!Object.isUndefined(m.alarm.url)) {
                    message = new Element('a', { href: m.alarm.url })
                        .insert(message);
                }
                message = new Element('div')
                    .insert(message);
                if (m.alarm.user) {
                    var select = new Element('select');
                        $H(Kronolith.conf.snooze).each(function(snooze) {
                            select.insert(new Element('option', { value: snooze.key }).insert(snooze.value));
                        });
                    message.insert(' ').insert(select);
                }
                var growl = this.Growler.growl(message, {
                    className: 'horde-alarm',
                    life: 8,
                    log: false,
                    sticky: true
                });

                document.observe('Growler:destroyed', function() {
                    this.alarms = this.alarms.without(m.alarm.id);
                }.bind(this));

                if (m.alarm.user) {
                    select.observe('change', function() {
                        if (select.getValue()) {
                            new Ajax.Request(
                                Kronolith.conf.URI_SNOOZE,
                                { parameters: { alarm: m.alarm.id,
                                                snooze: select.getValue() },
                                  onSuccess: function() {
                                      this.Growler.ungrowl(growl);
                                  }.bind(this)});
                        }
                    }.bind(this));
                }
                break;

            case 'horde.error':
            case 'horde.warning':
            case 'horde.message':
            case 'horde.success':
                this.Growler.growl(m.message.escapeHTML(), {
                    className: m.type.replace('.', '-'),
                    life: 8,
                    log: true,
                    sticky: m.type == 'horde.error'
                });
                var notify = $('kronolithNotifications'),
                    className = m.type.replace(/\./, '-'),
                    order = 'horde-error,horde-warning,horde-message,horde-success,kronolithNotifications';
                if (!notify.className ||
                    order.indexOf(notify.className) > order.indexOf(className)) {
                    notify.className = className;
                }
                notify.update(Kronolith.text.alerts.interpolate({ count: ++this.growls }));
                notify.up().show();
                break;
            }
        }, this);
    },

    logout: function(url)
    {
        this.is_logout = true;
        this.redirect(url || (Kronolith.conf.URI_IMP + '/LogOut'));
    },

    redirect: function(url)
    {
        url = this.addSID(url);
        if (parent.frames.horde_main) {
            parent.location = url;
        } else {
            window.location = url;
        }
    },

    addSID: function(url)
    {
        if (!Kronolith.conf.SESSION_ID) {
            return url;
        }
        return this.addURLParam(url, Kronolith.conf.SESSION_ID.toQueryParams());
    },

    addURLParam: function(url, params)
    {
        var q = url.indexOf('?');

        if (q != -1) {
            params = $H(url.toQueryParams()).merge(params).toObject();
            url = url.substring(0, q);
        }
        return url + '?' + Object.toQueryString(params);
    },

    go: function(fullloc, data)
    {
        var locParts = fullloc.split(':');
        var loc = locParts.shift();

        switch (loc) {
        case 'day':
        case 'week':
        case 'month':
        case 'year':
        case 'agenda':
        case 'tasks':
            this.closeView(loc);
            var locCap = loc.capitalize();
            $('kronolithNav' + locCap).addClassName('on');

            switch (loc) {
            case 'day':
            case 'agenda':
            case 'week':
            case 'month':
            case 'year':
                var date = locParts.shift();
                if (date) {
                    date = this.parseDate(date);
                } else {
                    date = this.date;
                }

                if (this.view == loc && date.getYear() == this.date.getYear() &&
                    ((loc == 'year') ||
                     (loc == 'month' && date.getMonth() == this.date.getMonth()) ||
                     (loc == 'week' && date.getRealWeek() == this.date.getRealWeek()) ||
                     ((loc == 'day'  || loc == 'agenda') && date.dateString() == this.date.dateString()))) {
                         return;
                }

                this.updateView(date, loc);
                var dates = this.viewDates(date, loc);
                this._loadEvents(dates[0], dates[1], loc);
                if ($('kronolithView' + locCap)) {
                    this.viewLoading = true;
                    $('kronolithView' + locCap).appear({
                            queue: 'end',
                            afterFinish: function() {
                                if (loc == 'week' || loc == 'day') {
                                    this._calculateRowSizes(loc + 'Sizes', 'kronolithView' + locCap);
                                }
                                this.viewLoading = false; }.bind(this)
                    });
                }
                $('kronolithLoading' + loc).insert($('kronolithLoading').remove());
                this.updateMinical(date, loc);
                this.date = date;

                break;

            case 'tasks':
                var tasktype = locParts.shift() || this.tasktype;
                if ((this.view == loc && this.tasktype == tasktype) ||
                    !([ 'all', 'complete', 'incomplete', 'future' ].include(tasktype))) {
                    return;
                }
                this.tasktype = tasktype;
                [ 'All', 'Complete', 'Incomplete', 'Future' ].each(function(tasktype) {
                    $($('kronolithTasks' + tasktype).parentNode).removeClassName('activeTab');
                });
                $('kronolithTasks' + this.tasktype.capitalize()).parentNode.addClassName('activeTab');
                this._loadTasks(this.tasktype);
                if ($('kronolithView' + locCap)) {
                    this.viewLoading = true;
                    $('kronolithView' + locCap).appear({ queue: 'end', afterFinish: function() { this.viewLoading = false; }.bind(this) });
                }
                $('kronolithLoading' + loc).insert($('kronolithLoading').remove());
                this.updateMinical(this.date, loc);

                break;

            default:
                if ($('kronolithView' + locCap)) {
                    this.viewLoading = true;
                    $('kronolithView' + locCap).appear({ queue: 'end', afterFinish: function() { this.viewLoading = false; }.bind(this) });
                }
                break;
            }

            this._addHistory(fullloc);
            this.view = loc;
            break;

        case 'search':
            var cals = [], term = locParts[0],
                query = Object.toJSON({ title: term });
            this.closeView();
            this.updateView(null, 'search', term);
            $H(Kronolith.conf.calendars).each(function(type) {
                $H(type.value).each(function(calendar) {
                    if (calendar.value.show) {
                        cals.push(type.key + '|' + calendar.key);
                    }
                });
            });
            this.startLoading('search', query);
            this.doAction('SearchEvents',
                          { cals: cals.toJSON(), query: query },
                          function(r) {
                              // Hide spinner.
                              this.loading--;
                              if (!this.loading) {
                                  $('kronolithLoading').hide();
                              }
                              if (r.response.view != 'search' ||
                                  r.response.query != this.eventsLoading['search'] ||
                                  Object.isUndefined(r.response.events)) {
                                  return;
                              }
                              $H(r.response.events).each(function(calendars) {
                                  $H(calendars.value).each(function(events) {
                                      this.createAgendaDay(events.key);
                                      $H(events.value).each(function(event) {
                                          event.value.calendar = calendars.key;
                                          event.value.start = Date.parse(event.value.s);
                                          event.value.end = Date.parse(event.value.e);
                                          this._insertEvent(event, events.key, 'agenda');
                                      }, this);
                                  }, this);
                              }, this);
                          }.bind(this));
            this.viewLoading = true;
            $('kronolithViewAgenda').appear({ queue: 'end', afterFinish: function() { this.viewLoading = false; }.bind(this) });
            $('kronolithLoadingagenda').insert($('kronolithLoading').remove());
            this.updateMinical(this.date, 'search');
            this._addHistory(fullloc);
            this.view = 'agenda';
            break;

        case 'event':
            // Load view first if necessary.
            if (!this.view) {
                this.go(Kronolith.conf.login_view);
                this.go.bind(this, fullloc, data).defer();
                return;
            }

            switch (locParts.length) {
            case 0:
                // New event.
                this.editEvent();
                break;
            case 1:
                // New event on a certain date.
                this.editEvent(null, null, locParts[0]);
                break;
            case 3:
                // Editing event.
                this.editEvent(locParts[0], locParts[1], locParts[2]);
                break;
            default:
                return;
            }
            this.updateMinical(this.date, this.view);
            this._addHistory(fullloc);
            break;

        case 'task':
            switch (locParts.length) {
            case 0:
                this.editTask();
                break;
            case 2:
                this.editTask(locParts[0], locParts[1]);
                break;
            }
            this._addHistory(fullloc);
            break;

        case 'calendar':
            if (!this.view) {
                this.go(Kronolith.conf.login_view);
                this.go.bind(this, fullloc, data).defer();
                return;
            }
            this.editCalendar(locParts.join(':'));
            this._addHistory(fullloc);
            break;

        case 'options':
            this.closeView('iframe');
            this.iframeContent(loc, Kronolith.conf.prefs_url);
            this.setTitle(Kronolith.text.prefs);
            this._addHistory(loc);
            this.view = 'iframe';
            break;
        }
    },

    /**
     * Rebuilds one of the calendar views for a new date.
     *
     * @param Date date    The date to show in the calendar.
     * @param string view  The view that's rebuilt.
     * @param mixed data   Any additional data that might be required.
     */
    updateView: function(date, view, data)
    {
        this.holidays = [];

        switch (view) {
        case 'day':
            this.dayEvents = [];
            this.dayGroups = [];
            this.allDayEvents = [];
            $('kronolithViewDay').down('caption span').innerHTML = this.setTitle(date.toString('D'));
            $('kronolithViewDay').down('.kronolithAllDayContainer').writeAttribute('id', 'kronolithEventsDay' + date.dateString());
            break;

        case 'week':
            this.dayEvents = [];
            this.dayGroups = [];
            this.allDayEvents = [];
            var div = $('kronolithEventsWeek').down('div'),
                th = $('kronolithViewWeekHead').down('.kronolithWeekDay'),
                td = $('kronolithViewWeekHead').down('tbody td').next('td'),
                hourRow = $('kronolithViewWeekBody').down('tr'),
                dates = this.viewDates(date, view),
                today = Date.today(),
                day, i, hourCol;

            $('kronolithViewWeek').down('caption span').innerHTML = this.setTitle(Kronolith.text.week.interpolate({ week: date.getRealWeek() }));

            for (i = 0; i < 24; i++) {
                day = dates[0].clone();
                hourCol = hourRow.down('td').next('td');
                while (hourCol) {
                    hourCol.removeClassName('kronolithToday');
                    if (day.equals(today)) {
                        hourCol.addClassName('kronolithToday');
                    }
                    hourCol = hourCol.next('td');
                    day.next().day();
                }
                hourRow = hourRow.next('tr');
            }
            day = dates[0].clone();
            for (i = 0; i < 7; i++) {
                div.writeAttribute('id', 'kronolithEventsWeek' + day.dateString());
                th.store('date', day.dateString())
                    .down('span').innerHTML = day.toString('dddd, d');
                td.removeClassName('kronolithToday')
                    .down('div')
                    .writeAttribute('id', 'kronolithAllDay' + day.dateString());
                if (day.equals(today)) {
                    td.addClassName('kronolithToday');
                }
                div = div.next('div');
                th = th.next('td');
                td = td.next('td');
                day.next().day();
            }
            break;

        case 'month':
            var tbody = $('kronolithViewMonthBody'),
                dates = this.viewDates(date, view),
                day = dates[0].clone(), row;

            $('kronolithViewMonth').down('caption span').innerHTML = this.setTitle(date.toString('MMMM yyyy'));

            // Remove old rows. Maybe we should only rebuild the calendars if
            // necessary.
            tbody.childElements().each(function(row) {
                if (row.identify() != 'kronolithRowTemplate') {
                    row.remove();
                }
            });

            // Build new calendar view.
            while (!day.isAfter(dates[1])) {
                tbody.insert(this.createWeekRow(day, date.getMonth(), dates).show());
                day.next().week();
            }
            this._equalRowHeights(tbody);

            break;

        case 'year':
            var month;

            $('kronolithYearDate').innerHTML = this.setTitle(date.toString('yyyy'));

            // Build new calendar view.
            for (month = 0; month < 12; month++) {
                $('kronolithYear' + month).update(this.createYearMonth(date.getFullYear(), month, 'kronolithYear').show());
            }

            break;

        case 'agenda':
        case 'search':
            // Agenda days are only created on demand, if there are any events
            // to add.
            if (view == 'agenda') {
                var dates = this.viewDates(date, view),
                    day = dates[0].clone();
                $('kronolithAgendaDate').innerHTML = this.setTitle(Kronolith.text.agenda + ' ' + dates[0].toString('d') + ' - ' + dates[1].toString('d'));
            } else {
                $('kronolithViewAgenda').down('caption span').update(this.setTitle(Kronolith.text.searching.interpolate({ term: data })));
            }

            // Remove old rows. Maybe we should only rebuild the calendars if
            // necessary.
            tbody = $('kronolithViewAgendaBody').childElements().each(function(row) {
                if (row.identify() != 'kronolithAgendaTemplate' &&
                    row.identify() != 'kronolithAgendaNoItems') {
                    row.remove();
                }
            });

            break;
        }
    },

    /**
     * Closes the currently active view.
     */
    closeView: function(loc)
    {
        [ 'Day', 'Week', 'Month', 'Year', 'Tasks', 'Agenda' ].each(function(a) {
            a = $('kronolithNav' + a);
            if (a) {
                a.removeClassName('on');
            }
        });
        if (this.view && this.view != loc) {
            $('kronolithView' + this.view.capitalize()).fade({ queue: 'end' });
            this.view = null;
        }
    },

    /**
     * Creates a single row of day cells for usage in the month and multi-week
     * views.
     *
     * @param Date date        The first day to show in the row.
     * @param integer month    The current month. Days not from the current
     *                         month get the kronolithOtherMonth CSS class
     *                         assigned.
     * @param array viewDates  Array of Date objects with the start and end
     *                         dates of the view.
     *
     * @return Element  The element rendering a week row.
     */
    createWeekRow: function(date, month, viewDates)
    {
        var day = date.clone(), today = new Date().dateString(),
            start = viewDates[0].dateString(), end = viewDates[1].dateString(),
            row, cell, dateString;

        // Create a copy of the row template.
        row = $('kronolithRowTemplate').cloneNode(true);
        row.removeAttribute('id');

        // Fill week number and day cells.
        cell = row.down()
            .setText(date.getRealWeek())
            .store('date', date.dateString())
            .next();
        while (cell) {
            dateString = day.dateString();
            cell.id = 'kronolithMonthDay' + dateString;
            cell.store('date', dateString);
            cell.removeClassName('kronolithOtherMonth').removeClassName('kronolithToday');
            if (day.getMonth() != month) {
                cell.addClassName('kronolithOtherMonth');
            }
            if (dateString == today) {
                cell.addClassName('kronolithToday');
            }
            new Drop(cell);
            cell.store('date', dateString)
                .down('.kronolithDay')
                .store('date', dateString)
                .innerHTML = day.getDate();

            cell = cell.next();
            day.add(1).day();
        }

        return row;
    },

    /**
     * Creates a table row for a single day in the agenda view, if it doesn't
     * exist yet.
     *
     * @param string date    The day to show in the row.
     *
     * @return Element  The element rendering a week row.
     */
    createAgendaDay: function(date)
    {
        // Exit if row exists already.
        if ($('kronolithAgendaDay' + date)) {
            return;
        }

        // Create a copy of the row template.
        var body = $('kronolithViewAgendaBody'),
            row = $('kronolithAgendaTemplate').cloneNode(true);
        row.removeAttribute('id');

        // Fill week number and day cells.
        row.down()
            .setText(this.parseDate(date).toString('D'))
            .store('date', date)
            .next()
            .writeAttribute('id', 'kronolithAgendaDay' + date);

        // Insert row.
        var nextRow;
        body.childElements().each(function(elm) {
            if (elm.down().retrieve('date') > date) {
                nextRow = elm;
                return;
            }
        });
        if (nextRow) {
            nextRow.insert({ before: row.show() });
        } else {
            body.insert(row.show());
        }

        return row;
    },

    /**
     * Creates a table for a single month in the year view.
     *
     * @param integer year     The year.
     * @param integer month    The month.
     * @param string idPrefix  If present, each day will get a DOM ID with this
     *                         prefix
     *
     * @return Element  The element rendering a month table.
     */
    createYearMonth: function(year, month, idPrefix)
    {
        // Create a copy of the month template.
        var table = $('kronolithYearTemplate').cloneNode(true),
            tbody = table.down('tbody');
        table.removeAttribute('id');
        tbody.writeAttribute('id', 'kronolithYearTable' + month)

        // Set month name.
        table.down('span')
            .store('date', year.toPaddedString(4) + (month + 1).toPaddedString(2) + '01')
            .innerHTML = Date.CultureInfo.monthNames[month];

        // Build month table.
        this.buildMinical(tbody, new Date(year, month, 1), null, idPrefix);

        return table;
    },

    _equalRowHeights: function(tbody)
    {
        var children = tbody.childElements();
        children.invoke('setStyle', { height: (100 / (children.size() - 1)) + '%' });
    },

    /**
     * Calculates some dimensions for the day and week view.
     *
     * @param string storage  Property name where the dimensions are stored.
     * @param string view     DOM node ID of the view.
     */
    _calculateRowSizes: function(storage, view)
    {
        if (!Object.isUndefined(this[storage])) {
            return;
        }

        this[storage] = {};
        var tr = $(view).down('.kronolithViewBody tr'),
            td = tr.down('td').next('td'), tdTop, tdHeight,
            tdAlign = td.getStyle('verticalAlign'),
            tr2 = tr.next('tr'),
            td2 = tr2.down('td').next('td'), td2Top,
            div = new Element('div').setStyle({ width: '1px', height: '1px' });

        td.insert({ top: div });
        tdTop = div.cumulativeOffset().top;
        td.setStyle({ verticalAlign: 'bottom' });
        td.insert({ bottom: div });
        tdHeight = div.cumulativeOffset().top + parseInt(td.getStyle('lineHeight')) - tdTop;
        td.setStyle({ verticalAlign: tdAlign });
        td2.insert({ top: div });
        td2Top = div.cumulativeOffset().top;
        div.remove();

        this[storage].height = td2Top - tdTop;
        this[storage].spacing = this[storage].height - parseInt(td.getStyle('height'));
    },

    /**
     * Rebuilds the mini calendar.
     *
     * @param Date date    The date to show in the calendar.
     * @param string view  The view that's displayed, determines which days in
     *                     the mini calendar are highlighted.
     */
    updateMinical: function(date, view)
    {
        // Update header.
        $('kronolithMinicalDate').store('date', date.dateString()).innerHTML = date.toString('MMMM yyyy');

        this.buildMinical($('kronolithMinical').down('tbody'), date, view);
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
    buildMinical: function(tbody, date, view, idPrefix)
    {
        var dates = this.viewDates(date, 'month'), day = dates[0].clone(),
            date7 = date.clone().add(1).week(), today = Date.today(),
            weekStart, weekEnd, weekEndDay, dateString, td, tr, i;

        // Remove old calendar rows. Maybe we should only rebuild the minical
        // if necessary.
        tbody.childElements().invoke('remove');

        for (i = 0; i < 42; i++) {
            dateString = day.dateString();
            // Create calendar row and insert week number.
            if (day.getDay() == Kronolith.conf.week_start) {
                tr = new Element('tr');
                tbody.insert(tr);
                td = new Element('td', { className: 'kronolithMinicalWeek' })
                    .store('weekdate', dateString);
                td.innerHTML = day.getRealWeek();
                tr.insert(td);
                weekStart = day.clone();
                weekEnd = day.clone();
                weekEnd.add(6).days();
            }

            // Insert day cell.
            td = new Element('td').store('date', dateString);
            if (day.getMonth() != date.getMonth()) {
                td.addClassName('kronolithMinicalEmpty');
            } else if (!Object.isUndefined(idPrefix)) {
                td.id = idPrefix + dateString;
            }

            // Highlight days currently being displayed.
            if (view &&
                (view == 'month' ||
                 (view == 'week' && date.between(weekStart, weekEnd)) ||
                 (view == 'day' && date.equals(day)) ||
                 (view == 'agenda' && !day.isBefore(date) && day.isBefore(date7)))) {
                td.addClassName('kronolithSelected');
            }

            // Highlight today.
            if (day.equals(today)) {
                td.addClassName('kronolithToday');
            }
            td.innerHTML = day.getDate();
            tr.insert(td);
            day.next().day();
        }
    },

    /**
     * Inserts a calendar entry in the sidebar menu.
     *
     * @param string type  The calendar type.
     * @param string id    The calendar id.
     * @param object cal   The calendar object.
     * @param Element div  Container DIV where to add the entry (optional).
     */
    insertCalendarInList: function(type, id, cal, div)
    {
        if (!div) {
            div = this.getCalendarList(type, cal.owner);
        }
        if (cal.owner) {
            div.insert(new Element('span', { className: 'kronolithCalEdit' })
                       .insert('&rsaquo;'));
        }
        div.insert(new Element('div', { className: cal.show ? 'kronolithCalOn' : 'kronolithCalOff' })
                   .store('calendar', id)
                   .store('calendarclass', type)
                   .setStyle({ backgroundColor: cal.bg, color: cal.fg })
                   .update(cal.name.escapeHTML()));
    },

    /**
     * Rebuilds the list of calendars.
     */
    updateCalendarList: function()
    {
        var my = 0, shared = 0, ext = $H(), extNames = $H(),
            remote, holidays, api, div;

        $H(Kronolith.conf.calendars.internal).each(function(cal) {
            if (cal.value.owner) {
                my++;
            } else {
                shared++;
            }
            this.insertCalendarInList('internal', cal.key, cal.value);
        }, this);
        if (my) {
            $('kronolithMyCalendars').show();
        } else {
            $('kronolithMyCalendars').hide();
        }
        if (shared) {
            $('kronolithSharedCalendars').show();
        } else {
            $('kronolithSharedCalendars').hide();
        }

        my = 0;
        shared = 0;
        $H(Kronolith.conf.calendars.tasklists).each(function(cal) {
            if (cal.value.owner) {
                my++;
            } else {
                shared++;
            }
            this.insertCalendarInList('tasklists', cal.key, cal.value);
        }, this);
        if (my) {
            $('kronolithMyTasklists').show();
        } else {
            $('kronolithMyTasklists').hide();
        }
        if (shared) {
            $('kronolithSharedTasklists').show();
        } else {
            $('kronolithSharedTasklists').hide();
        }

        $H(Kronolith.conf.calendars.external).each(function(cal) {
            var parts = cal.key.split('/'), api = parts.shift();
            if (!ext.get(api)) {
                ext.set(api, $H());
            }
            ext.get(api).set(parts.join('/'), cal.value);
            extNames.set(api, cal.value.api);
        });
        ext.each(function(api) {
            $('kronolithExternalCalendars')
                .insert(new Element('h3')
                        .insert({ bottom: extNames.get(api.key).escapeHTML() }))
                .insert(new Element('div', { id: 'kronolithExternalCalendar' + api.key, className: 'kronolithCalendars' }));
            api.value.each(function(cal) {
                this.insertCalendarInList('external', api.key + '/' + cal.key, cal.value, $('kronolithExternalCalendar' + api.key));
            }, this);
        }, this);

        remote = $H(Kronolith.conf.calendars.remote);
        remote.each(function(cal) {
            this.insertCalendarInList('remote', cal.key, cal.value);
        }, this);
        if (remote.size()) {
            $('kronolithRemoteCalendars').show();
        } else {
            $('kronolithRemoteCalendars').hide();
        }

        holidays = $H(Kronolith.conf.calendars.holiday);
        holidays.each(function(cal) {
            this.insertCalendarInList('holiday', cal.key, cal.value);
        }, this);
        if (holidays.size()) {
            $('kronolithHolidayCalendars').show();
        } else {
            $('kronolithHolidayCalendars').hide();
        }
    },

    /**
     * Returns the DIV container that holds all calendars of a certain type.
     *
     * @param string type  A calendar type
     *
     * @return Element  The container of the calendar type.
     */
    getCalendarList: function(type, personal)
    {
        switch (type) {
        case 'internal':
            return personal
                ? $('kronolithMyCalendars')
                : $('kronolithSharedCalendars');
        case 'tasklists':
            return personal
                ? $('kronolithMyTasklists')
                : $('kronolithSharedTasklists');
        case 'external':
            return $('kronolithExternalCalendars');
        case 'remote':
            return $('kronolithRemoteCalendars');
        case 'holiday':
            return $('kronolithHolidayCalendars');
        }
    },

    /**
     * Propagates a SELECT drop down list with the editable calendars.
     *
     * @param string id  The id of the SELECT element.
     */
    updateCalendarDropDown: function(id)
    {
        $(id).update();
        $H(Kronolith.conf.calendars.internal).each(function(cal) {
            if (cal.value.edit) {
                $(id).insert(new Element('option', { value: 'internal|' + cal.key })
                             .setStyle({ backgroundColor: cal.value.bg, color: cal.value.fg })
                             .update(cal.value.name.escapeHTML()));
            }
        });
    },

    /**
     * Sets the load signature and show the loading spinner.
     *
     * @param string resource   The loading resource.
     * @param string signatrue  The signature for this request.
     */
    startLoading: function(resource, signature)
    {
        this.eventsLoading[resource] = signature;
        this.loading++;
        $('kronolithLoading').show();
    },

    /**
     */
    _loadEvents: function(firstDay, lastDay, view, calendars)
    {
        if (typeof calendars == 'undefined') {
            calendars = [];
            $H(Kronolith.conf.calendars).each(function(type) {
                $H(type.value).each(function(cal) {
                    if (cal.value.show) {
                        calendars.push([type.key, cal.key]);
                    }
                });
            });
        }

        calendars.each(function(cal) {
            var startDay = firstDay.clone(), endDay = lastDay.clone(),
                cals = this.ecache.get(cal[0]),
                events, date;

            if (typeof cals != 'undefined' &&
                typeof cals.get(cal[1]) != 'undefined') {
                cals = cals.get(cal[1]);
                while (!Object.isUndefined(cals.get(startDay.dateString())) &&
                       startDay.isBefore(endDay)) {
                    this._insertEvents([startDay, startDay], view, cal.join('|'));
                    startDay.add(1).day();
                }
                while (!Object.isUndefined(cals.get(endDay.dateString())) &&
                       (!startDay.isAfter(endDay))) {
                    this._insertEvents([endDay, endDay], view, cal.join('|'));
                    endDay.add(-1).day();
                }
                if (startDay.compareTo(endDay) > 0) {
                    return;
                }
            }
            var start = startDay.dateString(), end = endDay.dateString(),
                calendar = cal.join('|');
            this.startLoading(calendar, start + end);
            this._storeCache($H(), calendar);
            this.doAction('ListEvents',
                          { start: start,
                            end: end,
                            cal: calendar,
                            view: view },
                          this._loadEventsCallback.bind(this));
        }, this);
    },

    /**
     * Callback method for inserting events in the current view.
     *
     * @param object r  The ajax response object.
     */
    _loadEventsCallback: function(r)
    {
        // Hide spinner.
        this.loading--;
        if (!this.loading) {
            $('kronolithLoading').hide();
        }

        if (typeof r.response.sig == 'undefined') {
            return;
        }

        var start = this.parseDate(r.response.sig.substr(0, 8)),
            end = this.parseDate(r.response.sig.substr(8, 8)),
            dates = [start, end];

        this._storeCache(r.response.events || {}, r.response.cal, dates);

        // Check if this is the still the result of the most current request.
        if (r.response.view != this.view ||
            r.response.sig != this.eventsLoading[r.response.cal]) {
            return;
        }

        this._insertEvents(dates, this.view, r.response.cal);
    },

    /**
     * Reads events from the cache and inserts them into the view.
     *
     * If inserting events into day and week views, the calendar parameter is
     * ignored, and events from all visible calendars are inserted instead.
     * This is necessary because the complete view has to be re-rendered if
     * events are not in chronological order.
     * The year view is specially handled too because there are no individual
     * events, only a summary of all events per day.
     *
     * @param Array dates      Start and end of dates to process.
     * @param string view      The view to update.
     * @param string calendar  The calendar to update.
     */
    _insertEvents: function(dates, view, calendar)
    {
        switch (view) {
        case 'day':
        case 'week':
            // The day and week views require the view to be completely
            // loaded, to correctly calculate the dimensions.
            if (this.viewLoading || this.view != view) {
                this._insertEvents.bind(this, [dates[0].clone(), dates[1].clone()], view, calendar).defer();
                return;
            }
            break;
        }

        var day = dates[0].clone(), date;
        while (!day.isAfter(dates[1])) {
            date = day.dateString();
            switch (view) {
            case 'day':
            case 'week':
                this.dayEvents = [];
                this.dayGroups = [];
                this.allDayEvents = [];
                if (view == 'day') {
                    $$('.kronolithEvent').invoke('remove');
                } else {
                    $('kronolithEventsWeek' + date)
                        .select('.kronolithEvent')
                        .invoke('remove');
                    $('kronolithAllDay' + date)
                        .select('.kronolithEvent')
                        .invoke('remove');
                }
                break;

            case 'month':
                $('kronolithMonthDay' + date)
                    .select('div')
                    .findAll(function(el) { return el.retrieve('calendar') == calendar; })
                    .invoke('remove');
                break;

            case 'year':
                title = '';
                busy = false;
            }

            this._getCacheForDate(date).sortBy(this._sortEvents).each(function(event) {
                switch (view) {
                case 'month':
                case 'agenda':
                    if (calendar != event.value.calendar) {
                        return;
                    }
                    if (calendar.startsWith('holiday|')) {
                        if (this.holidays.include(event.key)) {
                            return;
                        }
                        this.holidays.push(event.key);
                    }
                    break;

                case 'year':
                    if (event.value.al) {
                        title += Kronolith.text.allday;
                    } else {
                        title += event.value.start.toString('t') + '-' + event.value.end.toString('t');
                    }
                    title += ': ' + event.value.t.escapeHTML();
                    if (event.value.x == Kronolith.conf.status.tentative ||
                        event.value.x == Kronolith.conf.status.confirmed) {
                        busy = true;
                    }
                    title += '<br />';
                    return;
                }
                this._insertEvent(event, date, view);
            }, this);

            switch (view) {
            case 'agenda':
                if ($('kronolithViewAgendaBody').select('tr').length > 2) {
                    $('kronolithAgendaNoItems').hide();
                } else {
                    $('kronolithAgendaNoItems').show();
                }
                break;

            case 'year':
                td = $('kronolithYear' + date);
                td.removeClassName('kronolithHasEvents').removeClassName('kronolithIsBusy');
                if (title) {
                    td.writeAttribute('title', title).addClassName('kronolithHasEvents');
                    if (td.readAttribute('nicetitle')) {
                        Horde_ToolTips.detach(td);
                    }
                    Horde_ToolTips.attach(td);
                    if (busy) {
                        td.addClassName('kronolithIsBusy');
                    }
                }
            }

            day.next().day();
        }
        // Workaround Firebug bug.
        Prototype.emptyFunction();
    },

    /**
     * Creates the DOM node for an event bubble and inserts it into the view.
     *
     * @param object event  A Hash member with the event to insert.
     * @param string date   The day to update.
     * @param string view   The view to update.
     */
    _insertEvent: function(event, date, view)
    {
        var calendar = event.value.calendar.split('|');
        event.value.nodeId = 'kronolithEvent' + view + event.value.calendar + date + event.key;

        _createElement = function(event) {
            var el = new Element('div', { id: event.value.nodeId, className: 'kronolithEvent' })
                .store('calendar', event.value.calendar)
                .store('eventid', event.key);
            if (!Object.isUndefined(event.value.aj)) {
                el.store('ajax', event.value.aj);
            }
            return el;
        };

        switch (view) {
        case 'day':
        case 'week':
            var storage = view + 'Sizes',
                div = _createElement(event),
                style = { backgroundColor: Kronolith.conf.calendars[calendar[0]][calendar[1]].bg,
                          color: Kronolith.conf.calendars[calendar[0]][calendar[1]].fg };

            if (event.value.al) {
                if (view == 'day') {
                    $('kronolithViewDay').down('.kronolithAllDayContainer').insert(div.setStyle(style));
                } else {
                    $('kronolithAllDay' + date).insert(div.setStyle(style));
                }
                break;
            }

            var midnight = this.parseDate(date),
                innerDiv = new Element('div', { className: 'kronolithEventInfo' }),
                draggerTop, draggerBottom;
            if (event.value.fi) {
                draggerTop = new Element('div', { id: event.value.nodeId + 'top', className: 'kronolithDragger kronolithDraggerTop' }).setStyle(style);
            } else {
                innerDiv.setStyle({ top: 0 });
            }
            if (event.value.la) {
                draggerBottom = new Element('div', { id: event.value.nodeId + 'bottom', className: 'kronolithDragger kronolithDraggerBottom' }).setStyle(style);
            } else {
                innerDiv.setStyle({ bottom: 0 });
            }

            div.setStyle({
                top: (Math.round(midnight.getElapsed(event.value.start) / 60000) * this[storage].height / 60 | 0) + 'px',
                height: (Math.round(event.value.start.getElapsed(event.value.end) / 60000) * this[storage].height / 60 - this[storage].spacing | 0) + 'px',
                width: '100%'
            })
                .insert(innerDiv.setStyle(style));
            if (draggerTop) {
                div.insert(draggerTop);
            }
            if (draggerBottom) {
                div.insert(draggerBottom);
            }
            $(view == 'day' ? 'kronolithEventsDay' : 'kronolithEventsWeek' + date).insert(div);

            if (event.value.pe) {
                div.addClassName('kronolithEditable');
                // Number of pixels that cover 10 minutes.
                var step = this[storage].height / 6;
                if (draggerTop) {
                    // Top position of top dragger
                    var dragTop = draggerTop.cumulativeOffset().top;
                }
                if (draggerBottom) {
                    // Top position of bottom dragger
                    var dragBottom = draggerBottom.cumulativeOffset().top,
                        // Height of bottom dragger
                        dragBottomHeight = draggerBottom.getHeight();
                }
                    // Top position of the whole event div
                var eventTop = div.cumulativeOffset().top;
                if (draggerTop) {
                    // Bottom-most position (maximum y) of top dragger
                    var maxTop = div.offsetTop
                        - draggerTop.getHeight()
                        - parseInt(innerDiv.getStyle('lineHeight'));
                    if (draggerBottom) {
                        maxTop += draggerBottom.offsetTop;
                    }
                }
                if (draggerBottom) {
                    // Top-most position (minimum y) of bottom dragger (upper
                    // edge)
                    var minBottom = div.offsetTop
                        + parseInt(innerDiv.getStyle('lineHeight')),
                    // Bottom-most position (maximum y) of bottom dragger
                    // (upper edge)
                    maxBottom = 24 * this[storage].height
                        + dragBottomHeight;
                    if (draggerTop) {
                        minBottom += draggerTop.getHeight();
                    }
                }
                    // Height of the whole event div
                var divHeight = div.getHeight(),
                    // Maximum height of the whole event div
                    maxDiv = 24 * this[storage].height - divHeight,
                    // Whether the top dragger is dragged, vs. the bottom
                    // dragger
                    opts = {
                        threshold: 5,
                        constraint: 'vertical',
                        scroll: 'kronolithBody',
                        nodrop: true,
                        parentElement: function() {
                            return $(view == 'day' ? 'kronolithEventsDay' : 'kronolithEventsWeek' + date);
                        }
                    };

                if (draggerTop) {
                    opts.snap = function(x, y) {
                        y = Math.max(0, step * (Math.min(maxTop, y) / step | 0));
                        return [0, y];
                    }
                    var d = new Drag(event.value.nodeId + 'top', opts);
                    Object.extend(d, {
                        event: event,
                        innerDiv: innerDiv,
                        dragTop: dragTop,
                        midnight: midnight
                    });
                }

                if (draggerBottom) {
                    opts.snap = function(x, y) {
                        y = Math.min(maxBottom + dragBottomHeight + KronolithCore[storage].spacing, step * ((Math.max(minBottom, y) + dragBottomHeight + KronolithCore[storage].spacing) / step | 0)) - dragBottomHeight - KronolithCore[storage].spacing;
                        return [0, y];
                    }
                    var d = new Drag(event.value.nodeId + 'bottom', opts);
                    Object.extend(d, {
                        event: event,
                        innerDiv: innerDiv,
                        dragBottom: dragBottom,
                        midnight: midnight
                    });
                }

                if (view == 'week') {
                    var dates = this.viewDates(midnight, view),
                        minLeft = $('kronolithEventsWeek' + dates[0].toString('yyyyMMdd')).offsetLeft - $('kronolithEventsWeek' + date).offsetLeft,
                        maxLeft = $('kronolithEventsWeek' + dates[1].toString('yyyyMMdd')).offsetLeft - $('kronolithEventsWeek' + date).offsetLeft,
                        stepX = (maxLeft - minLeft) / 6;
                }
                var d = new Drag(div, {
                    threshold: 5,
                    nodrop: true,
                    parentElement: function() { return $(view == 'day' ? 'kronolithEventsDay' : 'kronolithEventsWeek' + date); },
                    snap: function(x, y) {
                        x = (view == 'week')
                            ? Math.max(minLeft, stepX * ((Math.min(maxLeft, x) + stepX / 2) / stepX | 0))
                            : 0;
                        y = Math.max(0, step * (Math.min(maxDiv, y) / step | 0));
                        return [x, y];
                    }
                });
                Object.extend(d, {
                    divHeight: divHeight,
                    startTop: div.offsetTop,
                    event: event,
                    midnight: midnight,
                    stepX: stepX
                });
            }

            var column = 1, columns, width, left, conflict = false,
                pos = this.dayGroups.length, placeFound = false;

            this.dayEvents.each(function(ev) {
                if (!ev.end.isAfter(event.value.start)) {
                    placeFound = ev;
                    return;
                }

                if (!conflict) {
                    conflict = ev;
                    for (i = 0; i < this.dayGroups.length; i++) {
                        if (this.dayGroups[i].indexOf(conflict) != -1) {
                            if (this.dayGroups[i].indexOf(placeFound) == -1) {
                                placeFound = false;
                            }
                            break;
                        }
                    }
                }
                if (!placeFound) {
                    column++;
                }
            }, this);
            event.value.column = column;

            if (conflict) {
                for (i = 0; i < this.dayGroups.length; i++) {
                    if (this.dayGroups[i].indexOf(conflict) != -1) {
                        pos = i;
                        break;
                    }
                }
                columns = Math.max(conflict.columns, column);
            } else {
                columns = column;
            }
            if (Object.isUndefined(this.dayGroups[pos])) {
                this.dayGroups[pos] = [];
            }
            this.dayGroups[pos].push(event.value);
            width = 100 / columns;
            this.dayGroups[pos].each(function(ev) {
                ev.columns = columns;
                $(ev.nodeId).setStyle({ width: width + '%', left: (width * (ev.column - 1)) + '%' });
            });
            this.dayEvents.push(event.value);

            div = innerDiv;
            break;

        case 'month':
            var div = _createElement(event)
                .setStyle({ backgroundColor: Kronolith.conf.calendars[calendar[0]][calendar[1]].bg,
                            color: Kronolith.conf.calendars[calendar[0]][calendar[1]].fg });

            $('kronolithMonthDay' + date).insert(div);
            if (event.value.pe) {
                div.setStyle({ cursor: 'move' });
                new Drag('kronolithEventmonth' + event.value.calendar + date + event.key, { threshold: 5, parentElement: function() { return $('kronolithViewMonthBody'); }, snapToParent: true });
            }
            break;

        case 'agenda':
            var div = _createElement(event)
                .setStyle({ backgroundColor: Kronolith.conf.calendars[calendar[0]][calendar[1]].bg,
                            color: Kronolith.conf.calendars[calendar[0]][calendar[1]].fg });
            if (!event.value.al) {
                div.update(new Element('span', { className: 'kronolithDate' }).update(event.value.start.toString('t')))
                    .insert(' ')
                    .insert(new Element('span', { className: 'kronolithSep' }).update('&middot;'))
                    .insert(' ');
            }
            this.createAgendaDay(date);
            $('kronolithAgendaDay' + date).insert(div);
            break;
        }

        this._setEventText(div, event.value)
            .observe('mouseover', div.addClassName.curry('kronolithSelected'))
            .observe('mouseout', div.removeClassName.curry('kronolithSelected'));
    },

    _setEventText: function(div, event)
    {
        var calendar = event.calendar.split('|');
        div.update();
        if (event.ic) {
            div.insert(new Element('img', { src: event.ic }));
        }
        div.insert(event.t.escapeHTML());
        if (event.a) {
            div.insert(' ')
                .insert(new Element('img', { src: Kronolith.conf.URI_IMG + 'alarm-' + Kronolith.conf.calendars[calendar[0]][calendar[1]].fg.substr(1) + '.png', title: Kronolith.text.alarm + ' ' + event.a }));
        }
        if (event.r) {
            div.insert(' ')
                .insert(new Element('img', { src: Kronolith.conf.URI_IMG + 'recur-' + Kronolith.conf.calendars[calendar[0]][calendar[1]].fg.substr(1) + '.png', title: Kronolith.text.recur[event.r] }));
        }
        return div;
    },

    /**
     * Finally removes an event from the DOM and the cache.
     *
     * @param string event     An event id.
     * @param string calendar  A calendar name.
     */
    _removeEvent: function(event, calendar)
    {
        this._deleteCache(event, calendar);
        $('kronolithBody').select('div').findAll(function(el) {
            return el.retrieve('calendar') == calendar &&
                el.retrieve('eventid') == event;
        }).invoke('remove');
    },

    /**
     * Calculates the event's start and end dates based on some drag and drop
     * information.
     */
    _calculateEventDates: function(event, storage, step, offset, height, start, end)
    {
        if (!Object.isUndefined(start)) {
            event.start = start;
            event.end = end;
        }
        event.start.set({
            hour: offset / this[storage].height | 0,
            minute: Math.round(offset % this[storage].height / step * 10)
        });
        var hour = (offset + height + this[storage].spacing) / this[storage].height | 0,
            minute = Math.round((offset + height + this[storage].spacing) % this[storage].height / step * 10),
            second = 0;
        if (hour == 24) {
            hour = 23;
            minute = 59;
            second = 59;
        }
        event.end.set({
            hour: hour,
            minute: minute,
            second: second
        });
    },

    /**
     * Returns the task cache storage names that hold the tasks of the
     * requested task type.
     *
     * @param string tasktype  The task type.
     *
     * @return array  The list of task cache storage names.
     */
    _getTaskStorage: function(tasktype)
    {
        var tasktypes;
        if (tasktype == 'all' || tasktype == 'future') {
            tasktypes = [ 'complete', 'incomplete' ];
        } else {
            tasktypes = [ tasktype ];
        }
        return tasktypes;
    },

    /**
     * Loads tasks, either from cache or from the server.
     *
     * @param integer tasktype  The tasks type (all, incomplete, complete, or
     *                          future).
     * @param Array tasksLists  The lists from where to obtain the tasks.
     */
    _loadTasks: function(tasktype, tasklists)
    {
        var tasktypes = this._getTaskStorage(tasktype), loading = false;

        if (Object.isUndefined(tasklists)) {
            tasklists = [];
            $H(Kronolith.conf.calendars.tasklists).each(function(tasklist) {
                if (tasklist.value.show)
                {
                    tasklists.push(tasklist.key.substring(6));
                }
            });
        }

        tasktypes.each(function(type) {
            if (Object.isUndefined(this.tcache.get(type))) {
                this._storeTasksCache($H(), type, null, true);
            }
            tasklists.each(function(list) {
                if (Object.isUndefined(this.tcache.get(type).get(list))) {
                    loading = true;
                    this.startLoading('tasks:' + type + list, tasktype);
                    this._storeTasksCache($H(), type, list, true);
                    this.doAction('ListTasks',
                                  { type: type,
                                    'sig' : tasktype,
                                    list: list },
                                  function(r) {
                                      this._loadTasksCallback(r, tasktype, true);
                                  }.bind(this));
                }
            }, this);
        }, this);

        if (!loading) {
            tasklists.each(function(list) {
                this._insertTasks(tasktype, list);
            }, this);
        }
    },

    /**
     * Callback method for inserting tasks in the current view.
     *
     * @param object r             The ajax response object.
     * @param string tasktype      The (UI) task type for that the response was
     *                             targeted.
     * @param boolean createCache  Whether to create a cache list entry for the
     *                             response, if none exists yet. Useful for
     *                             adding individual tasks to the cache without
     *                             assuming to have all tasks of the list.
     */
    _loadTasksCallback: function(r, tasktype, createCache)
    {
        // Hide spinner.
        this.loading--;
        if (!this.loading) {
            $('kronolithLoading').hide();
        }

        this._storeTasksCache(r.response.tasks || {}, r.response.type, r.response.list, createCache);

        // Check if this is the still the result of the most current request.
        if (this.view != 'tasks' ||
            this.eventsLoading['tasks:' + r.response.type + r.response.list] != r.response.sig) {
            return;
        }
        this._insertTasks(tasktype, r.response.list);
    },

    /**
     * Reads tasks from the cache and inserts them into the view.
     *
     * @param integer tasktype  The tasks type (all, incomplete, complete, or
     *                          future).
     * @param string tasksList  The task list to be drawn.
     */
    _insertTasks: function(tasktype, tasklist)
    {
        var tasktypes = this._getTaskStorage(tasktype), now = new Date();

        $('kronolithViewTasksBody').select('tr').findAll(function(el) {
            return el.retrieve('tasklist') == tasklist;
        }).invoke('remove');

        tasktypes.each(function(type) {
            var tasks = this.tcache.get(type).get(tasklist);
            $H(tasks).each(function(task) {
                switch (tasktype) {
                case 'complete':
                    if (!task.value.cp ||
                        (!Object.isUndefined(task.value.start) &&
                         task.value.start.isAfter(now))) {
                        return;
                    }
                    break;
                case 'incomplete':
                    if (task.value.cp ||
                        (!Object.isUndefined(task.value.start) &&
                         task.value.start.isAfter(now))) {
                        return;
                    }
                    break;
                case 'future':
                    if (Object.isUndefined(task.value.start) ||
                        !task.value.start.isAfter(now)) {
                        return;
                    }
                    break;
                }
                this._insertTask(task);
            }, this);
        }, this);

        if ($('kronolithViewTasksBody').select('tr').length > 3) {
            $('kronolithTasksNoItems').hide();
        } else {
            $('kronolithTasksNoItems').show();
        }
    },

    /**
     * Creates the DOM node for a task and inserts it into the view.
     *
     * @param object task  A Hash with the task to insert
     */
    _insertTask: function(task)
    {
        var body = $('kronolithViewTasksBody'),
            row = $('kronolithTasksTemplate').cloneNode(true),
            col = row.down(),
            div = col.down();

        row.removeAttribute('id');
        row.store('tasklist', task.value.l);
        row.store('taskid', task.key);
        col.addClassName('kronolithTask' + (!!task.value.cp ? 'Completed' : ''));
        col.insert(task.value.n.escapeHTML());
        if (!Object.isUndefined(task.value.du)) {
            var date = Date.parse(task.value.du),
                now = new Date();
            if (!now.isBefore(date)) {
                col.addClassName('kronolithTaskDue');
            }
            col.insert(new Element('span', { className: 'kronolithSep' }).update(' &middot; '));
            col.insert(new Element('span', { className: 'kronolithDate' }).update(date.toString(Kronolith.conf.date_format)));
        }

        if (!Object.isUndefined(task.value.sd)) {
            col.insert(new Element('span', { className: 'kronolithSep' }).update(' &middot; '));
            col.insert(new Element('span', { className: 'kronolithInfo' }).update(task.value.sd));
        }

        row.insert(col.show());
        this._insertTaskPosition(row, task);
    },

    /**
     * Inserts the task row in the correct position.
     *
     * @param Element newRow  The new row to be inserted.
     * @param object newTask  A Hash with the task being added.
     */
    _insertTaskPosition: function(newRow, newTask)
    {
        var rows = $('kronolithViewTasksBody').select('tr');
        // The first row is the add task row, the second a template, ignoring.
        for (var i = 3; i < rows.length; i++) {
            var rowTasklist = rows[i].retrieve('tasklist');
            var rowTaskId = rows[i].retrieve('taskid');
            var rowTask = this.tcache.inject(null, function(acc, list) {
                if (acc) {
                    return acc;
                }
                if (!Object.isUndefined(list.value.get(rowTasklist))) {
                    return list.value.get(rowTasklist).get(rowTaskId);
                }
            });

            if (Object.isUndefined(rowTask)) {
                // TODO: Throw error
                return;
            }
            if (!this._isTaskAfter(newTask.value, rowTask)) {
                break;
            }
        }
        rows[--i].insert({ after: newRow.show() });
    },

    /**
     * Analyzes which task should be drawn first.
     *
     * TODO: Very incomplete, only a dummy version
     */
    _isTaskAfter: function(taskA, taskB)
    {
        // TODO: Make all ordering system
        return (taskA.pr >= taskB.pr);
    },

    /**
     * Completes/uncompletes a task.
     *
     * @param string tasklist  The task list to which the tasks belongs
     * @param string taskid    The id of the task
     */
    _toggleCompletion: function(tasklist, taskid)
    {
        // Update the cache.
        var task = this.tcache.inject(null, function(acc, list) {
            if (acc) {
                return acc;
            }
            if (!Object.isUndefined(list.value.get(tasklist))) {
                return list.value.get(tasklist).get(taskid);
            }
        });
        if (Object.isUndefined(task)) {
            // This shouldn't happen.
            this._toggleCompletionClass(taskid);
            return;
        }
        task.cp = !task.cp;

        this.tcache.get(task.cp ? 'complete' : 'incomplete').get(tasklist).set(taskid, task);
        this.tcache.get(task.cp ? 'incomplete' : 'complete').get(tasklist).unset(taskid);

        // Remove row if necessary.
        var row = this._getTaskRow(taskid);
        if (!row) {
            return;
        }
        if ((this.tasktype == 'complete' && !task.cp) ||
            ((this.tasktype == 'incomplete' || this.tasktype == 'future_incomplete') && task.cp)) {
            row.fade({ afterFinish: function() { row.remove(); } });
        }
    },

    /**
     * Toggles the CSS class to show that a task is completed/uncompleted.
     *
     * @param string taskid  The id of the task.
     */
    _toggleCompletionClass: function(taskid)
    {
        var row = this._getTaskRow(taskid);
        if (!row) {
            return;
        }
        var col = row.down('td.kronolithTaskCol');
        col.toggleClassName('kronolithTask');
        col.toggleClassName('kronolithTaskCompleted');
    },

    /**
     * Returns the table row of a task.
     *
     * @param string taskid  The id of the task.
     *
     * @return Element  The table row of the task list, if found.
     */
    _getTaskRow: function(taskid)
    {
        return $('kronolithViewTasksBody').select('tr').find(function(el) {
            return el.retrieve('taskid') == taskid;
        });
    },

    editTask: function(tasklist, id)
    {
        RedBox.onDisplay = function() {
            try {
                $('kronolithTaskForm').focusFirstElement();
            } catch(e) {}
            RedBox.onDisplay = null;
        };

        $('kronolithTaskForm').enable();
        $('kronolithTaskForm').reset();
        $('kronolithTaskSave').show();
        $('kronolithTaskDelete').show();
        $('kronolithTaskForm').down('.kronolithFormActions .kronolithSep').show();
        this.updateTasklistDropDown();
        if (id) {
            RedBox.loading();
            this.doAction('GetTask', { list: tasklist, id: id }, this._editTask.bind(this));
        } else {
            $('kronolithTaskId').clear();
            $('kronolithTaskOldList').clear();
            $('kronolithTaskList').setValue(Kronolith.conf.tasks.default_tasklist);
            //$('kronolithTaskLocation').setValue('http://');
            $('kronolithTaskPriority').setValue(3);
            if (Kronolith.conf.tasks.default_due) {
                this.setDefaultDue();
            }
            $('kronolithTaskDelete').hide();
            RedBox.showHtml($('kronolithTaskDialog').show());
        }
    },

    /**
     * Callback method for showing task forms.
     *
     * @param object r  The ajax response object.
     */
    _editTask: function(r)
    {
        if (!r.response.task) {
            RedBox.close();
            window.history.back();
            return;
        }

        var task = r.response.task;

        /* Basic information */
        $('kronolithTaskId').setValue(task.id);
        $('kronolithTaskOldList').setValue(task.l);
        $('kronolithTaskList').setValue(task.l);
        $('kronolithTaskTitle').setValue(task.n);
        //$('kronolithTaskLocation').setValue(task.l);
        if (task.dd) {
            $('kronolithTaskDueDate').setValue(task.dd);
        }
        if (task.dt) {
            $('kronolithTaskDueTime').setValue(task.dt);
        }
        $('kronolithTaskDescription').setValue(task.de);
        $('kronolithTaskPriority').setValue(task.pr);
        $('kronolithTaskCompleted').setValue(task.cp);

        /* Alarm */
        if (task.a) {
            $('kronolithTaskAlarmOn').setValue(true);
            [10080, 1440, 60, 1].each(function(unit) {
                if (task.a % unit == 0) {
                    $('kronolithTaskAlarmValue').setValue(task.a / unit);
                    $('kronolithTaskAlarmUnit').setValue(unit);
                    throw $break;
                }
            });
        } else {
            $('kronolithEventAlarmOff').setValue(true);
        }

        if (!task.pe) {
            $('kronolithTaskSave').hide();
            $('kronolithTaskForm').disable();
        }
        if (!task.pd) {
            $('kronolithTaskDelete').show();
        }
        if (!task.pe && !task.pd) {
            $('kronolithTaskForm').down('.kronolithFormActions .kronolithSep').hide();
        }

        this.setTitle(task.n);
        RedBox.showHtml($('kronolithTaskDialog').show());
    },

    /**
     * Propagates a SELECT drop down list with the editable task lists.
     *
     * @param string id  The id of the SELECT element.
     */
    updateTasklistDropDown: function()
    {
        $('kronolithTaskList').update();
        $H(Kronolith.conf.calendars.tasklists).each(function(cal) {
            if (cal.value.edit) {
                $('kronolithTaskList').insert(new Element('option', { value: cal.key.substring(6) })
                             .setStyle({ backgroundColor: cal.value.bg, color: cal.value.fg })
                             .update(cal.value.name.escapeHTML()));
            }
        });
    },

    /**
     * Sets the default due date and time for tasks.
     */
    setDefaultDue: function()
    {
        if ($F('kronolithTaskDueDate') || $F('kronolithTaskDueTime')) {
            return;
        }
        $('kronolithTaskDueDate').setValue(new Date().add(Kronolith.conf.tasks.default_due_days).days().toString(Kronolith.conf.date_format));
        if (Kronolith.conf.tasks.default_due_time == 'now') {
            $('kronolithTaskDueTime').setValue(new Date().toString(Kronolith.conf.time_format));
        } else {
            var date = new Date();
            date.setHours(Kronolith.conf.tasks.default_due_time.replace(/:.*$/, ''));
            date.setMinutes(0);
            $('kronolithTaskDueTime').setValue(date.toString(Kronolith.conf.time_format));
        }
    },

    /**
     * Finally removes a task from the DOM and the cache.
     *
     * @param string task  A task id.
     * @param string list  A task list name.
     */
    _removeTask: function(task, list)
    {
        this._deleteTasksCache(task, list);
        $('kronolithViewTasksBody').select('tr').find(function(el) {
            return el.retrieve('tasklist') == list &&
                el.retrieve('taskid') == task;
        }).remove();
    },

    /**
     * Submits the task edit form to create or update a task.
     */
    saveTask: function()
    {
        if (this.wrongFormat.size()) {
            this.showNotifications([{ type: 'horde.warning', message: Kronolith.text.fix_form_values }]);
            return;
        }

        var tasklist = $F('kronolithTaskList'),
            taskid = $F('kronolithTaskId');
        this.startLoading('tasks:' + ($F('kronolithTaskCompleted') ? 'complete' : 'incomplete') + tasklist, this.tasktype);
        this.doAction('SaveTask',
                      $H($('kronolithTaskForm').serialize({ hash: true }))
                          .merge({ sig: this.tasktype }),
                      function(r) {
                          if (r.response.tasks && taskid) {
                              this._removeTask(taskid, tasklist);
                          }
                          this._loadTasksCallback(r, this.tasktype, false);
                          this._closeRedBox();
                          window.history.back();
                      }.bind(this));
    },

    /**
     * Opens the form for editing a calendar.
     *
     * @param string calendar  Calendar type and calendar id, separated by '|'.
     */
    editCalendar: function(calendar)
    {
        if ($('kronolithCalendarDialog')) {
            RedBox.showHtml($('kronolithCalendarDialog').show());
            this._editCalendar(calendar);
        } else {
            RedBox.loading();
            this.doAction('ChunkContent', { chunk: 'calendar' }, function(r) {
                if (r.response.chunk) {
                    RedBox.showHtml(r.response.chunk);
                    this._editCalendar(calendar);
                }
            }.bind(this));
        }
    },

    /**
     * Callback for editing a calendar. Fills the edit form with the correct
     * values.
     *
     * @param string calendar  Calendar type and calendar id, separated by '|'.
     */
    _editCalendar: function(calendar)
    {
        calendar = calendar.split('|');
        var type = calendar[0];
        calendar = calendar.length == 1 ? null : calendar[1];

        $('kronolithCalendarDialog').select('.kronolithCalendarDiv').invoke('hide');
        $('kronolithCalendar' + type + '1').show();
        $('kronolithCalendarForm' + type).select('.kronolithCalendarContinue').invoke('enable');

        var newCalendar = !calendar;
        if (calendar &&
            (Object.isUndefined(Kronolith.conf.calendars[type]) ||
             Object.isUndefined(Kronolith.conf.calendars[type][calendar]))) {
            if (type == 'remote') {
                newCalendar = true;
            } else {
                this._closeRedBox();
                window.history.back();
                return;
            }
        }

        /* Reset form to defaults if this is for adding calendars. */
        if (newCalendar) {
            var fields = [ 'Id', 'Name' ];
            switch (type) {
            case 'internal':
            case 'tasklists':
                fields.push('Description');
                break;
            case 'remote':
                fields.push('Description', 'Url', 'Username', 'Password');
                break;
            }
            fields.each(function(field) {
                $('kronolithCalendar' + type + field).clear();
            });
            $('kronolithCalendar' + type + 'Color').setValue('#dddddd').setStyle({ backgroundColor: '#dddddd', color: '#000' });
            $('kronolithCalendarForm' + type).down('.kronolithCalendarDelete').hide();
            if (calendar && type == 'remote') {
                $('kronolithCalendarremoteUrl').setValue(calendar);
            }
            return;
        }

        var info = Kronolith.conf.calendars[type][calendar];

        $('kronolithCalendar' + type + 'Id').setValue(calendar);
        $('kronolithCalendar' + type + 'Name').setValue(info.name);
        $('kronolithCalendar' + type + 'Color').setValue(info.bg).setStyle({ backgroundColor: info.bg, color: info.fg });

        switch (type) {
        case 'internal':
        case 'tasklists':
            $('kronolithCalendarinternalDescription').setValue(info.desc);
            break;
        case 'remote':
            $('kronolithCalendarremoteUrl').setValue(calendar);
            $('kronolithCalendarremoteDescription').setValue(info.desc);
            $('kronolithCalendarremoteUsername').setValue(info.user);
            $('kronolithCalendarremotePassword').setValue(info.password);
            break;
        }

        /* Currently we only show the calendar form for own calendars anyway,
           but be prepared. */
        var form = $('kronolithCalendarForm' + type);
        if (info.owner) {
            form.enable();
            if (type == 'internal' &&
                calendar == Kronolith.conf.user) {
                form.down('.kronolithCalendarDelete').hide();
            } else {
                form.down('.kronolithCalendarDelete').show();
            }
            form.down('.kronolithCalendarSave').show();
        } else {
            form.disable();
            form.down('.kronolithCalendarDelete').hide();
            form.down('.kronolithCalendarSave').hide();
        }
    },

    /**
     * Opens the next screen of the calendar management wizard.
     *
     * @param string type  The calendar type.
     */
    _calendarNext: function(type)
    {
        var i = 1;
        while (!$('kronolithCalendar' + type + i).visible()) {
            i++;
        }
        $('kronolithCalendar' + type + i).hide();
        $('kronolithCalendar' + type + ++i).show();
    },

    /**
     * Submits the calendar form to save the calendar data.
     *
     * @param Element  The form node.
     */
    saveCalendar: function(form)
    {
        var type = form.id.replace(/kronolithCalendarForm/, ''),
            data = form.serialize({ hash: true });

        this.doAction('SaveCalendar',
                      data,
                      function(r) {
                          if (r.response.saved) {
                              if (data.calendar) {
                                  var cal = Kronolith.conf.calendars[type][data.calendar],
                                      color = {
                                          backgroundColor: data.color,
                                          color: r.response.color
                                      };
                                  cal.bg = data.color;
                                  cal.fg = r.response.color;
                                  cal.name = data.name;
                                  cal.desc = data.description;
                                  this.getCalendarList(type, cal.owner).select('div').each(function(element) {
                                      if (element.retrieve('calendar') == data.calendar) {
                                          element
                                              .setStyle(color)
                                              .update(cal.name.escapeHTML());
                                          throw $break;
                                      }
                                  });
                                  $('kronolithBody').select('div').each(function(el) {
                                      if (el.retrieve('calendar') == type + '|' + data.calendar) {
                                          el.setStyle(color);
                                      }
                                  });
                              } else {
                                  var cal = {
                                      bg: data.color,
                                      fg: r.response.color,
                                      name: data.name,
                                      desc: data.description,
                                      edit: true,
                                      owner: true,
                                      show: true
                                  };
                                  Kronolith.conf.calendars[type][r.response.calendar] = cal;
                                  this.insertCalendarInList(type, r.response.calendar, cal);
                              }
                          }
                          form.down('.kronolithCalendarSave').enable();
                          this._closeRedBox();
                          window.history.back();
                      }.bind(this));
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
        return new Date(date.substr(0, 4), date.substr(4, 2) - 1, date.substr(6, 2));
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
     * Stores a set of events in the cache.
     *
     * For dates in the specified date ranges that don't contain any events,
     * empty cache entries are created so that those dates aren't re-fetched
     * each time.
     *
     * @param object events    A list of calendars and events as returned from
     *                         an ajax request.
     * @param string calendar  A calendar string or array.
     * @param string dates     A date range in the format yyyymmddyyyymmdd as
     *                         used in the ajax response signature.
     */
    _storeCache: function(events, calendar, dates)
    {
        if (Object.isString(calendar)) {
            calendar = calendar.split('|');
        }

        // Create cache entry for the calendar.
        if (!this.ecache.get(calendar[0])) {
            this.ecache.set(calendar[0], $H());
        }
        if (!this.ecache.get(calendar[0]).get(calendar[1])) {
            this.ecache.get(calendar[0]).set(calendar[1], $H());
        }
        var calHash = this.ecache.get(calendar[0]).get(calendar[1]);

        // Create empty cache entries for all dates.
        if (typeof dates != 'undefined') {
            var day = dates[0].clone(), date;
            while (!day.isAfter(dates[1])) {
                date = day.dateString();
                if (!calHash.get(date)) {
                    calHash.set(date, $H());
                }
                day.add(1).day();
            }
        }

        var cal = calendar.join('|');
        $H(events).each(function(date) {
            // Store calendar string and other useful information in event
            // objects.
            $H(date.value).each(function(event) {
                event.value.calendar = cal;
                event.value.start = Date.parse(event.value.s);
                event.value.end = Date.parse(event.value.e);
                event.value.sort = event.value.start.toString('HHmmss')
                    + (240000 - parseInt(event.value.end.toString('HHmmss'))).toPaddedString(6);
            });

            // Store events in cache.
            calHash.set(date.key, calHash.get(date.key).merge(date.value));
        });
    },

    /**
     * Stores a set of tasks in the cache.
     *
     * @param Hash tasks           The tasks to be stored.
     * @param string tasktypes     The task type that's being stored.
     * @param string tasklist      The task list to which the tasks belong.
     * @param boolean createCache  Whether to create a cache list entry for the
     *                             response, if none exists yet.
     */
    _storeTasksCache: function(tasks, tasktypes, tasklist, createCache)
    {
        var taskHashes = {}, cacheExists = {};

        if (tasktypes == 'all' || tasktypes == 'future') {
            tasktypes = [ 'complete', 'incomplete' ];
        } else {
            tasktypes = [ tasktypes ];
        }

        tasktypes.each(function(tasktype) {
            cacheExists[tasktype] = false;
            if (!this.tcache.get(tasktype)) {
                if (createCache) {
                    this.tcache.set(tasktype, $H());
                } else {
                    return;
                }
            }
            if (!tasklist) {
                return;
            }
            if (!this.tcache.get(tasktype).get(tasklist)) {
                if (createCache) {
                    this.tcache.get(tasktype).set(tasklist, $H());
                    cacheExists[tasktype] = true;
                } else {
                    return;
                }
            } else {
                cacheExists[tasktype] = true;
            }
            taskHashes[tasktype] = this.tcache.get(tasktype).get(tasklist);
        }, this);

        $H(tasks).each(function(task) {
            var tasktype = task.value.cp ? 'complete' : 'incomplete';
            if (!cacheExists[tasktype]) {
                return;
            }
            if (!Object.isUndefined(task.value.s)) {
                task.value.start = Date.parse(task.value.s);
            }
            if (!Object.isUndefined(task.value.du)) {
                task.value.due = Date.parse(task.value.du);
            }
            taskHashes[tasktype].set(task.key, task.value);
        });
    },

    /**
     * Deletes an event or a complete calendar from the cache.
     *
     * @param string event     An event ID or empty if deleting the calendar.
     * @param string calendar  A calendar string or array.
     */
    _deleteCache: function(event, calendar)
    {
        if (Object.isString(calendar)) {
            calendar = calendar.split('|');
        }
        if (!this.ecache.get(calendar[0]) ||
            !this.ecache.get(calendar[0]).get(calendar[1])) {
            return;
        }
        if (event) {
            this.ecache.get(calendar[0]).get(calendar[1]).each(function(day) {
                day.value.unset(event);
            });
        } else {
            this.ecache.get(calendar[0]).unset(calendar[1]);
        }
    },

    /**
     * Deletes a task from the cache.
     *
     * @param string task  A task ID.
     * @param string list  A task list string.
     */
    _deleteTasksCache: function(task, list)
    {
        this._deleteCache(task, [ 'external', 'tasks/' + list ]);
        [ 'complete', 'incomplete' ].each(function(type) {
            if (!Object.isUndefined(this.tcache.get(type)) &&
                !Object.isUndefined(this.tcache.get(type).get(list))) {
                this.tcache.get(type).get(list).unset(task);
            }
        }, this);
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
    _getCacheForDate: function(date)
    {
        var events = $H();
        this.ecache.each(function(type) {
            type.value.each(function(cal) {
                if (!Kronolith.conf.calendars[type.key][cal.key].show) {
                    return;
                }
                events = events.merge(cal.value.get(date));
            });
        });
        return events;
    },

    /**
     * Helper method for Enumerable.sortBy to sort events first by start time,
     * second by end time reversed.
     *
     * @param Hash event  A hash entry with the event object as the value.
     *
     * @return string  A comparable string.
     */
    _sortEvents: function(event)
    {
        return event.value.sort;
    },

    _addHistory: function(loc, data)
    {
        if (Horde.dhtmlHistory.getCurrentLocation() != loc) {
            Horde.dhtmlHistory.add(loc, data);
        }
    },

    iframeContent: function(name, loc)
    {
        this.closeView();

        if (name === null) {
            name = loc;
        }

        if ($('kronolithIframe' + name)) {
            $('kronolithIframe' + name).src = loc;
        } else {
            var iframe = new Element('iframe', { id: 'kronolithIframe' + name, className: 'kronolithIframe', frameBorder: 0, src: loc });
            //this._resizeIE6Iframe(iframe);
            $('kronolithViewIframe').insert(iframe);
        }

        $('kronolithViewIframe').appear({ queue: 'end' });
        this.view = 'iframe';
    },

    onResize: function(noupdate, nowait)
    {
    },

    /* Keydown event handler */
    keydownHandler: function(e)
    {
        var kc = e.keyCode || e.charCode,
            form = e.findElement('FORM');

        if (form) {
            switch (kc) {
            case Event.KEY_RETURN:
                switch (form.identify()) {
                case 'kronolithEventForm':
                    this.saveEvent();
                    e.stop();
                    break;

                case 'kronolithTaskForm':
                    this.saveTask();
                    e.stop();
                    break;

                case 'kronolithSearchForm':
                    this.go('search:' + $F('kronolithSearchTerm'))
                    e.stop();
                    break;

                case 'kronolithCalendarForminternal':
                case 'kronolithCalendarFormtasklists':
                case 'kronolithCalendarFormremote':
                    // Disabled for now, we have to also catch Continue buttons.
                    //this.saveCalendar(form);
                    //e.stop();
                    break;
                }
                break;

            case Event.KEY_ESC:
                switch (form.identify()) {
                case 'kronolithQuickinsertForm':
                    $('kronolithQuickinsert').fade();
                    break;
                case 'kronolithEventForm':
                    this._closeRedBox();
                    window.history.back();
                    break;
                }
                break;
            }

            return;
        }

        switch (kc) {
        case Event.KEY_ESC:
            this._closeRedBox();
            break;
        }
    },

    keyupHandler: function(e)
    {
        switch (e.element().readAttribute('id')) {
        case 'kronolithEventLocation':
            if ($F('kronolithEventLocation')) {
                $('kronolithEventMapLink').show();
            } else {
                $('kronolithEventMapLink').hide();
            }
            return;
        }

    },

    clickHandler: function(e, dblclick)
    {
        if (e.isRightClick() || typeof e.element != 'function') {
            return;
        }

        var elt = e.element(),
            orig = e.element(),
            id, tmp, calendar, calendarClass;

        while (Object.isElement(elt)) {
            id = elt.readAttribute('id');

            switch (id) {
            case 'kronolithLogo':
                this.go(Kronolith.conf.login_view);
                e.stop();
                return;

            case 'kronolithNewEvent':
                this.go('event');
                e.stop();
                return;

            case 'kronolithNewTask':
                this.go('task');
                e.stop();
                return;

            case 'kronolithQuickEvent':
                this.updateCalendarDropDown('kronolithQuickinsertCalendars');
                $('kronolithQuickinsert').appear({
                    duration: 0.3,
                    afterFinish: function() {
                        $('kronolithQuickinsertQ').focus();
                    }
                });
                e.stop();
                return;

            case 'kronolithQuickinsertSave':
                this.quickSaveEvent();
                e.stop();
                return;

            case 'kronolithQuickinsertCancel':
                $('kronolithQuickinsert').fade();
                $('kronolithQuickinsertQ').value = '';
                e.stop();
                return;

            case 'kronolithEventAllday':
                this.toggleAllDay();
                return;

            case 'kronolithEventStartPicker':
            case 'kronolithEventEndPicker':
            case 'kronolithTaskDuePicker':
                Horde_Calendar.open(id, Date.parseExact($F(id.replace(/Picker$/, 'Date')), Kronolith.conf.date_format));
                return;

            case 'kronolithEventLinkNone':
            case 'kronolithEventLinkDaily':
            case 'kronolithEventLinkWeekly':
            case 'kronolithEventLinkMonthly':
            case 'kronolithEventLinkYearly':
            case 'kronolithEventLinkLength':
                this.toggleRecurrence(id.substring(18));
                return;

            case 'kronolithEventSave':
                this.saveEvent();
                elt.disable();
                e.stop();
                return;

            case 'kronolithTaskSave':
                this.saveTask();
                elt.disable();
                e.stop();
                return;

            case 'kronolithEventDelete':
                var cal = $F('kronolithEventCalendar'),
                    eventid = $F('kronolithEventId');
                this.doAction('DeleteEvent',
                              { cal: cal, id: eventid },
                              function(r) {
                                  if (r.response.deleted) {
                                      this._removeEvent(eventid, cal);
                                  } else {
                                      $('kronolithBody').select('div').findAll(function(el) {
                                          return el.retrieve('calendar') == cal &&
                                              el.retrieve('eventid') == eventid;
                                      }).invoke('toggle');
                                  }
                              }.bind(this));
                $('kronolithBody').select('div').findAll(function(el) {
                    return el.retrieve('calendar') == cal &&
                        el.retrieve('eventid') == eventid;
                }).invoke('hide');
                this._closeRedBox();
                window.history.back();
                e.stop();
                return;

            case 'kronolithTaskDelete':
                var tasklist = $F('kronolithTaskOldList'),
                    taskid = $F('kronolithTaskId');
                this.doAction('DeleteTask',
                              { list: tasklist, id: taskid },
                              function(r) {
                                  if (r.response.deleted) {
                                      this._removeTask(taskid, tasklist);
                                  } else {
                                      $('kronolithViewTasksBody').select('tr').find(function(el) {
                                          return el.retrieve('tasklist') == tasklist &&
                                              el.retrieve('taskid') == taskid;
                                      }).toggle();
                                  }
                              }.bind(this));
                var taskrow = $('kronolithViewTasksBody').select('tr').find(function(el) {
                    return el.retrieve('tasklist') == tasklist &&
                        el.retrieve('taskid') == taskid;
                });
                if (taskrow) {
                    taskrow.hide();
                }
                this._closeRedBox();
                window.history.back();
                e.stop();
                return;

            case 'kronolithNavDay':
            case 'kronolithNavWeek':
            case 'kronolithNavMonth':
            case 'kronolithNavYear':
            case 'kronolithNavAgenda':
                this.go(id.substring(12).toLowerCase() + ':' + this.date.dateString());
                e.stop();
                return;

            case 'kronolithNavTasks':
                this.go('tasks');
                e.stop();
                return;

            case 'kronolithTasksAll':
            case 'kronolithTasksComplete':
            case 'kronolithTasksIncomplete':
            case 'kronolithTasksFuture':
                this.go('tasks:' + id.substring(14).toLowerCase());
                e.stop();
                return;

            case 'kronolithOptions':
                this.go('options');
                e.stop();
                return;

            case 'kronolithMinicalDate':
                this.go('month:' + orig.retrieve('date'));
                e.stop();
                return;

            case 'kronolithMinical':
                if (orig.id == 'kronolithMinicalPrev') {
                    var date = this.parseDate($('kronolithMinicalDate').retrieve('date'));
                    date.previous().month();
                    this.updateMinical(date, date.getMonth() == this.date.getMonth() ? this.view : undefined);
                    e.stop();
                    return;
                }
                if (orig.id == 'kronolithMinicalNext') {
                    var date = this.parseDate($('kronolithMinicalDate').retrieve('date'));
                    date.next().month();
                    this.updateMinical(date, date.getMonth() == this.date.getMonth() ? this.view : null);
                    e.stop();
                    return;
                }

                var tmp = orig;
                if (tmp.tagName != 'td') {
                    tmp.up('td');
                }
                if (tmp) {
                    if (tmp.retrieve('weekdate') &&
                        tmp.hasClassName('kronolithMinicalWeek')) {
                        this.go('week:' + tmp.retrieve('weekdate'));
                    } else if (tmp.retrieve('date') &&
                               !tmp.hasClassName('empty')) {
                        this.go('day:' + tmp.retrieve('date'));
                    }
                }
                e.stop();
                return;

            case 'kronolithEventsDay':
                this.go('event:' + this.date.dateString());
                e.stop();
                return;

            case 'kronolithViewMonth':
                if (orig.hasClassName('kronolithFirstCol')) {
                    var date = orig.retrieve('date');
                    if (date) {
                        this.go('week:' + date);
                        e.stop();
                        return;
                    }
                }
                e.stop();
                return;

            case 'kronolithViewYear':
                var tmp = orig;
                if (tmp.tagName != 'td') {
                    tmp.up('td');
                }
                if (tmp) {
                    if (tmp.retrieve('weekdate') &&
                        tmp.hasClassName('kronolithMinicalWeek')) {
                        this.go('week:' + tmp.retrieve('weekdate'));
                    } else if (tmp.hasClassName('kronolithMinicalDate')) {
                        this.go('month:' + tmp.retrieve('date'));
                    } else if (tmp.retrieve('date') &&
                               !tmp.hasClassName('empty')) {
                        this.go('day:' + tmp.retrieve('date'));
                    }
                }
                e.stop();
                return;

            case 'kronolithViewAgenda':
                var tmp = orig;
                if (tmp.tagName != 'td') {
                    tmp.up('td');
                }
                if (tmp && tmp.retrieve('date')) {
                    this.go('day:' + tmp.retrieve('date'));
                }
                e.stop();
                return;

            case 'kronolithSearchButton':
                this.go('search:' + $F('kronolithSearchTerm'))
                break;

            case 'kronolithNotifications':
                if (this.Growler.toggleLog()) {
                    elt.update(Kronolith.text.hidelog);
                } else {
                    $('kronolithNotifications').update(Kronolith.text.alerts.interpolate({ count: this.growls }));
                }
                break;
            }

            // Caution, this only works if the element has definitely only a
            // single CSS class.
            switch (elt.className) {
            case 'kronolithDateChoice':
            case 'kronolithGotoToday':
                this.go(this.view + ':' + new Date().dateString());
                e.stop();
                return;

            case 'kronolithPrev':
            case 'kronolithNext':
                var newDate = this.date.clone(),
                    offset = elt.className == 'kronolithPrev' ? -1 : 1;
                switch (this.view) {
                case 'day':
                case 'agenda':
                    newDate.add(offset).day();
                    break;
                case 'week':
                    newDate.add(offset).week();
                    break;
                case 'month':
                    newDate.add(offset).month();
                    break;
                case 'year':
                    newDate.add(offset).year();
                    break;
                }
                this.go(this.view + ':' + newDate.dateString());
                e.stop();
                return;

            case 'kronolithAdd':
                this.go('calendar:' + id.replace(/kronolithAdd/, ''));
                e.stop();
                return;

            case 'kronolithTabLink':
                var dialog = elt.up('form');
                dialog.select('.kronolithTabsOption').invoke('hide');
                dialog.select('.tabset li').invoke('removeClassName', 'activeTab');
                $(id.replace(/Link/, 'Tab')).show();
                elt.parentNode.addClassName('activeTab');
                if (id == 'kronolithEventLinkMap') {
                    /* Maps */
                    if (!this.mapInitialized) {
                        this.initializeMap();
                    }
                }
                e.stop();
                return;

            case 'kronolithFormCancel':
                this._closeRedBox();
                this.resetMap();
                window.history.back();
                e.stop();
                return;

            case 'kronolithEventTag':
                $('kronolithEventTags').autocompleter.addNewItemNode(elt.getText());
                e.stop();
                return;

            case 'kronolithEventGeo':
                this.ensureMap();
                this.geocode($F('kronolithEventLocation'));
                e.stop();
                return;

            case 'kronolithTaskRow':
                if (elt.retrieve('taskid')) {
                    this.go('task:' + elt.retrieve('tasklist') + ':' + elt.retrieve('taskid'));
                }
                e.stop();
                return;

            case 'kronolithCalEdit':
                this.go('calendar:' + elt.next().retrieve('calendarclass') + '|' + elt.next().retrieve('calendar'));
                e.stop();
                return;

            case 'kronolithEventsWeek':
            case 'kronolithAllDayContainer':
                var date = elt.identify().substr(elt.identify().length - 8);
                if (elt.className == 'kronolithAllDayContainer') {
                    date += 'all';
                }
                this.go('event:' + date);
                e.stop();
                return;
            }

            if (elt.hasClassName('kronolithEvent')) {
                if (!Object.isUndefined(elt.retrieve('ajax'))) {
                    this.go(elt.retrieve('ajax'));
                } else {
                    this.go('event:' + elt.retrieve('calendar') + ':' + elt.retrieve('eventid') + ':' + elt.up().retrieve('date'));
                }
                e.stop();
                return;
            } else if (elt.hasClassName('kronolithMonthDay')) {
                if (orig.hasClassName('kronolithDay')) {
                    var date = orig.retrieve('date');
                    if (date) {
                        this.go('day:' + date);
                        e.stop();
                        return;
                    }
                }
                this.go('event:' + elt.retrieve('date'));
                e.stop();
                return;
            } else if (elt.hasClassName('kronolithWeekDay')) {
                this.go('day:' + elt.retrieve('date'));
                e.stop();
                return;
            } else if (elt.hasClassName('kronolithTaskCheckbox')) {
                var taskid = elt.up('tr.kronolithTaskRow', 0).retrieve('taskid'),
                    tasklist = elt.up('tr.kronolithTaskRow', 0).retrieve('tasklist');
                this._toggleCompletionClass(taskid);
                this.doAction('ToggleCompletion',
                              { list: tasklist, id: taskid },
                              function(r) {
                                  if (r.response.toggled) {
                                      this._toggleCompletion(tasklist, taskid);
                                  } else {
                                      this._toggleCompletionClass(taskid);
                                  }
                              }.bind(this));
                e.stop();
                return;
            } else if (elt.hasClassName('kronolithCalendarSave')) {
                elt.disable();
                this.saveCalendar(elt.up('form'));
                e.stop();
                return;
            } else if (elt.hasClassName('kronolithCalendarContinue')) {
                var form = elt.up('form'),
                    type = form.id.replace(/kronolithCalendarForm/, ''),
                    i = 1;
                while (!$('kronolithCalendar' + type + i).visible()) {
                    i++;
                }
                if (type == 'remote') {
                    elt.disable();
                    var params = { url: $F('kronolithCalendarremoteUrl') };
                    if (i == 1) {
                        if (!$F('kronolithCalendarremoteUrl')) {
                            this.showNotifications([ { type: 'horde.warning', message: Kronolith.text.no_url }]);
                            e.stop();
                            return;
                        }
                        this.doAction('GetRemoteInfo',
                                      params,
                                      function(r) {
                                          if (r.response.success) {
                                              if (r.response.name) {
                                                  $('kronolithCalendarremoteName').setValue(r.response.name);
                                              }
                                              if (r.response.desc) {
                                                  $('kronolithCalendarremoteDescription').setValue(r.response.desc);
                                              }
                                              this._calendarNext(type);
                                              this._calendarNext(type);
                                          } else if (r.response.auth) {
                                              this._calendarNext(type);
                                          } else {
                                              elt.enable();
                                          }
                                      }.bind(this),
                                      { asynchronous: false });
                    }
                    if (i == 2) {
                        if ($F('kronolithCalendarremoteUsername')) {
                            params.username = $F('kronolithCalendarremoteUsername');
                            params.password =  $F('kronolithCalendarremotePassword');
                        }
                        this.doAction('GetRemoteInfo',
                                      params,
                                      function(r) {
                                          if (r.response.success) {
                                              if (r.response.name &&
                                                  !$F('kronolithCalendarremoteName')) {
                                                  $('kronolithCalendarremoteName').setValue(r.response.name);
                                              }
                                              if (r.response.desc &&
                                                  !$F('kronolithCalendarremoteDescription')) {
                                                  $('kronolithCalendarremoteDescription').setValue(r.response.desc);
                                              }
                                              this._calendarNext(type);
                                          } else if (r.response.auth) {
                                              this.showNotifications([{ type: 'horde.warning', message: Kronolith.text.wrong_auth }]);
                                              elt.enable();
                                          } else {
                                              elt.enable();
                                          }
                                      }.bind(this));
                    }
                    e.stop();
                    return;
                }
                this._calendarNext(type);
                elt.disable();
                e.stop();
                return;
            } else if (elt.hasClassName('kronolithCalendarDelete')) {
                var form = elt.up('form'),
                    type = form.id.replace(/kronolithCalendarForm/, ''),
                    calendar = $F('kronolithCalendar' + type + 'Id');
                this.doAction('DeleteCalendar',
                              { type: type, calendar: calendar },
                              function(r) {
                                  if (r.response.deleted) {
                                      var div = this.getCalendarList(type, Kronolith.conf.calendars[type][calendar].owner).select('div').find(function(element) {
                                          return element.retrieve('calendar') == calendar;
                                      });
                                      div.previous('span').remove();
                                      div.remove();
                                      this._deleteCache(null, calendar);
                                      $('kronolithBody').select('div').findAll(function(el) {
                                          return el.retrieve('calendar') == calendar;
                                      }).invoke('remove');
                                      delete Kronolith.conf.calendars[type][calendar];
                                  }
                                  this._closeRedBox();
                                  window.history.back();
                              }.bind(this));
                elt.disable();
                e.stop();
                return;
            }

            calClass = elt.retrieve('calendarclass');
            if (calClass) {
                var calendar = elt.retrieve('calendar');
                Kronolith.conf.calendars[calClass][calendar].show = !Kronolith.conf.calendars[calClass][calendar].show;
                if ([ 'day', 'week', 'month', 'year' ].include(this.view)) {
                    if (this.view == 'year' ||
                        Object.isUndefined(this.ecache.get(calClass)) ||
                        Object.isUndefined(this.ecache.get(calClass).get(calendar))) {
                        var dates = this.viewDates(this.date, this.view);
                        this._loadEvents(dates[0], dates[1], this.view, [[calClass, calendar]]);
                    } else {
                        $('kronolithBody').select('div').findAll(function(el) {
                            return el.retrieve('calendar') == calClass + '|' + calendar;
                        }).invoke('toggle');
                    }
                }
                elt.toggleClassName('kronolithCalOn');
                elt.toggleClassName('kronolithCalOff');
                switch (calClass) {
                case 'tasklists':
                    var tasklist = calendar.substr(6), toggle = true;
                    if (this.view == 'tasks') {
                        var tasktypes;
                        switch (this.tasktype) {
                        case 'all':
                        case 'future':
                            tasktypes = [ 'complete', 'incomplete' ];
                            break;
                        case 'complete':
                        case 'incomplete':
                            tasktypes = [ this.tasktype ];
                            break;
                        }
                        tasktypes.each(function(tasktype) {
                            if (Object.isUndefined(this.tcache.get(tasktype)) ||
                                Object.isUndefined(this.tcache.get(tasktype).get(tasklist))) {
                                toggle = false;
                            }
                        }, this);
                    }
                    if (toggle) {
                        $('kronolithViewTasksBody').select('tr').findAll(function(el) {
                            return el.retrieve('tasklist') == tasklist;
                        }).invoke('toggle');
                    } else {
                        this._loadTasks(this.tasktype, [ tasklist ]);
                    }
                    // Fall through.
                case 'remote':
                case 'external':
                case 'holiday':
                    calendar = calClass + '_' + calendar;
                    break;
                }
                this.doAction('SaveCalPref', { toggle_calendar: calendar });
                e.stop();
                return;
            }

            elt = elt.up();
        }
        // Workaround Firebug bug.
        Prototype.emptyFunction();
    },

    mouseHandler: function(e, type)
    {
        /*
        var elt = e.element();

        switch (type) {
        case 'over':
            if (DragDrop.Drags.drag && elt.hasClassName('exp')) {
                this._toggleSubFolder(elt.up(), 'exp');
            }
            break;
        }
        */
    },

    onDrop: function(e)
    {
        var drop = e.element(),
            el = e.memo.element,
            eventid = el.retrieve('eventid'),
            cal = el.retrieve('calendar'),
            viewDates = this.viewDates(this.date, 'month'),
            start = viewDates[0].toString('yyyyMMdd'),
            end = viewDates[1].toString('yyyyMMdd');

        if (drop == el.parentNode) {
            return;
        }

        drop.insert(el);
        this.startLoading(cal, start + end);
        this.doAction('UpdateEvent',
                      { cal: cal,
                        id: eventid,
                        view: this.view,
                        view_start: start,
                        view_end: end,
                        att: $H({ start_date: drop.retrieve('date') }).toJSON() },
                      function(r) {
                          if (r.response.events) {
                              this._removeEvent(eventid, cal);
                          }
                          this._loadEventsCallback(r);
                      }.bind(this));
    },

    onDragStart: function(e)
    {
        if (this.view == 'month') {
            return;
        }

        var elt = e.element();

        if (elt.hasClassName('kronolithDragger')) {
            elt = elt.up().addClassName('kronolithSelected');
        } else if (elt.hasClassName('kronolithEditable')) {
            elt.addClassName('kronolithSelected').setStyle({ left: 0, width: '100%', zIndex: 1 });
        }
    },

    onDrag: function(e)
    {
        if (this.view == 'month') {
            return;
        }

        var elt = e.element(),
            drag = DragDrop.Drags.getDrag(elt),
            event = drag.event.value,
            storage = this.view + 'Sizes',
            step = this[storage].height / 6;

        if (elt.hasClassName('kronolithDragger')) {
            var div = elt.up(),
                top = drag.ghost.cumulativeOffset().top,
                offset, height, dates;

            if (elt.hasClassName('kronolithDraggerTop')) {
                offset = top - drag.dragTop;
                height = div.offsetHeight - offset;
                div.setStyle({
                    top: (div.offsetTop + offset) + 'px'
                });
                offset = drag.ghost.offsetTop;
                drag.dragTop = top;
            } else {
                offset = top - drag.dragBottom;
                height = div.offsetHeight + offset;
                offset = div.offsetTop;
                drag.dragBottom = top;
            }
            div.setStyle({
                height: height + 'px'
            });

            this._calculateEventDates(event, storage, step, offset, height);
            drag.innerDiv.update('(' + event.start.toString(Kronolith.conf.time_format) + ' - ' + event.end.toString(Kronolith.conf.time_format) + ') ' + event.t.escapeHTML());
        } else if (elt.hasClassName('kronolithEditable')) {
            if (Object.isUndefined(drag.innerDiv)) {
                drag.innerDiv = drag.ghost.down('.kronolithEventInfo');
            }
            if (this.view == 'week') {
                var offsetX = Math.round(drag.ghost.offsetLeft / drag.stepX);
                event.offsetDays = offsetX;
                this._calculateEventDates(event, storage, step, drag.ghost.offsetTop, drag.divHeight, event.start.clone().addDays(offsetX), event.end.clone().addDays(offsetX));
            } else {
                event.offsetDays = 0;
                this._calculateEventDates(event, storage, step, drag.ghost.offsetTop, drag.divHeight);
            }
            event.offsetTop = drag.ghost.offsetTop - drag.startTop;
            drag.innerDiv.update('(' + event.start.toString(Kronolith.conf.time_format) + ' - ' + event.end.toString(Kronolith.conf.time_format) + ') ' + event.t.escapeHTML());
            elt.clonePosition(drag.ghost);
        }
    },

    onDragEnd: function(e)
    {
        if (this.view == 'month') {
            return;
        }

        if (!e.element().hasClassName('kronolithDragger') &&
            !e.element().hasClassName('kronolithEditable')) {
            return;
        }

        var div = e.element(),
            drag = DragDrop.Drags.getDrag(div),
            event = drag.event,
            date = drag.midnight,
            storage = this.view + 'Sizes',
            step = this[storage].height / 6,
            dates = this.viewDates(date, this.view),
            start = dates[0].dateString(),
            end = dates[1].dateString(),
            attributes;

        div.removeClassName('kronolithSelected');
        this._setEventText(drag.innerDiv, event.value);
        drag.destroy();
        this.startLoading(event.value.calendar, start + end);
        if (!Object.isUndefined(event.value.offsetTop)) {
            attributes = $H({ offDays: event.value.offsetDays,
                              offMins: event.value.offsetTop / step * 10 });
        } else if (div.hasClassName('kronolithDraggerTop')) {
            attributes = $H({ start: event.value.start });
        } else if (div.hasClassName('kronolithDraggerBottom')) {
            attributes = $H({ end: event.value.end });
        } else {
            attributes = $H({ start: event.value.start,
                              end: event.value.end });
        }
        this.doAction(
            'UpdateEvent',
            { cal: event.value.calendar,
              id: event.key,
              view: this.view,
              view_start: start,
              view_end: end,
              att: attributes.toJSON()
            },
            function(r) {
                if (r.response.events) {
                    this._removeEvent(event.key, event.value.calendar);
                }
                this._loadEventsCallback(r);
            }.bind(this));
    },

    editEvent: function(calendar, id, date)
    {
        if (Object.isUndefined($('kronolithEventTags').autocompleter)) {
            this.editEvent.bind(this, calendar, id, date).defer();
            return;
        }

        RedBox.onDisplay = function() {
            try {
                $('kronolithEventForm').focusFirstElement();
            } catch(e) {}
            if (Kronolith.conf.maps.driver &&
                $('kronolithEventLinkMap').up().hasClassName('activeTab') &&
                !this.mapInitialized) {

                this.initializeMap();
            }
            RedBox.onDisplay = null;
        }.bind(this);

        this.updateCalendarDropDown('kronolithEventTarget');
        this.toggleAllDay(false);
        $('kronolithEventForm').enable();
        $('kronolithEventForm').reset();
        $('kronolithEventMapLink').hide();
        $('kronolithEventSave').show();
        $('kronolithEventDelete').show();
        $('kronolithEventForm').down('.kronolithFormActions .kronolithSep').show();
        this.doAction('ListTopTags', {}, this._topTags);
        if (id) {
            RedBox.loading();
            this.doAction('GetEvent', { cal: calendar, id: id, date: date }, this._editEvent.bind(this));
        } else {
            $('kronolithEventTags').autocompleter.init();
            var d;
            if (date) {
                if (date.endsWith('all')) {
                    date = date.substring(0, date.length - 3);
                    $('kronolithEventAllday').setValue(true);
                    this.toggleAllDay(true);
                }
                d = this.parseDate(date);
            } else {
                d = new Date();
            }
            $('kronolithEventId').clear();
            $('kronolithEventCalendar').clear();
            $('kronolithEventTarget').setValue(Kronolith.conf.default_calendar);
            $('kronolithEventDelete').hide();
            $('kronolithEventStartDate').setValue(d.toString(Kronolith.conf.date_format));
            $('kronolithEventStartTime').setValue(d.toString(Kronolith.conf.time_format));
            d.add(1).hour();
            $('kronolithEventEndDate').setValue(d.toString(Kronolith.conf.date_format));
            $('kronolithEventEndTime').setValue(d.toString(Kronolith.conf.time_format));
            RedBox.showHtml($('kronolithEventDialog').show());
        }
    },

    /**
     * Submits the event edit form to create or update an event.
     */
    saveEvent: function()
    {
        if (this.wrongFormat.size()) {
            this.showNotifications([{ type: 'horde.warning', message: Kronolith.text.fix_form_values }]);
            return;
        }

        var cal = $F('kronolithEventTarget'),
            eventid = $F('kronolithEventId'),
            viewDates = this.viewDates(this.date, this.view),
            start = viewDates[0].dateString(),
            end = viewDates[1].dateString();
        $('kronolithEventTags').autocompleter.shutdown();
        this.startLoading(cal, start + end);
        this.doAction('SaveEvent',
                      $H($('kronolithEventForm').serialize({ hash: true }))
                          .merge({
                              view: this.view,
                              view_start: start,
                              view_end: end
                          }),
                      function(r) {
                          if (r.response.events && eventid) {
                              this._removeEvent(eventid, cal);
                          }
                          this._loadEventsCallback(r);
                          this.resetMap();
                          this._closeRedBox();
                          window.history.back();
                      }.bind(this));
    },

    quickSaveEvent: function()
    {
        var text = $F('kronolithQuickinsertQ'),
            viewDates = this.viewDates(this.date, this.view),
            start = viewDates[0].dateString(),
            end = viewDates[1].dateString();

        $('kronolithQuickinsert').fade();
        this.startLoading(null, start + end);
        this.doAction('QuickSaveEvent',
                      $H({ text: text,
                           view: this.view,
                           view_start: start,
                           view_end: end
                      }),
                      function(r) {
                          this._loadEventsCallback(r);
                          if (Object.isUndefined(r.msgs)) {
                              $('kronolithQuickinsertQ').value = '';
                          }
                      }.bind(this));
    },

    _topTags: function(r)
    {
        if (!r.response.tags) {
            $('kronolithEventTopTags').update();
            return;
        }
        t = new Element('div');
        r.response.tags.each(function(tag) {
            t.insert(new Element('span', { className: 'kronolithEventTag' }).update(tag.escapeHTML()));
        });
        $('kronolithEventTopTags').update(t);
        return;
    },

    /**
     * Callback method for showing event forms.
     *
     * @param object r  The ajax response object.
     */
    _editEvent: function(r)
    {
        if (!r.response.event) {
            RedBox.close();
            window.history.back();
            return;
        }

        var ev = r.response.event;

        if (!Object.isUndefined(ev.ln)) {
            this.iframeContent('event', ev.ln);
            this._closeRedBox();
            return;
        }

        /* Basic information */
        $('kronolithEventId').setValue(ev.id);
        $('kronolithEventCalendar').setValue(ev.ty + '|' + ev.c);
        $('kronolithEventTarget').setValue(ev.ty + '|' + ev.c);
        $('kronolithEventTitle').setValue(ev.t);
        $('kronolithEventLocation').setValue(ev.l);
        if (ev.l) {
            $('kronolithEventMapLink').show();
        }
        $('kronolithEventUrl').setValue(ev.u);
        $('kronolithEventAllday').setValue(ev.al);
        this.toggleAllDay(ev.al);
        $('kronolithEventStartDate').setValue(ev.sd);
        $('kronolithEventStartTime').setValue(ev.st);
        $('kronolithEventEndDate').setValue(ev.ed);
        $('kronolithEventEndTime').setValue(ev.et);
        $('kronolithEventDescription').setValue(ev.d);

        /* Alarm */
        if (ev.a) {
            $('kronolithEventAlarmOn').setValue(true);
            [10080, 1440, 60, 1].each(function(unit) {
                if (ev.a % unit == 0) {
                    $('kronolithEventAlarmValue').setValue(ev.a / unit);
                    $('kronolithEventAlarmUnit').setValue(unit);
                    throw $break;
                }
            });
        } else {
            $('kronolithEventAlarmOff').setValue(true);
        }

        /* Recurrence */
        if (ev.r) {
            var scheme = Kronolith.conf.recur[ev.r.t],
                div = $('kronolithEventRepeat' + scheme);
            $('kronolithEventLink' + scheme).setValue(true);
            this.toggleRecurrence(scheme);
            if (scheme == 'Monthly' || scheme == 'Yearly') {
                div.down('input[name=recur_' + scheme.toLowerCase() + '_scheme][value=' + ev.r.t + ']').setValue(true);
            }
            if (scheme == 'Weekly') {
                div.select('input[type=checkbox]').each(function(input) {
                    if (input.name == 'weekly[]' &&
                        input.value & ev.r.d) {
                        input.setValue(true);
                    }
                });
            }
            if (ev.r.i == 1) {
                div.down('input[name=recur_' + scheme.toLowerCase() + '][value=1]').setValue(true);
            } else {
                div.down('input[name=recur_' + scheme.toLowerCase() + '][value=0]').setValue(true);
                div.down('input[name=recur_' + scheme.toLowerCase() + '_interval]').setValue(ev.r.i);
            }
            if (!Object.isUndefined(ev.r.e)) {
                $('kronolithEventRepeatLength').down('input[name=recur_end_type][value=date]').setValue(true);
                $('kronolithEventRecurDate').setValue(Date.parse(ev.r.e).toString(Kronolith.conf.date_format));
            } else if (!Object.isUndefined(ev.r.c)) {
                $('kronolithEventRepeatLength').down('input[name=recur_end_type][value=count]').setValue(true);
                $('kronolithEventRecurCount').setValue(ev.r.c);
            } else {
                $('kronolithEventRepeatLength').down('input[name=recur_end_type][value=none]').setValue(true);
            }
        }

        /* Attendees */
        this.freeBusy = $H();
        $('kronolithEventStartDate').stopObserving('change');
        if (!Object.isUndefined(ev.at)) {
            $('kronolithEventAttendees').setValue(ev.at.pluck('l').join(', '));
            var table = $('kronolithEventTabAttendees').down('tbody');
            table.select('tr').invoke('remove');
            ev.at.each(function(attendee) {
                var tr = new Element('tr'), i;
                this.fbLoading++;
                this.doAction('GetFreeBusy',
                              { email: attendee.e },
                              function(r) {
                                  this.fbLoading--;
                                  if (!this.fbLoading) {
                                      $('kronolithFBLoading').hide();
                                  }
                                  if (Object.isUndefined(r.response.fb)) {
                                      return;
                                  }
                                  this.freeBusy.set(attendee.e, [ tr, r.response.fb ]);
                                  this._insertFreeBusy(attendee.e);
                              }.bind(this));
                tr.insert(new Element('td').writeAttribute('title', attendee.l).insert(attendee.e.escapeHTML()));
                for (i = 0; i < 24; i++) {
                    tr.insert(new Element('td', { className: 'kronolithFBUnknown' }));
                }
                table.insert(tr);
            }, this);
            if (this.fbLoading) {
                $('kronolithFBLoading').show();
            }
            $('kronolithEventStartDate').observe('change', function() {
                ev.at.each(function(attendee) {
                    this._insertFreeBusy(attendee.e);
                }, this);
            }.bind(this));
        }

        /* Tags */
        $('kronolithEventTags').autocompleter.init(ev.tg);

        /* Geo */
        if (ev.gl) {
            $('kronolithEventLocationLat').value = ev.gl.lat;
            $('kronolithEventLocationLon').value = ev.gl.lon;
        }

        if (!ev.pe) {
            $('kronolithEventSave').hide();
            $('kronolithEventForm').disable();
        }
        if (!ev.pd) {
            $('kronolithEventDelete').hide();
        }
        if (!ev.pe && !ev.pd) {
            $('kronolithEventForm').down('.kronolithFormActions .kronolithSep').hide();
        }

        this.setTitle(ev.t);
        RedBox.showHtml($('kronolithEventDialog').show());
    },

    /**
     * Inserts rows with free/busy information into the attendee table.
     *
     * @param string email  An email address as the free/busy identifier.
     */
    _insertFreeBusy: function(email)
    {
        if (!$('kronolithEventDialog').visible() ||
            !this.freeBusy.get(email)) {
            return;
        }
        var fb = this.freeBusy.get(email)[1],
            tr = this.freeBusy.get(email)[0],
            td = tr.select('td')[1],
            div = td.down('div');
        if (!td.getWidth()) {
            this._insertFreeBusy.bind(this, email).defer();
            return;
        }
        tr.select('td').each(function(td, i) {
            if (i != 0) {
                td.addClassName('kronolithFBFree');
            }
        });
        if (div) {
            div.remove();
        }
        var start = Date.parseExact($F('kronolithEventStartDate'), Kronolith.conf.date_format),
            end = start.clone().add(1).days(),
            width = td.getWidth();
        div = new Element('div').setStyle({ position: 'relative' });
        td.insert(div);
        $H(fb.b).each(function(busy) {
            var from = new Date(), to = new Date(), left;
            from.setTime(busy.key * 1000);
            to.setTime(busy.value * 1000);
            if (from.isAfter(end) || to.isBefore(start)) {
                return;
            }
            if (from.isBefore(start)) {
                from = start.clone();
            }
            if (to.isAfter(end)) {
                to = end.clone();
            }
            if (to.getHours() == 0 && to.getMinutes() == 0) {
                to.add(-1).minutes();
            }
            left = from.getHours() + from.getMinutes() / 60;
            div.insert(new Element('div', { className: 'kronolithFBBusy' }).setStyle({ zIndex: 1, top: 0, left: (left * width) + 'px', width: (((to.getHours() + to.getMinutes() / 60) - left) * width) + 'px' }));
        });
    },

    /**
     * Toggles the start and end time fields of the event edit form on and off.
     *
     * @param boolean on  Whether the event is an all-day event, i.e. the time
     *                    fields should be turned off. If not specified, the
     *                    current state is toggled.
     */
    toggleAllDay: function(on)
    {
        if (Object.isUndefined(on)) {
            on = $('kronolithEventStartTimeLabel').getStyle('visibility') == 'visible';
        }
        $('kronolithEventStartTimeLabel').setStyle({ visibility: on ? 'hidden' : 'visible' });
        $('kronolithEventEndTimeLabel').setStyle({ visibility: on ? 'hidden' : 'visible' });
    },

    /**
     * Toggles the recurrence fields of the event edit form.
     *
     * @param string recur  The recurrence part of the field name, i.e. 'None',
     *                      'Daily', etc.
     */
    toggleRecurrence: function(recur)
    {
        $('kronolithEventTabRecur').select('div').invoke('hide');
        if (recur != 'None') {
            $('kronolithEventRepeat' + recur).show();
            $('kronolithEventRepeatLength').show();
        }
    },

    checkDate: function(e) {
        var elm = e.element();
        if ($F(elm)) {
            var date = Date.parseExact($F(elm), Kronolith.conf.date_format) || Date.parse($F(elm));
            if (date) {
                elm.setValue(date.toString(Kronolith.conf.date_format));
                this.wrongFormat.unset(elm.id);
            } else {
                this.showNotifications([{ type: 'horde.warning', message: Kronolith.text.wrong_date_format.interpolate({ wrong: $F(elm), right: new Date().toString(Kronolith.conf.date_format) }) }]);
                this.wrongFormat.set(elm.id, true);
            }
        }
    },

    checkTime: function(e) {
        var elm = e.element();
        if ($F(elm)) {
            var time = Date.parseExact(new Date().toString(Kronolith.conf.date_format) + ' ' + $F(elm), Kronolith.conf.date_format + ' ' + Kronolith.conf.time_format) || Date.parse(new Date().toString('yyyy-MM-dd ') + $F(elm));
            if (time) {
                elm.setValue(time.toString(Kronolith.conf.time_format));
                this.wrongFormat.unset(elm.id);
            } else {
                this.showNotifications([{ type: 'horde.warning', message: Kronolith.text.wrong_time_format.interpolate({ wrong: $F(elm), right: new Date().toString(Kronolith.conf.time_format) }) }]);
                this.wrongFormat.set(elm.id, true);
            }
        }
    },

    _closeRedBox: function()
    {
        var content = RedBox.getWindowContents();
        if (content) {
            document.body.insert(content.hide());
        }
        RedBox.close();
    },

    toggleCalendar: function(elm)
    {
        elm.toggleClassName('on');
    },

    // By default, no context onShow action
    contextOnShow: Prototype.emptyFunction,

    // By default, no context onClick action
    contextOnClick: Prototype.emptyFunction,

    // Map
    initializeMap: function()
    {
         var layers = [];
         if (Kronolith.conf.maps.providers) {
             Kronolith.conf.maps.providers.each(function(l)
                 {
                     var p = new HordeMap[l]();
                     $H(p.getLayers()).values().each(function(e) { layers.push(e); });
                 });
         }

         this.map = new HordeMap.Map[Kronolith.conf.maps.driver](
             {
                 elt: 'kronolithEventMap',
                 delayed: true,
                 layers: layers,
                 markerDragEnd: this.onMarkerDragEnd.bind(this),
                 mapClick: this.afterClickMap.bind(this)
             });

         if ($('kronolithEventLocationLat').value) {
             var ll = { lat:$('kronolithEventLocationLat').value, lon: $('kronolithEventLocationLon').value };
             this.placeMapMarker(ll, true);
         }
         //@TODO: check for Location field - and if present, but no lat/lon value, attempt to
         // geocode it.
         this.map.display();
         this.mapInitialized = true;
    },

    resetMap: function()
    {
        this.mapInitialized = false;
        $('kronolithEventLocationLat').value = null;
        $('kronolithEventLocationLon').value = null;
        if (this.mapMarker) {
            this.map.removeMarker(this.mapMarker);
            this.mapMarker = null;
        }
        if (this.map) {
            this.map.destroy();
            this.map = null;
        }
    },

    /**
     * Callback for handling marker drag end.
     *
     * @param object r  An object that implenents a getLonLat() method to obtain
     *                  the new location of the marker.
     */
    onMarkerDragEnd: function(r)
    {
        var ll = r.getLonLat();
        $('kronolithEventLocationLon').value = ll.lon;
        $('kronolithEventLocationLat').value = ll.lat;
        var gc = new HordeMap.Geocoder[Kronolith.conf.maps.geocoder](this.map.map, 'kronolithEventMap');
        gc.reverseGeocode(ll, this.onReverseGeocode.bind(this), this.onGeocodeError.bind(this) );
    },

    /**
     * Callback for handling a reverse geocode request.
     *
     * @param array r  An array of objects containing the results. Each object in
     *                 the array is {lat:, lon:, address}
     */
    onReverseGeocode: function(r) {
        if (!r.length) {
            $('kronolithEventLocation').value = '';
            return;
        }
        $('kronolithEventLocation').value = r[0].address;
    },

    onGeocodeError: function(r)
    {
        KronolithCore.showNotifications([ { type: 'horde.error', message: Kronolith.text.geocode_error } ]);
    },


    /**
     * Callback for geocoding calls.
     * @TODO: Figure out the proper zoom level based on the detail of the
     * provided address.
     */
    onGeocode: function(r)
    {
        r = r.shift();
        ll = new OpenLayers.LonLat(r.lon, r.lat);
        this.placeMapMarker({ lat: r.lat, lon: r.lon }, true);
    },

    geocode: function(a) {
        if (!a) {
            return;
        }
        var gc = new HordeMap.Geocoder[Kronolith.conf.maps.geocoder](this.map.map, 'kronolithEventMap');
        gc.geocode(a, this.onGeocode.bind(this), this.onGeocodeError);
    },

    /**
     * Place the event marker on the map, ensuring it exists.
     * See note in onGeocode about zoomlevel
     */
    placeMapMarker: function(ll, center)
    {
        if (!this.mapMarker) {
            this.mapMarker = this.map.addMarker(
                    ll,
                    { draggable: true },
                    {
                        context: this,
                        dragend: this.onMarkerDragEnd
                    });
        } else {
            this.map.moveMarker(this.mapMarker, ll);
        }
        $('kronolithEventLocationLon').value = ll.lon;
        $('kronolithEventLocationLat').value = ll.lat;
        if (center) {
            this.map.setCenter(ll, 8);
            //this.map.zoomToFit();
        }
    },

    ensureMap: function()
    {
        if (!this.mapInitialized) {
            this.initializeMap();
        }
        var dialog = $('kronolithEventForm')
        dialog.select('.kronolithTabsOption').invoke('hide');
        dialog.select('.tabset li').invoke('removeClassName', 'activeTab');
        $('kronolithEventTabMap').show();
        $('kronolithEventLinkMap').parentNode.addClassName('activeTab');
    },

    /**
     * Callback that gets called after a new marker has been placed on the map
     * due to a single click on the map.
     *
     * @return object o  { lonlat: }
     */
    afterClickMap: function(o)
    {
        this.placeMapMarker(o.lonlat, false);
        var gc = new HordeMap.Geocoder[Kronolith.conf.maps.geocoder](this.map.map, 'kronolithEventMap');
        gc.reverseGeocode(o.lonlat, this.onReverseGeocode.bind(this), this.onGeocodeError.bind(this) );
    },

    /* Onload function. */
    onDomLoad: function()
    {
        var dateFields, timeFields;

        if (typeof ContextSensitive != 'undefined') {
            this.DMenu = new ContextSensitive({ onClick: this.contextOnClick, onShow: this.contextOnShow });
        }

        document.observe('keydown', KronolithCore.keydownHandler.bindAsEventListener(KronolithCore));
        document.observe('keyup', KronolithCore.keyupHandler.bindAsEventListener(KronolithCore));
        document.observe('click', KronolithCore.clickHandler.bindAsEventListener(KronolithCore));
        document.observe('dblclick', KronolithCore.clickHandler.bindAsEventListener(KronolithCore, true));
        document.observe('mouseover', KronolithCore.mouseHandler.bindAsEventListener(KronolithCore, 'over'));

        $('kronolithSearchTerm').observe('focus', function() {
            if ($F(this) == this.readAttribute('default')) {
                this.clear();
            }
        });
        $('kronolithSearchTerm').observe('blur', function() {
            if (!$F(this)) {
                this.setValue(this.readAttribute('default'));
            }
        });

        $('kronolithEventStartDate', 'kronolithEventEndDate', 'kronolithTaskDueDate').compact().invoke('observe', 'blur', this.checkDate.bind(this));
        $('kronolithEventStartTime', 'kronolithEventEndTime', 'kronolithTaskDueTime').compact().invoke('observe', 'blur', this.checkTime.bind(this));

        if (Kronolith.conf.has_tasks) {
            $('kronolithTaskDueDate', 'kronolithEventDueTime').compact().invoke('observe', 'focus', this.setDefaultDue.bind(this));
        }

        // Mouse wheel handler.
        dateFields = [ 'kronolithEventStartDate', 'kronolithEventEndDate' ];
        timeFields = [ 'kronolithEventStartTime', 'kronolithEventEndTime' ];
        if (Kronolith.conf.has_tasks) {
            dateFields.push('kronolithTaskDueDate');
            timeFields.push('kronolithTaskDueTime');
        }
        dateFields.each(function(field) {
            $(field).observe(Prototype.Browser.Gecko ? 'DOMMouseScroll' : 'mousewheel', function(e) {
                var date = Date.parseExact($F(field), Kronolith.conf.date_format);
                if (!date || (!e.wheelData && !e.detail)) {
                    return;
                }
                date.add(e.wheelData > 0 || e.detail < 0 ? 1 : -1).days();
                $(field).setValue(date.toString(Kronolith.conf.date_format));
            });
        });
        timeFields.each(function(field) {
            $(field).observe(Prototype.Browser.Gecko ? 'DOMMouseScroll' : 'mousewheel', function(e) {
                var time = $F(field).match(/(\d+)\s*:\s*(\d+)\s*((a|p)m)?/i),
                    hour, minute;
                if (!time || (!e.wheelData && !e.detail)) {
                    return;
                }

                minute = parseInt(time[2]);
                if (minute % 10) {
                    if (e.wheelData > 0 || e.detail < 0) {
                        minute = (minute + 10) / 10 | 0;
                    } else {
                        minute = minute / 10 | 0;
                    }
                    minute *= 10;
                } else {
                    minute += (e.wheelData > 0 || e.detail < 0 ? 10 : -10);
                }
                hour = parseInt(time[1]);
                if (minute < 0) {
                    if (hour > 0) {
                        hour--;
                        minute = 50;
                    } else {
                        minute = 0;
                    }
                } else if (minute >= 60) {
                    if (hour < 23) {
                        hour++;
                        minute = 0;
                    } else {
                        minute = 59;
                    }
                }

                $(field).setValue($F(field).replace(/(.*?)\d+(\s*:\s*)\d+(.*)/, '$1' + hour + ':' + minute.toPaddedString(2) + '$3'));

                /* Mozilla bug https://bugzilla.mozilla.org/show_bug.cgi?id=502818
                 * Need to stop or else multiple scroll events may be fired. We
                 * lose the ability to have the mousescroll bubble up, but that is
                 * more desirable than having the wrong scrolling behavior. */
                if (Prototype.Browser.Gecko && !e.stop) {
                    Event.stop(e);
                }
            });
        });

        if (Horde.dhtmlHistory.initialize()) {
            Horde.dhtmlHistory.addListener(this.go.bind(this));
        }

        this.updateCalendarList();

        /* Initialize the starting page if necessary. addListener() will have
         * already fired if there is a current location so only do a go()
         * call if there is no current location. */
        if (!Horde.dhtmlHistory.getCurrentLocation()) {
            this.go(Kronolith.conf.login_view);
        }

        $('kronolithMenu').select('div.kronolithCalendars div').each(function(s) {
            s.observe('mouseover', s.addClassName.curry('kronolithCalOver'));
            s.observe('mouseout', s.removeClassName.curry('kronolithCalOver'));
        });

        /* Add Growler notifications. */
        this.Growler = new Growler({
            log: true,
            location: 'br',
            noalerts: Kronolith.text.noalerts
        });

        if (Kronolith.conf.is_ie6) {
            /* Disable text selection in preview pane for IE 6. */
            document.observe('selectstart', Event.stop);

            /* Since IE 6 doesn't support hover over non-links, use javascript
             * events to replicate mouseover CSS behavior. */
            $('foobar').compact().invoke('select', 'LI').flatten().compact().each(function(e) {
                e.observe('mouseover', e.addClassName.curry('over')).observe('mouseout', e.removeClassName.curry('over'));
            });
        }
    }

};

/* Initialize global event handlers. */
document.observe('dom:loaded', KronolithCore.onDomLoad.bind(KronolithCore));
Event.observe(window, 'resize', KronolithCore.onResize.bind(KronolithCore));
document.observe('DragDrop2:drag', KronolithCore.onDrag.bindAsEventListener(KronolithCore));
document.observe('DragDrop2:drop', KronolithCore.onDrop.bindAsEventListener(KronolithCore));
document.observe('DragDrop2:end', KronolithCore.onDragEnd.bindAsEventListener(KronolithCore));
document.observe('DragDrop2:start', KronolithCore.onDragStart.bindAsEventListener(KronolithCore));
