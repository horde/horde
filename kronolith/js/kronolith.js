/**
 * kronolith.js - Base application logic.
 * NOTE: ContextSensitive.js must be loaded before this file.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
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
    //   daySizes, viewLoading

    view: '',
    ecache: $H(),
    tcache: $H(),
    efifo: {},
    eventsLoading: {},
    loading: 0,
    date: new Date(),
    tasktype: 'incomplete',
    growls: 0,

    doActionOpts: {
        onException: function(r, e) { KronolithCore.debug('onException', e); },
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
        var b, tmp = {};

        opts = Object.extend(this.doActionOpts, opts || {});
        params = $H(params);
        action = action.startsWith('*')
            ? action.substring(1)
            : Kronolith.conf.URI_AJAX + '/' + action;
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
            case 'kronolith.timeout':
                this.logout(Kronolith.conf.timeout_url);
                return true;

            case 'horde.error':
            case 'horde.warning':
            //case 'horde.alarm':
            case 'horde.message':
            case 'horde.success':
                this.Growler.growl(m.message, {
                    className: m.type.replace('.', '-'),
                    life: 8,
                    log: true,
                    sticky: m.type == 'horde.error'
                });
                var notify = $('kronolithNotifications'),
                    className = m.type.replace(/\./, '-'),
                    order = 'horde-error,horde-warning,horde-alarm,horde-message,horde-success';
                if (!notify.className ||
                    order.indexOf(notify.className) > order.indexOf(className)) {
                    notify.className = className;
                }
                notify.update(Kronolith.text.alerts.interpolate({ 'count': ++this.growls }));
                notify.up().show();
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
                     (loc == 'week' && date.getWeek() == this.date.getWeek()) ||
                     ((loc == 'day'  || loc == 'agenda') && date.dateString() == this.date.dateString()))) {
                         return;
                }

                this.updateView(date, loc);
                var dates = this.viewDates(date, loc);
                this._loadEvents(dates[0], dates[1], loc);
                if ($('kronolithView' + locCap)) {
                    this.viewLoading = true;
                    $('kronolithView' + locCap).appear({
                            'queue': 'end',
                            'afterFinish': function() {
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
                    $('kronolithView' + locCap).appear({ 'queue': 'end', 'afterFinish': function() { this.viewLoading = false; }.bind(this) });
                }
                $('kronolithLoading' + loc).insert($('kronolithLoading').remove());
                this.updateMinical(this.date, loc);

                break;

            default:
                if ($('kronolithView' + locCap)) {
                    this.viewLoading = true;
                    $('kronolithView' + locCap).appear({ 'queue': 'end', 'afterFinish': function() { this.viewLoading = false; }.bind(this) });
                }
                break;
            }

            this._addHistory(fullloc);
            this.view = loc;
            break;

        case 'search':
            var cals = [], term = locParts[1],
                query = Object.toJSON({ 'title': term });
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
            this.doAction('Search' + locParts[0],
                          { 'cals': cals.toJSON(), 'query': query },
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
            $('kronolithViewAgenda').appear({ 'queue': 'end', 'afterFinish': function() { this.viewLoading = false; }.bind(this) });
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
            case 2:
                // Editing event.
                this.editEvent(locParts[0], locParts[1]);
                break;
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
        switch (view) {
        case 'day':
            this.dayEvents = [];
            this.dayGroups = [];
            this.allDayEvents = [];
            $('kronolithViewDay').down('caption span').innerHTML = this.setTitle(date.toString('D'));
            break;

        case 'week':
            this.dayEvents = [];
            this.dayGroups = [];
            this.allDayEvents = [];
            var div = $('kronolithEventsWeek').down('div'),
                th = $('kronolithViewWeekHead').down('.kronolithWeekDay'),
                td = $('kronolithViewWeekHead').down('tbody').down('td').next('td'),
                dates = this.viewDates(date, view),
                day = dates[0].clone();

            $('kronolithViewWeek').down('caption span').innerHTML = this.setTitle(Kronolith.text.week.interpolate({ 'week': date.getWeek() }));

            for (var i = 0; i < 7; i++) {
                div.writeAttribute('id', 'kronolithEventsWeek' + day.dateString());
                th.store('date', day.dateString()).down('span').innerHTML = day.toString('dddd, d');
                td.down('div').writeAttribute('id', 'kronolithAllDay' + day.dateString());
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
                $('kronolithYear' + month).update(this.createYearMonth(date.getFullYear(), month).show());
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
                $('kronolithViewAgenda').down('caption span').update(this.setTitle(Kronolith.text.searching.interpolate({ 'term': data })));
            }

            // Remove old rows. Maybe we should only rebuild the calendars if
            // necessary.
            tbody = $('kronolithViewAgendaBody').childElements().each(function(row) {
                if (row.identify() != 'kronolithAgendaTemplate') {
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
            $('kronolithNav' + a).removeClassName('on');
        });
        if (this.view && this.view != loc) {
            $('kronolithView' + this.view.capitalize()).fade({ 'queue': 'end' });
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
        var monday = date.clone(), day = date.clone(),
            today = new Date().dateString(),
            start = viewDates[0].dateString(), end = viewDates[1].dateString(),
            row, cell, dateString;

        // Find monday of the week, to determine the week number.
        if (monday.getDay() != 1) {
            monday.moveToDayOfWeek(1, 1);
        }

        // Create a copy of the row template.
        row = $('kronolithRowTemplate').cloneNode(true);
        row.removeAttribute('id');

        // Fill week number and day cells.
        cell = row.down()
            .setText(monday.getWeek())
            .store('date', monday.dateString())
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
            new Drop(cell, { onDrop: function(drop) {
                var el = DragDrop.Drags.drag.element,
                    eventid = el.retrieve('eventid'),
                    cal = el.retrieve('calendar');
                if (drop == el.parentNode) {
                    return;
                }
                drop.insert(el);
                this.startLoading(cal, start + end);
                this.doAction('UpdateEvent',
                              { 'cal': cal,
                                'id': eventid,
                                'view': this.view,
                                'view_start': start,
                                'view_end': end,
                                'att': $H({ start_date: drop.retrieve('date') }).toJSON() },
                              function(r) {
                                  if (r.response.events) {
                                      this._removeEvent(eventid, cal);
                                  }
                                  this._loadEventsCallback(r);
                              }.bind(this));
            }.bind(this) });
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
            nextRow.insert({ 'before': row.show() });
        } else {
            body.insert(row.show());
        }

        return row;
    },

    /**
     * Creates a table for a single month in the year view.
     *
     * @param integer year   The year.
     * @param integer month  The month.
     *
     * @return Element  The element rendering a month table.
     */
    createYearMonth: function(year, month)
    {
        // Create a copy of the month template.
        var table = $('kronolithYearTemplate').cloneNode(true),
            tbody = table.down('tbody');
        table.removeAttribute('id');
        tbody.writeAttribute('id', 'kronolithYearTable' + month)

        // Set month name.
        table.down('SPAN')
            .store('date', year.toPaddedString(4) + (month + 1).toPaddedString(2) + '01')
            .innerHTML = Date.CultureInfo.monthNames[month];

        // Build month table.
        this.buildMinical(tbody, new Date(year, month, 1));

        return table;
    },

    _equalRowHeights: function(tbody)
    {
        var children = tbody.childElements();
        children.invoke('setStyle', { 'height': (100 / (children.size() - 1)) + '%' });
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
        var tr = $(view).down('.kronolithViewBody').down('tr'),
            td = tr.down('td').next('td'), tdTop, tdHeight,
            tdAlign = td.getStyle('verticalAlign'),
            tr2 = tr.next('tr'),
            td2 = tr2.down('td').next('td'), td2Top,
            div = new Element('DIV').setStyle({ 'width': '1px', 'height': '1px' });

        td.insert({ 'top': div });
        tdTop = div.cumulativeOffset().top;
        td.setStyle({ 'verticalAlign': 'bottom' });
        td.insert({ 'bottom': div });
        tdHeight = div.cumulativeOffset().top + parseInt(td.getStyle('lineHeight')) - tdTop;
        td.setStyle({ 'verticalAlign': tdAlign });
        td2.insert({ 'top': div });
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
     * @param Element tbody  The table body to add the days to.
     * @param Date date      The date to show in the calendar.
     * @param string view    The view that's displayed, determines which days in
     *                       the mini calendar are highlighted.
     */
    buildMinical: function(tbody, date, view)
    {
        var dates = this.viewDates(date, 'month'), day = dates[0].clone(),
            date7 = date.clone().add(1).week(),
            weekStart, weekEnd, weekEndDay, td, tr, i;

        // Remove old calendar rows. Maybe we should only rebuild the minical
        // if necessary.
        tbody.childElements().invoke('remove');

        for (i = 0; i < 42; i++) {
            // Create calendar row and insert week number.
            if (day.getDay() == Kronolith.conf.week_start) {
                tr = new Element('TR');
                tbody.insert(tr);
                td = new Element('TD', { 'class': 'kronolithMinicalWeek' })
                    .store('weekdate', day.dateString());
                td.innerHTML = day.getWeek();
                tr.insert(td);
                weekStart = day.clone();
                weekEnd = day.clone();
                weekEnd.add(6).days();
            }
            // Insert day cell.
            td = new Element('TD').store('date', day.dateString());
            if (day.getMonth() != date.getMonth()) {
                td.addClassName('kronolithMinicalEmpty');
            }
            // Highlight days currently being displayed.
            if (view &&
                (view == 'month' ||
                 (view == 'week' && date.between(weekStart, weekEnd)) ||
                 (view == 'day' && date.equals(day)) ||
                 (view == 'agenda' && !day.isBefore(date) && day.isBefore(date7)))) {
                td.addClassName('kronolithSelected');
            }
            td.innerHTML = day.getDate();
            tr.insert(td);
            day.next().day();
        }
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
                div = $('kronolithMyCalendars');
                div.insert(new Element('SPAN', { 'class': 'kronolithCalEdit' })
                           .insert('&rsaquo;'));
            } else {
                shared++;
                div = $('kronolithSharedCalendars');
            }
            div.insert(new Element('DIV', { 'class': cal.value.show ? 'kronolithCalOn' : 'kronolithCalOff' })
                       .store('calendar', cal.key)
                       .store('calendarclass', 'internal')
                       .setStyle({ backgroundColor: cal.value.bg, color: cal.value.fg })
                       .update(cal.value.name.escapeHTML()));
        });
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
                div = $('kronolithMyTasklists');
                div.insert(new Element('SPAN', { 'class': 'kronolithCalEdit' })
                           .insert('&rsaquo;'));
            } else {
                shared++;
                div = $('kronolithSharedTasklists');
            }
            div.insert(new Element('DIV', { 'class': cal.value.show ? 'kronolithCalOn' : 'kronolithCalOff' })
                       .store('calendar', cal.key)
                       .store('calendarclass', 'tasklists')
                       .setStyle({ backgroundColor: cal.value.bg, color: cal.value.fg })
                       .update(cal.value.name.escapeHTML()));
        });
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
                .insert(new Element('H3')
                        .insert(new Element('A', { 'class': 'kronolithAdd'  })
                                .update('+'))
                        .insert({ bottom: extNames.get(api.key).escapeHTML() }))
                .insert(new Element('DIV', { 'id': 'kronolithExternalCalendar' + api.key, 'class': 'kronolithCalendars' }));
            api.value.each(function(cal) {
                $('kronolithExternalCalendar' + api.key)
                    .insert(new Element('DIV', { 'class': cal.value.show ? 'kronolithCalOn' : 'kronolithCalOff' })
                            .store('calendar', api.key + '/' + cal.key)
                            .store('calendarclass', 'external')
                            .setStyle({ backgroundColor: cal.value.bg, color: cal.value.fg })
                            .update(cal.value.name.escapeHTML()));
            });
        });

        remote = $H(Kronolith.conf.calendars.remote);
        remote.each(function(cal) {
            $('kronolithRemoteCalendars')
                .insert(new Element('DIV', { 'class': cal.value.show ? 'kronolithCalOn' : 'kronolithCalOff' })
                        .store('calendar', cal.key)
                        .store('calendarclass', 'remote')
                        .setStyle({ backgroundColor: cal.value.bg, color: cal.value.fg })
                        .update(cal.value.name.escapeHTML()));
        });
        if (remote.size()) {
            $('kronolithRemoteCalendars').show();
        } else {
            $('kronolithRemoteCalendars').hide();
        }

        holidays = $H(Kronolith.conf.calendars.holiday);
        holidays.each(function(cal) {
            $('kronolithHolidayCalendars')
                .insert(new Element('DIV', { 'class': cal.value.show ? 'kronolithCalOn' : 'kronolithCalOff' })
                        .store('calendar', cal.key)
                        .store('calendarclass', 'holiday')
                        .setStyle({ backgroundColor: cal.value.bg, color: cal.value.fg })
                        .update(cal.value.name.escapeHTML()));
        });
        if (holidays.size()) {
            $('kronolithHolidayCalendars').show();
        } else {
            $('kronolithHolidayCalendars').hide();
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
                $(id).insert(new Element('OPTION', { 'value': 'internal|' + cal.key })
                             .setStyle({ 'backgroundColor': cal.value.bg, 'color': cal.value.fg })
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

            if (view == 'year') {
                td = $('kronolithYearTable' + day.getMonth()).select('td').find(function(elm) { return elm.retrieve('date') == date; });
                td.className = '';
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
     * @param object event     A Hash member with the event to insert.
     * @param string date      The day to update.
     * @param string view      The view to update.
     */
    _insertEvent: function(event, date, view)
    {
        event.value.nodeId = 'kronolithEvent' + view + event.value.calendar + date + event.key;

        _createElement = function(event) {
            var el = new Element('DIV', { 'id': event.value.nodeId, 'class': 'kronolithEvent' })
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
                style = { 'backgroundColor': event.value.bg,
                          'color': event.value.fg };

            if (event.value.al) {
                if (view == 'day') {
                    $('kronolithViewDay').down('.kronolithAllDayContainer').insert(div.setStyle(style));
                } else {
                    $('kronolithAllDay' + date).insert(div.setStyle(style));
                }
                break;
            }

            var midnight = this.parseDate(date),
                innerDiv = new Element('DIV', { 'class': 'kronolithEventInfo' }),
                draggerTop, draggerBottom;
            if (event.value.fi) {
                draggerTop = new Element('DIV', { 'id': event.value.nodeId + 'top', 'class': 'kronolithDragger kronolithDraggerTop' }).setStyle(style);
            } else {
                innerDiv.setStyle({ 'top': 0 });
            }
            if (event.value.la) {
                draggerBottom = new Element('DIV', { 'id': event.value.nodeId + 'bottom', 'class': 'kronolithDragger kronolithDraggerBottom' }).setStyle(style);
            } else {
                innerDiv.setStyle({ 'bottom': 0 });
            }

            div.setStyle({
                'top': (Math.round(midnight.getElapsed(event.value.start) / 60000) * this[storage].height / 60 | 0) + 'px',
                'height': (Math.round(event.value.start.getElapsed(event.value.end) / 60000) * this[storage].height / 60 - this[storage].spacing | 0) + 'px',
                'width': '100%'
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
                    draggingTop,
                    opts = {
                        'threshold': 5,
                        'constraint': 'vertical',
                        'scroll': 'kronolithBody',
                        'nodrop': true,
                        'parentElement': function() {
                            return $(view == 'day' ? 'kronolithEventsDay' : 'kronolithEventsWeek' + date);
                        },
                        'onStart': function(d, e) {
                            this.addClassName('kronolithSelected');
                        }.bind(div),
                        'onEnd': function(d, e) {
                            this[0]._onDragEnd(d, this[1], innerDiv, event, midnight, view, step, draggingTop);
                        }.bind([this, div]),
                        'onDrag': function(d, e) {
                            var div = this[1],
                                top = d.ghost.cumulativeOffset().top,
                                offset, height, dates;
                            draggingTop = d.ghost.hasClassName('kronolithDraggerTop');
                            if (draggingTop) {
                                offset = top - dragTop;
                                height = div.offsetHeight - offset;
                                div.setStyle({
                                    'top': (div.offsetTop + offset) + 'px'
                                });
                                offset = d.ghost.offsetTop;
                                dragTop = top;
                            } else {
                                offset = top - dragBottom;
                                height = div.offsetHeight + offset;
                                offset = div.offsetTop;
                                dragBottom = top;
                            }
                            div.setStyle({
                                'height': height + 'px'
                            });
                            this[0]._calculateEventDates(event.value, storage, step, offset, height);
                            innerDiv.update('(' + event.value.start.toString(Kronolith.conf.time_format) + ' - ' + event.value.end.toString(Kronolith.conf.time_format) + ') ' + event.value.t.escapeHTML());
                        }.bind([this, div])
                    };

                if (draggerTop) {
                    opts.snap = function(x, y, elm) {
                        y = Math.max(0, step * (Math.min(maxTop, y) / step | 0));
                        return [0, y];
                    }
                    new Drag(event.value.nodeId + 'top', opts);
                }

                if (draggerBottom) {
                    opts.snap = function(x, y, elm) {
                        y = Math.min(maxBottom + dragBottomHeight + KronolithCore[storage].spacing, step * ((Math.max(minBottom, y) + dragBottomHeight + KronolithCore[storage].spacing) / step | 0)) - dragBottomHeight - KronolithCore[storage].spacing;
                        return [0, y];
                    }
                    new Drag(event.value.nodeId + 'bottom', opts);
                }

                if (view == 'week') {
                    var dates = this.viewDates(midnight, view),
                        eventStart = event.value.start.clone(),
                        eventEnd = event.value.end.clone(),
                        minLeft = $('kronolithEventsWeek' + dates[0].toString('yyyyMMdd')).offsetLeft - $('kronolithEventsWeek' + date).offsetLeft,
                        maxLeft = $('kronolithEventsWeek' + dates[1].toString('yyyyMMdd')).offsetLeft - $('kronolithEventsWeek' + date).offsetLeft,
                        stepX = (maxLeft - minLeft) / 6;
                }
                var startTop = div.offsetTop;
                new Drag(div, {
                    'threshold': 5,
                    'nodrop': true,
                    'parentElement': function() { return $(view == 'day' ? 'kronolithEventsDay' : 'kronolithEventsWeek' + date); },
                    'snap': function(x, y, elm) {
                        if (view == 'week') {
                            x = Math.max(minLeft, stepX * ((Math.min(maxLeft, x) + stepX / 2) / stepX | 0));
                        } else {
                            x = 0;
                        }
                        y = Math.max(0, step * (Math.min(maxDiv, y) / step | 0));
                        return [x, y];
                    },
                    'onStart': function(d, e) {
                        this.addClassName('kronolithSelected');
                        this.setStyle({ 'left': 0, 'width': '100%', 'zIndex': 1 });
                    }.bind(div),
                    'onDrag': function(d, e) {
                        if (Object.isUndefined(d.innerDiv)) {
                            d.innerDiv = d.ghost.select('.kronolithEventInfo')[0];
                        }
                        if (view == 'week') {
                            var offsetX = Math.round(d.ghost.offsetLeft / stepX);
                            event.value.offsetDays = offsetX;
                            this[0]._calculateEventDates(event.value, storage, step, d.ghost.offsetTop, divHeight, eventStart.clone().addDays(offsetX), eventEnd.clone().addDays(offsetX));
                        } else {
                            event.value.offsetDays = 0;
                            this[0]._calculateEventDates(event.value, storage, step, d.ghost.offsetTop, divHeight);
                        }
                        event.value.offsetTop = d.ghost.offsetTop - startTop;
                        d.innerDiv.update('(' + event.value.start.toString(Kronolith.conf.time_format) + ' - ' + event.value.end.toString(Kronolith.conf.time_format) + ') ' + event.value.t.escapeHTML());
                        this[1].clonePosition(d.ghost);
                    }.bind([this, div]),
                    'onEnd': function(d, e) {
                        this[0]._onDragEnd(d, this[1], innerDiv, event, midnight, view, step);
                    }.bind([this, div])
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
                $(ev.nodeId).setStyle({ 'width': width + '%', 'left': (width * (ev.column - 1)) + '%' });
            });
            this.dayEvents.push(event.value);

            div = innerDiv;
            break;

        case 'month':
            var div = _createElement(event)
                .setStyle({ 'backgroundColor': event.value.bg,
                            'color': event.value.fg });

            $('kronolithMonthDay' + date).insert(div);
            if (event.value.pe) {
                div.setStyle({ 'cursor': 'move' });
                new Drag('kronolithEventmonth' + event.value.calendar + date + event.key, { threshold: 5, parentElement: function() { return $('kronolithViewMonthBody'); }, snapToParent: true });
            }
            break;

        case 'agenda':
            var div = _createElement(event)
                .setStyle({ 'backgroundColor': event.value.bg,
                            'color': event.value.fg });
            if (!event.value.al) {
                div.update(new Element('SPAN', { 'class': 'kronolithDate' }).update(event.value.start.toString('t')))
                    .insert(' ')
                    .insert(new Element('SPAN', { 'class': 'kronolithSep' }).update('&middot;'))
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
        div.update();
        if (event.ic) {
            div.insert(new Element('IMG', { 'src': event.ic }));
        }
        div.insert(event.t.escapeHTML());
        if (event.a) {
            div.insert(' ')
                .insert(new Element('IMG', { 'src': Kronolith.conf.URI_IMG + 'alarm-' + event.fg.substr(1) + '.png', 'title': Kronolith.text.alarm + ' ' + event.a }));
        }
        if (event.r) {
            div.insert(' ')
                .insert(new Element('IMG', { 'src': Kronolith.conf.URI_IMG + 'recur-' + event.fg.substr(1) + '.png', 'title': Kronolith.text.recur[event.r] }));
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
     * Called as the event handler after dragging/resizing a day/week event.
     */
    _onDragEnd: function(drag, div, innerDiv, event, date, view, step, top)
    {
        var dates = this.viewDates(date, view),
            start = dates[0].toString('yyyyMMdd'),
            end = dates[1].toString('yyyyMMdd'),
            attributes;
        div.removeClassName('kronolithSelected');
        this._setEventText(innerDiv, event.value);
        drag.destroy();
        this.startLoading(event.value.calendar, start + end);
        if (!Object.isUndefined(event.value.offsetTop)) {
            attributes = $H({ 'offDays': event.value.offsetDays,
                              'offMins': event.value.offsetTop / step * 10 });
        } else if (!Object.isUndefined(top)) {
            if (top) {
                attributes = $H({ 'start': event.value.start });
            } else {
                attributes = $H({ 'end': event.value.end });
            }
        } else {
            attributes = $H({ 'start': event.value.start,
                              'end': event.value.end });
        }
        this.doAction(
            'UpdateEvent',
            { 'cal': event.value.calendar,
              'id': event.key,
              'view': view,
              'view_start': start,
              'view_end': end,
              'att': attributes.toJSON()
            },
            function(r) {
                if (r.response.events) {
                    this._removeEvent(event.key, event.value.calendar);
                }
                this._loadEventsCallback(r);
            }.bind(this));
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
            tasklists.each(function(list) {
                if (Object.isUndefined(this.tcache.get(type)) ||
                    Object.isUndefined(this.tcache.get(type).get(list))) {
                    loading = true;
                    this.startLoading('tasks:' + type + list, tasktype);
                    this._storeTasksCache($H(), type, list, true);
                    this.doAction('ListTasks',
                                  { 'type': type,
                                    'sig' : tasktype,
                                    'list': list },
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
                if (tasktype != 'all' &&
                    !Object.isUndefined(task.value.start) &&
                    task.value.start.isAfter(now)) {
                    (function() {
                        if (this.tasktype == tasktype) {
                            this._insertTasks(tasktype, tasklist);
                        }
                    }).bind(this).delay((task.value.start.getTime() - now.getTime()) / 1000);
                }

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
                col.insert(new Element('SPAN', { 'class': 'kronolithSep' }).update(' &middot; '));
                col.insert(new Element('SPAN', { 'class': 'kronolithDate' }).update(date.toString(Kronolith.conf.date_format)));
            }
        }

        if (!Object.isUndefined(task.value.sd)) {
            col.insert(new Element('SPAN', { 'class': 'kronolithSep' }).update(' &middot; '));
            col.insert(new Element('SPAN', { 'class': 'kronolithInfo' }).update(task.value.sd));
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
        // The first row is a template one, so must be ignored
        for (var i = 1; i < rows.length; i++) {
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
        rows[--i].insert({ 'after': newRow.show() });
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
            row.fade({ 'afterFinish': function() { row.remove(); } });
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

        this.updateTasklistDropDown();
        if (id) {
            RedBox.loading();
            this.doAction('GetTask', { 'list': tasklist, 'id': id }, this._editTask.bind(this));
        } else {
            $('kronolithTaskId').clear();
            $('kronolithTaskOldList').clear();
            //$('kronolithTaskList').setValue(Kronolith.conf.default_tasklist);
            $('kronolithTaskDelete').hide();
            $('kronolithTaskDueDate').setValue(d.toString(Kronolith.conf.date_format));
            $('kronolithTaskDueTime').setValue(d.toString(Kronolith.conf.time_format));
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
        $('kronolithTaskDueDate').setValue(task.dd);
        $('kronolithTaskDueTime').setValue(task.dt);
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
                $('kronolithTaskList').insert(new Element('OPTION', { 'value': cal.key.substring(6) })
                             .setStyle({ 'backgroundColor': cal.value.bg, 'color': cal.value.fg })
                             .update(cal.value.name.escapeHTML()));
            }
        });
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
        var tasklist = $F('kronolithTaskList'),
            taskid = $F('kronolithTaskId');
        this.startLoading('tasks:' + ($F('kronolithTaskCompleted') ? 'complete' : 'incomplete') + tasklist, this.tasktype);
        this.doAction('SaveTask',
                      $H($('kronolithTaskForm').serialize({ 'hash': true }))
                          .merge({ 'sig': this.tasktype }),
                      function(r) {
                          if (r.response.tasks && taskid) {
                              this._removeTask(taskid, tasklist);
                              this._loadTasksCallback(r, this.tasktype, false);
                          }
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
     * Deletes an event from the cache.
     *
     * @param string event     An event ID.
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
        this.ecache.get(calendar[0]).get(calendar[1]).each(function(day) {
            day.value.unset(event);
        });
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
            var iframe = new Element('IFRAME', { 'id': 'kronolithIframe' + name, 'class': 'kronolithIframe', 'frameBorder': 0, 'src': loc });
            //this._resizeIE6Iframe(iframe);
            $('kronolithViewIframe').insert(iframe);
        }

        $('kronolithViewIframe').appear({ 'queue': 'end' });
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
                    this.go('search:' + $F('kronolithSearchContext') + ':' + $F('kronolithSearchTerm'))
                    e.stop();
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
        /*
        if (e.element().readAttribute('id') == 'foo') {
        }
        */
    },

    clickHandler: function(e, dblclick)
    {
        if (e.isRightClick()) {
            return;
        }

        var elt = e.element(),
            orig = e.element(),
            id, tmp, calendar, calendarClass;

        while (Object.isElement(elt)) {
            id = elt.readAttribute('id');

            switch (id) {
            case 'kronolithLogo':
                this.go('portal');
                e.stop();
                return;

            case 'kronolithNewEvent':
                this.go('event');
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

            case 'kronolithEventLinkDescription':
            case 'kronolithEventLinkReminder':
            case 'kronolithEventLinkRecur':
            case 'kronolithEventLinkUrl':
            case 'kronolithEventLinkAttendees':
            case 'kronolithEventLinkTags':
                $('kronolithEventDialog').select('.kronolithTabsOption').invoke('hide');
                $(id.replace(/Link/, 'Tab')).show();
                $('kronolithEventDialog').select('.tabset li').invoke('removeClassName', 'activeTab');
                elt.parentNode.addClassName('activeTab');
                e.stop();
                return;

            case 'kronolithTaskLinkDescription':
            case 'kronolithTaskLinkReminder':
            case 'kronolithTaskLinkUrl':
                $('kronolithTaskDialog').select('.kronolithTabsOption').invoke('hide');
                $(id.replace(/Link/, 'Tab')).show();
                $('kronolithTaskDialog').select('.tabset li').invoke('removeClassName', 'activeTab');
                elt.parentNode.addClassName('activeTab');
                e.stop();
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
                e.stop();
                return;

            case 'kronolithTaskSave':
                this.saveTask();
                e.stop();
                return;

            case 'kronolithEventDelete':
                var cal = $F('kronolithEventCalendar'),
                    eventid = $F('kronolithEventId');
                this.doAction('DeleteEvent',
                              { 'cal': cal, 'id': eventid },
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
                              { 'list': tasklist, 'id': taskid },
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
                $('kronolithViewTasksBody').select('tr').find(function(el) {
                    return el.retrieve('tasklist') == tasklist &&
                        el.retrieve('taskid') == taskid;
                }).hide();
                this._closeRedBox();
                window.history.back();
                e.stop();
                return;

            case 'kronolithEventCancel':
            case 'kronolithTaskCancel':
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
                this.go('search:' + $F('kronolithSearchContext') + ':' + $F('kronolithSearchTerm'))
                break;

            case 'kronolithNotifications':
                if (this.Growler.toggleLog()) {
                    elt.update(Kronolith.text.hidelog);
                } else {
                    $('kronolithNotifications').update(Kronolith.text.alerts.interpolate({ 'count': this.growls }));
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

            case 'kronolithEventTag':
                $('kronolithEventTags').autocompleter.addNewItemNode(elt.getText());
                e.stop();
                return;

            case 'kronolithTaskRow':
                this.go('task:' + elt.retrieve('tasklist') + ':' + elt.retrieve('taskid'));
                e.stop();
                return;
            }

            if (elt.hasClassName('kronolithEvent')) {
                if (!Object.isUndefined(elt.retrieve('ajax'))) {
                    this.go(elt.retrieve('ajax'));
                } else {
                    this.go('event:' + elt.retrieve('calendar') + ':' + elt.retrieve('eventid'));
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
                              { 'list': tasklist, 'id': taskid },
                              function(r) {
                                  if (r.response.toggled) {
                                      this._toggleCompletion(tasklist, taskid);
                                  } else {
                                      this._toggleCompletionClass(taskid);
                                  }
                              }.bind(this));
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
            RedBox.onDisplay = null;
        };

        this.updateCalendarDropDown('kronolithEventTarget');
        $('kronolithEventForm').enable();
        $('kronolithEventForm').reset();
        this.doAction('ListTopTags', {}, this._topTags);
        if (id) {
            RedBox.loading();
            this.doAction('GetEvent', { 'cal': calendar, 'id': id }, this._editEvent.bind(this));
        } else {
            $('kronolithEventTags').autocompleter.init();
            var d = date ? this.parseDate(date) : new Date();
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
        var cal = $F('kronolithEventCalendar'),
            eventid = $F('kronolithEventId'),
            viewDates = this.viewDates(this.date, this.view),
            start = viewDates[0].dateString(),
            end = viewDates[1].dateString();
        this.startLoading(cal, start + end);
        this.doAction('SaveEvent',
                      $H($('kronolithEventForm').serialize({ 'hash': true }))
                          .merge({
                              'view': this.view,
                              'view_start': start,
                              'view_end': end
                          }),
                      function(r) {
                          if (r.response.events && eventid) {
                              this._removeEvent(eventid, cal);
                          }
                          this._loadEventsCallback(r);
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
                      $H({ 'text': text,
                           'view': this.view,
                           'view_start': start,
                           'view_end': end
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
        t = new Element('DIV');
        r.response.tags.each(function(tag) {
            t.insert(new Element('SPAN', { 'class': 'kronolithEventTag' }).update(tag.escapeHTML()));
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
        if (!Object.isUndefined(ev.at)) {
            $('kronolithEventAttendees').setValue(ev.at.pluck('l').join(', '));
            var table = $('kronolithEventTabAttendees').down('tbody');
            ev.at.each(function(attendee) {
                var tr = new Element('tr'), i;
                tr.insert(new Element('td').writeAttribute('title', attendee.l).insert(attendee.e.escapeHTML()));
                for (i = 0; i < 24; i++) {
                    tr.insert(new Element('td'));
                }
                table.insert(tr);
            });
        }

        /* Tags */
        $('kronolithEventTags').autocompleter.init(ev.tg);

        if (ev.pe) {
            $('kronolithEventSave').show();
            $('kronolithEventForm').enable();
        } else {
            $('kronolithEventSave').hide();
            $('kronolithEventForm').disable();
        }
        if (ev.pd) {
            $('kronolithEventDelete').show();
        } else {
            $('kronolithEventDelete').hide();
        }

        this.setTitle(ev.t);
        RedBox.showHtml($('kronolithEventDialog').show());
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
        $('kronolithEventStartTimeLabel').setStyle({ 'visibility': on ? 'hidden' : 'visible' });
        $('kronolithEventEndTimeLabel').setStyle({ 'visibility': on ? 'hidden' : 'visible' });
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

    _closeRedBox: function()
    {
        document.body.insert(RedBox.getWindowContents().hide());
        RedBox.close();
    },

    /* Onload function. */
    onDomLoad: function()
    {
        if (typeof ContextSensitive != 'undefined') {
            this.DMenu = new ContextSensitive({ onClick: this.contextOnClick, onShow: this.contextOnShow });
        }

        document.observe('keydown', KronolithCore.keydownHandler.bindAsEventListener(KronolithCore));
        document.observe('keyup', KronolithCore.keyupHandler.bindAsEventListener(KronolithCore));
        document.observe('click', KronolithCore.clickHandler.bindAsEventListener(KronolithCore));
        document.observe('dblclick', KronolithCore.clickHandler.bindAsEventListener(KronolithCore, true));
        document.observe('mouseover', KronolithCore.mouseHandler.bindAsEventListener(KronolithCore, 'over'));

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
    },

    toggleCalendar: function(elm)
    {
        elm.toggleClassName('on');
    },

    // By default, no context onShow action
    contextOnShow: Prototype.emptyFunction,

    // By default, no context onClick action
    contextOnClick: Prototype.emptyFunction

};

/* Initialize global event handlers. */
document.observe('dom:loaded', KronolithCore.onDomLoad.bind(KronolithCore));
Event.observe(window, 'resize', KronolithCore.onResize.bind(KronolithCore));
