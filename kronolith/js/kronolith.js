/**
 * kronolith.js - Base application logic.
 *
 * TODO: loadingImg()
 *
 * Copyright 2008-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Jan Schneider <jan@horde.org>
 */

/* Kronolith object. */
KronolithCore = {
    // Vars used and defaulting to null/false:
    //   weekSizes, daySizes,
    //   groupLoading, colorPicker, duration, timeMarker, monthDays,
    //   allDays, eventsWeek, initialized

    view: '',
    ecache: $H(),
    cacheStart: null,
    cacheEnd: null,
    holidays: [],
    tcache: $H(),
    eventsLoading: {},
    loading: 0,
    viewLoading: [],
    fbLoading: 0,
    redBoxLoading: false,
    date: Date.today(),
    tasktype: 'incomplete',
    knl: {},
    wrongFormat: $H(),
    mapMarker: null,
    map: null,
    mapInitialized: false,
    freeBusy: $H(),
    search: 'future',
    effectDur: 0.4,
    macos: navigator.appVersion.indexOf('Mac') != -1,
    orstart: null,
    orend: null,
    lastRecurType: 'None',
    uatts: null,
    ucb: null,
    resourceACCache: { choices: [], map: $H() },
    paramsCache: null,
    attendees: [],
    resources: [],

    /**
     * Flag that indicates if the event currently displayed in the event
     * properties window is a recurring event.
     *
     * @type boolean
     */
    recurs: false,

    /**
     * The location that was open before the current location.
     *
     * @var string
     */
    lastLocation: '',

    /**
     * The currently open location.
     *
     * @var string
     */
    openLocation: '',

    /**
     * The current (main) location.
     *
     * This is different from openLocation as it isn't updated for any
     * locations that are opened in a popup view, e.g. events.
     *
     * @var string
     */
    currentLocation: '',

    kronolithBody: $('kronolithBody'),

    onException: function(parentfunc, r, e)
    {
        /* Make sure loading images are closed. */
        this.loading--;
        if (!this.loading) {
            $('kronolithLoading').hide();
        }
        this.closeRedBox();
        HordeCore.notify(HordeCore.text.ajax_error, 'horde.error');
        parentfunc(r, e);
    },

    setTitle: function(title)
    {
        document.title = Kronolith.conf.name + ' :: ' + title;
        return title;
    },

    // url = (string) URL to redirect to
    // hash = (boolean) If true, url is treated as hash information to alter
    //        on the current page
    redirect: function(url, hash)
    {
        if (hash) {
            window.location.hash = escape(url);
            window.location.reload();
        } else {
            HordeCore.redirect(url);
        }
    },

    go: function(fullloc, data)
    {
        if (!this.initialized) {
            this.go.bind(this, fullloc, data).defer();
            return;
        }

        if (this.viewLoading.size()) {
            this.viewLoading.push([ fullloc, data ]);
            return;
        }

        var locParts = fullloc.split(':');
        var loc = locParts.shift();

        if (this.openLocation == fullloc) {
            return;
        }

        this.viewLoading.push([ fullloc, data ]);

        if (loc != 'search') {
            HordeTopbar.searchGhost.reset();
        }

        this.switchTaskView(false);

        switch (loc) {
        case 'day':
        case 'week':
        case 'workweek':
        case 'month':
        case 'year':
        case 'agenda':
        case 'tasks':
            this.closeView(loc);
            var locCap = loc.capitalize();
            $('kronolithNav' + locCap).up().addClassName('horde-active');

            switch (loc) {
            case 'day':
            case 'agenda':
            case 'week':
            case 'workweek':
            case 'month':
            case 'year':
                var date = locParts.shift();
                if (date) {
                    date = this.parseDate(date);
                } else {
                    date = this.date;
                }

                if (this.view != 'agenda' &&
                    this.view == loc &&
                    date.getYear() == this.date.getYear() &&
                    ((loc == 'year') ||
                     (loc == 'month' && date.getMonth() == this.date.getMonth()) ||
                     ((loc == 'week' || loc == 'workweek') && date.getRealWeek() == this.date.getRealWeek()) ||
                     ((loc == 'day'  || loc == 'agenda') && date.dateString() == this.date.dateString()))) {
                         this.setViewTitle(date, loc);
                         this.addHistory(fullloc);
                         this.loadNextView();
                         return;
                }

                this.addHistory(fullloc);
                this.view = loc;
                this.date = date;
                this.updateView(date, loc);
                var dates = this.viewDates(date, loc);
                this.loadEvents(dates[0], dates[1], loc);
                $('kronolithView' + locCap).appear({
                        duration: this.effectDur,
                        queue: 'end',
                        afterFinish: function() {
                            if (loc == 'week' || loc == 'workweek' || loc == 'day') {
                                this.calculateRowSizes(loc + 'Sizes', 'kronolithView' + locCap);
                                if ($('kronolithTimeMarker')) {
                                    this.positionTimeMarker();
                                }
                                if ($('kronolithTimeMarker')) {
                                    $('kronolithTimeMarker').show();
                                }
                                // Scroll to the work day start time.
                                $('kronolithView' + locCap).down('.kronolithViewBody').scrollTop = 9 * this[loc + 'Sizes'].height;
                            }
                            this.loadNextView();
                        }.bind(this)
                });
                $('kronolithLoading' + loc).insert($('kronolithLoading').remove());
                this.updateMinical(date, loc);

                break;

            case 'tasks':
                var tasktype = locParts.shift() || this.tasktype;


                this.switchTaskView(true);
                $('kronolithCurrent')
                    .update(this.setTitle(Kronolith.text.tasks));
                if (this.view == loc && this.tasktype == tasktype) {
                    this.addHistory(fullloc);
                    this.loadNextView();
                    return;
                }
                if (!$w('all complete incomplete future').include(tasktype)) {
                    this.loadNextView();
                    return;
                }

                this.addHistory(fullloc);
                this.view = loc;
                this.tasktype = tasktype;
                $w('All Complete Incomplete Future').each(function(tasktype) {
                    $('kronolithTasks' + tasktype).up().removeClassName('horde-active');
                });
                $('kronolithTasks' + this.tasktype.capitalize()).up().addClassName('horde-active');
                this.loadTasks(this.tasktype);
                $('kronolithView' + locCap).appear({
                    duration: this.effectDur,
                    queue: 'end',
                    afterFinish: function() {
                        this.loadNextView();
                    }.bind(this) });
                $('kronolithLoading' + loc).insert($('kronolithLoading').remove());
                this.updateMinical(this.date);

                break;

            default:
                if (!$('kronolithView' + locCap)) {
                    break;
                }
                this.addHistory(fullloc);
                this.view = loc;
                $('kronolithView' + locCap).appear({
                    duration: this.effectDur,
                    queue: 'end',
                    afterFinish: function() {
                        this.loadNextView();
                    }.bind(this) });
                break;
            }

            break;

        case 'search':
            var cals = [], time = locParts[0], term = locParts[1],
                query = Object.toJSON({ title: term });

            if (!($w('all past future').include(time))) {
                this.loadNextView();
                return;
            }

            this.addHistory(fullloc);
            this.search = time;
            $w('All Past Future').each(function(time) {
                $('kronolithSearch' + time).up().removeClassName('horde-active');
            });
            $('kronolithSearch' + this.search.capitalize()).up().addClassName('horde-active');
            this.closeView('agenda');
            this.view = 'agenda';
            this.updateView(null, 'search', term);
            $H(Kronolith.conf.calendars).each(function(type) {
                $H(type.value).each(function(calendar) {
                    if (calendar.value.show) {
                        cals.push(type.key + '|' + calendar.key);
                    }
                });
            });
            $('kronolithAgendaNoItems').hide();
            this.startLoading('search', query);

            HordeCore.doAction('searchEvents', {
                cals: Object.toJSON(cals),
                query: query,
                time: this.search
            }, {
                callback: function(r) {
                    // Hide spinner.
                    this.loading--;
                    if (!this.loading) {
                        $('kronolithLoading').hide();
                    }
                    if (r.view != 'search' ||
                        r.query != this.eventsLoading.search) {
                        return;
                    }
                    if (Object.isUndefined(r.events)) {
                        $('kronolithAgendaNoItems').show();
                        return;
                    }
                    delete this.eventsLoading.search;
                    $H(r.events).each(function(calendars) {
                        $H(calendars.value).each(function(events) {
                            this.createAgendaDay(events.key);
                            $H(events.value).each(function(event) {
                                event.value.calendar = calendars.key;
                                event.value.start = Date.parse(event.value.s);
                                event.value.end = Date.parse(event.value.e);
                                this.insertEvent(event, events.key, 'agenda');
                            }, this);
                        }, this);
                    }, this);
                }.bind(this)
            });

            $('kronolithViewAgenda').appear({
                duration: this.effectDur,
                queue: 'end',
                afterFinish: function() {
                    this.loadNextView();
                }.bind(this) });
            $('kronolithLoadingagenda').insert($('kronolithLoading').remove());
            this.updateMinical(this.date);
            break;

        case 'event':
            // Load view first if necessary.
            if (!this.view ) {
                this.viewLoading.pop();
                this.go(Kronolith.conf.login_view);
                this.go.bind(this, fullloc, data).defer();
                return;
            }

            if (this.currentLocation == fullloc) {
                this.loadNextView();
                return;
            }

            this.addHistory(fullloc, false);
            switch (locParts.length) {
            case 0:
                // New event.
                this.editEvent();
                break;
            case 1:
                // New event on a certain date.
                this.editEvent(null, null, locParts[0]);
                break;
            default:
                // Editing event.
                var date = locParts.pop(),
                    event = locParts.pop(),
                    calendar = locParts.join(':');
                this.editEvent(calendar, event, date);
                break;
            }
            this.loadNextView();
            break;

        case 'task':
            // Load view first if necessary.
            if (!this.view ) {
                this.viewLoading.pop();
                this.go('tasks');
                this.go.bind(this, fullloc, data).defer();
                return;
            }

            this.switchTaskView(true);
            switch (locParts.length) {
            case 0:
                this.addHistory(fullloc, false);
                this.editTask();
                break;
            case 2:
                this.addHistory(fullloc, false);
                this.editTask(locParts[0], locParts[1]);
                break;
            }
            this.loadNextView();
            break;

        case 'calendar':
            if (!this.view) {
                this.viewLoading.pop();
                this.go(Kronolith.conf.login_view);
                this.go.bind(this, fullloc, data).defer();
                return;
            }
            this.addHistory(fullloc, false);
            this.editCalendar(locParts.join(':'));
            this.loadNextView();
            break;

        default:
            this.loadNextView();
            break;
        }
    },

    /**
     * Removes the last loaded view from the stack and loads the last added
     * view, if the stack is still not empty.
     *
     * We want to load views from a LIFO queue, because the queue is only
     * building up if the user switches to another view while the current view
     * still loads. In that case we can go directly to the most recently
     * clicked view and drop the remaining queue.
     */
    loadNextView: function()
    {
        var current = this.viewLoading.shift();
        if (this.viewLoading.size()) {
            var next = this.viewLoading.pop();
            this.viewLoading = [];
            if (current[0] != next[0] || current[1] || next[1]) {
                this.go(next[0], next[1]);
            }
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
            var today = Date.today();
            this.dayEvents = [];
            this.dayGroups = [];
            this.allDayEvents = [];
            $('kronolithCurrent')
                .update(this.setViewTitle(date, view, data));
            $('kronolithViewDay')
                .down('.kronolithAllDayContainer')
                .store('date', date.dateString());
            $('kronolithEventsDay').store('date', date.dateString());
            if (date.equals(today)) {
                this.addTimeMarker('kronolithEventsDay');
            }
            break;

        case 'week':
        case 'workweek':
            this.dayEvents = [];
            this.dayGroups = [];
            this.allDayEvents = [];
            this.allDays = {};
            this.eventsWeek = {};
            var what = view == 'week' ? 'Week' : 'Workweek',
                div = $('kronolithEvents' + what).down('div'),
                th = $('kronolithView' + what + 'Head').down('.kronolithWeekDay'),
                td = $('kronolithView' + what + 'Head').down('tbody td').next('td'),
                hourRow = $('kronolithView' + what + 'Body').down('tr'),
                dates = this.viewDates(date, view),
                today = Date.today(),
                day, dateString, i, hourCol;

            $('kronolithCurrent')
                .update(this.setViewTitle(date, view, data));

            for (i = 0; i < 24; i++) {
                day = dates[0].clone();
                hourCol = hourRow.down('td').next('td');
                while (hourCol) {
                    hourCol.removeClassName('kronolith-today');
                    if (day.equals(today)) {
                        hourCol.addClassName('kronolith-today');
                    }
                    hourCol = hourCol.next('td');
                    day.next().day();
                }
                hourRow = hourRow.next('tr');
            }
            day = dates[0].clone();

            for (i = 0; i < (view == 'week' ? 7 : 5); i++) {
                dateString = day.dateString();
                this.allDays['kronolithAllDay' + dateString] = td.down('div');
                this.eventsWeek['kronolithEvents' + what + dateString] = div;
                div.store('date', dateString)
                    .writeAttribute('id', 'kronolithEvents' + what + dateString);
                th.store('date', dateString)
                    .down('span').update(day.toString('dddd, d'));
                td.removeClassName('kronolith-today');
                this.allDays['kronolithAllDay' + dateString]
                    .writeAttribute('id', 'kronolithAllDay' + dateString)
                    .store('date', dateString);
                if (day.equals(today)) {
                    td.addClassName('kronolith-today');
                    this.addTimeMarker('kronolithEvents' + what + dateString);
                }
                new Drop(td.down('div'));
                div = div.next('div');
                th = th.next('td');
                td = td.next('td');
                day.next().day();
            }
            break;

        case 'month':
            var tbody = $('kronolith-month-body'),
                dates = this.viewDates(date, view),
                day = dates[0].clone();

            $('kronolithCurrent')
                .update(this.setViewTitle(date, view, data));

            // Remove old rows. Maybe we should only rebuild the calendars if
            // necessary.
            tbody.childElements().each(function(row) {
                if (row.identify() != 'kronolithRowTemplate') {
                    row.purge();
                    row.remove();
                }
            });

            // Build new calendar view.
            this.monthDays = {};
            while (!day.isAfter(dates[1])) {
                tbody.insert(this.createWeekRow(day, date.getMonth(), dates).show());
                day.next().week();
            }
            this.equalRowHeights(tbody);

            break;

        case 'year':
            var month;

            $('kronolithCurrent').update(this.setViewTitle(date, view, data));

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
                var dates = this.viewDates(date, view);
                $('kronolithCurrent')
                    .update(this.setViewTitle(date, view, data));
                $('kronolithSearchNavigation').up().up().hide();
            } else {
                $('kronolithCurrent')
                    .update(this.setViewTitle(date, view, data));
                $('kronolithSearchNavigation').up().up().show();
            }

            // Remove old rows. Maybe we should only rebuild the calendars if
            // necessary.
            tbody = $('kronolithViewAgendaBody').childElements().each(function(row) {
                if (row.identify() != 'kronolithAgendaTemplate' &&
                    row.identify() != 'kronolithAgendaNoItems') {
                    row.purge();
                    row.remove();
                }
            });

            break;
        }
    },

    /**
     * Sets the browser title of the calendar views.
     *
     * @param Date date    The date to show in the calendar.
     * @param string view  The view that's displayed.
     * @param mixed data   Any additional data that might be required.
     */
    setViewTitle: function(date, view, data)
    {
        switch (view) {
        case 'day':
            return this.setTitle(date.toString('D'));

        case 'week':
        case 'workweek':
            var dates = this.viewDates(date, view);
            return this.setTitle(dates[0].toString(Kronolith.conf.date_format) + ' - ' + dates[1].toString(Kronolith.conf.date_format));

        case 'month':
            return this.setTitle(date.toString('MMMM yyyy'));

        case 'year':
            return this.setTitle(date.toString('yyyy'));

        case 'agenda':
            var dates = this.viewDates(date, view);
            return this.setTitle(dates[0].toString(Kronolith.conf.date_format) + ' - ' + dates[1].toString(Kronolith.conf.date_format));

        case 'search':
            return this.setTitle(Kronolith.text.searching.interpolate({ term: data })).escapeHTML();
        }
    },

    /**
     * Closes the currently active view.
     */
    closeView: function(loc)
    {
        $w('Day Workweek Week Month Year Tasks Agenda').each(function(a) {
            a = $('kronolithNav' + a);
            if (a) {
                a.up().removeClassName('horde-active');
            }
        });
        if (this.view && this.view != loc) {
            $('kronolithView' + this.view.capitalize()).fade({
                duration: this.effectDur,
                queue: 'end'
            });
            this.view = null;
        }
    },

    /**
     * Creates a single row of day cells for usage in the month and multi-week
     * views.
     *
     * @param Date date        The first day to show in the row.
     * @param integer month    The current month. Days not from the current
     *                         month get the kronolith-other-month CSS class
     *                         assigned.
     * @param array viewDates  Array of Date objects with the start and end
     *                         dates of the view.
     *
     * @return Element  The element rendering a week row.
     */
    createWeekRow: function(date, month, viewDates)
    {
        var day = date.clone(), today = new Date().dateString(),
            row, cell, dateString;

        // Create a copy of the row template.
        row = $('kronolithRowTemplate').clone(true);
        row.removeAttribute('id');

        // Fill week number and day cells.
        cell = row.down()
            .setText(date.getRealWeek())
            .store('date', date.dateString())
            .next();
        while (cell) {
            dateString = day.dateString();
            this.monthDays['kronolithMonthDay' + dateString] = cell;
            cell.id = 'kronolithMonthDay' + dateString;
            cell.store('date', dateString);
            cell.removeClassName('kronolith-other-month').removeClassName('kronolith-today');
            if (day.getMonth() != month) {
                cell.addClassName('kronolith-other-month');
            }
            if (dateString == today) {
                cell.addClassName('kronolith-today');
            }
            new Drop(cell);
            cell.store('date', dateString)
                .down('.kronolith-day')
                .store('date', dateString)
                .update(day.getDate());

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
            row = $('kronolithAgendaTemplate').clone(true);

        // Fill week number and day cells.
        row.store('date', date)
            .down()
            .setText(this.parseDate(date).toString('D'))
            .next()
            .writeAttribute('id', 'kronolithAgendaDay' + date);
        row.removeAttribute('id');

        // Insert row.
        var nextRow;
        body.childElements().each(function(elm) {
            if (elm.retrieve('date') > date) {
                nextRow = elm;
                throw $break;
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
        var table = $('kronolithYearTemplate').clone(true),
            tbody = table.down('tbody');
        table.removeAttribute('id');
        tbody.writeAttribute('id', 'kronolithYearTable' + month);

        // Set month name.
        table.down('tr.kronolith-minical-nav th')
            .store('date', year.toPaddedString(4) + (month + 1).toPaddedString(2) + '01')
            .update(Date.CultureInfo.monthNames[month]);

        // Build month table.
        this.buildMinical(tbody, new Date(year, month, 1), null, idPrefix, year);

        return table;
    },

    equalRowHeights: function(tbody)
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
    calculateRowSizes: function(storage, view)
    {
        if (!Object.isUndefined(this[storage])) {
            return;
        }

        var td = $(view).down('.kronolithViewBody tr td').next('td'),
            layout = td.getLayout(),
            spacing = td.up('table').getStyle('borderSpacing');

        // FIXME: spacing is hardcoded for IE 7 because it doesn't know about
        // border-spacing, but still uses it. WTF?
        spacing = spacing ? parseInt($w(spacing)[1], 10) : 2;
        this[storage] = {};
        this[storage].height = layout.get('margin-box-height') + spacing;
        this[storage].spacing = this[storage].height - layout.get('padding-box-height') - layout.get('border-bottom');
    },

    /**
     * Adds a horizontal ruler representing the current time to the specified
     * container.
     *
     * @param string|Element  The container of the current day.
     */
    addTimeMarker: function(container)
    {
        if ($('kronolithTimeMarker')) {
            $('kronolithTimeMarker').remove();
            this.timeMarker.stop();
        }
        $(container).insert(new Element('div', { id: 'kronolithTimeMarker' }).setStyle({ position: 'absolute' }).hide());
        this.timeMarker = new PeriodicalExecuter(this.positionTimeMarker.bind(this), 60);
    },

    /**
     * Updates the horizontal ruler representing the current time.
     */
    positionTimeMarker: function()
    {
        var today = Date.today(), now;

        switch (this.view) {
        case 'day':
            if (!this.date.equals(today)) {
                $('kronolithTimeMarker').remove();
                this.timeMarker.stop();
                return;
            }
            break;
        case 'week':
        case 'workweek':
            if ($('kronolithTimeMarker').up().retrieve('date') != today.dateString()) {
                var newContainer = this.eventsWeek['kronolithEvents' + (this.view == 'week' ? 'Week' : 'Workweek') + today.dateString()];
                $('kronolithTimeMarker').remove();
                if (newContainer) {
                    this.addTimeMarker(newContainer);
                } else {
                    this.timeMarker.stop();
                }
                return;
            }
            break;
        default:
            $('kronolithTimeMarker').remove();
            this.timeMarker.stop();
            return;
        }

        now = new Date();
        $('kronolithTimeMarker').setStyle({ top: ((now.getHours() * 60 + now.getMinutes()) * this[this.view + 'Sizes'].height / 60 | 0) + 'px' });
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
        $('kronolithMinicalDate')
            .store('date', date.dateString())
            .update(date.toString('MMMM yyyy'));

        this.buildMinical($('kronolith-minical').down('tbody'), date, view);
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
     * @param integer year     If present, generating mini calendars for the
     *                         year view of this year.
     */
    buildMinical: function(tbody, date, view, idPrefix, year)
    {
        var dates = this.viewDates(date, 'month'),
            day = dates[0].clone(),
            date7 = date.clone().add(1).week(),
            today = Date.today(),
            week = this.viewDates(this.date, 'week'),
            workweek = this.viewDates(this.date, 'workweek'),
            dateString, td, tr, i;

        // Remove old calendar rows. Maybe we should only rebuild the minical
        // if necessary.
        tbody.childElements().invoke('remove');

        for (i = 0; i < 42; i++) {
            dateString = day.dateString();
            // Create calendar row and insert week number.
            if (day.getDay() == Kronolith.conf.week_start) {
                tr = new Element('tr');
                tbody.insert(tr);
                td = new Element('td', { className: 'kronolith-minical-week' })
                    .store('weekdate', dateString);
                td.update(day.getRealWeek());
                tr.insert(td);
                weekStart = day.clone();
                weekEnd = day.clone();
                weekEnd.add(6).days();
            }

            // Insert day cell.
            td = new Element('td').store('date', dateString);
            if (day.getMonth() != date.getMonth()) {
                td.addClassName('kronolith-other-month');
            } else if (!Object.isUndefined(idPrefix)) {
                td.id = idPrefix + dateString;
            }

            // Highlight days currently being displayed.
            if (view &&
                ((view == 'month' && this.date.between(dates[0], dates[1])) ||
                 (view == 'week' && day.between(week[0], week[1])) ||
                 (view == 'workweek' && day.between(workweek[0], workweek[1])) ||
                 (view == 'day' && day.equals(this.date)) ||
                 (view == 'agenda' && !day.isBefore(date) && day.isBefore(date7)))) {
                td.addClassName('kronolith-selected');
            }

            // Highlight today.
            if (day.equals(today) &&
                (Object.isUndefined(year) ||
                 (day.getYear() + 1900 == year &&
                  date.getMonth() == day.getMonth()))) {
                td.addClassName('kronolith-today');
            }
            td.insert(new Element('a').update(day.getDate()));
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
        var noItems, calendar, link;
        if (!div) {
            div = this.getCalendarList(type, cal.owner);
        }
        noItems = div.previous();
        if (noItems &&
            noItems.tagName == 'DIV' &&
            noItems.className == 'horde-info') {
            noItems.hide();
        }
        link = new Element('span', { className: type != 'resourcegroup' ? (cal.show ? 'horde-resource-on' : 'horde-resource-off') : 'horde-resource-none' })
            .insert(cal.name.escapeHTML());
        calendar = new Element('div')
            .store('calendar', id)
            .store('calendarclass', type)
            .setStyle({ backgroundColor: cal.bg, color: cal.fg });
        if (type != 'holiday' && type != 'external') {
            calendar.insert(
                new Element('span', { className: 'horde-resource-edit-' + cal.fg.substring(1) })
                    .setStyle({ backgroundColor: cal.bg, color: cal.fg })
                    .insert('&#9658;'));
        }
        calendar.insert(
            new Element('div', { className: 'horde-resource-link' })
                .insert(link));
        this.addShareIcon(cal, link);
        div.insert(calendar);
        if (cal.show) {
            this.addCalendarLegend(type, id, cal);
        }
    },

    /**
     * Add the share icon after the calendar name in the calendar list.
     *
     * @param object cal       A calendar object from Kronolith.conf.calendars.
     * @param Element element  The calendar element in the list.
     */
    addShareIcon: function(cal, element)
    {
        if (cal.owner && cal.perms) {
            $H(cal.perms).each(function(perm) {
                if (perm.key != 'type' &&
                    ((Object.isArray(perm.value) && perm.value.size()) ||
                     (!Object.isArray(perm.value) && perm.value))) {
                    element.insert(' ').insert(new Element('img', { src: Kronolith.conf.images.attendees.replace(/fff/, cal.fg.substring(1)), title: Kronolith.text.shared }));
                    throw $break;
                }
            });
        }
    },

    /**
     * Rebuilds the list of calendars.
     */
    updateCalendarList: function()
    {
        var ext = $H(), extNames = $H(),
            extContainer = $('kronolithExternalCalendars');

        $H(Kronolith.conf.calendars.internal).each(function(cal) {
            this.insertCalendarInList('internal', cal.key, cal.value);
        }, this);

        if (Kronolith.conf.tasks) {
            $H(Kronolith.conf.calendars.tasklists).each(function(cal) {
                this.insertCalendarInList('tasklists', cal.key, cal.value);
            }, this);
        }

        if (Kronolith.conf.calendars.resource) {
            $H(Kronolith.conf.calendars.resource).each(function(cal) {
               this.insertCalendarInList('resource', cal.key, cal.value);
            }, this);
        }

        if (Kronolith.conf.calendars.resourcegroup) {
            $H(Kronolith.conf.calendars.resourcegroup).each(function(cal) {
                this.insertCalendarInList('resourcegroup', cal.key, cal.value);
            }, this);
        }

        $H(Kronolith.conf.calendars.external).each(function(cal) {
            var parts = cal.key.split('/'), api = parts.shift();
            if (!ext.get(api)) {
                ext.set(api, $H());
            }
            ext.get(api).set(parts.join('/'), cal.value);
            extNames.set(api, cal.value.api ? cal.value.api : Kronolith.text.external_category);
        });
        ext.each(function(api) {
            extContainer
                .insert(new Element('div', { className: 'horde-sidebar-split' }))
                .insert(new Element('div')
                        .insert(new Element('h3')
                                .insert(new Element('span', { className: 'horde-expand', title: HordeSidebar.text.expand })
                                        .insert({ bottom: extNames.get(api.key).escapeHTML() })))
                        .insert(new Element('div', { id: 'kronolithExternalCalendar' + api.key, className: 'horde-resources', style: 'display:none' })));
            api.value.each(function(cal) {
                this.insertCalendarInList('external', api.key + '/' + cal.key, cal.value, $('kronolithExternalCalendar' + api.key));
            }, this);
        }, this);

        $H(Kronolith.conf.calendars.remote).each(function(cal) {
            this.insertCalendarInList('remote', cal.key, cal.value);
        }, this);

        if (Kronolith.conf.calendars.holiday) {
            $H(Kronolith.conf.calendars.holiday).each(function(cal) {
                if (cal.value.show) {
                   this.insertCalendarInList('holiday', cal.key, cal.value);
                }
            }, this);
        } else {
            $('kronolithAddholiday').up().hide();
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
        case 'resource':
            return $('kronolithResourceCalendars');
        case 'resourcegroup':
            return $('kronolithResourceGroups');
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
     * Loads a certain calendar, if the current view is still a calendar view.
     *
     * @param string type      The calendar type.
     * @param string calendar  The calendar id.
     */
    loadCalendar: function(type, calendar)
    {
        if (Kronolith.conf.calendars[type][calendar].show &&
            $w('day workweek week month year agenda').include(this.view)) {
            var dates = this.viewDates(this.date, this.view);
            this.deleteCache([type, calendar]);
            this.loadEvents(dates[0], dates[1], this.view, [[type, calendar]]);
        }
    },

    /**
     * Toggles a calendars visibility.
     *
     * @param string type      The calendar type.
     * @param string calendar  The calendar id.
     */
    toggleCalendar: function(type, calendar)
    {
        var elt = $('kronolithMenuCalendars').select('div').find(function(div) {
            return div.retrieve('calendarclass') == type &&
            div.retrieve('calendar') == calendar;
        }).down('.horde-resource-link').down('span');

        Kronolith.conf.calendars[type][calendar].show = !Kronolith.conf.calendars[type][calendar].show;
        elt.toggleClassName('horde-resource-on');
        elt.toggleClassName('horde-resource-off');

        if (Kronolith.conf.calendars[type][calendar].show) {
            this.addCalendarLegend(type, calendar, Kronolith.conf.calendars[type][calendar]);
        } else {
            this.deleteCalendarLegend(type, calendar);
        }

        switch (this.view) {
        case 'month':
        case 'agenda':
            if (Object.isUndefined(this.ecache.get(type)) ||
                Object.isUndefined(this.ecache.get(type).get(calendar))) {
                this.loadCalendar(type, calendar);
            } else {
                var allEvents = this.kronolithBody.select('div').findAll(function(el) {
                    return el.retrieve('calendar') == type + '|' + calendar;
                });
                if (this.view == 'month' && Kronolith.conf.max_events) {
                    var dates = this.viewDates(this.date, this.view);
                    if (elt.hasClassName('horde-resource-off')) {
                        var day, more, events, calendars = [];
                        $H(Kronolith.conf.calendars).each(function(type) {
                            $H(type.value).each(function(cal) {
                                if (cal.value.show) {
                                    calendars.push(type.key + '|' + cal.key);
                                }
                            });
                        });
                        allEvents.each(function(el) {
                            if (el.retrieve('calendar').startsWith('holiday|')) {
                                this.holidays = this.holidays.without(el.retrieve('eventid'));
                            }
                            el.remove();
                        }, this);
                        for (var date = dates[0]; !date.isAfter(dates[1]); date.add(1).days()) {
                            day = this.monthDays['kronolithMonthDay' + date.dateString()];
                            more = day.select('.kronolithMore');
                            events = day.select('.kronolith-event');
                            if (more.size() &&
                                events.size() < Kronolith.conf.max_events) {
                                more[0].purge();
                                more[0].remove();
                                events.invoke('remove');
                                calendars.each(function(calendar) {
                                    this.insertEvents([date, date], 'month', calendar);
                                }, this);
                            }
                        }
                    } else {
                        this.insertEvents(dates, 'month', type + '|' + calendar);
                    }
                } else {
                    allEvents.invoke('toggle');
                }
            }
            break;

        case 'year':
        case 'week':
        case 'workweek':
        case 'day':
            if (Object.isUndefined(this.ecache.get(type)) ||
                Object.isUndefined(this.ecache.get(type).get(calendar))) {
                this.loadCalendar(type, calendar);
            } else {
                this.insertEvents(this.viewDates(this.date, this.view), this.view);
            }
            break;

        case 'tasks':
            if (type != 'tasklists') {
                break;
            }
            var tasklist = calendar.substr(6);
            if (elt.hasClassName('horde-resource-off')) {
                $('kronolithViewTasksBody').select('tr').findAll(function(el) {
                    return el.retrieve('tasklist') == tasklist;
                }).invoke('remove');
            } else {
                this.loadTasks(this.tasktype, [ tasklist ]);
            }
            break;
        }

        if ($w('tasklists remote external holiday resource').include(type)) {
            calendar = type + '_' + calendar;
        }
        HordeCore.doAction('saveCalPref', { toggle_calendar: calendar });
    },

    /**
     * Propagates a SELECT drop down list with the editable calendars.
     *
     * @param string id  The id of the SELECT element.
     */
    updateCalendarDropDown: function(id)
    {
        $(id).update();
        ['internal', 'remote'].each(function(type) {
            $H(Kronolith.conf.calendars[type]).each(function(cal) {
                if (cal.value.edit) {
                    $(id).insert(new Element('option', { value: type + '|' + cal.key })
                                 .setStyle({ backgroundColor: cal.value.bg, color: cal.value.fg })
                                 .update(cal.value.name.escapeHTML()));
                }
            });
        });
    },

    /**
     * Adds a calendar entry to the print legend.
     *
     * @param string type  The calendar type.
     * @param string id    The calendar id.
     * @param object cal   The calendar object.
     */
    addCalendarLegend: function(type, id, cal)
    {
        $('kronolith-legend').insert(
            new Element('span')
                .insert(cal.name.escapeHTML())
                .store('calendar', id)
                .store('calendarclass', type)
                .setStyle({ backgroundColor: cal.bg, color: cal.fg })
        );
    },

    /**
     * Deletes a calendar entry from the print legend.
     *
     * @param string type  The calendar type.
     * @param string id    The calendar id.
     */
    deleteCalendarLegend: function(type, id)
    {
        var legend = $('kronolith-legend').select('span').find(function(span) {
            return span.retrieve('calendarclass') == type &&
                span.retrieve('calendar') == id;
        });
        if (legend) {
            legend.remove();
        }
    },

    /**
     * Opens a tab in a form.
     *
     * @param Element  The A element of a tab.
     */
    openTab: function(elt)
    {
        var dialog = elt.up('form'), tab = $(elt.id.replace(/Link/, 'Tab')),
            field;
        dialog.select('.kronolithTabsOption').invoke('hide');
        dialog.select('.tabset li').invoke('removeClassName', 'horde-active');
        tab.show();
        elt.up().addClassName('horde-active');
        if (elt.id == 'kronolithEventLinkMap') {
            if (!this.mapInitialized) {
                this.initializeMap();
            }
        }
        field = tab.down('textarea');
        if (!field) {
            field = tab.down('input');
        }
        if (field) {
            try {
                field.focus();
            } catch (e) {}
        }
        switch (tab.identify()) {
        case 'kronolithEventTabAttendees':
            this.attendeeStartDateHandler(this.getFBDate());
            break;
        case 'kronolithEventTabResources':
            this.resourceStartDateHandler(this.getFBDate());
            break;
        }
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
    loadEvents: function(firstDay, lastDay, view, calendars)
    {
        var loading = false;

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
                cals = this.ecache.get(cal[0]);

            if (typeof cals != 'undefined' &&
                typeof cals.get(cal[1]) != 'undefined') {
                cals = cals.get(cal[1]);
                while (!Object.isUndefined(cals.get(startDay.dateString())) &&
                       startDay.isBefore(endDay)) {
                    if (view != 'year') {
                        this.insertEvents([startDay, startDay], view, cal.join('|'));
                    }
                    startDay.add(1).day();
                }
                while (!Object.isUndefined(cals.get(endDay.dateString())) &&
                       (!startDay.isAfter(endDay))) {
                    if (view != 'year') {
                        this.insertEvents([endDay, endDay], view, cal.join('|'));
                    }
                    endDay.add(-1).day();
                }
                if (startDay.compareTo(endDay) > 0) {
                    return;
                }
            }
            var start = startDay.dateString(), end = endDay.dateString(),
                calendar = cal.join('|');
            loading = true;
            this.startLoading(calendar, start + end);
            this.storeCache($H(), calendar, null, true);

            HordeCore.doAction('listEvents', {
                start: start,
                end: end,
                cal: calendar,
                sig: start + end,
                view: view
            }, {
                callback: function(r) {
                    this.loadEventsCallback(r, true);
                }.bind(this)
            });
        }, this);

        if (!loading && view == 'year') {
            this.insertEvents([firstDay, lastDay], 'year');
        }
    },

    /**
     * Callback method for inserting events in the current view.
     *
     * @param object r             The ajax response object.
     * @param boolean createCache  Whether to create a cache list entry for the
     *                             response, if none exists yet. Useful for
     *                             (not) adding individual events to the cache
     *                             if it doesn't match any cached views.
     */
    loadEventsCallback: function(r, createCache)
    {
        // Hide spinner.
        this.loading--;
        if (!this.loading) {
            $('kronolithLoading').hide();
        }

        var start = this.parseDate(r.sig.substr(0, 8)),
            end = this.parseDate(r.sig.substr(8, 8)),
            dates = [start, end],
            currentDates;

        this.storeCache(r.events || {}, r.cal, dates, createCache);

        // Check if this is the still the result of the most current request.
        if (r.sig != this.eventsLoading[r.cal]) {
            return;
        }
        delete this.eventsLoading[r.cal];

        // Check if the calendar is still visible.
        var calendar = r.cal.split('|');
        if (!Kronolith.conf.calendars[calendar[0]][calendar[1]].show) {
            return;
        }

        // Check if the result is still for the current view.
        currentDates = this.viewDates(this.date, this.view);
        if (r.view != this.view ||
            !start.between(currentDates[0], currentDates[1])) {

            return;
        }

        if (this.view == 'day' ||
            this.view == 'week' ||
            this.view == 'workweek' ||
            this.view == 'month' ||
            this.view == 'agenda' ||
            (this.view == 'year' && !$H(this.eventsLoading).size())) {
            this.insertEvents(dates, this.view, r.cal);
        }
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
    insertEvents: function(dates, view, calendar)
    {
        switch (view) {
        case 'day':
        case 'week':
        case 'workweek':
            // The day and week views require the view to be completely
            // loaded, to correctly calculate the dimensions.
            if (this.viewLoading.size() || this.view != view) {
                this.insertEvents.bind(this, [dates[0].clone(), dates[1].clone()], view, calendar).defer();
                return;
            }
            break;
        }

        var day = dates[0].clone(),
                  viewDates = this.viewDates(this.date, this.view),
                  date, more, title, titles, events, monthDay, busyHours;
        while (!day.isAfter(dates[1])) {
            // Skip if somehow events slipped in though the view is gone.
            if (!day.between(viewDates[0], viewDates[1])) {
                if (window.console) {
                    window.console.trace();
                }
                day.next().day();
                continue;
            }

            date = day.dateString();
            switch (view) {
            case 'day':
            case 'week':
            case 'workweek':
                this.dayEvents = [];
                this.dayGroups = [];
                this.allDayEvents = [];
                if (view == 'day') {
                    $$('.kronolith-event').invoke('remove');
                } else {
                    this.eventsWeek['kronolithEvents' + (view == 'week' ? 'Week' : 'Workweek') + date]
                        .select('.kronolith-event')
                        .invoke('remove');
                    this.allDays['kronolithAllDay' + date]
                        .childElements()
                        .invoke('remove');
                }
                break;

            case 'month':
                monthDay = this.monthDays['kronolithMonthDay' + date];
                monthDay.select('div')
                    .findAll(function(el) { return el.retrieve('calendar') == calendar; })
                    .invoke('remove');
                break;

            case 'year':
                titles = [];
                busyHours = 0;
            }

            if (view == 'month' || view == 'agenda') {
                events = this.getCacheForDate(date, calendar);
            } else {
                events = this.getCacheForDate(date);
            }
            events.sortBy(this.sortEvents).each(function(event) {
                var insertBefore;
                switch (view) {
                case 'month':
                case 'agenda':
                    if (calendar.startsWith('holiday|')) {
                        if (this.holidays.include(event.key)) {
                            return;
                        }
                        this.holidays.push(event.key);
                    }
                    if (view == 'month' && Kronolith.conf.max_events) {
                        more = monthDay.down('.kronolithMore');
                        if (more) {
                            more.purge();
                            more.remove();
                        }
                    }
                    if (view == 'month') {
                        if (Kronolith.conf.max_events) {
                        var events = monthDay.select('.kronolith-event');
                        if (events.size() >= Kronolith.conf.max_events) {
                            if (date == (new Date().dateString())) {
                                // This is today.
                                if (event.value.al || event.value.end.isBefore()) {
                                    // No room for all-day or finished events.
                                    this.insertMore(date);
                                    return;
                                }
                                var remove, max;
                                // Find an event that is earlier than now or
                                // later then the current event.
                                events.each(function(elm) {
                                    var calendar = elm.retrieve('calendar').split('|'),
                                        event = this.ecache.get(calendar[0]).get(calendar[1]).get(date).get(elm.retrieve('eventid'));
                                    if (event.start.isBefore()) {
                                        remove = elm;
                                        throw $break;
                                    }
                                    if (!max || event.start.isAfter(max)) {
                                        max = event.start;
                                        remove = elm;
                                    }
                                }, this);
                                if (remove) {
                                    remove.purge();
                                    remove.remove();
                                    insertBefore = this.findInsertBefore(events.without(remove), event, date);
                                } else {
                                    this.insertMore(date);
                                    return;
                                }
                            } else {
                                // Not today.
                                var allDays = events.findAll(function(elm) {
                                    var calendar = elm.retrieve('calendar').split('|');
                                    return this.ecache.get(calendar[0]).get(calendar[1]).get(date).get(elm.retrieve('eventid')).al;
                                }.bind(this));
                                if (event.value.al) {
                                    // We want one all-day event.
                                    if (allDays.size()) {
                                        // There already is an all-day event.
                                        if (event.value.x == Kronolith.conf.status.confirmed ||
                                            event.value.x == Kronolith.conf.status.tentative) {
                                            // But is there a less important
                                            // one?
                                            var status = [Kronolith.conf.status.free, Kronolith.conf.status.cancelled];
                                            if (event.value.x == Kronolith.conf.status.confirmed) {
                                                status.push(Kronolith.conf.status.tentative);
                                            }
                                            var free = allDays.detect(function(elm) {
                                                var calendar = elm.retrieve('calendar').split('|');
                                                return status.include(this.ecache.get(calendar[0]).get(calendar[1]).get(date).get(elm.retrieve('eventid')).x);
                                            }.bind(this));
                                            if (!free) {
                                                this.insertMore(date);
                                                return;
                                            }
                                            insertBefore = free.next();
                                            free.purge();
                                            free.remove();
                                        } else {
                                            // No.
                                            this.insertMore(date);
                                            return;
                                        }
                                    } else {
                                        // Remove the last event to make room
                                        // for this one.
                                        var elm = events.pop();
                                        elm.purge();
                                        elm.remove();
                                        insertBefore = events.first();
                                    }
                                } else {
                                    if (allDays.size() > 1) {
                                        // We don't want more than one all-day
                                        // event.
                                        var elm = allDays.pop();
                                        // Remove element from events as well.
                                        events = events.without(elm);
                                        elm.purge();
                                        elm.remove();
                                        insertBefore = this.findInsertBefore(events, event, date);
                                    } else {
                                        // This day is full.
                                        this.insertMore(date);
                                        return;
                                    }
                                }
                            }
                            this.insertMore(date);
                        } else {
                            insertBefore = this.findInsertBefore(events, event, date);
                        }
                        } else {
                            var events = monthDay.select('.kronolith-event');
                            insertBefore = this.findInsertBefore(events, event, date);
                        }
                    }
                    break;

                case 'year':
                    title = '';
                    if (event.value.al) {
                        title += Kronolith.text.allday;
                    } else {
                        title += event.value.start.toString('t') + '-' + event.value.end.toString('t');
                    }
                    if (event.value.t) {
                        title += ': ' + event.value.t.escapeHTML();
                    }
                    if (event.value.x == Kronolith.conf.status.tentative ||
                        event.value.x == Kronolith.conf.status.confirmed) {
                        busyHours += event.value.start.getElapsed(event.value.end) / 3600000;
                    }
                    titles.push(title);
                    return;
                }
                this.insertEvent(event, date, view, insertBefore);
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
                var td = $('kronolithYear' + date);
                if (td.className == 'kronolith-minical-empty') {
                    continue;
                }
                if (td.hasClassName('kronolith-today')) {
                    td.className = 'kronolith-today';
                } else {
                    td.className = '';
                }
                if (titles.length) {
                    td.addClassName('kronolithHasEvents');
                    if (busyHours > 0) {
                        td.addClassName(this.getHeatmapClass(busyHours));
                        busyHours = 0;
                    }
                    td.down('a').writeAttribute('nicetitle', Object.toJSON(titles));
                }
            }

            day.next().day();
        }
        // Workaround Firebug bug.
        Prototype.emptyFunction();
    },

    findInsertBefore: function(events, event, date)
    {
        var insertBefore, insertSort;
        events.each(function(elm) {
            var calendar = elm.retrieve('calendar').split('|'),
                existing = this.ecache
                    .get(calendar[0])
                    .get(calendar[1])
                    .get(date)
                    .get(elm.retrieve('eventid'));
            if (event.value.sort < existing.sort &&
                (!insertSort || existing.sort < insertSort)) {
                insertBefore = elm;
                insertSort = existing.sort;
            }
        }, this);
        return insertBefore;
    },

    getHeatmapClass: function(hours)
    {
        return 'heat' + Math.min(Math.ceil(hours / 2), 6);
    },

    /**
     * Creates the DOM node for an event bubble and inserts it into the view.
     *
     * @param object event    A Hash member with the event to insert.
     * @param string date     The day to update.
     * @param string view     The view to update.
     * @param Element before  Insert the event before this element (month view).
     */
    insertEvent: function(event, date, view, before)
    {
        var calendar = event.value.calendar.split('|');
        event.value.nodeId = ('kronolithEvent' + view + event.value.calendar + date + event.key).replace(new RegExp('[^a-zA-Z0-9]', 'g'), '');

        var _createElement = function(event) {
            var className ='kronolith-event';
            switch (event.value.x) {
            case 3:
                className += ' kronolith-event-cancelled';
                break;
            case 1:
            case 4:
                className += ' kronolith-event-tentative';
                break;
            }
            var el = new Element('div', { id: event.value.nodeId, className: className })
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
        case 'workweek':
            var storage = view + 'Sizes',
                what = view == 'week' ? 'Week' : 'Workweek',
                div = _createElement(event),
                margin = view == 'day' ? 1 : 3,
                style = { backgroundColor: Kronolith.conf.calendars[calendar[0]][calendar[1]].bg,
                          color: Kronolith.conf.calendars[calendar[0]][calendar[1]].fg };

            div.writeAttribute('title', event.value.t);

            if (event.value.al) {
                if (view == 'day') {
                    $('kronolithViewDay').down('.kronolithAllDayContainer').insert(div.setStyle(style));
                } else {
                    var allDay = this.allDays['kronolithAllDay' + date],
                        existing = allDay.childElements(),
                        weekHead = $('kronolithView' + what + 'Head');
                    if (existing.size() == 3) {
                        if (existing[2].className != 'kronolithMore') {
                            existing[2].purge();
                            existing[2].remove();
                            allDay.insert({ bottom: new Element('span', { className: 'kronolithMore' }).store('date', date).insert(Kronolith.text.more) });
                        }
                    } else {
                        allDay.insert(div.setStyle(style));
                        if (event.value.pe) {
                            div.addClassName('kronolithEditable');
                            var layout = div.getLayout(),
                                minLeft = weekHead.down('.kronolith-first-col').getWidth() + this[storage].spacing + (parseInt(div.getStyle('marginLeft'), 10) || 0),
                                minTop = weekHead.down('thead').getHeight() + this[storage].spacing + (parseInt(div.getStyle('marginTop'), 10) || 0),
                                maxLeft = weekHead.getWidth() - layout.get('margin-box-width'),
                                maxTop = weekHead.down('thead').getHeight() + weekHead.down('.kronolith-all-day').getHeight(),
                                opts = {
                                    threshold: 5,
                                    parentElement: function() {
                                        return $('kronolithView' + what).down('.kronolith-view-head');
                                    },
                                    snap: function(x, y) {
                                        return [Math.min(Math.max(x, minLeft), maxLeft),
                                                Math.min(Math.max(y, minTop), maxTop - div.getHeight())];
                                    }
                                };
                            var d = new Drag(event.value.nodeId, opts);
                            div.store('drags', []);
                            Object.extend(d, {
                                event: event,
                                innerDiv: new Element('div'),
                                midnight: this.parseDate(date)
                            });
                            div.retrieve('drags').push(d);
                        }
                    }
                }
                break;
            }

            var midnight = this.parseDate(date),
                resizable = event.value.pe && (Object.isUndefined(event.value.vl) || event.value.vl),
                innerDiv = new Element('div', { className: 'kronolith-event-info' }),
                minHeight = 0, parentElement, draggerTop, draggerBottom,
                elapsed = (event.value.start.getHours() - midnight.getHours()) * 60 + (event.value.start.getMinutes() - midnight.getMinutes());
            switch (view) {
            case 'day':
                parentElement = $('kronolithEventsDay');
                break;
            case 'week':
                parentElement = this.eventsWeek['kronolithEventsWeek' + date];
                break;
            case 'workweek':
                parentElement = this.eventsWeek['kronolithEventsWorkweek' + date];
                break;
            }
            if (event.value.fi) {
                div.addClassName('kronolithFirst');
                if (resizable) {
                    draggerTop = new Element('div', { id: event.value.nodeId + 'top', className: 'kronolithDragger kronolithDraggerTop' }).setStyle(style);
                }
            } else {
                innerDiv.setStyle({ top: 0 });
            }
            if (event.value.la) {
                div.addClassName('kronolithLast');
                if (resizable) {
                    draggerBottom = new Element('div', { id: event.value.nodeId + 'bottom', className: 'kronolithDragger kronolithDraggerBottom' }).setStyle(style);
                }
            } else {
                innerDiv.setStyle({ bottom: 0 });
            }

            div.setStyle({
                top: (elapsed * this[storage].height / 60 | 0) + 'px',
                width: 100 - margin + '%'
            })
                .insert(innerDiv.setStyle(style));
            if (draggerTop) {
                div.insert(draggerTop);
            }
            if (draggerBottom) {
                div.insert(draggerBottom);
            }
            parentElement.insert(div);
            if (draggerTop) {
                minHeight += draggerTop.getHeight();
            }
            if (draggerBottom) {
                minHeight += draggerBottom.getHeight();
            }
            if (!minHeight) {
                minHeight = parseInt(innerDiv.getStyle('lineHeight'), 10)
                    + (parseInt(innerDiv.getStyle('paddingTop'), 10) || 0)
                    + (parseInt(innerDiv.getStyle('paddingBottom'), 10) || 0);
            }
            div.setStyle({ height: Math.max(Math.round(event.value.start.getElapsed(event.value.end) / 60000) * this[storage].height / 60 - this[storage].spacing | 0, minHeight) + 'px' });

            if (event.value.pe) {
                div.addClassName('kronolithEditable');
                div.store('drags', []);
                // Number of pixels that cover 10 minutes.
                var step = this[storage].height / 6,
                    stepX, minLeft, maxLeft, maxTop,
                    minBottom, maxBottom, dragBottomHeight;
                if (draggerBottom) {
                    // Height of bottom dragger
                    dragBottomHeight = draggerBottom.getHeight();
                }
                if (draggerTop) {
                    // Bottom-most position (maximum y) of top dragger
                    maxTop = div.offsetTop
                        - draggerTop.getHeight()
                        - parseInt(innerDiv.getStyle('lineHeight'), 10);
                    if (draggerBottom) {
                        maxTop += draggerBottom.offsetTop;
                    }
                }
                if (draggerBottom) {
                    // Top-most position (minimum y) of bottom dragger (upper
                    // edge)
                    minBottom = div.offsetTop
                        + parseInt(innerDiv.getStyle('lineHeight'), 10);
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
                        scroll: this.kronolithBody,
                        nodrop: true,
                        parentElement: function() {
                            return parentElement;
                        }
                    };

                if (draggerTop) {
                    opts.snap = function(x, y) {
                        y = Math.max(0, step * (Math.min(maxTop, y - this.scrollTop) / step | 0));
                        return [0, y];
                    }.bind(this);
                    var d = new Drag(event.value.nodeId + 'top', opts);
                    Object.extend(d, {
                        event: event,
                        innerDiv: innerDiv,
                        midnight: midnight
                    });
                    div.retrieve('drags').push(d);
                }

                if (draggerBottom) {
                    opts.snap = function(x, y) {
                        y = Math.min(maxBottom + dragBottomHeight + KronolithCore[storage].spacing, step * ((Math.max(minBottom, y - this.scrollTop) + dragBottomHeight + KronolithCore[storage].spacing) / step | 0)) - dragBottomHeight - KronolithCore[storage].spacing;
                        return [0, y];
                    }.bind(this);
                    var d = new Drag(event.value.nodeId + 'bottom', opts);
                    Object.extend(d, {
                        event: event,
                        innerDiv: innerDiv,
                        midnight: midnight
                    });
                    div.retrieve('drags').push(d);
                }

                if (view == 'week' || view == 'workweek') {
                    var dates = this.viewDates(midnight, view);
                    minLeft = this.eventsWeek['kronolithEvents' + what + dates[0].dateString()].offsetLeft - this.eventsWeek['kronolithEvents' + what + date].offsetLeft;
                    maxLeft = this.eventsWeek['kronolithEvents' + what + dates[1].dateString()].offsetLeft - this.eventsWeek['kronolithEvents' + what + date].offsetLeft;
                    stepX = (maxLeft - minLeft) / (view == 'week' ? 6 : 4);
                }
                var d = new Drag(div, {
                    threshold: 5,
                    nodrop: true,
                    parentElement: function() { return parentElement; },
                    snap: function(x, y) {
                        x = (view == 'week' || view == 'workweek')
                            ? Math.max(minLeft, stepX * ((Math.min(maxLeft, x - (x < 0 ? stepX : 0)) + stepX / 2) / stepX | 0))
                            : 0;
                        y = Math.max(0, step * (Math.min(maxDiv, y - this.scrollTop) / step | 0));
                        return [x, y];
                    }.bind(this)
                });
                Object.extend(d, {
                    divHeight: divHeight,
                    startTop: div.offsetTop,
                    event: event,
                    midnight: midnight,
                    stepX: stepX
                });
                div.retrieve('drags').push(d);
            }

            var
                // The current column that we're probing for available space.
                column = 1,
                // The number of columns in the current conflict group.
                columns,
                // The column width in the current conflict group.
                width,
                // The first event that conflict with the current event.
                conflict = false,
                // The conflict group where this event should go.
                pos = this.dayGroups.length,
                // The event below that the current event fits.
                placeFound = false,
                // The minimum (virtual) duration of each event, defined by the
                // minimum height of an event DIV.
                minMinutes = (minHeight + this[storage].spacing) * 60 / this[storage].height;

            // this.dayEvents contains all events of the current day.
            // this.dayGroups contains conflict groups, i.e. all events that
            // conflict with each other and share a set of columns.
            //
            // Go through all events that have been added to this day already.
            this.dayEvents.each(function(ev) {
                // Due to the minimum height of an event DIV, events might
                // visually overlap, even if they physically don't.
                var minEnd = ev.start.clone().add(minMinutes).minutes(),
                    end = ev.end.isAfter(minEnd) ? ev.end : minEnd;

                // If it doesn't conflict with the current event, go ahead.
                if (!end.isAfter(event.value.start)) {
                    return;
                }

                // Found a conflicting event, now find its conflict group.
                for (pos = 0; pos < this.dayGroups.length; pos++) {
                    if (this.dayGroups[pos].indexOf(ev) != -1) {
                        // Increase column for each conflicting event in this
                        // group.
                        this.dayGroups[pos].each(function(ce) {
                            var minEnd = ce.start.clone().add(minMinutes).minutes(),
                                end = ce.end.isAfter(minEnd) ? ce.end : minEnd;
                            if (end.isAfter(event.value.start)) {
                                column++;
                            }
                        });
                        throw $break;
                    }
                }
            }, this);
            event.value.column = event.value.columns = column;

            if (Object.isUndefined(this.dayGroups[pos])) {
                this.dayGroups[pos] = [];
            }
            this.dayGroups[pos].push(event.value);

            // See if the current event had to add yet another column.
            columns = Math.max(this.dayGroups[pos][0].columns, column);

            // Update the widths of all events in a conflict group.
            width = 100 / columns;
            this.dayGroups[pos].each(function(ev) {
                ev.columns = columns;
                $(ev.nodeId).setStyle({ width: width - margin + '%', left: (width * (ev.column - 1)) + '%' });
            });
            this.dayEvents.push(event.value);

            div = innerDiv;
            break;

        case 'month':
            var monthDay = this.monthDays['kronolithMonthDay' + date],
                div = _createElement(event)
                .setStyle({ backgroundColor: Kronolith.conf.calendars[calendar[0]][calendar[1]].bg,
                            color: Kronolith.conf.calendars[calendar[0]][calendar[1]].fg });
            div.writeAttribute('title', event.value.t);
            if (before) {
                before.insert({ before: div });
            } else {
                monthDay.insert(div);
            }
            if (event.value.pe) {
                div.setStyle({ cursor: 'move' });
                new Drag(event.value.nodeId, { threshold: 5, parentElement: function() { return $('kronolith-month-body'); }, snapToParent: true });
            }
            if (Kronolith.conf.max_events) {
                var more = monthDay.down('.kronolithMore');
                if (more) {
                    monthDay.insert({ bottom: more.remove() });
                }
            }
            break;

        case 'agenda':
            var div = _createElement(event)
                .setStyle({ backgroundColor: Kronolith.conf.calendars[calendar[0]][calendar[1]].bg,
                            color: Kronolith.conf.calendars[calendar[0]][calendar[1]].fg });
            this.createAgendaDay(date);
            $('kronolithAgendaDay' + date).insert(div);
            break;
        }

        this.setEventText(div, event.value,
                          { time: view == 'agenda' || Kronolith.conf.show_time })
            .observe('mouseover', div.addClassName.curry('kronolith-selected'))
            .observe('mouseout', div.removeClassName.curry('kronolith-selected'));
    },

    /**
     * Re-renders the necessary parts of the current view, if any event changes
     * in those parts require re-rendering.
     *
     * @param Array dates  The date strings of days to re-render.
     */
    reRender: function(dates)
    {
        switch (this.view) {
        case 'week':
        case 'workweek':
        case 'day':
            dates.each(function(date) {
                date = this.parseDate(date);
                this.insertEvents([ date, date ], this.view);
            }, this);
            break;
        case 'month':
            dates.each(function(date) {
                var day = this.monthDays['kronolithMonthDay' + date];
                day.select('.kronolith-event').each(function(event) {
                    if (event.retrieve('calendar').startsWith('holiday')) {
                        delete this.holidays[event.retrieve('eventid')];
                    }
                    event.remove();
                }, this);
                day.select('.kronolithMore').invoke('remove');
                date = this.parseDate(date);
                this.loadEvents(date, date, 'month');
            }, this);
            break;
        }
    },

    /**
     * Returns all dates of the current view that contain (recurrences) of a
     * certain event.
     *
     * @param String cal      A calendar string.
     * @param String eventid  An event id.
     *
     * @return Array  A list of date strings that contain a recurrence of the
     *                event.
     */
    findEventDays: function(cal, eventid)
    {
        cal = cal.split('|');
        var cache = this.ecache.get(cal[0]).get(cal[1]),
            dates = this.viewDates(this.date, this.view),
            day = dates[0], days = [], dateString;
        while (!day.isAfter(dates[1])) {
            dateString = day.dateString();
            if (cache.get(dateString).get(eventid)) {
                days.push(dateString);
            }
            day.add(1).days();
        }
        return days;
    },

    /**
     * Adds a "more..." button to the month view cell that links to the days,
     * or moves it to the buttom.
     *
     * @param string date  The date string of the day cell.
     */
    insertMore: function(date)
    {
        var monthDay = this.monthDays['kronolithMonthDay' + date],
            more = monthDay.down('.kronolithMore');
        if (more) {
            monthDay.insert({ bottom: more.remove() });
        } else {
            monthDay.insert({ bottom: new Element('span', { className: 'kronolithMore' }).store('date', date).insert(Kronolith.text.more) });
        }
    },

    setEventText: function(div, event, opts)
    {
        var calendar = event.calendar.split('|'),
            span = new Element('span'),
            time, end;
        opts = Object.extend({ time: false }, opts || {});

        div.update();
        if (event.ic) {
            div.insert(new Element('img', { src: event.ic, className: 'kronolithEventIcon' }));
        }
        if (opts.time && !event.al) {
            time = new Element('span', { className: 'kronolith-time' })
                .insert(event.start.toString(Kronolith.conf.time_format));
            if (!event.start.equals(event.end)) {
                end = event.end.clone();
                if (end.getHours() == 23 &&
                    end.getMinutes() == 59 &&
                    end.getSeconds() == 59) {
                    end.add(1).second();
                }
                time.insert('-' + end.toString(Kronolith.conf.time_format));
            }
            div.insert(time).insert(' ');
        }
        div.insert(event.t.escapeHTML());
        div.insert(span);
        if (event.a) {
            span.insert(' ')
                .insert(new Element('img', { src: Kronolith.conf.images.alarm.replace(/fff/, Kronolith.conf.calendars[calendar[0]][calendar[1]].fg.substr(1)), title: Kronolith.text.alarm + ' ' + event.a }));
        }
        if (event.r) {
            span.insert(' ')
                .insert(new Element('img', { src: Kronolith.conf.images.recur.replace(/fff/, Kronolith.conf.calendars[calendar[0]][calendar[1]].fg.substr(1)), title: Kronolith.text.recur[event.r] }));
        } else if (event.bid) {
            div.store('bid', event.bid);
            span.insert(' ')
                .insert(new Element('img', { src: Kronolith.conf.images.exception.replace(/fff/, Kronolith.conf.calendars[calendar[0]][calendar[1]].fg.substr(1)), title: Kronolith.text.recur.exception }));
        }
        return div;
    },

    /**
     * Finally removes events from the DOM and the cache.
     *
     * @param string calendar  A calendar name.
     * @param string event     An event id. If empty, all events from the
     *                         calendar are deleted.
     */
    removeEvent: function(calendar, event)
    {
        this.deleteCache(calendar, event);
        this.kronolithBody.select('div.kronolith-event').findAll(function(el) {
            return el.retrieve('calendar') == calendar &&
                (!event || el.retrieve('eventid') == event);
        }).invoke('remove');
    },

    /**
     * Removes all events that reprensent exceptions to the event series
     * represented by uid.
     *
     * @param string calendar  A calendar name.
     * @param string uid       An event uid.
     */
    removeException: function(calendar, uid)
    {
        this.kronolithBody.select('div.kronolith-event').findAll(function(el) {
            if (el.retrieve('calendar') == calendar && el.retrieve('bid') == uid) {
                this.removeEvent(calendar, el.retrieve('eventid'));
            }
        }.bind(this));
    },

    /**
     * Calculates the event's start and end dates based on some drag and drop
     * information.
     */
    calculateEventDates: function(event, storage, step, offset, height, start, end)
    {
        if (!Object.isUndefined(start)) {
            event.start = start;
            event.end = end;
        }
        event.start.set({
            hour: offset / this[storage].height | 0,
            minute: Math.round(offset % this[storage].height / step) * 10
        });
        var hour = (offset + height + this[storage].spacing) / this[storage].height | 0,
            minute = Math.round((offset + height + this[storage].spacing) % this[storage].height / step) * 10,
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

    switchTaskView: function(on)
    {
        if (on) {
            $('kronolithNewEvent', 'kronolithNewTask').compact()[0]
                .replace(Kronolith.conf.new_task);
            $('kronolithQuickEvent').addClassName('kronolithNewTask');
            $('kronolithHeader').down('.kronolithPrev').up().addClassName('disabled');
            $('kronolithHeader').down('.kronolithNext').up().addClassName('disabled');
        } else {
            $('kronolithNewEvent', 'kronolithNewTask').compact()[0]
                .replace(Kronolith.conf.new_event);
            $('kronolithQuickEvent').removeClassName('kronolithNewTask');
            $('kronolithHeader').down('.kronolithPrev').up().removeClassName('disabled');
            $('kronolithHeader').down('.kronolithNext').up().removeClassName('disabled');
        }
    },

    /**
     * Returns the task cache storage names that hold the tasks of the
     * requested task type.
     *
     * @param string tasktype  The task type.
     *
     * @return array  The list of task cache storage names.
     */
    getTaskStorage: function(tasktype)
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
    loadTasks: function(tasktype, tasklists)
    {
        var tasktypes = this.getTaskStorage(tasktype), loading = false,
            spinner = $('kronolithLoading');

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
            tasklists.each(function(list) {
                if (Object.isUndefined(this.tcache.get(type)) ||
                    Object.isUndefined(this.tcache.get(type).get(list))) {
                    loading = true;
                    this.loading++;
                    spinner.show();
                    HordeCore.doAction('listTasks', {
                        type: type,
                        list: list
                    }, {
                        callback: function(r) {
                            this.loadTasksCallback(r, true);
                        }.bind(this)
                    });
                }
            }, this);
        }, this);

        if (!loading) {
            tasklists.each(function(list) {
                this.insertTasks(tasktype, list);
            }, this);
        }
    },

    /**
     * Callback method for inserting tasks in the current view.
     *
     * @param object r             The ajax response object.
     * @param boolean createCache  Whether to create a cache list entry for the
     *                             response, if none exists yet. Useful for
     *                             (not) adding individual tasks to the cache
     *                             without assuming to have all tasks of the
     *                             list.
     */
    loadTasksCallback: function(r, createCache)
    {
        // Hide spinner.
        this.loading--;
        if (!this.loading) {
            $('kronolithLoading').hide();
        }

        this.storeTasksCache(r.tasks || {}, r.type, r.list, createCache);

        // Check if result is still valid for the current view.
        // There could be a rare race condition where two responses for the
        // same task(s) arrive in the wrong order. Checking this too, like we
        // do for events seems not worth it.
        var tasktypes = this.getTaskStorage(this.tasktype),
            tasklist = Kronolith.conf.calendars.tasklists['tasks/' + r.list];
        if (this.view != 'tasks' ||
            !tasklist || !tasklist.show ||
            !tasktypes.include(r.type)) {
            return;
        }
        this.insertTasks(this.tasktype, r.list);
    },

    /**
     * Reads tasks from the cache and inserts them into the view.
     *
     * @param integer tasktype  The tasks type (all, incomplete, complete, or
     *                          future).
     * @param string tasksList  The task list to be drawn.
     */
    insertTasks: function(tasktype, tasklist)
    {
        var tasktypes = this.getTaskStorage(tasktype), now = new Date();

        $('kronolithViewTasksBody').select('tr').findAll(function(el) {
            return el.retrieve('tasklist') == tasklist;
        }).invoke('remove');

        tasktypes.each(function(type) {
            if (!this.tcache.get(type)) {
                return;
            }
            var tasks = this.tcache.get(type).get(tasklist);
            $H(tasks).each(function(task) {
                switch (tasktype) {
                case 'complete':
                    if (!task.value.cp) {
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
                    if (task.value.cp ||
                        Object.isUndefined(task.value.start) ||
                        !task.value.start.isAfter(now)) {
                        return;
                    }
                    break;
                }
                this.insertTask(task);
            }, this);
        }, this);

        if ($('kronolithViewTasksBody').select('tr').length > 2) {
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
    insertTask: function(task)
    {
        var row = $('kronolithTasksTemplate').clone(true),
            col = row.down(), tagc;


        row.removeAttribute('id');
        row.store('tasklist', task.value.l);
        row.store('taskid', task.key);
        col.addClassName('kronolithTask' + (!!task.value.cp ? 'Completed' : ''));
        col.setStyle({
            backgroundColor: Kronolith.conf.calendars.tasklists['tasks/' + task.value.l].bg,
            color: Kronolith.conf.calendars.tasklists['tasks/' + task.value.l].fg,
            textIndent: task.value.i + 'em'
        });
        col.insert(task.value.n.escapeHTML());
        if (!Object.isUndefined(task.value.due)) {
            var now = new Date();
            if (!now.isBefore(task.value.due)) {
                col.addClassName('kronolithTaskDue');
            }
            col.insert(new Element('span', { className: 'kronolithSeparator' }).update(' &middot; '));
            col.insert(new Element('span', { className: 'kronolithDate' }).update(task.value.due.toString(Kronolith.conf.date_format)));
            if (task.value.r) {
                col.insert(' ')
                    .insert(new Element('img', { src: Kronolith.conf.images.recur.replace(/fff/, Kronolith.conf.calendars.tasklists['tasks/' + task.value.l].fg.substr(1)), title: Kronolith.text.recur[task.value.r] }));
            }
        }

        if (!Object.isUndefined(task.value.sd)) {
            col.insert(new Element('span', { className: 'kronolithSeparator' }).update(' &middot; '));
            col.insert(new Element('span', { className: 'kronolithInfo' }).update(task.value.sd.escapeHTML()));
        }

        if (task.value.t && task.value.t.size() > 0) {
            tagc = new Element('ul', { className: 'horde-tags' });
            task.value.t.each(function(x) {
                tagc.insert(new Element('li').update(x.escapeHTML()));
            });
            col.insert(tagc);
        }
        row.insert(col.show());
        this.insertTaskPosition(row, task);
    },

    /**
     * Inserts the task row in the correct position.
     *
     * @param Element newRow  The new row to be inserted.
     * @param object newTask  A Hash with the task being added.
     */
    insertTaskPosition: function(newRow, newTask)
    {
        var rows = $('kronolithViewTasksBody').select('tr'),
            rowTasklist, rowTaskId, rowTask, parentFound;
        // The first row is a template, ignoring.
        for (var i = 2; i < rows.length; i++) {
            rowTasklist = rows[i].retrieve('tasklist');
            rowTaskId = rows[i].retrieve('taskid');
            if (newTask.value.p) {
                if (rowTaskId == newTask.value.p) {
                    parentFound = true;
                    continue;
                }
                if (!parentFound) {
                    continue;
                }
            }
            rowTask = this.tcache.inject(null, function(acc, list) {
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
            if (!this.isTaskAfter(newTask.value, rowTask)) {
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
    isTaskAfter: function(taskA, taskB)
    {
        // TODO: Make all ordering system
        if ((taskA.p || taskB.p) && taskA.p != taskB.p) {
            return !taskA.p;
        }
        return (taskA.pr >= taskB.pr);
    },

    /**
     * Completes/uncompletes a task.
     *
     * @param string tasklist          The task list to which the tasks belongs.
     * @param string taskid            The id of the task.
     * @param boolean|string complete  True if the task is completed, a
     *                                 due date if there are still
     *                                 incomplete recurrences.
     */
    toggleCompletion: function(tasklist, taskid, complete)
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
            this.toggleCompletionClass(taskid);
            return;
        }
        if (Object.isUndefined(complete) || complete === true) {
            task.cp = !task.cp;
        }

        if (this.tcache.get(task.cp ? 'complete' : 'incomplete')) {
            this.tcache.get(task.cp ? 'complete' : 'incomplete').get(tasklist).set(taskid, task);
        }
        if (this.tcache.get(task.cp ? 'incomplete' : 'complete')) {
            this.tcache.get(task.cp ? 'incomplete' : 'complete').get(tasklist).unset(taskid);
        }

        // Remove row if necessary.
        var row = this.getTaskRow(taskid);
        if (!row) {
            return;
        }
        if ((this.tasktype == 'complete' && !task.cp) ||
            ((this.tasktype == 'incomplete' || this.tasktype == 'future_incomplete') && task.cp) ||
            ((complete === true) && (this.tasktype == 'future'))) {

            row.fade({
                duration: this.effectDur,
                afterFinish: function() {
                    row.purge();
                    row.remove();

                    //Check if items remained in interface
                    if ($('kronolithViewTasksBody').select('tr').length < 3) {
                        $('kronolithTasksNoItems').show();
                    }
                }
            });
        }

        // Update due date if necessary.
        if (!Object.isUndefined(complete) && complete !== true) {
            var now = new Date(), due = Date.parse(complete);
            row.down('span.kronolithDate')
                .update(due.toString(Kronolith.conf.date_format));
            if (now.isBefore(due)) {
                row.down('td.kronolithTaskCol')
                    .removeClassName('kronolithTaskDue');
            }
        }
    },

    /**
     * Toggles the CSS class to show that a task is completed/uncompleted.
     *
     * @param string taskid  The id of the task.
     */
    toggleCompletionClass: function(taskid)
    {
        var row = this.getTaskRow(taskid);
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
    getTaskRow: function(taskid)
    {
        return $('kronolithViewTasksBody').select('tr').find(function(el) {
            return el.retrieve('taskid') == taskid;
        });
    },

    editTask: function(tasklist, id, desc)
    {
        if (this.redBoxLoading) {
            return;
        }

        if (Object.isUndefined(HordeImple.AutoCompleter.kronolithTaskTags)) {
            this.editTask.bind(this, tasklist, id, desc).defer();
            return;
        }

        this.closeRedBox();
        this.quickClose();
        this.redBoxOnDisplay = RedBox.onDisplay;
        RedBox.onDisplay = function() {
            if (this.redBoxOnDisplay) {
                this.redBoxOnDisplay();
            }
            try {
                $('kronolithTaskForm').focusFirstElement();
            } catch(e) {}
            RedBox.onDisplay = this.redBoxOnDisplay;
        }.bind(this);

        this.openTab($('kronolithTaskForm').down('.tabset a.kronolithTabLink'));
        $('kronolithTaskForm').enable();
        $('kronolithTaskForm').reset();
        HordeImple.AutoCompleter.kronolithTaskTags.reset();
        $('kronolithTaskSave').show().enable();
        $('kronolithTaskDelete').show().enable();
        $('kronolithTaskForm').down('.kronolithFormActions .kronolithSeparator').show();
        this.updateTasklistDropDown();
        this.disableAlarmMethods('Task');
        this.knl.kronolithTaskDueTime.markSelected();
        if (id) {
            RedBox.loading();
            this.updateTaskParentDropDown(tasklist);
            this.updateTaskAssigneeDropDown(tasklist);
            HordeCore.doAction('getTask', {
                list: tasklist,
                id: id
            }, {
                callback: this.editTaskCallback.bind(this)
            });
            $('kronolithTaskTopTags').update();
        } else {
            $('kronolithTaskId').clear();
            $('kronolithTaskOldList').clear();
            $('kronolithTaskList').setValue(Kronolith.conf.tasks.default_tasklist);
            this.updateTaskParentDropDown(Kronolith.conf.tasks.default_tasklist);
            this.updateTaskAssigneeDropDown(Kronolith.conf.tasks.default_tasklist);
            $('kronolithTaskParent').setValue('');
            $('kronolithTaskAssignee').setValue('');
            //$('kronolithTaskLocation').setValue('http://');
            HordeCore.doAction('listTopTags', {}, {
                callback: this.topTagsCallback.curry('kronolithTaskTopTags', 'kronolithTaskTag')
            });
            $('kronolithTaskPriority').setValue(3);
            if (Kronolith.conf.tasks.default_due) {
                this.setDefaultDue();
            }
            if (desc) {
                $('kronolithTaskDescription').setValue(desc);
            }
            this.toggleRecurrence(false, 'None');
            $('kronolithTaskDelete').hide();
            this.redBoxLoading = true;
            RedBox.showHtml($('kronolithTaskDialog').show());
        }
    },

    /**
     * Callback method for showing task forms.
     *
     * @param object r  The ajax response object.
     */
    editTaskCallback: function(r)
    {
        if (!r.task) {
            RedBox.close();
            this.go(this.lastLocation);
            return;
        }

        var task = r.task;

        /* Basic information */
        $('kronolithTaskId').setValue(task.id);
        $('kronolithTaskOldList').setValue(task.l);
        $('kronolithTaskList').setValue(task.l);
        $('kronolithTaskTitle').setValue(task.n);
        $('kronolithTaskParent').setValue(task.p);
        $('kronolithTaskAssignee').setValue(task.as);
        //$('kronolithTaskLocation').setValue(task.l);
        if (task.dd) {
            $('kronolithTaskDueDate').setValue(task.dd);
        }
        if (task.dt) {
            $('kronolithTaskDueTime').setValue(task.dt);
            this.knl.kronolithTaskDueTime.setSelected(task.dt);
        }
        $('kronolithTaskDescription').setValue(task.de);
        $('kronolithTaskPriority').setValue(task.pr);
        $('kronolithTaskCompleted').setValue(task.cp);

        /* Alarm */
        if (task.a) {
            this.enableAlarm('Task', task.a);
            if (task.m) {
                $('kronolithTaskAlarmDefaultOff').checked = true;
                $H(task.m).each(function(method) {
                    if (!$('kronolithTaskAlarm' + method.key)) {
                        return;
                    }
                    $('kronolithTaskAlarm' + method.key).setValue(1);
                    if ($('kronolithTaskAlarm' + method.key + 'Params')) {
                        $('kronolithTaskAlarm' + method.key + 'Params').show();
                    }
                    $H(method.value).each(function(param) {
                        var input = $('kronolithTaskAlarmParam' + param.key);
                        if (!input) {
                            return;
                        }
                        if (input.type == 'radio') {
                            input.up('form').select('input[type=radio]').each(function(radio) {
                                if (radio.name == input.name &&
                                    radio.value == param.value) {
                                    radio.setValue(1);
                                    throw $break;
                                }
                            });
                        } else {
                            input.setValue(param.value);
                        }
                    });
                });
            }
        } else {
            $('kronolithTaskAlarmOff').setValue(true);
        }

        /* Recurrence */
        if (task.r) {
            this.setRecurrenceFields(false, task.r);
        } else {
            this.toggleRecurrence(false, 'None');
        }

        HordeImple.AutoCompleter.kronolithTaskTags.reset(task.t);

        if (!task.pe) {
            $('kronolithTaskSave').hide();
            $('kronolithTaskForm').disable();
        } else {
            HordeCore.doAction('listTopTags', {}, {
                callback: this.topTagsCallback.curry('kronolithTaskTopTags', 'kronolithTaskTag')
            });
        }

        if (!task.pd) {
            $('kronolithTaskDelete').show();
        }
        if (!task.pe && !task.pd) {
            $('kronolithTaskForm').down('.kronolithFormActions .kronolithSeparator').hide();
        }

        this.setTitle(task.n);
        this.redBoxLoading = true;
        RedBox.showHtml($('kronolithTaskDialog').show());

        /* Hide alarm message for this task. */
        if (r.msgs) {
            r.msgs = r.msgs.reject(function(msg) {
                if (msg.type != 'horde.alarm') {
                    return false;
                }
                var alarm = msg.flags.alarm;
                if (alarm.params && alarm.params.notify &&
                    alarm.params.notify.show &&
                    alarm.params.notify.show.tasklist &&
                    alarm.params.notify.show.task &&
                    alarm.params.notify.show.tasklist == task.l &&
                    alarm.params.notify.show.task == task.id) {
                    return true;
                }
                return false;
            });
        }
    },

    /**
     * Propagates a SELECT drop down list with the editable task lists.
     */
    updateTasklistDropDown: function()
    {
        var tasklist = $('kronolithTaskList');
        tasklist.update();
        $H(Kronolith.conf.calendars.tasklists).each(function(cal) {
            if (cal.value.edit) {
                tasklist.insert(new Element('option', { value: cal.key.substring(6) })
                                .setStyle({ backgroundColor: cal.value.bg, color: cal.value.fg })
                                .update(cal.value.name.escapeHTML()));
            }
        });
    },

    /**
     * Propagates a SELECT drop down list with the tasks of a task list.
     *
     * @param string list  A task list ID.
     */
    updateTaskParentDropDown: function(list)
    {
        var parents = $('kronolithTaskParent');
        parents.update(new Element('option', { value: '' })
                       .update(Kronolith.text.no_parent));
        HordeCore.doAction('listTasks', {
            type: 'future_incomplete',
            list: list
        }, {
            ajaxopts: { asynchronuous: false },
            callback: function(r) {
                $H(r.tasks).each(function(task) {
                    parents.insert(new Element('option', { value: task.key })
                                .setStyle({ textIndent: task.value.i + 'em' })
                                .update(task.value.n.escapeHTML()));
                });
            }.bind(this)
        });
    },

    /**
     * Propagates a SELECT drop down list with the users of a task list.
     *
     * @param string list  A task list ID.
     */
    updateTaskAssigneeDropDown: function(list)
    {
        var assignee = $('kronolithTaskAssignee');
        assignee.update(new Element('option', { value: '' })
                       .update(Kronolith.text.no_assignee));
        $H(Kronolith.conf.calendars.tasklists['tasks/' + list].users).each(function(user) {
            assignee.insert(new Element('option', { value: user.key })
                            .update(user.value.escapeHTML()));
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
     * Finally removes tasks from the DOM and the cache.
     *
     * @param string list  A task list name.
     * @param string task  A task id. If empty, all tasks from the list are
     *                     deleted.
     */
    removeTask: function(list, task)
    {
        this.deleteTasksCache(list, task);
        $('kronolithViewTasksBody').select('tr').findAll(function(el) {
            return el.retrieve('tasklist') == list &&
                (!task || el.retrieve('taskid') == task);
        }).invoke('remove');
        this.removeEvent('tasklists|tasks/' + list, task ? '_tasks' + task : null);
        if ($('kronolithViewTasksBody').select('tr').length > 2) {
            $('kronolithTasksNoItems').hide();
        } else {
            $('kronolithTasksNoItems').show();
        }
    },

    /**
     * Submits the task edit form to create or update a task.
     */
    saveTask: function()
    {
        if (this.wrongFormat.size() ||
            (($F('kronolithTaskAlarmOn')) && $F('kronolithTaskDueDate').length == 0)) {
            HordeCore.notify(Kronolith.text.fix_form_values, 'horde.warning');
            return;
        }

        var tasklist = $F('kronolithTaskOldList'),
            target = $F('kronolithTaskList'),
            taskid = $F('kronolithTaskId'),
            viewDates = this.viewDates(this.date, this.view),
            start = viewDates[0].dateString(),
            end = viewDates[1].dateString();

        HordeImple.AutoCompleter.kronolithTaskTags.shutdown();
        $('kronolithTaskSave').disable();
        this.startLoading('tasklists|tasks/' + target, start + end + this.tasktype);
        this.loading++;
        HordeCore.doAction(
            'saveTask',
            $H($('kronolithTaskForm').serialize({ hash: true })).merge({
                sig: start + end + this.tasktype,
                view: this.view,
                view_start: start,
                view_end: end
            }), {
                callback: function(r) {
                    if (r.tasks && taskid) {
                        this.removeTask(tasklist, taskid);
                    }
                    this.loadTasksCallback(r, false);
                    this.loadEventsCallback(r, false);
                    if (r.tasks) {
                        this.closeRedBox();
                        this.go(this.lastLocation);
                    } else {
                        $('kronolithTaskSave').enable();
                    }
                }.bind(this)
            }
        );
    },

    quickSaveTask: function()
    {
        var text = $F('kronolithQuicktaskQ'),
            viewDates = this.viewDates(this.date, 'tasks'),
            start = viewDates[0].dateString(),
            end = viewDates[1].dateString(),
            params = {
                sig: start + end + this.tasktype,
                view: 'tasks',
                view_start: start,
                view_end: end,
                tasklist: Kronolith.conf.tasks.default_tasklist,
                text: text
            };

        this.closeRedBox();
        this.startLoading('tasklists|tasks/' + Kronolith.conf.tasks.default_tasklist,
                          params.sig);
        this.loading++;
        HordeCore.doAction('quickSaveTask', params, {
            callback: function(r) {
                this.loadTasksCallback(r, false);
                this.loadEventsCallback(r, false);
                if (!r.tasks || !$H(r.tasks).size()) {
                    this.editTask(null, null, text);
                } else {
                    $('kronolithQuicktaskQ').value = '';
                }
            }.bind(this)
         });
    },

    /**
     * Opens the form for editing a calendar.
     *
     * @param string calendar  Calendar type and calendar id, separated by '|'.
     */
    editCalendar: function(calendar)
    {
        if (this.redBoxLoading) {
            return;
        }

        this.closeRedBox();
        this.quickClose();

        var type = calendar.split('|')[0], cal = calendar.split('|')[1];
        if (!$w('internal tasklists remote holiday resource resourcegroup').include(type)) {
            return;
        }

        if (cal &&
            (Object.isUndefined(Kronolith.conf.calendars[type]) ||
             Object.isUndefined(Kronolith.conf.calendars[type][cal])) &&
            (type == 'internal' || type == 'tasklists')) {
            HordeCore.doAction('getCalendar', {
                cal: cal
            }, {
                callback: function(r) {
                    if (r.calendar) {
                        Kronolith.conf.calendars[type][cal] = r.calendar;
                        this.insertCalendarInList(type, cal, r.calendar);
                        $('kronolithSharedCalendars').show();
                        this.editCalendar(type + '|' + cal);
                    } else {
                        this.go(this.lastLocation);
                    }
                }.bind(this)
            });
            return;
        }

        this.redBoxOnDisplay = RedBox.onDisplay;
        RedBox.onDisplay = function() {
            if (this.redBoxOnDisplay) {
                this.redBoxOnDisplay();
            }
            try {
                $('kronolithCalendarForm' + type).focusFirstElement();
            } catch(e) {}
            RedBox.onDisplay = this.redBoxOnDisplay;
        }.bind(this);

        if ($('kronolithCalendarDialog')) {
            this.redBoxLoading = true;
            RedBox.showHtml($('kronolithCalendarDialog').show());
            this.editCalendarCallback(calendar);
        } else {
            RedBox.loading();
            HordeCore.doAction('chunkContent', {
                chunk: 'calendar'
            }, {
                callback: function(r) {
                    if (r.chunk) {
                        this.redBoxLoading = true;
                        RedBox.showHtml(r.chunk);
                        ['internal', 'tasklists'].each(function(type) {
                            $('kronolithC' + type + 'PGList').observe('change', function() {
                                $('kronolithC' + type + 'PG').setValue(1);
                                this.permsClickHandler(type, 'G');
                            }.bind(this));
                        }, this);
                        this.editCalendarCallback(calendar);
                    } else {
                        this.closeRedBox();
                    }
                }.bind(this)
            });
        }
    },

    /**
     * Callback for editing a calendar. Fills the edit form with the correct
     * values.
     *
     * @param string calendar  Calendar type and calendar id, separated by '|'.
     */
    editCalendarCallback: function(calendar)
    {
        calendar = calendar.split('|');
        var type = calendar[0];
        calendar = calendar.length == 1 ? null : calendar[1];

        var form = $('kronolithCalendarForm' + type),
            firstTab = form.down('.tabset a.kronolithTabLink'),
            info;

        form.enable();
        form.reset();
        if (firstTab) {
            this.openTab(firstTab);
        }
        $('kronolithCalendarDialog').select('.kronolithCalendarDiv').invoke('hide');
        $('kronolithCalendar' + type + '1').show();
        form.select('.kronolithCalendarContinue').invoke('enable');
        $('kronolithC' + type + 'PUNew', 'kronolithC' + type + 'PGNew').compact().each(function(elm) {
            if (elm.tagName == 'SELECT') {
                $A(elm.options).each(function(option) {
                    option.writeAttribute('disabled', false);
                });
            }
        });

        var newCalendar = !calendar;
        if (calendar &&
            (Object.isUndefined(Kronolith.conf.calendars[type]) ||
             Object.isUndefined(Kronolith.conf.calendars[type][calendar]))) {
            if (type != 'remote') {
                this.closeRedBox();
                this.go(this.lastLocation);
                return;
            }
            newCalendar = true;
        }
        if (type == 'resourcegroup') {
            this.updateResourcegroupSelect();
        }
        if (newCalendar) {
            switch (type) {
            case 'internal':
                HordeImple.AutoCompleter.kronolithCalendarinternalTags.reset();
                // Fall through.
            case 'tasklists':
                $('kronolithCalendar' + type + 'LinkExport').up('span').hide();
                break;
            case 'remote':
                if (calendar) {
                    $('kronolithCalendarremoteUrl').setValue(calendar);
                    $('kronolithCalendarremoteId').setValue(calendar);
                }
                break;
            case 'holiday':
                $('kronolithCalendarholidayDriver').update();
                $H(Kronolith.conf.calendars.holiday).each(function(calendar) {
                    if (calendar.value.show) {
                        return;
                    }
                    $('kronolithCalendarholidayDriver').insert(
                        new Element('option', { value: calendar.key })
                            .setStyle({ color: calendar.value.fg, backgroundColor: calendar.value.bg })
                            .insert(calendar.value.name.escapeHTML())
                    );
                });
                break;
            }
            $('kronolithCalendar' + type + 'Id').clear();
            var color = '#', i;
            for (i = 0; i < 3; i++) {
                color += (Math.random() * 256 | 0).toColorPart();
            }
            $('kronolithCalendar' + type + 'Color').setValue(color).setStyle({ backgroundColor: color, color: Color.brightness(Color.hex2rgb(color)) < 125 ? '#fff' : '#000' });
            form.down('.kronolithCalendarDelete').hide();
            $('kronolithCalendarinternalImportButton').hide();
        } else {
            info = Kronolith.conf.calendars[type][calendar];

            $('kronolithCalendar' + type + 'Id').setValue(calendar);
            $('kronolithCalendar' + type + 'Name').setValue(info.name);
            $('kronolithCalendar' + type + 'Color').setValue(info.bg).setStyle({ backgroundColor: info.bg, color: info.fg });
            $('kronolithCalendarinternalImportButton').hide();

            switch (type) {
            case 'internal':
                HordeImple.AutoCompleter.kronolithCalendarinternalTags.reset(Kronolith.conf.calendars.internal[calendar].tg);
                $('kronolithCalendar' + type + 'ImportCal').setValue('internal_' + calendar);
                if ($('kronolithCalendar' + type + 'LinkImport')) {
                    if (info.edit) {
                        $('kronolithCalendar' + type + 'LinkImport').up('li').show();
                    } else {
                        $('kronolithCalendar' + type + 'LinkImport').up('li').hide();
                    }
                }
                $('kronolithCalendar' + type + 'UrlFeed').setValue(info.feed);
                $('kronolithCalendar' + type + 'EmbedUrl').setValue(info.embed);
                // Fall through.
            case 'tasklists':
                $('kronolithCalendar' + type + 'Description').setValue(info.desc);
                if ($('kronolithCalendar' + type + 'LinkExport')) {
                    $('kronolithCalendar' + type + 'LinkExport').up('span').show();
                    $('kronolithCalendar' + type + 'Export').href = type == 'internal'
                        ? Kronolith.conf.URI_CALENDAR_EXPORT.interpolate({ calendar: calendar })
                        : Kronolith.conf.tasks.URI_TASKLIST_EXPORT.interpolate({ tasklist: calendar.substring(6) });
                }
                $('kronolithCalendar' + type + 'LinkUrls').up().show();
                if (info.caldav) {
                    $('kronolithCalendar' + type + 'UrlCaldav').setValue(info.caldav);
                    $('kronolithCalendar' + type + 'Caldav').show();
                } else {
                    $('kronolithCalendar' + type + 'Caldav').hide();
                }
                $('kronolithCalendar' + type + 'UrlWebdav').setValue(info.sub);
                break;
            case 'remote':
                $('kronolithCalendarremoteUrl').setValue(calendar);
                $('kronolithCalendarremoteDescription').setValue(info.desc);
                $('kronolithCalendarremoteUsername').setValue(info.user);
                $('kronolithCalendarremotePassword').setValue(info.password);
                break;
            case 'resourcegroup':
                $('kronolithCalendarresourcegroupDescription').setValue(info.desc);
                $('kronolithCalendarresourcegroupmembers').setValue(info.members);
                break;
            case 'resource':
                $('kronolithCalendarresourceDescription').setValue(info.desc);
                $('kronolithCalendarresourceResponseType').setValue(info.response_type);
                $('kronolithCalendarresourceExport').href = Kronolith.conf.URI_RESOURCE_EXPORT.interpolate({ calendar: calendar });
            }
        }

        if (newCalendar || info.owner) {
            if (type == 'internal' || type == 'tasklists') {
                this.updateGroupDropDown([['kronolithC' + type + 'PGList', this.updateGroupPerms.bind(this, type)],
                                          ['kronolithC' + type + 'PGNew']]);
                $('kronolithC' + type + 'PBasic').show();
                $('kronolithC' + type + 'PAdvanced').hide();
                $('kronolithC' + type + 'PNone').setValue(1);
                if ($('kronolithC' + type + 'PAllShow')) {
                    $('kronolithC' + type + 'PAllShow').disable();
                }
                $('kronolithC' + type + 'PGList').disable();
                $('kronolithC' + type + 'PGPerms').disable();
                $('kronolithC' + type + 'PUList').disable();
                $('kronolithC' + type + 'PUPerms').disable();
                $('kronolithC' + type + 'PAdvanced').select('tr').findAll(function(tr) {
                    return tr.retrieve('remove');
                }).invoke('remove');
                $('kronolithCalendar' + type + 'LinkUrls').up().show();
                form.down('.kronolithColorPicker').show();
                if (type == 'internal') {
                    HordeCore.doAction('listTopTags', {}, {
                        callback: this.topTagsCallback.curry('kronolithCalendarinternalTopTags', 'kronolithCalendarTag')
                    });
                }
                form.down('.kronolithCalendarSubscribe').hide();
                form.down('.kronolithCalendarUnsubscribe').hide();
                if ($('kronolithCalendar' + type + 'LinkPerms')) {
                    $('kronolithCalendar' + type + 'LinkPerms').up('span').show();
                }
                if (!Object.isUndefined(info) && info.owner) {
                    this.setPermsFields(type, info.perms);
                }
            }
            if (type == 'remote' || type == 'internal' || type == 'tasklists') {
                if (newCalendar ||
                    (type == 'internal' && calendar == Kronolith.conf.user) ||
                    (type == 'tasklists' && calendar == 'tasks/' + Kronolith.conf.user)) {
                    form.select('.kronolithCalendarDelete').invoke('hide');
                } else {
                    form.select('.kronolithCalendarDelete').invoke('show');
                }
            }
            form.down('.kronolithCalendarSave').show();
            form.down('.kronolithFormActions .kronolithSeparator').show();
        } else {
            form.disable();
            form.down('.kronolithColorPicker').hide();
            form.down('.kronolithCalendarDelete').hide();
            form.down('.kronolithCalendarSave').hide();
            if (type == 'internal' || type == 'tasklists') {
                $('kronolithCalendar' + type + 'UrlCaldav').enable();
                $('kronolithCalendar' + type + 'UrlAccount').enable();
                $('kronolithCalendar' + type + 'UrlWebdav').enable();
                if (type == 'internal') {
                    $('kronolithCalendar' + type + 'UrlFeed').enable();
                    $('kronolithCalendar' + type + 'EmbedUrl').enable();
                    if (info.edit) {
                        $('kronolithCalendarinternalImport').enable();
                        if (info.del) {
                            $('kronolithCalendarinternalImportOver').enable();
                        }
                        $('kronolithCalendarinternalImportButton').show().enable();
                    }
                }
                HordeImple.AutoCompleter.kronolithCalendarinternalTags.disable();
                if (Kronolith.conf.calendars[type][calendar].show) {
                    form.down('.kronolithCalendarSubscribe').hide();
                    form.down('.kronolithCalendarUnsubscribe').show().enable();
                } else {
                    form.down('.kronolithCalendarSubscribe').show().enable();
                    form.down('.kronolithCalendarUnsubscribe').hide();
                }
                form.down('.kronolithFormActions .kronolithSeparator').show();
                if ($('kronolithCalendar' + type + 'LinkPerms')) {
                    $('kronolithCalendar' + type + 'LinkPerms').up('span').hide();
                }
            } else {
                form.down('.kronolithFormActions .kronolithSeparator').hide();
            }
        }
    },

    /**
     * Updates the select list in the resourcegroup calendar dialog.
     */
    updateResourcegroupSelect: function()
    {
        if (!Kronolith.conf.calendars.resource) {
            return;
        }
        $('kronolithCalendarresourcegroupmembers').update();
        $H(Kronolith.conf.calendars.resource).each(function(r) {
            var o = new Element('option', { value: r.value.id }).update(r.value.name);
            $('kronolithCalendarresourcegroupmembers').insert(o);
        });
    },

    /**
     * Handles clicks on the radio boxes of the basic permissions screen.
     *
     * @param string type  The calendar type, 'internal' or 'taskslists'.
     * @param string perm  The permission to activate, 'None', 'All', or
     *                     'Group'.
     */
    permsClickHandler: function(type, perm)
    {
        $('kronolithC' + type + 'PAdvanced')
            .select('input[type=checkbox]')
            .invoke('setValue', 0);
        $('kronolithC' + type + 'PAdvanced').select('tr').findAll(function(tr) {
            return tr.retrieve('remove');
        }).invoke('remove');

        switch (perm) {
        case 'None':
            if ($('kronolithC' + type + 'PAllShow')) {
                $('kronolithC' + type + 'PAllShow').disable();
            }
            $('kronolithC' + type + 'PGList').disable();
            $('kronolithC' + type + 'PGPerms').disable();
            $('kronolithC' + type + 'PUList').disable();
            $('kronolithC' + type + 'PUPerms').disable();
            break;
        case 'All':
            $('kronolithC' + type + 'PAllShow').enable();
            $('kronolithC' + type + 'PGList').disable();
            $('kronolithC' + type + 'PGPerms').disable();
            $('kronolithC' + type + 'PUList').disable();
            $('kronolithC' + type + 'PUPerms').disable();
            var perms = {
                'default': Kronolith.conf.perms.read,
                'guest': Kronolith.conf.perms.read
            };
            if ($F('kronolithC' + type + 'PAllShow')) {
                perms['default'] |= Kronolith.conf.perms.show;
                perms['guest'] |= Kronolith.conf.perms.show;
            }
            this.setPermsFields(type, perms);
            break;
        case 'G':
            if ($('kronolithC' + type + 'PAllShow')) {
                $('kronolithC' + type + 'PAllShow').disable();
            }
            $('kronolithC' + type + 'PGList').enable();
            $('kronolithC' + type + 'PGPerms').enable();
            $('kronolithC' + type + 'PUList').disable();
            $('kronolithC' + type + 'PUPerms').disable();
            var group = $F('kronolithC' + type + 'PGSingle')
                ? $F('kronolithC' + type + 'PGSingle')
                : $F('kronolithC' + type + 'PGList');
            this.insertGroupOrUser(type, 'group', group, true);
            $('kronolithC' + type + 'PGshow_' + group).setValue(1);
            $('kronolithC' + type + 'PGread_' + group).setValue(1);
            if ($F('kronolithC' + type + 'PGPerms') == 'edit') {
                $('kronolithC' + type + 'PGedit_' + group).setValue(1);
            } else {
                $('kronolithC' + type + 'PGedit_' + group).setValue(0);
            }
            $('kronolithC' + type + 'PGdel_' + group).setValue(0);
            if ($('kronolithC' + type + 'PGdelegate_' + group)) {
                $('kronolithC' + type + 'PGdelegate_' + group).setValue(0);
            }
            break;
        case 'U':
            if ($('kronolithC' + type + 'PAllShow')) {
                $('kronolithC' + type + 'PAllShow').disable();
            }
            $('kronolithC' + type + 'PGList').disable();
            $('kronolithC' + type + 'PGPerms').disable();
            $('kronolithC' + type + 'PUList').enable();
            $('kronolithC' + type + 'PUPerms').enable();
            var users = $F('kronolithC' + type + 'PUList').strip();
            users = users ? users.split(/\s*(?:,|\n)\s*/) : [];
            users.each(function(user) {
                if (!this.insertGroupOrUser(type, 'user', user, true)) {
                    return;
                }
                $('kronolithC' + type + 'PUshow_' + user).setValue(1);
                $('kronolithC' + type + 'PUread_' + user).setValue(1);
                if ($F('kronolithC' + type + 'PUPerms') == 'edit') {
                    $('kronolithC' + type + 'PUedit_' + user).setValue(1);
                } else {
                    $('kronolithC' + type + 'PUedit_' + user).setValue(0);
                }
                $('kronolithC' + type + 'PUdel_' + user).setValue(0);
                if ($('kronolithC' + type + 'PUdelegate_' + user)) {
                    $('kronolithC' + type + 'PUdelegate_' + user).setValue(0);
                }
            }, this);
            break;
        }
    },

    /**
     * Populates the permissions field matrix.
     *
     * @param string type   The calendar type, 'internal' or 'taskslists'.
     * @param object perms  An object with the resource permissions.
     */
    setPermsFields: function(type, perms)
    {
        if (this.groupLoading) {
            this.setPermsFields.bind(this, type, perms).defer();
            return;
        }

        var allperms = $H(Kronolith.conf.perms),
            advanced = false, users = [],
            basic, same, groupPerms, groupId, userPerms;
        $H(perms).each(function(perm) {
            switch (perm.key) {
            case 'default':
            case 'guest':
                if (Object.isUndefined(same)) {
                    same = perm.value;
                } else if (Object.isUndefined(basic) &&
                           same == perm.value &&
                           (perm.value == Kronolith.conf.perms.read ||
                            perm.value == (Kronolith.conf.perms.read | Kronolith.conf.perms.show))) {
                    basic = perm.value == Kronolith.conf.perms.read ? 'all_read' : 'all_show';
                } else if (perm.value != 0) {
                    advanced = true;
                }
                break;
            case 'creator':
                if (perm.value != 0) {
                    advanced = true;
                }
                break;
            case 'groups':
                if (!Object.isArray(perm.value)) {
                    $H(perm.value).each(function(group) {
                        if (!this.insertGroupOrUser(type, 'group', group.key)) {
                            return;
                        }
                        if (!$('kronolithC' + type + 'PGshow_' + group.key)) {
                            // Group doesn't exist anymore.
                            delete perm.value[group.key];
                            return;
                        }
                        groupPerms = group.value;
                        groupId = group.key;
                    }, this);
                    if (Object.isUndefined(basic) &&
                        $H(perm.value).size() == 1 &&
                        (groupPerms == (Kronolith.conf.perms.show | Kronolith.conf.perms.read) ||
                         groupPerms == (Kronolith.conf.perms.show | Kronolith.conf.perms.read | Kronolith.conf.perms.edit))) {
                        basic = groupPerms == (Kronolith.conf.perms.show | Kronolith.conf.perms.read) ? 'group_read' : 'group_edit';
                    } else {
                        advanced = true;
                    }
                }
                break;
            case 'users':
                if (!Object.isArray(perm.value)) {
                    $H(perm.value).each(function(user) {
                        if (user.key != Kronolith.conf.user) {
                            if (!this.insertGroupOrUser(type, 'user', user.key)) {
                                return;
                            }
                            if (!$('kronolithC' + type + 'PUshow_' + user.key)) {
                                // User doesn't exist anymore.
                                delete perm.value[user.key];
                                return;
                            }
                            // Check if we already have other basic permissions.
                            if (Object.isUndefined(userPerms) &&
                                !Object.isUndefined(basic)) {
                                advanced = true;
                            }
                            // Check if all users have the same permissions.
                            if (!Object.isUndefined(userPerms) &&
                                userPerms != user.value) {
                                advanced = true;
                            }
                            userPerms = user.value;
                            if (!advanced &&
                                (userPerms == (Kronolith.conf.perms.show | Kronolith.conf.perms.read) ||
                                 userPerms == (Kronolith.conf.perms.show | Kronolith.conf.perms.read | Kronolith.conf.perms.edit))) {
                                basic = userPerms == (Kronolith.conf.perms.show | Kronolith.conf.perms.read) ? 'user_read' : 'user_edit';
                                users.push(user.key);
                            } else {
                                advanced = true;
                            }
                        }
                    }, this);
                }
                break;
            }

            allperms.each(function(baseperm) {
                if (baseperm.key == 'all') {
                    return;
                }
                switch (perm.key) {
                case 'default':
                case 'guest':
                case 'creator':
                    if (baseperm.value & perm.value) {
                        $('kronolithC' + type + 'P' + perm.key + baseperm.key).setValue(1);
                    }
                    break;
                case 'groups':
                    $H(perm.value).each(function(group) {
                        if (baseperm.value & group.value) {
                            $('kronolithC' + type + 'PG' + baseperm.key + '_' + group.key).setValue(1);
                        }
                    });
                    break;
                case 'users':
                    $H(perm.value).each(function(user) {
                        if (baseperm.value & user.value &&
                            user.key != Kronolith.conf.user) {
                            $('kronolithC' + type + 'PU' + baseperm.key + '_' + user.key).setValue(1);
                        }
                    });
                    break;
                }
            });
        }.bind(this));

        if (advanced) {
            this.activateAdvancedPerms(type);
        } else {
            switch (basic) {
            case 'all_read':
                $('kronolithC' + type + 'PAll').setValue(1);
                $('kronolithC' + type + 'PAllShow').setValue(0);
                $('kronolithC' + type + 'PAllShow').enable();
                break;
            case 'all_show':
                $('kronolithC' + type + 'PAll').setValue(1);
                $('kronolithC' + type + 'PAllShow').setValue(1);
                $('kronolithC' + type + 'PAllShow').enable();
                break;
            case 'group_read':
            case 'group_edit':
                var setGroup = function(group) {
                    if ($('kronolithC' + type + 'PGList').visible()) {
                        $('kronolithC' + type + 'PGList').setValue(group);
                        if ($('kronolithC' + type + 'PGList').getValue() != group) {
                            // Group no longer exists.
                            this.permsClickHandler(type, 'None');
                        }
                    } else if ($('kronolithC' + type + 'PGSingle').getValue() != group) {
                        // Group no longer exists.
                        this.permsClickHandler(type, 'None');
                    }
                }.bind(this, groupId);
                if (this.groupLoading) {
                    setGroup.defer();
                } else {
                    setGroup();
                }
                $('kronolithC' + type + 'PG').setValue(1);
                $('kronolithC' + type + 'PGPerms').setValue(basic.substring(6));
                $('kronolithC' + type + 'PAdvanced').hide();
                $('kronolithC' + type + 'PBasic').show();
                $('kronolithC' + type + 'PGPerms').enable();
                break;
            case 'user_read':
            case 'user_edit':
                $('kronolithC' + type + 'PUList').enable().setValue(users.join(', '));
                $('kronolithC' + type + 'PU').setValue(1);
                $('kronolithC' + type + 'PUPerms').setValue(basic.substring(5));
                $('kronolithC' + type + 'PAdvanced').hide();
                $('kronolithC' + type + 'PBasic').show();
                $('kronolithC' + type + 'PUPerms').enable();
                break;
            }
        }
   },

    /**
     * Propagates a SELECT drop down list with the groups.
     *
     * @param array params  A two-dimensional array with the following values
     *                      in each element:
     *                      - The id of the SELECT element.
     *                      - A callback method that is invoked with the group
     *                        list passes as an argument.
     */
    updateGroupDropDown: function(params)
    {
        this.groupLoading = true;
        params.each(function(param) {
            var elm = $(param[0]), options = elm.childElements();
            options.invoke('remove');
            elm.up('form').disable();
        });
        HordeCore.doAction('listGroups', {}, {
            callback: function(r) {
                var groups;
                if (r.groups) {
                    groups = $H(r.groups);
                    params.each(function(param) {
                        groups.each(function(group) {
                            $(param[0]).insert(new Element('option', { value: group.key }).update(group.value.escapeHTML()));
                        });
                    });
                }
                params.each(function(param) {
                    $(param[0]).up('form').enable();
                    if (param[1]) {
                        param[1](groups);
                    }
                });
                this.groupLoading = false;
            }.bind(this)
        });
    },

    /**
     * Updates the group permission interface after the group list has
     * been loaded.
     *
     * @param string type  The calendar type, 'internal' or 'taskslists'.
     * @param Hash groups  The list of groups.
     */
    updateGroupPerms: function(type, groups)
    {
        $('kronolithC' + type + 'PGSingle').clear();
        if (!groups) {
            $('kronolithC' + type + 'PGNew').up('div').hide();
            $('kronolithC' + type + 'PG').up('span').hide();
        } else {
            $('kronolithC' + type + 'PGNew').up('div').show();
            $('kronolithC' + type + 'PG').up('span').show();
            if (groups.size() == 1) {
                $('kronolithC' + type + 'PGName')
                    .update('&quot;' + groups.values()[0].escapeHTML() + '&quot;')
                    .show();
                $('kronolithC' + type + 'PGSingle').setValue(groups.keys()[0]);
                $('kronolithC' + type + 'PGList').hide();
            } else {
                $('kronolithC' + type + 'PGName').hide();
                $('kronolithC' + type + 'PGList').show();
            }
        }
    },

    /**
     * Inserts a group or user row into the advanced permissions interface.
     *
     * @param string type          The calendar type, 'internal' or
     *                             'taskslists'.
     * @param what string          Either 'group' or 'user'.
     * @param group string         The group id or user name to insert.
     *                             Defaults to the value of the drop down.
     * @param stay_basic boolean   Enforces to NOT switch to the advanced
     *                             permissions screen.
     *
     * @return boolean  Whether a row has been inserted.
     */
    insertGroupOrUser: function(type, what, id, stay_basic)
    {
        var elm = $(what == 'user' ? 'kronolithC' + type + 'PUNew' : 'kronolithC' + type + 'PGNew');
        if (id) {
            elm.setValue(id);
        }
        var value = elm.getValue();
        if (!value) {
            if (id) {
                HordeCore.notify(Kronolith.text.invalid_user + ': ' + id, 'horde.error');
            }
            return false;
        }

        var tr = elm.up('tr'),
            row = tr.clone(true).store('remove', true),
            td = row.down('td'),
            clearName = elm.tagName == 'SELECT' ? elm.options[elm.selectedIndex].text: elm.getValue();

        td.update();
        td.insert(clearName.escapeHTML())
            .insert(new Element('input', { type: 'hidden', name: (what == 'user' ? 'u' : 'g') + '_names[' + value + ']', value: value }));
        row.select('input[type=checkbox]').each(function(input) {
            input.writeAttribute('name', input.name.replace(/\[.*?$/, '[' + value + ']'))
                .writeAttribute('id', input.id.replace(/new/, value))
                .next()
                .writeAttribute('for', input.id);
        });
        tr.insert({ before: row });

        if (elm.tagName == 'SELECT') {
            elm.options[elm.selectedIndex].writeAttribute('disabled', true);
            elm.selectedIndex = 0;
        } else {
            elm.clear();
        }

        if (!stay_basic) {
            this.activateAdvancedPerms(type);
        }

        return true;
    },

    /**
     * Activates the advanced permissions.
     *
     * @param string type  The calendar type, 'internal' or 'taskslists'.
     */
    activateAdvancedPerms: function(type)
    {
        [$('kronolithC' + type + 'PNone'),
         $('kronolithC' + type + 'PU'),
         $('kronolithC' + type + 'PG')].each(function(radio) {
            radio.checked = false;
        });
        if ($('kronolithC' + type + 'PAll')) {
            $('kronolithC' + type + 'PAll').checked = false;
        }
        $('kronolithC' + type + 'PBasic').hide();
        $('kronolithC' + type + 'PAdvanced').show();
    },

    /**
     * Opens the next screen of the calendar management wizard.
     *
     * @param string type  The calendar type.
     */
    calendarNext: function(type)
    {
        var i = 1;
        while (!$('kronolithCalendar' + type + i).visible()) {
            i++;
        }
        $('kronolithCalendar' + type + i).hide();
        $('kronolithCalendar' + type + (++i)).show();
        if (this.colorPicker) {
            this.colorPicker.hide();
        }
    },

    /**
     * Submits the calendar form to save the calendar data.
     *
     * @param Element form  The form node.
     *
     * @return boolean  Whether the save request was successfully sent.
     */
    saveCalendar: function(form)
    {
        if (this.colorPicker) {
            this.colorPicker.hide();
        }
        var data = form.serialize({ hash: true });

        if (data.type == 'holiday') {
            this.insertCalendarInList('holiday', data.driver, Kronolith.conf.calendars.holiday[data.driver]);
            this.toggleCalendar('holiday', data.driver);
            form.down('.kronolithCalendarSave').enable();
            this.closeRedBox();
            this.go(this.lastLocation);
            return;
        }

        if (data.name.empty()) {
            HordeCore.notify(data.type == 'tasklists' ? Kronolith.text.no_tasklist_title : Kronolith.text.no_calendar_title, 'horde.warning');
            $('kronolithCalendar' + data.type + 'Name').focus();
            return false;
        }
        HordeCore.doAction('saveCalendar', data, {
            callback: this.saveCalendarCallback.bind(this, form, data)
        });
        return true;
    },

    calendarImport: function(form, disableForm)
    {
        if ($F('kronolithCalendarinternalImport')) {
            HordeCore.notify(Kronolith.text.import_warning, 'horde.message');
            this.loading++;
            $('kronolithLoading').show();
            var name = 'kronolithIframe' + Math.round(Math.random() * 1000),
                iframe = new Element('iframe', { src: 'about:blank', name: name, id: name }).setStyle({ display: 'none' });
            document.body.insert(iframe);
            form.enable();
            form.target = name;
            form.submit();
            if (disableForm) {
                form.disable();
            }
        }
    },

    /**
     * Callback method after saving a calendar.
     *
     * @param Element form  The form node.
     * @param object data   The serialized form data.
     * @param object r      The ajax response object.
     */
    saveCalendarCallback: function(form, data, r)
    {
        var type = form.id.replace(/kronolithCalendarForm/, '');

        // If saving the calendar changed the owner, we need to delete
        // and re-insert the calendar.
        if (r.deleted) {
            this.deleteCalendar(type, data.calendar);
            delete data.calendar;
        }
        if (r.saved) {
            this.calendarImport(form, false);
            var cal = r.calendar, id;
            if (data.calendar) {
                var color = {
                    backgroundColor: cal.bg,
                    color: cal.fg
                },
                legendSpan;
                id = data.calendar;
                this.getCalendarList(type, cal.owner).select('div').each(function(element) {
                    if (element.retrieve('calendar') == id) {
                        var link = element.down('.horde-resource-link span');
                        element.setStyle(color);
                        link.update(cal.name.escapeHTML());
                        this.addShareIcon(cal, link);
                        throw $break;
                    }
                }, this);
                this.kronolithBody.select('div').each(function(el) {
                    if (el.retrieve('calendar') == type + '|' + id) {
                        el.setStyle(color);
                    }
                });
                legendSpan = $('kronolith-legend').select('span')
                    .find(function(span) {
                        return span.retrieve('calendarclass') == type &&
                            span.retrieve('calendar') == id;
                    });
                if (legendSpan) {
                    legendSpan.setStyle(color).update(cal.name.escapeHTML());
                }
                Kronolith.conf.calendars[type][id] = cal;
            } else {
                id = r.id;
                if (!Kronolith.conf.calendars[type]) {
                    Kronolith.conf.calendars[type] = [];
                }
                Kronolith.conf.calendars[type][id] = cal;
                this.insertCalendarInList(type, id, cal);
                this.storeCache($H(), [type, id], this.viewDates(this.date, this.view), true);
                if (type == 'tasklists') {
                    this.storeTasksCache($H(), this.tasktype, id.replace(/^tasks\//, ''), true);
                }
            }
            if (type == 'remote') {
                this.loadCalendar(type, id);
            }
        }
        form.down('.kronolithCalendarSave').enable();
        this.closeRedBox();
        this.go(this.lastLocation);
    },

    /**
     * Deletes a calendar and all its events from the interface and cache.
     *
     * @param string type      The calendar type.
     * @param string calendar  The calendar id.
     */
    deleteCalendar: function(type, calendar)
    {
        var container = this.getCalendarList(type, Kronolith.conf.calendars[type][calendar].owner),
            noItems = container.previous(),
            div = container.select('div').find(function(element) {
                return element.retrieve('calendar') == calendar;
            }),
            arrow = div.down('span');
        arrow.purge();
        arrow.remove();
        div.purge();
        div.remove();
        if (noItems &&
            noItems.tagName == 'DIV' &&
            noItems.className == 'horde-info' &&
            !container.childElements().size()) {
            noItems.show();
        }
        this.deleteCalendarLegend(type, calendar);
        this.removeEvent(type + '|' + calendar);
        this.deleteCache([type, calendar]);
        if (type == 'tasklists' && this.view == 'tasks') {
            this.removeTask(calendar.replace(/^tasks\//, ''));
        }
        delete Kronolith.conf.calendars[type][calendar];
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
        case 'workweek':
            if (view == 'workweek') {
                start.add(1).days();
            }
            start.moveToBeginOfWeek(view == 'week' ? Kronolith.conf.week_start : 1);
            end = start.clone();
            end.moveToEndOfWeek(Kronolith.conf.week_start);
            if (view == 'workweek') {
                end.add(Kronolith.conf.week_start == 0 ? -1 : -2).days();
            }
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
     * @param object events        A list of calendars and events as returned
     *                             from an ajax request.
     * @param string calendar      A calendar string or array.
     * @param string dates         A date range in the format yyyymmddyyyymmdd
     *                             as used in the ajax response signature.
     * @param boolean createCache  Whether to create a cache list entry for the
     *                             response, if none exists yet.
     */
    storeCache: function(events, calendar, dates, createCache)
    {
        if (Object.isString(calendar)) {
            calendar = calendar.split('|');
        }

        // Create cache entry for the calendar.
        if (!this.ecache.get(calendar[0])) {
            if (!createCache) {
                return;
            }
            this.ecache.set(calendar[0], $H());
        }
        if (!this.ecache.get(calendar[0]).get(calendar[1])) {
            if (!createCache) {
                return;
            }
            this.ecache.get(calendar[0]).set(calendar[1], $H());
        }
        var calHash = this.ecache.get(calendar[0]).get(calendar[1]);

        // Create empty cache entries for all dates.
        if (!!dates) {
            var day = dates[0].clone(), date;
            while (!day.isAfter(dates[1])) {
                date = day.dateString();
                if (!calHash.get(date)) {
                    if (!createCache) {
                        return;
                    }
                    if (!this.cacheStart || this.cacheStart.isAfter(day)) {
                        this.cacheStart = day.clone();
                    }
                    if (!this.cacheEnd || this.cacheEnd.isBefore(day)) {
                        this.cacheEnd = day.clone();
                    }
                    calHash.set(date, $H());
                }
                day.add(1).day();
            }
        }

        var cal = calendar.join('|');
        $H(events).each(function(date) {
            // We might not have a cache for this date if the event lasts
            // longer than the current view
            if (!calHash.get(date.key)) {
                return;
            }

            // Store calendar string and other useful information in event
            // objects.
            $H(date.value).each(function(event) {
                event.value.calendar = cal;
                event.value.start = Date.parse(event.value.s);
                event.value.end = Date.parse(event.value.e);
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
    storeTasksCache: function(tasks, tasktypes, tasklist, createCache)
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
                if (!createCache) {
                    return;
                }
                this.tcache.set(tasktype, $H());
            }
            if (!tasklist) {
                return;
            }
            if (!this.tcache.get(tasktype).get(tasklist)) {
                if (!createCache) {
                    return;
                }
                this.tcache.get(tasktype).set(tasklist, $H());
                cacheExists[tasktype] = true;
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
     * @param string calendar  A calendar string or array.
     * @param string event     An event ID or empty if deleting the calendar.
     * @param string day       A specific day to delete in yyyyMMdd form.
     */
    deleteCache: function(calendar, event, day)
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
        } else if (day) {
            this.ecache.get(calendar[0]).get(calendar[1]).unset(day);
        } else {
            this.ecache.get(calendar[0]).unset(calendar[1]);
        }
    },

    /**
     * Deletes tasks from the cache.
     *
     * @param string list  A task list string.
     * @param string task  A task ID. If empty, all tasks from the list are
     *                     deleted.
     */
    deleteTasksCache: function(list, task)
    {
        this.deleteCache([ 'external', 'tasks/' + list ], task);
        $w('complete incomplete').each(function(type) {
            if (!Object.isUndefined(this.tcache.get(type)) &&
                !Object.isUndefined(this.tcache.get(type).get(list))) {
                if (task) {
                    this.tcache.get(type).get(list).unset(task);
                } else {
                    this.tcache.get(type).unset(list);
                }
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
    getCacheForDate: function(date, calendar)
    {
        if (calendar) {
            var cals = calendar.split('|');
            if (!this.ecache.get(cals[0]) ||
                !this.ecache.get(cals[0]).get(cals[1])) {
                return $H();
            }
            var x = this.ecache.get(cals[0]).get(cals[1]).get(date);
            return this.ecache.get(cals[0]).get(cals[1]).get(date);
        }

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
    sortEvents: function(event)
    {
        return event.value.sort;
    },

    /**
     * Adds a new location to the history and displays it in the URL hash.
     *
     * This is not really a history, because only the current and the last
     * location are stored.
     *
     * @param string loc    The location to save.
     * @param boolean save  Whether to actually save the location. This should
     *                      be false for any location that are displayed on top
     *                      of another location, i.e. in a popup view.
     */
    addHistory: function(loc, save)
    {
        location.hash = encodeURIComponent(loc);
        this.lastLocation = this.currentLocation;
        if (Object.isUndefined(save) || save) {
            this.currentLocation = loc;
        }
        this.openLocation = loc;
    },

    /**
     * Loads an external page.
     *
     * @param string loc  The URL of the page to load.
     */
    loadPage: function(loc)
    {
        window.location.assign(loc);
    },

    searchSubmit: function(e)
    {
        this.go('search:' + this.search + ':' + $F('horde-search-input'));
    },

    searchReset: function(e)
    {
        HordeTopbar.searchGhost.reset();
    },

    /**
     * Event handler for HordeCore:showNotifications events.
     */
    showNotification: function(e)
    {
        if (!e.memo.flags ||
            !e.memo.flags.alarm ||
            !e.memo.flags.growl ||
            !e.memo.flags.alarm.params.notify.ajax) {
            return;
        }

        var growl = e.memo.flags.growl, link = growl.down('A');

        if (link) {
            link.observe('click', function(ee) {
                ee.stop();
                HordeCore.Growler.ungrowl(growl);
                this.go(e.memo.flags.alarm.params.notify.ajax);
            }.bind(this));
        }
    },

    /* Keydown event handler */
    keydownHandler: function(e)
    {
        if (e.stopped) {
            return;
        }

        var kc = e.keyCode || e.charCode,
            form = e.findElement('FORM'), trigger = e.findElement();

        switch (trigger.id) {
        case 'kronolithEventLocation':
            if (kc == Event.KEY_RETURN && $F('kronolithEventLocation')) {
                this.initializeMap(true);
                this.geocode($F('kronolithEventLocation'));
                e.stop();
                return;
            }
            break;

        case 'kronolithCalendarinternalUrlCaldav':
        case 'kronolithCalendarinternalUrlWebdav':
        case 'kronolithCalendarinternalUrlAccount':
        case 'kronolithCalendarinternalUrlFeed':
        case 'kronolithCalendartasklistsUrlCaldav':
        case 'kronolithCalendartasklistsUrlWebdav':
        case 'kronolithCalendartasklistsUrlAccount':
            if (String.fromCharCode(kc) != 'C' ||
                (this.macos && !e.metaKey) ||
                (!this.macos && !e.ctrlKey)) {
                e.stop();
                return;
            }
            break;
        }

        if (form) {
            switch (kc) {
            case Event.KEY_RETURN:
                switch (form.identify()) {
                case 'kronolithEventForm':
                    if (e.element().tagName != 'TEXTAREA') {
                        this.saveEvent();
                        e.stop();
                    }
                    break;

                case 'kronolithTaskForm':
                    if (e.element().tagName != 'TEXTAREA') {
                        this.saveTask();
                        e.stop();
                    }
                    break;

                case 'kronolithQuickinsertForm':
                    this.quickSaveEvent();
                    e.stop();
                    break;

                case 'kronolithCalendarForminternal':
                case 'kronolithCalendarFormtasklists':
                case 'kronolithCalendarFormremote':
                    // Disabled for now, we have to also catch Continue buttons.
                    //var saveButton = form.down('.kronolithCalendarSave');
                    //saveButton.disable();
                    //if (!this.saveCalendar(form)) {
                    //    saveButton.enable();
                    //}
                    //e.stop();
                    break;
                }
                break;

            case Event.KEY_ESC:
                switch (form.identify()) {
                case 'kronolithQuickinsertForm':
                case 'kronolithQuicktaskForm':
                    this.quickClose();
                    break;
                case 'kronolithEventForm':
                case 'kronolithTaskForm':
                    Horde_Calendar.hideCal();
                    this.closeRedBox();
                    this.go(this.lastLocation);
                    break;
                }
                break;
            }

            return;
        }

        switch (kc) {
        case Event.KEY_ESC:
            Horde_Calendar.hideCal();
            this.closeRedBox();
            break;
        }
    },

    keyupHandler: function(e)
    {
        switch (e.element().readAttribute('id')) {
        case 'kronolithEventLocation':
            if ($F('kronolithEventLocation') && Kronolith.conf.maps.driver) {
                $('kronolithEventMapLink').show();
            } else if (Kronolith.conf.maps.driver) {
                $('kronolithEventMapLink').hide();
                this.removeMapMarker();
            }
            return;

        case 'kronolithEventStartTime':
        case 'kronolithEventEndTime':
            var field = $(e.element().readAttribute('id')), kc = e.keyCode;

            switch(e.keyCode) {
            case Event.KEY_UP:
            case Event.KEY_DOWN:
            case Event.KEY_RIGHT:
            case Event.KEY_LEFT:
                return;
            default:
                if ($F(field) !== this.knl[field.identify()].getCurrentEntry()) {
                    this.knl[field.identify()].markSelected(null);
                }
                return;
            }
        }

    },

    clickHandler: function(e, dblclick)
    {
        if (e.isRightClick() || typeof e.element != 'function') {
            return;
        }

        var elt = e.element(),
            orig = e.element(),
            id, tmp, calendar;

        while (Object.isElement(elt)) {
            id = elt.readAttribute('id');

            switch (id) {
            case 'kronolithNewEvent':
                this.go('event');
                e.stop();
                return;

            case 'kronolithNewTask':
                this.go('task');
                e.stop();
                return;

            case 'kronolithQuickEvent':
                if (this.view == 'tasks') {
                    RedBox.showHtml($('kronolithQuicktask').show());
                } else {
                    this.updateCalendarDropDown('kronolithQuickinsertCalendars');
                    $('kronolithQuickinsertCalendars').setValue(Kronolith.conf.default_calendar);
                    RedBox.showHtml($('kronolithQuickinsert').show());
                }
                e.stop();
                return;

            case 'kronolithQuickinsertSave':
                this.quickSaveEvent();
                e.stop();
                return;

            case 'kronolithQuicktaskSave':
                this.quickSaveTask();
                e.stop();
                return;

            case 'kronolithQuickinsertCancel':
            case 'kronolithQuicktaskCancel':
                this.quickClose();
                e.stop();
                return;

            case 'kronolithGotoToday':
                var view = this.view;
                if (!$w('day workweek week month year agenda').include(view)) {
                    view = Kronolith.conf.login_view;
                }
                this.go(view + ':' + new Date().dateString());
                e.stop();
                return;

            case 'kronolithEventAllday':
                this.toggleAllDay();
                break;

            case 'kronolithEventAlarmDefaultOn':
                this.disableAlarmMethods('Event');
                break;

            case 'kronolithTaskAlarmDefaultOn':
                this.disableAlarmMethods('Task');
                break;

            case 'kronolithEventAlarmPrefs':
                HordeCore.redirect(HordeCore.addURLParam(
                    Kronolith.conf.prefs_url,
                    {
                        group: 'notification'
                    }
                ));
                e.stop();
                break;

            case 'kronolithTaskAlarmPrefs':
                if (Kronolith.conf.tasks.prefs_url) {
                    HordeCore.redirect(HordeCore.addURLParam(
                        Kronolith.conf.tasks.prefs_url,
                        {
                            group: 'notification'
                        }
                    ));
                }
                e.stop();
                break;

            case 'kronolithEventLinkNone':
            case 'kronolithEventLinkDaily':
            case 'kronolithEventLinkWeekly':
            case 'kronolithEventLinkMonthly':
            case 'kronolithEventLinkYearly':
            case 'kronolithEventLinkLength':
            case 'kronolithTaskLinkNone':
            case 'kronolithTaskLinkDaily':
            case 'kronolithTaskLinkWeekly':
            case 'kronolithTaskLinkMonthly':
            case 'kronolithTaskLinkYearly':
            case 'kronolithTaskLinkLength':
                this.toggleRecurrence(
                    id.startsWith('kronolithEvent'),
                    id.substring(id.startsWith('kronolithEvent') ? 18 : 17));
                break;

            case 'kronolithEventRepeatDaily':
            case 'kronolithEventRepeatWeekly':
            case 'kronolithEventRepeatMonthly':
            case 'kronolithEventRepeatYearly':
            case 'kronolithEventRepeatLength':
            case 'kronolithTaskRepeatDaily':
            case 'kronolithTaskRepeatWeekly':
            case 'kronolithTaskRepeatMonthly':
            case 'kronolithTaskRepeatYearly':
            case 'kronolithTaskRepeatLength':
                this.toggleRecurrence(
                    id.startsWith('kronolithEvent'),
                    id.substring(id.startsWith('kronolithEvent') ? 20 : 19));
                break;

            case 'kronolithEventSave':
                if (!elt.disabled) {
                    this._checkDate($('kronolithEventStartDate'));
                    this._checkDate($('kronolithEventEndDate'));
                    if ($F('kronolithEventAttendees') && $F('kronolithEventId')) {
                        $('kronolithEventSendUpdates').setValue(0);
                        $('kronolithEventDiv').hide();
                        $('kronolithUpdateDiv').show();
                        e.stop();
                        break;
                    }
                }
            case 'kronolithEventSendUpdateYes':
                if (this.uatts) {
                    this.uatts.u = true;
                } else {
                    $('kronolithEventSendUpdates').setValue(1);
                }
            case 'kronolithEventSendUpdateNo':
                if (this.uatts) {
                    this.doDragDropUpdate(this.uatts, this.ucb);
                    this.uatts = null;
                    this.ucb = null;
                    this.closeRedBox();
                    $('kronolithUpdateDiv').hide();
                    $('kronolithEventDiv').show();
                } else if (!elt.disabled) {
                    this.saveEvent();
                }
                e.stop();
                break;
            case 'kronolithEventConflictYes':
                this.doSaveEvent();
                e.stop();
                break;
            case 'kronolithEventConflictNo':
                $('kronolithConflictDiv').hide();
                $('kronolithEventDiv').show();
                e.stop();
                break;
            case 'kronolithEventSaveAsNew':
                if (!elt.disabled) {
                    $('kronolithEventSendUpdates').setValue(1);
                    this.saveEvent(true);
                }
                e.stop();
                break;

            case 'kronolithTaskSave':
                if (!elt.disabled) {
                    this.saveTask();
                }
                e.stop();
                break;

            case 'kronolithEventDeleteCancel':
                $('kronolithDeleteDiv').hide();
                $('kronolithEventDiv').show();
                e.stop();
                return;

            case 'kronolithEventSendCancellationYes':
                $('kronolithRecurDeleteAll').enable();
                $('kronolithRecurDeleteCurrent').enable();
                $('kronolithRecurDeleteFuture').enable();
                this.paramsCache.sendupdates = 1;
            case 'kronolithEventSendCancellationNo':
                $('kronolithRecurDeleteAll').enable();
                $('kronolithRecurDeleteCurrent').enable();
                $('kronolithRecurDeleteFuture').enable();
                $('kronolithCancellationDiv').hide();
                this.delete_verified = true;
            case 'kronolithEventDelete':
                if ((Kronolith.conf.confirm_delete || this.recurs) && !this.delete_verified) {
                    $('kronolithEventDiv').hide();
                    $('kronolithDeleteDiv').show();
                    e.stop();
                    break;
                } else {
                    $('kronolithEventDiv').hide();
                    this.delete_verified = false;
                }
                // Fallthrough
            case 'kronolithRecurDeleteAll':
            case 'kronolithRecurDeleteCurrent':
            case 'kronolithRecurDeleteFuture':
            case 'kronolithEventDeleteConfirm':
                if (elt.disabled) {
                    e.stop();
                    break;
                }
                elt.disable();
                var cal = $F('kronolithEventCalendar'),
                    eventid = $F('kronolithEventId');
                if (id != 'kronolithEventSendCancellationNo' &&
                    id != 'kronolithEventSendCancellationYes') {
                    this.paramsCache = {
                        cal: cal,
                        id: eventid,
                        rstart: $F('kronolithEventRecurOStart'),
                        cstart: this.cacheStart.toISOString(),
                        cend: this.cacheEnd.toISOString()
                    };
                    switch (id) {
                    case 'kronolithRecurDeleteAll':
                        this.paramsCache.r = 'all';
                        break;
                    case 'kronolithRecurDeleteCurrent':
                        this.paramsCache.r = 'current';
                        break;
                    case 'kronolithRecurDeleteFuture':
                        this.paramsCache.r = 'future';
                        break;
                    }
                }

                if (id != 'kronolithEventSendCancellationNo'
                    && id != 'kronolithEventSendCancellationYes'
                    && $F('kronolithEventAttendees')) {

                    $('kronolithDeleteDiv').hide();
                    $('kronolithCancellationDiv').show();
                    e.stop();
                    break;
                }

                this.kronolithBody.select('div').findAll(function(el) {
                    return el.retrieve('calendar') == cal &&
                        el.retrieve('eventid') == eventid;
                }).invoke('hide');
                var viewDates = this.viewDates(this.date, this.view),
                start = viewDates[0].toString('yyyyMMdd'),
                end = viewDates[1].toString('yyyyMMdd');
                this.paramsCache.sig = start + end + (Math.random() + '').slice(2);
                this.paramsCache.view_start = start;
                this.paramsCache.view_end = end;

                HordeCore.doAction('deleteEvent', this.paramsCache, {
                    callback: function(elt,r) {
                        if (r.deleted) {
                            var days;
                            if (this.view == 'month' ||
                                this.view == 'week' ||
                                this.view == 'workweek' ||
                                this.view == 'day') {
                                days = this.findEventDays(cal, eventid);
                                days.each(function(day) {
                                    this.refreshResources(day, cal, eventid);
                                }.bind(this));
                            }
                            this.removeEvent(cal, eventid);
                            if (r.uid) {
                                this.removeException(cal, r.uid);
                            }
                            this.loadEventsCallback(r, false);
                            if (days && days.length) {
                                this.reRender(days);
                            }
                        } else {
                            this.kronolithBody.select('div').findAll(function(el) {
                                return el.retrieve('calendar') == cal &&
                                       el.retrieve('eventid') == eventid;
                            }).invoke('show');
                        }
                        elt.enable();
                    }.curry(elt).bind(this)
                });

                $('kronolithDeleteDiv').hide();
                $('kronolithEventDiv').show();
                this.closeRedBox();
                this.go(this.lastLocation);
                e.stop();
                break;

            case 'kronolithTaskDelete':
                if (elt.disabled) {
                    e.stop();
                    break;
                }

                elt.disable();
                var tasklist = $F('kronolithTaskOldList'),
                    taskid = $F('kronolithTaskId');

                HordeCore.doAction('deleteTask', {
                    list: tasklist,
                    id: taskid
                }, {
                    callback: function(r) {
                        if (r.deleted) {
                            this.removeTask(tasklist, taskid);
                        } else {
                            elt.enable();
                            $('kronolithViewTasksBody').select('tr').find(function(el) {
                                return el.retrieve('tasklist') == tasklist &&
                                       el.retrieve('taskid') == taskid;
                            }).toggle();
                        }
                    }.bind(this)
                });

                var taskrow = $('kronolithViewTasksBody').select('tr').find(function(el) {
                    return el.retrieve('tasklist') == tasklist &&
                        el.retrieve('taskid') == taskid;
                });
                if (taskrow) {
                    taskrow.hide();
                }
                this.closeRedBox();
                this.go(this.lastLocation);
                e.stop();
                break;

            case 'kronolithCinternalPMore':
            case 'kronolithCinternalPLess':
            case 'kronolithCtasklistsPMore':
            case 'kronolithCtasklistsPLess':
                var type = id.match(/kronolithC(.*)P/)[1];
                $('kronolithC' + type + 'PBasic').toggle();
                $('kronolithC' + type + 'PAdvanced').toggle();
                e.stop();
                break;

            case 'kronolithCinternalPNone':
            case 'kronolithCinternalPAll':
            case 'kronolithCinternalPG':
            case 'kronolithCinternalPU':
            case 'kronolithCtasklistsPNone':
            case 'kronolithCtasklistsPAll':
            case 'kronolithCtasklistsPG':
            case 'kronolithCtasklistsPU':
                var info = id.match(/kronolithC(.*)P(.*)/);
                this.permsClickHandler(info[1], info[2]);
                break;

            case 'kronolithCinternalPAllShow':
            case 'kronolithCtasklistsPAllShow':
                var type = id.match(/kronolithC(.*)P/)[1];
                this.permsClickHandler(type, 'All');
                break;

            case 'kronolithCinternalPAdvanced':
            case 'kronolithCtasklistsPAdvanced':
                var type = id.match(/kronolithC(.*)P/)[1];
                if (orig.tagName != 'INPUT') {
                    break;
                }
                this.activateAdvancedPerms(type);
                if (orig.name.match(/u_.*||new/)) {
                    this.insertGroupOrUser(type, 'user');
                }
                break;

            case 'kronolithCinternalPUAdd':
            case 'kronolithCinternalPGAdd':
            case 'kronolithCtasklistsPUAdd':
            case 'kronolithCtasklistsPGAdd':
                var info = id.match(/kronolithC(.*)P(.)/);
                this.insertGroupOrUser(info[1], info[2] == 'U' ? 'user' : 'group');
                break;

            case 'kronolithNavDay':
            case 'kronolithNavWeek':
            case 'kronolithNavWorkweek':
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

            case 'kronolithMinicalDate':
                this.go('month:' + orig.retrieve('date'));
                e.stop();
                return;

            case 'kronolith-minical':
                if (orig.id == 'kronolith-minical-prev') {
                    var date = this.parseDate($('kronolithMinicalDate').retrieve('date'));
                    date.previous().month();
                    this.updateMinical(date, date.getMonth() == this.date.getMonth() ? this.view : undefined);
                    e.stop();
                    return;
                }
                if (orig.id == 'kronolith-minical-next') {
                    var date = this.parseDate($('kronolithMinicalDate').retrieve('date'));
                    date.next().month();
                    this.updateMinical(date, date.getMonth() == this.date.getMonth() ? this.view : null);
                    e.stop();
                    return;
                }

                var tmp = orig;
                if (tmp.tagName.toLowerCase() != 'td') {
                    tmp = tmp.up('td');
                }
                if (tmp) {
                    if (tmp.retrieve('weekdate') &&
                        tmp.hasClassName('kronolith-minical-week')) {
                        this.go('week:' + tmp.retrieve('weekdate'));
                    } else if (tmp.retrieve('date') &&
                               !tmp.hasClassName('empty')) {
                        this.go('day:' + tmp.retrieve('date'));
                    }
                }
                e.stop();
                return;

            case 'kronolithEventsDay':
                var date = this.date.clone();
                date.add(Math.round((e.pointerY() - elt.cumulativeOffset().top + elt.up('.kronolithViewBody').scrollTop) / this.daySizes.height * 2) * 30).minutes();
                this.go('event:' + date.toString('yyyyMMddHHmm'));
                e.stop();
                return;

            case 'kronolithViewMonth':
                if (orig.hasClassName('kronolith-first-col')) {
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
                if (tmp.tagName.toLowerCase() != 'td' && tmp.tagName.toLowerCase() != 'th') {
                    tmp = tmp.up('td');
                }
                if (tmp) {
                    if (tmp.retrieve('weekdate') &&
                        tmp.hasClassName('kronolith-minical-week')) {
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

            case 'kronolithViewAgendaBody':
                var tmp = orig;
                if (tmp.tagName != 'TR') {
                    tmp = tmp.up('tr');
                }
                if (tmp && tmp.retrieve('date')) {
                    this.go('day:' + tmp.retrieve('date'));
                }
                e.stop();
                return;

            case 'kronolithSearchButton':
                this.go('search:' + this.search + ':' + $F('horde-search-input'));
                e.stop();
                break;

            case 'kronolithSearchFuture':
                if (this.search != 'future') {
                    this.go('search:future:' + $F('horde-search-input'));
                }
                e.stop();
                break;

            case 'kronolithSearchPast':
                if (this.search != 'past') {
                    this.go('search:past:' + $F('horde-search-input'));
                }
                e.stop();
                break;

            case 'kronolithSearchAll':
                if (this.search != 'all') {
                    this.go('search:all:' + $F('horde-search-input'));
                }
                e.stop();
                break;
            case 'kronolithEventToTimeslice':
                var params = $H();
                params.set('e', $('kronolithEventId').value);
                params.set('cal', $('kronolithEventCalendar').value);
                params.set('t', $('kronolithEventTimesliceType').value);
                params.set('c', $('kronolithEventTimesliceClient').value);
                HordeCore.doAction('toTimeslice', params);
                break;
            case 'kronolithEventDialog':
            case 'kronolithTaskDialog':
                Horde_Calendar.hideCal();
                return;

            case 'kronolithCalendarDialog':
                if (this.colorPicker) {
                    this.colorPicker.hide();
                }
                return;

            case 'kronolithEditRecurCurrent':
            case 'kronolithEditRecurFuture':
                $('kronolithEventStartDate').setValue(this.orstart);
                $('kronolithEventEndDate').setValue(this.orend);
                if (id == 'kronolithEditRecurCurrent') {
                    this.toggleRecurrence('Exception');
                } else {
                    this.toggleRecurrence(this.lastRecurType);
                }
                return;
            case 'kronolithEditRecurAll':
                this.toggleRecurrence(this.lastRecurType);
                break;
            case 'kronolithEventUrlToggle':
                $('kronolithEventUrlDisplay').hide();
                $('kronolithEventUrl').show();
                e.stop();
                return;
            case 'kronolithCalendarinternalImportButton':
                // Used when user has edit perms to a shared calendar.
                this.calendarImport(elt.up('form'), true);
                break;
            }

            // Caution, this only works if the element has definitely only a
            // single CSS class.
            switch (elt.className) {
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
                case 'workweek':
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

            case 'horde-add':
                this.go('calendar:' + id.replace(/kronolithAdd/, ''));
                e.stop();
                return;

            case 'kronolithTabLink':
                this.openTab(elt);
                e.stop();
                break;

            case 'horde-cancel':
                this.closeRedBox();
                this.resetMap();
                this.go(this.lastLocation);
                e.stop();
                break;

            case 'kronolithEventTag':
                HordeImple.AutoCompleter.kronolithEventTags.addNewItemNode(elt.getText());
                e.stop();
                break;

            case 'kronolithCalendarTag':
                HordeImple.AutoCompleter.kronolithCalendarinternalTags.addNewItemNode(elt.getText());
                e.stop();
                break;

            case 'kronolithTaskTag':
                HordeImple.AutoCompleter.kronolithTaskTags.addNewItemNode(elt.getText());
                e.stop();
                break;

            case 'kronolithEventGeo':
                this.initializeMap(true);
                this.geocode($F('kronolithEventLocation'));
                e.stop();
                break;

            case 'kronolithTaskRow':
                if (elt.retrieve('taskid')) {
                    this.go('task:' + elt.retrieve('tasklist') + ':' + elt.retrieve('taskid'));
                }
                e.stop();
                return;

            case 'horde-resource-edit-000':
            case 'horde-resource-edit-fff':
                this.go('calendar:' + elt.up().retrieve('calendarclass') + '|' + elt.up().retrieve('calendar'));
                e.stop();
                return;

            case 'kronolithMore':
                this.go('day:' + elt.retrieve('date'));
                e.stop();
                return;

            case 'kronolithDatePicker':
                id = elt.readAttribute('id');
                Horde_Calendar.open(id, Date.parseExact($F(id.replace(/Picker$/, 'Date')), Kronolith.conf.date_format));
                e.stop();
                return;

            case 'kronolithColorPicker':
                var input = elt.previous();
                this.colorPicker = new ColorPicker({
                    color: $F(input),
                    offsetParent: elt,
                    update: [[input, 'value'],
                             [input, 'background']]
                });
                e.stop();
                return;
            }

            if (elt.hasClassName('kronolith-event')) {
                if (!Object.isUndefined(elt.retrieve('ajax'))) {
                    this.go(elt.retrieve('ajax'));
                } else {
                    this.go('event:' + elt.retrieve('calendar') + ':' + elt.retrieve('eventid') + ':' + elt.up().retrieve('date'));
                }
                e.stop();
                return;
            } else if (elt.hasClassName('kronolithMonthDay')) {
                if (orig.hasClassName('kronolith-day')) {
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
            } else if (elt.hasClassName('kronolithEventsWeek') ||
                       elt.hasClassName('kronolithEventsWorkweek') ||
                       elt.hasClassName('kronolithAllDayContainer')) {
                var date = elt.retrieve('date');
                if (elt.hasClassName('kronolithAllDayContainer')) {
                    date += 'all';
                } else {
                    date = this.parseDate(date);
                    date.add(Math.round((e.pointerY() - elt.cumulativeOffset().top + elt.up('.kronolithViewBody').scrollTop) / (elt.hasClassName('kronolithEventsWeek') ? this.weekSizes.height : this.workweekSizes.height) * 2) * 30).minutes();
                    date = date.toString('yyyyMMddHHmm');
                }
                this.go('event:' + date);
                e.stop();
                return;
            } else if (elt.hasClassName('kronolithTaskCheckbox')) {
                var taskid = elt.up('tr.kronolithTaskRow', 0).retrieve('taskid'),
                    tasklist = elt.up('tr.kronolithTaskRow', 0).retrieve('tasklist');
                this.toggleCompletionClass(taskid);

                HordeCore.doAction('toggleCompletion', {
                    list: tasklist,
                    id: taskid
                }, {
                    callback: function(r) {
                        if (r.toggled) {
                            this.toggleCompletion(tasklist, taskid, r.toggled);
                            if (r.toggled !== true) {
                                this.toggleCompletionClass(taskid);
                            }
                        } else {
                            this.toggleCompletionClass(taskid);
                        }
                    }.bind(this)
                });

                e.stop();
                return;
            } else if (elt.hasClassName('kronolithCalendarSave')) {
                if (!elt.disabled) {
                    elt.disable();
                    if (!this.saveCalendar(elt.up('form'))) {
                        elt.enable();
                    }
                }
                e.stop();
                break;
            } else if (elt.hasClassName('kronolithCalendarContinue')) {
                if (elt.disabled) {
                    e.stop();
                    break;
                }

                elt.disable();
                var form = elt.up('form'),
                    type = form.id.replace(/kronolithCalendarForm/, ''),
                    i = 1;
                while (!$('kronolithCalendar' + type + i).visible()) {
                    i++;
                }
                if (type == 'remote') {
                    var params = { url: $F('kronolithCalendarremoteUrl') };
                    if (i == 1) {
                        if (!$F('kronolithCalendarremoteUrl')) {
                            HordeCore.notify(Kronolith.text.no_url, 'horde.warning');
                            e.stop();
                            break;
                        }

                        HordeCore.doAction('getRemoteInfo', params, {
                            asynchronous: false,
                            callback: function(r) {
                                if (r.success) {
                                    if (r.name) {
                                        $('kronolithCalendarremoteName').setValue(r.name);
                                    }
                                    if (r.desc) {
                                        $('kronolithCalendarremoteDescription').setValue(r.desc);
                                    }
                                    this.calendarNext(type);
                                    this.calendarNext(type);
                                } else if (r.auth) {
                                    this.calendarNext(type);
                                } else {
                                    elt.enable();
                                }
                            }.bind(this)
                        });

                    }
                    if (i == 2) {
                        if ($F('kronolithCalendarremoteUsername')) {
                            params.user = $F('kronolithCalendarremoteUsername');
                            params.password =  $F('kronolithCalendarremotePassword');
                        }

                        HordeCore.doAction('getRemoteInfo', params, {
                            callback: function(r) {
                                if (r.success) {
                                    if (r.name &&
                                        !$F('kronolithCalendarremoteName')) {
                                        $('kronolithCalendarremoteName').setValue(r.name);
                                    }
                                    if (r.desc &&
                                        !$F('kronolithCalendarremoteDescription')) {
                                        $('kronolithCalendarremoteDescription').setValue(r.desc);
                                    }
                                    this.calendarNext(type);
                                } else {
                                    if (r.auth) {
                                        HordeCore.notify(Kronolith.text.wrong_auth, 'horde.warning');
                                    }
                                    elt.enable();
                                }
                            }.bind(this)
                        });
                    }
                    e.stop();
                    break;
                }
                this.calendarNext(type);
                e.stop();
                break;
            } else if (elt.hasClassName('kronolithCalendarDelete')) {
                var form = elt.up('form'),
                    type = form.id.replace(/kronolithCalendarForm/, ''),
                    calendar = $F('kronolithCalendar' + type + 'Id');

                if ((type == 'tasklists' &&
                     !window.confirm(Kronolith.text.delete_tasklist)) ||
                    (type != 'tasklists' &&
                     !window.confirm(Kronolith.text.delete_calendar))) {
                    e.stop();
                    break;
                }

                if (!elt.disabled) {
                    elt.disable();

                    HordeCore.doAction('deleteCalendar', {
                        type: type,
                        calendar: calendar
                    }, {
                        callback: function(r) {
                            if (r.deleted) {
                                this.deleteCalendar(type, calendar);
                            }
                            this.closeRedBox();
                            this.go(this.lastLocation);
                        }.bind(this)
                    });
                }
                e.stop();
                break;
            } else if (elt.hasClassName('kronolithCalendarSubscribe') ||
                       elt.hasClassName('kronolithCalendarUnsubscribe')) {
                var form = elt.up('form');
                this.toggleCalendar($F(form.down('input[name=type]')),
                                    $F(form.down('input[name=calendar]')));
                this.closeRedBox();
                this.go(this.lastLocation);
                e.stop();
                break;
            } else if (elt.tagName == 'INPUT' &&
                       (elt.name == 'event_alarms[]' ||
                        elt.name == 'task[alarm_methods][]')) {
                if (elt.name == 'event_alarms[]') {
                    $('kronolithEventAlarmOn').setValue(1);
                    $('kronolithEventAlarmDefaultOff').setValue(1);
                } else {
                    $('kronolithTaskAlarmOn').setValue(1);
                    $('kronolithTaskAlarmDefaultOff').setValue(1);
                }
                if ($(elt.id + 'Params')) {
                    if (elt.getValue()) {
                        $(elt.id + 'Params').show();
                    } else {
                        $(elt.id + 'Params').hide();
                    }
                }
                break;
            }

            var calClass = elt.retrieve('calendarclass');
            if (calClass) {
                this.toggleCalendar(calClass, elt.retrieve('calendar'));
                e.stop();
                return;
            }

            elt = elt.up();
        }
        // Workaround Firebug bug.
        Prototype.emptyFunction();
    },

    /**
     * Handles date selections from a date picker.
     */
    datePickerHandler: function(e)
    {
        var field = e.element().previous();
        field.setValue(e.memo.toString(Kronolith.conf.date_format));
        this.updateTimeFields(field.identify());
    },

    /**
     * Handles moving an event to a different day in month view and all day
     * events in weekly/daily views.
     */
    onDrop: function(e)
    {
        var drop = e.element(),
            el = e.memo.element;

        if (drop == el.up()) {
            return;
        }

        var lastDate = this.parseDate(el.up().retrieve('date')),
            newDate = this.parseDate(drop.retrieve('date')),
            diff = newDate.subtract(lastDate),
            eventid = el.retrieve('eventid'),
            cal = el.retrieve('calendar'),
            viewDates = this.viewDates(this.date, this.view),
            start = viewDates[0].toString('yyyyMMdd'),
            end = viewDates[1].toString('yyyyMMdd'),
            sig = start + end + (Math.random() + '').slice(2),
            events = this.getCacheForDate(lastDate.toString('yyyyMMdd'), cal),
            attributes = $H({ offDays: diff }),
            event = events.find(function(e) { return e.key == eventid; });

        drop.insert(el);
        this.startLoading(cal, sig);
        if (event.value.r) {
            attributes.set('rday', lastDate);
            attributes.set('cstart', this.cacheStart);
            attributes.set('cend', this.cacheEnd);
        }
        var uatts = {
            cal: cal,
            id: eventid,
            view: this.view,
            sig: sig,
            view_start: start,
            view_end: end,
            att: Object.toJSON(attributes)
        },
        callback = function(r) {
          if (r.events) {
              // Check if this is the still the result of the
              // most current request.
              if (r.sig == this.eventsLoading[r.cal]) {
                  var days;
                  if ((this.view == 'month' &&
                       Kronolith.conf.max_events) ||
                      this.view == 'week' ||
                      this.view == 'workweek' ||
                      this.view == 'day') {
                      days = this.findEventDays(cal, eventid);
                  }
                  this.removeEvent(cal, eventid);
                  if (days && days.length) {
                      this.reRender(days);
                  }
              }
              $H(r.events).each(function(days) {
                  $H(days.value).each(function(event) {
                      if (event.value.c.startsWith('tasks/')) {
                          var tasklist = event.value.c.substr(6),
                              task = event.key.substr(6),
                              taskObject;
                          if (this.tcache.get('incomplete') &&
                              this.tcache.get('incomplete').get(tasklist) &&
                              this.tcache.get('incomplete').get(tasklist).get(task)) {
                              taskObject = this.tcache.get('incomplete').get(tasklist).get(task);
                              taskObject.due = Date.parse(event.value.s);
                              this.tcache.get('incomplete').get(tasklist).set(task, taskObject);
                          }
                      }
                  }, this);
              }, this);
          }
          this.loadEventsCallback(r, false);
          $H(r.events).each(function(days) {
              $H(days.value).each(function(event) {
                  if (event.key == eventid) {
                      this.refreshResources(days.key, cal, eventid, lastDate.toString('yyyyMMdd'), event);
                  }
              }.bind(this))
          }.bind(this));
      }.bind(this);

      if (event.value.mt) {
          $('kronolithEventDiv').hide();
          $('kronolithUpdateDiv').show();
          RedBox.showHtml($('kronolithEventDialog').show());
          this.uatts = uatts;
          this.ucb = callback;
      } else {
          this.doDragDropUpdate(uatts, callback);
      }
    },

    onDragStart: function(e)
    {
        if (this.view == 'month') {
            return;
        }

        var elt = e.element();

        if (elt.hasClassName('kronolithDragger')) {
            elt.up().addClassName('kronolith-selected');
            DragDrop.Drags.getDrag(elt).top = elt.cumulativeOffset().top;
        } else if (elt.hasClassName('kronolithEditable')) {
            elt.addClassName('kronolith-selected').setStyle({ left: 0, width: (this.view == 'week' || this.view == 'workweek') ? '90%' : '95%', zIndex: 1 });
        }

        this.scrollTop = $('kronolithView' + this.view.capitalize())
            .down('.kronolithViewBody')
            .scrollTop;
        this.scrollLast = this.scrollTop;
    },

    onDrag: function(e)
    {
        if (this.view == 'month') {
            return;
        }

        var elt = e.element(),
            drag = DragDrop.Drags.getDrag(elt);
            storage = this.view + 'Sizes',
            step = this[storage].height / 6;

            if (!drag.event) {
                return;
            }

        var event = drag.event.value;

        if (elt.hasClassName('kronolithDragger')) {
            // Resizing the event.
            var div = elt.up(),
                top = drag.ghost.cumulativeOffset().top,
                scrollTop = $('kronolithView' + this.view.capitalize()).down('.kronolithViewBody').scrollTop,
                offset = 0,
                height;

            // Check if view has scrolled since last call.
            if (scrollTop != this.scrollLast) {
                offset = scrollTop - this.scrollLast;
                this.scrollLast = scrollTop;
            }
            if (elt.hasClassName('kronolithDraggerTop')) {
                offset += top - drag.top;
                height = div.offsetHeight - offset;
                div.setStyle({
                    top: (div.offsetTop + offset) + 'px'
                });
                offset = drag.ghost.offsetTop;
                drag.top = top;
            } else {
                offset += top - drag.top;
                height = div.offsetHeight + offset;
                offset = div.offsetTop;
                drag.top = top;
            }
            div.setStyle({
                height: height + 'px'
            });

            this.calculateEventDates(event, storage, step, offset, height);
            drag.innerDiv.update('(' + event.start.toString(Kronolith.conf.time_format) + ' - ' + event.end.toString(Kronolith.conf.time_format) + ') ' + event.t.escapeHTML());
        } else if (elt.hasClassName('kronolithEditable')) {
            // Moving the event.
            if (Object.isUndefined(drag.innerDiv)) {
                drag.innerDiv = drag.ghost.down('.kronolith-event-info');
            }
            if ((this.view == 'week') || (this.view == 'workweek')) {
                var offsetX = Math.round(drag.ghost.offsetLeft / drag.stepX);
                event.offsetDays = offsetX;
                this.calculateEventDates(event, storage, step, drag.ghost.offsetTop, drag.divHeight, event.start.clone().addDays(offsetX), event.end.clone().addDays(offsetX));
            } else {
                event.offsetDays = 0;
                this.calculateEventDates(event, storage, step, drag.ghost.offsetTop, drag.divHeight);
            }
            event.offsetTop = drag.ghost.offsetTop - drag.startTop;
            drag.innerDiv.update('(' + event.start.toString(Kronolith.conf.time_format) + ' - ' + event.end.toString(Kronolith.conf.time_format) + ') ' + event.t.escapeHTML());
            elt.clonePosition(drag.ghost, { offsetLeft: (this.view == 'week' || this.view == 'workweek') ? -2 : 0 });
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
            event = drag.event;


        if (event.value.al) {
            return;
        }
        var date = drag.midnight,
            storage = this.view + 'Sizes',
            step = this[storage].height / 6,
            dates = this.viewDates(date, this.view),
            start = dates[0].dateString(),
            end = dates[1].dateString(),
            sig = start + end + (Math.random() + '').slice(2),
            element, attributes;

        div.removeClassName('kronolith-selected');
        if (!Object.isUndefined(drag.innerDiv)) {
            this.setEventText(drag.innerDiv, event.value);
        }
        this.startLoading(event.value.calendar, sig);
        if (!Object.isUndefined(event.value.offsetTop)) {
            attributes = $H({ offDays: event.value.offsetDays,
                              offMins: Math.round(event.value.offsetTop / step) * 10 });
            element = div;
        } else if (div.hasClassName('kronolithDraggerTop')) {
            attributes = $H({ start: event.value.start });
            element = div.up();
        } else if (div.hasClassName('kronolithDraggerBottom')) {
            attributes = $H({ end: event.value.end });
            element = div.up();
        } else {
            attributes = $H({ start: event.value.start,
                              end: event.value.end });
            element = div;
        }
        if (event.value.r) {
            attributes.set('rstart', event.value.s);
            attributes.set('rend', event.value.e);
            attributes.set('cstart', this.cacheStart);
            attributes.set('cend', this.cacheEnd);
        }
        element.retrieve('drags').invoke('destroy');
        var uatts = {
            cal: event.value.calendar,
            id: event.key,
            view: this.view,
            sig: sig,
            view_start: start,
            view_end: end,
            att: Object.toJSON(attributes)
        },
        callback = function(r) {
            // Check if this is the still the result of the most current
            // request.
            if (r.events &&
                r.sig == this.eventsLoading[r.cal]) {
                if (event.value.rs) {
                    var d = new Date(event.value.s);
                    this.refreshResources(d.toString('yyyyMMdd'), event.value.calendar, event.key)
                }
                this.removeEvent(event.value.calendar, event.key);
            }
            this.loadEventsCallback(r, false);
        }.bind(this);

        if (event.value.mt) {
            $('kronolithEventDiv').hide();
            $('kronolithUpdateDiv').show();
            RedBox.showHtml($('kronolithEventDialog').show());
            this.uatts = uatts;
            this.ucb = callback;
        } else {
            this.doDragDropUpdate(uatts, callback);
        }
    },

    doDragDropUpdate: function(att, cb)
    {
        HordeCore.doAction('updateEvent', att, {
            callback: cb
        });
    },

    /**
     * Refresh any resource calendars bound to the given just-updated event.
     * Clears the old resource event from UI and cache, and clears the cache
     * for the days of the new event, in order to allow listEvents to refresh
     * the UI.
     *
     * @param  string dt       The current/new date for the event (yyyyMMdd).
     * @param  string cal      The calendar the event exists in.
     * @param  string eventid  The eventid that is changing.
     * @param  string last_dt  The previous date for the event, if known. (yyyyMMdd).
     * @param  object event    The event object (if a new event) dt is ignored.
     *
     */
    refreshResources: function(dt, cal, eventid, last_dt, event)
    {
        var events = this.getCacheForDate(dt, cal),
            update_cals = [], r_dates;

        if (!event) {
            event = events.find(function(e) { return e.key == eventid; });
        }
        if (!dt) {
            dt = new Date(event.value.s);
        } else {
            dt = this.parseDate(dt);
        }
        if (event) {
            $H(event.value.rs).each(function(r) {
                var r_cal = ['resource', r.value.calendar],
                    r_events = this.getCacheForDate(last_dt, r_cal.join('|')),
                    r_event, day, end;

                if (r_events) {
                    r_event = r_events.find(function(e) { return e.value.uid == event.value.uid });
                    if (r_event) {
                        this.removeEvent(r_cal, r_event.key);
                        day = new Date(r_event.value.s);
                        end = new Date(r_event.value.s);
                        while (!day.isAfter(end)) {
                            this.deleteCache(r_cal, null, day.toString('yyyyMMdd'));
                            day.add(1).day();
                        }
                        day = new Date(event.value.s);
                        end = new Date(event.value.e);

                        while (!day.isAfter(end)) {
                            this.deleteCache(r_cal, null, day.toString('yyyyMMdd'));
                            day.add(1).day();
                        }
                    } else {
                        // Don't know the previous date/time so just nuke the cache.
                       this.deleteCache(r_cal);
                    }
                } else {
                    this.deleteCache(r_cal);
                }
                update_cals.push(r_cal);
            }.bind(this));

            if (update_cals.length) {
                dates = this.viewDates(dt, this.view);
                // Ensure we also grab the full length of the events.
                if (dates[0].isAfter(dt)) {
                    dates[0] = dt;
                }
                var dt_end = new Date(event.value.e);
                if (dt_end.isAfter(dates[1])) {
                    dates[1] = dt_end;
                }
                this.loadEvents(dates[0], dates[1], this.view, update_cals);
            }
        }
    },

    editEvent: function(calendar, id, date, title)
    {
        if (this.redBoxLoading) {
            return;
        }
        if (Object.isUndefined(HordeImple.AutoCompleter.kronolithEventTags)) {
            this.editEvent.bind(this, calendar, id, date).defer();
            return;
        }

        this.closeRedBox();
        this.quickClose();
        this.redBoxOnDisplay = RedBox.onDisplay;
        RedBox.onDisplay = function() {
            if (this.redBoxOnDisplay) {
                this.redBoxOnDisplay();
            }
            try {
                $('kronolithEventForm').focusFirstElement();
            } catch(e) {}
            if (Kronolith.conf.maps.driver &&
                $('kronolithEventLinkMap').up().hasClassName('horde-active') &&
                !this.mapInitialized) {

                this.initializeMap();
            }
            RedBox.onDisplay = this.redBoxOnDisplay;
        }.bind(this);
        this.attendees = [];
        this.resources = [];
        this.updateCalendarDropDown('kronolithEventTarget');
        this.toggleAllDay(false);
        this.openTab($('kronolithEventForm').down('.tabset a.kronolithTabLink'));
        this.disableAlarmMethods('Event');
        this.knl.kronolithEventStartTime.markSelected();
        this.knl.kronolithEventEndTime.markSelected();
        $('kronolithEventForm').reset();
        this.resetMap();
        HordeImple.AutoCompleter.kronolithEventAttendees.reset();
        HordeImple.AutoCompleter.kronolithEventTags.reset();
        HordeImple.AutoCompleter.kronolithEventResources.reset();
        if (Kronolith.conf.maps.driver) {
            $('kronolithEventMapLink').hide();
        }
        $('kronolithEventSave').show().enable();
        $('kronolithEventSaveAsNew').show().enable();
        $('kronolithEventDelete').show().enable();
        $('kronolithEventDeleteConfirm').enable();
        $('kronolithEventTarget').show();
        $('kronolithEventTargetRO').hide();
        $('kronolithEventForm').down('.kronolithFormActions .kronolithSeparator').show();
        $('kronolithEventExceptions').clear();
        if (id) {
            // An id passed to this function indicates we are editing an event.
            RedBox.loading();
            var attributes = { cal: calendar, id: id, date: date };
            // Need the current st and et of this instance.
            var events = this.getCacheForDate(date.toString('yyyyMMdd'), calendar);
            if (events) {
                var ev = events.find(function(e) { return e.key == id; });
                if (ev[1].r) {
                    attributes.rsd = ev[1].start.dateString();
                    attributes.red = ev[1].end.dateString();
                }
            }
            HordeCore.doAction('getEvent', attributes, {
                callback: this.editEventCallback.bind(this)
            });
            $('kronolithEventTopTags').update();
        } else {
            // This is a new event.
            HordeCore.doAction('listTopTags', {}, {
                callback: this.topTagsCallback.curry('kronolithEventTopTags', 'kronolithEventTag')
            });
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
            if (title) {
                $('kronolithEventTitle').setValue(title);
            }
            $('kronolithEventId').clear();
            $('kronolithEventCalendar').clear();
            $('kronolithEventTarget').setValue(Kronolith.conf.default_calendar);
            $('kronolithEventDelete').hide();
            $('kronolithEventStartDate').setValue(d.toString(Kronolith.conf.date_format));
            $('kronolithEventStartTime').setValue(d.toString(Kronolith.conf.time_format));
            this.updateFBDate(d);
            d.add(1).hour();
            this.duration = 60;
            $('kronolithEventEndDate').setValue(d.toString(Kronolith.conf.date_format));
            $('kronolithEventEndTime').setValue(d.toString(Kronolith.conf.time_format));
            $('kronolithEventLinkExport').up('span').hide();
            $('kronolithEventSaveAsNew').hide();
            $('kronolithEventUrlDisplay').hide();
            $('kronolithEventUrl').show();
            this.toggleRecurrence(true, 'None');
            $('kronolithEventEditRecur').hide();
            this.enableAlarm('Event', Kronolith.conf.default_alarm);
            this.redBoxLoading = true;
            RedBox.showHtml($('kronolithEventDialog').show());
        }
    },

    /**
     * Generates ajax request parameters for requests to save events.
     *
     * @return object  An object with request parameters.
     */
    saveEventParams: function()
    {
        var viewDates = this.viewDates(this.date, this.view),
            params = {
                sig: viewDates[0].dateString() + viewDates[1].dateString(),
                view: this.view
            };
        if (this.cacheStart) {
            params.view_start = this.cacheStart.dateString();
            params.view_end = this.cacheEnd.dateString();
        }
        return params;
    },

    /**
     * Submits the event edit form to create or update an event.
     */
    saveEvent: function(asnew)
    {
        this.validateEvent(asnew);
    },

    /**
     * Perform any preliminary checks necessary. doSaveEvent will be called from
     * the callback if checks are successful.
     *
     */
    validateEvent: function(asnew)
    {
        if (this.wrongFormat.size()) {
            HordeCore.notify(Kronolith.text.fix_form_values, 'horde.warning');
            return;
        }

        // Check that there are no conflicts.
        if (Kronolith.conf.has_resources && $F('kronolithEventResourceIds')) {
            HordeCore.doAction(
                'checkResources',
                {
                    s: this.getDate('start').toISOString(),
                    e: this.getDate('end').toISOString(),
                    i: $F('kronolithEventId'),
                    c: $F('kronolithEventCalendar'),
                    r: $F('kronolithEventResourceIds')
                },
                {
                    callback: this.validateEventCallback.curry(asnew).bind(this)
                }
            );
        } else {
            this.validateEventCallback(asnew, {});
        }
    },

    validateEventCallback: function(asnew, r)
    {
        var conflict = false;

        $H(r).each(function(a) {
            // 3 == Kronolith::RESPONSE_DECLINED
            if (a.value == 3) {
                $('kronolithEventDiv').hide();
                $('kronolithConflictDiv').show();
                conflict = true;
                return;
            }
        });
        if (!conflict) {
            this.doSaveEvent(asnew);
        }
    },

    doSaveEvent: function(asnew)
    {
        var cal = $F('kronolithEventCalendar'),
            target = $F('kronolithEventTarget'),
            eventid = $F('kronolithEventId'),
            params;

        if (this.mapInitialized) {
            $('kronolithEventMapZoom').value = this.map.getZoom();
        }

        params = $H($('kronolithEventForm').serialize({ hash: true }))
            .merge(this.saveEventParams());
        params.set('as_new', asnew ? 1 : 0);
        if (this.cacheStart) {
            params.set('cstart', this.cacheStart.toISOString());
            params.set('cend', this.cacheEnd.toISOString());
        }
        HordeImple.AutoCompleter.kronolithEventTags.shutdown();
        $('kronolithEventSave').disable();
        $('kronolithEventSaveAsNew').disable();
        $('kronolithEventDelete').disable();
        this.startLoading(target, params.get('sig'));
        HordeCore.doAction('saveEvent', params, {
            callback: function(r) {
                if (!asnew && r.events && eventid) {
                    this.removeEvent(cal, eventid);
                }
                this.loadEventsCallback(r, false);

                // Refresh bound exceptions
                var calendar = cal.split('|'), refreshed = false;
                $H(r.events).each(function(d) {
                    $H(d.value).each(function(evt) {
                        if (evt.value.bid) {
                            var cache = this.getCacheForDate(this.findEventDays(cal, evt.key, cal));
                            cache.each(function(entry) {
                                if (entry.value.bid == evt.value.bid && evt.value.c != calendar[1]) {
                                    this.removeEvent(cal, entry.key);
                                }
                            }.bind(this));
                        }
                        if (!refreshed && ((evt.key == eventid) || !eventid) && evt.value.rs) {
                            this.refreshResources(null, cal, eventid, false, evt);
                            refreshed = true;
                        }
                    }.bind(this))
                }.bind(this));

                if (r.events) {
                    this.resetMap();
                    this.closeRedBox();
                    this.go(this.lastLocation);
                } else {
                    $('kronolithEventSave').enable();
                    $('kronolithEventSaveAsNew').enable();
                    $('kronolithEventDelete').enable();
                }
                $('kronolithUpdateDiv').hide();
                $('kronolithConflictDiv').hide();
                $('kronolithEventDiv').show();
            }.bind(this)
        });
    },

    quickSaveEvent: function()
    {
        var text = $F('kronolithQuickinsertQ'),
            cal = $F('kronolithQuickinsertCalendars'),
            params;

        params = $H($('kronolithEventForm').serialize({ hash: true }))
            .merge(this.saveEventParams());
        params.set('text', text);
        params.set('cal', cal);

        this.closeRedBox();
        this.startLoading(cal, params.get('sig'));
        HordeCore.doAction('quickSaveEvent', params, {
            callback: function(r) {
                this.loadEventsCallback(r, false);
                if (r.error) {
                    this.editEvent(null, null, null, text);
                } else {
                    $('kronolithQuickinsertQ').value = '';
                }
             }.bind(this)
         });
    },

    /**
     * Closes and resets the quick event form.
     */
    quickClose: function()
    {
        $('kronolithQuickinsertQ').value = '';
        if ($('kronolithQuicktaskQ')) {
            $('kronolithQuicktaskQ').value = '';
        }
        this.closeRedBox();
    },

    topTagsCallback: function(update, tagclass, r)
    {
        $('kronolithEventTabTags').select('label').invoke('show');
        if (!r.tags) {
            $(update).update();
            return;
        }

        var t = new Element('ul', { className: 'horde-tags' });
        r.tags.each(function(tag) {
            if (tag == null) {
                return;
            }
            t.insert(new Element('li', { className: tagclass }).update(tag.escapeHTML()));
        });
        $(update).update(t);
    },

    /**
     * Callback method for showing event forms.
     *
     * @param object r  The ajax response object.
     */
    editEventCallback: function(r)
    {
        if (!r.event) {
            RedBox.close();
            this.go(this.lastLocation);
            return;
        }

        var ev = r.event;

        if (!Object.isUndefined(ev.ln)) {
            this.loadPage(ev.ln);
            this.closeRedBox();
            return;
        }

        /* Basic information */
        $('kronolithEventId').setValue(ev.id);
        $('kronolithEventCalendar').setValue(ev.ty + '|' + ev.c);
        $('kronolithEventTarget').setValue(ev.ty + '|' + ev.c);
        $('kronolithEventTargetRO').update(Kronolith.conf.calendars[ev.ty][ev.c].name.escapeHTML());
        $('kronolithEventTitle').setValue(ev.t);
        $('kronolithEventLocation').setValue(ev.l);
        $('kronolithEventTimezone').setValue(ev.tz);
        if (ev.l && Kronolith.conf.maps.driver) {
            $('kronolithEventMapLink').show();
        }
        if (ev.uhl) {
            $('kronolithEventUrlDisplay').down().update(ev.uhl);
            $('kronolithEventUrlDisplay').show();
            $('kronolithEventUrl').hide();
        }
        else {
            $('kronolithEventUrlDisplay').hide();
            $('kronolithEventUrl').show();
        }

        if (ev.u) {
            $('kronolithEventUrl').setValue(ev.u);
        }

        $('kronolithEventAllday').setValue(ev.al);

        if (ev.r && ev.rsd && ev.red) {
            // Save the original datetime, so we can properly create the
            // exception.
            var osd = Date.parse(ev.rsd + ' ' + ev.st);
            var oed = Date.parse(ev.red + ' ' + ev.et);

            $('kronolithEventRecurOStart').setValue(osd.toString('s'));
            $('kronolithEventRecurOEnd').setValue(oed.toString('s'));

            // ...and put the same value in the form field to replace the
            // date of the initial series.
            $('kronolithEventStartDate').setValue(ev.sd);
            $('kronolithEventEndDate').setValue(ev.ed);
            // Save the current datetime in case we are not editing 'all'
            this.orstart = ev.rsd;
            this.orend = ev.red;
        } else {
            $('kronolithEventStartDate').setValue(ev.sd);
            $('kronolithEventEndDate').setValue(ev.ed);
            $('kronolithEventRecurEnd').clear();
            $('kronolithEventRecurOStart').clear();
            $('kronolithEventRecurOEnd').clear();
            this.orstart = null;
            this.orend = null;
        }

        $('kronolithEventStartTime').setValue(ev.st);
        this.knl.kronolithEventStartTime.setSelected(ev.st);
        this.updateFBDate(Date.parseExact(ev.sd, Kronolith.conf.date_format));
        $('kronolithEventEndTime').setValue(ev.et);
        this.knl.kronolithEventEndTime.setSelected(ev.et);
        this.duration = Math.abs(Date.parse(ev.e).getTime() - Date.parse(ev.s).getTime()) / 60000;
        this.toggleAllDay(ev.al);
        $('kronolithEventStatus').setValue(ev.x);
        $('kronolithEventDescription').setValue(ev.d);
        $('kronolithEventPrivate').setValue(ev.pv);
        $('kronolithEventLinkExport').up('span').show();
        $('kronolithEventExport').href = Kronolith.conf.URI_EVENT_EXPORT.interpolate({ id: ev.id, calendar: ev.c, type: ev.ty });

        /* Alarm */
        if (ev.a) {
            this.enableAlarm('Event', ev.a);
            if (ev.m) {
                $('kronolithEventAlarmDefaultOff').checked = true;
                $H(ev.m).each(function(method) {
                    $('kronolithEventAlarm' + method.key).setValue(1);
                    if ($('kronolithEventAlarm' + method.key + 'Params')) {
                        $('kronolithEventAlarm' + method.key + 'Params').show();
                        $H(method.value).each(function(param) {
                            var input = $('kronolithEventAlarmParam' + param.key);
                            if (input.type == 'radio') {
                                input.up('form').select('input[type=radio]').each(function(radio) {
                                    if (radio.name == input.name &&
                                        radio.value == param.value) {
                                        radio.setValue(1);
                                        throw $break;
                                    }
                                });
                            } else {
                                input.setValue(param.value);
                            }
                        });
                    }
                });
            }
        } else {
            $('kronolithEventAlarmOff').setValue(true);
        }

        /* Recurrence */
        if (ev.r) {
            this.setRecurrenceFields(true, ev.r);
            $('kronolithRecurDelete').show();
            $('kronolithNoRecurDelete').hide();
            $('kronolithEventEditRecur').show();
            this.recurs = true;
        } else if (ev.bid) {
            $('kronolithRecurDelete').hide();
            $('kronolithNoRecurDelete').show();
            $('kronolithEventEditRecur').hide();
            var div = $('kronolithEventRepeatException');
            div.down('span').update(ev.eod);
            this.toggleRecurrence(true, 'Exception');
            this.recurs = false;
        } else {
            $('kronolithRecurDelete').hide();
            $('kronolithNoRecurDelete').show();
            $('kronolithEventEditRecur').hide();
            this.toggleRecurrence(true, 'None');
            this.recurs = false;
        }

        /* Attendees */
        if (!Object.isUndefined(ev.at)) {
            HordeImple.AutoCompleter.kronolithEventAttendees.reset(ev.at.pluck('l'));
            ev.at.each(this.addAttendee.bind(this));
            if (this.fbLoading) {
                $('kronolithFBLoading').show();
            }
        }

        /* Resources */
        if (!Object.isUndefined(ev.rs)) {
            var rs = $H(ev.rs);
            HordeImple.AutoCompleter.kronolithEventResources.reset(rs.values().pluck('name'));
            rs.each(function(r) { this.addResource(r.value, r.key); }.bind(this));
            if (this.fbLoading) {
                $('kronolithResourceFBLoading').show();
            }
        }

        /* Tags */
        HordeImple.AutoCompleter.kronolithEventTags.reset(ev.tg);

        /* Geo */
        if (ev.gl) {
            $('kronolithEventLocationLat').value = ev.gl.lat;
            $('kronolithEventLocationLon').value = ev.gl.lon;
            $('kronolithEventMapZoom').value = Math.max(1, ev.gl.zoom);
        }

        if (!ev.pe) {
            $('kronolithEventSave').hide();
            HordeImple.AutoCompleter.kronolithEventTags.disable();
            $('kronolithEventTabTags').select('label').invoke('hide');
        } else {
            HordeCore.doAction('listTopTags', {}, {
                callback: this.topTagsCallback.curry('kronolithEventTopTags', 'kronolithEventTag')
            });
        }
        if (!ev.pd) {
            $('kronolithEventDelete').hide();
            $('kronolithEventTarget').hide();
            $('kronolithEventTargetRO').show();
        }

        this.setTitle(ev.t);
        this.redBoxLoading = true;
        RedBox.showHtml($('kronolithEventDialog').show());

        /* Hide alarm message for this event. */
        if (r.msgs) {
            r.msgs = r.msgs.reject(function(msg) {
                if (msg.type != 'horde.alarm') {
                    return false;
                }
                var alarm = msg.flags.alarm;
                if (alarm.params && alarm.params.notify &&
                    alarm.params.notify.show &&
                    alarm.params.notify.show.calendar &&
                    alarm.params.notify.show.event &&
                    alarm.params.notify.show.calendar == ev.c &&
                    alarm.params.notify.show.event == ev.id) {
                    return true;
                }
                return false;
            });
        }
    },

    /**
     * Adds an attendee row to the free/busy table.
     *
     * @param object attendee  An attendee object with the properties:
     *                         - e: email address
     *                         - l: the display name of the attendee
     */
    addAttendee: function(attendee)
    {
        if (typeof attendee == 'string') {
            if (attendee.include('@')) {
                HordeCore.doAction('parseEmailAddress', {
                    email: attendee
                }, {
                    callback: function (r) {
                        if (r.email) {
                            this.addAttendee({ e: r.email, l: attendee });
                        }
                    }.bind(this)
                });
                return;
            } else {
                attendee = { l: attendee };
            }
        }

        if (attendee.e) {
            this.attendees.push(attendee);
            this.fbLoading++;
            HordeCore.doAction('getFreeBusy', {
                email: attendee.e
            }, {
                callback: function(r) {
                    this.fbLoading--;
                    if (!this.fbLoading) {
                        $('kronolithFBLoading').hide();
                    }
                    if (!Object.isUndefined(r.fb)) {
                        this.freeBusy.get(attendee.l)[1] = r.fb;
                        this.insertFreeBusy(attendee.l, this.getFBDate());
                    }
                }.bind(this)
            });
        }

        var tr = new Element('tr'), response, i;
        this.freeBusy.set(attendee.l, [ tr ]);
        attendee.r = attendee.r || 1;
        switch (attendee.r) {
            case 1: response = 'None'; break;
            case 2: response = 'Accepted'; break;
            case 3: response = 'Declined'; break;
            case 4: response = 'Tentative'; break;
        }
        tr.insert(new Element('td')
                  .writeAttribute('title', attendee.l)
                  .addClassName('kronolithAttendee' + response)
                  .insert(attendee.e ? attendee.e.escapeHTML() : attendee.l.escapeHTML()));
        for (i = 0; i < 24; i++) {
            tr.insert(new Element('td', { className: 'kronolithFBUnknown' }));
        }
        $('kronolithEventAttendeesList').down('tbody').insert(tr);
    },

    resetFBRows: function()
    {
        this.attendees.each(function(attendee) {
            var row = this.freeBusy.get(attendee.l)[0];
            row.update();

            attendee.r = attendee.r || 1;
            switch (attendee.r) {
                case 1: response = 'None'; break;
                case 2: response = 'Accepted'; break;
                case 3: response = 'Declined'; break;
                case 4: response = 'Tentative'; break;
            }
            row.insert(new Element('td')
                      .writeAttribute('title', attendee.l)
                      .addClassName('kronolithAttendee' + response)
                      .insert(attendee.e ? attendee.e.escapeHTML() : attendee.l.escapeHTML()));
            for (i = 0; i < 24; i++) {
                row.insert(new Element('td', { className: 'kronolithFBUnknown' }));
            }
        }.bind(this));
        this.resources.each(function(resource) {
            var row = this.freeBusy.get(resource)[0],
                tdone = row.down('td');
            row.update();
            row.update(tdone);
            for (i = 0; i < 24; i++) {
                row.insert(new Element('td', { className: 'kronolithFBUnknown' }));
            }
        }.bind(this));
    },

    addResource: function(resource, id)
    {
        var v, response = 1;
        if (!id) {
            // User entered
            this.resourceACCache.choices.each(function(i) {
                if (i.name == resource) {
                    v = i.code;
                    throw $break;
                } else {
                    v = false;
                }
            }.bind(this));
        } else {
            // Populating from an edit event action
            v = id;
            response = resource.response;
            resource = resource.name;
        }

        switch (response) {
            case 1: response = 'None'; break;
            case 2: response = 'Accepted'; break;
            case 3: response = 'Declined'; break;
            case 4: response = 'Tentative'; break;
        }
        var att = {
            'resource': v
        },
        tr, i;
        if (att.resource) {
            this.fbLoading++;
            HordeCore.doAction('getFreeBusy', att, {
                callback: this.addResourceCallback.curry(resource).bind(this)
            });
            tr = new Element('tr');
            this.freeBusy.set(resource, [ tr ]);
            tr.insert(new Element('td')
                .writeAttribute('title', resource)
                .addClassName('kronolithAttendee' + response)
                .insert(resource.escapeHTML()));
            for (i = 0; i < 24; i++) {
                tr.insert(new Element('td', { className: 'kronolithFBUnknown' }));
            }
            $('kronolithEventResourcesList').down('tbody').insert(tr);
            this.resourceACCache.map.set(resource, v);
            $('kronolithEventResourceIds').value = this.resourceACCache.map.values();
        } else {
            HordeCore.notify(Kronolith.text.unknown_resource + ': ' + resource, 'horde.error');
        }
    },

    removeResource: function(resource)
    {
        var row = this.freeBusy.get(resource)[0];
        row.purge();
        row.remove();
        this.resourceACCache.map.unset(resource);
        $('kronolithEventResourceIds').value = this.resourceACCache.map.values();
    },

    addResourceCallback: function(resource, r)
    {
        this.fbLoading--;
        if (!this.fbLoading) {
            $('kronolithResourceFBLoading').hide();
        }
        if (Object.isUndefined(r.fb)) {
            return;
        }
        this.resources.push(resource);
        this.freeBusy.get(resource)[1] = r.fb;
        this.insertFreeBusy(resource);
    },

    /**
     * Removes an attendee row from the free/busy table.
     *
     * @param string attendee  The display name of the attendee.
     */
    removeAttendee: function(attendee)
    {
        var row = this.freeBusy.get(attendee)[0];
        row.purge();
        row.remove();
    },

    normalizeAttendee: function(attendee)
    {
        var pattern = /:(.*);/;
        var match = pattern.exec(attendee);
        if (match) {
           return match[1].split(',');
        }
        return [attendee];
    },

    checkOrganizerAsAttendee: function()
    {
        if (HordeImple.AutoCompleter.kronolithEventAttendees.selectedItems.length == 1 &&
            HordeImple.AutoCompleter.kronolithEventAttendees.selectedItems.first().rawValue != Kronolith.conf.email) {
            // Invite the organizer of this event to the new event.
            HordeImple.AutoCompleter.kronolithEventAttendees.addNewItemNode(Kronolith.conf.email);
            this.addAttendee(Kronolith.conf.email);
        }
    },

    getFBDate: function ()
    {
        var startDate = $('kronolithFBDate').innerHTML.split(' ');
        if (startDate.length > 1) {
            startDate = startDate[1];
        } else {
            startDate = startDate[0];
        }
        return Date.parseExact(startDate, Kronolith.conf.date_format);
    },

    /**
     * Updates rows with free/busy information in the attendees table.
     *
     * @param string attendee  An attendee display name as the free/busy
     *                         identifier.
     * @param date   start     An optinal start date for f/b info. If omitted,
     *                         $('kronolithEventStartDate') is used.
     */
    insertFreeBusy: function(attendee, start)
    {
        if (!$('kronolithEventDialog').visible() ||
            !this.freeBusy.get(attendee)) {
            return;
        }
        var fb = this.freeBusy.get(attendee)[1],
            tr = this.freeBusy.get(attendee)[0],
            td = tr.select('td')[1],
            div = td.down('div'), start;
        if (!fb) {
            return;
        }

        if (!td.getWidth()) {
            this.insertFreeBusy.bind(this, attendee, start).defer();
            return;
        }

        if (div) {
            div.purge();
            div.remove();
        }
        if (!start) {
            start = Date.parseExact($F('kronolithEventStartDate'), Kronolith.conf.date_format);
        }
        var end = start.clone().add(1).days(),
            width = td.getWidth(),
            fbs = this.parseDate(fb.s),
            fbe = this.parseDate(fb.e);


        if (start.isBefore(fbs) || end.isBefore(fbs) || start.isAfter(fbe)) {
            return;
        }

        tr.select('td').each(function(td, i) {
            if (i != 0) {
                td.className = 'kronolithFBFree';
            }
            i++;
        });
        div = new Element('div').setStyle({ position: 'relative', height: td.offsetHeight + 'px' });
        td.insert(div);
        $H(fb.b).each(function(busy) {
            var left, from = Date.parse(busy.key).addSeconds(1),
            to = Date.parse(busy.value).addSeconds(1);
            if (!end.isAfter(from) || to.isBefore(start)) {
                return;
            }
            if (from.isBefore(start)) {
                from = start.clone();
            }
            if (to.isAfter(end)) {
                to = end.clone();
            }
            if (to.getHours() === 0 && to.getMinutes() === 0) {
                to.add(-1).minutes();
            }
            left = from.getHours() + from.getMinutes() / 60;
            div.insert(new Element('div', { className: 'kronolithFBBusy' }).setStyle({ zIndex: 1, top: 0, left: (left * width) + 'px', width: (((to.getHours() + to.getMinutes() / 60) - left) * width) + 'px' }));
        });

    },

    fbStartDateOnChange: function()
    {
        if (!$F('kronolithEventStartDate')) {
          this._checkDate($('kronolithEventStartDate'));
          return;
        }
        this.fbStartDateHandler(Date.parseExact($F('kronolithEventStartDate'), Kronolith.conf.date_format));
    },

    /**
     * @param Date start  The start date.
     */
    fbStartDateHandler: function(start)
    {
        this.updateFBDate(start);
        this.resetFBRows();
        // Need to check visisbility - multiple changes will break the display
        // due to the use of .defer() in insertFreeBusy().
        if ($('kronolithEventTabAttendees').visible()) {
            this.attendeeStartDateHandler(start);
        }
        if ($('kronolithEventTabResources').visible()) {
            this.resourceStartDateHandler(start);
        }
    },

    attendeeStartDateHandler: function(start)
    {
        this.attendees.each(function(attendee) {
            this.insertFreeBusy(attendee.l, start);
        }, this);
    },

    resourceStartDateHandler: function(start)
    {
        this.resources.each(function(resource) {
            this.insertFreeBusy(resource, start);
        }, this);
    },

    nextFreebusy: function()
    {
        this.fbStartDateHandler(this.getFBDate().addDays(1));
    },

    prevFreebusy: function()
    {
        this.fbStartDateHandler(this.getFBDate().addDays(-1));
    },

    /**
     * @start Date object
     */
    updateFBDate: function(start)
    {
        $('kronolithFBDate').update(start.toString('dddd') + ' ' + start.toString(Kronolith.conf.date_format));
        $('kronolithResourceFBDate').update(start.toString('dddd') + ' ' + start.toString(Kronolith.conf.date_format));
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
        var end = this.getDate('end'),
            old = $('kronolithEventStartTimeLabel').getStyle('visibility') == 'hidden';
        if (Object.isUndefined(on)) {
            on = $('kronolithEventStartTimeLabel').getStyle('visibility') == 'visible';
        }
        if (end) {
            if (on) {
                if (end.getHours() == 0 && end.getMinutes() == 0) {
                    end.add(-1).minute();
                }
            } else if (old) {
                end.setHours(23);
                end.setMinutes(59);
            }
            $('kronolithEventEndDate').setValue(end.toString(Kronolith.conf.date_format));
            $('kronolithEventEndTime').setValue(end.toString(Kronolith.conf.time_format));
        }
        $('kronolithEventStartTimeLabel').setStyle({ visibility: on ? 'hidden' : 'visible' });
        $('kronolithEventEndTimeLabel').setStyle({ visibility: on ? 'hidden' : 'visible' });
    },

    /**
     * Enables the alarm in the event or task form and sets the correct value
     * and unit.
     *
     * @param string type    The object type, either 'Event' or 'Task'.
     * @param integer alarm  The alarm time in seconds.
     */
    enableAlarm: function(type, alarm) {
        if (!alarm) {
            return;
        }
        type = 'kronolith' + type + 'Alarm';
        $(type + 'On').setValue(true);
        [10080, 1440, 60, 1].each(function(unit) {
            if (alarm % unit === 0) {
                $(type + 'Value').setValue(alarm / unit);
                $(type + 'Unit').setValue(unit);
                throw $break;
            }
        });
    },

    /**
     * Disables all custom alarm methods in the event form.
     */
    disableAlarmMethods: function(type) {
        $('kronolith' + type + 'TabReminder').select('input').each(function(input) {
            if (input.name == (type == 'Event' ? 'event_alarms[]' : 'task[alarm_methods][]')) {
                input.setValue(0);
                if ($(input.id + 'Params')) {
                    $(input.id + 'Params').hide();
                }
            }
        });
    },

    /**
     * Toggles the recurrence fields of the event and task edit forms.
     *
     * @param boolean event  Whether to use the event form.
     * @param string recur   The recurrence part of the field name, i.e. 'None',
     *                       'Daily', etc.
     */
    toggleRecurrence: function(event, recur)
    {
        var prefix = 'kronolith' + (event ? 'Event' : 'Task');
        if (recur == 'Exception') {
            if (!$(prefix + 'RepeatException').visible()) {
                $(prefix + 'TabRecur').select('div').invoke('hide');
                $(prefix + 'RepeatException').show();
            }
        } else if (recur != 'None') {
            var div = $(prefix + 'Repeat' + recur),
                length = $(prefix + 'RepeatLength');
            this.lastRecurType = recur;
            if (!div.visible()) {
                $(prefix + 'TabRecur').select('div').invoke('hide');
                div.show();
                length.show();
                $(prefix + 'RepeatType').show();
            }
            switch (recur) {
            case 'Daily':
            case 'Weekly':
            case 'Monthly':
            case 'Yearly':
                var recurLower = recur.toLowerCase();
                if (div.down('input[name=recur_' + recurLower + '][value=1]').checked) {
                    div.down('input[name=recur_' + recurLower + '_interval]').disable();
                } else {
                    div.down('input[name=recur_' + recurLower + '_interval]').enable();
                }
                break;
            }

            if (length.down('input[name=recur_end_type][value=date]').checked) {
                $(prefix + 'RecurDate').enable();
                $(prefix + 'RecurPicker').setStyle({ visibility: 'visible' });
            } else {
                $(prefix + 'RecurDate').disable();
                $(prefix + 'RecurPicker').setStyle({ visibility: 'hidden' });
            }
            if (length.down('input[name=recur_end_type][value=count]').checked) {
                $(prefix + 'RecurCount').enable();
            } else {
                $(prefix + 'RecurCount').disable();
            }
        } else {
            $(prefix + 'TabRecur').select('div').invoke('hide');
            $(prefix + 'RepeatType').show();
        }
    },

    /**
     * Fills the recurrence fields of the event and task edit forms.
     *
     * @param boolean event  Whether to use the event form.
     * @param object recur   The recurrence object from the ajax response.
     */
    setRecurrenceFields: function(event, recur)
    {
        var scheme = Kronolith.conf.recur[recur.t],
            schemeLower = scheme.toLowerCase(),
            prefix = 'kronolith' + (event ? 'Event' : 'Task'),
            div = $(prefix + 'Repeat' + scheme);
        $(prefix + 'Link' + scheme).setValue(true);
        if (scheme == 'Monthly' || scheme == 'Yearly') {
            div.down('input[name=recur_' + schemeLower + '_scheme][value=' + recur.t + ']').setValue(true);
        }
        if (scheme == 'Weekly') {
            div.select('input[type=checkbox]').each(function(input) {
                if (input.name == 'weekly[]' &&
                    input.value & recur.d) {
                    input.setValue(true);
                }
            });
        }
        if (recur.i == 1) {
            div.down('input[name=recur_' + schemeLower + '][value=1]').setValue(true);
        } else {
            div.down('input[name=recur_' + schemeLower + '][value=0]').setValue(true);
            div.down('input[name=recur_' + schemeLower + '_interval]').setValue(recur.i);
        }
        if (!Object.isUndefined(recur.e)) {
            $(prefix + 'RepeatLength').down('input[name=recur_end_type][value=date]').setValue(true);
            $(prefix + 'RecurDate').setValue(Date.parse(recur.e).toString(Kronolith.conf.date_format));
        } else if (!Object.isUndefined(recur.c)) {
            $(prefix + 'RepeatLength').down('input[name=recur_end_type][value=count]').setValue(true);
            $(prefix + 'RecurCount').setValue(recur.c);
        } else {
            $(prefix + 'RepeatLength').down('input[name=recur_end_type][value=none]').setValue(true);
        }
        $(prefix + 'Exceptions').setValue(recur.ex || '');
        if ($(prefix + 'Completions')) {
            $(prefix + 'Completions').setValue(recur.co || '');
        }
        this.toggleRecurrence(event, scheme);
    },

    /**
     * Returns the Date object representing the date and time specified in the
     * event form's start or end fields.
     *
     * @param string what  Which fields to parse, either 'start' or 'end'.
     *
     * @return Date  The date object or null if the fields can't be parsed.
     */
    getDate: function(what) {
        var dateElm, timeElm, date, time;
        if (what == 'start') {
            dateElm = 'kronolithEventStartDate';
            timeElm = 'kronolithEventStartTime';
        } else {
            dateElm = 'kronolithEventEndDate';
            timeElm = 'kronolithEventEndTime';
        }
        date = Date.parseExact($F(dateElm), Kronolith.conf.date_format)
            || Date.parse($F(dateElm));
        if (date) {
            time = Date.parseExact($F(timeElm), Kronolith.conf.time_format);
            if (!time) {
                time = Date.parse($F(timeElm));
            }
            if (time) {
                date.setHours(time.getHours());
                date.setMinutes(time.getMinutes());
            }
        }
        return date;
    },

    checkDate: function(e) {
        this._checkDate(e.element());
    },

    _checkDate: function(elm)
    {
        if ($F(elm)) {
            var date = Date.parseExact($F(elm), Kronolith.conf.date_format) || Date.parse($F(elm));
            if (date) {
                elm.setValue(date.toString(Kronolith.conf.date_format));
                this.wrongFormat.unset(elm.id);
            } else {
                HordeCore.notify(Kronolith.text.wrong_date_format.interpolate({ wrong: $F(elm), right: new Date().toString(Kronolith.conf.date_format) }), 'horde.warning');
                this.wrongFormat.set(elm.id, true);
            }
        } else {
            HordeCore.notify(Kronolith.text.wrong_date_format.interpolate({ wrong: $F(elm), right: new Date().toString(Kronolith.conf.date_format) }), 'horde.warning');
            this.wrongFormat.set(elm.id, true);
        }
    },

    /**
     * Attaches a KeyNavList drop down to one of the time fields.
     *
     * @param string|Element field  A time field (id).
     *
     * @return KeyNavList  The drop down list object.
     */
    attachTimeDropDown: function(field)
    {
        var list = [], d = new Date(), time, opts;

        d.setHours(0);
        d.setMinutes(0);
        do {
            time = d.toString(Kronolith.conf.time_format);
            list.push({ l: time, v: time });
            d.add(30).minutes();
        } while (d.getHours() !== 0 || d.getMinutes() !== 0);

        field = $(field);
        opts = {
            list: list,
            domParent: field.up('.kronolithDialog'),
            onChoose: function(value) {
                if (value) {
                    field.setValue(value);
                }
                this.updateTimeFields(field.identify());
            }.bind(this)
        };

        this.knl[field.id] = new KeyNavList(field, opts);

        return this.knl[field.id];
    },

    checkTime: function(e) {
        var elm = e.element();
        if ($F(elm)) {
            var time = Date.parseExact(new Date().toString(Kronolith.conf.date_format) + ' ' + $F(elm), Kronolith.conf.date_format + ' ' + Kronolith.conf.time_format) || Date.parse(new Date().toString('yyyy-MM-dd ') + $F(elm));
            if (time) {
                elm.setValue(time.toString(Kronolith.conf.time_format));
                this.wrongFormat.unset(elm.id);
            } else {
                HordeCore.notify(Kronolith.text.wrong_time_format.interpolate({ wrong: $F(elm), right: new Date().toString(Kronolith.conf.time_format) }), 'horde.warning');
                this.wrongFormat.set(elm.id, true);
            }
        }
    },

    /**
     * Updates the start time in the event form after changing the end time.
     */
    updateStartTime: function(date) {
        var start = this.getDate('start'), end = this.getDate('end');
        if (!start) {
            return;
        }
        if (!date) {
            date = end;
        }
        if (!date) {
            return;
        }
        if (start.isAfter(end)) {
            $('kronolithEventStartDate').setValue(date.toString(Kronolith.conf.date_format));
            $('kronolithEventStartTime').setValue($F('kronolithEventEndTime'));
        }
        this.duration = Math.abs(date.getTime() - start.getTime()) / 60000;
    },

    /**
     * Updates the end time in the event form after changing the start time.
     */
    updateEndTime: function() {
        var date = this.getDate('start');
        if (!date) {
            return;
        }
        date.add(this.duration).minutes();
        $('kronolithEventEndDate').setValue(date.toString(Kronolith.conf.date_format));
        $('kronolithEventEndTime').setValue(date.toString(Kronolith.conf.time_format));
    },

    /**
     * Event handler for scrolling the mouse over the date field.
     *
     * @param Event e       The mouse event.
     * @param string field  The field name.
     */
    scrollDateField: function(e, field) {
        var date = Date.parseExact($F(field), Kronolith.conf.date_format);
        if (!date || (!e.wheelData && !e.detail)) {
            return;
        }
        date.add(e.wheelData > 0 || e.detail < 0 ? 1 : -1).days();
        $(field).setValue(date.toString(Kronolith.conf.date_format));
        switch (field) {
        case 'kronolithEventStartDate':
            this.updateEndTime();
            break;
        case 'kronolithEventEndDate':
            this.updateStartTime(date);
            break;
        }
    },

    /**
     * Event handler for scrolling the mouse over the time field.
     *
     * @param Event e       The mouse event.
     * @param string field  The field name.
     */
    scrollTimeField: function(e, field) {
        var time = Date.parseExact($F(field), Kronolith.conf.time_format) || Date.parse($F(field)),
            newTime, minute;
        if (!time || (!e.wheelData && !e.detail)) {
            return;
        }

        newTime = time.clone();
        newTime.add(e.wheelData > 0 || e.detail < 0 ? 10 : -10).minutes();
        minute = newTime.getMinutes();
        if (minute % 10) {
            if (e.wheelData > 0 || e.detail < 0) {
                minute = minute / 10 | 0;
            } else {
                minute = (minute - 10) / 10 | 0;
            }
            minute *= 10;
            newTime.setMinutes(minute);
        }
        if (newTime.getDate() != time.getDate()) {
            if (newTime.isAfter(time)) {
                newTime = time.clone().set({ hour: 23, minute: 59 });
            } else {
                newTime = time.clone().set({ hour: 0, minute: 0 });
            }
        }

        $(field).setValue(newTime.toString(Kronolith.conf.time_format));
        this.updateTimeFields(field);

        /* Mozilla bug https://bugzilla.mozilla.org/show_bug.cgi?id=502818
         * Need to stop or else multiple scroll events may be fired. We
         * lose the ability to have the mousescroll bubble up, but that is
         * more desirable than having the wrong scrolling behavior. */
        if (Prototype.Browser.Gecko && !e.stop) {
            Event.stop(e);
        }
    },

    /**
     * Updates the time fields of the event dialog after either has been
     * changed.
     *
     * @param string field  The id of the field that has been changed.
     */
    updateTimeFields: function(field)
    {
        switch (field) {
        case 'kronolithEventStartDate':
            this.fbStartDateHandler(Date.parseExact($F(field), Kronolith.conf.date_format));
        case 'kronolithEventStartTime':
            this.updateEndTime();
            break;
        case 'kronolithEventEndDate':
        case 'kronolithEventEndTime':
            this.updateStartTime();
            this.fbStartDateHandler(Date.parseExact($F('kronolithEventStartDate'), Kronolith.conf.date_format));
            break;
        }
    },

    /**
     * Closes a RedBox overlay, after saving its content to the body.
     */
    closeRedBox: function()
    {
        if (!RedBox.getWindow()) {
            return;
        }
        var content = RedBox.getWindowContents();
        if (content) {
            document.body.insert(content.hide());
        }
        RedBox.close();
    },

    // By default, no context onShow action
    contextOnShow: Prototype.emptyFunction,

    // By default, no context onClick action
    contextOnClick: Prototype.emptyFunction,

    // Map
    initializeMap: function(ignoreLL)
    {
        if (this.mapInitialized) {
            return;
        }
        var layers = [];
        if (Kronolith.conf.maps.providers) {
            Kronolith.conf.maps.providers.each(function(l) {
                var p = new HordeMap[l]();
                $H(p.getLayers()).values().each(function(e) {layers.push(e);});
            });
        }

        this.map = new HordeMap.Map[Kronolith.conf.maps.driver]({
            elt: 'kronolithEventMap',
            delayed: true,
            layers: layers,
            markerDragEnd: this.onMarkerDragEnd.bind(this),
            mapClick: this.afterClickMap.bind(this)
        });

        if ($('kronolithEventLocationLat').value && !ignoreLL) {
            var ll = { lat:$('kronolithEventLocationLat').value, lon: $('kronolithEventLocationLon').value };
            // Note that we need to cast the value of zoom to an integer here,
            // otherwise the map display breaks.
            this.placeMapMarker(ll, true, $('kronolithEventMapZoom').value - 0);
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
        $('kronolithEventMapZoom').value = null;
        if (this.mapMarker) {
            this.map.removeMarker(this.mapMarker, {});
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
    onReverseGeocode: function(r)
    {
        if (!r.length) {
            return;
        }
        $('kronolithEventLocation').value = r[0].address;
    },

    onGeocodeError: function(r)
    {
        $('kronolithEventGeo_loading_img').toggle();
        HordeCore.notify(Kronolith.text.geocode_error + ' ' + r, 'horde.error');
    },

    /**
     * Callback for geocoding calls.
     */
    onGeocode: function(r)
    {
        $('kronolithEventGeo_loading_img').toggle();
        r = r.shift();
        if (r.precision) {
            zoom = r.precision * 2;
        } else {
            zoom = null;
        }
        this.ensureMap(true);
        this.placeMapMarker({ lat: r.lat, lon: r.lon }, true, zoom);
    },

    geocode: function(a) {
        if (!a) {
            return;
        }
        $('kronolithEventGeo_loading_img').toggle();
        var gc = new HordeMap.Geocoder[Kronolith.conf.maps.geocoder](this.map.map, 'kronolithEventMap');
        gc.geocode(a, this.onGeocode.bind(this), this.onGeocodeError);
    },

    /**
     * Place the event marker on the map, at point ll, ensuring it exists.
     * Optionally center the map on the marker and zoom. Zoom only honored if
     * center is set, and if center is set, but zoom is null, we zoomToFit().
     *
     */
    placeMapMarker: function(ll, center, zoom)
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

        if (center) {
            this.map.setCenter(ll, zoom);
            if (!zoom) {
                this.map.zoomToFit();
            }
        }
        $('kronolithEventLocationLon').value = ll.lon;
        $('kronolithEventLocationLat').value = ll.lat;
    },

    /**
     * Remove the event marker from the map. Called after clearing the location
     * field.
     */
    removeMapMarker: function()
    {
        if (this.mapMarker) {
            this.map.removeMarker(this.mapMarker, {});
            $('kronolithEventLocationLon').value = null;
            $('kronolithEventLocationLat').value = null;
        }

        this.mapMarker = false;
    },

    /**
     * Ensures the map tab is visible and sets UI elements accordingly.
     */
    ensureMap: function(ignoreLL)
    {
        if (!this.mapInitialized) {
            this.initializeMap(ignoreLL);
        }
        var dialog = $('kronolithEventForm');
        dialog.select('.kronolithTabsOption').invoke('hide');
        dialog.select('.tabset li').invoke('removeClassName', 'horde-active');
        $('kronolithEventTabMap').show();
        $('kronolithEventLinkMap').up().addClassName('horde-active');
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

        /* Initialize the starting page. */
        var tmp = location.hash;
        if (!tmp.empty() && tmp.startsWith('#')) {
            tmp = (tmp.length == 1) ? '' : tmp.substring(1);
        }
        if (tmp.empty()) {
            this.updateView(this.date, Kronolith.conf.login_view);
            $('kronolithView' + Kronolith.conf.login_view.capitalize()).show();
        }
        HordeCore.doAction('listCalendars', {}, { callback: this.initialize.bind(this, tmp) });

        RedBox.onDisplay = function() {
            this.redBoxLoading = false;
        }.bind(this);
        RedBox.duration = this.effectDur;

        $('kronolithEventStartDate', 'kronolithEventEndDate', 'kronolithTaskDueDate').compact().invoke('observe', 'blur', this.checkDate.bind(this));
        var timeFields = $('kronolithEventStartTime', 'kronolithEventEndTime', 'kronolithTaskDueTime').compact();
        timeFields.invoke('observe', 'blur', this.checkTime.bind(this));
        timeFields.each(function(field) {
            var dropDown = this.attachTimeDropDown(field);
            field.observe('click', function() { dropDown.show(); });
        }, this);
        $('kronolithEventStartDate', 'kronolithEventStartTime').invoke('observe', 'change', this.updateEndTime.bind(this));
        $('kronolithEventEndDate', 'kronolithEventEndTime').invoke('observe', 'change', function() { this.updateStartTime(); }.bind(this));

        if (Kronolith.conf.has_tasks) {
            $('kronolithTaskDueDate', 'kronolithTaskDueTime').compact().invoke('observe', 'focus', this.setDefaultDue.bind(this));
            $('kronolithTaskList').observe('change', function() {
                this.updateTaskParentDropDown($F('kronolithTaskList'));
                this.updateTaskAssigneeDropDown($F('kronolithTaskList'));
            }.bind(this));
        }

        document.observe('keydown', KronolithCore.keydownHandler.bindAsEventListener(KronolithCore));
        document.observe('keyup', KronolithCore.keyupHandler.bindAsEventListener(KronolithCore));
        document.observe('click', KronolithCore.clickHandler.bindAsEventListener(KronolithCore));
        document.observe('dblclick', KronolithCore.clickHandler.bindAsEventListener(KronolithCore, true));

        // Mouse wheel handler.
        dateFields = [ 'kronolithEventStartDate', 'kronolithEventEndDate' ];
        timeFields = [ 'kronolithEventStartTime', 'kronolithEventEndTime' ];
        if (Kronolith.conf.has_tasks) {
            dateFields.push('kronolithTaskDueDate');
            timeFields.push('kronolithTaskDueTime');
        }
        dateFields.each(function(field) {
            $(field).observe(Prototype.Browser.Gecko ? 'DOMMouseScroll' : 'mousewheel', this.scrollDateField.bindAsEventListener(this, field));
        }, this);
        timeFields.each(function(field) {
            $(field).observe(Prototype.Browser.Gecko ? 'DOMMouseScroll' : 'mousewheel', this.scrollTimeField.bindAsEventListener(this, field));
        }, this);

        $('kronolithEventStartDate').observe('change', this.fbStartDateOnChange.bind(this));
        $('kronolithFBDatePrev').observe('click', this.prevFreebusy.bind(this));
        $('kronolithFBDateNext').observe('click', this.nextFreebusy.bind(this));
        $('kronolithResourceFBDatePrev').observe('click', this.prevFreebusy.bind(this));
        $('kronolithResourceFBDateNext').observe('click', this.nextFreebusy.bind(this));

        this.updateMinical(this.date);
    },

    initialize: function(location, r)
    {
        Kronolith.conf.calendars = r.calendars;
        this.updateCalendarList();
        HordeSidebar.refreshEvents();
        $('kronolithLoadingCalendars').hide();
        $('kronolithMenuCalendars').show();
        this.initialized = true;

        /* Initialize the starting page. */
        if (!location.empty()) {
            this.go(decodeURIComponent(location));
        } else {
            this.go(Kronolith.conf.login_view);
        }

        /* Start polling. */
        new PeriodicalExecuter(function()
            {
                HordeCore.doAction('poll');
                $(kronolithGotoToday).update(Date.today().toString(Kronolith.conf.date_format));
            },
            60
        );
    }

};

/* Initialize global event handlers. */
document.observe('dom:loaded', KronolithCore.onDomLoad.bind(KronolithCore));
document.observe('DragDrop2:drag', KronolithCore.onDrag.bindAsEventListener(KronolithCore));
document.observe('DragDrop2:drop', KronolithCore.onDrop.bindAsEventListener(KronolithCore));
document.observe('DragDrop2:end', KronolithCore.onDragEnd.bindAsEventListener(KronolithCore));
document.observe('DragDrop2:start', KronolithCore.onDragStart.bindAsEventListener(KronolithCore));
document.observe('Horde_Calendar:select', KronolithCore.datePickerHandler.bindAsEventListener(KronolithCore));
document.observe('FormGhost:reset', KronolithCore.searchReset.bindAsEventListener(KronolithCore));
document.observe('FormGhost:submit', KronolithCore.searchSubmit.bindAsEventListener(KronolithCore));
document.observe('HordeCore:showNotifications', KronolithCore.showNotification.bindAsEventListener(KronolithCore));
if (Prototype.Browser.IE) {
    $('kronolithBody').observe('selectstart', Event.stop);
}

/* Extend AJAX exception handling. */
HordeCore.onException = HordeCore.onException.wrap(KronolithCore.onException.bind(KronolithCore));
