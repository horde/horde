/**
 * hordecore.js - Horde core AJAX code.
 *
 * This file requires prototypejs v1.8.0+.
 *
 * Events fired:
 *   - HordeCore:doActionComplete
 *   - HordeCore:showNotifications
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 */

var HordeCore = {

    // Vars used and defaulting to null/false:
    //   Growler, inAjaxCallback, is_logout
    alarms: [],
    base: null,
    server_error: 0,

    doActionOpts: function()
    {
        return {
            evalJS: false,
            evalJSON: true,
            onException: this.onException.bind(this),
            onFailure: this.onFailure.bind(this)
        };
    },

    debug: function(label, e)
    {
        if (!this.is_logout && window.console && window.console.error) {
            window.console.error(label, Prototype.Browser.Gecko ? e : $H(e).inspect());
        }
    },

    onException: function(r, e)
    {
        this.debug('onException', e);
    },

    onFailure: function(t, o)
    {
        this.debug('onFailure', t);
        this.notify(HordeCoreText.ajax_error, 'horde.error');
    },

    // opts: (Object) ajaxopts, callback
    doAction: function(action, params, opts)
    {
        params = $H(params).clone();
        opts = opts || {};

        var ajaxopts = Object.extend(this.doActionOpts, opts.ajaxopts || {});

        this.addRequestParams(params);
        ajaxopts.parameters = params;

        ajaxopts.onComplete = function(t, o) {
            this.doActionComplete(t, opts.callback);
        }.bind(this);

        new Ajax.Request(HordeCoreConf.URI_AJAX + action, ajaxopts);
    },

    // form: (Element) DOM Element (or DOM ID)
    // opts: (Object) ajaxopts, callback
    submitForm: function(form, opts)
    {
        opts = opts || {};

        var ajaxopts = Object.extend(this.doActionOpts, opts.ajaxopts || {});
        ajaxopts.onComplete = function(t, o) {
            this.doActionComplete(t, opts.callback);
        }.bind(this);

        $(form).request(ajaxopts);
    },

    // params: (Hash) URL parameters
    addRequestParams: function(params)
    {
        if (HordeCoreConf.SID) {
            params.update(HordeCoreConf.SID.toQueryParams());
        }
    },

    doActionComplete: function(request, callback)
    {
        this.inAjaxCallback = true;

        if (!request.responseJSON) {
            if (++this.server_error == 3) {
                this.notify(HordeCoreText.ajax_timeout, 'horde.error');
            }
            if (request.request) {
                request.request.options.onFailure(request, {});
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
            r.msgs.push({
                message: HordeCoreText.ajax_recover,
                type: 'horde.success'
            });
        }
        this.server_error = 0;

        this.showNotifications(r.msgs);

        if (r.response) {
            document.fire('HordeCore:doActionComplete', r.response);
        }

        this.inAjaxCallback = false;
    },

    showNotifications: function(msgs, opts)
    {
        if (!msgs.size() || this.is_logout) {
            return;
        }

        if (opts && opts.base && this.base) {
            return this.base.HordeCore.showNotifications(msgs);
        }

        msgs.find(function(m) {
            if (!Object.isString(m.message)) {
                return;
            }

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
                    if (alarm.params.notify.url) {
                        message = new Element('A', { href: alarm.params.notify.url }).insert(message);
                    }
                    if (alarm.params.notify.sound) {
                        Sound.play(alarm.params.notify.sound);
                    }
                }
                message = new Element('DIV').insert(message);
                if (alarm.params &&
                    alarm.params.notify &&
                    alarm.params.notify.subtitle) {
                    message.insert(new Element('BR')).insert(alarm.params.notify.subtitle);
                }
                if (alarm.user) {
                    var select = '<select>';
                    $H(HordeCoreText.snooze_select).each(function(snooze) {
                        select += '<option value="' + snooze.key + '">' + snooze.value + '</option>';
                    });
                    select += '</select>';
                    message.insert('<br /><br />' + HordeCoreText.snooze.interpolate({ time: select, dismiss_start: '<input type="button" value="', dismiss_end: '" class="button ko" />' }));
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
                            new Ajax.Request(HordeCoreConf.URI_SNOOZE, {
                                parameters: {
                                    alarm: alarm.id,
                                    snooze: e.element().getValue()
                                }
                            });
                        }
                    }.bindAsEventListener(this))
                    .observe('click', function(e) {
                        e.stop();
                    });
                    message.down('input[type=button]').observe('click', function(e) {
                        new Ajax.Request(HordeCoreConf.URI_SNOOZE, {
                            parameters: {
                                alarm: alarm.id,
                                snooze: -1
                            }
                        });
                    }.bindAsEventListener(this));
                }
                break;

            case 'horde.error':
            case 'horde.message':
            case 'horde.success':
            case 'horde.warning':
                this.Growler.growl(
                    (m.flags && m.flags.include('content.raw')) ? m.message : m.message.escapeHTML(),
                    {
                        className: m.type.replace('.', '-'),
                        life: (m.type == 'horde.error' ? 12 : 8),
                        log: 1
                    }
                );
                break;
            }

            document.fire('HordeCore:showNotifications', m);
        }, this);
    },

    notify: function(msg, type, opts)
    {
        this.showNotifications([ {
            message: msg,
            type: type
        } ], opts);
    },

    // url: (string) TODO
    // params: (object) TODO
    // opts: (object) 'name', 'onload'
    popupWindow: function(url, params, opts)
    {
        params = params || {};
        opts = opts || {};

        var params = {
            height: HordeCoreConf.popup_height,
            name: (opts.name || '_hordepopup').gsub(/\W/, '_'),
            noalert: true,
            onload: opts.onload,
            url: this.addURLParam(url, params),
            width: HordeCoreConf.popup_width
        };

        if (!Horde.popup(params)) {
            this.notify(HordeCoreText.popup_block, 'horde.warning');
        }
    },

    closePopup: function()
    {
        // Mozilla bug/feature: it will not close a browser window
        // automatically if there is code remaining to be performed (or, at
        // least, not here) unless the mouse is moved or a keyboard event
        // is triggered after the callback is complete. (As of FF 2.0.0.3 and
        // 1.5.0.11).  So wait for the callback to complete before attempting
        // to close the window.
        if (this.inAjaxCallback) {
            this.closePopup.bind(this).defer();
        } else {
            window.close();
        }
    },

    // url: (string) Logout URL to redirect to
    logout: function(url)
    {
        this.is_logout = true;
        this.redirect(url || (HordeCoreConf.URI_AJAX + 'logOut'));
    },

    // url: (string) URL to redirect to
    redirect: function(url)
    {
        window.location.assign(this.addURLParam(url));
    },

    // id: (string) The ID to use for the loading image.
    // base: (Element) The base element over which the loading image should
    //       appear.
    // show: (boolean) If true, show image; if false, hide image.
    loadingImg: function(id, base, show)
    {
        var elt = $(id);

        if (show) {
            if (!elt) {
                elt = new Element('SPAN', { className: 'loadingImg', id: id }).hide();
                $(document.body).insert(elt);
            }

            elt.clonePosition(base, {
                setHeight: false,
                setWidth: false
            }).show();
        } else if (elt) {
            elt.fade({ duration: 0.2 });
        }
    },

    // url: (string) URL
    // params: (object) List of parameters to add to URL
    addURLParam: function(url, params)
    {
        var q = url.indexOf('?');
        params = $H(params);

        this.addRequestParams(params);

        if (q != -1) {
            params.update(url.toQueryParams());
            url = url.substring(0, q);
        }

        return params.size()
            ? (url + '?' + params.toQueryString())
            : url;
    },

    onDomLoad: function()
    {
        /* Determine base window. Need a try/catch block here since, if the
         * page was loaded by an opener out of this current domain, this will
         * throw an exception. */
        try {
            if (parent.opener &&
                parent.opener.location.host == window.location.host &&
                parent.opener.HordeCore) {
                this.base = parent.opener.HordeCore.base || parent.opener;
            }
        } catch (e) {}

        /* Add Growler notification handler. */
        if (HordeCoreConf.growler_log) {
            this.Growler = new Growler({
                info: HordeCoreText.growlerinfo,
                location: 'br',
                log: true,
                noalerts: HordeCoreText.growlernoalerts
            });
        } else {
            this.Growler = new Growler({ location: 'br' });
        }
    }

};

document.observe('dom:loaded', HordeCore.onDomLoad.bind(HordeCore));
