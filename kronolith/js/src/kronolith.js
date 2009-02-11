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
    //  eventForm

    view: '',
    remove_gc: [],
    date: new Date(),

    debug: function(label, e)
    {
        if (!this.is_logout && Kronolith.conf.debug) {
            alert(label + ': ' + (e instanceof Error ? e.name + '-' + e.message : Object.inspect(e)));
        }
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
                    alerts = $('alerts'),
                    div = new Element('DIV', { className: m.type.replace('.', '-') }),
                    msg = m.message;

                if (!alerts) {
                    alerts = new Element('DIV', { id: 'alerts' });
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
                    iefix = new Element('DIV', { id: 'ie6alertsfix' }).clonePosition(div, { setLeft: false, setTop: false });
                    iefix.insert(div.remove());
                    alerts.insert(iefix);
                }

                if ($w('horde.error kronolith.request kronolith.sticky').indexOf(m.type) == -1) {
                    this.alertsFade.bind(this, div).delay(m.type == 'horde.warning' ? 10 : 3);
                }

                if (m.type == 'kronolith.request') {
                    this.alertrequest = div;
                }

                if (tmp = $('alertslog')) {
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
                        if (tmp.down().hasClassName('noalerts')) {
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
            div = $('alertslog').down('DIV'),
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
                parent.readAttribute('id') == 'ie6alertsfix') {
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

    /* Add/remove mouse events on the fly.
     * Parameter: object with the following names - id, type, offset
     *   (optional), left (optional), onShow (optional)
     * Valid types:
     *   'message', 'draft'  --  Message list rows
     *   'container', 'special', 'folder'  --  Folders
     *   'reply', 'forward', 'otheractions'  --  Message list buttons
     *   'contacts'  --  Linked e-mail addresses */
    addMouseEvents: function(p)
    {
        this.DMenu.addElement(p.id, 'ctx_' + p.type, p);
    },

    /* elt = DOM element */
    removeMouseEvents: function(elt)
    {
        this.DMenu.removeElement($(elt).identify());
        this.addGC(elt);
    },

    /* Add a popdown menu to an actions button. */
    addPopdown: function(bid, ctx)
    {
        var bidelt = $(bid);
        bidelt.insert({ after: $($('popdown_img').cloneNode(false)).writeAttribute('id', bid + '_img').show() });
        this.addMouseEvents({ id: bid + '_img', type: ctx, offset: bidelt.up(), left: true });
    },

    /* Utility functions. */
    addGC: function(elt)
    {
        this.remove_gc = this.remove_gc.concat(elt);
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
            if (this.view == loc) {
                break;
            }

            var locCap = loc.capitalize();
            [ 'Day', 'Week', 'Month', 'Year', 'Tasks', 'Agenda' ].each(function(a) {
                $('kronolithNav' + a).removeClassName('on');
            });
            $('kronolithNav' + locCap).addClassName('on');
            if (this.view) {
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

                this.updateView(date, loc);
                if ($('kronolithView' + locCap)) {
                    $('kronolithView' + locCap).appear();
                }

                this.updateMinical(date, loc);
                $('kronolithBody').select('div.kronolithEvent').each(function(s) {
                    s.observe('mouseover', s.addClassName.curry('kronolithSelected'));
                    s.observe('mouseout', s.removeClassName.curry('kronolithSelected'));
                });

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
            var body = $('kronolithViewMonth').down('.kronolithViewBody'),
                day = date.clone(), monthEnd = date.clone(),
                cell, monday;

            // Calculate first and last days being displayed.
            day.setDate(1);
            this.moveToBeginOfWeek(day);
            monthEnd.moveToLastDayOfMonth();
            this.moveToBeginOfWeek(monthEnd);

            // Remove old rows. Maybe we should only rebuild the calendars if
            // necessary.
            body.childElements().invoke('remove');

            // Build new calendar view.
            while (day.compareTo(monthEnd) < 1) {
                var row = body.insert(this.createWeekRow(day, date.getMonth()).show());
                day.next().week();
            }
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
        // Find monday of the week, to determine the week number.
        var monday = date.clone(), day = date.clone();
        if (monday.getDay() != 1) {
            monday.moveToDayOfWeek(1, 1);
        }

        // Create a copy of the row template.
        var row = $('kronolithRowTemplate').cloneNode(true);
        row.removeAttribute('id');

        // Fill week number and day cells.
        var cell = row.down().setText(monday.getWeek()).next();
        while (cell) {
            cell.removeClassName('kronolithOtherMonth');
            if (typeof month != 'undefined' && day.getMonth() != month) {
                cell.addClassName('kronolithOtherMonth');
            }
            cell.down('.kronolithDay').setText(day.getDate());
            cell = cell.next();
            day.add(1).day();
        }

        return row;
    },

    /**
     * Rebuilds the mini calendar
     *
     * @param Date date    The date to show in the calendar.
     * @param string view  The view that's displayed, determines which days in
     *                     the mini calendar are highlighted.
     */
    updateMinical: function(date, view)
    {
        var tbody = $('kronolithMinical').down('tbody'),
            day = date.clone(), monthEnd = date.clone(),
            weekStart, weekEnd, weekEndDay, td, tr;

        day.setDate(1);
        this.moveToBeginOfWeek(day);
        monthEnd.moveToLastDayOfMonth();
        this.moveToEndOfWeek(monthEnd);

        // Update header.
        $('kronolithMinicalDate').setText(date.toString('MMMM yyyy')).setAttribute('date', date.toString('yyyyMMdd'));

        // Remove old calendar rows. Maybe we should only rebuild the minical
        // if necessary.
        tbody.childElements().invoke('remove');

        while (day.compareTo(monthEnd) < 1) {
            // Create calendar row and insert week number.
            if (day.getDay() == Kronolith.conf.week_start) {
                tr = new Element('tr');
                tbody.insert(tr);
                td = new Element('td', { 'class': 'kronolithMinicalWeek', date: day.toString('yyyyMMdd') }).setText(day.getWeek());;
                tr.insert(td);
                weekStart = day.clone();
                weekEnd = day.clone();
                weekEnd.add(6).days();
            }
            // Insert day cell.
            td = new Element('td', {date: day.toString('yyyyMMdd')});
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
     * Parses a date attribute string into a Date object.
     *
     * For other strings use Date.parse().
     *
     * @param string date  A yyyymmdd date string.
     *
     * @return Date  A date object.
     */
    parseDate: function(date)
    {
        return new Date(date.substr(0, 4), date.substr(4, 2) - 1, date.substr(6, 2));
    },

    /**
     * Moves a date to the end of the corresponding week.
     *
     * @param Date date  A Date object. Passed by reference!
     *
     * @return Date  The same Date object, now pointing to the end of the week.
     */
    moveToEndOfWeek: function(date)
    {
        var weekEndDay = Kronolith.conf.week_start + 6;
        if (weekEndDay > 6) {
            weekEndDay -= 7;
        }
        if (date.getDay() != weekEndDay) {
            date.moveToDayOfWeek(weekEndDay, 1);
        }
        return date;
    },

    /**
     * Moves a date to the begin of the corresponding week.
     *
     * @param Date date  A Date object. Passed by reference!
     *
     * @return Date  The same Date object, now pointing to the begin of the
     *               week.
     */
    moveToBeginOfWeek: function(date)
    {
        if (date.getDay() != Kronolith.conf.week_start) {
            date.moveToDayOfWeek(Kronolith.conf.week_start, -1);
        }
        return date;
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

    _onMenuShow: function(ctx)
    {
        var elts, folder, ob, sel;

        switch (ctx.ctx) {
        case 'ctx_folder':
            elts = $('ctx_folder_create', 'ctx_folder_rename', 'ctx_folder_delete');
            folder = this.DMenu.element();
            if (folder.readAttribute('mbox') == 'INBOX') {
                elts.invoke('hide');
            } else if (Kronolith.conf.fixed_folders.indexOf(folder.readAttribute('mbox')) != -1) {
                elts.shift();
                elts.invoke('hide');
            } else {
                elts.invoke('show');
            }

            if (folder.hasAttribute('u')) {
                $('ctx_folder_poll').hide();
                $('ctx_folder_nopoll').show();
            } else {
                $('ctx_folder_poll').show();
                $('ctx_folder_nopoll').hide();
            }
            break;

        case 'ctx_message':
            [ $('ctx_message_reply_list') ].invoke(this.viewport.createSelection('domid', ctx.id).get('dataob').first().listmsg ? 'show' : 'hide');
            break;

        case 'ctx_reply':
            sel = this.viewport.getSelected();
            if (sel.size() == 1) {
                ob = sel.get('dataob').first();
            }
            [ $('ctx_reply_reply_list') ].invoke(ob && ob.listmsg ? 'show' : 'hide');
            break;

        case 'ctx_otheractions':
            $('oa_seen', 'oa_unseen', 'oa_flagged', 'oa_clear', 'oa_sep1', 'oa_blacklist', 'oa_whitelist', 'oa_sep2').compact().invoke(this.viewport.getSelected().size() ? 'show' : 'hide');
            break;
        }
        return true;
    },

    _onResize: function(noupdate, nowait)
    {
        if (this.viewport) {
            this.viewport.onResize(noupdate, nowait);
        }
        this._resizeIE6();
    },

    updateTitle: function()
    {
        var elt, label, unseen;
        if (this.viewport.isFiltering()) {
            label = Kronolith.text.search + ' :: ' + this.viewport.getMetaData('total_rows') + ' ' + Kronolith.text.resfound;
        } else {
            elt = $(this.getFolderId(this.folder));
            if (elt) {
                unseen = elt.readAttribute('u');
                label = elt.readAttribute('l');
                if (unseen > 0) {
                    label += ' (' + unseen + ')';
                }
            } else {
                label = this.viewport.getMetaData('label');
            }
        }
        this.setTitle(label);
    },

    /* Keydown event handler */
    _keydownHandler: function(e)
    {
        var kc = e.keyCode || e.charCode;

        switch (kc) {
        case Event.KEY_ESC:
            this._closeRedBox();
            break;
        }
    },

    _keyupHandler: function(e)
    {
        /*
        if (e.element().readAttribute('id') == 'foo') {
        }
        */
    },

    _clickHandler: function(e, dblclick)
    {
        if (e.isRightClick()) {
            return;
        }

        var elt = e.element(),
            orig = e.element(),
            id, tmp;

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

            case 'kronolithEventActions':
                if (orig.match('input.button')) {
	            this._closeRedBox();
                }
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

            case 'kronolithMenu':
                if (orig.match('div.kronolithCalendars div')) {
                    this.toggleCalendar(orig);
                }
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
                    this.go('week');
                } else if (orig.hasClassName('kronolithDay')) {
                    this.go('day');
                }
                e.stop();
                return;

            case 'kronolithBody':
                var tmp = orig;
                if (!tmp.match('div.kronolithEvent')) {
                    tmp = tmp.up('div.kronolithEvent');
                }
                if (tmp) {
                     this.editEvent();
                }
                e.stop();
                return;

            case 'alertsloglink':
                this.toggleAlertsLog();
                break;

            case 'alerts':
                this.alertsFade(elt);
                break;
            }

            elt = elt.up();
        }
    },

    _mouseHandler: function(e, type)
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

    editEvent: function()
    {
        // todo: fill form.
        $('kronolithEventForm').select('div.kronolithTags span').each(function(s) {
	    $('id_tags').value = $F('id_tags') + s.getText() + ', ';
        });

        RedBox.showHtml($('kronolithEventForm').show());
        this.eventForm = RedBox.getWindowContents();
    },

    _closeRedBox: function()
    {
        RedBox.close();
        this.eventForm = null;
    },

    /* Onload function. */
    onLoad: function()
    {
        if (Horde.dhtmlHistory.initialize()) {
            Horde.dhtmlHistory.addListener(this.go.bind(this));
        }

        /* Initialize the starting page if necessary. addListener() will have
         * already fired if there is a current location so only do a go()
         * call if there is no current location. */
        if (!Horde.dhtmlHistory.getCurrentLocation()) {
            this.go(Kronolith.conf.login_view);
        }

        /* Add popdown menus. */
        /*
        this.addPopdown('button_reply', 'reply');
        this.DMenu.disable('button_reply_img', true, true);
        this.addPopdown('button_forward', 'forward');
        this.DMenu.disable('button_forward_img', true, true);
        this.addPopdown('button_other', 'otheractions');
        */

        $('kronolithMenu').select('div.kronolithCalendars div').each(function(s) {
            s.observe('mouseover', s.addClassName.curry('kronolithCalOver'));
            s.observe('mouseout', s.removeClassName.curry('kronolithCalOver'));
        });

        this._resizeIE6();
    },

    // IE 6 width fixes (See Bug #6793)
    _resizeIE6: function()
    {
        // One width to rule them all:
        // 20 label, 2 label border, 2 label margin, 16 scrollbar,
        // 7 cols, 2 col border, 2 col margin
        var col_width = (($('kronolithViewMonth').getWidth()-20-2-2-16)/7)-2-2;
        $('kronolithViewMonth').select('.kronolithCol').invoke('setStyle', { width: col_width + 'px' });

        // Set month dimensions.
        // 6 rows, 2 row border, 2 row margin
        var col_height = (($('kronolithViewMonth').getHeight()-25)/6)-2-2;
        $('kronolithViewMonth').select('.kronolithViewBody .kronolithCol').invoke('setStyle', { height: col_height + 'px' });
        $('kronolithViewMonth').select('.kronolithViewBody .kronolithFirstCol').invoke('setStyle', { height: col_height + 'px' });

        // Set week dimensions.
        $('kronolithViewWeek').select('.kronolithCol').invoke('setStyle', { width: (col_width - 1) + 'px' });

        // Set day dimensions.
        // 20 label, 2 label border, 2 label margin, 16 scrollbar, 2 col border
        var head_col_width = $('kronolithViewDay').getWidth()-20-2-2-16-3;
        // 20 label, 2 label border, 2 label margin, 16 scrollbar, 2 col border
        // 7 cols
        var col_width = ((head_col_width+7)/7)-1;
        $('kronolithViewDay').select('.kronolithViewHead .kronolithCol').invoke('setStyle', { width: head_col_width + 'px' });
        $('kronolithViewDay').select('.kronolithViewBody .kronolithCol').invoke('setStyle', { width: col_width + 'px' });
        $('kronolithViewDay').select('.kronolithViewBody .kronolithAllDay .kronolithCol').invoke('setStyle', { width: head_col_width + 'px' });

        /*
        if (Kronolith.conf.is_ie6) {
            var tmp = parseInt($('sidebarPanel').getStyle('width'), 10),
                tmp1 = document.viewport.getWidth() - tmp - 30;
            $('normalfolders').setStyle({ width: tmp + 'px' });
            $('kronlithmain').setStyle({ width: tmp1 + 'px' });
            $('msglist').setStyle({ width: (tmp1 - 5) + 'px' });
            $('msgBody').setStyle({ width: (tmp1 - 25) + 'px' });
            tmp = $('dimpmain_portal').down('IFRAME');
            if (tmp) {
                this._resizeIE6Iframe(tmp);
            }
        }
        */
    },

    _resizeIE6Iframe: function(iframe)
    {
        if (Kronolith.conf.is_ie6) {
            iframe.setStyle({ width: $('kronolithmain').getStyle('width'), height: (document.viewport.getHeight() - 20) + 'px' });
        }
    },

    toggleCalendar: function(elm)
    {
        if (elm.hasClassName('on')) {
            elm.removeClassName('on');
        } else {
            elm.addClassName('on');
        }
    }

};

// Initialize DMenu now.  Need to init here because IE doesn't load dom:loaded
// in a predictable order.
if (typeof ContextSensitive != 'undefined') {
    KronolithCore.DMenu = new ContextSensitive();
}

document.observe('dom:loaded', function() {
    /* Don't do additional onload stuff if we are in a popup. We need a
     * try/catch block here since, if the page was loaded by an opener
     * out of this current domain, this will throw an exception. */
    try {
        if (parent.opener &&
            parent.opener.location.host == window.location.host &&
            parent.opener.KronolithCore) {
            Kronolith.baseWindow = parent.opener.Kronolith.baseWindow || parent.opener;
        }
    } catch (e) {}

    /* Init garbage collection function - runs every 10 seconds. */
    new PeriodicalExecuter(function() {
        if (KronolithCore.remove_gc.size()) {
            try {
                $A(KronolithCore.remove_gc.splice(0, 75)).compact().invoke('stopObserving');
            } catch (e) {
                KronolithCore.debug('remove_gc[].stopObserving', e);
            }
        }
    }, 10);

    //$('kronolithLoading').hide();
    //$('kronolithPage').show();

    /* Start message list loading as soon as possible. */
    KronolithCore.onLoad();

    /* Bind key shortcuts. */
    document.observe('keydown', KronolithCore._keydownHandler.bindAsEventListener(KronolithCore));
    document.observe('keyup', KronolithCore._keyupHandler.bindAsEventListener(KronolithCore));

    /* Bind mouse clicks. */
    document.observe('click', KronolithCore._clickHandler.bindAsEventListener(KronolithCore));
    document.observe('dblclick', KronolithCore._clickHandler.bindAsEventListener(KronolithCore, true));
    document.observe('mouseover', KronolithCore._mouseHandler.bindAsEventListener(KronolithCore, 'over'));

    /* Resize elements on window size change. */
    Event.observe(window, 'resize', KronolithCore._onResize.bind(KronolithCore));

    if (Kronolith.conf.is_ie6) {
        /* Disable text selection in preview pane for IE 6. */
        document.observe('selectstart', Event.stop);

        /* Since IE 6 doesn't support hover over non-links, use javascript
         * events to replicate mouseover CSS behavior. */
        $('foobar').compact().invoke('select', 'LI').flatten().compact().each(function(e) {
            e.observe('mouseover', e.addClassName.curry('over')).observe('mouseout', e.removeClassName.curry('over'));
        });
    }
});

Event.observe(window, 'load', function() {
    KronolithCore.window_load = true;
});

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
    numericSort: function()
    {
        return this.sort(function(a, b) {
            if (a > b) {
                return 1;
            } else if (a < b) {
                return -1;
            }
            return 0;
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
