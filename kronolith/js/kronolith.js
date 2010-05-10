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
    //   DMenu, Growler, inAjaxCallback, is_logout,
    //   daySizes, viewLoading, groupLoading, freeBusy, colorPicker, duration

    view: '',
    ecache: $H(),
    holidays: [],
    tcache: $H(),
    efifo: {},
    eventsLoading: {},
    loading: 0,
    fbLoading: 0,
    redBoxLoading: false,
    inOptions: false,
    date: Date.today(),
    tasktype: 'incomplete',
    growls: 0,
    alarms: [],
    wrongFormat: $H(),
    mapMarker: null,
    map: null,
    mapInitialized: false,
    search: 'future',
    effectDur: 0.4,
    macos: navigator.appVersion.indexOf('Mac') !=- 1,

    doActionOpts: {
        onException: function(parentfunc, r, e)
        {
            /* Make sure loading images are closed. */
            this.loading--;
            if (!this.loading) {
                $('kronolithLoading').hide();
            }
            this.closeRedBox();
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
                this.logout(m.message);
                return true;

            case 'horde.alarm':
                var alarm = m.flags.alarm;
                // Only show one instance of an alarm growl.
                if (this.alarms.include(alarm.id)) {
                    break;
                }

                this.alarms.push(alarm.id);

                message = alarm.title.escapeHTML();
                if (alarm.params && alarm.params.notify) {
                    if (alarm.params.notify.ajax) {
                        message = new Element('a')
                            .insert(message)
                            .observe('click', function() {
                                this.Growler.ungrowl(growl);
                                this.go(alarm.params.notify.ajax);
                            }.bind(this));
                    } else if (alarm.params.notify.url) {
                        message = new Element('a', { href: alarm.params.notify.url })
                            .insert(message);
                    }
                    if (alarm.params.notify.sound) {
                        Sound.play(alarm.params.notify.sound);
                    }
                }
                message = new Element('div')
                    .insert(message);
                if (alarm.params && alarm.params.notify &&
                    alarm.params.notify.subtitle) {
                    message.insert(new Element('br')).insert(alarm.params.notify.subtitle);
                }
                if (alarm.user) {
                    var select = '<select>';
                    $H(Kronolith.conf.snooze).each(function(snooze) {
                        select += '<option value="' + snooze.key + '">' + snooze.value + '</option>';
                    });
                    select += '</select>';
                    message.insert('<br /><br />' + Kronolith.text.snooze.interpolate({ time: select, dismiss_start: '<input type="button" value="', dismiss_end: '" class="button ko" />' }));
                }
                var growl = this.Growler.growl(message, {
                    className: 'horde-alarm',
                    life: 8,
                    log: false,
                    sticky: true
                });
                growl.store('alarm', alarm.id);

                document.observe('Growler:destroyed', function(e) {
                    var id = e.element().retrieve('alarm');
                    if (id) {
                        this.alarms = this.alarms.without(id);
                    }
                }.bindAsEventListener(this));

                if (alarm.user) {
                    message.down('select').observe('change', function(e) {
                        if (e.element().getValue()) {
                            this.Growler.ungrowl(growl);
                            new Ajax.Request(
                                Kronolith.conf.URI_SNOOZE,
                                { parameters: { alarm: alarm.id,
                                                snooze: e.element().getValue() } });
                        }
                    }.bindAsEventListener(this));
                    message.down('input[type=button]').observe('click', function(e) {
                        this.Growler.ungrowl(growl);
                        new Ajax.Request(
                            Kronolith.conf.URI_SNOOZE,
                            { parameters: { alarm: alarm.id,
                                            snooze: -1 } });
                    }.bindAsEventListener(this));
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
                    order = 'horde-error,horde-warning,horde-message,horde-success,kronolithNotifications',
                    open = notify.hasClassName('kronolithClose');
                notify.removeClassName('kronolithClose');
                if (order.indexOf(notify.className) > order.indexOf(className)) {
                    notify.className = className;
                }
                if (open) {
                    notify.addClassName('kronolithClose');
                }
                break;
            }
        }, this);
    },

    logout: function()
    {
        this.is_logout = true;
        this.redirect(Kronolith.conf.URI_AJAX + 'logOut');
    },

    redirect: function(url, force)
    {
        var ptr = parent.frames.horde_main ? parent : window;

        ptr.location.assign(this.addURLParam(url));

        // Catch browsers that don't redirect on assign().
        if (force && !Prototype.Browser.WebKit) {
            (function() { ptr.location.reload(); }).delay(0.5);
        }
    },

    addURLParam: function(url, params)
    {
        var q = url.indexOf('?');
        params = $H(params);

        if (Kronolith.conf.SESSION_ID) {
            params.update(Kronolith.conf.SESSION_ID.toQueryParams());
        }

        if (q != -1) {
            params.update(url.toQueryParams());
            url = url.substring(0, q);
        }

        return params.size() ? (url + '?' + params.toQueryString()) : url;
    },

    go: function(fullloc, data)
    {
        var locParts = fullloc.split(':');
        var loc = locParts.shift();

        if (this.inOptions && loc != 'options') {
            this.redirect(window.location.href.sub(window.location.hash, '#' + fullloc), true);
            return;
        }

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

                if (this.view != 'agenda' &&
                    this.view == loc && date.getYear() == this.date.getYear() &&
                    ((loc == 'year') ||
                     (loc == 'month' && date.getMonth() == this.date.getMonth()) ||
                     (loc == 'week' && date.getRealWeek() == this.date.getRealWeek()) ||
                     ((loc == 'day'  || loc == 'agenda') && date.dateString() == this.date.dateString()))) {
                         return;
                }

                this.updateView(date, loc);
                var dates = this.viewDates(date, loc);
                this.loadEvents(dates[0], dates[1], loc);
                if ($('kronolithView' + locCap)) {
                    this.viewLoading = true;
                    $('kronolithView' + locCap).appear({
                            duration: this.effectDur,
                            queue: 'end',
                            afterFinish: function() {
                                if (loc == 'week' || loc == 'day') {
                                    this.calculateRowSizes(loc + 'Sizes', 'kronolithView' + locCap);
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
                    !($w('all complete incomplete future').include(tasktype))) {
                    return;
                }
                this.tasktype = tasktype;
                $w('All Complete Incomplete Future').each(function(tasktype) {
                    $('kronolithTasks' + tasktype).up().removeClassName('activeTab');
                });
                $('kronolithTasks' + this.tasktype.capitalize()).up().addClassName('activeTab');
                this.loadTasks(this.tasktype);
                if ($('kronolithView' + locCap)) {
                    this.viewLoading = true;
                    $('kronolithView' + locCap).appear({
                        duration: this.effectDur,
                        queue: 'end',
                        afterFinish: function() {
                            this.viewLoading = false;
                        }.bind(this) });
                }
                $('kronolithLoading' + loc).insert($('kronolithLoading').remove());
                this.updateMinical(this.date);

                break;

            default:
                if ($('kronolithView' + locCap)) {
                    this.viewLoading = true;
                    $('kronolithView' + locCap).appear({
                        duration: this.effectDur,
                        queue: 'end',
                        afterFinish: function() {
                            this.viewLoading = false;
                        }.bind(this) });
                }
                break;
            }

            this.addHistory(fullloc);
            this.view = loc;
            break;

        case 'search':
            var cals = [], time = locParts[0], term = locParts[1],
                query = Object.toJSON({ title: term });

            if (!($w('all past future').include(time))) {
                return;
            }

            this.search = time;
            $w('All Past Future').each(function(time) {
                $('kronolithSearch' + time).up().removeClassName('activeTab');
            });
            $('kronolithSearch' + this.search.capitalize()).up().addClassName('activeTab');
            this.closeView('agenda');
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
            this.doAction('searchEvents',
                          { cals: cals.toJSON(), query: query, time: this.search },
                          function(r) {
                              // Hide spinner.
                              this.loading--;
                              if (!this.loading) {
                                  $('kronolithLoading').hide();
                              }
                              if (r.response.view != 'search' ||
                                  r.response.query != this.eventsLoading['search']) {
                                  return;
                              }
                              if (Object.isUndefined(r.response.events)) {
                                  $('kronolithAgendaNoItems').show();
                                  return;
                              }
                              delete this.eventsLoading['search'];
                              $H(r.response.events).each(function(calendars) {
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
                          }.bind(this));
            this.viewLoading = true;
            $('kronolithViewAgenda').appear({
                duration: this.effectDur,
                queue: 'end',
                afterFinish: function() {
                    this.viewLoading = false;
                }.bind(this) });
            $('kronolithLoadingagenda').insert($('kronolithLoading').remove());
            this.updateMinical(this.date);
            this.addHistory(fullloc);
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
            default:
                // Editing event.
                var date = locParts.pop(),
                    event = locParts.pop(),
                    calendar = locParts.join(':');
                this.editEvent(calendar, event, date);
                break;
            }
            this.addHistory(fullloc);
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
            this.addHistory(fullloc);
            break;

        case 'calendar':
            if (!this.view) {
                this.go(Kronolith.conf.login_view);
                this.go.bind(this, fullloc, data).defer();
                return;
            }
            this.editCalendar(locParts.join(':'));
            this.addHistory(fullloc);
            break;

        case 'options':
            var url = Kronolith.conf.prefs_url;
            if (data) {
                url += (url.include('?') ? '&' : '?') + $H(data).toQueryString();
            }
            this.inOptions = true;
            this.closeView('iframe');
            this.iframeContent(url);
            this.setTitle(Kronolith.text.prefs);
            this.updateMinical(this.date);
            this.addHistory(loc);
            break;

        case 'app':
            this.addHistory(fullloc);
            this.closeView('iframe');
            var app = locParts.shift();
            if (data) {
                this.iframeContent(data);
            } else if (Kronolith.conf.app_urls[app]) {
                this.iframeContent(Kronolith.conf.app_urls[app]);
            }
            this.updateMinical(this.date);
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
            $('kronolithViewDay')
                .down('caption span')
                .update(this.setTitle(date.toString('D')));
            $('kronolithViewDay')
                .down('.kronolithAllDayContainer')
                .store('date', date.dateString());
            $('kronolithEventsDay').store('date', date.dateString());
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

            $('kronolithViewWeek')
                .down('caption span')
                .update(this.setTitle(dates[0].toString('d') + ' - ' + dates[1].toString('d')));

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
                div.store('date', day.dateString())
                    .writeAttribute('id', 'kronolithEventsWeek' + day.dateString());
                th.store('date', day.dateString())
                    .down('span').update(day.toString('dddd, d'));
                td.removeClassName('kronolithToday')
                    .down('div')
                    .writeAttribute('id', 'kronolithAllDay' + day.dateString())
                    .store('date', day.dateString());
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

            $('kronolithViewMonth')
                .down('caption span')
                .update(this.setTitle(date.toString('MMMM yyyy')));

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
            this.equalRowHeights(tbody);

            break;

        case 'year':
            var month;

            $('kronolithYearDate').update(this.setTitle(date.toString('yyyy')));

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
                $('kronolithAgendaDate')
                    .update(this.setTitle(Kronolith.text.agenda + ' ' + dates[0].toString('d') + ' - ' + dates[1].toString('d')));
                $('kronolithAgendaNavigation').show();
                $('kronolithSearchNavigation').hide();
            } else {
                $('kronolithAgendaDate')
                    .update(this.setTitle(Kronolith.text.searching.interpolate({ term: data })));
                $('kronolithAgendaNavigation').hide();
                $('kronolithSearchNavigation').show();
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
        $w('Day Week Month Year Tasks Agenda').each(function(a) {
            a = $('kronolithNav' + a);
            if (a) {
                a.removeClassName('on');
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
            row = $('kronolithAgendaTemplate').cloneNode(true);

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
        var table = $('kronolithYearTemplate').cloneNode(true),
            tbody = table.down('tbody');
        table.removeAttribute('id');
        tbody.writeAttribute('id', 'kronolithYearTable' + month)

        // Set month name.
        table.down('span')
            .store('date', year.toPaddedString(4) + (month + 1).toPaddedString(2) + '01')
            .update(Date.CultureInfo.monthNames[month]);

        // Build month table.
        this.buildMinical(tbody, new Date(year, month, 1), null, idPrefix);

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
        spacing = spacing ? parseInt($w(spacing)[1]) : 2;
        this[storage] = {};
        this[storage].height = layout.get('margin-box-height') + spacing;
        this[storage].spacing = this[storage].height - layout.get('padding-box-height');
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
                td.update(day.getRealWeek());
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
            td.update(day.getDate());
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
        var noItems;
        if (!div) {
            div = this.getCalendarList(type, cal.owner);
        }
        noItems = div.previous();
        if (noItems &&
            noItems.tagName == 'DIV' &&
            noItems.className == 'kronolithDialogInfo') {
            noItems.hide();
        }
        if (type != 'holiday' && type != 'external') {
            div.insert(new Element('span', { className: 'kronolithCalEdit' })
                   .insert('&#9656;'));
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
        var ext = $H(), extNames = $H();

        $H(Kronolith.conf.calendars.internal).each(function(cal) {
            this.insertCalendarInList('internal', cal.key, cal.value);
        }, this);

        if (Kronolith.conf.tasks) {
            $H(Kronolith.conf.calendars.tasklists).each(function(cal) {
                this.insertCalendarInList('tasklists', cal.key, cal.value);
            }, this);
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

        $H(Kronolith.conf.calendars.remote).each(function(cal) {
            this.insertCalendarInList('remote', cal.key, cal.value);
        }, this);

        $H(Kronolith.conf.calendars.holiday).each(function(cal) {
            this.insertCalendarInList('holiday', cal.key, cal.value);
        }, this);
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
     * Loads a certain calendar, if the current view is still a calendar view.
     *
     * @param string type      The calendar type.
     * @param string calendar  The calendar id.
     */
    loadCalendar: function(type, calendar)
    {
        if (Kronolith.conf.calendars[type][calendar].show &&
            $w('day week month year').include(this.view)) {
            var dates = this.viewDates(this.date, this.view);
            this.deleteCache(null, [type, calendar]);
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
        });

        Kronolith.conf.calendars[type][calendar].show = !Kronolith.conf.calendars[type][calendar].show;
        elt.toggleClassName('kronolithCalOn');
        elt.toggleClassName('kronolithCalOff');

        switch (this.view) {
        case 'month':
            if (Object.isUndefined(this.ecache.get(type)) ||
                Object.isUndefined(this.ecache.get(type).get(calendar))) {
                this.loadCalendar(type, calendar);
            } else {
                var allEvents = $('kronolithBody').select('div').findAll(function(el) {
                    return el.retrieve('calendar') == type + '|' + calendar;
                });
                if (Kronolith.conf.max_events) {
                    var dates = this.viewDates(this.date, this.view);
                    if (elt.hasClassName('kronolithCalOff')) {
                        var day, more, events, calendars = [];
                        $H(Kronolith.conf.calendars).each(function(type) {
                            $H(type.value).each(function(cal) {
                                if (cal.value.show) {
                                    calendars.push(type.key + '|' + cal.key);
                                }
                            });
                        });
                        allEvents.invoke('remove');
                        for (var date = dates[0]; !date.isAfter(dates[1]); date.add(1).days()) {
                            day = $('kronolithMonthDay' + date.dateString());
                            more = day.select('.kronolithMore');
                            events = day.select('.kronolithEvent');
                            if (more.size() &&
                                events.size() < Kronolith.conf.max_events) {
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
            if (elt.hasClassName('kronolithCalOff')) {
                $('kronolithViewTasksBody').select('tr').findAll(function(el) {
                    return el.retrieve('tasklist') == tasklist;
                }).invoke('remove');
            } else {
                this.loadTasks(this.tasktype, [ tasklist ]);
            }
            break;
        }

        if ($w('tasklists remote external holiday').include(type)) {
            calendar = type + '_' + calendar;
        }
        this.doAction('saveCalPref', { toggle_calendar: calendar });
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
     * Opens a tab in a form.
     *
     * @param Element  The A element of a tab.
     */
    openTab: function(elt)
    {
        var dialog = elt.up('form');
        dialog.select('.kronolithTabsOption').invoke('hide');
        dialog.select('.tabset li').invoke('removeClassName', 'activeTab');
        $(elt.id.replace(/Link/, 'Tab')).show();
        elt.up().addClassName('activeTab');
        if (elt.id == 'kronolithEventLinkMap') {
                    /* Maps */
            if (!this.mapInitialized) {
                this.initializeMap();
            }
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
                cals = this.ecache.get(cal[0]),
                events, date;

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
            this.storeCache($H(), calendar);
            this.doAction('listEvents',
                          { start: start,
                            end: end,
                            cal: calendar,
                            view: view },
                          this.loadEventsCallback.bind(this));
        }, this);

        if (!loading && view == 'year') {
            this.insertEvents([firstDay, lastDay], 'year');
        }
    },

    /**
     * Callback method for inserting events in the current view.
     *
     * @param object r  The ajax response object.
     */
    loadEventsCallback: function(r)
    {
        // Hide spinner.
        this.loading--;
        if (!this.loading) {
            $('kronolithLoading').hide();
        }

        var start = this.parseDate(r.response.sig.substr(0, 8)),
            end = this.parseDate(r.response.sig.substr(8, 8)),
            dates = [start, end];

        this.storeCache(r.response.events || {}, r.response.cal, dates);

        // Check if this is the still the result of the most current request.
        if (r.response.view != this.view ||
            r.response.sig != this.eventsLoading[r.response.cal]) {
            return;
        }
        delete this.eventsLoading[r.response.cal];

        if (this.view != 'year' || !$H(this.eventsLoading).size()) {
            this.insertEvents(dates, this.view, r.response.cal);
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
            // The day and week views require the view to be completely
            // loaded, to correctly calculate the dimensions.
            if (this.viewLoading || this.view != view) {
                this.insertEvents.bind(this, [dates[0].clone(), dates[1].clone()], view, calendar).defer();
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
                        .childElements()
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

            this.getCacheForDate(date).sortBy(this.sortEvents).each(function(event) {
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
                    if (view == 'month' && Kronolith.conf.max_events) {
                        var events = $('kronolithMonthDay' + date).select('.kronolithEvent');
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
                                    remove.remove();
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
                                            free.remove();
                                        } else {
                                            // No.
                                            this.insertMore(date);
                                            return;
                                        }
                                    } else {
                                        // Remove the last event to make room
                                        // for this one.
                                        events.pop().remove();
                                    }
                                } else {
                                    if (allDays.size() > 1) {
                                        // We don't want more than one all-day
                                        // event.
                                        allDays.pop().remove();
                                    } else {
                                        // This day is full.
                                        this.insertMore(date);
                                        return;
                                    }
                                }
                            }
                        }
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
                this.insertEvent(event, date, view);
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
                if (td.className == 'kronolithMinicalEmpty') {
                    continue;
                }
                if (td.hasClassName('kronolithToday')) {
                    td.className = 'kronolithToday';
                } else {
                    td.className = '';
                }
                if (td.retrieve('nicetitle')) {
                    Horde_ToolTips.detach(td);
                    td.store('nicetitle');
                }
                if (title) {
                    td.addClassName('kronolithHasEvents');
                    td.store('nicetitle', title);
                    td.observe('mouseover', Horde_ToolTips.onMouseover.bindAsEventListener(Horde_ToolTips));
                    td.observe('mouseout', Horde_ToolTips.out.bind(Horde_ToolTips));
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
    insertEvent: function(event, date, view)
    {
        var calendar = event.value.calendar.split('|');
        event.value.nodeId = 'kronolithEvent' + view + event.value.calendar + date + event.key;

        _createElement = function(event) {
            var className ='kronolithEvent';
            switch (event.value.x) {
            case 3:
                className += ' kronolithEventCancelled';
                break;
            case 1:
            case 4:
                className += ' kronolithEventTentative';
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
            var storage = view + 'Sizes',
                div = _createElement(event),
                margin = view == 'day' ? 5 : 10,
                style = { backgroundColor: Kronolith.conf.calendars[calendar[0]][calendar[1]].bg,
                          color: Kronolith.conf.calendars[calendar[0]][calendar[1]].fg };

            if (event.value.al) {
                if (view == 'day') {
                    $('kronolithViewDay').down('.kronolithAllDayContainer').insert(div.setStyle(style));
                } else {
                    var existing = $('kronolithAllDay' + date).select('div');
                    if (existing.size() == 3) {
                        if (existing[2].className != 'kronolithMore') {
                            existing[2].remove();
                            $('kronolithAllDay' + date).insert({ bottom: new Element('span', { className: 'kronolithMore' }).store('date', date).insert(Kronolith.text.more) });
                        }
                    } else {
                        $('kronolithAllDay' + date).insert(div.setStyle(style));
                    }
                }
                break;
            }

            var midnight = this.parseDate(date),
                innerDiv = new Element('div', { className: 'kronolithEventInfo' }),
                minHeight = 0, height,
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
                width: 100 - margin + '%'
            })
                .insert(innerDiv.setStyle(style));
            if (draggerTop) {
                div.insert(draggerTop);
            }
            if (draggerBottom) {
                div.insert(draggerBottom);
            }
            $(view == 'day' ? 'kronolithEventsDay' : 'kronolithEventsWeek' + date).insert(div);
            if (draggerTop) {
                minHeight += draggerTop.getHeight();
            }
            if (draggerBottom) {
                minHeight += draggerBottom.getHeight();
            }
            div.setStyle({ height: Math.max(Math.round(event.value.start.getElapsed(event.value.end) / 60000) * this[storage].height / 60 - this[storage].spacing | 0, minHeight) + 'px' });

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
                        minLeft = $('kronolithEventsWeek' + dates[0].dateString()).offsetLeft - $('kronolithEventsWeek' + date).offsetLeft,
                        maxLeft = $('kronolithEventsWeek' + dates[1].dateString()).offsetLeft - $('kronolithEventsWeek' + date).offsetLeft,
                        stepX = (maxLeft - minLeft) / 6;
                }
                var d = new Drag(div, {
                    threshold: 5,
                    nodrop: true,
                    parentElement: function() { return $(view == 'day' ? 'kronolithEventsDay' : 'kronolithEventsWeek' + date); },
                    snap: function(x, y) {
                        x = (view == 'week')
                            ? Math.max(minLeft, stepX * ((Math.min(maxLeft, x - (x < 0 ? stepX : 0)) + stepX / 2) / stepX | 0))
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
                // The event below the current event fits.
                placeFound = false;

            // this.dayEvents contains all events of the current day.
            // this.dayGroups contains conflict groups, i.e. all events that
            // conflict with each other and share a set of columns.
            //
            // Go through all events that have been added to this day already.
            this.dayEvents.each(function(ev) {
                // If it doesn't conflict with the current event, rember it
                // as a possible event below that we can put the current event
                // and go ahead.
                if (!ev.end.isAfter(event.value.start)) {
                    placeFound = ev;
                    return;
                }

                if (!conflict) {
                    // This is the first conflicting event.
                    conflict = ev;
                    for (i = 0; i < this.dayGroups.length; i++) {
                        // Find the conflict group of the conflicting event.
                        if (this.dayGroups[i].indexOf(conflict) != -1) {
                            // If our possible candidate "above" is a member of
                            // this group, it's no longer a candidate.
                            if (this.dayGroups[i].indexOf(placeFound) == -1) {
                                placeFound = false;
                            }
                            break;
                        }
                    }
                }
                // We didn't find a place, put the event a column further right.
                if (!placeFound) {
                    column++;
                }
            }, this);
            event.value.column = column;

            if (conflict) {
                // We had a conflict, find the matching conflict group and add
                // the current event there.
                for (i = 0; i < this.dayGroups.length; i++) {
                    if (this.dayGroups[i].indexOf(conflict) != -1) {
                        pos = i;
                        break;
                    }
                }
                // See if the current event had to add yet another column.
                columns = Math.max(conflict.columns, column);
            } else {
                columns = column;
            }
            if (Object.isUndefined(this.dayGroups[pos])) {
                this.dayGroups[pos] = [];
            }
            this.dayGroups[pos].push(event.value);
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
            var div = _createElement(event)
                .setStyle({ backgroundColor: Kronolith.conf.calendars[calendar[0]][calendar[1]].bg,
                            color: Kronolith.conf.calendars[calendar[0]][calendar[1]].fg });
            $('kronolithMonthDay' + date).insert(div);
            if (event.value.pe) {
                div.setStyle({ cursor: 'move' });
                new Drag('kronolithEventmonth' + event.value.calendar + date + event.key, { threshold: 5, parentElement: function() { return $('kronolithViewMonthBody'); }, snapToParent: true });
            }
            if (Kronolith.conf.max_events) {
                var more = $('kronolithMonthDay' + date).down('.kronolithMore');
                if (more) {
                    $('kronolithMonthDay' + date).insert({ bottom: more.remove() });
                }
            }
            break;

        case 'agenda':
            var div = _createElement(event)
                .setStyle({ backgroundColor: Kronolith.conf.calendars[calendar[0]][calendar[1]].bg,
                            color: Kronolith.conf.calendars[calendar[0]][calendar[1]].fg });
            if (!event.value.al) {
                div.update(new Element('span', { className: 'kronolithDate' }).update(event.value.start.toString('t')))
                    .insert(' ')
                    .insert(new Element('span', { className: 'kronolithSeparator' }).update('&middot;'))
                    .insert(' ');
            }
            this.createAgendaDay(date);
            $('kronolithAgendaDay' + date).insert(div);
            break;
        }

        this.setEventText(div, event.value, view == 'month' ? 30 : null)
            .observe('mouseover', div.addClassName.curry('kronolithSelected'))
            .observe('mouseout', div.removeClassName.curry('kronolithSelected'));
    },

    /**
     * Adds a "more..." button to the month view cell that links to the days,
     * or moves it to the buttom.
     *
     * @param string date  The date string of the day cell.
     */
    insertMore: function(date)
    {
        var more = $('kronolithMonthDay' + date).down('.kronolithMore');
        if (more) {
            $('kronolithMonthDay' + date).insert({ bottom: more.remove() });
        } else {
            $('kronolithMonthDay' + date).insert({ bottom: new Element('span', { className: 'kronolithMore' }).store('date', date).insert(Kronolith.text.more) });
        }
    },

    setEventText: function(div, event, length)
    {
        var calendar = event.calendar.split('|');
        div.update();
        if (event.ic) {
            div.insert(new Element('img', { src: event.ic }));
        }
        div.insert((length ? event.t.truncate(length) : event.t).escapeHTML());
        if (event.a) {
            div.insert(' ')
                .insert(new Element('img', { src: Kronolith.conf.URI_IMG + 'alarm-' + Kronolith.conf.calendars[calendar[0]][calendar[1]].fg.substr(1) + '.png', title: Kronolith.text.alarm + ' ' + event.a }));
        }
        if (event.r) {
            div.insert(' ')
                .insert(new Element('img', { src: Kronolith.conf.URI_IMG + 'recur-' + Kronolith.conf.calendars[calendar[0]][calendar[1]].fg.substr(1) + '.png', title: Kronolith.text.recur[event.r] }));
        } else if (event.bid) {
            div.insert(' ')
                .insert(new Element('img', { src: Kronolith.conf.URI_IMG + 'exception-' + Kronolith.conf.calendars[calendar[0]][calendar[1]].fg.substr(1) + '.png', title: Kronolith.text.recur['Exception'] }));
        }
        return div;
    },

    /**
     * Finally removes an event from the DOM and the cache.
     *
     * @param string event     An event id.
     * @param string calendar  A calendar name.
     */
    removeEvent: function(event, calendar)
    {
        this.deleteCache(event, calendar);
        $('kronolithBody').select('div').findAll(function(el) {
            return el.retrieve('calendar') == calendar &&
                el.retrieve('eventid') == event;
        }).invoke('remove');
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
        var tasktypes = this.getTaskStorage(tasktype), loading = false;

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
                    $('kronolithLoading').show();
                    this.doAction('listTasks',
                                  { type: type,
                                    list: list },
                                  function(r) {
                                      this.loadTasksCallback(r, true);
                                  }.bind(this));
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
     *                             adding individual tasks to the cache without
     *                             assuming to have all tasks of the list.
     */
    loadTasksCallback: function(r, createCache)
    {
        // Hide spinner.
        this.loading--;
        if (!this.loading) {
            $('kronolithLoading').hide();
        }

        this.storeTasksCache(r.response.tasks || {}, r.response.type, r.response.list, createCache);
        if (Object.isUndefined(r.response.tasks)) {
            return;
        }

        // Check if result is still valid for the current view.
        // There could be a rare race condition where two responses for the
        // same task(s) arrive in the wrong order. Checking this too, like we
        // do for events seems not worth it.
        var tasktypes = this.getTaskStorage(this.tasktype),
            tasklist = Kronolith.conf.calendars.tasklists['tasks/' + r.response.list];
        if (this.view != 'tasks' ||
            !tasklist || !tasklist.show ||
            !tasktypes.include(r.response.type)) {
            return;
        }
        this.insertTasks(this.tasktype, r.response.list);
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
                this.insertTask(task);
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
    insertTask: function(task)
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
            col.insert(new Element('span', { className: 'kronolithSeparator' }).update(' &middot; '));
            col.insert(new Element('span', { className: 'kronolithDate' }).update(date.toString(Kronolith.conf.date_format)));
        }

        if (!Object.isUndefined(task.value.sd)) {
            col.insert(new Element('span', { className: 'kronolithSeparator' }).update(' &middot; '));
            col.insert(new Element('span', { className: 'kronolithInfo' }).update(task.value.sd));
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
        return (taskA.pr >= taskB.pr);
    },

    /**
     * Completes/uncompletes a task.
     *
     * @param string tasklist  The task list to which the tasks belongs
     * @param string taskid    The id of the task
     */
    toggleCompletion: function(tasklist, taskid)
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
        task.cp = !task.cp;

        this.tcache.get(task.cp ? 'complete' : 'incomplete').get(tasklist).set(taskid, task);
        this.tcache.get(task.cp ? 'incomplete' : 'complete').get(tasklist).unset(taskid);

        // Remove row if necessary.
        var row = this.getTaskRow(taskid);
        if (!row) {
            return;
        }
        if ((this.tasktype == 'complete' && !task.cp) ||
            ((this.tasktype == 'incomplete' || this.tasktype == 'future_incomplete') && task.cp)) {
            row.fade({
                duration: this.effectDur,
                afterFinish: function() {
                    row.remove();
                }
            });
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

    editTask: function(tasklist, id)
    {
        if (this.redBoxLoading) {
            return;
        }

        this.closeRedBox();
        this.redBoxOnDisplay = RedBox.onDisplay;
        RedBox.onDisplay = function() {
            if (this.redBoxOnDisplay) {
                this.redBoxOnDisplay();
            }
            try {
                $('kronolithTaskForm').focusFirstElement();
            } catch(e) {}
            RedBox.onDisplay = this.redBoxOnDisplay;
        };

        this.openTab($('kronolithTaskForm').down('.tabset a.kronolithTabLink'));
        $('kronolithTaskForm').enable();
        $('kronolithTaskForm').reset();
        $('kronolithTaskSave').show();
        $('kronolithTaskDelete').show();
        $('kronolithTaskForm').down('.kronolithFormActions .kronolithSeparator').show();
        this.updateTasklistDropDown();
        if (id) {
            RedBox.loading();
            this.doAction('getTask', { list: tasklist, id: id }, this.editTaskCallback.bind(this));
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
            $('kronolithTaskForm').down('.kronolithFormActions .kronolithSeparator').hide();
        }

        this.setTitle(task.n);
        this.redBoxLoading = true;
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
    removeTask: function(task, list)
    {
        this.deleteTasksCache(task, list);
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

        var tasklist = $F('kronolithTaskOldList'),
            taskid = $F('kronolithTaskId');
        this.loading++;
        $('kronolithLoading').show();
        this.doAction('saveTask',
                      $H($('kronolithTaskForm').serialize({ hash: true }))
                          .merge({ sig: this.tasktype }),
                      function(r) {
                          if (r.response.tasks && taskid) {
                              this.removeTask(taskid, tasklist);
                          }
                          this.loadTasksCallback(r, false);
                          this.closeRedBox();
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
        if (this.redBoxLoading) {
            return;
        }

        this.closeRedBox();
        if ($('kronolithCalendarDialog')) {
            this.redBoxLoading = true;
            RedBox.showHtml($('kronolithCalendarDialog').show());
            this.editCalendarCallback(calendar);
        } else {
            RedBox.loading();
            this.doAction('chunkContent', { chunk: 'calendar' }, function(r) {
                if (r.response.chunk) {
                    this.redBoxLoading = true;
                    RedBox.showHtml(r.response.chunk);
                    this.editCalendarCallback(calendar);
                } else {
                    this.closeRedBox();
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

        if (type == 'internal' || type == 'tasklists') {
            this.updateGroupDropDown([['kronolithC' + type + 'PGList', this.updateGroupPerms.bind(this, type)],
                                      ['kronolithC' + type + 'PGNew']]);
            $('kronolithC' + type + 'PBasic').show();
            $('kronolithC' + type + 'PAdvanced').hide();
            $('kronolithC' + type + 'PNone').setValue(1);
            $('kronolithC' + type + 'PAllShow').disable();
            $('kronolithC' + type + 'PGList').disable();
            $('kronolithC' + type + 'PGPerms').disable();
            $('kronolithC' + type + 'PUList').disable();
            $('kronolithC' + type + 'PUPerms').disable();
            $('kronolithC' + type + 'PAdvanced').select('tr').findAll(function(tr) {
                return tr.retrieve('remove');
            }).invoke('remove');
            $('kronolithCalendar' + type + 'Urls').hide();
        }

        var newCalendar = !calendar;
        if (calendar &&
            (Object.isUndefined(Kronolith.conf.calendars[type]) ||
             Object.isUndefined(Kronolith.conf.calendars[type][calendar]))) {
            switch (type) {
            case 'internal':
            case 'tasklists':
                this.doAction('getCalendar', { type: type, cal: calendar }, function(r) {
                    if (r.response.calendar) {
                        Kronolith.conf.calendars[type][calendar] = r.response.calendar;
                        this.insertCalendarInList(type, calendar, r.response.calendar);
                        $('kronolithSharedCalendars').show();
                        this.editCalendarCallback(type + '|' + calendar);
                    }
                }.bind(this));
                return;
            case 'remote':
                newCalendar = true;
                break;
            default:
                this.closeRedBox();
                window.history.back();
                return;
            }
        }
        if (newCalendar) {
            switch (type) {
            case 'internal':
                kronolithCTagAc.reset();
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
            }
            $('kronolithCalendar' + type + 'Id').clear();
            $('kronolithCalendar' + type + 'Color').setValue('#dddddd').setStyle({ backgroundColor: '#dddddd', color: '#000' });
            form.down('.kronolithCalendarDelete').hide();
        } else {
            info = Kronolith.conf.calendars[type][calendar];

            $('kronolithCalendar' + type + 'Id').setValue(calendar);
            $('kronolithCalendar' + type + 'Name').setValue(info.name);
            $('kronolithCalendar' + type + 'Color').setValue(info.bg).setStyle({ backgroundColor: info.bg, color: info.fg });

            switch (type) {
            case 'internal':
                kronolithCTagAc.reset(Kronolith.conf.calendars.internal[calendar].tg);
                $('kronolithCalendar' + type + 'ImportCal').setValue(calendar);
                if (info.edit) {
                    $('kronolithCalendar' + type + 'LinkImport').up('li').show();
                } else {
                    $('kronolithCalendar' + type + 'LinkImport').up('li').hide();
                }
                $('kronolithCalendar' + type + 'UrlFeed').setValue(info.feed);
                // Fall through.
            case 'tasklists':
                $('kronolithCalendar' + type + 'Description').setValue(info.desc);
                $('kronolithCalendar' + type + 'LinkExport').up('span').show();
                $('kronolithCalendar' + type + 'Export').href = type == 'internal'
                    ? Kronolith.conf.URI_CALENDAR_EXPORT + '=' + calendar
                    : Kronolith.conf.tasks.URI_TASKLIST_EXPORT + '=' + calendar.substring(6);
                $('kronolithCalendar' + type + 'Urls').show();
                $('kronolithCalendar' + type + 'UrlSub').setValue(info.sub);
                break;
            case 'remote':
                $('kronolithCalendarremoteUrl').setValue(calendar);
                $('kronolithCalendarremoteDescription').setValue(info.desc);
                $('kronolithCalendarremoteUsername').setValue(info.user);
                $('kronolithCalendarremotePassword').setValue(info.password);
                break;
            }
        }

        if (newCalendar || info.owner) {
            form.down('.kronolithColorPicker').show();
            if (type == 'internal' || type == 'tasklists') {
                if (type == 'internal') {
                    this.doAction('listTopTags', null, this.topTagsCallback.curry('kronolithCalendarinternalTopTags', 'kronolithCalendarTag'));
                }
                form.down('.kronolithCalendarSubscribe').hide();
                form.down('.kronolithCalendarUnsubscribe').hide();
                $('kronolithCalendar' + type + 'LinkPerms').up('li').show();
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
                kronolithCTagAc.disable();
                if (Kronolith.conf.calendars[type][calendar].show) {
                    form.down('.kronolithCalendarSubscribe').hide();
                    form.down('.kronolithCalendarUnsubscribe').show().enable();
                } else {
                    form.down('.kronolithCalendarSubscribe').show().enable();
                    form.down('.kronolithCalendarUnsubscribe').hide();
                }
                form.down('.kronolithFormActions .kronolithSeparator').show();
                $('kronolithCalendar' + type + 'LinkPerms').up('li').hide();
            } else {
                form.down('.kronolithFormActions .kronolithSeparator').hide();
            }
        }
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
            $('kronolithC' + type + 'PAllShow').disable();
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
            $('kronolithC' + type + 'PAllShow').disable();
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
            $('kronolithC' + type + 'PGdelete_' + group).setValue(0);
            if ($('kronolithC' + type + 'PGdelegate_' + group)) {
                $('kronolithC' + type + 'PGdelegate_' + group).setValue(0);
            }
            break;
        case 'U':
            $('kronolithC' + type + 'PAllShow').disable();
            $('kronolithC' + type + 'PGList').disable();
            $('kronolithC' + type + 'PGPerms').disable();
            $('kronolithC' + type + 'PUList').enable();
            $('kronolithC' + type + 'PUPerms').enable();
            var users = $F('kronolithC' + type + 'PUList').strip();
            users = users ? users.split(/,\s*/) : [];
            users.each(function(user) {
                this.insertGroupOrUser(type, 'user', user, true);
                $('kronolithC' + type + 'PUshow_' + user).setValue(1);
                $('kronolithC' + type + 'PUread_' + user).setValue(1);
                if ($F('kronolithC' + type + 'PUPerms') == 'edit') {
                    $('kronolithC' + type + 'PUedit_' + user).setValue(1);
                } else {
                    $('kronolithC' + type + 'PUedit_' + user).setValue(0);
                }
                $('kronolithC' + type + 'PUdelete_' + user).setValue(0);
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
                        this.insertGroupOrUser(type, 'group', group.key);
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
                            this.insertGroupOrUser(type, 'user', user.key);
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
                            this.permsClickHandler('None');
                        }
                    } else if ($('kronolithC' + type + 'PGSingle').getValue() != group) {
                        // Group no longer exists.
                        this.permsClickHandler('None');
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
            var options = $(param[0]).childElements();
            options.shift();
            options.invoke('remove');
        });
        this.doAction('listGroups', null, function(r) {
            var groups;
            if (r.response.groups) {
                groups = $H(r.response.groups);
                params.each(function(param) {
                    groups.each(function(group) {
                        $(param[0]).insert(new Element('option', { value: group.key })
                                     .update(group.value.escapeHTML()));
                    });
                });
            }
            params.each(function(param) {
                if (param[1]) {
                    param[1](groups);
                }
            });
            this.groupLoading = false;
        }.bind(this));
    },

    /**
     * Updates the basic group permission interface after the group list has
     * been loaded.
     *
     * @param string type  The calendar type, 'internal' or 'taskslists'.
     * @param Hash groups  The list of groups.
     */
    updateGroupPerms: function(type, groups)
    {
        $('kronolithC' + type + 'PGSingle').clear();
        if (!groups) {
            $('kronolithC' + type + 'PG').up('span').hide();
        } else if (groups.size() == 1) {
            $('kronolithC' + type + 'PGName')
                .update('&quot;' + groups.values()[0].escapeHTML() + '&quot;')
                .show();
            $('kronolithC' + type + 'PGSingle').setValue(groups.keys()[0]);
            $('kronolithC' + type + 'PGList').hide();
        } else {
            $('kronolithC' + type + 'PGName').hide();
            $('kronolithC' + type + 'PGList').show();
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
     * @param notadvanced boolean  Enforces to NOT switch to the advanced
     *                             permissions screen.
     */
    insertGroupOrUser: function(type, what, id, notadvanced)
    {
        var elm = $(what == 'user' ? 'kronolithC' + type + 'PUNew' : 'kronolithC' + type + 'PGNew');
        if (id) {
            elm.setValue(id);
        }
        var value = elm.getValue();
        if (!value) {
            return;
        }

        var tr = elm.up('tr'),
            row = tr.cloneNode(true).store('remove', true),
            td = row.down('td');

        td.down('label').remove();
        // For some strange prototype/firefox box, an instance .remove()
        // doesn't work here.
        Element.remove(td.down(elm.tagName));
        td.insert((elm.tagName == 'SELECT' ? elm.options[elm.selectedIndex].text: elm.getValue()).escapeHTML())
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

        if (!notadvanced) {
            this.activateAdvancedPerms(type);
        }
    },

    /**
     * Activates the advanced permissions.
     *
     * @param string type  The calendar type, 'internal' or 'taskslists'.
     */
    activateAdvancedPerms: function(type)
    {
        [$('kronolithC' + type + 'PNone'), $('kronolithC' + type + 'PAll'), $('kronolithC' + type + 'PG')].invoke('writeAttribute', 'checked', false);
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
        $('kronolithCalendar' + type + ++i).show();
        if (this.colorPicker) {
            this.colorPicker.hide();
        }
    },

    /**
     * Submits the calendar form to save the calendar data.
     *
     * @param Element form  The form node.
     */
    saveCalendar: function(form)
    {
        var data = form.serialize({ hash: true });
        this.doAction('saveCalendar', data,
                      this.saveCalendarCallback.bind(this, form, data));
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

        if (r.response.saved) {
            if ($F('kronolithCalendarinternalImport')) {
                var name = 'kronolithIframe' + Math.round(Math.random() * 1000),
                    iframe = new Element('iframe', { src: 'about:blank', name: name, id: name }).setStyle({ display: 'none' });
                document.body.insert(iframe);
                form.target = name;
                form.submit();
            }
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
                if (r.response.perms) {
                    cal.perms = r.response.perms;
                }
                if (data.tags) {
                    cal.tg = data.tags.split(',');
                }
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
                if (r.response.perms) {
                    cal.perms = r.response.perms;
                }
                if (data.tags) {
                    cal.tg = data.tags.split(',');
                }
                if (!Kronolith.conf.calendars[type]) {
                    Kronolith.conf.calendars[type] = [];
                }
                Kronolith.conf.calendars[type][r.response.calendar] = cal;
                this.insertCalendarInList(type, r.response.calendar, cal);
            }
        }
        form.down('.kronolithCalendarSave').enable();
        this.closeRedBox();
        window.history.back();
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
    storeCache: function(events, calendar, dates)
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
    deleteCache: function(event, calendar)
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
    deleteTasksCache: function(task, list)
    {
        this.deleteCache(task, [ 'external', 'tasks/' + list ]);
        $w('complete incomplete').each(function(type) {
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
    getCacheForDate: function(date)
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
    sortEvents: function(event)
    {
        return event.value.sort;
    },

    addHistory: function(loc, data)
    {
        if (Horde.dhtmlHistory.getCurrentLocation() != loc) {
            Horde.dhtmlHistory.add(loc, data);
        }
    },

    /**
     * Loads an external page into the iframe view.
     *
     * @param string loc  The URL of the page to load.
     */
    iframeContent: function(loc)
    {
        var view = $('kronolithViewIframe'), iframe = $('kronolithIframe');
        view.hide();
        if (!iframe) {
            view.insert(new Element('iframe', { id: 'kronolithIframe', className: 'kronolithIframe', frameBorder: 0 }));
            iframe = $('kronolithIframe');
        }
        iframe.observe('load', function() {
            view.appear({ duration: this.effectDur, queue: 'end' });
            iframe.stopObserving('load');
        }.bind(this));
        iframe.src = loc;
        this.view = 'iframe';
    },

    /* Keydown event handler */
    keydownHandler: function(e)
    {
        var kc = e.keyCode || e.charCode,
            form = e.findElement('FORM'), trigger = e.findElement();

        switch (trigger.id) {
        case 'kronolithEventLocation':
            if (kc == Event.KEY_RETURN) {
                this.ensureMap();
                this.geocode($F('kronolithEventLocation'));
                e.stop();
                return;
            }
            break;

        case 'kronolithCalendarinternalUrlSub':
        case 'kronolithCalendarinternalUrlFeed':
        case 'kronolithCalendartasklistsUrlSub':
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
                    this.saveEvent();
                    e.stop();
                    break;

                case 'kronolithTaskForm':
                    this.saveTask();
                    e.stop();
                    break;

                case 'kronolithSearchForm':
                    this.go('search:' + this.search + ':' + $F('kronolithSearchTerm'))
                    e.stop();
                    break;

                case 'kronolithQuickinsertForm':
                    this.quickSaveEvent();
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
                    $('kronolithQuickinsert').fade({ duration: this.effectDur });
                    break;
                case 'kronolithEventForm':
                    this.closeRedBox();
                    window.history.back();
                    break;
                }
                break;
            }

            return;
        }

        switch (kc) {
        case Event.KEY_ESC:
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
                if (Kronolith.conf.URI_HOME) {
                    this.redirect(Kronolith.conf.URI_HOME);
                } else {
                    this.go(Kronolith.conf.login_view);
                }
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
                    duration: this.effectDur,
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
                $('kronolithQuickinsert').fade({ duration: this.effectDur });
                $('kronolithQuickinsertQ').value = '';
                e.stop();
                return;

            case 'kronolithEventAllday':
                this.toggleAllDay();
                break;

            case 'kronolithEventAlarmDefaultOn':
                this.disableAlarmMethods();
                break;

            case 'kronolithEventAlarmPrefs':
                this.closeRedBox();
                window.history.back();
                this.go('options', { app: 'kronolith', group: 'notification' });
                e.stop();
                break;

            case 'kronolithTaskAlarmPrefs':
                this.closeRedBox();
                window.history.back();
                this.go('options', { app: 'nag', group: 'notification' });
                e.stop();
                break;

            case 'kronolithEventLinkNone':
            case 'kronolithEventLinkDaily':
            case 'kronolithEventLinkWeekly':
            case 'kronolithEventLinkMonthly':
            case 'kronolithEventLinkYearly':
            case 'kronolithEventLinkLength':
                this.toggleRecurrence(id.substring(18));
                break;

            case 'kronolithEventSave':
                this.saveEvent();
                $('kronolithEventSave').disable();
                $('kronolithEventSaveAsNew').disable();
                e.stop();
                break;

            case 'kronolithEventSaveAsNew':
                this.saveEvent(true);
                $('kronolithEventSave').disable();
                $('kronolithEventSaveAsNew').disable();
                e.stop();
                break;

            case 'kronolithTaskSave':
                this.saveTask();
                elt.disable();
                e.stop();
                break;

            case 'kronolithEventDelete':
                var cal = $F('kronolithEventCalendar'),
                    eventid = $F('kronolithEventId'),
                    view = this.view,
                    date = this.date;
                this.doAction('deleteEvent',
                              { cal: cal, id: eventid },
                              function(r) {
                                  if (r.response.deleted) {
                                      this.removeEvent(eventid, cal);
                                  } else {
                                      $('kronolithBody').select('div').findAll(function(el) {
                                          return el.retrieve('calendar') == cal &&
                                              el.retrieve('eventid') == eventid;
                                      }).invoke('toggle');
                                  }
                                  if (view == this.view &&
                                      date.equals(this.date) &&
                                      (view == 'week' || view == 'day')) {
                                      // Re-render.
                                      this.insertEvents(this.viewDates(this.date, view), view);
                                  }
                              }.bind(this));
                $('kronolithBody').select('div').findAll(function(el) {
                    return el.retrieve('calendar') == cal &&
                        el.retrieve('eventid') == eventid;
                }).invoke('hide');
                this.closeRedBox();
                window.history.back();
                e.stop();
                break;

            case 'kronolithTaskDelete':
                var tasklist = $F('kronolithTaskOldList'),
                    taskid = $F('kronolithTaskId');
                this.doAction('deleteTask',
                              { list: tasklist, id: taskid },
                              function(r) {
                                  if (r.response.deleted) {
                                      this.removeTask(taskid, tasklist);
                                      if ($('kronolithViewTasksBody').select('tr').length > 3) {
                                          $('kronolithTasksNoItems').hide();
                                      } else {
                                          $('kronolithTasksNoItems').show();
                                      }
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
                this.closeRedBox();
                window.history.back();
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

            case 'kronolithLogout':
                this.logout();
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
                this.go('search:' + this.search + ':' + $F('kronolithSearchTerm'));
                e.stop();
                break;

            case 'kronolithSearchFuture':
                if (this.search != 'future') {
                    this.go('search:future:' + $F('kronolithSearchTerm'));
                }
                e.stop();
                break;

            case 'kronolithSearchPast':
                if (this.search != 'past') {
                    this.go('search:past:' + $F('kronolithSearchTerm'));
                }
                e.stop();
                break;

            case 'kronolithSearchAll':
                if (this.search != 'all') {
                    this.go('search:all:' + $F('kronolithSearchTerm'));
                }
                e.stop();
                break;

            case 'kronolithNotifications':
                var img = elt.down('img'), iconName;
                if (this.Growler.toggleLog()) {
                    elt.title = Kronolith.text.hidelog;
                    elt.addClassName('kronolithClose');
                } else {
                    elt.title = Kronolith.text.alerts.interpolate({ count: this.growls });
                    elt.removeClassName('kronolithClose');
                }
                Horde_ToolTips.detach(elt);
                Horde_ToolTips.attach(elt);
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
                this.openTab(elt);
                e.stop();
                break;

            case 'kronolithFormCancel':
                this.closeRedBox();
                this.resetMap();
                window.history.back();
                e.stop();
                break;

            case 'kronolithEventTag':
                kronolithETagAc.addNewItemNode(elt.getText());
                e.stop();
                break;

            case 'kronolithCalendarTag':
                kronolithCTagAc.addNewItemNode(elt.getText());
                e.stop();
                break;

            case 'kronolithEventGeo':
                this.ensureMap();
                this.geocode($F('kronolithEventLocation'));
                e.stop();
                break;

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
                var date = elt.retrieve('date');
                if (elt.className == 'kronolithAllDayContainer') {
                    date += 'all';
                }
                this.go('event:' + date);
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
                this.toggleCompletionClass(taskid);
                this.doAction('toggleCompletion',
                              { list: tasklist, id: taskid },
                              function(r) {
                                  if (r.response.toggled) {
                                      this.toggleCompletion(tasklist, taskid);
                                  } else {
                                      this.toggleCompletionClass(taskid);
                                  }
                              }.bind(this));
                e.stop();
                return;
            } else if (elt.hasClassName('kronolithCalendarSave')) {
                elt.disable();
                this.saveCalendar(elt.up('form'));
                e.stop();
                break;
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
                            break;
                        }
                        this.doAction('getRemoteInfo',
                                      params,
                                      function(r) {
                                          if (r.response.success) {
                                              if (r.response.name) {
                                                  $('kronolithCalendarremoteName').setValue(r.response.name);
                                              }
                                              if (r.response.desc) {
                                                  $('kronolithCalendarremoteDescription').setValue(r.response.desc);
                                              }
                                              this.calendarNext(type);
                                              this.calendarNext(type);
                                          } else if (r.response.auth) {
                                              this.calendarNext(type);
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
                        this.doAction('getRemoteInfo',
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
                                              this.calendarNext(type);
                                          } else if (r.response.auth) {
                                              this.showNotifications([{ type: 'horde.warning', message: Kronolith.text.wrong_auth }]);
                                              elt.enable();
                                          } else {
                                              elt.enable();
                                          }
                                      }.bind(this));
                    }
                    e.stop();
                    break;
                }
                this.calendarNext(type);
                elt.disable();
                e.stop();
                break;
            } else if (elt.hasClassName('kronolithCalendarDelete')) {
                var form = elt.up('form'),
                    type = form.id.replace(/kronolithCalendarForm/, ''),
                    calendar = $F('kronolithCalendar' + type + 'Id');
                this.doAction('deleteCalendar',
                              { type: type, calendar: calendar },
                              function(r) {
                                  if (r.response.deleted) {
                                      var container = this.getCalendarList(type, Kronolith.conf.calendars[type][calendar].owner),
                                          noItems = container.previous(),
                                          div = container.select('div').find(function(element) {
                                              return element.retrieve('calendar') == calendar;
                                          });
                                      div.previous('span').remove();
                                      div.remove();
                                      if (noItems &&
                                          noItems.tagName == 'DIV' &&
                                          noItems.className == 'kronolithDialogInfo' &&
                                          !container.childElements().size()) {
                                          noItems.show();
                                      }
                                      this.deleteCache(null, calendar);
                                      $('kronolithBody').select('div').findAll(function(el) {
                                          return el.retrieve('calendar') == calendar;
                                      }).invoke('remove');
                                      delete Kronolith.conf.calendars[type][calendar];
                                  }
                                  this.closeRedBox();
                                  window.history.back();
                              }.bind(this));
                elt.disable();
                e.stop();
                break;
            } else if (elt.hasClassName('kronolithCalendarSubscribe') ||
                       elt.hasClassName('kronolithCalendarUnsubscribe')) {
                var form = elt.up('form');
                this.toggleCalendar($F(form.down('input[name=type]')),
                                    $F(form.down('input[name=calendar]')));
                this.closeRedBox();
                window.history.back();
                e.stop();
                break;
            } else if (elt.tagName == 'INPUT' && elt.name == 'event_alarms[]') {
                $('kronolithEventAlarmDefaultOff').setValue(1);
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
        e.element().previous().setValue(e.memo.toString(Kronolith.conf.date_format));
        this.updateEndTime()
    },

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
            viewDates = this.viewDates(this.date, 'month'),
            start = viewDates[0].toString('yyyyMMdd'),
            end = viewDates[1].toString('yyyyMMdd');

        drop.insert(el);
        this.startLoading(cal, start + end);
        this.doAction('updateEvent',
                      { cal: cal,
                        id: eventid,
                        view: this.view,
                        view_start: start,
                        view_end: end,
                        att: $H({ offDays: diff }).toJSON() },
                      function(r) {
                          if (r.response.events) {
                              this.removeEvent(eventid, cal);
                          }
                          this.loadEventsCallback(r);
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

            this.calculateEventDates(event, storage, step, offset, height);
            drag.innerDiv.update('(' + event.start.toString(Kronolith.conf.time_format) + ' - ' + event.end.toString(Kronolith.conf.time_format) + ') ' + event.t.escapeHTML());
        } else if (elt.hasClassName('kronolithEditable')) {
            if (Object.isUndefined(drag.innerDiv)) {
                drag.innerDiv = drag.ghost.down('.kronolithEventInfo');
            }
            if (this.view == 'week') {
                var offsetX = Math.round(drag.ghost.offsetLeft / drag.stepX);
                event.offsetDays = offsetX;
                this.calculateEventDates(event, storage, step, drag.ghost.offsetTop, drag.divHeight, event.start.clone().addDays(offsetX), event.end.clone().addDays(offsetX));
            } else {
                event.offsetDays = 0;
                this.calculateEventDates(event, storage, step, drag.ghost.offsetTop, drag.divHeight);
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
        if (!Object.isUndefined(drag.innerDiv)) {
            this.setEventText(drag.innerDiv, event.value);
        }
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
            'updateEvent',
            { cal: event.value.calendar,
              id: event.key,
              view: this.view,
              view_start: start,
              view_end: end,
              att: attributes.toJSON()
            },
            function(r) {
                if (r.response.events) {
                    this.removeEvent(event.key, event.value.calendar);
                }
                this.loadEventsCallback(r);
            }.bind(this));
    },

    editEvent: function(calendar, id, date)
    {
        if (this.redBoxLoading) {
            return;
        }
        if (typeof kronolithETagAc == 'undefined') {
            this.editEvent.bind(this, calendar, id, date).defer();
            return;
        }

        this.closeRedBox();
        this.redBoxOnDisplay = RedBox.onDisplay;
        RedBox.onDisplay = function() {
           if (this.redBoxOnDisplay) {
               this.redBoxOnDisplay();
           }
           try {
                $('kronolithEventForm').focusFirstElement();
            } catch(e) {}
            if (Kronolith.conf.maps.driver &&
                $('kronolithEventLinkMap').up().hasClassName('activeTab') &&
                !this.mapInitialized) {

                this.initializeMap();
            }
            RedBox.onDisplay = this.redBoxOnDisplay;
        }.bind(this);

        this.updateCalendarDropDown('kronolithEventTarget');
        this.toggleAllDay(false);
        this.openTab($('kronolithEventForm').down('.tabset a.kronolithTabLink'));
        this.disableAlarmMethods();
        $('kronolithEventForm').reset();
        kronolithEAttendeesAc.reset();
        kronolithETagAc.reset();
        $('kronolithEventAttendeesList').select('tr').invoke('remove');
        if (Kronolith.conf.maps.driver) {
            $('kronolithEventMapLink').hide();
        }
        $('kronolithEventSave').show().enable();
        $('kronolithEventSaveAsNew').show().enable();
        $('kronolithEventDelete').show();
        $('kronolithEventForm').down('.kronolithFormActions .kronolithSeparator').show();
        if (id) {
            RedBox.loading();
            this.doAction('getEvent', { cal: calendar, id: id, date: date }, this.editEventCallback.bind(this));
            $('kronolithEventTopTags').update();
        } else {
            this.doAction('listTopTags', null, this.topTagsCallback.curry('kronolithEventTopTags', 'kronolithEventTag'));
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
            this.duration = 60;
            $('kronolithEventEndDate').setValue(d.toString(Kronolith.conf.date_format));
            $('kronolithEventEndTime').setValue(d.toString(Kronolith.conf.time_format));
            $('kronolithEventLinkExport').up('span').hide();
            $('kronolithEventSaveAsNew').hide();
            this.redBoxLoading = true;
            RedBox.showHtml($('kronolithEventDialog').show());
        }
    },

    /**
     * Submits the event edit form to create or update an event.
     */
    saveEvent: function(asnew)
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
        kronolithETagAc.shutdown();
        this.startLoading(cal, start + end);
        this.doAction('saveEvent',
                      $H($('kronolithEventForm').serialize({ hash: true }))
                          .merge({
                              view: this.view,
                              view_start: start,
                              view_end: end,
                              as_new: asnew ? 1 : 0
                          }),
                      function(r) {
                          if (!asnew && r.response.events && eventid) {
                              this.removeEvent(eventid, cal);
                          }
                          this.loadEventsCallback(r);
                          this.resetMap();
                          this.closeRedBox();
                          window.history.back();
                      }.bind(this));
    },

    quickSaveEvent: function()
    {
        var text = $F('kronolithQuickinsertQ'),
            viewDates = this.viewDates(this.date, this.view),
            start = viewDates[0].dateString(),
            end = viewDates[1].dateString();

        $('kronolithQuickinsert').fade({ duration: this.effectDur });
        this.startLoading(null, start + end);
        this.doAction('quickSaveEvent',
                      $H({ text: text,
                           view: this.view,
                           view_start: start,
                           view_end: end
                      }),
                      function(r) {
                          this.loadEventsCallback(r);
                          if (Object.isUndefined(r.msgs)) {
                              $('kronolithQuickinsertQ').value = '';
                          }
                      }.bind(this));
    },

    topTagsCallback: function(update, tagclass, r)
    {
        $('kronolithEventTabTags').select('label').each(function(e) {e.show()});
        if (!r.response.tags) {
            $(update).update();
            return;
        }

        var t = new Element('div');
        r.response.tags.each(function(tag) {
            t.insert(new Element('span', { className: tagclass }).update(tag.escapeHTML()));
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
        if (!r.response.event) {
            RedBox.close();
            window.history.back();
            return;
        }

        var ev = r.response.event;

        if (!Object.isUndefined(ev.ln)) {
            this.iframeContent(ev.ln);
            this.closeRedBox();
            return;
        }

        /* Basic information */
        $('kronolithEventId').setValue(ev.id);
        $('kronolithEventCalendar').setValue(ev.ty + '|' + ev.c);
        $('kronolithEventTarget').setValue(ev.ty + '|' + ev.c);
        $('kronolithEventTitle').setValue(ev.t);
        $('kronolithEventLocation').setValue(ev.l);
        if (ev.l && Kronolith.conf.maps.driver) {
            $('kronolithEventMapLink').show();
        }
        $('kronolithEventUrl').setValue(ev.u);
        $('kronolithEventAllday').setValue(ev.al);
        this.toggleAllDay(ev.al);
        $('kronolithEventStartDate').setValue(ev.sd);
        $('kronolithEventStartTime').setValue(ev.st);
        $('kronolithEventEndDate').setValue(ev.ed);
        $('kronolithEventEndTime').setValue(ev.et);
        this.duration = Math.abs(Date.parse(ev.e).getTime() - Date.parse(ev.s).getTime()) / 60000;
        $('kronolithEventStatus').setValue(ev.x);
        $('kronolithEventDescription').setValue(ev.d);
        $('kronolithEventPrivate').setValue(ev.pv);
        $('kronolithEventLinkExport').up('span').show();
        $('kronolithEventExport').href = Kronolith.conf.URI_EVENT_EXPORT.interpolate({ id: ev.id, calendar: ev.c, type: ev.ty });

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
            if (ev.m) {
                $('kronolithEventAlarmDefaultOff').checked = true;
                $H(ev.m).each(function(method) {
                    $('kronolithEventAlarm' + method.key).setValue(1);
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
                });
            }
        } else {
            $('kronolithEventAlarmOff').setValue(true);
        }

        /* Recurrence */
        if (ev.r) {
            var scheme = Kronolith.conf.recur[ev.r.t],
                schemeLower = scheme.toLowerCase(),
                div = $('kronolithEventRepeat' + scheme);
            $('kronolithEventLink' + scheme).setValue(true);
            this.toggleRecurrence(scheme);
            if (scheme == 'Monthly' || scheme == 'Yearly') {
                div.down('input[name=recur_' + schemeLower + '_scheme][value=' + ev.r.t + ']').setValue(true);
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
                div.down('input[name=recur_' + schemeLower + '][value=1]').setValue(true);
            } else {
                div.down('input[name=recur_' + schemeLower + '][value=0]').setValue(true);
                div.down('input[name=recur_' + schemeLower + '_interval]').setValue(ev.r.i);
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
        } else if (ev.bid) {
            div = $('kronolithEventRepeatException');
            div.down('span').update(ev.eod);
            this.toggleRecurrence('Exception');
        } else {
            this.toggleRecurrence('None');
        }

        /* Attendees */
        this.freeBusy = $H();
        if (this.attendeeStartDateHandler) {
            $('kronolithEventStartDate').stopObserving('change', this.attendeeStartDateHandler);
        }
        if (!Object.isUndefined(ev.at)) {
            kronolithEAttendeesAc.reset(ev.at.pluck('l'));
            var table = $('kronolithEventAttendeesList').down('tbody');
            ev.at.each(function(attendee) {
                var tr = new Element('tr'), i;
                if (attendee.e) {
                    this.fbLoading++;
                    this.doAction('getFreeBusy',
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
                                      this.insertFreeBusy(attendee.e);
                                  }.bind(this));
                }
                tr.insert(new Element('td').writeAttribute('title', attendee.l).insert(attendee.e ? attendee.e.escapeHTML() : attendee.l));
                for (i = 0; i < 24; i++) {
                    tr.insert(new Element('td', { className: 'kronolithFBUnknown' }));
                }
                table.insert(tr);
            }, this);
            if (this.fbLoading) {
                $('kronolithFBLoading').show();
            }
            this.attendeeStartDateHandler = function() {
                ev.at.each(function(attendee) {
                    this.insertFreeBusy(attendee.e);
                }, this);
            }.bind(this);
            $('kronolithEventStartDate').observe('change', this.attendeeStartDateHandler);
        }

        /* Tags */
        kronolithETagAc.reset(ev.tg);

        /* Geo */
        if (ev.gl) {
            $('kronolithEventLocationLat').value = ev.gl.lat;
            $('kronolithEventLocationLon').value = ev.gl.lon;
        }

        if (!ev.pe) {
            $('kronolithEventSave').hide();
            kronolithETagAc.disable();
            $('kronolithEventTabTags').select('label').each(function(e) {e.hide()});
        } else {
             this.doAction('listTopTags', null, this.topTagsCallback.curry('kronolithEventTopTags', 'kronolithEventTag'));
        }
        if (!ev.pd) {
            $('kronolithEventDelete').hide();
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
     * Inserts rows with free/busy information into the attendee table.
     *
     * @todo Update when changing dates; only show free time for fb times we
     *       actually received.
     *
     * @param string email  An email address as the free/busy identifier.
     */
    insertFreeBusy: function(email)
    {
        if (!$('kronolithEventDialog').visible() ||
            !this.freeBusy.get(email)) {
            return;
        }
        var fb = this.freeBusy.get(email)[1],
            tr = this.freeBusy.get(email)[0],
            td = tr.select('td')[1],
            div = td.down('div'),
            i = 0;
        if (!td.getWidth()) {
            this.insertFreeBusy.bind(this, email).defer();
            return;
        }
        tr.select('td').each(function(td, i) {
            if (i != 0) {
                td.className = 'kronolithFBFree';
            }
            i++;
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
     * Disables all custom alarm methods in the event form.
     */
    disableAlarmMethods: function() {
        $('kronolithEventTabReminder').select('input').each(function(input) {
            if (input.name == 'event_alarms[]') {
                input.setValue(0);
                if ($(input.id + 'Params')) {
                    $(input.id + 'Params').hide();
                }
            }
        });
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
        if (recur == 'Exception') {
            $('kronolithEventRepeatException').show();
        } else if (recur != 'None') {
            $('kronolithEventRepeat' + recur).show();
            $('kronolithEventRepeatLength').show();
            $('kronolithEventRepeatType').show();
        } else {
            $('kronolithEventRepeatType').show();
        }
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
            var start = this.getDate('start'), end = this.getDate('end');
            if (start) {
                if (start.isAfter(end)) {
                    $('kronolithEventStartDate').setValue(date.toString(Kronolith.conf.date_format));
                    $('kronolithEventStartTime').setValue($F('kronolithEventEndTime'));
                }
                this.duration = Math.abs(date.getTime() - start.getTime()) / 60000;
            }
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
            hour, minute;
        if (!time || (!e.wheelData && !e.detail)) {
            return;
        }

        minute = time.getMinutes();
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
        hour = time.getHours();
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
        time.setHours(hour);
        time.setMinutes(minute);

        $(field).setValue(time.toString(Kronolith.conf.time_format));
        switch (field) {
        case 'kronolithEventStartTime':
            this.updateEndTime();
            break;
        case 'kronolithEventEndTime':
            var start = this.getDate('start'), end = this.getDate('end');
            if (start) {
                if (start.isAfter(end)) {
                    $('kronolithEventStartDate').setValue(end.toString(Kronolith.conf.date_format));
                    $('kronolithEventStartTime').setValue($F('kronolithEventEndTime'));
                }
                this.duration = Math.abs(end.getTime() - start.getTime()) / 60000;
            }
            break;
        }

        /* Mozilla bug https://bugzilla.mozilla.org/show_bug.cgi?id=502818
         * Need to stop or else multiple scroll events may be fired. We
         * lose the ability to have the mousescroll bubble up, but that is
         * more desirable than having the wrong scrolling behavior. */
        if (Prototype.Browser.Gecko && !e.stop) {
            Event.stop(e);
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

             // @TODO: a default/configurable default zoom level?
             this.placeMapMarker(ll, true, 8);
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
    onReverseGeocode: function(r)
    {
        if (!r.length) {
            $('kronolithEventLocation').value = '';
            return;
        }
        $('kronolithEventLocation').value = r[0].address;
    },

    onGeocodeError: function(r)
    {
        KronolithCore.showNotifications([ { type: 'horde.error', message: Kronolith.text.geocode_error + ' ' + r} ]);
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
        $('kronolithEventLocationLon').value = ll.lon;
        $('kronolithEventLocationLat').value = ll.lat;
        if (center) {
            this.map.setCenter(ll, zoom);
        }
    },

    /**
     * Remove the event marker from the map. Called after clearing the location
     * field.
     */
    removeMapMarker: function()
    {
        if (this.mapMarker) {
            this.map.removeMarker(this.mapMarker);
            $('kronolithEventLocationLon').value = null;
            $('kronolithEventLocationLat').value = null;
        }

        this.mapMarker = false;
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
        $('kronolithEventLinkMap').up().addClassName('activeTab');
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

        RedBox.onDisplay = function() {
            this.redBoxLoading = false;
        }.bind(this);

        document.observe('keydown', KronolithCore.keydownHandler.bindAsEventListener(KronolithCore));
        document.observe('keyup', KronolithCore.keyupHandler.bindAsEventListener(KronolithCore));
        document.observe('click', KronolithCore.clickHandler.bindAsEventListener(KronolithCore));
        document.observe('dblclick', KronolithCore.clickHandler.bindAsEventListener(KronolithCore, true));

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
        $('kronolithEventStartDate', 'kronolithEventStartTime').invoke('observe', 'change', this.updateEndTime.bind(this));

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
            $(field).observe(Prototype.Browser.Gecko ? 'DOMMouseScroll' : 'mousewheel', this.scrollDateField.bindAsEventListener(this, field));
        }, this);
        timeFields.each(function(field) {
            $(field).observe(Prototype.Browser.Gecko ? 'DOMMouseScroll' : 'mousewheel', this.scrollTimeField.bindAsEventListener(this, field));
        }, this);

        this.updateCalendarList();
        this.updateMinical(this.date);

        if (Horde.dhtmlHistory.initialize()) {
            Horde.dhtmlHistory.addListener(this.go.bind(this));
        }

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
document.observe('DragDrop2:drag', KronolithCore.onDrag.bindAsEventListener(KronolithCore));
document.observe('DragDrop2:drop', KronolithCore.onDrop.bindAsEventListener(KronolithCore));
document.observe('DragDrop2:end', KronolithCore.onDragEnd.bindAsEventListener(KronolithCore));
document.observe('DragDrop2:start', KronolithCore.onDragStart.bindAsEventListener(KronolithCore));
document.observe('Horde_Calendar:select', KronolithCore.datePickerHandler.bindAsEventListener(KronolithCore));
