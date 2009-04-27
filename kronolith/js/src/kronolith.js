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
    //   eventForm, daySizes, viewLoading

    view: '',
    ecache: $H(),
    efifo: {},
    eventsLoading: $H(),
    loading: 0,
    date: new Date(),

    doActionOpts: {
        onException: function(r, e) { KronolitCore.debug('onException', e); },
        onFailure: function(t, o) { KronolithCore.debug('onFailure', t); },
        evalJS: false,
        evalJSON: true
    },

    debug: function(label, e)
    {
        if (!this.is_logout && Kronolith.conf.debug) {
            alert(label + ': ' + (e instanceof Error ? e.name + '-' + e.message : Object.inspect(e)));
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

        if (!r.msgs_noauto) {
            this.showNotifications(r.msgs);
        }

        if (this.onDoActionComplete) {
            this.onDoActionComplete(r);
        }

        this.inAjaxCallback = false;
    },

    setTitle: function(title)
    {
        document.title = Kronolith.conf.name + ' :: ' + title;
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
            case 'horde.message':
            case 'horde.success':
            case 'horde.warning':
                this.Growler.growl(m.message, {
                    className: m.type.replace('.', '-'),
                    life: 8,
                    log: true,
                    sticky: m.type == 'horde.error'
                });
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
            var locCap = loc.capitalize();
            [ 'Day', 'Week', 'Month', 'Year', 'Tasks', 'Agenda' ].each(function(a) {
                $('kronolithNav' + a).removeClassName('on');
            });
            $('kronolithNav' + locCap).addClassName('on');
            if (this.view && this.view != loc) {
                $('kronolithView' + this.view.capitalize()).fade({ 'queue': 'end' });
            }

            switch (loc) {
            case 'day':
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
                     (loc == 'day' && date.dateString() == this.date.dateString()))) {
                         return;
                }

                this.updateView(date, loc);
                var dates = this.viewDates(date, loc);
                this._loadEvents(dates[0], dates[1], loc);
                if ($('kronolithView' + locCap)) {
                    this.viewLoading = true;
                    $('kronolithView' + locCap).appear({ 'queue': 'end', 'afterFinish': function() { this.viewLoading = false; }.bind(this) });
                }
                this.updateMinical(date, loc);
                this.date = date;

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

        case 'options':
            //this.highlightSidebar('appoptions');
            this._addHistory(loc);
            this.setTitle(Kronolith.text.prefs);
            this.iframeContent(loc, Kronolith.conf.prefs_url);
            break;
        }
    },

    /**
     * Rebuilds one of the calendar views for a new date.
     *
     * @param Date date    The date to show in the calendar.
     * @param string view  The view that's rebuilt.
     */
    updateView: function(date, view)
    {
        switch (view) {
        case 'day':
            this.dayEvents = [];
            this.dayGroups = [];
            this.allDayEvents = [];
            $('kronolithViewDay').down('.kronolithCol').setText(date.toString('D'));
            break;

        case 'week':
            this.dayEvents = [];
            this.dayGroups = [];
            this.allDayEvents = [];
            var div = $('kronolithEventsWeek').down('div'),
                th = $('kronolithViewWeekHead').down('.kronolithWeekDay'),
                td = $('kronolithViewWeekBody').down('td').next('td'),
                dates = this.viewDates(date, view),
                day = dates[0].clone();
            for (var i = 0; i < 7; i++) {
                div.writeAttribute('id', 'kronolithEventsWeek' + day.dateString());
                th.writeAttribute('date', day.dateString()).down('span').setText(day.toString('dddd, d'));
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
                day = dates[0].clone(), rows = 0, row;

            // Remove old rows. Maybe we should only rebuild the calendars if
            // necessary.
            tbody.childElements().each(function(row) {
                if (row.identify() != 'kronolithRowTemplate') {
                    row.remove();
                }
            });

            // Build new calendar view.
            while (day.compareTo(dates[1]) < 1) {
                row = tbody.insert(this.createWeekRow(day, date.getMonth(), dates).show());
                day.next().week();
                rows++;
            }
            this._equalRowHeights(tbody);

            break;
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
            .writeAttribute('date', monday.dateString())
            .next();
        while (cell) {
            dateString = day.dateString();
            cell.id = 'kronolithMonthDay' + dateString;
            cell.writeAttribute('date', dateString);
            cell.removeClassName('kronolithOtherMonth').removeClassName('kronolithToday');
            if (day.getMonth() != month) {
                cell.addClassName('kronolithOtherMonth');
            }
            if (dateString == today) {
                cell.addClassName('kronolithToday');
            }
            new Drop(cell, { onDrop: function(drop) {
                var el = DragDrop.Drags.drag.element,
                    eventid = el.readAttribute('eventid'),
                    cal = el.readAttribute('calendar');
                if (drop == el.parentNode) {
                    return;
                }
                drop.insert(el);
                this.eventsLoading[cal] = start + end;
                this.loading++;
                $('kronolithLoading').show();
                this.doAction('UpdateEvent',
                              { 'cal': cal,
                                'id': eventid,
                                'view': this.view,
                                'view_start': start,
                                'view_end': end,
                                'att': $H({ start_date: drop.readAttribute('date') }).toJSON() },
                              function(r) {
                                  if (r.response.events) {
                                      this._removeEvent(eventid, cal);
                                      this._loadEventsCallback(r);
                                  }
                              }.bind(this));
            }.bind(this) });
            cell.down('.kronolithDay')
                .setText(day.getDate())
                .writeAttribute('date', dateString);
            cell.down('.kronolithAddEvent')
                .writeAttribute('date', dateString);
            cell = cell.next();
            day.add(1).day();
        }

        return row;
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
        var trA = $(view).down('.kronolithAllDay'),
            tdA = trA.down('td'),
            tr = trA.next('tr'),
            td = tr.down('td'), height;
        this[storage].offset = tr.offsetTop - trA.offsetTop;
        this[storage].height = tr.next('tr').offsetTop - tr.offsetTop;
        this[storage].spacing = this[storage].height - tr.getHeight()
            + parseInt(td.getStyle('borderTopWidth'))
            + parseInt(td.getStyle('borderBottomWidth'));
        this[storage].allDay = tr.offsetTop - trA.offsetTop;
        this[storage].allDay -= this[storage].allDay - trA.getHeight()
            + parseInt(td.getStyle('borderTopWidth'))
            + parseInt(tdA.getStyle('borderBottomWidth'));
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
        var tbody = $('kronolithMinical').down('tbody'),
            dates = this.viewDates(date, 'month'), day = dates[0].clone(),
            weekStart, weekEnd, weekEndDay, td, tr;

        // Update header.
        $('kronolithMinicalDate').setText(date.toString('MMMM yyyy')).setAttribute('date', date.dateString());

        // Remove old calendar rows. Maybe we should only rebuild the minical
        // if necessary.
        tbody.childElements().invoke('remove');

        while (day.compareTo(dates[1]) < 1) {
            // Create calendar row and insert week number.
            if (day.getDay() == Kronolith.conf.week_start) {
                tr = new Element('tr');
                tbody.insert(tr);
                td = new Element('td', { 'class': 'kronolithMinicalWeek', date: day.dateString() }).setText(day.getWeek());;
                tr.insert(td);
                weekStart = day.clone();
                weekEnd = day.clone();
                weekEnd.add(6).days();
            }
            // Insert day cell.
            td = new Element('td', {date: day.dateString()});
            if (day.getMonth() != date.getMonth()) {
                td.addClassName('kronolithMinicalEmpty');
            }
            // Highlight days currently being displayed.
            if (view &&
                (view == 'month' ||
                 (view == 'week' && date.between(weekStart, weekEnd)) ||
                 (view == 'day' && date.compareTo(day) == 0))) {
                td.addClassName('kronolithSelected');
            }
            td.setText(day.getDate());
            tr.insert(td);
            day.next().day();
        }
    },

    /**
     * Rebuilds the list of calendars.
     */
    updateCalendarList: function()
    {
        var my = 0, shared = 0, ext = {}, extNames = {},
            remote, api, div;

        $H(Kronolith.conf.calendars.internal).each(function(cal) {
            if (cal.value.owner) {
                my++;
                div = $('kronolithMyCalendars');
            } else {
                shared++;
                div = $('kronolithSharedCalendars');
            }
            div.insert(new Element('DIV', { 'calendar': cal.key, 'calendarclass': 'internal', 'class': cal.value.show ? 'kronolithCalOn' : 'kronolithCalOff' })
                       .setStyle({ backgroundColor: cal.value.bg, color: cal.value.fg })
                       .update(cal.value.name));
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

        $H(Kronolith.conf.calendars.external).each(function(cal) {
            api = cal.key.split('/');
            if (typeof ext[api[0]] == 'undefined') {
                ext[api[0]] = {};
            }
            ext[api[0]][api[1]] = cal.value;
            extNames[api[0]] = cal.value.api;
        });
        $H(ext).each(function(api) {
            $('kronolithExternalCalendars')
                .insert(new Element('H3')
                        .insert(new Element('A', { 'class': 'kronolithAdd'  })
                                .update('+'))
                        .insert({ bottom: extNames[api.key] }))
                .insert(new Element('DIV', { 'id': 'kronolithExternalCalendar' + api.key, 'class': 'kronolithCalendars' }));
            $H(api.value).each(function(cal) {
                $('kronolithExternalCalendar' + api.key)
                    .insert(new Element('DIV', { 'calendar': api.key + '/' + cal.key, 'calendarclass': 'external', 'class': cal.value.show ? 'kronolithCalOn' : 'kronolithCalOff' })
                            .setStyle({ backgroundColor: cal.value.bg, color: cal.value.fg })
                            .update(cal.value.name));
            });
        });

        remote = $H(Kronolith.conf.calendars.remote);
        remote.each(function(cal) {
            $('kronolithRemoteCalendars')
                .insert(new Element('DIV', { 'calendar': cal.key, 'calendarclass': 'remote', 'class': cal.value.show ? 'kronolithCalOn' : 'kronolithCalOff' })
                        .setStyle({ backgroundColor: cal.value.bg, color: cal.value.fg })
                        .update(cal.value.name));
        });
        if (remote.size()) {
            $('kronolithRemoteCalendars').show();
        } else {
            $('kronolithRemoteCalendars').hide();
        }
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
            this.eventsLoading[calendar] = start + end;
            this.loading++;
            $('kronolithLoading').show();
            this._storeCache($H(), calendar);
            this.doAction('ListEvents', { start: start, end: end, cal: calendar, view: view }, this._loadEventsCallback.bind(this));
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

        var start = Date.parseExact(r.response.sig.substr(0, 8), 'yyyyMMdd'),
            end = Date.parseExact(r.response.sig.substr(8, 8), 'yyyyMMdd'),
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
     * If inserting events into day views, the calendar parameter is ignored,
     * and events from all visible calendars are inserted instead. This is
     * necessary because the complete view has to be re-rendered if events are
     * not in chronological order.
     *
     * @param Array dates      Start and end of dates to process.
     * @param string view      The view to update.
     * @param string calendar  The calendar to update.
     */
    _insertEvents: function(dates, view, calendar)
    {
        if (view == 'day' || view == 'week') {
            // The day and week views require the view to be completely
            // loaded, to correctly calculate the dimensions.
            if (this.viewLoading || this.view != view) {
                this._insertEvents.bind(this, [dates[0].clone(), dates[1].clone()], view, calendar).defer();
                return;
            }
        }

        var day = dates[0].clone(), date;
        while (!day.isAfter(dates[1])) {
            date = day.dateString();
            if (view == 'day' || view == 'week') {
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
            }

            this._getCacheForDate(date).sortBy(this._sortEvents).each(function(event) {
                if (view != 'day' && view != 'week' &&
                    calendar && calendar != event.value.calendar) {
                    return;
                }
                this._insertEvent(event, date, view);
            }, this);
            day.next().day();
        }
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
            return new Element('DIV', {
                'id': event.value.nodeId,
                'calendar': event.value.calendar,
                'eventid' : event.key,
                'class': 'kronolithEvent'
            });
        };

        switch (view) {
        case 'day':
        case 'week':
            var storage = view + 'Sizes';
            this._calculateRowSizes(storage, view == 'day' ? 'kronolithViewDay' : 'kronolithViewWeek');
            var div = _createElement(event),
                style = { 'backgroundColor': event.value.bg,
                          'color': event.value.fg };

            if (event.value.al) {
                if (view == 'day') {
                    $('kronolithViewDayBody').down('td').next('td').insert(div.setStyle(style));
                } else {
                    $('kronolithAllDay' + date).insert(div.setStyle(style));
                }
                break;
            }

            var midnight = Date.parseExact(date, 'yyyyMMdd'),
                start = Date.parse(event.value.s),
                end = Date.parse(event.value.e),
                innerDiv = new Element('DIV', { 'class': 'kronolithEventInfo' }),
                draggerTop = new Element('DIV', { 'id': event.value.nodeId + 'top', 'class': 'kronolithDragger kronolithDraggerTop' }).setStyle(style),
                draggerBottom = new Element('DIV', { 'id': event.value.nodeId + 'bottom', 'class': 'kronolithDragger kronolithDraggerBottom' }).setStyle(style);

            div.setStyle({
                'top': ((midnight.getElapsed(start) / 60000 | 0) * this[storage].height / 60 + this[storage].offset | 0) + 'px',
                'height': ((start.getElapsed(end) / 60000 | 0) * this[storage].height / 60 - this[storage].spacing | 0) + 'px',
                'width': '100%'
            })
                .insert(innerDiv.setStyle(style))
                .insert(draggerTop)
                .insert(draggerBottom);
            $(view == 'day' ? 'kronolithEventsDay' : 'kronolithEventsWeek' + date).insert(div);

            if (event.value.pe) {
                div.addClassName('kronolithEditable');
                var minTop = this[storage].allDay + this[storage].spacing,
                    step = this[storage].height / 6,
                    dragTop = draggerTop.cumulativeOffset()[1],
                    dragBottom = draggerBottom.cumulativeOffset()[1],
                    dragBottomHeight = draggerBottom.getHeight(),
                    eventTop = div.cumulativeOffset()[1],
                    maxTop = div.offsetTop + draggerBottom.offsetTop
                        - this[storage].allDay - this[storage].spacing
                        - draggerTop.getHeight()
                        - parseInt(innerDiv.getStyle('lineHeight')),
                    minBottom = div.offsetTop
                        - this[storage].allDay - this[storage].spacing
                        + draggerTop.getHeight() - dragBottomHeight
                        + parseInt(innerDiv.getStyle('lineHeight')),
                    maxBottom = 24 * KronolithCore[storage].height
                        + KronolithCore[storage].allDay
                        - dragBottomHeight - minTop;
                    opts = {
                        'threshold': 5,
                        'constraint': 'vertical',
                        'scroll': 'kronolithBody',
                        'parentElement': function() {
                            return $(view == 'day' ? 'kronolithEventsDay' : 'kronolithEventsWeek' + date);
                        },
                        'onStart': function(d, e) {
                            this.addClassName('kronolithSelected');
                        }.bind(div),
                        'onEnd': function(d, e) {
                            this[1].removeClassName('kronolithSelected');
                            this[0]._setEventText(innerDiv, event.value);
                        }.bind([this, div]),
                        'onDrag': function(d, e) {
                            var top = d.ghost.cumulativeOffset()[1],
                                draggingTop = d.ghost.hasClassName('kronolithDraggerTop'),
                                offset, height;
                            if (draggingTop) {
                                offset = top - dragTop;
                                height = this[1].offsetHeight - offset;
                                this[1].setStyle({
                                    'top': (this[1].offsetTop + offset) + 'px',
                                });
                                offset = d.ghost.offsetTop - minTop;
                                dragTop = top;
                            } else {
                                offset = top - dragBottom;
                                height = this[1].offsetHeight + offset;
                                offset = this[1].offsetTop - this[0][storage].allDay - this[0][storage].spacing;
                                dragBottom = top;
                            }
                            this[1].setStyle({
                                'height': height + 'px'
                            });
                            var hourFrom = offset / this[0][storage].height | 0,
                                minFrom = Math.round(offset % this[0][storage].height / step * 10).toPaddedString(2),
                                hourTo = (offset + height + this[0][storage].spacing) / this[0][storage].height | 0,
                                minTo = Math.round((offset + height + this[0][storage].spacing) % this[0][storage].height / step * 10).toPaddedString(2)
                            innerDiv.update('(' + hourFrom + ':' + minFrom + '-' + hourTo + ':' + minTo + ') ' + event.value.t);
                        }.bind([this, div])
                    };

                opts.snap = function(x, y, elm) {
                    y = Math.max(0, step * (Math.min(maxTop, y - minTop) / step | 0)) + minTop;
                    return [0, y];
                }
                new Drag(event.value.nodeId + 'top', opts);

                opts.snap = function(x, y, elm) {
                    y = Math.min(maxBottom, step * (Math.max(minBottom, y - minTop - dragBottomHeight) / step | 0) + dragBottomHeight) + minTop;
                    return [0, y];
                }
                new Drag(event.value.nodeId + 'bottom', opts);
            }

            var column = 1, columns, width, left, conflict = false,
                pos = this.dayGroups.length, placeFound = false;

            this.dayEvents.each(function(ev) {
                var evStart = Date.parse(ev.s), evEnd = Date.parse(ev.e);
                if (!evEnd.isAfter(start)) {
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
        }

        this._setEventText(div, event.value)
            .observe('mouseover', div.addClassName.curry('kronolithSelected'))
            .observe('mouseout', div.removeClassName.curry('kronolithSelected'));
    },

    _setEventText: function(div, event)
    {
        div.setText(event.t);
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

    _removeEvent: function(event, calendar)
    {
        this._deleteCache(event, calendar);
        $('kronolithViewMonth').select('div[calendar=' + calendar + '][eventid=' + event + ']').invoke('remove');
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
            start.moveToBeginOfWeek();
            end.moveToEndOfWeek();
            break;
        case 'month':
            start.setDate(1);
            start.moveToBeginOfWeek();
            end.moveToLastDayOfMonth();
            end.moveToEndOfWeek();
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
     * @param object events    A list of calendars and events as return from
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

        // Create empty cache entries for all dates.
        if (typeof dates != 'undefined') {
            var calHash = this.ecache.get(calendar[0]).get(calendar[1]),
                day = dates[0].clone(), date;
            while (!day.isAfter(dates[1])) {
                date = day.dateString();
                if (!calHash.get(date)) {
                    calHash.set(date, {});
                }
                day.add(1).day();
            }
        }

        // Store calendar string in event objects.
        var cal = calendar.join('|');
        $H(events).each(function(date) {
            $H(date.value).each(function(event) {
                event.value.calendar = cal;
            });
        });

        // Store events in cache.
        this.ecache.get(calendar[0]).set(calendar[1], this.ecache.get(calendar[0]).get(calendar[1]).merge(events));
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
            delete day.value[event];
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
        return Date.parse(event.value.s).toString('HHmmss')
            + (240000 - parseInt(Date.parse(event.value.e).toString('HHmmss'))).toPaddedString('0');
    },

    _addHistory: function(loc, data)
    {
        if (Horde.dhtmlHistory.getCurrentLocation() != loc) {
            Horde.dhtmlHistory.add(loc, data);
        }
    },

    iframeContent: function(name, loc)
    {
        if (name === null) {
            name = loc;
        }

        var container = $('dimpmain_portal'), iframe;
        if (!container) {
            this.showNotifications([ { type: 'horde.error', message: 'Bad portal!' } ]);
            return;
        }

        iframe = new Element('IFRAME', { id: 'iframe' + name, className: 'iframe', frameBorder: 0, src: loc });
        this._resizeIE6Iframe(iframe);

        // Hide menu in prefs pages.
        if (name == 'options') {
            iframe.observe('load', function() { $('iframeoptions').contentWindow.document.getElementById('menu').style.display = 'none'; });
        }

        container.insert(iframe);
    },

    onResize: function(noupdate, nowait)
    {
    },

    /* Keydown event handler */
    keydownHandler: function(e)
    {
        var kc = e.keyCode || e.charCode;

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

            case 'kronolithToday':
                this.go(Kronolith.conf.login_view);
                e.stop();
                return;

            case 'id_fullday':
                this.eventForm.select('.edit_at').each(Element.toggle);
                e.stop();
                return;

            case 'kronolithNewEvent':
                this.editEvent();
                e.stop();
                return;

            case 'kronolithEventSave':
                var cal = $F('kronolithEventCalendar'),
                    eventid = $F('kronolithEventId'),
                    viewDates = this.viewDates(this.date, this.view),
                    start = viewDates[0].dateString(),
                    end = viewDates[1].dateString();
                this.eventsLoading[cal] = start + end;
                this.loading++;
                $('kronolithLoading').show();
                this.doAction('SaveEvent',
                              $H($('kronolithEventForm').serialize({ 'hash': true }))
                                  .merge({
                                      'view': this.view,
                                      'view_start': start,
                                      'view_end': end
                                  }),
                              function(r) {
                                  if (r.response.events) {
                                      if (eventid) {
                                          this._removeEvent(eventid, cal);
                                      }
                                      this._loadEventsCallback(r);
                                  }
                                  this._closeRedBox();
                              }.bind(this));
                e.stop();
                return;

            case 'kronolithEventDelete':
                // todo: fix using new id(s).
                var cal = $F('kronolithEventCalendar'),
                    eventid = $F('kronolithEventId'),
                    elm = $('kronolithEvent' + this.view + cal + eventid);
                this.doAction('DeleteEvent',
                              { 'cal': cal, 'id': eventid },
                              function(r) {
                                  if (r.response.deleted) {
                                      this._removeEvent(eventid, cal);
                                  } else {
                                      elm.toggle();
                                  }
                              }.bind(this));
                elm.hide();
                this._closeRedBox();
                e.stop();
                return;

            case 'kronolithEventCancel':
                this._closeRedBox();
                e.stop();
                return;

            case 'kronolithNavDay':
            case 'kronolithNavWeek':
            case 'kronolithNavMonth':
            //case 'kronolithNavYear':
            //case 'kronolithNavTasks':
            //case 'kronolithNavAgenda':
                this.go(id.substring(12).toLowerCase() + ':' + this.date.dateString());
                e.stop();
                return;

            case 'kronolithMinicalDate':
                this.go('month:' + orig.readAttribute('date'));
                e.stop();
                return;

            case 'kronolithMinical':
                if (orig.id == 'kronolithMinicalPrev') {
                    var date = this.parseDate($('kronolithMinicalDate').readAttribute('date'));
                    date.previous().month();
                    this.updateMinical(date);
                    e.stop();
                    return;
                }
                if (orig.id == 'kronolithMinicalNext') {
                    var date = this.parseDate($('kronolithMinicalDate').readAttribute('date'));
                    date.next().month();
                    this.updateMinical(date);
                    e.stop();
                    return;
                }

                var tmp = orig;
                if (tmp.tagName != 'td') {
                    tmp.up('td');
                }
                if (tmp && tmp.readAttribute('date')) {
                    if (tmp.hasClassName('kronolithMinicalWeek')) {
                        this.go('week:' + tmp.readAttribute('date'));
                    } else if (!tmp.hasClassName('empty')) {
                        this.go('day:' + tmp.readAttribute('date'));
                    }
                }
                e.stop();
                return;

            case 'kronolithViewMonth':
                if (orig.hasClassName('kronolithFirstCol')) {
                    var date = orig.readAttribute('date');
                    if (date) {
                        this.go('week:' + date);
                        e.stop();
                        return;
                    }
                } else if (orig.hasClassName('kronolithDay')) {
                    var date = orig.readAttribute('date');
                    if (date) {
                        this.go('day:' + date);
                        e.stop();
                        return;
                    }
                }
                e.stop();
                return;

            case 'alertsloglink':
                tmp = $('alertsloglink').down('A');
                if (this.Growler.toggleLog()) {
                    tmp.update(DIMP.text.hidealog);
                } else {
                    tmp.update(DIMP.text.showalog);
                }
                break;
            }

            if (elt.hasClassName('kronolithEvent')) {
                this.editEvent(elt.readAttribute('calendar'), elt.readAttribute('eventid'));
                e.stop();
                return;
            } else if (elt.hasClassName('kronolithAddEvent')) {
                this.editEvent(null, null, elt.readAttribute('date'));
                e.stop();
                return;
            } else if (elt.hasClassName('kronolithWeekDay')) {
                this.go('day:' + elt.readAttribute('date'));
                e.stop();
                return;
            } else if (elt.hasClassName('kronolithEventTag')) {
                $('tags').autocompleter.addNewItemNode(elt.getText());
                e.stop();
                return;
            }

            calClass = elt.readAttribute('calendarclass');
            if (calClass) {
                var calendar = elt.readAttribute('calendar');
                Kronolith.conf.calendars[calClass][calendar].show = !Kronolith.conf.calendars[calClass][calendar].show;
                if (typeof this.ecache.get(calClass) == 'undefined' ||
                    typeof this.ecache.get(calClass).get(calendar) == 'undefined') {
                    var dates = this.viewDates(this.date, this.view);
                    this._loadEvents(dates[0], dates[1], this.view, [[calClass, calendar]]);
                } else {
                    $('kronolithBody').select('div[calendar=' + calClass + '|' + calendar + ']').invoke('toggle');
                }
                elt.toggleClassName('kronolithCalOn');
                elt.toggleClassName('kronolithCalOff');
                if (calClass == 'remote' || calClass == 'external') {
                    calendar = calClass + '_' + calendar;
                }
                this.doAction('SaveCalPref', { toggle_calendar: calendar });
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
        RedBox.onDisplay = function() {
            try {
                $('kronolithEventForm').focusFirstElement();
            } catch(e) {}
            RedBox.onDisplay = null;
        };

        $('tags').autocompleter.init();
        $('kronolithEventForm').enable();
        $('kronolithEventForm').reset();
        this.doAction('ListTopTags', {}, this._topTags);
        if (id) {
            RedBox.loading();
            this.doAction('GetEvent', { 'cal': calendar, 'id': id }, this._editEvent.bind(this));
        } else {
            var d = date ? Date.parseExact(date, 'yyyyMMdd') : new Date();
            $('kronolithEventId').value = '';
            $('kronolithEventCalendar').value = Kronolith.conf.default_calendar;
            $('kronolithEventDelete').hide();
            $('kronolithEventStartDate').value = d.toString(Kronolith.conf.date_format);
            $('kronolithEventStartTime').value = d.toString(Kronolith.conf.time_format);
            d.add(1).hour();
            $('kronolithEventEndDate').value = d.toString(Kronolith.conf.date_format);
            $('kronolithEventEndTime').value = d.toString(Kronolith.conf.time_format);
            RedBox.showHtml($('kronolithEventDialog').show());
            this.eventForm = RedBox.getWindowContents();
        }
    },

    _topTags: function(r)
    {
        $('kronolithEventTopTags').update();
        if (!r.response.tags) {
            return;
        }
        r.response.tags.each(function(tag) {
            $('kronolithEventTopTags').insert(new Element('span', { 'class': 'kronolithEventTag' }).update(tag));
        });
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
            return;
        }

        var ev = r.response.event;
        $('kronolithEventId').value = ev.id;
        $('kronolithEventCalendar').value = ev.ty + '|' + ev.c;
        $('kronolithEventTitle').value = ev.t;
        $('kronolithEventLocation').value = ev.l;
        $('kronolithEventAllday').checked = ev.al;
        $('kronolithEventStartDate').value = ev.sd
        $('kronolithEventStartTime').value = ev.st;
        $('kronolithEventEndDate').value = ev.ed;
        $('kronolithEventEndTime').value = ev.et;
        $('tags').autocompleter.init(ev.tg);
        if (ev.r) {
            // @todo: refine
            $A($('kronolithEventRecurrence').options).find(function(option) {
                return option.value == ev.r || option.value == -1;
                }).selected = true;
        }
        if (ev.pe) {
            $('kronolithEventSave').show();
            $('kronolithEventForm').enable();
        } else {
            $('kronolithEventSave').hide();
            $('kronolithEventForm').disable();
            $('kronolithEventCancel').enable();
        }
        if (ev.pd) {
            $('kronolithEventDelete').show();
        } else {
            $('kronolithEventDelete').hide();
        }

        RedBox.showHtml($('kronolithEventDialog').show());
        this.eventForm = RedBox.getWindowContents();
    },

    _closeRedBox: function()
    {
        RedBox.close();
        this.eventForm = null;
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
            location: 'br',
            log: true,
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

/* Helper methods for setting/getting element text without mucking
 * around with multiple TextNodes. */
Element.addMethods({
    setText: function(element, text)
    {
        var t = 0;
        $A(element.childNodes).each(function(node) {
            if (node.nodeType == 3) {
                if (t++) {
                    Element.remove(node);
                } else {
                    node.nodeValue = text;
                }
            }
        });

        if (!t) {
            $(element).insert(text);
        }

        return element;
    },

    getText: function(element, recursive)
    {
        var text = '';
        $A(element.childNodes).each(function(node) {
            if (node.nodeType == 3) {
                text += node.nodeValue;
            } else if (recursive && node.hasChildNodes()) {
                text += $(node).getText(true);
            }
        });
        return text;
    }
});

/* Create some utility functions. */
Object.extend(Array.prototype, {
    // Need our own diff() function because prototypejs's without() function
    // does not handle array input.
    diff: function(values)
    {
        return this.select(function(value) {
            return !values.include(value);
        });
    },
    numericSort: function()
    {
        return this.collect(Number).sort(function(a,b) {
            return (a > b) ? 1 : ((a < b) ? -1 : 0);
        });
    }
});

Object.extend(String.prototype, {
    // We define our own version of evalScripts() to make sure that all
    // scripts are running in the same scope and that all functions are
    // defined in the global scope. This is not the case when using
    // prototype's evalScripts().
    evalScripts: function()
    {
        var re = /function\s+([^\s(]+)/g;
        this.extractScripts().each(function(s) {
            var func;
            eval(s);
            while (func = re.exec(s)) {
                window[func[1]] = eval(func[1]);
            }
        });
    }
});

Object.extend(Date.prototype, {
    /**
     * Moves a date to the end of the corresponding week.
     *
     * @return Date  The same Date object, now pointing to the end of the week.
     */
    moveToEndOfWeek: function()
    {
        var weekEndDay = Kronolith.conf.week_start + 6;
        if (weekEndDay > 6) {
            weekEndDay -= 7;
        }
        if (this.getDay() != weekEndDay) {
            this.moveToDayOfWeek(weekEndDay, 1);
        }
        return this;
    },

    /**
     * Moves a date to the begin of the corresponding week.
     *
     * @return Date  The same Date object, now pointing to the begin of the
     *               week.
     */
    moveToBeginOfWeek: function()
    {
        if (this.getDay() != Kronolith.conf.week_start) {
            this.moveToDayOfWeek(Kronolith.conf.week_start, -1);
        }
        return this;
    },

    /**
     * Format date and time to be passed around as a short url parameter,
     * cache id, etc.
     *
     * @return string  Date and time.
     */
    dateString: function()
    {
        return this.toString('yyyyMMdd');
    }

});

/* Initialize global event handlers. */
document.observe('dom:loaded', KronolithCore.onDomLoad.bind(KronolithCore));
Event.observe(window, 'resize', KronolithCore.onResize.bind(KronolithCore));
