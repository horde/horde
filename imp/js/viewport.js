/**
 * viewport.js - Code to create a viewport window, with optional split pane
 * functionality.
 *
 * Usage:
 * ======
 * var viewport = new ViewPort({ options });
 *
 * Required options:
 * -----------------
 * ajax: (function) The function that will send the AJAX request to the server
 *       endpoint. The parameters to send to the server will be passed in as
 *       the first argument as a Hash object.
 * container: (Element/string) A DOM element/ID of the container that holds
 *            the viewport. This element should be empty and have no children.
 * onContent: (function) A function that takes 2 arguments - the data object
 *            for the row and a string indicating the current pane_mode.
 *
 *            This function MUST return the HTML representation of the row.
 *
 *            This representation MUST include both the DOM ID (stored in
 *            the VP_domid data entry) and the CSS class name (stored as an
 *            array in the VP_bg data entry) in the outermost element.
 *
 *            Selected rows will contain the classname 'vpRowSelected'.
 *
 *
 * Optional options:
 * -----------------
 * buffer_pages: (integer) The number of viewable pages to send to the browser
 *               per server access when listing rows.
 * empty_msg: (string | function) A string to display when the view is empty.
 *            Inserted in a SPAN element with class 'vpEmpty'. If a function,
 *            will use the return value from the function for the text.
 * limit_factor: (integer) When browsing through a list, if a user comes
 *               within this percentage of the end of the current cached
 *               viewport, send a background request to the server to retrieve
 *               the next slice.
 * list_class: (string) The CSS class to use for the results list.
 * list_header: (Element/string) A DOM element to insert above the results list
 *              as a header.
 * lookbehind: (integer) What percentage of the received buffer should be
 *             used to download rows before the given row number?
 * onAjaxRequest: (function) Callback function that allows additional
 *                parameters to be added to the outgoing AJAX request.
 *                params: (Hash) The params list (the current view can be
 *                        obtained via the view property).
 *                return: (Hash) The params list to use for the outgoing
 *                        request.
 * onContentOffset: (function) Callback function that alters the starting
 *                  offset of the content about to be rendered.
 *                  params: (integer) The current offset.
 *                  return: (integer) The altered offset.
 * onSlide: (function) Callback function that is triggered when the
 *          viewport slider bar is moved.
 *          params: NONE
 *          return: NONE
 * page_size: (integer) Default page size to view on load. Only used if
 *            pane_mode is 'horiz'.
 * pane_data: (Element/string) A DOM element/ID of the container to hold
 *            the split pane data. This element will be moved inside of the
 *            container element.
 * pane_mode: (string) The split pane mode to show on load? Either empty,
 *            'horiz', or 'vert'.
 * pane_width: (integer) The default pane width to use on load. Only used if
 *             pane_mode is 'vert'.
 * split_bar_class: (object) The CSS class(es) to use for the split bar.
 *                  Takes two properties: 'horiz' and 'vert'.
 * split_bar_handle_class: (object) The CSS class(es) to use for the split bar
 *                         handle. Takes two properties: 'horiz' and 'vert'.
 * wait: (integer) How long, in seconds, to wait before displaying an
 *       informational message to users that the list is still being
 *       built.
 *
 *
 * Custom events:
 * --------------
 * Custom events are triggered on the container element. The parameters given
 * below are available through the 'memo' property of the Event object.
 *
 * ViewPort:add
 *   Fired when a row has been added to the screen.
 *   params: (Element) The viewport row being added.
 *
 * ViewPort:clear
 *   Fired when a row is being removed from the screen.
 *   params: (Element) The viewport row being removed.
 *
 * ViewPort:contentComplete
 *   Fired when the view has changed and all viewport rows have been added.
 *   params: NONE
 *
 * ViewPort:deselect
 *   Fired when rows are deselected.
 *   params: (object) opts = (object) Boolean options [right]
 *                    vs = (ViewPort_Selection) A ViewPort_Selection object.
 *
 * ViewPort:endFetch
 *   Fired when a fetch AJAX response is completed.
 *   params: (string) Current view.
 *
 * ViewPort:endRangeFetch
 *   Fired when a fetch rangeslice AJAX response is completed.
 *   params: (string) Current view.
 *
 * ViewPort:fetch
 *   Fired when a non-background AJAX response is sent.
 *   params: (string) Current view.
 *
 * ViewPort:remove
 *   Fired when rows are removed from the buffer.
 *   params: (ViewPort_Selection) The removed rows.
 *
 * ViewPort:select
 *   Fired when rows are selected.
 *   params: (object) opts = (object) Boolean options [delay, right]
 *                    vs = (ViewPort_Selection) A ViewPort_Selection object.
 *
 * ViewPort:splitBarChange
 *   Fired when the splitbar is moved.
 *   params: (string) The current pane mode ('horiz' or 'vert').
 *
 * ViewPort:splitBarEnd
 *   Fired when the splitbar is released.
 *   params: (string) The current pane mode ('horiz' or 'vert').
 *
 * ViewPort:splitBarStart
 *   Fired when the splitbar is initially clicked.
 *   params: (string) The current pane mode ('horiz' or 'vert').
 *
 * ViewPort:wait
 *   Fired if viewport_wait seconds have passed since request was sent.
 *   params: (string) Current view.
 *
 *
 * Outgoing AJAX request has the following params:
 * -----------------------------------------------
 * For ALL requests:
 *   cache: (string) The list of uids cached on the browser.
 *   cacheid: (string) A unique string that changes whenever the viewport
 *            list changes.
 *   initial: (integer) This is the initial browser request for this view.
 *   requestid: (integer) A unique identifier for this AJAX request.
 *   view: (string) The view of the request.
 *
 * For a row request:
 *   slice: (string) The list of rows to retrieve from the server.
 *          In the format: [first_row]:[last_row]
 *
 * For a search request:
 *   after: (integer) The number of rows to return after the selected row.
 *   before: (integer) The number of rows to return before the selected row.
 *   search: (JSON object) The search query.
 *
 * For a rangeslice request:
 *   rangeslice: (integer) If present, indicates that slice is a rangeslice
 *               request.
 *   slice: (string) The list of rows to retrieve from the server.
 *          In the format: [first_row]:[last_row]
 *
 *
 * Incoming AJAX response has the following parameters:
 * ----------------------------------------------------
 * cacheid: (string) A unique string that changes whenever the viewport
 *          list changes.
 * data: (object) Data for each entry. Keys are a unique ID (see also the
 *       'rowlist' entry). Values are the data objects. Internal keys for
 *       these data objects must NOT begin with the string 'VP_' (reserved
 *       keys). These values update current cached values.
 * data_reset: (integer) If set, purge all browser cached data objects.
 * disappear: (array) The list of unique IDs that are browser cached but no
 *            longer exist on the server.
 * label: (string) [REQUIRED on initial response] The label to use for the
 *        view.
 * metadata: (object) Metadata for the view. Entries in buffer are updated
 *           with these entries.
 * metadata_reset: (integer) If set, purges all browser cached metadata.
 * rangelist: (object) The list of unique IDs -> rownumbers that correspond
 *            to the given request. Only returned for a rangeslice request.
 * requestid: (string) The request ID from the outgoing AJAX request.
 * rowlist: (object) A mapping of unique IDs (keys) to the row numbers
 *          (values). Row numbers start at 1.
 * rowlist_reset: (integer) If set, purges the browser cached rowlist.
 * rownum: (integer) The row number to position screen on.
 * totalrows: (integer) Total number of rows in the view.
 * view: (string) The view ID of the request.
 *
 *
 * Data entries:
 * -------------
 * In addition to the data provided from the server, the following
 * dynamically created entries are also available:
 *   VP_domid: (string) The DOM ID of the row.
 *   VP_id: (string) The unique ID used to store the data entry.
 *   VP_rownum: (integer) The row number of the row.
 *
 *
 * Scroll bars are styled using these CSS class names:
 * ---------------------------------------------------
 * vpScroll - The scroll bar container.
 * vpScrollUp - The UP arrow.
 * vpScrollCursor - The cursor used to slide within the bounds.
 * vpScrollDown - The DOWN arrow.
 *
 *
 * Requires prototypejs 1.6+, scriptaculous 1.8+ (effects.js only), and
 * Horde's dragdrop2.js and slider2.js.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

var ViewPort = Class.create({

    initialize: function(opts)
    {
        this.opts = Object.extend({
            buffer_pages: 10,
            limit_factor: 35,
            lookbehind: 40,
            split_bar_class: {},
            split_bar_handle_class: {}
        }, opts);

        this.opts.container = $(opts.container);
        this.opts.pane_data = $(opts.pane_data);

        this.opts.content = new Element('DIV', { className: opts.list_class }).setStyle({ float: 'left' });
        this.opts.list_container = new Element('DIV');
        if (this.opts.list_header) {
            this.opts.list_container.insert(this.opts.list_header);
        }
        this.opts.list_container.insert(this.opts.content);

        this.opts.container.insert(this.opts.list_container);

        this.scroller = new ViewPort_Scroller(this);

        this.split_pane = {
            curr: null,
            currbar: null,
            horiz: {
                loc: opts.page_size
            },
            init: false,
            spacer: null,
            vert: {
                width: opts.pane_width
            }
        };
        this.views = {};

        this.pane_mode = opts.pane_mode;

        this.isbusy = this.page_size = null;
        this.request_num = 1;
        this.id = 0;

        // Init empty string now.
        this.empty_msg = new Element('SPAN', { className: 'vpEmpty' });

        Event.observe(window, 'resize', this.onResize.bind(this));
    },

    // view = (string) ID of view.
    // opts = (object) background: (boolean) Load view in background?
    //                 search: (object) Search parameters
    loadView: function(view, opts)
    {
        var buffer, curr, ps,
            f_opts = {},
            init = true;

        this._clearWait();

        // Need a page size before we can continue - this is what determines
        // the slice size to request from the server.
        if (this.page_size === null) {
            ps = this.getPageSize(this.pane_mode ? 'default' : 'max');
            if (isNaN(ps)) {
                return this.loadView.bind(this, view, opts).defer();
            }
            this.page_size = ps;
        }

        if (this.view) {
            if (!opts.background && (view != this.view)) {
                // Need to store current buffer to save current offset
                buffer = this._getBuffer();
                buffer.setMetaData({ offset: this.currentOffset() }, true);
                this.views[this.view] = buffer;
            }
            init = false;
        }

        if (opts.background) {
            f_opts = { background: true, view: view };
        } else {
            if (!this.view) {
                this.onResize(true);
            } else if (this.view != view) {
                delete this.active_req;
            }
            this.view = view;
        }

        if (curr = this.views[view]) {
            this._updateContent(curr.getMetaData('offset') || 0, f_opts);
            if (!opts.background) {
                this.opts.ajax(this.addRequestParams({ checkcache: 1 }));
            }
            return;
        }

        if (!init) {
            this.visibleRows().each(this.opts.content.fire.bind(this.opts.content, 'ViewPort:clear'));
            this.opts.content.update();
            this.scroller.clear();
        }

        this.views[view] = buffer = this._getBuffer(view, true);

        if (opts.search) {
            f_opts.search = opts.search;
        } else {
            f_opts.offset = 0;
        }

        f_opts.initial = 1;

        this._fetchBuffer(f_opts);
    },

    // view = ID of view
    deleteView: function(view)
    {
        if (this.view == view) {
            return false;
        }

        delete this.views[view];
        return true;
    },

    // rownum = (integer) Row number
    // opts = (Object) [bottom, noupdate, top] TODO
    scrollTo: function(rownum, opts)
    {
        var s = this.scroller,
            to = null;
        opts = opts || {};

        s.noupdate = opts.noupdate;

        switch (this.isVisible(rownum)) {
        case -1:
            to = rownum - 1;
            break;

        case 0:
            if (opts.top) {
                to = rownum - 1;
            }
            break;

        case 1:
            to = opts.bottom
                ? Math.max(0, rownum - this.getPageSize())
                : Math.min(rownum - 1, this.getMetaData('total_rows') - this.getPageSize());
            break;
        }

        if (to !== null) {
            s.moveScroll(to);
        }

        s.noupdate = false;
    },

    // rownum = (integer) Row number
    isVisible: function(rownum)
    {
        var offset = this.currentOffset();
        return (rownum < offset + 1)
            ? -1
            : ((rownum > (offset + this.getPageSize('current'))) ? 1 : 0);
    },

    // params = (object) Parameters to add to outgoing URL
    reload: function(params)
    {
        this._fetchBuffer({
            offset: this.currentOffset(),
            params: $H(params),
            purge: true
        });
    },

    // vs = (ViewPort_Selection) A ViewPort_Selection object.
    remove: function(vs)
    {
        if (vs.size()) {
            if (this.isbusy) {
                this.remove.bind(this, vs).defer();
            } else {
                this.isbusy = true;
                try {
                    this._remove(vs);
                    if (vs.getBuffer().getView() == this.view) {
                        this.requestContentRefresh(this.currentOffset());
                    }
                } catch (e) {
                    this.isbusy = false;
                    throw e;
                }
                this.isbusy = false;
            }
        }
    },

    // vs = (ViewPort_Selection) A ViewPort_Selection object.
    _remove: function(vs)
    {
        var buffer = vs.getBuffer();

        if (buffer.getView() == this.view) {
            this.deselect(vs);
        }

        this.opts.container.fire('ViewPort:remove', vs);

        buffer.remove(vs.get('rownum'));
        buffer.setMetaData({ total_rows: buffer.getMetaData('total_rows') - vs.size() }, true);
    },

    // nowait = (boolean) If true, don't delay before resizing.
    // size = (integer) The page size to use instead of auto-determining.
    onResize: function(nowait, size)
    {
        if (!this.opts.content.visible()) {
            return;
        }

        if (this.resizefunc) {
            clearTimeout(this.resizefunc);
        }

        if (nowait) {
            this._onResize(size);
        } else {
            this.resizefunc = this._onResize.bind(this, size).defer();
        }
    },

    // size = (integer) The page size to use instead of auto-determining.
    _onResize: function(size)
    {
        var c_opts = {},
            h = this.opts.list_header ?  this.opts.list_header.getHeight() : 0,
            lh = this._getLineHeight(),
            sp = this.split_pane;

        if (size) {
            this.page_size = size;
        }

        if (this.view && sp.curr != this.pane_mode) {
            c_opts.updated = true;
        }

        // Get split pane dimensions
        switch (this.pane_mode) {
        case 'horiz':
            this._initSplitBar();

            if (!size) {
                this.page_size = (sp.horiz.loc && sp.horiz.loc > 0)
                    ? Math.min(sp.horiz.loc, this.getPageSize('max'))
                    : this.getPageSize('default');
            }
            sp.horiz.loc = this.page_size;

            h += lh * this.page_size;
            this.opts.list_container.setStyle({
                height: h + 'px',
                float: 'none',
                overflow: 'hidden',
                width: 'auto'
            });
            this.opts.content.setStyle({ width: '100%' });
            sp.currbar.show();
            this.opts.pane_data.show().setStyle({ height: Math.max(document.viewport.getHeight() - this.opts.pane_data.viewportOffset()[1], 0) + 'px' });
            break;

        case 'vert':
            this._initSplitBar();

            if (!size) {
                this.page_size = this.getPageSize('max');
            }

            if (!sp.vert.width) {
                sp.vert.width = parseInt(this.opts.container.clientWidth * 0.35, 10);
            }

            h += lh * this.page_size;
            this.opts.list_container.setStyle({
                float: 'left',
                height: h + 'px'
            });
            this.opts.content.setStyle({ width: sp.vert.width + 'px' });
            sp.currbar.setStyle({ height: h - sp.currbar.getLayout().get('border-bottom') + 'px' }).show();
            this.opts.pane_data.setStyle({ height: h - this.opts.pane_data.getLayout().get('border-bottom') + 'px' }).show();
            break;

        default:
            if (sp.curr) {
                if (this.pane_mode == 'horiz') {
                    sp.horiz.loc = this.page_size;
                }
                [ this.opts.pane_data, sp.currbar ].invoke('hide');
                delete sp.curr;
                delete sp.currbar;
            }

            if (!size) {
                this.page_size = this.getPageSize('max');
            }

            this.opts.list_container.setStyle({
                float: 'none',
                height: (h + (lh * this.page_size)) + 'px',
                overflow: 'hidden',
                width: 'auto'
            });
            this.opts.content.setStyle({ width: '100%' });
            break;
        }

        if (this.view) {
            this.requestContentRefresh(this.currentOffset(), c_opts);
        }
    },

    // offset = (integer) Offset of row to display
    // opts = (object) See _updateContent()
    requestContentRefresh: function(offset, opts)
    {
        offset = Math.max(0, offset);

        if (!this._updateContent(offset, opts)) {
            return false;
        }

        var limit = this._getBuffer().isNearingLimit(offset);
        if (limit) {
            this._fetchBuffer({
                background: true,
                nearing: limit,
                offset: offset
            });
        }

        return true;
    },

    // opts = (object) The following parameters:
    // One of the following is REQUIRED:
    //   offset: (integer) Value of offset
    //   search: (object) List of search keys/values
    //
    // OPTIONAL:
    //   background: (boolean) Do fetch in background
    //   callback: (function) A callback to run when the request is complete
    //   initial: (boolean) Is this the initial access to this view?
    //   nearing: (string) TODO [only used w/offset]
    //   params: (object) Parameters to add to outgoing URL
    //   purge: (boolean) If true, purge the current rowlist and rebuild.
    //          Attempts to reuse the current data cache.
    //   view: (string) The view to retrieve. Defaults to current view.
    _fetchBuffer: function(opts)
    {
        if (this.isbusy) {
            this._fetchBuffer.bind(this, opts).defer();
        } else {
            this.isbusy = true;
            try {
                this._fetchBufferDo(opts);
            } catch (e) {
                this.isbusy = false;
                throw e;
            }
            this.isbusy = false;
        }
    },

    _fetchBufferDo: function(opts)
    {
        var llist, lrows, rlist, tmp, value,
            view = (opts.view || this.view),
            b = this._getBuffer(view),
            params = $H(opts.params),
            r_id = this.request_num++;

        // Only fire fetch event if we are loading in foreground.
        if (!opts.background) {
            this.opts.container.fire('ViewPort:fetch', view);
        }

        params.update({ requestid: r_id });

        // Determine if we are querying via offset or a search query
        if (opts.search || opts.initial || opts.purge) {
            if (opts.search) {
                value = opts.search;
                params.set('search', Object.toJSON(value));
            }

            if (opts.initial) {
                params.set('initial', 1);
            }

            if (opts.purge) {
                b.resetRowlist();
            }

            tmp = this._lookbehind();

            params.update({
                after: this.bufferSize() - tmp,
                before: tmp
            });
        }

        if (!opts.search) {
            value = opts.offset + 1;

            // llist: keys - request_ids; vals - loading rownums
            llist = b.getMetaData('llist') || $H();
            lrows = llist.values().flatten();

            b.setMetaData({ req_offset: opts.offset }, true);

            /* If the current offset is part of a pending request, update
             * the offset. */
            if (lrows.size() &&
                b.sliceLoaded(value, lrows)) {
                /* One more hurdle. If we are loading in background, and now
                 * we are in foreground, we need to search for the request
                 * that contains the current rownum. For now, just use the
                 * last request. */
                if (!this.active_req && !opts.background) {
                    this.active_req = llist.keys().numericSort().last();
                }
                return;
            }

            /* This gets the list of rows needed which do not already appear
             * in the buffer. */
            tmp = this._getSliceBounds(value, opts.nearing, view);
            rlist = $A($R(tmp.start, tmp.end)).diff(b.getAllRows());

            if (!rlist.size()) {
                return;
            }

            /* Add rows to the loading list for the view. */
            rlist = rlist.diff(lrows).numericSort();
            llist.set(r_id, rlist);
            b.setMetaData({ llist: llist }, true);

            params.update({ slice: rlist.first() + ':' + rlist.last() });
        }

        if (opts.callback) {
            tmp = b.getMetaData('callback') || $H();
            tmp.set(r_id, opts.callback);
            b.setMetaData({ callback: tmp }, true);
        }

        if (!opts.background) {
            this.active_req = r_id;
            this._handleWait();
        }

        this.opts.ajax(this.addRequestParams(params, {
            noslice: true,
            view: view
        }));
    },

    // rownum = (integer) Row number
    // nearing = (string) 'bottom', 'top', null
    // view = (string) ID of view.
    _getSliceBounds: function(rownum, nearing, view)
    {
        var b_size = this.bufferSize(),
            ob = {}, trows;

        switch (nearing) {
        case 'bottom':
            ob.start = rownum + this.getPageSize();
            ob.end = ob.start + b_size;
            break;

        case 'top':
            ob.start = Math.max(rownum - b_size, 1);
            ob.end = rownum;
            break;

        default:
            ob.start = rownum - this._lookbehind();

            /* Adjust slice if it runs past edge of available rows. In this
             * case, fetching a tiny buffer isn't as useful as switching
             * the unused buffer space to the other endpoint. Always allow
             * searching past the value of total_rows, since the size of the
             * dataset may have increased. */
            trows = this.getMetaData('total_rows', view);
            if (trows) {
                ob.end = ob.start + b_size;

                if (ob.end > trows) {
                    ob.start -= ob.end - trows;
                }

                if (ob.start < 1) {
                    ob.end += 1 - ob.start;
                    ob.start = 1;
                }
            } else {
                ob.start = Math.max(ob.start, 1);
                ob.end = ob.start + b_size;
            }
            break;
        }

        return ob;
    },

    _lookbehind: function()
    {
        return parseInt((this.opts.lookbehind * 0.01) * this.bufferSize(), 10);
    },

    // args = (object) The list of parameters.
    // opts = (object) [noslice, view]
    // Returns a Hash object
    addRequestParams: function(args, opts)
    {
        args = args || {};
        opts = opts || {};

        var cid = this.getMetaData('cacheid', opts.view),
            params = $H(),
            cached, rowlist;

        params.set('view', opts.view || this.view);

        if (cid) {
            params.set('cacheid', cid);
        }

        if (!opts.noslice) {
            rowlist = this._getSliceBounds(this.currentOffset(), null, opts.view);
            params.set('slice', rowlist.start + ':' + rowlist.end);
        }

        cached = this._getBuffer(opts.view).getAllUIDs();
        if (cached.size()) {
            params.set('cache', Object.toJSON(cached));
        }

        params.update(args);

        return this.opts.onAjaxRequest
            ? $H(this.opts.onAjaxRequest(params))
            : params;
    },

    // r - (object) responseJSON returned from the server.
    parseJSONResponse: function(r)
    {
        if (r.rangelist) {
            this.select(this.createSelection('uid', r.rangelist, r.view));
            this.opts.container.fire('ViewPort:endRangeFetch', r.view);
        }

        if (!Object.isUndefined(r.cacheid)) {
            this._ajaxResponse(r);
        }
    },

    // r = (Object) viewport response object
    _ajaxResponse: function(r)
    {
        if (this.isbusy) {
            this._ajaxResponse.bind(this, r).defer();
        } else {
            this.isbusy = true;
            try {
                this._ajaxResponseDo(r);
            } catch (e) {
                this.isbusy = false;
                throw e;
            }
            this.isbusy = false;
        }
    },

    _ajaxResponseDo: function(r)
    {
        this._clearWait();

        var callback, offset, tmp,
            buffer = this._getBuffer(r.view),
            llist = buffer.getMetaData('llist') || $H();

        if (r.data_reset) {
            this.deselect(this.getSelected());
        } else if (r.disappear && r.disappear.size()) {
            this._remove(this.createSelection('uid', r.disappear, r.view));
        }

        buffer.update(Object.isArray(r.data) ? {} : r.data, Object.isArray(r.rowlist) ? {} : r.rowlist, r.metadata || {}, {
            datareset: r.data_reset,
            mdreset: r.metadata_reset,
            rowreset: r.rowlist_reset
        });

        llist.unset(r.requestid);

        tmp = {
            cacheid: r.cacheid,
            llist: llist
        };
        if (r.label) {
            tmp.label = r.label;
        }
        if (r.totalrows) {
            tmp.total_rows = r.totalrows;
        }
        buffer.setMetaData(tmp, true);

        if (r.requestid &&
            r.requestid == this.active_req) {
            delete this.active_req;
            callback = buffer.getMetaData('callback');
            offset = buffer.getMetaData('req_offset');

            if (callback && callback.get(r.requestid)) {
                callback.get(r.requestid)(r);
                callback.unset(r.requestid);
            }

            buffer.setMetaData({ callback: undefined, req_offset: undefined }, true);

            this.opts.container.fire('ViewPort:endFetch', r.view);
        }

        if (this.view == r.view) {
            this._updateContent(Object.isUndefined(r.rownum) ? (Object.isUndefined(offset) ? this.currentOffset() : offset) : Number(r.rownum) - 1, { updated: r.rowlist_reset });
        } else if (r.rownum) {
            // We loaded in the background. If rownumber information was
            // provided, we need to save this or else we will position the
            // viewport incorrectly.
            buffer.setMetaData({ offset: Number(r.rownum) - 1 }, true);
        }
    },

    // offset = (integer) Offset of row to display
    // opts = (object) TODO [background, updated, view]
    _updateContent: function(offset, opts)
    {
        offset = Math.max(0, offset);
        opts = opts || {};

        if (!this._getBuffer(opts.view).sliceLoaded(offset)) {
            opts.offset = offset;
            this._fetchBuffer(opts);
            return false;
        }

        var added = {},
            c = this.opts.content,
            page_size = this.getPageSize(),
            tmp = [],
            vr = this.visibleRows(),
            fdiv, rows;

        this.scroller.setSize(page_size, this.getMetaData('total_rows'));
        this.scrollTo(offset + 1, { noupdate: true, top: true });

        offset = this.currentOffset();
        if (this.opts.onContentOffset) {
            offset = this.opts.onContentOffset(offset);
        }

        rows = this.createSelection('rownum', $A($R(offset + 1, offset + page_size)));

        if (rows.size()) {
            fdiv = document.createDocumentFragment().appendChild(new Element('DIV'));

            rows.get('dataob').each(function(r) {
                var elt;
                if (!opts.updated && (elt = $(r.VP_domid))) {
                    tmp.push(elt);
                } else {
                    fdiv.insert({ top: this.prepareRow(r) });
                    added[r.VP_domid] = 1;
                    tmp.push(fdiv.down());
                }
            }, this);

            if (vr.size()) {
                vr.pluck('id').diff(rows.get('domid')).each($).compact().each(this.opts.content.fire.bind(this.opts.content, 'ViewPort:clear'));
            }

            c.childElements().invoke('remove');

            tmp.each(function(r) {
                c.insert(r);
                if (added[r.identify()]) {
                    this.opts.container.fire('ViewPort:add', r);
                }
            }, this);
        } else {
            vr.each(this.opts.content.fire.bind(this.opts.content, 'ViewPort:clear'));
            vr.invoke('remove');
            c.update(this.empty_msg.clone(true).insert(Object.isFunction(this.opts.empty_msg) ? this.opts.empty_msg() : this.opts.empty_msg));
        }

        this.scroller.updateDisplay();
        this.opts.container.fire('ViewPort:contentComplete');

        return true;
    },

    prepareRow: function(row)
    {
        var r = Object.clone(row);

        r.VP_bg = this.getSelected().contains('uid', r.VP_id)
            ? [ 'vpRowSelected' ]
            : [];

        return this.opts.onContent(r, this.pane_mode);
    },

    updateRow: function(row)
    {
        var d = $(row.VP_domid);
        if (d) {
            this.opts.container.fire('ViewPort:clear', d);
            d.replace(this.prepareRow(row));
            this.opts.container.fire('ViewPort:add', $(row.VP_domid));
        }
    },

    _handleWait: function(call)
    {
        this._clearWait();

        // Server did not respond in defined amount of time.  Alert the
        // callback function and set the next timeout.
        if (call) {
            this.opts.container.fire('ViewPort:wait', this.view);
        }

        // Call wait handler every x seconds
        if (this.opts.viewport_wait) {
            this.waitHandler = this._handleWait.bind(this, true).delay(this.opts.viewport_wait);
        }
    },

    _clearWait: function()
    {
        if (this.waitHandler) {
            clearTimeout(this.waitHandler);
            delete this.waitHandler;
        }
    },

    visibleRows: function()
    {
        return this.opts.content.select('DIV.vpRow');
    },

    getMetaData: function(id, view)
    {
        return this._getBuffer(view).getMetaData(id);
    },

    setMetaData: function(vals, view)
    {
        this._getBuffer(view).setMetaData(vals, false);
    },

    _getBuffer: function(view, create)
    {
        view = view || this.view;

        return (!create && this.views[view])
            ? this.views[view]
            : new ViewPort_Buffer(this, view);
    },

    currentOffset: function()
    {
        return this.scroller.currentOffset();
    },

    // return: (object) The current viewable range of the viewport.
    //         first: Top-most row offset
    //         last: Bottom-most row offset
    currentViewableRange: function()
    {
        var offset = this.currentOffset();
        return {
            first: offset + 1,
            last: Math.min(offset + this.getPageSize(), this.getMetaData('total_rows'))
        };
    },

    _getLineHeight: function()
    {
        var mode = this.pane_mode || 'horiz';

        if (!this.split_pane[mode].lh) {
            // To avoid hardcoding the line height, create a temporary row to
            // figure out what the CSS says.
            var d = new Element('DIV', { className: this.opts.list_class }).insert(this.prepareRow({ VP_domid: null }, mode)).hide();
            $(document.body).insert(d);
            this.split_pane[mode].lh = d.getHeight();
            d.remove();
        }

        return this.split_pane[mode].lh;
    },

    // (type) = (string) [null (DEFAULT), 'current', 'default', 'max']
    // return: (integer) Number of rows in current view.
    getPageSize: function(type)
    {
        switch (type) {
        case 'current':
            return Math.min(this.page_size, this.getMetaData('total_rows'));

        case 'default':
            return (this.pane_mode == 'vert')
                ? this.getPageSize('max')
                : Math.max(parseInt(this.getPageSize('max') * 0.45, 10), 5);

        case 'max':
            return parseInt((document.viewport.getHeight() - this.opts.content.viewportOffset()[1]) / this._getLineHeight(), 10);

        default:
            return this.page_size;
        }
    },

    bufferSize: function()
    {
        // Buffer size must be at least the maximum page size.
        return Math.round(Math.max(this.getPageSize('max') + 1, this.opts.buffer_pages * this.getPageSize()));
    },

    limitTolerance: function()
    {
        return Math.round(this.bufferSize() * (this.opts.limit_factor / 100));
    },

    // mode = (string) Either 'horiz', 'vert', or empty.
    showSplitPane: function(mode)
    {
        this.pane_mode = mode;
        this.onResize(true);
    },

    // Return the vertical width of the row listing if splitbar is enabled
    // and is in vertical mode.
    getVertWidth: function()
    {
        return (this.pane_mode == 'vert')
            ? this.opts.content.getWidth()
            : 0;
    },

    _initSplitBar: function()
    {
        var sp = this.split_pane;

        if (sp.currbar) {
            sp.currbar.hide();
        }

        sp.curr = this.pane_mode;

        if (sp[this.pane_mode].bar) {
            sp.currbar = sp[this.pane_mode].bar.show();
            return;
        }

        sp.currbar = sp[this.pane_mode].bar = new Element('DIV', { className: this.opts.split_bar_class[this.pane_mode] })
            .insert(new Element('DIV', { className: this.opts.split_bar_handle_class[this.pane_mode] }));

        if (!this.opts.pane_data.descendantOf(this.opts.container)) {
            this.opts.container.insert(this.opts.pane_data.remove());
        }

        this.opts.pane_data.insert({ before: sp.currbar });

        switch (this.pane_mode) {
        case 'horiz':
            new Drag(sp.currbar, {
                constraint: 'vertical',
                ghosting: true,
                nodrop: true,
                snap: function(x, y, elt) {
                    var sp = this.split_pane,
                        l = parseInt((y - sp.pos) / sp.lh);
                    if (l < 1) {
                        l = 1;
                    } else if (l > sp.max) {
                        l = sp.max;
                    }
                    sp.lines = l;
                    return [ x, sp.pos + (l * sp.lh) ];
                }.bind(this)
            });
            break;

        case 'vert':
            new Drag(sp.currbar.setStyle({ float: 'left' }), {
                constraint: 'horizontal',
                ghosting: true,
                nodrop: true,
                snapToParent: true
            });
            break;
        }

        if (!sp.init) {
            document.observe('DragDrop2:end', this._onDragEnd.bindAsEventListener(this));
            document.observe('DragDrop2:start', this._onDragStart.bindAsEventListener(this));
            document.observe('dblclick', this._onDragDblClick.bindAsEventListener(this));
            sp.init = true;
        }
    },

    _onDragStart: function(e)
    {
        var sp = this.split_pane;

        if (e.element() != sp.currbar) {
            return;
        }

        if (this.pane_mode == 'horiz') {
            // Cache these values since we will be using them multiple
            // times in snap().
            sp.lh = this._getLineHeight();
            sp.lines = this.page_size;
            sp.max = this.getPageSize('max');
            sp.orig = this.page_size;
            sp.pos = this.opts.content.viewportOffset()[1];
        }

        this.opts.container.fire('ViewPort:splitBarStart', this.pane_mode);
    },

    _onDragEnd: function(e)
    {
        var change, drag,
            sp = this.split_pane;

        if (e.element() != sp.currbar) {
            return;
        }

        switch (this.pane_mode) {
        case 'horiz':
            this.onResize(true, sp.lines);
            change = (sp.orig != sp.lines);
            break;

        case 'vert':
            drag = DragDrop.Drags.getDrag(e.element());
            sp.vert.width = drag.lastCoord[0] - this.opts.list_container.viewportOffset()[0];
            this.opts.content.setStyle({ width: sp.vert.width + 'px' });
            change = drag.wasDragged;
            break;
        }

        if (change) {
            this.opts.container.fire('ViewPort:splitBarChange', this.pane_mode);
        }
        this.opts.container.fire('ViewPort:splitBarEnd', this.pane_mode);
    },

    _onDragDblClick: function(e)
    {
        if (e.element() != this.split_pane.currbar) {
            return;
        }

        var change, old_size = this.page_size;

        switch (this.pane_mode) {
        case 'horiz':
            this.onResize(true, this.getPageSize('default'));
            change = (old_size != this.page_size);
            break;

        case 'vert':
            this.opts.content.setStyle({ width: parseInt(this.opts.container.clientWidth * 0.45, 10) + 'px' });
            change = true;
        }

        if (change) {
            this.opts.container.fire('ViewPort:splitBarChange', this.pane_mode);
        }
    },

    getAllRows: function(view)
    {
        var buffer = this._getBuffer(view);
        return buffer
            ? buffer.getAllRows()
            : [];
    },

    createSelection: function(format, data, view)
    {
        var buffer = this._getBuffer(view);
        return buffer
            ? new ViewPort_Selection(buffer, format, data)
            : new ViewPort_Selection(this._getBuffer(this.view));
    },

    // Creates a selection object comprising all entries contained in the
    // buffer.
    createSelectionBuffer: function(view)
    {
        return this.createSelection('rownum', this.getAllRows(view), view);
    },

    getSelection: function(view)
    {
        var buffer = this._getBuffer(view);
        return this.createSelection('uid', buffer ? buffer.getSelected().get('uid') : [], view);
    },

    // vs = (ViewPort_Selection | array) A ViewPort_Selection object -or- an
    //       array of row numbers.
    // opts = (object) [add, search]
    select: function(vs, opts)
    {
        opts = opts || {};

        var b = this._getBuffer(),
            sel, slice;

        if (Object.isArray(vs)) {
            slice = this.createSelection('rownum', vs);
            if (vs.size() != slice.size()) {
                this.opts.container.fire('ViewPort:fetch', this.view);
                return this.opts.ajax(this.addRequestParams({
                    rangeslice: 1,
                    slice: vs.min() + ':' + vs.size()
                }));
            }
            vs = slice;
        }

        if (opts.search) {
            return this._fetchBuffer({
                callback: function(r) {
                    if (r.rownum) {
                        this.select(this.createSelection('rownum', [ r.rownum ]), { add: opts.add });
                    }
                }.bind(this),
                search: opts.search
            });
        }

        if (!opts.add) {
            sel = this.getSelected();
            b.deselect(sel, true);
            sel.get('div').invoke('removeClassName', 'vpRowSelected');
        }
        b.select(vs);
        vs.get('div').invoke('addClassName', 'vpRowSelected');
        this.opts.container.fire('ViewPort:select', { opts: opts, vs: vs });
    },

    // vs = (ViewPort_Selection) A ViewPort_Selection object.
    // opts = (object) TODO [clearall]
    deselect: function(vs, opts)
    {
        opts = opts || {};

        if (vs.size() &&
            this._getBuffer().deselect(vs, opts && opts.clearall)) {
            vs.get('div').invoke('removeClassName', 'vpRowSelected');
            this.opts.container.fire('ViewPort:deselect', { opts: opts, vs: vs });
        }
    },

    getSelected: function()
    {
        return Object.clone(this._getBuffer().getSelected());
    }

}),

ViewPort_Scroller = Class.create({
    // Variables initialized to undefined:
    //   noupdate, scrollDiv, scrollbar, vertscroll, vp

    initialize: function(vp)
    {
        this.vp = vp;
    },

    _createScrollBar: function()
    {
        if (this.scrollDiv) {
            return;
        }

        var c = this.vp.opts.content;

        // Create the outer div.
        this.scrollDiv = new Element('DIV', { className: 'vpScroll' }).setStyle({ float: 'left', overflow: 'hidden' }).hide();
        c.insert({ after: this.scrollDiv });

        this.scrollDiv.observe('Slider2:change', this._onScroll.bind(this));
        if (this.vp.opts.onSlide) {
            this.scrollDiv.observe('Slider2:slide', this.vp.opts.onSlide);
        }

        // Create scrollbar object.
        this.scrollbar = new Slider2(this.scrollDiv, {
            buttonclass: { up: 'vpScrollUp', down: 'vpScrollDown' },
            cursorclass: 'vpScrollCursor',
            pagesize: this.vp.getPageSize(),
            totalsize: this.vp.getMetaData('total_rows')
       });

        // Mouse wheel handler.
        c.observe(Prototype.Browser.Gecko ? 'DOMMouseScroll' : 'mousewheel', function(e) {
            var move_num = Math.min(this.vp.getPageSize(), 3);
            this.moveScroll(this.currentOffset() + ((e.wheelDelta >= 0 || e.detail < 0) ? (-1 * move_num) : move_num));
            /* Mozilla bug https://bugzilla.mozilla.org/show_bug.cgi?id=502818
             * Need to stop or else multiple scroll events may be fired. We
             * lose the ability to have the mousescroll bubble up, but that is
             * more desirable than having the wrong scrolling behavior. */
            if (Prototype.Browser.Gecko && !e.stop) {
                Event.stop(e);
            }
        }.bindAsEventListener(this));
    },

    setSize: function(viewsize, totalsize)
    {
        this._createScrollBar();
        this.scrollbar.setHandleLength(viewsize, totalsize);
    },

    updateDisplay: function()
    {
        var c = this.vp.opts.content,
            vs = false;

        if (this.scrollbar.needScroll()) {
            switch (this.vp.pane_mode) {
            case 'vert':
                this.scrollDiv.setStyle({ marginLeft: 0 });
                if (!this.vertscroll) {
                    c.setStyle({ width: (c.clientWidth - this.scrollDiv.getWidth()) + 'px' });
                }
                vs = true;
                break;

            case 'horiz':
            default:
                this.scrollDiv.setStyle({ marginLeft: '-' + this.scrollDiv.getWidth() + 'px' });
                break;
            }

            this.scrollDiv.setStyle({ height: c.clientHeight + 'px' });
        } else if ((this.vp.pane_mode == 'vert') && this.vertscroll) {
            c.setStyle({ width: (c.clientWidth + this.scrollDiv.getWidth()) + 'px' });
        }

        this.vertscroll = vs;
        this.scrollbar.updateHandleLength();
    },

    clear: function()
    {
        this.setSize(0, 0);
        this.scrollbar.updateHandleLength();
    },

    // offset = (integer) Offset to move the scrollbar to
    moveScroll: function(offset)
    {
        this._createScrollBar();
        this.scrollbar.setScrollPosition(offset);
    },

    _onScroll: function()
    {
        if (!this.noupdate) {
            this.vp.requestContentRefresh(this.currentOffset());
        }
    },

    currentOffset: function()
    {
        return this.scrollbar ? this.scrollbar.getValue() : 0;
    }

}),

/* Note: recognize the difference between offset (current location in the
 * viewport - starts at 0) with start parameters (the row numbers - starts
 * at 1). */
ViewPort_Buffer = Class.create({

    initialize: function(vp, view)
    {
        this.vp = vp;
        this.view = view;
        this.clear();
    },

    getView: function()
    {
        return this.view;
    },

    // d = (object) Data
    // l = (object) Rowlist
    // md = (object) User defined metadata
    // opts = (object) TODO [datareset, mdreset, rowreset]
    update: function(d, l, md, opts)
    {
        d = $H(d);
        l = $H(l);
        opts = opts || {};

        if (!opts.datareset) {
            this.data.update(d);
        } else {
            this.data = d;
        }

        if (opts.rowreset || opts.datareset) {
            this.resetRowlist();
        }

        l.each(function(o) {
            this.data.get(o.key).VP_rownum = o.value;
            this.rowlist.set(o.value, o.key);
        }, this);

        if (opts.mdreset) {
            this.usermdata = $H();
        }

        $H(md).each(function(pair) {
            if (Object.isString(pair.value) ||
                Object.isNumber(pair.value) ||
                Object.isArray(pair.value)) {
                this.usermdata.set(pair.key, pair.value);
            } else {
                var val = this.usermdata.get(pair.key);
                if (val) {
                    val.update($H(pair.value));
                } else {
                    this.usermdata.set(pair.key, $H(pair.value));
                }
            }
        }, this);
    },

    // offset = (integer) Offset of the beginning of the slice.
    // rows = (array) Additional rows to include in the search.
    sliceLoaded: function(offset, rows)
    {
        var range, tr = this.getMetaData('total_rows');

        // Undefined here indicates we have never sent a previous buffer
        // request.
        if (Object.isUndefined(tr)) {
            return false;
        }

        range = $A($R(offset + 1, Math.min(offset + this.vp.getPageSize() - 1, tr)));

        return rows
            ? (range.diff(this.rowlist.keys().concat(rows)).size() == 0)
            : !this._rangeCheck(range);
    },

    isNearingLimit: function(offset)
    {
        if (this.rowlist.size() != this.getMetaData('total_rows')) {
            if (offset != 0 &&
                this._rangeCheck($A($R(Math.max(offset + 1 - this.vp.limitTolerance(), 1), offset)))) {
                return 'top';
            } else if (this._rangeCheck($A($R(offset + 1, Math.min(offset + this.vp.limitTolerance() + this.vp.getPageSize() - 1, this.getMetaData('total_rows')))).reverse())) {
                // Search for missing rows in reverse order since in normal
                // usage (sequential scrolling through the row list) rows are
                // more likely to be missing at furthest from the current
                // view.
                return 'bottom';
            }
        }
    },

    _rangeCheck: function(range)
    {
        return !range.all(this.rowlist.get.bind(this.rowlist));
    },

    getData: function(uids)
    {
        return uids.collect(function(u) {
            var e = this.data.get(u);
            if (!Object.isUndefined(e)) {
                // We can directly write the rownum to the original object
                // since we will always rewrite when creating rows.
                if (!e.VP_domid) {
                    e.VP_domid = 'VProw_' + (++this.vp.id);
                }
                e.VP_id = u;
                return e;
            }
        }, this).compact();
    },

    getAllUIDs: function()
    {
        return this.rowlist.values();
    },

    getAllRows: function()
    {
        return this.rowlist.keys();
    },

    domidsToUIDs: function(ids)
    {
        var i = 0,
            idsize = ids.size(),
            uids = [];

        this.data.each(function(d) {
            if (d.value.VP_domid && ids.include(d.value.VP_domid)) {
                uids.push(d.key);
                if (++i == idsize) {
                    throw $break;
                }
            }
        });

        return uids;
    },

    rowsToUIDs: function(rows)
    {
        return rows.collect(this.rowlist.get.bind(this.rowlist)).compact();
    },

    UIDsToRows: function(uids)
    {
        return rows.collect(this.rowlist.index.bind(this.rowlist)).compact();
    },

    // vs = (ViewPort_Selection) TODO
    select: function(vs)
    {
        this.selected.add('uid', vs.get('uid'));
    },

    // vs = (ViewPort_Selection) TODO
    // clearall = (boolean) Clear all entries?
    deselect: function(vs, clearall)
    {
        var size = this.selected.size();

        if (clearall) {
            this.selected.clear();
        } else {
            this.selected.remove('uid', vs.get('uid'));
        }
        return size != this.selected.size();
    },

    getSelected: function()
    {
        return this.selected;
    },

    // rownums = (array) Array of row numbers to remove.
    remove: function(rownums)
    {
        var minrow = rownums.min(),
            rowsize = this.rowlist.size(),
            rowsubtract = 0,
            newsize = rowsize - rownums.size();

        return this.rowlist.keys().each(function(n) {
            n = parseInt(n, 10);
            if (n >= minrow) {
                var id = this.rowlist.get(n), r;
                if (rownums.include(n)) {
                    this.data.unset(id);
                    rowsubtract++;
                } else if (rowsubtract) {
                    r = n - rowsubtract;
                    this.rowlist.set(r, id);
                    this.data.get(id).VP_rownum = r;
                }
                if (n > newsize) {
                    this.rowlist.unset(n);
                }
            }
        }, this);
    },

    removeData: function(uids)
    {
        uids.each(this.data.unset.bind(this.data));
    },

    resetRowlist: function()
    {
        this.rowlist = $H();
        this.setMetaData({ total_rows: 0 }, true);
    },

    clear: function()
    {
        this.data = $H();
        this.mdata = $H({ total_rows: 0 });
        this.selected = new ViewPort_Selection(this);
        this.usermdata = $H();
        this.resetRowlist();
    },

    getMetaData: function(id)
    {
        var data = this.mdata.get(id);

        return Object.isUndefined(data)
            ? this.usermdata.get(id)
            : data;
    },

    setMetaData: function(vals, priv)
    {
        if (priv) {
            this.mdata.update(vals);
        } else {
            this.usermdata.update(vals);
        }
    },

    debug: function()
    {
        return Object.toJSON({
            data: this.data,
            mdata: this.mdata,
            rowlist: this.rowlist,
            selected: this.selected.get('uid')
        });
    }

}),

ViewPort_Selection = Class.create({

    // Define property to aid in object detection
    viewport_selection: true,

    // Formats:
    //     'dataob' = Data objects
    //     'div' = DOM DIVs
    //     'domid' = DOM IDs
    //     'rownum' = Row numbers
    //     'uid' = Unique IDs
    initialize: function(buffer, format, data)
    {
        this.buffer = buffer;
        this.clear();
        if (!Object.isUndefined(format)) {
            this.add(format, data);
        }
    },

    add: function(format, d)
    {
        var c = this._convert(format, d);
        this.data = this.data.size() ? this.data.concat(c).uniq() : c;
    },

    remove: function(format, d)
    {
        this.data = this.data.diff(this._convert(format, d));
    },

    _convert: function(format, d)
    {
        d = Object.isArray(d) ? d : [ d ];

        // Data is stored internally as UIDs.
        switch (format) {
        case 'dataob':
            return d.pluck('VP_id');

        case 'div':
            // ID here is the DOM ID of the element object.
            d = d.pluck('id');
            // Fall-through

        case 'domid':
            return this.buffer.domidsToUIDs(d);

        case 'rownum':
            return this.buffer.rowsToUIDs(d);

        case 'uid':
            return d;
        }
    },

    clear: function()
    {
        this.data = [];
    },

    get: function(format)
    {
        format = Object.isUndefined(format) ? 'uid' : format;
        if (format == 'uid') {
            return this.data;
        }
        var d = this.buffer.getData(this.data);

        switch (format) {
        case 'dataob':
            return d;

        case 'div':
            return d.pluck('VP_domid').collect(function(e) { return $(e); }).compact();

        case 'domid':
            return d.pluck('VP_domid');

        case 'rownum':
            return d.pluck('VP_rownum');
        }
    },

    contains: function(format, d)
    {
        return this.data.include(this._convert(format, d).first());
    },

    // params = (Object) Key is search key, value is object -> key of object
    // must be the following:
    //   equal - Matches any value contained in the query array.
    //   include - Matches if this value is contained within the array.
    //   notequal - Matches any value not contained in the query array.
    //   notinclude - Matches if this value is not contained within the array.
    //   regex - Matches the RegExp contained in the query.
    search: function(params)
    {
        return new ViewPort_Selection(this.buffer, 'uid', this.get('dataob').findAll(function(i) {
            // i = data object
            return $H(params).all(function(k) {
                // k.key = search key; k.value = search criteria
                return $H(k.value).all(function(s) {
                    // Normalize dynamically created values. We know the
                    // required types for these values, and certain browsers
                    // do strict type-checking (e.g. Chrome).
                    switch (k.key) {
                    case 'VP_domid':
                    case 'VP_id':
                        s.value = s.value.invoke('toString');
                        break;

                    case 'VP_rownum':
                        s.value = s.value.collect(function(i) {
                            var val = parseInt(i, 10);
                            return isNaN(val) ? null : val;
                        }).compact();
                        break;
                    }

                    // s.key = search type; s.value = search query
                    switch (s.key) {
                    case 'equal':
                    case 'notequal':
                        var r = i[k.key] && s.value.include(i[k.key]);
                        return (s.key == 'equal') ? r : !r;

                    case 'include':
                    case 'notinclude':
                        var r = i[k.key] && Object.isArray(i[k.key]) && i[k.key].include(s.value);
                        return (s.key == 'include') ? r : !r;

                    case 'regex':
                        return i[k.key].match(s.value);
                    }
                });
            });
        }).pluck('VP_id'));
    },

    size: function()
    {
        return this.data.size();
    },

    set: function(vals)
    {
        this.get('dataob').each(function(d) {
            $H(vals).each(function(v) {
                d[v.key] = v.value;
            });
        });
    },

    getBuffer: function()
    {
        return this.buffer;
    }

});

/** Utility Functions **/
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
        return this.collect(Number).sort(function(a, b) {
            return (a > b) ? 1 : ((a < b) ? -1 : 0);
        });
    }
});
