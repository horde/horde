/**
 * Dynamic message view.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2005-2014 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */

var ImpMessage = {

    // buid,
    // mbox,
    // msg_atc,
    // msg_md,

    quickreply: function(type)
    {
        var func;

        switch (type) {
        case 'reply':
        case 'reply_all':
        case 'reply_auto':
        case 'reply_list':
            $('compose').show();
            $('redirect').hide();
            func = 'getReplyData';
            break;

        case 'forward_auto':
        case 'forward_attach':
        case 'forward_body':
        case 'forward_both':
            $('compose').show();
            $('redirect').hide();
            func = 'getForwardData';
            break;

        case 'forward_editasnew':
            $('compose').show();
            $('redirect').hide();
            func = 'getResumeData';
            type = 'editasnew';
            break;

        case 'forward_redirect':
            $('compose').hide();
            $('redirect').show();
            func = 'getRedirectData';
            break;
        }

        $('msgData').hide();
        $('qreply').show();

        ImpCore.doAction(func, {
            imp_compose: $F('composeCache'),
            type: type,
            view: this.mbox
        }, {
            callback: function(r) {
                ImpCompose.fillForm(r);
                $(document).fire('AutoComplete:reset');
            },
            uids: [ this.buid ]
        });
    },

    updateAddressHeader: function(e)
    {
        ImpCore.doAction('addressHeader', {
            header: e.element().up('TR').identify().substring(9).toLowerCase(),
            view: this.mbox
        }, {
            callback: this._updateAddressHeaderCallback.bind(this),
            uids: [ this.buid ]
        });
    },
    _updateAddressHeaderCallback: function(r)
    {
        $H(r.hdr_data).each(function(d) {
            this.updateHeader(d.key, d.value);
        }, this);
    },

    updateHeader: function(hdr, data, limit)
    {
        // Can't use capitalize() here.
        var elt = $('msgHeader' + hdr.charAt(0).toUpperCase() + hdr.substring(1));
        if (elt) {
            elt.down('TD', 1).replace(ImpCore.buildAddressLinks(data, elt.down('TD', 1).clone(false), limit));
        }
    },

    reloadPart: function(mimeid, params)
    {
        ImpCore.doAction('inlineMessageOutput', Object.extend(params, {
            mimeid: mimeid,
            view: this.mbox
        }), {
            callback: function(r) {
                $('messageBody')
                    .down('DIV[impcontentsmimeid="' + r.mimeid + '"]')
                    .replace(r.text);
            },
            uids: [ this.buid ]
        });
    },

    /* Click handlers. */
    clickHandler: function(e)
    {
        var base;

        switch (e.element().readAttribute('id')) {
        case 'windowclose':
            window.close();
            e.memo.hordecore_stop = true;
            break;

        case 'forward_link':
            this.quickreply('forward_auto');
            e.memo.stop();
            break;

        case 'reply_link':
            this.quickreply('reply_auto');
            e.memo.stop();
            break;

        case 'button_delete':
        case 'button_innocent':
        case 'button_spam':
            if ((base = ImpCore.baseAvailable())) {
                base.focus();
                if (e.element().identify() == 'button_delete') {
                    base.DimpBase.deleteMsg({
                        mailbox: this.mbox,
                        uid: this.buid
                    });
                } else {
                    base.DimpBase.reportSpam(e.element().identify() == 'button_spam', {
                        mailbox: this.mbox,
                        uid: this.buid
                    });
                }
            } else {
                if (e.element().identify() == 'button_delete') {
                    ImpCore.doAction('deleteMessages', {
                        view: this.mbox
                    }, {
                        uids: [ this.buid ],
                        view: this.mbox
                    });
                } else {
                    ImpCore.doAction('reportSpam', {
                        spam: ~~(e.element().identify() == 'button_spam'),
                        view: this.mbox
                    }, {
                        uids: [ this.buid ],
                        view: this.mbox
                    });
                }
            }
            window.close();
            e.memo.hordecore_stop = true;
            break;

        case 'msg_view_source':
            HordeCore.popupWindow(ImpCore.conf.URI_VIEW, {
                actionID: 'view_source',
                buid: this.buid,
                id: 0,
                mailbox: this.mbox
            }, {
                name: this.buid + '|' + this.mbox
            });
            break;

        case 'msg_all_parts':
            ImpCore.doAction('messageMimeTree', {
                view: this.mbox
            }, {
                callback: this._mimeTreeCallback.bind(this),
                uids: [ this.buid ]
            });
            break;

        case 'qreply':
            if (e.memo.element().match('DIV.headercloseimg IMG')) {
                ImpCompose.confirmCancel();
            }
            break;

        case 'send_mdn_link':
            ImpCore.doAction('sendMDN', {
                view: this.mbox
            }, {
                callback: function(r) {
                    $('sendMdnMessage').up(1).fade({ duration: 0.2 });
                },
                uids: [ this.buid ]
            });
            e.memo.stop();
            break;

        default:
            if (e.element().hasClassName('printAtc')) {
                HordeCore.popupWindow(ImpCore.conf.URI_VIEW, {
                    actionID: 'print_attach',
                    buid: this.buid,
                    id: e.element().readAttribute('mimeid'),
                    mailbox: this.mbox
                }, {
                    name: this.buid + '|' + this.mbox + '|print',
                    onload: IMP_JS.printWindow
                });
                e.memo.stop();
            } else if (e.element().hasClassName('stripAtc')) {
                if (window.confirm(ImpCore.text.strip_warn)) {
                    ImpCore.reloadMessage({
                        actionID: 'strip_attachment',
                        buid: this.buid,
                        id: e.element().readAttribute('mimeid'),
                        mailbox: this.mbox
                    });
                }
                e.memo.stop();
            }
            break;
        }
    },

    contextOnClick: function(e)
    {
        var id = e.memo.elt.readAttribute('id');

        switch (id) {
        case 'ctx_reply_reply':
        case 'ctx_reply_reply_all':
        case 'ctx_reply_reply_list':
            this.quickreply(id.substring(10));
            break;

        case 'ctx_forward_attach':
        case 'ctx_forward_body':
        case 'ctx_forward_both':
        case 'ctx_forward_editasnew':
        case 'ctx_forward_redirect':
            this.quickreply(id.substring(4));
            break;
        }
    },

    resizeWindow: function()
    {
        var mb = $('msgData').down('DIV.messageBody');

        mb.setStyle({ height: Math.max(document.viewport.getHeight() - mb.cumulativeOffset()[1] - parseInt(mb.getStyle('paddingTop'), 10) - parseInt(mb.getStyle('paddingBottom'), 10), 0) + 'px' });
    },

    _mimeTreeCallback: function(r)
    {
        $('msg_all_parts').up().hide();

        $('partlist').show().update(r.tree);

        this.resizeWindow();
    },

    onDomLoad: function()
    {
        var base;

        HordeCore.initHandler('click');

        if (ImpCore.conf.disable_compose) {
            $('reply_link', 'forward_link').compact().invoke('up', 'SPAN').invoke('remove');
            delete ImpCore.context.ctx_contacts['new'];
        } else {
            ImpCore.addPopdown('reply_link', 'reply');
            ImpCore.addPopdown('forward_link', 'forward');
            if (!this.reply_list) {
                delete ImpCore.context.ctx_reply.reply_list;
            }
        }

        /* Set up address linking. */
        [ 'from', 'to', 'cc', 'bcc', 'replyTo' ].each(function(a) {
            if (this[a]) {
                this.updateHeader(a, this[a], true);
                delete this[a];
            }
        }, this);

        if ((base = ImpCore.baseAvailable())) {
            if (this.strip) {
                base.DimpBase.poll();
            } else if (this.tasks) {
                if (this.tasks['imp:maillog']) {
                    this.tasks['imp:maillog'].each(function(l) {
                        if (this.mbox == l.mbox &&
                            this.buid == l.buid) {
                            ImpCore.updateMsgLog(l.log);
                        }
                    }, this);
                    delete this.tasks['imp:maillog'];
                }
                base.DimpBase.tasksHandler({ tasks: this.tasks });
            }
        }

        ImpCore.msgMetadata(this.msg_md);
        delete this.msg_md;

        ImpCore.updateAtcList(this.msg_atc);
        delete this.msg_atc;

        $('dimpLoading').hide();
        $('msgData').show();

        this.resizeWindow();
    }

};

/* Attach event handlers. */
/* Initialize onload handler. */
document.observe('dom:loaded', function() {
    if (Prototype.Browser.IE && !document.addEventListener) {
        // IE 8
        IMP_JS.iframeResize = IMP_JS.iframeResize.wrap(function(parentfunc, e, id) {
            if ($('msgData').visible()) {
                (function() { parentfunc(e, id); }).defer();
            } else {
                IMP_JS.iframeResize.bind(IMP_JS, e, id).defer();
            }
        });
        ImpMessage.onDomLoad.bind(ImpMessage).delay(0.1);
    } else {
        ImpMessage.onDomLoad();
    }
});
document.observe('HordeCore:click', ImpMessage.clickHandler.bindAsEventListener(ImpMessage));
Event.observe(window, 'resize', ImpMessage.resizeWindow.bind(ImpMessage));

/* ContextSensitive events. */
document.observe('ContextSensitive:click', ImpMessage.contextOnClick.bindAsEventListener(ImpMessage));

/* ImpCore handlers. */
document.observe('ImpCore:updateAddressHeader', ImpMessage.updateAddressHeader.bindAsEventListener(ImpMessage));

/* Define reloadMessage() method for this page. */
ImpCore.reloadMessage = function(params) {
    window.location = HordeCore.addURLParam(document.location.href, params);
};

/* Define reloadPart() method for this page. */
ImpCore.reloadPart = ImpMessage.reloadPart.bind(ImpMessage);
