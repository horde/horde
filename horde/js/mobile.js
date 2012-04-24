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
    serverError: 0,

    /**
     * Common URLs.
     *
     * Required properties to be set from calling applications:
     * - ajax: AJAX endpoint.
     */
    urls: {},

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
     */
    doAction: function(action, params, callback, opts)
    {
        $.mobile.showPageLoadingMsg();
        var options = $.extend({
            'data': params,
            'error': $.noop,
            'success': function(d, t, x) {
                HordeMobile.doActionComplete(d, callback);
            },
            'type': 'post',
            'url': HordeMobile.urls.ajax + action,
        }, opts || {});
        $.ajax(options);
    },

    doActionComplete: function(d, callback)
    {
        var r = d.response;

        HordeMobile.inAjaxCallback = true;

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

        $.mobile.hidePageLoadingMsg();
    },

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

        var list = $('#horde-notification'), li;
        list.html('');

        $.each(msgs, function(key, m) {
            switch (m.type) {
            case 'horde.ajaxtimeout':
                HordeMobile.logout(m.message);
                return false;

            case 'horde.error':
            case 'horde.warning':
            case 'horde.message':
            case 'horde.success':
                li = $('<li class="' + m.type.replace('.', '-') + '">');
                if (m.flags && $.inArray('content.raw', m.flags) != -1) {
                    // TODO: This needs some fixing:
                    li.html(m.message.replace('<a href=', '<a rel="external" href='));
                } else {
                    li.text(m.message);
                }
                list.append(li);
                break;
            }
        });

        if (list.html()) {
            $.mobile.changePage($('#smartmobile-notification'), { transition: 'pop' });
        }
    },

    logout: function(url)
    {
        HordeMobile.is_logout = true;
        window.location = (url || HordeMobile.urls.ajax + 'logOut');
    },

    /**
     * Safe wrapper that makes sure that no dialog is still open before
     * calling a function.
     *
     * @param function func    A function to execute after the current dialog
     *                         has been closed
     * @param array whitelist  A list of page IDs that should not be waited
                               for.
     */
    onDialogClose: function(func, whitelist)
    {
        whitelist = whitelist || [];

        if ($.mobile.activePage.jqmData('role') == 'dialog' &&
            $.inArray($.mobile.activePage.attr('id'), whitelist) == -1) {
            $.mobile.activePage.bind('pagehide', function(e) {
                $(e.currentTarget).unbind(e);
                window.setTimeout(function() {
                    HordeMobile.onDialogClose(func, whitelist);
                }, 0);
            });
        } else {
            func();
        }
    },

    /**
     * Safe wrapper around $.mobile.changePage() that makes sure that no
     * dialog is still open before changing to the new page.
     *
     * @param string|object page  The page to navigate to.
     */
    changePage: function(page)
    {
        HordeMobile.onDialogClose(function() { $.mobile.changePage(page); });
    },

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

        $('#smartmobile-notification').live('pagebeforeshow', function() {
            $('#horde-notification').listview('refresh');
        });
    }
};

$(HordeMobile.onDocumentReady);
