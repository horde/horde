/**
 * Base logic for all jQuery Mobile applications.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Horde
 */
var HordeMobile = {

    notify_handler: function(m) { return HordeMobile.showNotifications(m); },
    //page_init: false,
    serverError: 0,

    loading: 0,

    /**
     * Common URLs.
     *
     * Set by Horde_PageOutput:
     *   - ajax_url: AJAX endpoint.
     *   - logout_url: Logout URL.
     *   - token: AJAX session token.
     */
    conf: {},

    /**
     * Debug.
     */
    debug: function(label, e)
    {
        if (!HordeMobile.is_logout && window.console && window.console.error) {
            window.console.error(label, jQuery.browser.mozilla ? e : jQuery.makeArray(e));
        }
    },

    /**
     * Perform an Ajax action
     *
     * @param string action      The AJAX request method.
     * @param object params      The parameter hash for the AJAX request.
     * @param function callback  A callback function for successful request.
     * @param object opts        Additional options for jQuery.ajax().
     *
     * @return jqXHR  jQuery XHR object.
     */
    doAction: function(action, params, callback, opts)
    {
        params = params || {};

        params.token = HordeMobile.conf.token;
        if (HordeMobile.conf.sid) {
            $.extend(params, HordeMobile.conf.sid.toQueryParams());
        }

        HordeMobile.loading++;
        $.mobile.showPageLoadingMsg();

        return $.ajax($.extend({
            data: params,
            error: $.noop,
            success: function(d, t, x) {
                HordeMobile.doActionComplete(d, callback);
            },
            type: 'post',
            url: HordeMobile.conf.ajax_url + action,
        }, opts || {}));
    },

    doActionComplete: function(d, callback)
    {
        var r = d.response;

        HordeMobile.inAjaxCallback = true;

        if (d.reload) {
            if (d.reload === true) {
                window.location.reload();
            } else {
                window.location.assign(d.reload);
            }
            return;
        }

        HordeMobile.notify_handler(d.msgs || []);

        if (d.tasks) {
            $(document).trigger('HordeMobile:runTasks', d.tasks);
        }

        if (r && $.isFunction(callback)) {
            try {
                callback(r);
            } catch (e) {
                HordeMobile.debug('doActionComplete', e);
            }
        }

        HordeMobile.inAjaxCallback = false;

        HordeMobile.loading--;
        if (!HordeMobile.loading) {
            $.mobile.hidePageLoadingMsg();
        }
    },

    /**
     * Output a notification.
     *
     * @param object msgs
     */
    showNotifications: function(msgs)
    {
        if (!msgs.length || HordeMobile.is_logout) {
            return;
        }

        if (!$.mobile.pageContainer) {
            window.setTimeout(function() {
                HordeMobile.showNotifications(msgs);
            }, 100);
            return;
        }

        $.each(msgs, function(key, m) {
            switch (m.type) {
            case 'horde.ajaxtimeout':
                HordeMobile.logout(m.message);
                return false;

            case 'horde.error':
            case 'horde.message':
            case 'horde.success':
            case 'horde.warning':
                $('#horde-notification').growler('notify', m.message, m.type, {
                    raw: (m.flags && $.inArray('content.raw', m.flags) != -1),
                    sticky: (m.type == 'horde.error')
                });
                break;
            }
        });
    },

    /**
     * Logout.
     *
     * @param string url  Use this URL instead of the default.
     */
    logout: function(url)
    {
        HordeMobile.is_logout = true;
        window.location = (url || HordeMobile.conf.logout_url);
    },

    /**
     * Wrapper around $.mobile.changePage() to do Horde framework specifc
     * tasks.
     *
     * @param string|object page  The page to navigate to.
     * @param object data         The request data object.
     * @param object opts         Options to pass to $.mobile.changePage.
     */
    changePage: function(page, data, opts)
    {
        opts = opts || {};

        if (data) {
            opts.dataUrl = data.toPage;
        }

        $.mobile.changePage($('#' + page), opts);
    },

    /**
     * Returns the current page ID.
     *
     * @return string  The page ID, or null if no page is loaded.
     */
    currentPage: function(page)
    {
        return $.mobile.activePage
            ? $.mobile.activePage.attr('id')
            : null;
    },

    /**
     * Create a URL to a smartmobile page.
     *
     * @param string page    The page name.
     * @param object params  URL parameters.
     */
    createUrl: function(page, params)
    {
        var url = '#' + page, tmp = [];
        params = params || {};

        if (!$.isEmptyObject(params)) {
            $.each(params, function(k, v) {
                tmp.push(k + '=' + (typeof(v) == 'undefined' ? '' : v));
            });
            url += '?' + tmp.join('&');
        }

        return url;
    },

    /**
     * Manually update hash: jqm exits too early if calling changePage() with
     * the same page but different hash parameters.
     *
     * @param object url  A parsed URL object.
     */
    updateHash: function(url)
    {
        $.mobile.urlHistory.ignoreNextHashChange = true;
        $.mobile.path.set(url.parsed.hash);
    },

    /**
     * Commands to run when changing a page.
     */
    onPageBeforeChange: function(e, data)
    {
        /* This code is needed for deep hash linking with parameters, since
         * the jquery mobile code will consider these hashes invalid and will
         * load the first page instead. */
        if (!this.page_init &&
            !$.mobile.activePage &&
            typeof data.toPage !== 'string') {
            data.toPage = location.href;
        }

        this.page_init = true;

        /* Add view/parameter data to dataUrl:
         *   - params: (object) List of URL parameters.
         *   - parsed: (object) Parsed URL object.
         *   - view: (string) The current view (URL hash value). */
        if (typeof data.toPage === 'string') {
            var parsed = $.mobile.path.parseUrl(data.toPage),
                match = /^#([^?]*)/.exec(parsed.hash);

            data.options.parsedUrl = {
                params: $.extend({}, parsed.search.toQueryParams(), parsed.hash.toQueryParams()),
                parsed: parsed,
                view: match ? match[1] : undefined
            };
        } else {
            data.options.parsedUrl = {};
        }
    },

    /**
     * Commands to run when the DOM is ready.
     */
    onDocumentReady: function()
    {
        // Global ajax options.
        $.ajaxSetup({
            dataFilter: function(data, type) {
                // Remove json security token
                filter = /^\/\*-secure-([\s\S]*)\*\/s*$/;
                return data.replace(filter, "$1");
            }
        });

        // Setup notifications
        $('#horde-notification').growler();

        // Fix swipe threshold.
        $.event.special.swipe.horizontalDistanceThreshold = 50;
    }
};

$(HordeMobile.onDocumentReady);
$(document).bind('pagebeforechange', HordeMobile.onPageBeforeChange);
