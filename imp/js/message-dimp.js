/**
 * Copyright 2005-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

var DimpMessage = {

    // Variables defaulting to empty/false: buid, mbox

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

        DimpCore.doAction(func, {
            imp_compose: $F('composeCache'),
            type: type,
            view: this.mbox
        }, {
            callback: function(r) {
                DimpCompose.fillForm(r);
                $(document).fire('AutoComplete:reset');
            },
            uids: [ this.buid ]
        });
    },

    updateAddressHeader: function(e)
    {
        DimpCore.doAction('addressHeader', {
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
            elt.down('TD', 1).replace(DimpCore.buildAddressLinks(data, elt.down('TD', 1).clone(false), limit));
        }
    },

    /* Click handlers. */
    clickHandler: function(e)
    {
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
            if (HordeCore.base.DimpBase) {
                HordeCore.base.focus();
                if (e.element().identify() == 'button_delete') {
                    HordeCore.base.DimpBase.deleteMsg({
                        mailbox: this.mbox,
                        uid: this.buid
                    });
                } else {
                    HordeCore.base.DimpBase.reportSpam(e.element().identify() == 'button_spam', {
                        mailbox: this.mbox,
                        uid: this.buid
                    });
                }
            } else {
                if (e.element().identify() == 'button_delete') {
                    DimpCore.doAction('deleteMessages', {
                        view: this.mbox
                    }, {
                        uids: [ this.buid ],
                        view: this.mbox
                    });
                } else {
                    DimpCore.doAction('reportSpam', {
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
            HordeCore.popupWindow(DimpCore.conf.URI_VIEW, {
                actionID: 'view_source',
                buid: this.buid,
                id: 0,
                mailbox: this.mbox
            }, {
                name: this.buid + '|' + this.mbox
            });
            break;

        case 'msg_all_parts':
            DimpCore.doAction('messageMimeTree', {
                view: this.mbox
            }, {
                callback: this._mimeTreeCallback.bind(this),
                uids: [ this.buid ]
            });
            break;

        case 'qreply':
            if (e.memo.element().match('DIV.headercloseimg IMG')) {
                DimpCompose.confirmCancel();
            }
            break;

        case 'send_mdn_link':
            DimpCore.doAction('sendMDN', {}, {
                callback: function(r) {
                    $('sendMdnMessage').up(1).fade({ duration: 0.2 });
                },
                uids: [ this.buid ],
                view: this.mbox
            });
            e.memo.stop();
            break;

        default:
            if (e.element().hasClassName('printAtc')) {
                HordeCore.popupWindow(DimpCore.conf.URI_VIEW, {
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
                if (window.confirm(DimpCore.text.strip_warn)) {
                    DimpCore.reloadMessage({
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

        $('partlist').update(r.tree);
        $('msgHeaderAtc').down('TD.label').update(DimpCore.text.allparts_label + ':');
        $('msgHeaderAtc').show();

        this.resizeWindow();
    },

    onDomLoad: function()
    {
        HordeCore.initHandler('click');

        if (DimpCore.conf.disable_compose) {
            $('reply_link', 'forward_link').compact().invoke('up', 'SPAN').invoke('remove');
            delete DimpCore.context.ctx_contacts['new'];
        } else {
            DimpCore.addPopdown('reply_link', 'reply');
            DimpCore.addPopdown('forward_link', 'forward');
            if (!this.reply_list) {
                delete DimpCore.context.ctx_reply.reply_list;
            }
        }

        /* Set up address linking. */
        [ 'from', 'to', 'cc', 'bcc', 'replyTo' ].each(function(a) {
            if (this[a]) {
                this.updateHeader(a, this[a], true);
                delete this[a];
            }
        }, this);
        delete this.addr_limit;

        if (HordeCore.base.DimpBase) {
            if (this.strip) {
                HordeCore.base.DimpBase.poll();
            } else if (this.tasks) {
                if (this.tasks['imp:maillog']) {
                    this.tasks['imp:maillog'].each(function(l) {
                        if (this.mbox == l.mbox &&
                            this.buid == l.buid) {
                            DimpCore.updateMsgLog(l.log);
                            $('msgloglist').show();
                        }
                    }, this);
                    delete this.tasks['imp:maillog'];
                }
                HordeCore.base.DimpBase.tasksHandler({ tasks: this.tasks });
            }
        }

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
        DimpMessage.onDomLoad.bind(DimpMessage).delay(0.1);
    } else {
        DimpMessage.onDomLoad();
    }
});
document.observe('HordeCore:click', DimpMessage.clickHandler.bindAsEventListener(DimpMessage));
Event.observe(window, 'resize', DimpMessage.resizeWindow.bind(DimpMessage));

/* ContextSensitive events. */
document.observe('ContextSensitive:click', DimpMessage.contextOnClick.bindAsEventListener(DimpMessage));

/* DimpCore handlers. */
document.observe('DimpCore:updateAddressHeader', DimpMessage.updateAddressHeader.bindAsEventListener(DimpMessage));

/* Define reloadMessage() method for this page. */
DimpCore.reloadMessage = function(params) {
    window.location = HordeCore.addURLParam(document.location.href, params);
};
