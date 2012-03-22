/**
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

var DimpMessage = {

    // Variables defaulting to empty/false: mbox, uid

    quickreply: function(type)
    {
        var func, ob = {};
        ob[this.mbox] = [ this.uid ];

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

        case 'forward_redirect':
            $('compose').hide();
            $('redirect').show();
            func = 'getRedirectData';
            break;
        }

        $('msgData').hide();
        $('qreply').show();

        DimpCore.doAction(func,
                          { imp_compose: $F('composeCache'),
                            type: type },
                          { uids: ob,
                            callback: this.msgTextCallback.bind(this) });
    },

    msgTextCallback: function(r)
    {
        switch (r.type) {
        case 'forward_redirect':
            if (r.imp_compose) {
                $('composeCacheRedirect').setValue(r.imp_compose);
            }
            break;

        default:
            if (r.imp_compose) {
                $('composeCache').setValue(r.imp_compose);
            }

            if (!r.opts) {
                r.opts = {};
            }
            r.opts.noupdate = true;
            r.opts.show_editor = (r.format == 'html');

            $('identity', 'last_identity').invoke('setValue', (r.identity === null) ? $F('identity') : r.identity);

            DimpCompose.fillForm(r.body, r.header, r.opts);
            break;
        }
    },

    updateAddressHeader: function(e)
    {
        var tmp = {};
        tmp[this.mbox] = [ this.uid ];

        DimpCore.doAction('addressHeader', {
            header: e.element().up('TR').identify().substring(9).toLowerCase()
        }, {
            callback: this._updateAddressHeaderCallback.bind(this),
            uids: tmp
        });
    },
    _updateAddressHeaderCallback: function(r)
    {
        $H(r.hdr_data).each(function(d) {
            this.updateHeader(d.key, d.value, 0);
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
    clickHandler: function(parentfunc, e)
    {
        if (e.isRightClick()) {
            return;
        }

        var elt = orig = e.element(), id, tmp;

        while (Object.isElement(elt)) {
            id = elt.readAttribute('id');

            switch (id) {
            case 'windowclose':
                window.close();
                e.stop();
                return;

            case 'forward_link':
            case 'reply_link':
                this.quickreply(id == 'reply_link' ? 'reply_auto' : 'forward_auto');
                e.stop();
                return;

            case 'button_delete':
            case 'button_ham':
            case 'button_spam':
                if (HordeCore.base.DimpBase) {
                    HordeCore.base.focus();
                    if (id == 'button_delete') {
                        HordeCore.base.DimpBase.deleteMsg({ uid: this.uid, mailbox: this.mbox });
                    } else {
                        HordeCore.base.DimpBase.reportSpam(id == 'button_spam', { uid: this.uid, mailbox: this.mbox });
                    }
                } else {
                    tmp = {};
                    tmp[this.mbox] = [ this.uid ];
                    if (id == 'button_delete') {
                        DimpCore.doAction('deleteMessages', {
                            view: this.mbox
                        }, {
                            uids: tmp
                        });
                    } else {
                        DimpCore.doAction('reportSpam', {
                            spam: Number(id == 'button_spam'),
                            view: this.mbox
                        }, {
                            uids: tmp
                        });
                    }
                }
                window.close();
                e.stop();
                return;

            case 'msgloglist_toggle':
            case 'partlist_toggle':
                tmp = (id == 'partlist_toggle') ? 'partlist' : 'msgloglist';
                $(tmp + '_col', tmp + '_exp').invoke('toggle');
                Effect.toggle(tmp, 'blind', {
                    afterFinish: function() {
                        this.resizeWindow();
                        $('msgData').down('DIV.messageBody').setStyle({ overflowY: 'auto' })
                    }.bind(this),
                    beforeSetup: function() {
                        $('msgData').down('DIV.messageBody').setStyle({ overflowY: 'hidden' })
                    },
                    duration: 0.2,
                    queue: {
                        position: 'end',
                        scope: tmp,
                        limit: 2
                    }
                });
                break;

            case 'msg_view_source':
                HordeCore.popupWindow(DIMP.conf.URI_VIEW, {
                    actionID: 'view_source',
                    id: 0,
                    mailbox: this.mbox,
                    uid: this.uid
                }, {
                    name: this.uid + '|' + this.mbox
                });
                break;

            case 'msg_all_parts':
                tmp = {};
                tmp[this.mbox] = [ this.uid ];
                DimpCore.doAction('messageMimeTree', { }, { uids: tmp, callback: this._mimeTreeCallback.bind(this) });
                break;

            case 'qreply':
                if (orig.match('DIV.headercloseimg IMG')) {
                    DimpCompose.confirmCancel();
                }
                break;

            case 'send_mdn_link':
                tmp = {};
                tmp[this.mbox] = [ this.uid ];
                DimpCore.doAction('sendMDN', {
                    uid: DimpCore.toUIDString(tmp)
                }, {
                    callback: function(r) {
                        $('sendMdnMessage').up(1).fade({ duration: 0.2 });
                    }
                });
                e.stop();
                return;

            default:
                if (elt.hasClassName('printAtc')) {
                    HordeCore.popupWindow(DIMP.conf.URI_VIEW, {
                        actionID: 'print_attach',
                        id: elt.readAttribute('mimeid'),
                        mailbox: this.mbox,
                        uid: this.uid
                    }, {
                        name: this.uid + '|' + this.mbox + '|print',
                        onload: IMP_JS.printWindow
                    });
                    e.stop();
                    return;
                } else if (elt.hasClassName('stripAtc')) {
                    DimpCore.reloadMessage({
                        actionID: 'strip_attachment',
                        mailbox: this.mbox,
                        id: elt.readAttribute('mimeid'),
                        uid: this.uid
                    });
                    e.stop();
                    return;
                }
                break;
            }

            elt = elt.up();
        }

        parentfunc(e);
    },

    contextOnClick: function(parentfunc, e)
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
        case 'ctx_forward_redirect':
            this.quickreply(id.substring(4));
            break;

        default:
            parentfunc(e);
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
        $('msgAtc').down('SPAN.atcLabel').update(DIMP.text.allparts_label);
        $('msgAtc').show();
    },

    onDomLoad: function()
    {
        DimpCore.init();

        if (DIMP.conf.disable_compose) {
            $('reply_link', 'forward_link').compact().invoke('up', 'SPAN').invoke('remove');
            delete DIMP.context.ctx_contacts['new'];
        } else {
            DimpCore.addPopdownButton('reply_link', 'reply');
            DimpCore.addPopdownButton('forward_link', 'forward');
            if (!this.reply_list) {
                delete DIMP.context.ctx_reply['reply_list'];
            }
        }

        /* Set up address linking. */
        [ 'from', 'to', 'cc', 'bcc', 'replyTo' ].each(function(a) {
            if (this[a]) {
                this.updateHeader(a, this[a], this.addr_limit && this.addr_limit[a] ? this.addr_limit[a] : 0);
                delete this[a];
            }
        }, this);
        delete this.addr_limit;

        /* Add message information. */
        if (this.log) {
            $('msgLogInfo').show();
            DimpCore.updateMsgLog(this.log);
        }

        if (HordeCore.base.DimpBase) {
            if (this.strip) {
                HordeCore.base.DimpBase.poll();
            } else if (this.tasks) {
                HordeCore.base.DimpBase.tasksHandler(this.tasks);
            }
        }

        $('dimpLoading').hide();
        $('pageContainer').show();

        this.resizeWindow();
    }

};

/* ContextSensitive functions. */
DimpCore.contextOnClick = DimpCore.contextOnClick.wrap(DimpMessage.contextOnClick.bind(DimpMessage));

/* Click handler. */
DimpCore.clickHandler = DimpCore.clickHandler.wrap(DimpMessage.clickHandler.bind(DimpMessage));

/* Attach event handlers. */
document.observe('dom:loaded', DimpMessage.onDomLoad.bind(DimpMessage));
Event.observe(window, 'resize', DimpMessage.resizeWindow.bind(DimpMessage));

/* DimpCore handlers. */
document.observe('DimpCore:updateAddressHeader', DimpMessage.updateAddressHeader.bindAsEventListener(DimpMessage));
