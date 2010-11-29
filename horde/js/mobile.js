/**
 * Base logic for all jQuery Mobile applications.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Horde
 */
 var HordeMobile = {

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
     * @param string action      The AJAX request
     * @param object params      The parameter hash
     * @param function callback  The callback function
     */
    doAction: function(action, params, callback)
    {
        $.mobile.pageLoading();
        var options = {
            'url': HordeMobile.urls.ajax + action,
            'data': params,
            'error': HordeMobile.errorCallback,
            'success': function(d, t, x) { HordeMobile.doActionComplete(d, callback); },
            'type': 'post'
        };
        $.ajax(options);
    },

    doActionComplete: function(d, callback)
    {
        var r = d.response;
        if (r && $.isFunction(callback)) {
            try {
                callback(r);
            } catch (e) {
                HordeMobile.debug('doActionComplete', e);
            }
        }

        HordeMobile.server_error = 0;
        HordeMobile.showNotifications(d.msgs || []);
        HordeMobile.inAjaxCallback = false;
        $.mobile.pageLoading(true);
    },

    showNotifications: function(m)
    {
        $.each(m, function(key, msg) {
            if (msg.type == 'horde.ajaxtimeout') {
                HordeMobile.logout(msg.message);
            }
        });
    },

    logout: function(url)
    {
        HordeMobile.is_logout = true;
        window.location = (url || HordeMobile.urls.ajax + 'logOut');
    },

    errorCallback: function(x, t, e)
    {

    },

    onDocumentReady: function()
    {
        // Global ajax options.
        $.ajaxSetup({
            dataFilter: function(data, type)
            {
                // Remove json security token
                filter = /^\/\*-secure-([\s\S]*)\*\/s*$/;
                return data.replace(filter, "$1");
            }
        });
    }
};
$(HordeMobile.onDocumentReady);
