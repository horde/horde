/**
 * ViewPort.js - Code to create a viewport window in a web browser.
 *
 * Usage:
 * ======
 * var viewport = new ViewPort({ options });
 *
 * Required options:
 * -----------------
 * ajax_url: (string) TODO
 *           Response: 'ViewPort'
 * content: (Element/string) A DOM element/ID of the container to hold the
 *          viewport rows.
 * template: (string) TODO DIV with 'vpData'
 *                    Class: 'vpRow' 'vpRowSelected'
 *
 * Optional options:
 * -----------------
 * ajax_opts: (object) TODO
 * buffer_pages: (integer) The number of viewable pages to send to the browser
 *               per server access when listing rows.
 * empty_msg: (string) A string to display when the view is empty. Inserted in
 *            a SPAN element with class 'vpEmpty'.
 * error_msg: (string) A string to display when an error is encountered.
 *            Inserted in a SPAN element with class 'vpError'.
 * limit_factor: (integer) When browsing through a list, if a user comes
 *               within this percentage of the end of the current cached
 *               viewport, send a background request to the server to retrieve
 *               the next slice.
 * page_size: (integer) Default page size to view on load.
 * show_split_pane: (boolean) Show the split pane on load?
 * split_bar: (Element/string) A DOM element/ID of the element used to display
 *            the split bar.
 * split_pane: (Element/string) A DOM element/ID of the container to hold
 *             the split pane info.
 * wait: (integer) How long, in seconds, to wait before displaying an
 *       informational message to users that the message list is still being
 *       built.
 *
 * Callbacks:
 * ----------
 * onAjaxRequest
 * onAjaxResponse
 * onCachedList
 * onCacheUpdate
 * onClear
 * onContent
 * onContentComplete
 * onDeselect
 * onEndFetch
 * onFail
 * onFetch
 * onSelect
 * onSlide
 * onSplitBarChange
 * onWait
 *
 * Outgoing AJAX request has the following params (TODO):
 * ------------------------------------------------------
 * For a row request:
 *   request_id: (integer) TODO
 *   rownum: (integer) TODO
 *   slice: (string)
 *
 * For a search request:
 *   request_id: (integer) TODO
 *   search: (JSON object)
 *   search_after: (integer)
 *   search_before: (integer)
 *
 * For a rangeslice request:
 *   rangeslice: (boolean)
 *   slice: (string)
 *
 * Incoming AJAX response has the following params (TODO):
 * -------------------------------------------------------
 * cacheid
 * data
 * label
 * metadata (optional)
 * request_id
 * rangelist: TODO
 * reset (optional) - If set, purges all cached data
 * resetmd (optional) - If set, purges all user metadata
 * rowlist
 * rownum (optional)
 * totalrows
 * update (optional) - If set, update the rowlist instead of overwriting it.
 * view
 *
 * Requires prototypejs 1.6+, DimpSlider.js, scriptaculous 1.8+ (effects.js
 * only), and Horde's dragdrop2.js.
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

/**
 * ViewPort
 */
var ViewPort = Class.create({

    initialize: function(opts)
    {
        this.opts = Object.extend({
            buffer_pages: 5,
            limit_factor: 35
        }, opts);

        this.opts.content = $(opts.content);
        this.opts.split_pane = $(opts.split_pane);

        this.scroller = new ViewPort_Scroller(this);
        this.template = new Template(opts.template);

        this.current_req_lookup = {};
        this.current_req = {};
        this.fetch_hash = {};
        this.views = {};

        this.split_bar_loc = opts.page_size;
        this.show_split_pane = opts.show_split_pane;

        this.isbusy = this.line_height = this.page_size = this.split_bar = null;
        this.request_num = 1;

        // Init empty string now.
        this.empty_msg = new Element('SPAN', { className: 'vpEmpty' }).insert(opts.empty_msg);

        // Set up AJAX response function.
        this.ajax_response = this.opts.onAjaxResponse || this._ajaxRequestComplete.bind(this);

        Event.observe(window, 'resize', this.onResize.bind(this));
    },

    // view = ID of view.
    // search = (object) Search parameters
    // background = Load view in background?
    loadView: function(view, search, background)
    {
        var buffer, curr, init = true, opts = {}, ps;

        this._clearWait();

        // Need a page size before we can continue - this is what determines
        // the slice size to request from the server.
        if (this.page_size === null) {
            ps = this.getPageSize(this.show_split_pane ? 'default' : 'max');
            if (isNaN(ps)) {
                return this.loadView.bind(this, view, search, background).defer();
            }
            this.page_size = ps;
        }

        if (this.view) {
            if (!background) {
                // Need to store current buffer to save current offset
                buffer = this._getBuffer();
                buffer.setMetaData({ offset: this.currentOffset() }, true);
                this.views[this.view] = buffer;
            }
            init = false;
        }

        if (background) {
            opts = { background: true, view: view };
        } else {
            if (!this.view) {
                this.onResize(true);
            }
            this.view = view;
        }

        if (curr = this.views[view]) {
            this._updateContent(curr.getMetaData('offset') || 0, opts);
            if (!background) {
                this._ajaxRequest({ checkcache: 1, rownum: this.currentOffset() + 1 });
            }
            return;
        }

        if (!init) {
            if (this.opts.onClear) {
                this.opts.onClear(this.visibleRows());
            }
            this.opts.content.update();
            this.scroller.clear();
        }

        this.views[view] = buffer = this._getBuffer(view, true);

        if (search) {
            opts.search = search;
        } else {
            opts.offset = 0;
        }

        this._fetchBuffer(opts);
    },

    // view = ID of view
    deleteView: function(view)
    {
        delete this.views[view];
    },

    // rownum = (integer) Row number
    // opts = (Object) [noupdate, top] TODO
    scrollTo: function(rownum, opts)
    {
        var s = this.scroller;
        opts = opts || {};

        s.noupdate = opts.noupdate;

        switch (this.isVisible(rownum)) {
        case -1:
            s.moveScroll(rownum - 1);
            break;

        case 0:
            if (opts.top) {
                s.moveScroll(rownum - 1);
            }
            break;

        case 1:
            s.moveScroll(Math.min(rownum, this.getMetaData('total_rows') - this.getPageSize() + 1));
            break;
        }

        s.noupdate = false;
    },

    // rownum = Row number
    isVisible: function(rownum)
    {
        var offset = this.currentOffset();
        return (rownum < offset + 1)
            ? -1
            : ((rownum > (offset + this.getPageSize('current'))) ? 1 : 0);
    },

    // params = TODO
    reload: function(params)
    {
        this._fetchBuffer({
            offset: this.currentOffset(),
            params: params,
            purge: true
        });
    },

    // vs = (Viewport_Selection) A Viewport_Selection object.
    // opts = (object) TODO [cacheid, noupdate, view]
    remove: function(vs, opts)
    {
        if (!vs.size()) {
            return;
        }

        if (this.isbusy) {
            this.remove.bind(this, vs, opts).defer();
            return;
        }

        this.isbusy = true;
        opts = opts || {};

        var args,
            i = 0,
            visible = vs.get('div'),
            vsize = visible.size();

        this.deselect(vs);

        if (opts.cacheid) {
            this._getBuffer(opts.view).setMetaData({ cacheid: opts.cacheid }, true);
        }

        // If we have visible elements to remove, only call refresh after
        // the last effect has finished.
        if (vsize) {
            // Set 'to' to a value slightly above 0 to prevent Effect.Fade
            // from auto hiding.  Hiding is unnecessary, since we will be
            // removing from the document shortly.
            args = { duration: 0.25, to: 0.01 };
            visible.each(function(v) {
                if (++i == vsize) {
                    args.afterFinish = this._removeids.bind(this, vs, opts);
                }
                Effect.Fade(v, args);
            }, this);
        } else {
            this._removeids(vs, opts);
        }
    },

    // vs = (Viewport_Selection) A Viewport_Selection object.
    // opts = (object) TODO [noupdate, view]
    _removeids: function(vs, opts)
    {
        this._getBuffer(opts.view).setMetaData({ total_rows: this.getMetaData('total_rows', opts.view) - vs.size() }, true);

        if (this.opts.onClear) {
            this.opts.onClear(vs.get('div').compact());
        }

        this._getBuffer().remove(vs.get('rownum'));

        if (this.opts.onCacheUpdate) {
            this.opts.onCacheUpdate(opts.view || this.view);
        }

        if (!opts.noupdate) {
            this.requestContentRefresh(this.currentOffset());
        }

        this.isbusy = false;
    },

    // nowait = (boolean) TODO
    onResize: function(nowait)
    {
        if (!this.opts.content.visible()) {
            return;
        }

        if (this.resizefunc) {
            clearTimeout(this.resizefunc);
        }

        if (nowait) {
            this._onResize();
        } else {
            this.resizefunc = this._onResize.bind(this).delay(0.1);
        }
    },

    _onResize: function()
    {
        // This is needed for IE 6 - or else horizontal scrolling can occur.
        if (!this.opts.content.offsetHeight) {
            return this._onResize.bind(this).defer();
        }

        var diff, h, setpane,
            c = $(this.opts.content),
            de = document.documentElement,
            lh = this._getLineHeight();

        // Get split pane dimensions
        if (this.opts.split_pane) {
            if (this.show_split_pane) {
                if (!this.opts.split_pane.visible()) {
                    this._initSplitBar();
                    if (this.split_bar_loc &&
                        this.split_bar_loc > 0) {
                        this.split_bar_loc = this.page_size = Math.min(this.split_bar_loc, this.getPageSize('splitmax'));
                    } else {
                        this.page_size = this.getPageSize('default');
                    }
                }
                setpane = true;
            } else if (this.opts.split_pane.visible()) {
                this.split_bar_loc = this.page_size;
                [ this.opts.split_pane, this.split_bar ].invoke('hide');
            }
        }

        if (!setpane) {
            this.page_size = this.getPageSize('max');
        }

        // Do some magic to ensure we never cause a horizontal scroll.
        h = lh * this.page_size;
        c.setStyle({ height: h + 'px' });
        if (setpane) {
            this.opts.split_pane.setStyle({ height: (this._getMaxHeight() - h - lh) + 'px' }).show();
            this.split_bar.show();
        } else if (diff = de.scrollHeight - de.clientHeight) {
            c.setStyle({ height: (lh * (this.page_size - 1)) + 'px' });
        }

        this.scroller.onResize();
    },

    // offset = (integer) TODO
    requestContentRefresh: function(offset)
    {
        if (!this._updateContent(offset)) {
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
    //   nearing: (string) TODO [only used w/offset]
    //   params: (object) Parameters to add to outgoing URL
    //   view: (string) The view to retrieve. Defaults to current view.
    _fetchBuffer: function(opts)
    {
        if (this.isbusy) {
            return this._fetchBuffer.bind(this, opts).defer();
        }

        this.isbusy = true;

        // Only call onFetch() if we are loading in foreground.
        if (!opts.background && this.opts.onFetch) {
            this.opts.onFetch();
        }

        var view = (opts.view || this.view),
            allrows,
            b = this._getBuffer(view),
            cr,
            params = $H(opts.params),
            request_id,
            request_string,
            request_old,
            rlist,
            tmp,
            type,
            value,
            viewable;

        // If asking for an explicit purge, add to the request.
        if (opts.purge) {
            params.set('purge', true);
        }

        // Determine if we are querying via offset or a search query
        if (opts.search) {
            type = 'search';
            value = opts.search;
            tmp = this._lookbehind(view);

            params.update({
                search_after: b.bufferSize() - tmp,
                search_before: tmp
            });
        } else {
            type = 'rownum';
            value = opts.offset + 1;

            // This gets the list of rows needed which do not already appear
            // in the buffer.
            allrows = b.getAllRows();
            tmp = opts.rowlist || this._getSliceBounds(value, opts.nearing, view);
            rlist = $A($R(tmp.start, tmp.end)).diff(allrows);

            if (!opts.purge && !rlist.size()) {
                this.isbusy = false;
                return;
            }

            params.update({ slice: rlist.first() + ':' + rlist.last() });
        }
        params.set(type, Object.toJSON(value));

        // Generate a unique request ID value based on the search params.
        // Since javascript does not have a native hashing function, use a
        // local lookup table instead.
        request_string = [ view, type, value ].toJSON();
        request_id = this.fetch_hash[request_string];

        // If we have a current request pending in the current view, figure
        // out if we need to send a new request
        cr = this.current_req[view];
        if (cr) {
            if (request_id && cr.get(request_id)) {
                // Check for repeat request.  We technically should never
                // reach here but if we do, make sure we don't go into an
                // infinite loop.
                if (++cr.get(request_id).count == 4) {
                    this._displayFetchError();
                    this._removeRequest(view, request_id);
                    this.isbusy = false;
                    return;
                }
            } else if (type == 'rownum') {
                // Check for message list requests that are requesting
                // (essentially) the same message slice - such as two
                // scroll down requests sent in quick succession.  If the
                // original request contains the viewable slice needed by the
                // second request, ignore the later request and just
                // reposition the viewport on display.
                viewable = $A($R(value, value + this.getPageSize())).diff(allrows);
                if (!viewable.size()) {
                    this.isbusy = false;
                    return;
                }
                request_old = cr.keys().numericSort().find(function(k) {
                    var r = cr.get(k).rlist;
                    viewable = viewable.diff(r);
                    if (!viewable.size()) {
                        return true;
                    }
                    rlist = rlist.diff(r);
                });
                if (request_old) {
                    if (!opts.background) {
                        this._addRequest(view, request_old, { background: false, offset: value - 1 });
                    }
                    this.isbusy = false;
                    return;
                } else if (!opts.background) {
                    // Set all other pending requests to background, since the
                    // current request is now the active request.
                    cr.keys().each(function(k) {
                        this._addRequest(view, k, { background: true });
                    }, this);
                }
            }
            // If we are in search mode, we must bite the bullet and simply
            // accept the entire slice back from the server.
        }

        if (!request_id) {
            request_id = this.fetch_hash[request_string] = this.request_num++;
        }
        params.set('request_id', request_id);
        this._addRequest(view, request_id, { background: opts.background, offset: value - 1, rlist: rlist });

        this._ajaxRequest(params, { noslice: true, view: view });
        this._handleWait();
        this.isbusy = false;
    },

    // rownum = (integer) Row number
    // nearing = (string) 'bottom', 'top', null
    // view = (string) The view to retrieve. Defaults to current view.
    _getSliceBounds: function(rownum, nearing, view)
    {
        var b_size = this._getBuffer(view).bufferSize(),
            ob = {};

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
            ob.start = Math.max(rownum - this._lookbehind(view), 1);
            ob.end = ob.start + b_size;
            break;
        }

        return ob;
    },

    // view = (string) The view to retrieve. Defaults to current view.
    _lookbehind: function(view)
    {
        return parseInt(0.4 * this._getBuffer(view).bufferSize(), 10);
    },

    // args = (object) The list of parameters.
    // opts = (object) [noslice, view]
    // Returns a Hash object
    addRequestParams: function(args, opts)
    {
        opts = opts || {};
        var cid = this.getMetaData('cacheid', opts.view),
            cached, params, rowlist;

        params = this.opts.onAjaxRequest
            ? this.opts.onAjaxRequest(opts.view || this.view)
            : $H();

        params.update({ view: opts.view || this.view });

        if (cid) {
            params.update({ cacheid: cid });
        }

        if (!opts.noslice) {
            rowlist = this._getSliceBounds(this.currentOffset(), null, opts.view);
            params.update({ slice: rowlist.start + ':' + rowlist.end });
        }

        if (this.opts.onCachedList) {
            cached = this.opts.onCachedList(opts.view || this.view);
        } else {
            cached = this._getBuffer(opts.view).getAllUIDs();
            cached = cached.size()
                ? cached.toJSON()
                : '';
        }

        if (cached.length) {
            params.update({ cached: cached });
        }

        return params.merge(args);
    },

    // params - (object) A list of parameters to send to server
    // opts - (object) Args to pass to addRequestParams().
    _ajaxRequest: function(params, other)
    {
        new Ajax.Request(this.opts.ajax_url, Object.extend(this.opts.ajax_opts || {}, {
            evalJS: false,
            evalJSON: true,
            onComplete: this.ajax_response,
            parameters: this.addRequestParams(params, other)
        }));
    },

    _ajaxRequestComplete: function(r)
    {
        if (r.responseJSON) {
            this.parseJSONResponse(r.responseJSON);
        }
    },

    // r - (object) responseJSON returned from the server.
    parseJSONResponse: function(r)
    {
        if (!r.ViewPort) {
            return;
        }

        r = r.ViewPort;

        if (r.rangelist) {
            this.select(this.createSelection('uid', r.rangelist, r.view));
            if (this.opts.onEndFetch) {
                this.opts.onEndFetch();
            }
        }

        if (r.cacheid) {
            this._ajaxResponse(r);
        }
    },

    // r = (Object) viewport response object
    _ajaxResponse: function(r)
    {
        if (this.isbusy) {
            this._ajaxResponse.bind(this, r).defer();
            return;
        }

        this.isbusy = true;
        this._clearWait();

        var buffer, cr_id,
            view = r.request_id ? this.current_req_lookup[r.request_id] : r.view,
            cr = this.current_req[view];

        if (cr && r.request_id) {
            cr_id = cr.get(r.request_id);
        }

        buffer = this._getBuffer(view);
        buffer.update(Object.isArray(r.data) ? {} : r.data, Object.isArray(r.rowlist) ? {} : r.rowlist, r.metadata || {}, { reset: r.reset, resetmd: r.resetmd, update: r.update });

        buffer.setMetaData({
            cacheid: r.cacheid,
            label: r.label,
            total_rows: r.totalrows
        }, true);

        if (this.opts.onCacheUpdate) {
            this.opts.onCacheUpdate(view);
        }

        if (r.request_id) {
            this._removeRequest(view, r.request_id);
        }

        this.isbusy = false;

        /* Don't update the viewport if we are now in a different view, or
         * if we are loading in the background. */
        if ((this.view == view || r.search) &&
            !(cr_id && cr_id.background) &&
            this._updateContent((cr_id && cr_id.offset) ? cr_id.offset : (r.rownum ? parseInt(r.rownum) - 1 : this.currentOffset())) &&
            this.opts.onEndFetch) {
            this.opts.onEndFetch();
        }
    },

    // Adds a request to the current request queue.
    // Requests are stored by view ID. Under each ID is the following:
    //   count: (integer) Number of times slice has attempted to be loaded
    //   background: (boolean) Do not update current view
    //   offset: (integer) The offset to use
    //   rlist: (array) The row list
    // params = (object) [background, offset, rlist]
    _addRequest: function(view, r_id, params)
    {
        var req_view = this.current_req[view], req;
        if (!req_view) {
            req_view = this.current_req[view] = $H();
        }

        req = req_view.get(r_id);
        if (!req) {
            req = req_view.set(r_id, { count: 1 });
        }
        ['background', 'offset', 'rlist'].each(function(p) {
            if (!Object.isUndefined(params[p])) {
                req[p] = params[p];
            }
        });

        this.current_req_lookup[r_id] = view;
    },

    // Removes a request to the current request queue.
    // view = (string) The view to remove a request for
    // r_id = (string) The request ID to remove
    _removeRequest: function(view, r_id)
    {
        var cr = this.current_req[view];
        if (cr) {
            cr.unset(r_id);
            if (!cr.size()) {
                delete this.current_req[view];
            }
        }
        delete this.current_req_lookup[r_id];
    },

    // offset = (integer) TODO
    // opts = (object) TODO [background, view]
    _updateContent: function(offset, opts)
    {
        opts = opts || {};

        if (!this._getBuffer(opts.view).sliceLoaded(offset)) {
            this._fetchBuffer($H(opts).merge({ offset: offset }).toObject());
            return false;
        }

        var c = this.opts.content,
            c_nodes = [],
            page_size = this.getPageSize(),
            rows;

        if (this.opts.onClear) {
            this.opts.onClear(c.childElements());
        }

        this.scroller.updateSize();
        this.scrollTo(offset + 1, { noupdate: true, top: true });

        offset = this.currentOffset();
        rows = this.createSelection('rownum', $A($R(offset + 1, offset + page_size)));

        if (rows.size()) {
            c_nodes = rows.get('dataob');
            c.update(c_nodes.collect(this.prepareRow.bind(this)).join(''));
        } else {
            c.update(this.empty_msg);
        }

        if (this.opts.onContentComplete) {
            this.opts.onContentComplete(c_nodes);
        }

        return true;
    },

    prepareRow: function(row)
    {
        var r = Object.clone(row);

        r.bg = r.bg
            ? row.bg.clone()
            : [];

        if (this.getSelected().contains('uid', r.vp_id)) {
            r.bg.push('vpRowSelected');
        }

        r.bg.unshift('vpRow');

        if (this.opts.onContent) {
            this.opts.onContent(r);
        }

        // Mandatory DOM ID and class information.
        r.vpData = 'id="' + r.domid + '" class="' + r.bg.join(' ') + '"';

        return this.template.evaluate(r);
    },

    updateRow: function(row)
    {
        var d = $(row.domid);
        if (d) {
            if (this.opts.onClear) {
                this.opts.onClear([ d ]);
            }

            d.replace(this.prepareRow(row));

            if (this.opts.onContentComplete) {
                this.opts.onContentComplete([ row ]);
            }
        }

    },

    _displayFetchError: function()
    {
        if (this.opts.onFail) {
            this.opts.onFail();
        }

        if (this.opts.errormsg) {
            this.opts.content.update(new Element('SPAN', { className: 'vpError' }).insert(this.opts.errormsg));
        }
    },

    _handleWait: function(call)
    {
        this._clearWait();

        // Server did not respond in defined amount of time.  Alert the
        // callback function and set the next timeout.
        if (call && this.opts.onWait) {
            this.opts.onWait();
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
            this.waitHandler = null;
        }
    },

    visibleRows: function()
    {
        return this.opts.content.childElements();
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
            : new ViewPort_Buffer(this, this.opts.buffer_pages, this.opts.limit_factor, view);
    },

    currentOffset: function()
    {
        return this.scroller.currentOffset();
    },

    _getLineHeight: function()
    {
        if (!this.line_height) {
            // To avoid hardcoding the line height, create a temporary row to
            // figure out what the CSS says.
            var d = new Element('DIV', { className: this.opts.content_class }).insert(new Element('DIV', { className: 'vpRow' })).hide();
            $(document.body).insert(d);
            this.line_height = d.getHeight();
            d.remove();
        }

        return this.line_height;
    },

    // (type) = (string) [null (DEFAULT), 'current', 'default', 'max']
    getPageSize: function(type)
    {
        switch (type) {
        case 'current':
            return Math.min(this.page_size, this.getMetaData('total_rows'));

        case 'default':
            return Math.max(parseInt(this.getPageSize('max') * 0.45), 5);

        case 'max':
        case 'splitmax':
            return parseInt((this._getMaxHeight() - (type == 'max' ? 0 : 100)) / this._getLineHeight());

        default:
            return this.page_size;
        }
    },

    _getMaxHeight: function()
    {
        return document.viewport.getHeight() - this.opts.content.viewportOffset()[1];
    },

    showSplitPane: function(show)
    {
        this.show_split_pane = show;
        this.onResize(true);
    },

    _initSplitBar: function()
    {
        if (this.split_bar) {
            return;
        }

        this.split_bar = $(this.opts.split_bar);
        new Drag(this.split_bar, {
            constraint: 'vertical',
            ghosting: true,
            nodrop: true,
            onStart: function() {
                // Cache these values since we will be using them multiple
                // times in snap().
                this.sp = {
                    lh: this._getLineHeight(),
                    lines: this.page_size,
                    max: this.getPageSize('splitmax'),
                    orig: this.page_size,
                    pos: $(this.opts.content).positionedOffset()[1]
                };
            }.bind(this),
            snap: function(x, y, elt) {
                var l = parseInt((y - this.sp.pos) / this.sp.lh);
                if (l < 1) {
                    l = 1;
                } else if (l > this.sp.max) {
                    l = this.sp.max;
                }
                this.sp.lines = l;
                return [ x, this.sp.pos + (l * this.sp.lh) ];
            }.bind(this),
            onEnd: function() {
                this.page_size = this.sp.lines;
                this.onResize(true);
                if (this.opts.onSplitBarChange &&
                    this.sp.orig != this.sp.lines) {
                    this.opts.onSplitBarChange();
                }
            }.bind(this)
        });
        this.split_bar.observe('dblclick', function() {
            var old_size = this.page_size;
            this.page_size = this.getPageSize('default');
            this._onResize(true);
            if (this.opts.onSplitBarChange &&
                old_size != this.page_size) {
                this.opts.onSplitBarChange();
            }
        }.bind(this));
    },

    createSelection: function(format, data, view)
    {
        var buffer = this._getBuffer(view);
        return buffer ? new ViewPort_Selection(buffer, format, data) : new ViewPort_Selection(this._getBuffer(this.view));
    },

    getSelection: function(view)
    {
        var buffer = this._getBuffer(view);
        return this.createSelection('uid', buffer ? buffer.getAllUIDs() : [], view);
    },

    // vs = (Viewport_Selection | array) A Viewport_Selection object -or-, if
    //       opts.range is set, an array of row numbers.
    // opts = (object) TODO [add, range]
    select: function(vs, opts)
    {
        opts = opts || {};

        var b = this._getBuffer(),
            sel, slice;

        if (opts.range) {
            slice = this.createSelection('rownum', vs);
            if (vs.size() != slice.size()) {
                if (this.opts.onFetch) {
                    this.opts.onFetch();
                }

                this._ajaxRequest({ rangeslice: 1, slice: vs.min() + ':' + vs.size() });
                return;
            }
            vs = slice;
        }

        if (!opts.add) {
            sel = this.getSelected();
            b.deselect(sel, true);
            sel.get('div').invoke('removeClassName', 'vpRowSelected');
        }
        b.select(vs);
        vs.get('div').invoke('addClassName', 'vpRowSelected');
        if (this.opts.onSelect) {
            this.opts.onSelect(vs, opts);
        }
    },

    // vs = (Viewport_Selection) A Viewport_Selection object.
    // opts = (object) TODO [clearall]
    deselect: function(vs, opts)
    {
        opts = opts || {};

        if (vs.size() &&
            this._getBuffer().deselect(vs, opts && opts.clearall)) {
            vs.get('div').invoke('removeClassName', 'vpRowSelected');
            if (this.opts.onDeselect) {
                this.opts.onDeselect(vs, opts)
            }
        }
    },

    getSelected: function()
    {
        return Object.clone(this._getBuffer().getSelected());
    }

}),

/**
 * ViewPort_Scroller
 */
ViewPort_Scroller = Class.create({
    // Variables initialized to undefined: noupdate

    initialize: function(vp)
    {
        this.vp = vp;
    },

    _createScrollBar: function()
    {
        if (this.scrollDiv) {
            return false;
        }

        var c = this.vp.opts.content;

        // Create the outer div.
        this.scrollDiv = new Element('DIV', { className: 'sbdiv' }).setStyle({ height: c.getHeight() + 'px' }).hide();

        // Add scrollbar to parent viewport and give our parent a right
        // margin just big enough to accomodate the scrollbar.
        c.insert({ after: this.scrollDiv }).setStyle({ marginRight: '-' + this.scrollDiv.getWidth() + 'px' });

        // Create scrollbar object.
        this.scrollbar = new DimpSlider(this.scrollDiv, {
            buttonclass: { up: 'sbup', down: 'sbdown' },
            cursorclass: 'sbcursor',
            onChange: this._onScroll.bind(this),
            onSlide: this.vp.opts.onSlide ? this.vp.opts.onSlide : null,
            pagesize: this.vp.getPageSize(),
            totalsize: this.vp.getMetaData('total_rows')
       });

        // Mouse wheel handler.
        c.observe(Prototype.Browser.Gecko ? 'DOMMouseScroll' : 'mousewheel', function(e) {
            var move_num = Math.min(this.vp.getPageSize(), 3);
            this.moveScroll(this.currentOffset() + ((e.wheelDelta >= 0 || e.detail < 0) ? (-1 * move_num) : move_num));
        }.bindAsEventListener(this));

        return true;
    },

    onResize: function()
    {
        if (!this.scrollDiv) {
            return;
        }

        // Update the container div.
        this.scrollsize = this.vp.opts.content.getHeight();
        this.scrollDiv.setStyle({ height: this.scrollsize + 'px' });

        // Update the scrollbar size
        this.updateSize();

        // Update displayed content.
        this.vp.requestContentRefresh(this.currentOffset());
    },

    updateSize: function()
    {
        if (!this._createScrollBar()) {
            this.scrollbar.updateHandleLength(this.vp.getPageSize(), this.vp.getMetaData('total_rows'));
        }
    },

    clear: function()
    {
        if (this.scrollDiv) {
            this.scrollbar.updateHandleLength(0, 0);
        }
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

/**
 * ViewPort_Buffer
 *
 * Note: recognize the difference between offset (current location in the
 * viewport - starts at 0) with start parameters (the row numbers - starts
 * at 1).
 */
ViewPort_Buffer = Class.create({

    initialize: function(vp, b_pages, l_factor, view)
    {
        this.bufferPages = b_pages;
        this.limitFactor = l_factor;
        this.vp = vp;
        this.view = view;
        this.clear();
    },

    getView: function()
    {
        return this.view;
    },

    _limitTolerance: function()
    {
        return Math.round(this.bufferSize() * (this.limitFactor / 100));
    },

    bufferSize: function()
    {
        // Buffer size must be at least the maximum page size.
        return Math.round(Math.max(this.vp.getPageSize('max') + 1, this.bufferPages * this.vp.getPageSize()));
    },

    // d = (object) Data
    // l = (object) Rowlist
    // md = (object) User defined metadata
    // opts = (object) TODO [reset, resetmd, update]
    update: function(d, l, md, opts)
    {
        var val;
        d = $H(d);
        l = $H(l);
        opts = opts || {};

        if (!opts.reset && this.data.size()) {
            this.data.update(d);
        } else {
            this.data = d;
        }

        if (opts.update || opts.reset) {
            this.uidlist = l;
            this.rowlist = $H();
        } else {
            this.uidlist = this.uidlist.size() ? this.uidlist.merge(l) : l;
        }

        l.each(function(o) {
            this.rowlist.set(o.value, o.key);
        }, this);

        if (opts.resetmd) {
            this.usermdata = $H(md);
        } else {
            $H(md).each(function(pair) {
                if (Object.isString(pair.value) || Object.isNumber(pair.value)) {
                    this.usermdata.set(pair.key, pair.value);
                } else {
                    val = this.usermdata.get(pair.key);
                    if (val) {
                        this.usermdata.get(pair.key).update($H(pair.value));
                    } else {
                        this.usermdata.set(pair.key, $H(pair.value));
                    }
                }
            }, this);
        }
    },

    // offset = (integer) Offset of the beginning of the slice.
    sliceLoaded: function(offset)
    {
        return !this._rangeCheck($A($R(offset + 1, Math.min(offset + this.vp.getPageSize() - 1, this.getMetaData('total_rows')))));
    },

    isNearingLimit: function(offset)
    {
        if (this.uidlist.size() != this.getMetaData('total_rows')) {
            if (offset != 0 &&
                this._rangeCheck($A($R(Math.max(offset + 1 - this._limitTolerance(), 1), offset)))) {
                return 'top';
            } else if (this._rangeCheck($A($R(offset + 1, Math.min(offset + this._limitTolerance() + this.vp.getPageSize() - 1, this.getMetaData('total_rows')))).reverse())) {
                // Search for missing messages in reverse order since in
                // normal usage (sequential scrolling through the message
                // list) messages are more likely to be missing at furthest
                // from the current view.
                return 'bottom';
            }
        }

        return false;
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
                e.domid = 'vp_row' + u;
                e.rownum = this.uidlist.get(u);
                e.vp_id = u;
                return e;
            }
        }, this).compact();
    },

    getAllUIDs: function()
    {
        return this.uidlist.keys();
    },

    getAllRows: function()
    {
        return this.rowsToUIDs(this.rowlist.keys());
    },

    rowsToUIDs: function(rows)
    {
        return rows.collect(this.rowlist.get.bind(this.rowlist)).compact();
    },

    // vs = (Viewport_Selection) TODO
    select: function(vs)
    {
        this.selected.add('uid', vs.get('uid'));
    },

    // vs = (Viewport_Selection) TODO
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
            if (n >= minrow) {
                var id = this.rowlist.get(n), r;
                if (rownums.include(n)) {
                    this.data.unset(id);
                    this.uidlist.unset(id);
                    rowsubtract++;
                } else if (rowsubtract) {
                    r = n - rowsubtract;
                    this.rowlist.set(r, id);
                    this.uidlist.set(id, r);
                }
                if (n > newsize) {
                    this.rowlist.unset(n);
                }
            }
        }, this);
    },

    clear: function()
    {
        this.data = $H();
        this.mdata = $H({ total_rows: 0 });
        this.rowlist = $H();
        this.selected = new ViewPort_Selection(this);
        this.uidlist = $H();
        this.usermdata = $H();
    },

    getMetaData: function(id)
    {
        return this.mdata.get(id) || this.usermdata.get(id);
    },

    setMetaData: function(vals, priv)
    {
        if (priv) {
            this.mdata.update(vals);
        } else {
            this.usermdata.update(vals);
        }
    }
}),

/**
 * ViewPort_Selection
 */
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

        switch (format) {
        case 'dataob':
            return d.pluck('vp_id');

        case 'div':
            return d.pluck('id').invoke('substring', 6);

        case 'domid':
            return d.invoke('substring', 6);

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
            return d.pluck('domid').collect(function(e) { return $(e); }).compact();

        case 'domid':
            return d.pluck('domid');

        case 'rownum':
            return d.pluck('rownum');
        }
    },

    contains: function(format, d)
    {
        return this.data.include(this._convert(format, d).first());
    },

    // params = (Object) Key is search key, value is object -> key of object
    // must be the following:
    //   equal - Matches any value contained in the query array.
    //   not - Matches any value not contained in the query array.
    //   regex - Matches the RegExp contained in the query.
    search: function(params)
    {
        return new ViewPort_Selection(this.buffer, 'uid', this.get('dataob').findAll(function(i) {
            // i = data object
            return $H(params).all(function(k) {
                // k.key = search key; k.value = search criteria
                return $H(k.value).all(function(s) {
                    // s.key = search type; s.value = search query
                    switch (s.key) {
                    case 'equal':
                    case 'not':
                        var r = i[k.key] && s.value.include(i[k.key]);
                        return (s.key == 'equal') ? r : !r;

                    case 'regex':
                        return i[k.key].match(s.value);
                    }
                });
            });
        }).pluck('vp_id'));
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
