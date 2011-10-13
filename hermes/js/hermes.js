HermesCore = {
    view: '',
    viewLoading: [],
    inPrefs: false,
    effectDur: 0.4,
    loading: 0,
    inAjaxCallback: false,
    server_error: 0,
    hermesBody: $('hermesBody'),
    clientIdMap: {},
    slices: [],
    sortbyfield: 'sortDate',
    reverseSort: false,
    sortDir: 'up',
    selectedSlices: [],
    today: null,
    redBoxLoading: false,

    doActionOpts: {
        onException: function(parentfunc, r, e)
        {
            /* Make sure loading images are closed. */
            this.loading--;
            if (!this.loading) {
                $('hermesLoading').hide();
            }
            this.closeRedBox();
            this.showNotifications([ {type: 'horde.error', message: Hermes.text.ajax_error} ]);
            this.debug('onException', e);
        }.bind(this),

        onFailure: function(t, o) {
            HermesCore.debug('onFailure', t);
            HermesCore.showNotifications([ {type: 'horde.error', message: Hermes.text.ajax_error} ]);
        },
        evalJS: false,
        evalJSON: true
    },

    /* 'action' -> if action begins with a '*', the exact string will be used
     *  instead of sending the action to the ajax handler. */
    doAction: function(action, params, callback, opts)
    {
        // poll?
        if (action == 'poll') {
            callback = this.pollCallback.bind(this);
        }
        opts = Object.extend(this.doActionOpts, opts || {});
        params = $H(params);
        action = action.startsWith('*')
            ? action.substring(1)
            : Hermes.conf.URI_AJAX + action;
        if (Hermes.conf.SESSION_ID) {
            params.update(Hermes.conf.SESSION_ID.toQueryParams());
        }
        opts.parameters = params.toQueryString();
        opts.onComplete = function(t, o) {this.doActionComplete(t, callback);}.bind(this);
        new Ajax.Request(action, opts);
    },

    doActionComplete: function(request, callback)
    {
        this.inAjaxCallback = true;

        if (!request.responseJSON) {
            if (++this.server_error == 3) {
                this.showNotifications([ {type: 'horde.error', message: Hermes.text.ajax_timeout} ]);
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
            r.msgs.push({type: 'horde.success', message: Hermes.text.ajax_recover});
        }
        this.server_error = 0;

        this.showNotifications(r.msgs);
        this.inAjaxCallback = false;
    },

    go: function(fullloc, data)
    {
        if (this.viewLoading.size()) {
            this.viewLoading.push([ fullloc, data ]);
            return;
        }
        var locParts = fullloc.split(':');
        var loc = locParts.shift();
        if (this.inPrefs && loc != 'prefs') {
            this.redirect(fullloc, true);
            return;
        }
        if (this.openLocation == fullloc) {
            return;
        }
        this.viewLoading.push([ fullloc, data ]);
        switch (loc) {
        case 'time':
        case 'search':
            this.closeView(loc);
            var locCap = loc.capitalize();
            $('hermesNav' + locCap).addClassName('on');
            switch (loc) {
            case 'time':
                this.updateView(loc);
                this.loadSlices();
                // Fall through
            default:
                if (!$('hermesView' + locCap)) {
                    break;
                }
                this.addHistory(fullloc);
                this.view = loc;
                $('hermesView' + locCap).appear({
                    duration: this.effectDur,
                    queue: 'end',
                    afterFinish: function() {
                        this.loadNextView();
                    }.bind(this)});
                break;
            }
            break;

        case 'prefs':
            var url = Hermes.conf.prefs_url;
            if (data) {
                url += (url.include('?') ? '&' : '?') + $H(data).toQueryString();
            }
            this.addHistory(loc);
            this.inPrefs = true;
            this.closeView('iframe');
            this.iframeContent(url);
            this.setTitle(Hermes.text.prefs);
            this.loadNextView();
            break;

        case 'app':
            this.addHistory(fullloc);
            this.closeView('iframe');
            var app = locParts.shift();
            if (data) {
                this.iframeContent(data);
            } else if (Hermes.conf.app_urls[app]) {
                this.iframeContent(Hermes.conf.app_urls[app]);
            }
            this.view = 'iframe';
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
     * Closes the currently active view.
     */
    closeView: function(loc)
    {
        $w('Time').each(function(a) {
            a = $('hermesNav' + a);
            if (a) {
                a.removeClassName('on');
            }
        });
        if (this.view && this.view != loc) {
            $('hermesView' + this.view.capitalize()).fade({
                duration: this.effectDur,
                queue: 'end'
            });
            this.view = null;
        }
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

    clickHandler: function(e, dblclick)
    {
        var slice, sid, elt, id;

        if (e.isRightClick() || typeof e.element != 'function') {
            return;
        }

        elt = e.element();
        while (Object.isElement(elt)) {
            id = elt.readAttribute('id');
            switch (id) {
            case 'hermesLogo':
                if (Hermes.conf.URI_HOME) {
                    this.redirect(Hermes.conf.URI_HOME);
                } else {
                    this.go(Hermes.conf.login_view);
                }
                e.stop();
                return;
            case 'hermesNavTime':
                this.go('time');
                e.stop();
                return;
            case 'hermesNavSearch':
                this.go('search');
                e.stop();
                return;
            case 'hermesTimeSaveAsNew':
                $('hermesTimeFormId').value = null;
            case 'hermesTimeSave':
                this.saveTime();
                e.stop();
                return;
            case 'hermesTimeListSubmit':
                this.submitSlices();
                e.stop();
                return;
            case 'hermesTimeListHeader':
                var el = e.element().identify();
                if (el == 'sortDate' ||
                    el == 'sortClient' ||
                    el == 'sortCostObject' ||
                    el == 'sortType' ||
                    el == 'sortHours' ||
                    el == 'sortBill' ||
                    el == 'sortDesc') {

                    this.handleSort(e.element());
                    e.stop();
                }
                return;
            case 'hermesOptions':
                this.go('prefs');
                e.stop();
                return;
            case 'hermesLogout':
                this.logout();
                e.stop();
                return;
            case 'hermesTimeFormCollapse':
                if ($('hermesTimeForm').visible()) {
                    $('hermesTimeForm').slideUp({ duration: this.effectDur });
                    $('hermesTimeFormCollapse').removeClassName('hermesTimeFormShown');
                    $('hermesTimeFormCollapse').addClassName('hermesTimeFormHidden');
                    $()
                } else {
                    $('hermesTimeForm').slideDown({ duration: this.effectDur });
                    $('hermesTimeFormCollapse').addClassName('hermesTimeFormShown');
                    $('hermesTimeFormCollapse').removeClassName('hermesTimeFormHidden');
                }
                e.stop();
                return;
            case 'hermesTimerSave':
                this.newTimer();
                e.stop();
                return;
            }

            switch (elt.className) {
            case 'hermesDatePicker':
                id = elt.readAttribute('id');
                Horde_Calendar.open(id, Date.parseExact($F(id.replace(/Picker$/, 'Date')), Hermes.conf.date_format));
                e.stop();
                return;
            case 'hermesTimeFormCancel':
                if ($('hermesTimeSaveAsNew').visible()) {
                    $('hermesTimeSaveAsNew').toggle();
                }
                $('hermesTimeForm').reset();
                // fallthrough
            case 'hermesFormCancel':
                this.closeRedBox();
                e.stop();
                return;
            case 'hermesAdd':
                $('hermesTimerDialog').appear({
                    duration: this.effectDur,
                    afterFinish: function() {
                        $('hermesTimerTitle').focus();
                    }
                });
                return;
            case 'hermesTimerCancel':
                $('hermesTimerDialog').fade({
                    duration: this.effectDur
                });
                e.stop();
                return;
            case 'hermesStopTimer':
                this.stopTimer(elt);
                e.stop();
                return;
            case 'hermesPauseTimer':
                this.pauseTimer(elt);
                e.stop();
                return;
            case 'hermesPlayTimer':
                this.playTimer(elt);
                e.stop();
                return;
            }
            if (elt.hasClassName('hermesTimeListSelect')) {
                if (elt.up().identify() == 'hermesTimeListHeader') {
                    this.toggleAllRows(elt);
                    e.stop();
                    return;
                }
                elt.up().toggleClassName('hermesSelectedRow');
                elt.toggleClassName('hermesSelectedSlice');
                elt.toggleClassName('hermesUnselectedSlice');
                e.stop();
                return;
            } else if (elt.hasClassName('sliceDelete')) {
                this.deleteSlice(elt.up().up());
                e.stop();
                return;
            } else if (elt.hasClassName('sliceEdit')) {
                slice = elt.up().up();
                sid = slice.retrieve('sid');
                this.populateSliceForm(sid);
                e.stop();
                return;
            }
            elt = elt.up();
        }

        // Workaround Firebug bug.
        Prototype.emptyFunction();
    },

    // elt Element for the checkall checkbox
    toggleAllRows: function(elt)
    {
        var select = false;
        if (elt.hasClassName('hermesUnselectedSlice')) {
           select = true;
        }
        $('hermesTimeListInternal').select('.hermesTimeListRow').each(function(e) {
            var c = e.down();
            if (select && !e.hasClassName('QuickFinderNoMatch')) {
                c.addClassName('hermesSelectedSlice');
                c.removeClassName('hermesUnselectedSlice');
            } else {
                c.removeClassName('hermesSelectedSlice');
                c.addClassName('hermesUnselectedSlice');
            }
        });
        elt.toggleClassName('hermesUnselectedSlice');
        elt.toggleClassName('hermesSelectedSlice');
    },

    populateSliceForm: function(sid)
    {
        var d, slice;
        if (!$('hermesTimeSaveAsNew').visible()) {
            $('hermesTimeSaveAsNew').toggle();
        }
        slice = this.getSliceFromCache(sid);
        $('hermesTimeFormClient').setValue(slice.c);
        // Manually update the client list, and wait for the callback to continue
        // TODO: Cache the deliverable list for each client to avoid hitting
        //       the server for each edit.
        this.doAction('listDeliverables',
              { 'c': $F('hermesTimeFormClient') },
              function(r) {
                this.listDeliverablesCallback(r);
                $('hermesTimeFormCostobject').setValue(slice.co);
              }.bind(this)
        );
        d = this.parseDate(slice.d);
        $('hermesTimeFormStartDate').setValue(d.toString(Hermes.conf.date_format));
        $('hermesTimeFormHours').setValue(slice.h);
        $('hermesTimeFormJobtype').setValue(slice.t);
        $('hermesTimeFormDesc').setValue(slice.desc);
        $('hermesTimeFormNotes').setValue(slice.n);
        $('hermesTimeFormId').setValue(slice.i);
        $('hermesTimeFormBillable').setValue(slice.b == 1);
    },

    deleteSlice: function(slice)
    {
        $('hermesLoading').show();
        sid = slice.retrieve('sid');
        this.doAction('deleteSlice', {'id': sid}, this.deletesliceCallback.curry(slice, sid).bind(this));
    },

    deletesliceCallback: function(elt, sid, r)
    {
        $('hermesLoading').hide();
        if (r.response) {
            this.removeSliceFromUI(elt, sid);
        } else {
            // Error?
        }
        this.updateTimeSummary();
    },

    removeSliceFromUI: function(elt, sid)
    {
        elt.fade({ duration: this.effectDur, queue: 'end' });
        this.removeSliceFromCache(sid);
        this.updateTimeSummary();
    },

    getSliceFromCache: function(sid)
    {
        s = this.slices.length;
        for (var i = 0; i <= (s - 1); i++) {
            if (this.slices[i].i == sid) {
                return this.slices[i];
            }
        }
    },

    // Replaces current sid entry with slice
    replaceSliceInCache: function(sid, slice)
    {
        this.removeSliceFromCache(sid);
        this.slices.push(slice);
    },

    // Removes sid's slice from cache
    removeSliceFromCache: function(sid)
    {
        s = this.slices.length;
        for (var i = 0; i <= (s - 1); i++) {
            if (this.slices[i].i == sid) {
                this.slices.splice(i, 1);
                break;
            }
        }
    },

    /**
     * Handles date selections from a date picker.
     */
    datePickerHandler: function(e)
    {
        var field = e.element().previous();
        field.setValue(e.memo.toString(Hermes.conf.date_format));
    },

    /**
     * Handle change events on the client field. Pulls in list of
     * deliverables for the client.
     */
    clientChangeHandler: function(e)
    {
        $('hermesLoading').show();
        this.doAction('listDeliverables',
                      {'c': $F('hermesTimeFormClient')},
                      this.listDeliverablesCallback.bind(this)
        );
    },

    /**
     * Update the deliverable list for the current client
     */
    listDeliverablesCallback: function(r)
    {
        $('hermesLoading').hide();
        $('hermesTimeFormCostobject').childElements().each(function(el) {
            el.remove();
        });
        var h = $H(r.response);
        h.each(function(i) {
           new Element('option', {'value': i.key});
           $('hermesTimeFormCostobject').insert(new Element('option', {'value': i.key}).insert(i.value));
        });
    },

    saveTime: function()
    {
        if (!$F('hermesTimeFormDesc') ||
            !$F('hermesTimeFormHours'),
            !$F('hermesTimeFormJobtype')) {

            alert(Hermes.text.fix_form_values);
            return;
        }
        $('hermesLoading').show();
        params = $H($('hermesTimeForm').serialize({ hash: true }));
        // New or Edit?
        if ($F('hermesTimeFormId') > 0) {
            this.doAction('updateSlice', params, this.editTimeCallback.curry($F('hermesTimeFormId')).bind(this));
        } else {
            this.doAction('enterTime', params, this.saveTimeCallback.bind(this));
        }
        $('hermesTimeSaveAsNew').hide();
    },

    saveTimeCallback: function(r)
    {
        $('hermesLoading').hide();
        // Just push the new slice on the stack, and rerender the view.
        this.slices.push(r.response);
        this.reverseSort = false;
        this.updateView(this.view);
        this.buildTimeTable();
    },

    // Handles rerendering view after updating a slice.
    // TODO: Need to probably optimise this and saveTimeCallback()
    editTimeCallback: function(sid, r)
    {
        $('hermesLoading').hide();
        this.replaceSliceInCache(sid, r.response);
        this.reverseSort = false;
        this.updateView(this.view);
        this.buildTimeTable();
        $('hermesTimeForm').reset();
        $('hermesTimeFormId').value = null;
        $('hermesTimeSaveAsNew').hide();
    },

    newTimer: function()
    {
        this.doAction('addTimer', { 'desc': $F('hermesTimerTitle') }, this.newTimerCallback.bind(this));
    },

    newTimerCallback: function(r)
    {
        if (!r.response.id) {
            $('hermesTimerDialog').fade({ duration: this.effectDur });
        }

        this.insertTimer({ 'id': r.response.id, 'e': 0, 'paused': false }, $F('hermesTimerTitle'));
    },

    insertTimer: function(r, d)
    {
        var title = new Element('div', { 'class': 'hermesTimerLabel' }).update(
            d + ' (' + r.e + ' hours)'
        );
        var stop = new Element('span', { 'class': 'hermesTimerControls' }).update(
            new Element('img', { 'class': 'hermesStopTimer', 'src': Hermes.conf.images.timerlog })
        ).store('tid', r.id);;

        if (r.paused) {
            stop.insert(new Element('img', { 'class': 'hermesPlayTimer', 'src' : Hermes.conf.images.timerplay }));
        } else {
            stop.insert(new Element('img', { 'class': 'hermesPauseTimer', 'src' : Hermes.conf.images.timerpause }));
        }

        var timer = new Element('div', { 'class': 'hermesMenuItem hermesTimer rounded' });
        timer.insert(stop).insert(title);
        $('hermesMenuTimers').insert({ 'top': timer });
        $('hermesTimerDialog').fade({
            duration: this.effectDur,
            afterFinish: function() {
                $('hermesTimerTitle').value = '';
            }
        });
    },

    listTimersCallback: function(r)
    {
        var timers = r.response;
        for (var i = 0; i < timers.length; i++) {
            this.insertTimer(timers[i], timers[i].name);
        };
    },

    stopTimer: function(elt)
    {
        var t = elt.up().retrieve('tid');
        this.doAction('stopTimer', { 't': t }, this.closeTimerCallback.curry(elt).bind(this));
    },

    pauseTimer: function(elt)
    {
        var t = elt.up().retrieve('tid');
        this.doAction('pauseTimer', { 't': t }, this.pauseTimerCallback.curry(elt).bind(this));
    },

    playTimer: function(elt)
    {
        var t = elt.up().retrieve('tid');
        this.doAction('startTimer', { 't': t }, this.playTimerCallback.curry(elt).bind(this));
    },

    closeTimerCallback: function(elt, r)
    {
        if (r.response) {
            $('hermesTimeFormHours').setValue(r.response.h);
            $('hermesTimeFormNotes').setValue(r.response.n);
        }
        elt.up().up().fade({
            duration: this.effectDur,
        });
    },

    pauseTimerCallback: function(elt, r)
    {
        elt.src = Hermes.conf.images.timerplay;
        elt.removeClassName('hermesPauseTimer');
        elt.addClassName('hermesPlayTimer');
    },

    playTimerCallback: function(elt, r)
    {
        elt.src = Hermes.conf.images.timerpause;
        elt.removeClassName('hermesPlayTimer');
        elt.addClassName('hermesPauseTimer');
    },

    //removeTimer: function(t)
    submitSlices: function()
    {
        $('hermesLoading').show();
        var sliceIds = [];
        var slices = [];
        $('hermesTimeListInternal').select('.hermesSelectedSlice').each(function(s) {
            sliceIds.push(s.up().retrieve('sid'));
            slices.push(s.up());
        }.bind(this));
        this.doAction('submitSlices',
                      { 'items': sliceIds.join(':') },
                      this.submitSlicesCallback.curry(slices).bind(this));
    },

    submitSlicesCallback: function(ids, r)
    {
        $('hermesLoading').hide();
        ids.each(function(i) { this.removeSliceFromUI(i, i.retrieve('sid'), null); }.bind(this));
    },

    /**
     * Perform any tasks needed to update a view.
     */
    updateView: function(view)
    {
        switch (view) {
        case 'time':
            var tbody = $('hermesTimeListInternal');
            // TODO: Probably more effecient way
            tbody.childElements().each(function(row) {
                row.purge();
                row.remove();
            });
            if ($('hermesTimeListHeader')) {
                $('hermesTimeListHeader').select('div').each(function(d) {
                   d.removeClassName('sortup');
                   d.removeClassName('sortdown');
                });
            }
        }
    },

    /**
     * Fetch timeslices from the server for the current user.
     */
    loadSlices: function()
    {
        $('hermesLoading').show();
        this.slices = [];
        this.doAction('getTimeSlices', { "e": Hermes.conf.user, "s": false }, this.loadSlicesCallback.bind(this));
    },

    /**
     * Build the slice display
     */
    loadSlicesCallback: function(r)
    {
        $('hermesLoading').hide();
        this.slices = r.response;
        this.buildTimeTable();
        this.updateMinical(new Date());
    },

    updateTimeSummary: function()
    {
        var total = 0, totalb = 0, today = 0, todayb = 0;
        this.slices.each(function(i) {
            var h = parseFloat(i.h);
            total = total + h;
            if (i.b == 1) { totalb = totalb + h }
            if (i.d == this.today) {
                today = today + h;
                if (i.b == 1) { todayb = todayb + h }
            }
        }.bind(this));

        $('hermesSummaryTodayBillable').down().update(todayb.toFixed(2));
        $('hermesSummaryTodayNonBillable').down().update((today - todayb).toFixed(2));
        $('hermesSummaryTotalBillable').down().update(totalb.toFixed(2));
        $('hermesSummaryTotalNonBillable').down().update((total - totalb).toFixed(2));
    },

    buildTimeTable: function()
    {
        var slices, t;
        if (this.reverseSort) {
            slices = this.slices.reverse();
            this.sortDir = (this.sortDir == 'up') ? 'down' : 'up';
        } else {
            this.sortDir = 'down';
            switch (this.sortbyfield) {
            case 'sortDate':
                // Date defaults to reverse
                this.sortDir = 'up';
                slices = this.slices.sort(this.sortDate).reverse();
                break;
            case 'sortClient':
               slices = this.slices.sort(this.sortClient);
               break;
            case 'sortCostObject':
                slices = this.slices.sort(this.sortCostObject);
                break;
            case 'sortType':
                slices = this.slices.sort(this.sortType);
                break;
            case 'sortHours':
                this.sortDir = 'up';
                slices = this.slices.sort(this.sortHours).reverse();
                break;
            case 'sortBill':
                slices = this.slices.sort(this.sortBill);
                break;
            case 'sortDesc':
                slices = this.slices.sort(this.sortDesc);
                break;
            default:
                slices = this.slices;
                break;
            }
        }
        this.slices = slices;
        t = $('hermesTimeListInternal');
        t.hide();
        slices.each(function(slice) {
            t.insert(this.buildTimeRow(slice).toggle());
        }.bind(this));
        $(this.sortbyfield).up('div').addClassName('sort' + this.sortDir);
        t.appear({ duration: this.effectDur, queue: 'end' });
        this.onResize();
        this.updateTimeSummary();
        // Init the quickfinder now that we have a list of children.
        $$('input').each(QuickFinder.attachBehavior.bind(QuickFinder));
    },

    buildTimeRow: function(slice)
    {
        var row, cell, d;

        // Save the cn info for possible later use
        if (!HermesCore.clientIdMap[slice.c]) {
            HermesCore.clientIdMap[slice.c] = slice.cn;
        }
        row = $('hermesTimeListTemplate').clone(true);
        row.addClassName('hermesTimeListRow');
        row.removeAttribute('id');
        row.store('sid', slice.i);
        d = this.parseDate(slice.d);
        cell = row.down().update(' ');
        cell = cell.next().update(d.toString(Hermes.conf.date_format));
        if (!slice.cn) {
            cell = cell.next().update(' ');
        } else {
            cell = cell.next().update(slice.cn[Hermes.conf.client_name_field]);
        }
        cell = cell.next().update((slice.con) ? slice.con : ' ');
        cell = cell.next().update((slice.tn) ? slice.tn : ' ');
        cell = cell.next().update((slice.desc) ? slice.desc : ' ');
        cell = cell.next().update((slice.b == 1) ? 'Y' : 'N');
        cell = cell.next().update(slice.h);
        return row;
    },

    handleSort: function(e)
    {
        if (this.sortbyfield == e.identify()) {
            this.reverseSort = true;
        } else {
            this.reverseSort = false;
        }
        this.sortbyfield = e.identify();
        this.updateView(this.view);
        this.buildTimeTable();
    },

    /**
     * Loads an external page into the iframe view.
     *
     * @param string loc  The URL of the page to load.
     */
    iframeContent: function(loc)
    {
        var view = $('hermesViewIframe'), iframe = $('hermesIframe');
        view.hide();
        if (!iframe) {
            view.insert(new Element('iframe', {id: 'hermesIframe', className: 'hermesIframe', frameBorder: 0}));
            iframe = $('hermesIframe');
        }
        iframe.observe('load', function() {
            view.appear({duration: this.effectDur, queue: 'end'});
            iframe.stopObserving('load');
        }.bind(this));
        iframe.src = loc;
        this.view = 'iframe';
    },

    setTitle: function(title)
    {
        document.title = Hermes.conf.name + ' :: ' + title;
        return title;
    },

    logout: function(url)
    {
        this.is_logout = true;
        this.redirect(url || (Hermes.conf.URI_AJAX + 'logOut'));
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
            window.location.assign(this.addURLParam(url));
        }
    },

    addURLParam: function(url, params)
    {
        var q = url.indexOf('?');
        params = $H(params);

        if (Hermes.conf.SESSION_ID) {
            params.update(Hermes.conf.SESSION_ID.toQueryParams());
        }

        if (q != -1) {
            params.update(url.toQueryParams());
            url = url.substring(0, q);
        }

        return params.size() ? (url + '?' + params.toQueryString()) : url;
    },

    /**
     * Closes the currently active view.
     */
    closeView: function(loc)
    {
        $w('Time CostObjects Clients JobTypes Search').each(function(a) {
            a = $('HermesNav' + a);
            if (a) {
                a.removeClassName('on');
            }
        });
        if (this.view && this.view != loc) {
            $('hermesView' + this.view.capitalize()).fade({
                duration: this.effectDur,
                queue: 'end'
            });
            this.view = null;
        }
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

                var message = alarm.title.escapeHTML();
                if (alarm.params && alarm.params.notify) {
                    if (alarm.params.notify.ajax) {
                        message = new Element('a')
                            .insert(message)
                            .observe('click', function(e) {
                                this.Growler.ungrowl(e.findElement('div'));
                                this.go(alarm.params.notify.ajax);
                            }.bindAsEventListener(this));
                    } else if (alarm.params.notify.url) {
                        message = new Element('a', {href: alarm.params.notify.url})
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
                    $H(Hermes.conf.snooze).each(function(snooze) {
                        select += '<option value="' + snooze.key + '">' + snooze.value + '</option>';
                    });
                    select += '</select>';
                    message.insert('<br /><br />' + Hermes.text.snooze.interpolate({time: select, dismiss_start: '<input type="button" value="', dismiss_end: '" class="button ko" />'}));
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
                                Hermes.conf.URI_SNOOZE,
                                {parameters: {alarm: alarm.id,
                                                snooze: e.element().getValue()}});
                        }
                    }.bindAsEventListener(this))
                    .observe('click', function(e) {
                        e.stop();
                    });
                    message.down('input[type=button]').observe('click', function(e) {
                        new Ajax.Request(
                            Hermes.conf.URI_SNOOZE,
                            {parameters: {alarm: alarm.id,
                                            snooze: -1}});
                    }.bindAsEventListener(this));
                }
                break;

            case 'horde.error':
            case 'horde.warning':
            case 'horde.message':
            case 'horde.success':
                this.Growler.growl(
                    m.flags && m.flags.include('content.raw')
                        ? m.message.replace(new RegExp('<a href="([^"]+)"'), '<a href="#" onclick="HermesCore.iframeContent(\'$1\')"')
                        : m.message.escapeHTML(),
                    {
                        className: m.type.replace('.', '-'),
                        life: 8,
                        log: true,
                        sticky: m.type == 'horde.error'
                    });
                var notify = $('hermesNotifications'),
                    className = m.type.replace(/\./, '-'),
                    order = 'horde-error,horde-warning,horde-message,horde-success,hermesNotifications',
                    open = notify.hasClassName('hermesClose');
                notify.removeClassName('hermesClose');
                if (order.indexOf(notify.className) > order.indexOf(className)) {
                    notify.className = className;
                }
                if (open) {
                    notify.addClassName('hermesClose');
                }
                break;
            }
        }, this);
    },

    debug: function(label, e)
    {
        if (!this.is_logout && window.console && window.console.error) {
            window.console.error(label, Prototype.Browser.Gecko ? e : $H(e).inspect());
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

    onResize: function(event)
    {
        //$('hermesTimeListBody').setStyle({height: document.height - 440 + 'px'});
    },

    sortDate: function(a, b)
    {
       return (a.d < b.d) ? -1 : (a.d > b.d) ? 1 : 0;
    },

    sortClient: function(a, b)
    {
        return (a.cn.name < b.cn.name) ? -1 : (a.cn.name > b.cn.name) ? 1 : 0;
    },

    sortCostObject: function(a, b)
    {
        return (a.con < b.con) ? -1 : (a.con > b.con) ? 1 : 0;
    },

    sortType: function(a, b)
    {
        return (a.tn < b.tn) ? -1 : (a.tn > b.tn) ? 1 : 0;
    },

    sortHours: function(a, b)
    {
        return (parseFloat(a.h) < parseFloat(b.h)) ? -1 : (parseFloat(a.h) > parseFloat(b.h)) ? 1 : 0;
    },

    sortBill: function(a, b)
    {
        return (a.b < b.b) ? -1 : (a.b > b.b) ? 1 : 0;
    },

    sortDesc: function(a, b)
    {
        return (a.desc < b.desc) ? -1 : (a.desc > b.desc) ? 1 : 0;
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
        $('hermesMinicalDate')
            .store('date', date.dateString())
            .update(date.toString('MMMM yyyy'));
        this.buildMinical($('hermesMinical').down('tbody'), date, view);
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
            weekStart, weekEnd, dateString, td, tr, i;

        // Remove old calendar rows. Maybe we should only rebuild the minical
        // if necessary.
        tbody.childElements().invoke('remove');

        for (i = 0; i < 42; i++) {
            dateString = day.dateString();
            // Create calendar row and insert week number.
            if (day.getDay() == 0) {
                tr = new Element('tr');
                tbody.insert(tr);
                td = new Element('td', { className: 'hermesMinicalWeek' })
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
                td.addClassName('hermesMinicalEmpty');
            } else if (!Object.isUndefined(idPrefix)) {
                td.id = idPrefix + dateString;
            }

            // Highlight days currently being displayed.
            //if (view &&
            //    (view == 'month' ||
            //     (view == 'week' && date.between(weekStart, weekEnd)) ||
            //     (view == 'day' && date.equals(day)) ||
            //     (view == 'agenda' && !day.isBefore(date) && day.isBefore(date7)))) {
            if (date.between(weekStart, weekEnd)) {
                td.addClassName('heremsSelected');
            }

            // Highlight today.
            if (day.equals(today)) {
                td.addClassName('hermesToday');
            }
            td.update(day.getDate());
            tr.insert(td);
            day.next().day();
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
            start.moveToBeginOfWeek(0);
            end.moveToEndOfWeek(0);
            break;
        case 'month':
            start.setDate(1);
            start.moveToBeginOfWeek(0);
            end.moveToLastDayOfMonth();
            end.moveToEndOfWeek(0);
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

    pollCallback: function(r)
    {
        // Update timers.
        if(r.response) {
            for (var i = 0; i < r.response.length; i++) {
                var t = r.response[i];
                $('hermesMenuTimers').select('.hermesMenuItem').each(function(elt) {
                    if (elt.down('.hermesStopTimer').up().retrieve('tid') == t['id']) {
                        elt.down('.hermesTimerLabel').update(t.name + ' (' + t.e + ' hours)');
                    }
                });
            }
        }
    },

    /* Onload function. */
    onDomLoad: function()
    {
        document.observe('click', HermesCore.clickHandler.bindAsEventListener(HermesCore));
        $('hermesTimeFormClient').observe('change', HermesCore.clientChangeHandler.bindAsEventListener(HermesCore));

        RedBox.onDisplay = function() {
            this.redBoxLoading = false;
        }.bind(this);
        RedBox.duration = this.effectDur;

        // @TODO: Minical that have dates with hours highlighted?
        //this.updateMinical(this.date);
        this.today = new Date().toString('yyyyMMdd');

        // Default the date field to today
        $('hermesTimeFormStartDate').setValue(new Date().toString(Hermes.conf.date_format));

        /* Initialize the starting page. */
        var tmp = location.hash;

        if (!tmp.empty() && tmp.startsWith('#')) {
            tmp = (tmp.length == 1) ? '' : tmp.substring(1);
        }
        if (!tmp.empty()) {
            this.go(decodeURIComponent(tmp));
        } else {
            this.go(Hermes.conf.login_view);
        }

        /* Add Growler notifications. */
        this.Growler = new Growler({
            log: true,
            location: 'br',
            noalerts: Hermes.text.noalerts,
            info: Hermes.text.growlerinfo
        });
        this.Growler.growlerlog.observe('Growler:toggled', function(e) {
            var button = $('hermesNotifications');
            if (e.memo.visible) {
                button.title = Hermes.text.hidelog;
                button.addClassName('hermesClose');
            } else {
                button.title = Hermes.text.alerts.interpolate({count: this.growls});
                button.removeClassName('hermesClose');
            }
            Horde_ToolTips.detach(button);
            Horde_ToolTips.attach(button);
        }.bindAsEventListener(this));

        this.doAction('listTimers', [], this.listTimersCallback.bind(this));

        /* Start polling. */
        new PeriodicalExecuter(this.doAction.bind(this, 'poll'), 120);
        document.observe('Horde_Calendar:select', HermesCore.datePickerHandler.bindAsEventListener(HermesCore));
        Event.observe(window, 'resize', this.onResize.bind(this));
    }

};
document.observe('dom:loaded', HermesCore.onDomLoad.bind(HermesCore));
