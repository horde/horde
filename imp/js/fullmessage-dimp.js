/**
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

var DimpFullmessage = {

    // Variables defaulting to empty/false: mailbox, uid
    quickreply: function(type)
    {
        var func, ob = {};
        ob[this.mailbox] = [ this.uid ];

        $('msgData').hide();
        $('qreply').show();

        switch (type) {
        case 'reply':
        case 'reply_all':
        case 'reply_list':
            func = 'GetReplyData';
            break;

        case 'forward':
            func = 'GetForwardData';
            break;
        }

        DimpCore.doAction(func,
                          { imp_compose: $F('composeCache'),
                            type: type },
                          { uids: ob,
                            callback: this.msgTextCallback.bind(this) });
    },

    msgTextCallback: function(result)
    {
        if (!result.response) {
            return;
        }

        var r = result.response,
            editor_on = ((r.format == 'html') && !DimpCompose.editor_on),
            id = (r.identity === null) ? $F('identity') : r.identity,
            i = DimpCompose.getIdentity(id, editor_on);

        $('identity', 'last_identity').invoke('setValue', id);

        DimpCompose.fillForm((i.id[2]) ? ("\n" + i.sig + r.body) : (r.body + "\n" + i.sig), r.header);

        if (r.fwd_list && r.fwd_list.length) {
            r.fwd_list.each(function(ptr) {
                DimpCompose.addAttach(ptr.number, ptr.name, ptr.type, ptr.size);
            });
        }

        if (editor_on) {
            DimpCompose.toggleHtmlEditor(true);
        }

        if (r.imp_compose) {
            $('composeCache').setValue(r.imp_compose);
        }
    },

    /* Click handlers. */
    clickHandler: function(parentfunc, e)
    {
        if (e.isRightClick()) {
            return;
        }

        var elt = orig = e.element(), id;

        while (Object.isElement(elt)) {
            id = elt.readAttribute('id');

            switch (id) {
            case 'windowclose':
                window.close();
                e.stop();
                return;

            case 'forward_link':
            case 'reply_link':
                this.quickreply(id == 'reply_link' ? 'reply' : 'forward');
                e.stop();
                return;

            case 'button_deleted':
            case 'button_ham':
            case 'button_spam':
                if (id == 'button_deleted') {
                    if (DIMP.baseWindow && DIMP.baseWindow.DimpBase) {
                        DIMP.baseWindow.DimpBase.deleteMsg({ uid: this.uid, mailbox: this.mailbox });
                    } else {
                        DIMP.baseWindow.DimpBase.reportSpam(id == 'button_spam', { uid: this.uid, mailbox: this.mailbox });
                    }
                    window.close();
                }
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
                DimpCore.popupWindow(DimpCore.addURLParam(DIMP.conf.URI_VIEW, { uid: this.uid, mailbox: this.mailbox, actionID: 'view_source', id: 0 }, true), this.uid + '|' + this.mailbox);
                break;

            case 'qreply':
                if (orig.match('DIV.headercloseimg IMG')) {
                    DimpCompose.confirmCancel();
                }
                break;
            }

            elt = elt.up();
        }

        parentfunc(e);
    },

    contextOnClick: function(parentfunc, elt, baseelt, menu)
    {
        var id = elt.readAttribute('id');

        switch (id) {
        case 'ctx_reply_reply':
        case 'ctx_reply_reply_all':
        case 'ctx_reply_reply_list':
            this.quickreply(id.substring(10));
            break;

        default:
            parentfunc(elt, baseelt, menu);
            break;
        }
    },

    /* Add a popdown menu to a dimpactions button. */
    addPopdown: function(bid, ctx)
    {
        var bidelt = $(bid);
        bidelt.insert({ after: new Element('SPAN', { className: 'iconImg popdownImg popdown', id: bid + '_img' }) });
        DimpCore.DMenu.addElement(bid + '_img', 'ctx_' + ctx, { offset: bidelt.up(), left: true });
    },

    resizeWindow: function()
    {
        var mb = $('msgData').down('DIV.messageBody');

        mb.setStyle({ height: (document.viewport.getHeight() - mb.cumulativeOffset()[1] - parseInt(mb.getStyle('paddingTop'), 10) - parseInt(mb.getStyle('paddingBottom'), 10)) + 'px' });
    },

    onDomLoad: function()
    {
        DimpCore.growler_log = false;
        DimpCore.init();

        if (DIMP.conf.disable_compose) {
            tmp = $('reply_link', 'forward_link').compact().invoke('up', 'SPAN').concat([ $('ctx_contacts_new') ]).compact().invoke('remove');
        } else {
            this.addPopdown('reply_link', 'replypopdown');
        }

        /* Set up address linking. */
        [ 'from', 'to', 'cc', 'bcc', 'replyTo' ].each(function(a) {
            if (this[a]) {
                var elt = $('msgHeader' + a.charAt(0).toUpperCase() + a.substring(1)).down('TD', 1);
                elt.replace(DimpCore.buildAddressLinks(this[a], elt.cloneNode(false)));
            }
        }, this);

        /* Add message information. */
        if (this.log) {
            $('msgLogInfo').show();
            DimpCore.updateMsgLog(this.log);
        }

        $('dimpLoading').hide();
        $('pageContainer').show();

        this.resizeWindow();
    }

};

/* ContextSensitive functions. */
DimpCore.contextOnClick = DimpCore.contextOnClick.wrap(DimpFullmessage.contextOnClick.bind(DimpFullmessage));

/* Click handler. */
DimpCore.clickHandler = DimpCore.clickHandler.wrap(DimpFullmessage.clickHandler.bind(DimpFullmessage));

/* Attach event handlers. */
document.observe('dom:loaded', DimpFullmessage.onDomLoad.bind(DimpFullmessage));
Event.observe(window, 'resize', DimpFullmessage.resizeWindow.bind(DimpFullmessage));
