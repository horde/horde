/**
 * hordecore.js - Horde core AJAX code.
 *
 * This file requires prototypejs v1.8.0+.
 *
 * Events fired:
 *   - HordeCore:ajaxException
 *   - HordeCore:ajaxFailure
 *   - HordeCore:doActionComplete
 *   - HordeCore:runTasks
 *   - HordeCore:showNotifications
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 */

var HordeCore = {

    // Vars used and defaulting to null/false:
    //   Growler, conf, inAjaxCallback, is_logout, submit_frame, text

    alarms: [],
    base: null,
    handlers: {},
    loading: {},
    notify_handler: function(m) { HordeCore.showNotifications(m); },
    server_error: 0,
    submit_frame: [],

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
        document.fire('HordeCore:ajaxException', [ r, e ]);
    },

    onFailure: function(t, o)
    {
        this.debug('onFailure', t);
        this.notify(this.text.ajax_error, 'horde.error');
        document.fire('HordeCore:ajaxFailure', [ t, o ]);
    },

    // opts: (Object) ajaxopts, callback, loading
    doAction: function(action, params, opts)
    {
        params = $H(params).clone();
        opts = opts || {};

        var ajaxopts = Object.extend(this.doActionOpts(), opts.ajaxopts || {});

        this.addRequestParams(params);

        ajaxopts.parameters = params;

        this.initLoading(opts.loading);

        ajaxopts.onComplete = function(t) {
            this.doActionComplete(t, opts);
        }.bind(this);

        return new Ajax.Request(this.conf.URI_AJAX + action, ajaxopts);
    },

    // form: (Element) DOM Element (or DOM ID)
    // opts: (Object) ajaxopts, callback, loading
    submitForm: function(form, opts)
    {
        opts = opts || {};

        var ajaxopts = Object.extend(this.doActionOpts(), opts.ajaxopts || {});

        this.initLoading(opts.loading);

        ajaxopts.onComplete = function(t, o) {
            this.doActionComplete(t, opts);
        }.bind(this);
        ajaxopts.parameters = $H(ajaxopts.parameters || {});
        this.addRequestParams(ajaxopts.parameters);

        $(form).request(ajaxopts);
    },

    // Do a raw submit (non-AJAX).
    // form: (Element) DOM Element (or DOM ID)
    // opts: (Object) callback
    submit: function(form, opts)
    {
        form = $(form);
        opts = opts || {};

        var params = $H();

        this.addRequestParams(params);

        params.each(function(pair) {
            if (!form.down('INPUT[name=' + pair.key + ']')) {
                form.insert(new Element('INPUT', {
                    name: pair.key,
                    type: 'hidden'
                }).setValue(pair.value));
            }
        });

        if (opts.callback) {
            this.handleSubmit(form, {
                callback: opts.callback
            });
        }

        form.submit();
    },

    handleSubmit: function(form, opts)
    {
        form = $(form);
        opts = opts || {};

        if (this.submit_frame[form.identify()]) {
            return;
        }

        var sf = new Element('IFRAME', { name: 'submit_frame', src: 'javascript:false' }).hide();
        $(document.body).insert(sf);
        $(form).writeAttribute('target', 'submit_frame');

        sf.observe('load', function(sf) {
            this.doActionComplete({
                responseJSON: (sf.contentDocument || sf.contentWindow.document).body.innerHTML.evalJSON(true)
            }, opts);
        }.bind(this, sf));

        this.submit_frame[form.identify()] = sf;
    },

    // params: (Hash) URL parameters
    addRequestParams: function(params)
    {
        if (this.conf.SID) {
            params.update(this.conf.SID.toQueryParams());
        }
        params.set('token', this.conf.TOKEN);
    },

    // resp = Ajax.Response object
    // opts = HordeCore options (callback, loading)
    doActionComplete: function(resp, opts)
    {
        this.inAjaxCallback = true;

        if (!resp.responseJSON) {
            if (++this.server_error == 3) {
                this.notify(this.text.ajax_timeout, 'horde.error');
            }
            if (resp.request) {
                resp.request.options.onFailure(resp, {});
            }
            this.endLoading(opts.loading);
            this.inAjaxCallback = false;
            return;
        }

        var r = resp.responseJSON;

        if (r.reload) {
            if (r.reload === true) {
                window.location.reload();
            } else {
                window.location.assign(r.reload);
            }
            return;
        }

        if (!r.msgs) {
            r.msgs = [];
        }

        if (this.server_error >= 3) {
            r.msgs.push({
                message: this.text.ajax_recover,
                type: 'horde.success'
            });
        }
        this.server_error = 0;

        if (r.tasks) {
            document.fire('HordeCore:runTasks', {
                response: resp,
                tasks: r.tasks
            });
        }

        if (r.response && Object.isFunction(opts.callback)) {
            try {
                opts.callback(r.response, resp);
            } catch (e) {
                this.debug('doActionComplete', e);
            }
        }

        document.fire('HordeCore:doActionComplete');

        this.notify_handler(r.msgs);

        this.endLoading(opts.loading);

        this.inAjaxCallback = false;
    },

    initLoading: function(id)
    {
        if (id && id.length) {
            if (this.loading[id]) {
                ++this.loading[id];
            } else {
                this.loading[id] = 1;
                document.fire('HordeCore:loadingStart', id);
            }
        }
    },

    endLoading: function(id)
    {
        if (id && id.length && this.loading[id]) {
            if (this.loading[id] == 1) {
                delete this.loading[id];
                document.fire('HordeCore:loadingEnd', id);
            } else {
                --this.loading[id];
            }
        }
    },

    showNotifications: function(msgs)
    {
        if (!msgs.size() || this.is_logout) {
            return;
        }

        if (!this.Growler) {
            return this.showNotifications.bind(this, msgs).defer();
        }

        msgs.find(function(m) {
            if (!Object.isString(m.message)) {
                return;
            }

            switch (m.type) {
            case 'horde.ajaxtimeout':
            case 'horde.noauth':
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
                    $H(this.text.snooze_select).each(function(snooze) {
                        select += '<option value="' + snooze.key + '">' + snooze.value + '</option>';
                    });
                    select += '</select>';
                    message.insert('<br /><br />' + this.text.snooze.interpolate({ time: select, dismiss_start: '<input type="button" value="', dismiss_end: '" class="horde-default" />' }));
                }
                var growl = this.Growler.growl(message, {
                    className: 'horde-alarm',
                    life: 8,
                    log: false,
                    opacity: 0.9,
                    sticky: true
                });
                growl.store('alarm', alarm.id);

                if (alarm.user) {
                    message.down('select').observe('change', function(e) {
                        if (e.element().getValue()) {
                            this.Growler.ungrowl(growl);
                            new Ajax.Request(this.conf.URI_SNOOZE, {
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
                        new Ajax.Request(this.conf.URI_SNOOZE, {
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
                        log: 1,
                        opacity: 0.9,
                        sticky: m.flags && m.flags.include('sticky')
                    }
                );
                break;
            }

            document.fire('HordeCore:showNotifications', m);
        }, this);
    },

    notify: function(msg, type)
    {
        this.showNotifications([ {
            message: msg,
            type: type
        } ]);
    },

    // url: (string) TODO
    // params: (object) TODO
    // opts: (object) 'name', 'onload'
    popupWindow: function(url, params, opts)
    {
        opts = opts || {};
        params = $H(params || {});

        this.addRequestParams(params);

        var p = {
            height: this.conf.popup_height,
            name: (opts.name || '_hordepopup').gsub(/\W/, '_'),
            noalert: true,
            onload: opts.onload,
            params: params,
            url: url,
            width: this.conf.popup_width
        };

        if (!HordePopup.popup(p)) {
            this.notify(HordePopup.popup_block_text, 'horde.warning');
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
        this.redirect(url || this.conf.URI_LOGOUT);
    },

    // url: (string) URL to redirect to
    redirect: function(url)
    {
        window.location.assign(this.addURLParam(url));
    },

    // Redirect to the download link.
    download: function(name, params)
    {
        var url = this.addURLParam(this.conf.URI_DLOAD, params);
        // Guaranteed to have at least one URL parameter, since download
        // URL requires the app name. So just append filename to end.
        url += '&fn=/' + encodeURIComponent(name);
        window.location.assign(url);
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
            }).setOpacity(1).show();
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

    initHandler: function(type)
    {
        if (!this.handlers[type]) {
            switch (type) {
            case 'click':
            case 'dblclick':
                this.handlers[type] = this.clickHandler.bindAsEventListener(this);
                document.observe(type, this.handlers[type]);
                break;
            }
        }
    },

    // 'HordeCore:click'/'HordeCore:dblclick' is fired on every element up
    // to the document root. The memo attribute is the original Event. If
    // this original Event contains a non-zero value of hordecore_stop,
    // bubbling is immediately stopped.
    clickHandler: function(e)
    {
        if (e.isRightClick()) {
            return;
        }

        var elt = e.element();

        while (Object.isElement(elt)) {
            elt.fire('HordeCore:' + e.type, e);
            if (e.hordecore_stop) {
                e.stop();
                break;
            }
            elt = elt.up();
        }
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
        if (window.Growler) {
            if (this.conf.growler_log) {
                this.Growler = new Growler({
                    info: this.text.growlerinfo,
                    location: 'br',
                    log: true,
                    noalerts: this.text.growlernoalerts
                });
            } else {
                this.Growler = new Growler({ location: 'br' });
            }
        }
    }

};

document.observe('dom:loaded', HordeCore.onDomLoad.bind(HordeCore));
document.observe('Growler:destroyed', function(e) {
    var id = e.element().retrieve('alarm');
    if (id) {
        this.alarms = this.alarms.without(id);
    }
}.bindAsEventListener(HordeCore));
