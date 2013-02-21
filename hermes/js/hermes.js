/**
 * hermes.js - Base Hermes application logic.
 *
 * Copyright 2010-2013 Horde LLC (http://www.horde.org)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 */

 /* Hermes Object. */
HermesCore = {
    view: '',
    viewLoading: [],
    effectDur: 0.4,
    loading: 0,
    inAjaxCallback: false,
    server_error: 0,
    hermesBody: $('hermesBody'),
    clientIdMap: {},
    slices: [],
    searchSlices: [],
    sortbyfield: 'sortDate',
    searchSortbyfield: 'sortDate',
    reverseSort: false,
    searchReverseSort: false,
    sortDir: 'up',
    searchSortDir: 'up',
    today: null,
    redBoxLoading: false,
    fromSearch: false,

    onException: function(parentfunc, r, e)
    {
        this.loading--;
        if (!this.loading) {
            $('.hermesLoading').hide();
        }
        this.closeRedBox();
        HordeCore.notify(HordeCore.text.ajax_error, 'horde.error');
        parentfunc(r, e);
    },

    setTitle: function(title)
    {
        document.title = Hermes.conf.name + ' :: ' + title;
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
        if (this.viewLoading.size()) {
            this.viewLoading.push([ fullloc, data ]);
            return;
        }
        var locParts = fullloc.split(':'),
            loc = locParts.shift(),
            locCap;

        if (this.openLocation == fullloc) {
            return;
        }

        this.viewLoading.push([ fullloc, data ]);

        switch (loc) {
        case 'time':
        case 'search':
        case 'adminjobs':
            this.closeView(loc);
            locCap = loc.capitalize();
            $('hermesNav' + locCap).up().addClassName('horde-active');
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
        var current = this.viewLoading.shift(),
            next;
        if (this.viewLoading.size()) {
            next = this.viewLoading.pop();
            this.viewLoading = [];
            if (current[0] != next[0] || current[1] || next[1]) {
                this.go(next[0], next[1]);
            }
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
            // Main navigation links
            case 'hermesNavTime':
                this.go('time');
                e.stop();
                return;

            case 'hermesNavSearch':
                this.go('search');
                e.stop();
                return;

            case 'hermesNavAdminjobs':
                this.go('adminjobs');
                e.stop();
                return;

            // Time entry form actions
            case 'hermesTimeSaveAsNew':
                $('hermesTimeFormId').value = null;
            case 'hermesTimeSave':
                this.saveTime();
                e.stop();
                return;

            case 'hermesJobSaveAsNew':
                $('hermesJobFormId').value = null;
            case 'hermesJobSave':
                this.saveJobType();
                e.stop();
                return;
            case 'hermesTimeReset':
                $('hermesTimeSaveAsNew').hide();
                $('hermesTimeForm').reset();
                $('hermesTimeFormId').value = 0;
                e.stop();
                return;
            case 'hermesSearchReset':
                $('hermesSearchForm').reset();
                e.stop();
                return;
            // Slice list actions
            case 'hermesTimeListSubmit':
            case 'hermesSearchListSubmit':
                this.submitSlices();
                e.stop();
                return;

            case 'hermesSearchListHeader':
                var el = e.element().identify();
                if (el == 'sSortDate' ||
                    el == 'sSortClient' ||
                    el == 'sSortEmployee' ||
                    el == 'sSortCostObject' ||
                    el == 'sSortType' ||
                    el == 'sSortHours' ||
                    el == 'sSortBill' ||
                    el == 'sSortDesc') {

                    this.handleSearchSort(e.element());
                    e.stop();
                }
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

                    this.handleEntrySort(e.element());
                    e.stop();
                }
                return;

            case 'hermesTimeFormCollapse':
                if ($('hermesTimeForm').visible()) {
                    $('hermesTimeForm').slideUp({ duration: this.effectDur });
                    $('hermesTimeFormCollapse').removeClassName('hermesTimeFormShown');
                    $('hermesTimeFormCollapse').addClassName('hermesTimeFormHidden');
                } else {
                    $('hermesTimeForm').slideDown({ duration: this.effectDur });
                    $('hermesTimeFormCollapse').addClassName('hermesTimeFormShown');
                    $('hermesTimeFormCollapse').removeClassName('hermesTimeFormHidden');
                }
                e.stop();
                return;

            // Timer form
            case 'hermesAddTimer':
                RedBox.showHtml($('hermesTimerDialog').show());
                return;

            case 'hermesTimerSave':
                this.newTimer();
                this.closeRedBox();
                e.stop();
                return;

            case 'hermesExportCancel':
            case 'hermesTimerCancel':
                this.closeRedBox();
                e.stop();
                return;

            // Export
            case 'hermesExport':
                RedBox.showHtml($('hermesExportDialog').show());
                return;

            case 'hermesDoExport':
                var keys = this.getSearchResultKeys(),
                url = HordeCore.addURLParam(Hermes.conf.URI_EXPORT, {
                      'f': $F('hermesExportFormat'),
                      'm': $F('hermesExportMark'),
                      's': keys.join(',') });
                HordeCore.redirect(url);
                e.stop();
                return;

            // Search Form
            case 'hermesSearch':
                this.search();
                e.stop();
                return;
            }

            switch (elt.className) {
            case 'hermesDatePicker':
                id = elt.readAttribute('id');
                Horde_Calendar.open(id, Date.parseExact($F(id.replace(/Picker$/, 'Date')), Hermes.conf.date_format));
                e.stop();
                return;
            }

            if (elt.hasClassName('hermesTimeListSelect')) {
                if (elt.up().identify() == 'hermesTimeListHeader' ||
                    elt.up().identify() == 'hermesSearchListHeader') {
                    this.toggleAllRows(elt);
                    e.stop();
                    return;
                }

                elt.up().toggleClassName('hermesSelectedRow');
                elt.toggleClassName('hermesSelectedSlice');
                elt.toggleClassName('hermesUnselectedSlice');
                this.checkSelected();
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
            } else if (elt.hasClassName('timer-saveable')) {
                this.stopTimer(elt);
                e.stop();
                return;
            } else if (elt.hasClassName('timer-running')) {
                this.pauseTimer(elt);
                e.stop();
                return;
            } else if (elt.hasClassName('timer-paused')) {
                this.playTimer(elt);
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
        var select = false, target;
        if (elt.hasClassName('hermesUnselectedSlice')) {
           select = true;
        }
        if (elt.up().identify() == 'hermesTimeListHeader') {
            target = $('hermesTimeListInternal');
        } else {
            target = $('hermesSearchListInternal');
        }
        target.select('.hermesTimeListRow').each(function(e) {
            var c = e.down();
            if (select && !e.hasClassName('QuickFinderNoMatch')) {
                c.addClassName('hermesSelectedSlice');
                c.up().addClassName('hermesSelectedRow');
                c.removeClassName('hermesUnselectedSlice');
            } else {
                c.up().removeClassName('hermesSelectedRow');
                c.removeClassName('hermesSelectedSlice');
                c.addClassName('hermesUnselectedSlice');
            }
        });
        elt.toggleClassName('hermesUnselectedSlice');
        elt.toggleClassName('hermesSelectedSlice');
        this.checkSelected();
    },

    /**
     * Check that we have selected slices and [dis|en]able the submit button
     * accordingly.
     */
    checkSelected: function()
    {
        var haveSelected = false;
        if (this.view == 'time') {
            $('hermesTimeListInternal').select('.hermesSelectedSlice').each(function(s) {
                haveSelected = true;
                throw $break;
            }.bind(this));
            if (haveSelected) {
                $('hermesTimeListSubmit').enable()
            } else {
                $('hermesTimeListSubmit').disable();
            }
        } else if (this.view == 'search') {
            $('hermesSearchListInternal').select('.hermesSelectedSlice').each(function(s) {
                haveSelected = true;
                throw $break;
            }.bind(this));
            if (haveSelected) {
                $('hermesSearchListSubmit').enable()
            } else {
                $('hermesSearchListSubmit').disable();
            }
        }
    },

    /**
     * Populate the slice form with the selected time slice from the slice list.
     *
     * @param sid  The slice id.
     */
    populateSliceForm: function(sid)
    {
        var slice = this.getSliceFromCache(sid, this.view),
            d = this.parseDate(slice.d);

        $('hermesTimeSaveAsNew').show();
        $('hermesTimeFormClient').setValue(slice.c);

        HordeCore.doAction('listDeliverables',
            { 'c': $F('hermesTimeFormClient') },
            { 'callback': function(r) {
                  this.listDeliverablesCallback(r);
                  $('hermesTimeFormCostobject').setValue(slice.co);
                }.bind(this)
            }
        );
        $('hermesTimeFormStartDate').setValue(d.toString(Hermes.conf.date_format));
        $('hermesTimeFormHours').setValue(slice.h);
        $('hermesTimeFormJobtype').setValue(slice.t);
        $('hermesTimeFormDesc').setValue(slice.desc);
        $('hermesTimeFormNotes').setValue(slice.n);
        $('hermesTimeFormId').setValue(slice.i);
        $('hermesTimeFormBillable').setValue(slice.b == 1);

        // We might be on the search form when we click edit.
        this.fromSearch = (this.view == 'search');
        this.go('time');
    },

    /**
     * Permanently delete a time slice
     *
     * @param slice  The DOM element of the slice in the slice list to remove.
     */
    deleteSlice: function(slice)
    {
        var sid = slice.retrieve('sid');
        $('hermesLoadingTime').show();
        HordeCore.doAction('deleteSlice',
            { 'id': sid },
            { 'callback': this.deletesliceCallback.curry(slice).bind(this) }
        );
    },

    /**
     * Callback for the deleteSlice action. Hides the spinner, removes the
     * slice's DOM element from the UI and updates time summary.
     */
    deletesliceCallback: function(elt)
    {
        $('hermesLoadingTime').hide();
        this.removeSliceFromUI(elt);
        this.updateTimeSummary();
    },

    /**
     * Removes the slice's DOM element from the UI.
     *
     * @param elt  The DOM element of the slice in the slice list.
     */
    removeSliceFromUI: function(elt)
    {
        elt.fade({ duration: this.effectDur, queue: 'end' });
        this.removeSliceFromCache(elt.retrieve('sid'));
        this.updateTimeSummary();
    },

    /**
     * Retrieve a slice from the cache
     *
     * @param sid  The slice id.
     *
     * @return The slice entry from the cache.
     */
    getSliceFromCache: function(sid, cache)
    {
        var s, c;

        if (!cache || cache == 'time') {
           s = this.slices.length;
           c = this.slices;
        } else if (cache == 'search') {
            s = this.searchSlices.length;
            c = this.searchSlices;
        }

        for (var i = 0; i <= (s - 1); i++) {
            if (c[i].i == sid) {
                return c[i];
            }
        }
    },

    /**
     * Replaces current sid entry in the cache with slice
     *
     * @param sid    The slice id to replace.
     * @param slice  The slice data to replace it with.
     * @param cache  The cache to replace the data in (time|search)
     */
    replaceSliceInCache: function(sid, slice, cache)
    {
        if (!cache) {
            cache = 'time';
        }
        this.removeSliceFromCache(sid, cache);
        if (cache == 'time') {
            this.slices.push(slice);
        } else if (cache == 'search') {
            this.searchSlices.push(slice);
        }
    },

    /**
     * Removes sid's slice from cache
     *
     * @param sid  The slice id
     */
    removeSliceFromCache: function(sid, cache)
    {
        var s, c;

        if (!cache || cache == 'time') {
           s = this.slices.length;
           c = this.slices;
        } else if (cache == 'search') {
            s = this.searchSlices.length;
            c = this.searchSlices;
        }
        for (var i = 0; i <= (s - 1); i++) {
            if (c[i].i == sid) {
                c.splice(i, 1);
                break;
            }
        }
    },

    getSearchResultKeys: function()
    {
        var s = this.searchSlices.length,
            c = this.searchSlices,
            k = [];
        for (var i = 0; i <= (s - 1); i++) {
            k.push(c[i].i);
        }

        return k;
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
        if (this.view == 'time') {
            $('hermesLoadingTime').show();
            HordeCore.doAction('listDeliverables',
                { 'c': $F('hermesTimeFormClient') },
                { 'callback': this.listDeliverablesCallback.bind(this) }
            );
        } else if (this.view == 'search') {
            $('hermesLoadingSearch').show();
            HordeCore.doAction('listDeliverables',
                { 'c': $F('hermesSearchFormClient') },
                { 'callback': this.listDeliverablesCallback.bind(this) }
            );
        }
    },

    jobtypeChangeHandler: function(e)
    {
        HordeCore.doAction('listJobTypes',
            { 'id': $F('hermesJobTypeSelect') },
            { 'callback': this.listJobtypeCallback.bind(this) }
        );
    },

    listJobtypeCallback: function(r)
    {
        var job = r[0];
        $('hermesJobFormName').setValue(job.name);
        $('hermesJobFormId').setValue(job.id);
        $('hermesJobFormBillable').setValue(job.billable == 1);
        $('hermesJobFormEnabled').setValue(job.enabled == 1);
        $('hermesJobFormRate').setValue(job.rate);
        $('hermesJobSaveAsNew').show();
    },

    /**
     * Update the deliverable list for the current client
     */
    listDeliverablesCallback: function(r)
    {
        this.updateCostObjects(r, this.view);
    },

    updateCostObjects: function(r, view)
    {
        var h = $H(r), elm;

        if (view == 'time') {
            $('hermesLoadingTime').hide();
            elm = $('hermesTimeFormCostobject');
        } else if (view == 'search') {
            $('hermesLoadingSearch').hide();
            elm = $('hermesSearchFormCostobject');
        }
        elm.childElements().each(function(el) {
            el.remove();
        });
        h.each(function(i) {
           elm.insert(new Element('option', { 'value': i.key }).insert(i.value));
        });
    },

    saveJobType: function()
    {
        var params = $H($('hermesJobForm').serialize({ hash: true }));
        if ($F('hermesJobFormId') > 0) {
            HordeCore.doAction('updateJobType',
               params,
               { 'callback': this.updateJobTypeCallback.bind(this) }
            );
        } else {
            HordeCore.doAction('createJobType',
                params,
                { 'callback': this.createJobTypeCallback.bind(this) }
            );

        }
    },

    createJobTypeCallback: function(r)
    {
        // Build the new select list in the admin,time,search form.
    },

    updateJobTypeCallback: function(r)
    {
        // Update the jobtype select list in the admin,time,search form.
    },

    /**
     * Save a slice entry.
     */
    saveTime: function()
    {
        if (!$F('hermesTimeFormDesc') ||
            !$F('hermesTimeFormHours'),
            !$F('hermesTimeFormJobtype')) {

            HordeCore.notify(Hermes.text.fix_form_values, 'horde.warning');
            return;
        }

        var params = $H($('hermesTimeForm').serialize({ hash: true }));

        $('hermesLoadingTime').show();
        // New or Edit?
        if ($F('hermesTimeFormId') > 0) {
            HordeCore.doAction('updateSlice',
                params,
                { 'callback': this.editTimeCallback.curry($F('hermesTimeFormId')).bind(this) }
            );
        } else {
            HordeCore.doAction('enterTime',
                params,
                { 'callback': this.saveTimeCallback.bind(this) }
            );
        }
        $('hermesTimeSaveAsNew').hide();
    },

    /**
     * Callback for the enterTime action called when adding a NEW slice.
     * Just pushes the new slice on the stack, and rerenders the view.
     *
     * @param r  The results from the Ajax call.
     */
    saveTimeCallback: function(r)
    {
        $('hermesLoadingTime').hide();

        if (r === true) {
            // Successfully entered, but not for current user. Don't add to UI.
            return;
        }
        this.slices.push(r);
        this.reverseSort = false;
        this.updateView(this.view);
        this.buildTimeTable();
    },

    search: function()
    {
        var params = $H($('hermesSearchForm').serialize({ hash: true }));

        $('hermesLoadingSearch').show();
        HordeCore.doAction('search',
            params,
            { 'callback': this.searchCallback.bind(this) }
        );
    },

    searchCallback: function(r)
    {
        $('hermesLoadingSearch').hide();
        this.searchSlices = r;
        this.buildSearchResultsTable();
    },

    /**
     * Callback from the updateSlice action called when updating an EXISTING
     * slice.
     *
     * @param sid  The slice id
     * @param r    The results from the Ajax call.
     */
    editTimeCallback: function(sid, r)
    {
        $('hermesLoadingTime').hide();
        if (this.getSliceFromCache(sid)) {
            this.replaceSliceInCache(sid, r);
            this.reverseSort = false;
            this.updateView(this.view);
            this.buildTimeTable();
        }
        $('hermesTimeForm').reset();
        $('hermesTimeFormId').value = null;
        $('hermesTimeSaveAsNew').hide();

        if (this.fromSearch) {
            this.fromSearch = false;
            this.replaceSliceInCache(sid, r, 'search');
            this.updateView('search');
            this.buildSearchResultsTable();
            this.go('search');
        }
    },

    /**
     * Stores a new timer in the backend.
     */
    newTimer: function()
    {
        HordeCore.doAction('addTimer',
            { 'desc': $F('hermesTimerTitle') },
            { 'callback': this.newTimerCallback.bind(this) }
        );
    },

    /**
     * Callback for adding a new timer. Closes the timer dialog and inserts the
     * timer's details in the sideBar.
     *
     * @param r  The data returned from the Ajax method.
     */
    newTimerCallback: function(r)
    {
        if (!r.id) {
            $('hermesTimerDialog').fade({ duration: this.effectDur });
        }

        this.insertTimer({ 'id': r.id, 'e': 0, 'paused': false }, $F('hermesTimerTitle'));
    },

    /**
     * Inserts a new timer in the sideBar.
     *
     * @param r  The timer's data.
     * @param d  The timer's description.
     */
    insertTimer: function(r, d)
    {
        var title = new Element('div').update(d + ' (' + r.e + ' hours)'),
            controls = new Element('span', { 'class': 'timerControls' }),
            stop = new Element('span', { 'class': 'timerControls timer-saveable' }),
            timer = new Element('div', { 'class': 'horde-resource-none' }).store('tid', r.id),
            wrapper, wrapperClass;

        if (r.paused) {
            controls.addClassName('timer-paused');
            wrapperClass = 'inactive-timer';
        } else {
            controls.addClassName('timer-running');
            wrapperClass = 'active-timer';
        }

        wrapper = new Element('div', { 'class': wrapperClass }).insert(
            timer.insert(stop).insert(controls).insert(title)
        );
        $('hermesMenuTimers').insert({ 'top': wrapper });
        $('hermesTimerDialog').fade({
            duration: this.effectDur,
            afterFinish: function() {
                $('hermesTimerTitle').value = '';
            }
        });
    },

    /**
     * Callback for the initial listTimers call.
     *
     * @param r  The data returned from the Ajax method.
     */
    listTimersCallback: function(r)
    {
        for (var i = 0; i < r.length; i++) {
            this.insertTimer(r[i], r[i].name);
        };
    },

    /**
     * Stops and permanently deletes a timer.
     *
     * @param elt  The DOM elt of the timer in the sideBar.
     */
    stopTimer: function(elt)
    {
        HordeCore.doAction('stopTimer',
            { 't': elt.up().retrieve('tid') },
            { 'callback': this.stopTimerCallback.curry(elt).bind(this) }
        );
    },

    /**
     * Pauses a timer
     *
     * @param elt  The DOM elt of the timer in the sideBar.
     */
    pauseTimer: function(elt)
    {
        HordeCore.doAction('pauseTimer',
            { 't': elt.up().retrieve('tid') },
            { 'callback': this.pauseTimerCallback.curry(elt).bind(this) }
        );
    },

    /**
     * Restarts a paused timer.
     *
     * @param elt  The DOM elt of the timer in the sideBar.
     */
    playTimer: function(elt)
    {
        HordeCore.doAction('startTimer',
            { 't': elt.up().retrieve('tid') },
            { 'callback': this.playTimerCallback.curry(elt).bind(this) }
        );
    },

    /**
     * Callback for the stopTimer call.
     * Populates the time form with values from the timer and removes timer
     * from the sideBar.
     *
     * @param elt  The timer's sideBar DOM element.
     * @param r    The Ajax response.
     */
    stopTimerCallback: function(elt, r)
    {
        if (r) {
            $('hermesTimeFormHours').setValue(r.h);
            $('hermesTimeFormNotes').setValue(r.n);
        }
        elt.up().fade({
            duration: this.effectDur,
        });
    },

    /**
     * Callback for the pauseTimer call.
     * Updates the timer's UI to reflect it's paused status.
     *
     * @param elt  The timer's sideBar DOM element.
     */
    pauseTimerCallback: function(elt)
    {
        elt.removeClassName('timer-running');
        elt.addClassName('timer-paused');
        elt.up().up().addClassName('inactive-timer').removeClassName('active-timer');
    },

    /**
     * Callback for the playTimer call.
     * Updates the timer's UI to reflect it's running status.
     *
     * @param elt  The timer's sideBar DOM element.
     */
    playTimerCallback: function(elt)
    {
        elt.removeClassName('timer-paused');
        elt.addClassName('timer-running');
        elt.up().up().addClassName('active-timer').removeClassName('inactive-timer');
    },

    /**
     * Submit a group of slices.
     */
    submitSlices: function()
    {
        var sliceIds = [],
        slices = [],
        elt;

        if (this.view == 'time') {
            $('hermesLoadingTime').show();
            elt = $('hermesTimeListInternal');
        } else if (this.view == 'search') {
            $('hermesLoadingSearch').show();
            elt = $('hermesSearchListInternal');
        }

        elt.select('.hermesSelectedSlice').each(function(s) {
            sliceIds.push(s.up().retrieve('sid'));
            slices.push(s.up());
        }.bind(this));
        HordeCore.doAction('submitSlices',
            { 'items': sliceIds.join(':') },
            { 'callback': this.submitSlicesCallback.curry(slices).bind(this) }
        );
    },

    /**
     * Callback for the submitSlices call.
     * Responsible for hiding the spinner and removing the submitted slices from
     * the slice list.
     *
     * @param slices  The DOM elements of the slices that have been submitted.
     */
    submitSlicesCallback: function(slices)
    {
        if (this.view == 'time') {
            $('hermesLoadingTime').hide();
            slices.each(function(i) { this.removeSliceFromUI(i); }.bind(this));
        } else if (this.view == 'search') {
            $('hermesLoadingSearch').hide();
            $('hermesSearchListInternal').select('.hermesSelectedSlice').each(function(s) {
                s.removeClassName('hermesSelectedSlice')
                    .removeClassName('hermesTimeListSelect')
                    .addClassName('hermesTimeListUnselectable');
            }.bind(this));

        }
        this.checkSelected();
    },

    /**
     * Perform any tasks needed to update a view.
     *
     * @param view  The view to update.
     */
    updateView: function(view)
    {
        switch (view) {
        case 'time':
            $('hermesTimeListInternal').childElements().each(function(row) {
                row.purge();
                row.remove();
            });
            if ($('hermesTimeListHeader')) {
                $('hermesTimeListHeader').select('div').each(function(d) {
                   d.removeClassName('sortup');
                   d.removeClassName('sortdown');
                });
            }
            break;
        case 'search':
            $('hermesSearchListInternal').childElements().each(function(row) {
                row.purge();
                row.remove();
            });
            if ($('hermesSearchListHeader')) {
                $('hermesSearchListHeader').select('div').each(function(d) {
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
        $('hermesLoadingTime').show();
        this.slices = [];
        HordeCore.doAction('loadSlices',
            { 'e': Hermes.conf.user, 's': false },
            { 'callback': this.loadSlicesCallback.bind(this) }
        );
    },

    /**
     * Build the slice display
     */
    loadSlicesCallback: function(r)
    {
        $('hermesLoadingTime').hide();
        this.slices = r;
        this.buildTimeTable();
    },

    /**
     * Updates the sideBar's unsubmitted time summary.
     */
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

    /**
     * Builds the slice list.
     */
    buildTimeTable: function()
    {
        var t = $('hermesTimeListInternal'),
            slices;

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
        t.hide();
        slices.each(function(slice) {
            t.insert(this.buildTimeRow(slice).toggle());
        }.bind(this));
        $(this.sortbyfield).up('div').addClassName('sort' + this.sortDir);
        t.appear({ duration: this.effectDur, queue: 'end' });
        this.updateTimeSummary();
        $$('input').each(QuickFinder.attachBehavior.bind(QuickFinder));
    },

    /**
     * Builds the results list.
     */
    buildSearchResultsTable: function()
    {
        var t = $('hermesSearchListInternal'),
            slices, total = 0;

        t.update();
        if (this.searchReverseSort) {
            slices = this.searchSlices.reverse();
            this.searchSortDir = (this.searchSortDir == 'up') ? 'down' : 'up';
        } else {
            this.searchSortDir = 'down';
            switch (this.searchSortbyfield) {
            case 'sSortDate':
                // Date defaults to reverse
                this.searchSortDir = 'up';
                slices = this.searchSlices.sort(this.sortDate).reverse();
                break;
            case 'sSortClient':
               slices = this.searchSlices.sort(this.sortClient);
               break;
            case 'sSortCostObject':
                slices = this.searchSlices.sort(this.sortCostObject);
                break;
            case 'sSortType':
                slices = this.searchSlices.sort(this.sortType);
                break;
            case 'sSortHours':
                this.searchSortDir = 'up';
                slices = this.searchSlices.sort(this.sortHours).reverse();
                break;
            case 'sSortBill':
                slices = this.searchSlices.sort(this.sortBill);
                break;
            case 'sSortDesc':
                slices = this.searchSlices.sort(this.sortDesc);
                break;
            default:
                slices = this.searchSlices;
                break;
            }
        }
        this.searchSlices = slices;
        t.hide();
        slices.each(function(slice) {
            t.insert(this.buildSearchRow(slice).toggle());
            total = total + parseFloat(slice.h);
        }.bind(this));
        $(this.searchSortbyfield).up('div').addClassName('sort' + this.searchSortDir);
        t.appear({ duration: this.effectDur, queue: 'end' });
        this.updateTimeSummary();
        $('hermesSearchSum').update(total);
        $$('input').each(QuickFinder.attachBehavior.bind(QuickFinder));
    },

    /**
     * Builds the DOM structure for a single slice row in the slice list.
     *
     * @param slice  The slices data.
     *
     * @return A DOM element representing the slice suitable for inserting into
     *         the slice list.
     */
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

    /**
     * Builds the DOM structure for a single slice row in the results list.
     *
     * @param slice  The slices data.
     *
     * @return A DOM element representing the slice suitable for inserting into
     *         the slice list.
     */
    buildSearchRow: function(slice)
    {
        var row, cell, d;

        // Save the cn info for possible later use
        if (!HermesCore.clientIdMap[slice.c]) {
            HermesCore.clientIdMap[slice.c] = slice.cn;
        }
        row = $('hermesSearchListTemplate').clone(true);
        row.addClassName('hermesTimeListRow');
        row.removeAttribute('id');
        row.store('sid', slice.i);
        if (!slice.x) {
            row.down().removeClassName('hermesUnselectedSlice')
                .removeClassName('hermesTimeListSelect')
                .addClassName('hermesTimeListUnselectable')
                .next().next().next().next().next().next().next().next().next()
                .update();
        }
        d = this.parseDate(slice.d);
        cell = row.down().update(' ');
        cell = cell.next().update(d.toString(Hermes.conf.date_format));
        cell = cell.next().update(slice.e);
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

    handleEntrySort: function(e)
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

    handleSearchSort: function(e)
    {
        if (this.searchSortbyfield == e.identify()) {
            this.searchReverseSort = true;
        } else {
            this.searchReverseSort = false;
        }
        this.searchSortbyfield = e.identify();
        this.updateView(this.view);
        this.buildSearchResultsTable();
    },

    /**
     * Closes the currently active view.
     */
    closeView: function(loc)
    {
        $w('Time Search Admin').each(function(a) {
            a = $('hermesNav' + a);
            if (a) {
                a.up().removeClassName('horde-active');
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

    sortEmployee: function(a, b)
    {
        return (a.e < b.e) ? -1 : (a.e > b.e) ? 1 : 0;
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
        if (r) {
            for (var i = 0; i < r.length; i++) {
                var t = r[i];
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
        // General click handler.
        document.observe('click', HermesCore.clickHandler.bindAsEventListener(HermesCore));

        // Change handler for loading cost objects per client.
        $('hermesTimeFormClient').observe('change', HermesCore.clientChangeHandler.bindAsEventListener(HermesCore));
        $('hermesSearchFormClient').observe('change', HermesCore.clientChangeHandler.bindAsEventListener(HermesCore));

        // Handler for the jobtype selection
        if ($('hermesJobTypeSelect')) {
            $('hermesJobTypeSelect').observe('change', HermesCore.jobtypeChangeHandler.bindAsEventListener(HermesCore));
        }

        RedBox.onDisplay = function() {
            this.redBoxLoading = false;
        }.bind(this);
        RedBox.duration = this.effectDur;

        this.today = new Date().toString('yyyyMMdd');

        // Default the date field to today
        $('hermesTimeFormStartDate').setValue(new Date().toString(Hermes.conf.date_format));

        // Initialize the starting page.
        var tmp = location.hash;
        if (!tmp.empty() && tmp.startsWith('#')) {
            tmp = (tmp.length == 1) ? '' : tmp.substring(1);
        }
        if (!tmp.empty()) {
            this.go(decodeURIComponent(tmp));
            if (decodeURIComponent(tmp) != 'time') {
                // We need to load the slices so the time summary can display.
                this.loadSlices();
            }
        } else {
            this.go(Hermes.conf.login_view);
        }

        document.observe('Growler:toggled', function(e) {
            var button = $('hermesNotifications');
            if (e.memo.visible) {
                button.title = Hermes.text.hidelog;
                button.addClassName('hermesClose');
            } else {
                button.title = Hermes.text.alerts;
                button.removeClassName('hermesClose');
            }
        }.bindAsEventListener(this));

        // List active timers
        HordeCore.doAction('listTimers', [], { 'callback': this.listTimersCallback.bind(this) });

        // Populate the deliverables with the default list.
        HordeCore.doAction('listDeliverables',
            { },
            { 'callback': function(r) {
                this.updateCostObjects(r, 'time');
                this.updateCostObjects(r, 'search');}.bind(this)
            }
        );
        new PeriodicalExecuter(HordeCore.doAction.bind(HordeCore, 'poll'), 60);
    }
};
document.observe('dom:loaded', HermesCore.onDomLoad.bind(HermesCore));
document.observe('Horde_Calendar:select', HermesCore.datePickerHandler.bindAsEventListener(HermesCore));
HordeCore.onException = HordeCore.onException.wrap(HermesCore.onException.bind(HermesCore));


