/**
 * Growler.js - Display 'Growl'-like notifications.
 *
 * Notice Options (passed to 'Growler.growl()'):
 * 'className' - (string) An optional additional CSS class to apply to notice.
 * 'header' - (string) The title that is displayed for the notice.
 * 'life' - (float) The number of seconds in which the notice remains visible.
 * 'log' - (boolean) If true, will log the entry.
 * 'speedin' - (float) The speed in seconds in which the notice is shown.
 * 'speedout' - (float) The speed in seconds in which the notice is hidden.
 * 'sticky' - (boolean) Determines if the notice should always remain visible
 *            until closed by the user.
 *
 * Growler Options (passed to 'new Growler()'):
 * 'location' - (string) The location of the growler notices. This can be:
 *   tr (top-right)
 *   br (bottom-right)
 *   tl (top-left)
 *   bl (bottom-left)
 *   tc (top-center)
 *   bc (bottom-center)
 * 'log' - (boolean) Enable logging.
 * 'noalerts' - (string) The localized string to display when no log entries
 *              are present.
 *
 * Custom Events:
 * --------------
 * Custom events are triggered on the notice element. The parameters given
 * below are available through the 'memo' property of the Event object.
 *
 * Growler:created
 *   Fired on TODO
 *   params: NONE
 *
 * Growler:destroyed
 *   Fired on TODO
 *   params: NONE
 *
 *
 * Growler has been tested with Safari 3(Mac|Win), Firefox 3(Mac|Win), IE6,
 * IE7, and Opera.
 *
 * Requires prototypejs 1.6+ and scriptaculous 1.8+ (effects.js only).
 *
 * Code adapted from k.Growler v1.0.0
 *   http://code.google.com/p/kproto/
 *   Written by Kevin Armstrong <kevin@kevinandre.com>
 *   Released under the MIT license
 *
 * @author Michael Slusarz <slusarz@horde.org>
 */

(function() {

    var noticeOptions = {
        header: '',
        speedin: 0.3,
        speedout: 0.5,
        life: 5,
        sticky: false,
        className: ''
    },

    growlerOptions = {
        location: 'tr',
        log: false,
        noalerts: 'No Alerts'
    },

    IE6 = Prototype.Browser.IE
        ? (parseFloat(navigator.appVersion.split("MSIE ")[1]) || 0) == 6
        : 0;

    function removeNotice(n, o)
    {
        o = o || noticeOptions;

        $(n).fade({
            duration: o.speedout,
            afterFinish: function() {
                try {
                    var ne = n.down('DIV.GrowlerNoticeExit');
                    if (!Object.isUndefined(ne)) {
                        ne.stopObserving('click', removeNotice);
                    }
                    n.fire('Growler:destroyed');
                } catch (e) {}

                try {
                    n.remove();
                    if (!$('Growler').childElements().size()) {
                        $('Growler').hide().setOpacity(1);
                    }
                } catch (e) {}
            }
        });
    }

    function removeLog(l)
    {
        try {
            var le = l.down('DIV.GrowlerNoticeExit');
            if (!Object.isUndefined(le)) {
                le.stopObserving('click', removeLog);
            }
        } catch (e) {}
        try {
            l.remove();
        } catch (e) {}
    }

    window.Growler = Class.create({

        initialize: function(opts)
        {
            var ch, cw, sl, st;
            opts = Object.extend(Object.clone(growlerOptions), opts || {});

            this.growler = new Element('DIV', { id: 'Growler' }).setStyle({ position: IE6 ? 'absolute' : 'fixed', padding: '10px', zIndex: 50000 }).hide();

            if (IE6) {
                ch = '0 - this.offsetHeight + ( document.documentElement.clientHeight ? document.documentElement.clientHeight : document.body.clientHeight )';
                cw = '0 - this.offsetWidth + ( document.documentElement.clientWidth ? document.documentElement.clientWidth : document.body.clientWidth )';
                sl = '( document.documentElement.scrollLeft ? document.documentElement.scrollLeft : document.body.scrollLeft )';
                st = '( document.documentElement.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop )';
            } else if (opts.log) {
                this.growlerlog = new Element('DIV', { id: 'GrowlerLog' }).insert(new Element('DIV').hide().insert(new Element('UL').insert(new Element('LI', { className: 'NoAlerts' }).insert(opts.noalerts))));
                $(document.body).insert(this.growlerlog);
            }

            switch (opts.location) {
            case 'br':
                if (IE6) {
                    this.growler.style.setExpression('left', "( " + cw + " + " + sl + " ) + 'px'");
                    this.growler.style.setExpression('top', "( " + ch + "+ " + st + " ) + 'px'");
                } else {
                    this.growler.setStyle({ bottom: 0, right: 0 });
                }
                break;

            case 'tl':
                if (IE6) {
                    this.growler.style.setExpression('left', sl + " + 'px'");
                    this.growler.style.setExpression('top', st + " + 'px'");
                } else {
                    this.growler.setStyle({ top: 0, left: 0 });
                }
                break;

            case 'bl':
                if (IE6) {
                    this.growler.style.setExpression('left', sl + " + 'px'");
                    this.growler.style.setExpression('top', "( " + ch + " + " + st + " ) + 'px'");
                } else {
                    this.growler.setStyle({ top: 0, right: 0 });
                }
                break;

            case 'tc':
                if (!IE6) {
                    this.growler.setStyle({ top: 0, left: '25%', width: '50%' });
                }
                break;

            case 'bc':
                if (!IE6) {
                    this.growler.setStyle({ bottom: 0, left: '25%', width: '50%' });
                }
                break;

            default:
                if (IE6) {
                    this.growler.setStyle({ bottom: 'auto', right: 'auto' });
                    this.growler.style.setExpression('left', "( " + cw + " + " + sl + " ) + 'px'");
                    this.growler.style.setExpression('top', st + " + 'px'");
                } else {
                    this.growler.setStyle({ top: 0, right: 0 });
                }
                break;
            }

            this.growler.wrap(document.body);

            this.growler.observe('mouseenter', function() {
                this.growler.fade({
                    duration: 0.25,
                    queue: { limit: 2, scope: 'growler' },
                    to: 0.3
                });
            }.bind(this));
            this.growler.observe('mouseleave', function() {
                this.growler.appear({
                    duration: 0.25,
                    queue: { limit: 2, scope: 'growler' },
                    to: 1
                });
            }.bind(this));
        },

        growl: function(msg, options)
        {
            options = options || {};
            var notice, noticeExit, log, logExit, tmp,
                opts = Object.clone(noticeOptions);
            Object.extend(opts, options);

            if (opts.log && this.growlerlog) {
                tmp = this.growlerlog.down('DIV UL');
                if (tmp.down().hasClassName('NoAlerts')) {
                    tmp.down().remove();
                }
                log = new Element('LI', { className: opts.className.empty() ? null : opts.className }).insert(msg).insert(new Element('SPAN', { className: 'alertdate'} ).insert('[' + (new Date).toLocaleString() + ']'));
                logExit = new Element('DIV', { className: 'GrowlerNoticeExit' }).update("&times;");
                logExit.observe('click', removeLog.curry(log));
                log.insert(logExit);
                tmp.insert(log);
            }

            notice = new Element('DIV', { className: 'GrowlerNotice' }).setStyle({ display: 'block', opacity: 0 });
            if (!opts.className.empty()) {
                notice.addClassName(opts.className);
            }

            noticeExit = new Element('DIV', { className: 'GrowlerNoticeExit' }).update("&times;");
            noticeExit.observe('click', removeNotice.curry(notice, opts));
            notice.insert(noticeExit);

            if (!opts.header.empty()) {
                notice.insert(new Element('DIV', { className: 'GrowlerNoticeHead' }).update(opts.header))
            }

            notice.insert(new Element('DIV', { className: 'GrowlerNoticeBody' }).update(msg));

            this.growler.show().insert(notice);

            new Effect.Opacity(notice, { to: 0.85, duration: opts.speedin });

            if (!opts.sticky) {
                removeNotice.delay(opts.life, notice, opts);
            }

            notice.fire('Growler:created');

            return notice;
        },

        ungrowl: function(n, o)
        {
            removeNotice(n, o);
        },

        toggleLog: function()
        {
            if (!this.growlerlog) {
                return;
            }
            Effect.toggle(this.growlerlog.down('DIV'), 'blind', {
                duration: 0.5,
                queue: {
                    position: 'end',
                    scope: 'GrowlerLog',
                    limit: 2
                }
            });
            this.logvisible = !this.logvisible;
            return this.logvisible;
        },

        logVisible: function()
        {
            return this.growlerlog && this.logvisible;
        },

        logSize: function()
        {
            return (this.growlerlog && this.growlerlog.down('.NoAlerts'))
                ? 0
                : this.growlerlog.down('UL').childElements().size();
        }

    });

})();
