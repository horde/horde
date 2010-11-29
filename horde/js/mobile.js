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

    showNotifications: function(msgs)
    {
        if (!msgs.length || HordeMobile.is_logout) {
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
            $.mobile.changePage('notification');
            list.listview('refresh');
        }
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
