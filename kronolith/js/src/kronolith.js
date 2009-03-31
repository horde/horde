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
    //   DMenu, alertrequest, inAjaxCallback, is_logout, onDoActionComplete,
    //   eventForm

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
            case 'kronolith.request':
            case 'kronolith.sticky':
                var iefix, log, tmp,
                    alerts = $('hordeAlerts'),
                    div = new Element('DIV', { className: m.type.replace('.', '-') }),
                    msg = m.message;

                if (!alerts) {
                    alerts = new Element('DIV', { id: 'hordeAlerts' });
                    $(document.body).insert(alerts);
                }

                if ($w('kronolith.request kronolith.sticky').indexOf(m.type) == -1) {
                    msg = msg.unescapeHTML().unescapeHTML();
                }
                alerts.insert(div.update(msg));

                // IE6 has a bug that does not allow the body of a div to be
                // clicked to trigger an onclick event for that div (it only
                // seems to be an issue if the div is overlaying an element
                // that itself contains an image).  However, the alert box
                // normally displays over the message list, and we use several
                // graphics in the default message list layout, so we see this
                // buggy behavior 99% of the time.  The workaround is to
                // overlay the div with a like sized div containing a clear
                // gif, which tricks IE into the correct behavior.
                if (Kronolith.conf.is_ie6) {
                    iefix = new Element('DIV', { id: 'hordeIE6AlertsFix' }).clonePosition(div, { setLeft: false, setTop: false });
                    iefix.insert(div.remove());
                    alerts.insert(iefix);
                }

                if ($w('horde.error kronolith.request kronolith.sticky').indexOf(m.type) == -1) {
                    this.alertsFade.bind(this, div).delay(m.type == 'horde.warning' ? 10 : 3);
                }

                if (m.type == 'kronolith.request') {
                    this.alertrequest = div;
                }

                if (tmp = $('hordeAlertslog')) {
                    switch (m.type) {
                    case 'horde.error':
                        log = Kronolith.text.alog_error;
                        break;

                    case 'horde.message':
                        log = Kronolith.text.alog_message;
                        break;

                    case 'horde.success':
                        log = Kronolith.text.alog_success;
                        break;

                    case 'horde.warning':
                        log = Kronolith.text.alog_warning;
                        break;
                    }

                    if (log) {
                        tmp = tmp.down('DIV UL');
                        if (tmp.down().hasClassName('hordeNoalerts')) {
                            tmp.down().remove();
                        }
                        tmp.insert(new Element('LI').insert(new Element('P', { className: 'label' }).insert(log)).insert(new Element('P', { className: 'indent' }).insert(msg).insert(new Element('SPAN', { className: 'alertdate'}).insert('[' + (new Date).toLocaleString() + ']'))));
                    }
                }
            }
        }, this);
    },

    alertsFade: function(elt)
    {
        if (elt) {
            Effect.Fade(elt, { duration: 1.5, afterFinish: this.removeAlert.bind(this) });
        }
    },

    toggleAlertsLog: function()
    {
        var alink = $('alertsloglink').down('A'),
            div = $('hordeAlertslog').down('DIV'),
            opts = { duration: 0.5 };
        if (div.visible()) {
            Effect.BlindUp(div, opts);
            alink.update(Kronolith.text.showalog);
        } else {
            Effect.BlindDown(div, opts);
            alink.update(Kronolith.text.hidealog);
        }
    },

    removeAlert: function(effect)
    {
        try {
            var elt = $(effect.element),
                parent = elt.up();

            elt.remove();
            if (!parent.childElements().size() &&
                parent.readAttribute('id') == 'hordeIE6AlertsFix') {
                parent.remove();
            }
        } catch (e) {
            this.debug('removeAlert', e);
        }
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
                $('kronolithView' + this.view.capitalize()).fade();
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
                     (loc == 'day' && date.dateString() == this.date.dateString()))) {
                         return;
                }

                this.updateView(date, loc);
                if ($('kronolithView' + locCap)) {
                    $('kronolithView' + locCap).appear();
                }
                this.updateMinical(date, loc);
                this.date = date;

                break;

            default:
                if ($('kronolithView' + locCap)) {
                    $('kronolithView' + locCap).appear();
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
            $('kronolithViewDay').down('.kronolithCol').setText(date.toString('D'));
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
                row = tbody.insert(this.createWeekRow(day, date.getMonth()).show());
                day.next().week();
                rows++;
            }
            this._equalRowHeights(tbody);

            // Load events.
            this._loadEvents(dates[0], dates[1], this._loadEventsCallback.bind(this), view);

            break;
        }
    },

    /**
     * Creates a single row of day cells for usage in the month and multi-week
     * views.
     *
     * @param Date date      The first day to show in the row.
     * @oaram integer month  The current month. Days not from the current month
     *                       get the kronolithOtherMonth CSS class assigned.
     *
     * @return Element  The element rendering a week row.
     */
    createWeekRow: function(date, month)
    {
        var monday = date.clone(), day = date.clone(),
            today = new Date().dateString(),
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
            if (typeof month != 'undefined' && day.getMonth() != month) {
                cell.addClassName('kronolithOtherMonth');
            }
            if (dateString == today) {
                cell.addClassName('kronolithToday');
            }
            new Drop(cell, { onDrop: function(drop) {
                var el = DragDrop.Drags.drag.element;
                if (drop == el.parentNode) {
                    return;
                }
                drop.insert(el);
                this.doAction('UpdateEvent',
                              { cal: el.readAttribute('calendar'),
                                id: el.readAttribute('eventid'),
                                att: $H({ start_date: drop.readAttribute('date') }).toJSON() });
            }.bind(this) });
            cell.down('.kronolithDay')
                .setText(day.getDate())
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
    _loadEvents: function(firstDay, lastDay, callback, view, calendars)
    {
        var start = firstDay.dateString(), end = lastDay.dateString(),
            driver, calendar;

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
            var calendar = cal.join('|');
            this.eventsLoading[calendar] = start + end;
            this.loading++;
            $('kronolithLoading').show();
            this._storeCache($H(), calendar);
            this.doAction('ListEvents', { start: start, end: end, cal: calendar, view: view }, callback.bind(this));
        }, this);
    },

    /**
     * Callback method for inserting events in the current view.
     *
     * @param object r  The ajax response object.
     */
    _loadEventsCallback: function(r)
    {
        var div;

        // Hide spinner.
        this.loading--;
        if (!this.loading) {
            $('kronolithLoading').hide();
        }

        if (r.response.events) {
            this._storeCache(r.response.events, r.response.cal);

            // Check if this is the still the result of the most current
            // request.
            if (r.response.view != this.view ||
                r.response.sig != this.eventsLoading[r.response.cal]) {
                return;
            }

            $H(r.response.events).each(function(date) {
                $H(date.value).each(function(event) {
                    switch (this.view) {
                    case 'month':
                        div = new Element('DIV', {
                            'id': 'kronolithEventmonth' + r.response.cal + event.key,
                            'calendar': r.response.cal,
                            'eventid' : event.key,
                            'class': 'kronolithEvent',
                            'style': 'background-color:' + event.value.bg + ';color:' + event.value.fg
                        });
                        div.setText(event.value.t)
                            .observe('mouseover', div.addClassName.curry('kronolithSelected'))
                            .observe('mouseout', div.removeClassName.curry('kronolithSelected'));
                        if (event.value.a) {
                            div.insert(' ')
                                .insert(new Element('IMG', { 'src': Kronolith.conf.URI_IMG + 'alarm-' + event.value.fg.substr(1) + '.png', 'title': Kronolith.text.alarm + ' ' + event.value.a }));
                        }
                        if (event.value.r) {
                            div.insert(' ')
                                .insert(new Element('IMG', { 'src': Kronolith.conf.URI_IMG + 'recur-' + event.value.fg.substr(1) + '.png', 'title': Kronolith.text.recur[event.value.r] }));
                        }
                        $('kronolithMonthDay' + date.key).insert(div);
                        if (event.value.e) {
                            div.setStyle({ 'cursor': 'move' });
                            new Drag('kronolithEventmonth' + r.response.cal + event.key, { threshold: 5, parentElement: function() { return $('kronolithViewMonthBody'); }, snapToParent: true });
                        }
                        break;
                    }
                }, this);
            }, this);
        }
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

    _storeCache: function(events, calendar)
    {
        if (Object.isString(calendar)) {
            calendar = calendar.split('|');
        }
        if (!this.ecache[calendar[0]]) {
            this.ecache[calendar[0]] = $H();
        }
        if (!this.ecache[calendar[0]][calendar[1]]) {
            this.ecache[calendar[0]][calendar[1]] = $H();
        }
        this.ecache[calendar[0]][calendar[1]] = this.ecache[calendar[0]][calendar[1]].merge(events);
    },

    _deleteCache: function(event, calendar)
    {
        if (Object.isString(calendar)) {
            calendar = calendar.split('|');
        }
        if (!this.ecache[calendar[0]] ||
            !this.ecache[calendar[0]][calendar[1]]) {
            return;
        }
        this.ecache[calendar[0]][calendar[1]].each(function(day) {
            if (day.value[event]) {
                delete day.value[event];
            }
        });
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
        this._resizeIE6();
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

        if (this.alertrequest) {
            this.alertsFade(this.alertrequest);
            this.alertrequest = null;
        }

        while (Object.isElement(elt)) {
            id = elt.readAttribute('id');

            switch (id) {
            case 'kronolithLogo':
                this.go('portal');
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
                this.go(id.substring(12).toLowerCase());
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
                this.toggleAlertsLog();
                break;

            case 'hordeAlerts':
                this.alertsFade(elt);
                break;
            }

            if (elt.hasClassName('kronolithEvent')) {
                this.editEvent(elt.readAttribute('calendar'), elt.readAttribute('eventid'));
                e.stop();
                return;
            } else if (elt.hasClassName('kronolithEventTag')) {
                var etags = $F('kronolithEventTags');
                $('kronolithEventTags').value = (etags ? etags + ', ' :  '') + elt.getText();
                e.stop();
                return;
            }
            
            calClass = elt.readAttribute('calendarclass');
            if (calClass) {
                var calendar = elt.readAttribute('calendar');
                if (typeof this.ecache[calClass] == 'undefined' ||
                    typeof this.ecache[calClass][calendar] == 'undefined') {
                    var dates = this.viewDates(this.date, this.view);
                    this._loadEvents(dates[0], dates[1], this._loadEventsCallback.bind(this), this.view, [[calClass, calendar]]);
                } else {
                    $('kronolithViewMonth').select('div[calendar=' + calClass + '|' + calendar + ']').invoke('toggle');
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

    editEvent: function(calendar, id)
    {
        RedBox.onDisplay = function() {
            try {
                $('kronolithEventForm').focusFirstElement();
            } catch(e) {}
            RedBox.onDisplay = null;
        };

        this.doAction('ListTopTags', {}, this._topTags);
        if (id) {
            RedBox.loading();
            this.doAction('GetEvent', { 'cal': calendar, 'id': id }, this._editEvent.bind(this));
        } else {
            var d = new Date();
            $('kronolithEventForm').enable();
            $('kronolithEventForm').reset();
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

        try {
            var ev = r.response.event;
            $('kronolithEventId').value = ev.i;
            $('kronolithEventCalendar').value = ev.ty + '|' + ev.c;
            $('kronolithEventTitle').value = ev.t;
            $('kronolithEventLocation').value = ev.l;
            $('kronolithEventAllday').checked = ev.al;
            $('kronolithEventStartDate').value = ev.sd
            $('kronolithEventStartTime').value = ev.st;
            $('kronolithEventEndDate').value = ev.ed;
            $('kronolithEventEndTime').value = ev.et;
            $('kronolithEventTags').value = ev.tg.join(', ');
            if (ev.r) {
                // @todo: refine
                $A($('kronolithEventRecurrence').options).find(function(option) {
                    return option.value == ev.r;
                }).selected = true;
            }
            if (ev.e) {
                $('kronolithEventSave').show();
                $('kronolithEventForm').enable();
            } else {
                $('kronolithEventSave').hide();
                $('kronolithEventForm').disable();
                $('kronolithEventCancel').enable();
            }
            if (ev.d) {
                $('kronolithEventDelete').show();
            } else {
                $('kronolithEventDelete').hide();
            }
        } catch (e) {}

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
        if (elm.hasClassName('on')) {
            elm.removeClassName('on');
        } else {
            elm.addClassName('on');
        }
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
