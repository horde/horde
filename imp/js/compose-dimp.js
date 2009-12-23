/**
 * compose.js - Javascript code used in the DIMP compose view.
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

var DimpCompose = {
    // Variables defaulting to empty/false:
    //   auto_save_interval, compose_cursor, disabled, drafts_mbox, editor_on,
    //   is_popup, knl_p, knl_sm, mp_padding, resizebcc, resizecc, resizeto,
    //   row_height, rte, skip_spellcheck, spellcheck, sc_submit, uploading
    last_msg: '',

    confirmCancel: function()
    {
        if (window.confirm(DIMP.text_compose.cancel)) {
            if ((this.is_popup || DIMP.conf_compose.popup) &&
                DIMP.baseWindow &&
                DIMP.baseWindow.DimpBase) {
                DIMP.baseWindow.focus();
            }
            DimpCore.doAction(DIMP.conf_compose.auto_save_interval_val ? 'DeleteDraft' : 'CancelCompose', { imp_compose: $F('composeCache') }, { ajaxopts: { asynchronous: DIMP.conf_compose.qreply } });
            this.updateDraftsMailbox();
            return this.closeCompose();
        }
    },

    updateDraftsMailbox: function()
    {
        if (this.is_popup &&
            DIMP.baseWindow.DimpBase.folder == DIMP.conf_compose.drafts_mbox) {
            DIMP.baseWindow.DimpBase.poll();
        }
    },

    closeCompose: function()
    {
        if (DIMP.conf_compose.qreply) {
            this.closeQReply();
        } else if (this.is_popup || DIMP.conf_compose.popup) {
            DimpCore.closePopup();
        } else {
            DimpCore.redirect(DIMP.conf.URI_DIMP);
        }
    },

    closeQReply: function()
    {
        var al = $('attach_list').childElements();
        this.last_msg = '';

        if (al.size()) {
            this.removeAttach(al);
        }

        $('composeCache').clear();
        $('qreply', 'sendcc', 'sendbcc').invoke('hide');
        [ $('msgData'), $('togglecc'), $('togglebcc') ].invoke('show');
        if (this.editor_on) {
            this.toggleHtmlEditor();
        }
        $('compose').reset();

        // Disable auto-save-drafts now.
        if (this.auto_save_interval) {
            this.auto_save_interval.stop();
        }
    },

    changeIdentity: function()
    {
        var lastSignature, msg, nextSignature, pos,
            id = $F('identity'),
            last = this.getIdentity($F('last_identity')),
            msgval = $('composeMessage'),
            next = this.getIdentity(id);

        this.setSentMailLabel(next.id[3], next.id[5], true);
        $('bcc').setValue(next.id[6]);
        this.setSaveSentMail(next.id[4]);

        // Finally try and replace the signature.
        if (this.editor_on) {
            msg = this.rte.getData().replace(/\r\n/g, '\n');
            lastSignature = '<p><!--begin_signature--><!--end_signature--></p>';
            nextSignature = '<p><!--begin_signature-->' + next.sig.replace(/^ ?<br \/>\n/, '').replace(/ +/g, ' ') + '<!--end_signature--></p>';

            // Dot-all functionality achieved with [\s\S], see:
            // http://simonwillison.net/2004/Sep/20/newlines/
            msg = msg.replace(/<p>\s*<!--begin_signature-->[\s\S]*?<!--end_signature-->\s*<\/p>/, lastSignature);
        } else {
            msg = $F(msgval).replace(/\r\n/g, '\n');
            lastSignature = last.sig;
            nextSignature = next.sig;
        }

        pos = (last.id[2])
            ? msg.indexOf(lastSignature)
            : msg.lastIndexOf(lastSignature);

        if (pos != -1) {
            if (next.id[2] == last.id[2]) {
                msg = msg.substring(0, pos) + nextSignature + msg.substring(pos + lastSignature.length, msg.length);
            } else if (next.id[2]) {
                msg = nextSignature + msg.substring(0, pos) + msg.substring(pos + lastSignature.length, msg.length);
            } else {
                msg = msg.substring(0, pos) + msg.substring(pos + lastSignature.length, msg.length) + nextSignature;
            }

            msg = msg.replace(/\r\n/g, '\n').replace(/\n/g, '\r\n');
            if (this.editor_on) {
                this.rte.setData(msg);
            } else {
                msgval.setValue(msg);
            }
            $('last_identity').setValue(id);
        }
    },

    setSaveSentMail: function(set)
    {
        var ssm = $('save_sent_mail'), tmp;

        if (ssm) {
            ssm.setValue(set);

            tmp = $('attach_cell').down('LABEL');
            if (tmp) {
                [ tmp ].invoke(set ? 'show' : 'hide');
            }
        }
    },

    setSentMailLabel: function(s, l, sel)
    {
        var label = $('sent_mail_folder_label');

        if (!label) {
            return;
        }

        if (!l) {
            l = DIMP.conf_compose.flist.find(function(f) {
                return f.v == s;
            });
            l = l.f || l.v;
        }

        $('save_sent_mail_folder').setValue(s);
        $('sent_mail_folder_label').writeAttribute('title', l.escapeHTML()).setText(l.truncate(15)).up(1).show();

        if (DIMP.conf_compose.flist && sel) {
            this.knl_sm.setSelected(s);
        }
    },

    setPriorityLabel: function(s, l)
    {
        var label = $('priority_label');

        if (!label) {
            return;
        }

        if (!l) {
            l = DIMP.conf_compose.priority.find(function(f) {
                return f.v == s;
            });
        }

        $('priority').setValue(s);
        $('priority_label').setText(l.l);
    },

    getIdentity: function(id, editor_on)
    {
        editor_on = Object.isUndefined(editor_on) ? this.editor_on : editor_on;
        return {
            id: DIMP.conf_compose.identities[id],
            sig: DIMP.conf_compose.identities[id][(editor_on ? 1 : 0)].replace(/^\n/, '')
        };
    },

    uniqueSubmit: function(action)
    {
        var c = $('compose');

        if (DIMP.SpellCheckerObject &&
            DIMP.SpellCheckerObject.isActive()) {
            DIMP.SpellCheckerObject.resume();
            this.skip_spellcheck = true;
        }

        if (action == 'send_message' || action == 'save_draft') {
            switch (action) {
            case 'send_message':
                if (($F('subject') == '') &&
                    !window.confirm(DIMP.text_compose.nosubject)) {
                    return;
                }

                if (!this.skip_spellcheck &&
                    DIMP.conf_compose.spellcheck &&
                    DIMP.SpellCheckerObject &&
                    !DIMP.SpellCheckerObject.isActive()) {
                    this.sc_submit = action;
                    DIMP.SpellCheckerObject.spellCheck();
                    return;
                }
                break;
            }

            // Don't send/save until uploading is completed.
            if (this.uploading) {
                (function() { if (this.disabled) { this.uniqueSubmit(action); } }).bind(this).delay(0.25);
                return;
            }
        }

        c.setStyle({ cursor: 'wait' });
        this.skip_spellcheck = false;
        $('action').setValue(action);

        if (action == 'add_attachment') {
            // We need a submit action here because browser security models
            // won't let us access files on user's filesystem otherwise.
            this.uploading = true;
            c.submit();
        } else {
            // Move HTML text to textarea field for submission.
            if (this.editor_on) {
                this.rte.updateElement();
            }

            // Use an AJAX submit here so that we can do javascript-y stuff
            // before having to close the window on success.
            DimpCore.doAction('*' + DIMP.conf.URI_COMPOSE, c.serialize(true), { callback: this.uniqueSubmitCallback.bind(this) });

            // Can't disable until we send the message - or else nothing
            // will get POST'ed.
            if (action == 'send_message' || action == 'save_draft') {
                this.setDisabled(true);
            }
        }
    },

    uniqueSubmitCallback: function(r)
    {
        var elt,
            d = r.response;

        if (!d) {
            return;
        }

        if (d.imp_compose) {
            $('composeCache').setValue(d.imp_compose);
        }

        if (d.success || d.action == 'add_attachment') {
            switch (d.action) {
            case 'auto_save_draft':
            case 'save_draft':
                this.setDisabled(false);

                this.updateDraftsMailbox();

                if (d.action == 'save_draft') {
                    if (this.is_popup && !DIMP.conf_compose.qreply) {
                        DIMP.baseWindow.DimpCore.showNotifications(r.msgs);
                        r.msgs = [];
                    }
                    if (DIMP.conf_compose.close_draft) {
                        return this.closeCompose();
                    }
                }
                break;

            case 'send_message':
                if (this.is_popup) {
                    if (d.reply_type) {
                        DIMP.baseWindow.DimpBase.flag(d.reply_type == 'reply' ? '\\answered' : '$forwarded', true, { uid: d.uid, mailbox: d.reply_folder, noserver: true });
                    }

                    // @TODO: Needed?
                    if (d.folder) {
                        DIMP.baseWindow.DimpBase.createFolder(d.folder);
                    }

                    if (d.draft_delete) {
                        DIMP.baseWindow.DimpBase.poll();
                    }

                    if (d.log) {
                        DIMP.baseWindow.DimpBase.updateMsgLog(d.log, { uid: d.uid, mailbox: d.reply_folder });
                    }

                    if (!DIMP.conf_compose.qreply) {
                        DIMP.baseWindow.DimpCore.showNotifications(r.msgs);
                        r.msgs = [];
                    }
                }
                return this.closeCompose();

            case 'add_attachment':
                this.uploading = false;
                if (d.success) {
                    this.addAttach(d.info.number, d.info.name, d.info.type, d.info.size);
                } else {
                    this.setDisabled(false);
                }
                if (DIMP.conf_compose.attach_limit != -1 &&
                    $('attach_list').childElements().size() > DIMP.conf_compose.attach_limit) {
                    $('upload').enable();
                    elt = new Element('DIV', [ DIMP.text_compose.atc_limit ]);
                } else {
                    elt = new Element('INPUT', { type: 'file', name: 'file_1' });
                }
                $('upload_wait').next().show();
                $('upload_wait').replace(elt.writeAttribute('id', 'upload'));
                this.resizeMsgArea();
                break;
            }
        }

        this.setDisabled(false);
        $('compose').setStyle({ cursor: null });
    },

    setDisabled: function(disable)
    {
        this.disabled = disable;
        DimpCore.loadingImg('sendingImg', 'composeMessageParent', disable);
        DimpCore.toggleButtons($('compose').select('DIV.dimpActions A'), disable);
        [ $('compose') ].invoke(disable ? 'disable' : 'enable');
        if (DIMP.SpellCheckerObject) {
            DIMP.SpellCheckerObject.disable(disable);
        }
        if (this.editor_on) {
            this.RTELoading(disable ? 'show' : 'hide', true);
        }
    },

    toggleHtmlEditor: function(noupdate)
    {
        if (!DIMP.conf_compose.rte_avail) {
            return;
        }
        noupdate = noupdate || false;
        if (DIMP.SpellCheckerObject) {
            DIMP.SpellCheckerObject.resume();
        }

        var config, text;

        if (this.editor_on) {
            this.editor_on = false;

            text = this.rte.getData();
            $('composeMessageParent').childElements().invoke('hide');
            $('composeMessage').show().setStyle({ visibility: null }).focus();
            this.RTELoading('show');

            DimpCore.doAction('Html2Text', { text: text }, { callback: this.setMessageText.bind(this), ajaxopts: { asynchronous: false } });

            this.RTELoading('hide');
        } else {
            this.editor_on = true;
            if (!noupdate) {
                DimpCore.doAction('Text2Html', { text: $F('composeMessage') }, { callback: this.setMessageText.bind(this), ajaxopts: { asynchronous: false } });
            }

            // Try to reuse the old fckeditor instance.
            try {
                this.rte.setData($F('composeMessage'));
                $('composeMessageParent').childElements().invoke('show');
                $('composeMessage').hide();
                this.resizeMsgArea();
            } catch (e) {
                config = Object.clone(IMP.ckeditor_config);
                if (!config.on) {
                    config.on = {};
                }
                config.on.instanceReady = function(evt) {
                    this.resizeMsgArea();
                    this.RTELoading('hide');
                    this.rte.focus();
                }.bind(this);
                this.RTELoading('show');
                this.rte = CKEDITOR.replace('composeMessage', config);
            }
        }
        $('htmlcheckbox').setValue(this.editor_on);
        $('html').setValue(this.editor_on ? 1 : 0);
    },

    RTELoading: function(cmd, notxt)
    {
        var o;

        if (!$('rteloading')) {
            $(document.body).insert(new Element('DIV', { id: 'rteloading' }).hide()).insert(new Element('SPAN', { id: 'rteloadingtxt' }).hide().insert(DIMP.text.loading));
        }

        if (cmd == 'hide') {
            $('rteloading', 'rteloadingtxt').invoke('hide');
        } else {
            $('rteloading').clonePosition('composeMessageParent').show();
            if (!notxt) {
                o = $('rteloading').viewportOffset();
                $('rteloadingtxt').setStyle({ top: (o.top + 15) + 'px', left: (o.left + 15) + 'px' }).show();
            }
        }
    },

    getMsgAreaHeight: function()
    {
        if (!this.mp_padding) {
            this.mp_padding = $('composeMessageParent').getHeight() - $('composeMessage').getHeight();
        }

        return document.viewport.getHeight() - $('composeMessageParent').cumulativeOffset()[1] - this.mp_padding;
    },

    initializeSpellChecker: function()
    {
        document.observe('SpellChecker:noerror', this._onSpellCheckNoError.bind(this));

        if (DIMP.conf_compose.rte_avail) {
            document.observe('SpellChecker:after', this._onSpellCheckAfter.bind(this));
            document.observe('SpellChecker:before', this._onSpellCheckBefore.bind(this));
        }
    },

    _onSpellCheckAfter: function()
    {
        if (this.editor_on) {
            this.rte.setData($F('composeMessage'));
            $('composeMessage').next().show();
        }
        this.sc_submit = false;
    },

    _onSpellCheckBefore: function()
    {
        DIMP.SpellCheckerObject.htmlAreaParent = this.editor_on
            ? 'composeMessageParent'
            : null;

        if (this.editor_on) {
            this.rte.updateElement();
            $('composeMessage').next().hide();
        }
    },

    _onSpellCheckNoError: function()
    {
        if (this.sc_submit) {
            this.skip_spellcheck = true;
            this.uniqueSubmit(this.sc_submit);
        } else {
            DimpCore.showNotifications([ { type: 'horde.message', message: DIMP.text_compose.spell_noerror } ]);
            this._onSpellCheckAfter();
        }
    },

    setMessageText: function(r)
    {
        var ta = $('composeMessage');
        if (!ta) {
            $('composeMessageParent').insert(new Element('TEXTAREA', { id: 'composeMessage', name: 'message', style: 'width:100%;' }).insert(r.response.text));
        } else {
            ta.setValue(r.response.text);
        }

        if (!this.editor_on) {
            this.resizeMsgArea();
        }
    },

    fillForm: function(msg, header, focus, noupdate)
    {
        // On IE, this can get loaded before DOM:loaded. Check for an init
        // value and don't load until it is available.
        if (!this.resizeto) {
            this.fillForm.bind(this, msg, header, focus, noupdate).defer();
            return;
        }

        var bcc_add,
            identity = this.getIdentity($F('last_identity')),
            msgval = $('composeMessage');

        if (!this.last_msg.empty() &&
            this.last_msg != $F(msgval).replace(/\r/g, '') &&
            !window.confirm(DIMP.text_compose.fillform)) {
            return;
        }

        // Set auto-save-drafts now if not already active.
        if (DIMP.conf_compose.auto_save_interval_val &&
            !this.auto_save_interval) {
            this.auto_save_interval = new PeriodicalExecuter(function() {
                var cur_msg = this.editor_on
                    ? this.rte.getData()
                    : $F(msgval);
                cur_msg = cur_msg.replace(/\r/g, '');
                if (!cur_msg.empty() && this.last_msg != cur_msg) {
                    this.uniqueSubmit('auto_save_draft');
                    this.last_msg = cur_msg;
                }
            }.bind(this), DIMP.conf_compose.auto_save_interval_val * 60);
        }

        if (this.editor_on) {
            this.rte.setData(msg);
            this.last_msg = this.rte.getData().replace(/\r/g, '');
        } else {
            msgval.setValue(msg);
            this.setCursorPosition(msgval);
            this.last_msg = $F(msgval).replace(/\r/g, '');
        }

        $('to').setValue(header.to);
        this.resizeto.resizeNeeded();
        if (header.cc) {
            $('cc').setValue(header.cc);
            this.resizecc.resizeNeeded();
        }
        if (DIMP.conf_compose.cc) {
            this.toggleCC('cc', true);
        }
        this.setSentMailLabel(identity.id[3], identity.id[5], true);
        this.setSaveSentMail(identity.id[4]);
        if (header.bcc) {
            $('bcc').setValue(header.bcc);
            this.resizebcc.resizeNeeded();
        }
        if (identity.id[6]) {
            bcc_add = $F('bcc');
            if (bcc_add) {
                bcc_add += ', ';
            }
            $('bcc').setValue(bcc_add + identity.id[6]);
        }
        if (DIMP.conf_compose.bcc) {
            this.toggleCC('bcc', true);
        }
        $('subject').setValue(header.subject);

        Field.focus(focus || 'to');
        this.resizeMsgArea();

        if (DIMP.conf_compose.show_editor) {
            if (!this.editor_on) {
                this.toggleHtmlEditor(noupdate || false);
            }
            if (focus == 'composeMessage') {
                this.focusEditor();
            }
        }
    },

    focusEditor: function()
    {
        try {
            this.rte.focus();
        } catch (e) {
            this.focusEditor.bind(this).defer();
        }
    },

    addAttach: function(atc_num, name, type, size)
    {
        var span = new Element('SPAN').insert(name),
            li = new Element('LI').insert(span).insert(' [' + type + '] (' + size + ' KB) '),
            input = new Element('SPAN', { atc_id: atc_num, className: 'remove' }).insert(DIMP.text_compose.remove);
        li.insert(input);
        $('attach_list').insert(li).show();

        if (type != 'application/octet-stream') {
            span.addClassName('attachName');
        }

        this.resizeMsgArea();
    },

    removeAttach: function(e)
    {
        var ids = [];
        e.each(function(n) {
            n = $(n);
            ids.push(n.down('SPAN.remove').readAttribute('atc_id'));
            n.fade({
                afterFinish: function() {
                    n.remove();
                    this.resizeMsgArea();
                }.bind(this),
                duration: 0.4
            });
        }, this);
        if (!$('attach_list').childElements().size()) {
            $('attach_list').hide();
        }
        DimpCore.doAction('DeleteAttach', { atc_indices: ids, imp_compose: $F('composeCache') });
    },

    resizeMsgArea: function()
    {
        var m, rows,
            de = document.documentElement,
            msg = $('composeMessage');

        if (!document.loaded) {
            this.resizeMsgArea.bind(this).defer();
            return;
        }

        if (this.editor_on) {
            this.rte.resize('100%', this.getMsgAreaHeight(), false);
        }

        if (!this.row_height) {
            // Change the ID and name to not conflict with msg node.
            m = $(msg.cloneNode(false)).writeAttribute({ id: null, name: null }).setStyle({ visibility: 'hidden' });
            $(document.body).insert(m);
            m.writeAttribute('rows', 1);
            this.row_height = m.getHeight();
            m.writeAttribute('rows', 2);
            this.row_height = m.getHeight() - this.row_height;
            m.remove();
        }

        /* Logic: Determine the size of a given textarea row, divide that size
         * by the available height, round down to the lowest integer row, and
         * resize the textarea. */
        rows = parseInt(this.getMsgAreaHeight() / this.row_height);
        msg.writeAttribute({ rows: rows, disabled: false });
        if (de.scrollHeight - de.clientHeight) {
            msg.writeAttribute({ rows: rows - 1 });
        }
    },

    uploadAttachment: function()
    {
        var u = $('upload');
        this.uniqueSubmit('add_attachment');
        u.next().hide();
        u.replace(new Element('SPAN', { id: 'upload_wait' }).insert(DIMP.text_compose.uploading + ' (' + $F(u) + ')'));
    },

    attachmentComplete: function()
    {
        var sf = $('submit_frame'),
            doc = sf.contentDocument || sf.contentWindow.document;
        DimpCore.doActionComplete({ responseJSON: doc.body.innerHTML.evalJSON(true) }, this.uniqueSubmitCallback.bind(this));
    },

    toggleCC: function(type, immediate)
    {
        var t = $('toggle' + type);

        $('send' + type).show();
        if (immediate) {
            t.hide();
        } else {
            t.fade({ duration: 0.4 });
        }
    },

    /* Sets the cursor to the given position. */
    setCursorPosition: function(input)
    {
        var pos, range;

        switch (DIMP.conf_compose.compose_cursor) {
        case 'top':
            pos = 0;
            $('composeMessage').setValue('\n' + $F('composeMessage'));
            break;

        case 'bottom':
            pos = $F('composeMessage').length;
            break;

        case 'sig':
            pos = $F('composeMessage').replace(/\r\n/g, '\n').lastIndexOf(this.getIdentity($F('last_identity')).sig) - 1;
            break;

        default:
            return;
        }

        if (input.setSelectionRange) {
            /* This works in Mozilla */
            Field.focus(input);
            input.setSelectionRange(pos, pos);
            if (pos) {
                (function() { input.scrollTop = input.scrollHeight - input.offsetHeight; }).defer();
            }
        } else if (input.createTextRange) {
            /* This works in IE */
            range = input.createTextRange();
            range.collapse(true);
            range.moveStart('character', pos);
            range.moveEnd('character', 0);
            Field.select(range);
            range.scrollIntoView(true);
        }
    },

    /* Open the addressbook window. */
    openAddressbook: function()
    {
        window.open(DIMP.conf_compose.URI_ABOOK, 'contacts', 'toolbar=no,location=no,status=no,scrollbars=yes,resizable=yes,width=550,height=300,left=100,top=100');
    },

    /* Click observe handler. */
    clickHandler: function(parentfunc, e)
    {
        if (e.isRightClick()) {
            return;
        }

        var elt = orig = e.element(),
            atc_num, id;

        while (Object.isElement(elt)) {
            id = elt.readAttribute('id');

            switch (id) {
            case 'togglebcc':
            case 'togglecc':
                this.toggleCC(id.substring(6));
                this.resizeMsgArea();
                break;

            case 'compose_close':
                this.confirmCancel();
                break;

            case 'draft_button':
            case 'send_button':
                if (!this.disabled) {
                    this.uniqueSubmit(id == 'send_button' ? 'send_message' : 'save_draft');
                }
                break;

            case 'htmlcheckbox':
                if (!this.editor_on || window.confirm(DIMP.text_compose.toggle_html)) {
                    this.toggleHtmlEditor();
                } else {
                    $('htmlcheckbox').setValue(true);
                }
                break;

            case 'sendcc':
            case 'sendbcc':
            case 'sendto':
                if (orig.match('TD.label SPAN')) {
                    this.openAddressbook();
                }
                break;

            case 'attach_list':
                if (orig.match('SPAN.remove')) {
                    this.removeAttach([ orig.up() ]);
                } else if (orig.match('SPAN.attachName')) {
                    atc_num = orig.next().readAttribute('atc_id');
                    DimpCore.popupWindow(DimpCore.addURLParam(DIMP.conf.URI_VIEW, { composeCache: $F('composeCache'), actionID: 'compose_attach_preview', id: atc_num }), $F('composeCache') + '|' + atc_num);
                }
                break;

            case 'save_sent_mail':
                this.setSaveSentMail($F(elt));
                break;
            }

            elt = elt.up();
        }

        parentfunc(e);
    },

    changeHandler: function(e)
    {
        var elt = e.element(),
            id = elt.readAttribute('id');

        switch (id) {
        case 'identity':
            this.changeIdentity();
            break;

        case 'upload':
            this.uploadAttachment();
            break;
        }
    },

    onDomLoad: function()
    {
        var boundResize = this.resizeMsgArea.bind(this);

        DimpCore.growler_log = false;
        DimpCore.init();

        this.is_popup = (DIMP.baseWindow && DIMP.baseWindow.DimpBase);

        /* Attach event handlers. */
        document.observe('change', this.changeHandler.bindAsEventListener(this));
        Event.observe(window, 'resize', this.resizeMsgArea.bind(this));
        $('compose').observe('submit', Event.stop);
        $('submit_frame').observe('load', this.attachmentComplete.bind(this));

        this.resizeMsgArea();
        this.initializeSpellChecker();

        // Automatically resize address fields.
        this.resizeto = new ResizeTextArea('to', boundResize);
        this.resizecc = new ResizeTextArea('cc', boundResize);
        this.resizebcc = new ResizeTextArea('bcc', boundResize);

        /* Add addressbook link formatting. */
        if (DIMP.conf_compose.URI_ABOOK) {
            $('sendto', 'sendcc', 'sendbcc').each(function(a) {
                a.down('TD.label SPAN').addClassName('composeAddrbook');
            });
        }

        /* Create folderlist. */
        if (DIMP.conf_compose.flist) {
            this.knl_sm = new KeyNavList('save_sent_mail', {
                esc: true,
                list: DIMP.conf_compose.flist,
                onChoose: this.setSentMailLabel.bind(this)
            });
            this.knl_sm.setSelected(this.getIdentity($F('identity'))[3]);
            $('sent_mail_folder_label').insert({ after: new Element('SPAN', { className: 'popdownImg' }).observe('click', function(e) { if (!this.disabled) { this.knl_sm.show(); this.knl_sm.ignoreClick(e); e.stop(); } }.bindAsEventListener(this)) });
        }

        /* Create priority list. */
        if (DIMP.conf_compose.priority) {
            this.knl_p = new KeyNavList('priority_label', {
                esc: true,
                list: DIMP.conf_compose.priority,
                onChoose: this.setPriorityLabel.bind(this)
            });
            this.setPriorityLabel('normal');
            $('priority_label').insert({ after: new Element('SPAN', { className: 'popdownImg' }).observe('click', function(e) { if (!this.disabled) { this.knl_p.show(); this.knl_p.ignoreClick(e); e.stop(); } }.bindAsEventListener(this)) });
        }

        $('dimpLoading').hide();
        $('pageContainer').show();

        // Safari requires a submit target iframe to be at least 1x1 size or
        // else it will open content in a new window.  See:
        //   http://blog.caboo.se/articles/2007/4/2/ajax-file-upload
        if (Prototype.Browser.WebKit) {
            $('submit_frame').writeAttribute({ position: 'absolute', width: '1px', height: '1px' }).setStyle({ left: '-999px' }).show();
        }
    }

},

ResizeTextArea = Class.create({
    // Variables defaulting to empty:
    //   defaultRows, field, onResize
    maxRows: 5,

    initialize: function(field, onResize)
    {
        this.field = $(field);

        this.defaultRows = Math.max(this.field.readAttribute('rows'), 1);
        this.onResize = onResize;

        var func = this.resizeNeeded.bindAsEventListener(this);
        this.field.observe('mousedown', func).observe('keyup', func);

        this.resizeNeeded();
    },

    resizeNeeded: function()
    {
        var lines = $F(this.field).split('\n'),
            cols = this.field.readAttribute('cols'),
            newRows = lines.size(),
            oldRows = this.field.readAttribute('rows');

        lines.each(function(line) {
            if (line.length >= cols) {
                newRows += Math.floor(line.length / cols);
            }
        });

        if (newRows != oldRows) {
            this.field.writeAttribute('rows', (newRows > oldRows) ? Math.min(newRows, this.maxRows) : Math.max(this.defaultRows, newRows));

            if (this.onResize) {
                this.onResize();
            }
        }
    }

});

/* Attach event handlers. */
document.observe('dom:loaded', DimpCompose.onDomLoad.bind(DimpCompose));

/* Click handler. */
DimpCore.clickHandler = DimpCore.clickHandler.wrap(DimpCompose.clickHandler.bind(DimpCompose));
