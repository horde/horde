/**
 * Growler.js - Display 'Growl'-like notifications.
 *
 * Notice Options (passed to 'Growler.growl()'):
 *   - className: (string) An optional additional CSS class to apply to notice.
 *   - header: (string) The title that is displayed for the notice.
 *   - life: (float) The number of seconds in which the notice remains visible.
 *   - log: (boolean) If true, will log the entry.
 *   - opacity: (float) The default opacity of the notifications.
 *   - speedin: (float) The speed in seconds in which the notice is shown.
 *   - speedout: (float) The speed in seconds in which the notice is hidden.
 *   - sticky: (boolean) Determines if the notice should always remain visible
 *             until closed by the user.
 *
 * Growler Options (passed to 'new Growler()'):
 *   - info: (string) The localized string to display as an information message
 *           at the top of the log.
 *   - location: (string) The location of the growler notices. This can be:
 *     - tr: top-right
 *     - br: bottom-right
 *     - tl: top-left
 *     - bl: bottom-left
 *     - tc: top-center
 *     - bc: bottom-center
 *   - log: (boolean) Enable logging.
 *   - noalerts: (string) The localized string to display when no log entries
 *              are present.
 *   - noclick: (boolean) Don't check for clicks on Growler elements.
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
 * Growler:linkClick
 *   Fired on click of a link in a Growler message.
 *   params: The link
 *
 * Growler:toggled
 *   Fired on toggling of the backlog
 *   params: The state after the toggling
 *
 *
 * Growler has been tested with Safari 3(Mac|Win), Firefox 3(Mac|Win), IE7+,
 * and Opera.
 *
 * Requires prototypejs 1.6+ and scriptaculous 1.8+ (effects.js only).
 *
 * Code adapted from k.Growler v1.0.0
 *   http://code.google.com/p/kproto/
 *   Written by Kevin Armstrong <kevin@kevinandre.com>
 *   Released under the MIT license
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2010-2015 Horde LLC
 * @license    LGPL-2.1 (http://www.horde.org/licenses/lgpl21)
 */

(function() {

    var noticeOptions = {
        header: '',
        speedin: 0.3,
        speedout: 0.5,
        life: 5,
        opacity: 0.8,
        sticky: false,
        className: ''
    },

    growlerOptions = {
        clear: 'Clear All',
        location: 'tr',
        log: false,
        noalerts: 'No Alerts',
        info: 'This is the notification log.'
    };

    function removeNotice(n)
    {
        n.fade({
            afterFinish: function() {
                try {
                    n.fire('Growler:destroyed');
                    n.remove();
                    if (!$('Growler').childElements().size()) {
                        $('Growler').hide();
                    }
                } catch (e) {}
            },
            duration: n.retrieve('so')
        });
    }

    window.Growler = Class.create({

        initialize: function(opts)
        {
            var style;

            this.opts = Object.extend(Object.clone(growlerOptions), opts || {});

            this.growler = new Element('DIV', { id: 'Growler' }).setStyle({
                position: 'fixed',
                padding: '10px',
                zIndex: 50000
            }).hide();
            this.growler.on('click', 'DIV.GrowlerNotice', function(e, elt) {
                removeNotice(elt);
            });

            if (this.opts.log) {
                this.growlerlog = new Element('DIV', { id: 'GrowlerLog' })
                    .insert(new Element('DIV').hide()
                        .insert(new Element('UL')
                            .insert(new Element('LI', { className: 'GrowlerInfo' })
                                .insert(this.opts.info)
                                .insert(new Element('SPAN', { className: 'GrowlerLogClear' })
                                    .insert('[')
                                    .insert(new Element('A')
                                        .insert(this.opts.clear))
                                    .insert(']')))
                            .insert(new Element('LI', { className: 'GrowlerNoAlerts' })
                                .insert(this.opts.noalerts))));
                $(document.body).insert(this.growlerlog);
                this.growlerlog.on('click', 'SPAN.GrowlerLogClear A', function() {
                    this.growlerlog.down('LI.GrowlerInfo').siblings().invoke('remove');
                    this.growlerlog.down('UL').insert(
                        new Element('LI', { className: 'GrowlerNoAlerts' })
                            .insert(this.opts.noalerts));
                }.bind(this));
            }

            style = {
                bc: { bottom: 0, left: '25%', width: '50%' },
                bl: { top: 0, right: 0 },
                br: { bottom: 0, right: 0 },
                tc: { top: 0, left: '25%', width: '50%' },
                tl: { top: 0, left: 0 }
            };

            this.growler.setStyle(
                style[this.opts.location] || { top: 0, right: 0 }
            );
            this.growler.wrap(document.body);

            if (!opts.noclick) {
                this.growler.on('click', 'A', function(e) {
                    e.stop();
                    this.growler.fire('Growler:linkClick', e.element());
                }.bind(this));
            }
        },

        growl: function(msg, options)
        {
            var notice, log, tmp,
                opts = Object.clone(noticeOptions);
            Object.extend(opts, options || {});

            /* Check if sticky notice with same message already exists. */
            if (this.growler.select('> .GrowlerSticky').detect(function(n) {
                    return (n.down('.GrowlerNoticeBody').textContent == msg);
                })) {
                return;
            }

            if (opts.log && this.growlerlog) {
                tmp = this.growlerlog.down('DIV UL');
                if (tmp.down('.GrowlerNoAlerts')) {
                    tmp.down('.GrowlerNoAlerts').remove();
                }
                tmp.insert(
                    new Element('LI', {
                        className: opts.className.empty() ? null : opts.className
                    }).insert(msg).insert(new Element('SPAN', {
                        className: 'GrowlerAlertDate'}
                    ).insert('[' + (new Date()).toLocaleString() + ']'))
                );
            }

            notice = new Element('DIV', { className: 'GrowlerNotice' })
                .setStyle({ display: 'block', opacity: 0 })
                .store('so', opts.speedout);
            if (!opts.className.empty()) {
                notice.addClassName(opts.className);
            }

            notice.insert(new Element('DIV', {
                className: 'GrowlerNoticeExit'
            }).update("&times;"));

            if (!opts.header.empty()) {
                notice.insert(new Element('DIV', {
                    className: 'GrowlerNoticeHead'
                }).update(opts.header));
            }

            notice.insert(new Element('DIV', {
                className: 'GrowlerNoticeBody'
            }).update(msg));

            this.growler.show().insert(notice);

            new Effect.Opacity(notice, {
                to: opts.opacity,
                duration: opts.speedin
            });

            if (opts.sticky) {
                notice.addClassName('GrowlerSticky');
            } else {
                removeNotice.delay(opts.life, notice);
            }

            notice.fire('Growler:created');

            return notice;
        },

        ungrowl: function(n)
        {
            removeNotice(n);
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
            this.growlerlog.fire('Growler:toggled', {
                visible: this.logvisible
            });
            return this.logvisible;
        },

        logVisible: function()
        {
            return this.growlerlog && this.logvisible;
        },

        logSize: function()
        {
            return (this.growlerlog && this.growlerlog.down('.GrowlerNoAlerts'))
                ? 0
                : this.growlerlog.down('UL').childElements().size();
        }

    });

})();
