/**
 * compose.js - Javascript code used in the DIMP compose view.
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

var DimpCompose = {
    // Variables defaulting to empty/false:
    //   auto_save_interval, compose_cursor, disabled, drafts_mbox,
    //   editor_wait, is_popup, knl, last_msg, loaded, old_action,
    //   old_identity, rte, skip_spellcheck, spellcheck, sc_submit, uploading

    knl: {},

    confirmCancel: function()
    {
        if (window.confirm(DIMP.text_compose.cancel)) {
            if ((this.is_popup || DIMP.conf_compose.popup) &&
                DIMP.baseWindow &&
                DIMP.baseWindow.DimpBase &&
                !DIMP.conf_compose.qreply) {
                DIMP.baseWindow.focus();
            }
            DimpCore.doAction(DIMP.conf_compose.auto_save_interval_val ? 'deleteDraft' : 'cancelCompose', { imp_compose: $F('composeCache') }, { ajaxopts: { asynchronous: DIMP.conf_compose.qreply } });
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
        if (IMP_Compose_Base.editor_on) {
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
        var identity = IMP_Compose_Base.getIdentity($F('identity'));

        this.setPopdownLabel('sm', identity.id.smf_name, identity.id.smf_display);
        $('bcc').setValue(identity.id.bcc);
        this.setSaveSentMail(identity.id.smf_save);

        IMP_Compose_Base.replaceSignature($F('identity'));
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

    createPopdown: function(id, opts)
    {
        this.knl[id] = {
            knl: new KeyNavList(opts.base, {
                esc: true,
                list: opts.data,
                onChoose: this.setPopdownLabel.bind(this, id)
            }),
            opts: opts
        };

        $(opts.label).insert({ after: new Element('SPAN', { className: 'popdownImg' }).observe('click', function(e) { if (!this.disabled) { this.knl[id].knl.show(); this.knl[id].knl.ignoreClick(e); e.stop(); } }.bindAsEventListener(this)) });
    },

    setPopdownLabel: function(id, s, l)
    {
        var k = this.knl[id];

        if (!k) {
            return;
        }

        if (!l) {
            l = k.opts.data.find(function(f) {
                return f.v == s;
            });

            if (!l) {
                return;
            }

            l = (id == 'sm')
                ? (l.f || l.v)
                : l.l;
        }

        $(k.opts.input).setValue(s);
        $(k.opts.label).writeAttribute('title', l.escapeHTML()).setText(l.truncate(15)).up(1).show();

        if (id == 'sm') {
            k.knl.setSelected(s);
        }
    },

    retrySubmit: function(action)
    {
        if (this.old_action) {
            this.uniqueSubmit(this.old_action);
            this.old_action = null;
        }
    },

    uniqueSubmit: function(action)
    {
        var c = (action == 'redirectMessage')
            ? $('redirect')
            : $('compose');

        if (DIMP.SpellChecker &&
            DIMP.SpellChecker.isActive()) {
            DIMP.SpellChecker.resume();
            this.skip_spellcheck = true;
        }

        if (this.editor_wait && IMP_Compose_Base.editor_on) {
            return this.uniqueSubmit.bind(this, action).defer();
        }

        if (action == 'sendMessage' || action == 'saveDraft') {
            switch (action) {
            case 'sendMessage':
                if (!this.skip_spellcheck &&
                    DIMP.conf_compose.spellcheck &&
                    DIMP.SpellChecker &&
                    !DIMP.SpellChecker.isActive()) {
                    this.sc_submit = action;
                    DIMP.SpellChecker.spellCheck();
                    return;
                }

                if (($F('subject') == '') &&
                    !window.confirm(DIMP.text_compose.nosubject)) {
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

        if (action == 'addAttachment') {
            // We need a submit action here because browser security models
            // won't let us access files on user's filesystem otherwise.
            this.uploading = true;
            c.submit();
        } else {
            // Move HTML text to textarea field for submission.
            if (IMP_Compose_Base.editor_on) {
                this.rte.updateElement();
            }

            // Use an AJAX submit here so that we can do javascript-y stuff
            // before having to close the window on success.
            DimpCore.doAction(action, c.serialize(true), { callback: this.uniqueSubmitCallback.bind(this) });

            // Can't disable until we send the message - or else nothing
            // will get POST'ed.
            if (action != 'autoSaveDraft') {
                this.setDisabled(true);
            }
        }
    },

    uniqueSubmitCallback: function(r)
    {
        var d = r.response;

        if (!d) {
            return;
        }

        if (d.imp_compose) {
            $('composeCache').setValue(d.imp_compose);
        }

        if (d.success || d.action == 'addAttachment') {
            switch (d.action) {
            case 'autoSaveDraft':
            case 'saveDraft':
                this.updateDraftsMailbox();

                if (d.action == 'saveDraft') {
                    if (this.is_popup && !DIMP.conf_compose.qreply) {
                        DIMP.baseWindow.DimpCore.showNotifications(r.msgs);
                        r.msgs = [];
                    }
                    if (DIMP.conf_compose.close_draft) {
                        return this.closeCompose();
                    }
                }
                break;

            case 'sendMessage':
                if (this.is_popup && DIMP.baseWindow.DimpBase) {
                    if (d.reply_type) {
                        DIMP.baseWindow.DimpBase.flag(d.reply_type == 'forward' ? '$forwarded' : '\\answered', true, { uid: d.uid, mailbox: d.reply_folder, noserver: true });
                    }

                    if (d.mailbox) {
                        DIMP.baseWindow.DimpBase.mailboxCallback(r);
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

            case 'redirectMessage':
                if (this.is_popup && DIMP.baseWindow.DimpBase) {
                    if (d.log) {
                        DIMP.baseWindow.DimpBase.updateMsgLog(d.log, { uid: d.uid, mailbox: d.mbox });
                    }

                    if (!DIMP.conf_compose.qreply) {
                        DIMP.baseWindow.DimpCore.showNotifications(r.msgs);
                        r.msgs = [];
                    }
                }
                return this.closeCompose();

            case 'addAttachment':
                this.uploading = false;
                if (d.success) {
                    this.addAttach(d.atc.num, d.atc.name, d.atc.type, d.atc.size);
                }

                $('upload_wait').hide();
                this.initAttachList();
                this.resizeMsgArea();
                break;
            }
        } else {
            if (!Object.isUndefined(d.identity)) {
                this.old_identity = $F('identity');
                $('identity').setValue(d.identity);
                this.changeIdentity();
                $('noticerow', 'identitychecknotice').invoke('show');
            }

            if (!Object.isUndefined(d.encryptjs)) {
                this.old_action = d.action;
                eval(d.encryptjs.join(';'));
            }
        }

        this.setDisabled(false);

        $((d.action == 'redirectMessage') ? 'redirect' : 'compose').setStyle({ cursor: null });
    },

    setDisabled: function(disable)
    {
        this.disabled = disable;

        if ($('redirect').visible()) {
            DimpCore.loadingImg('sendingImg', 'redirect', disable);
            DimpCore.toggleButtons($('redirect').select('DIV.dimpActions A'), disable);
        } else {
            DimpCore.loadingImg('sendingImg', 'composeMessageParent', disable);
            DimpCore.toggleButtons($('compose').select('DIV.dimpActions A'), disable);
            [ $('compose') ].invoke(disable ? 'disable' : 'enable');
            if (DIMP.SpellChecker) {
                DIMP.SpellChecker.disable(disable);
            }
            if (IMP_Compose_Base.editor_on) {
                this.RTELoading(disable ? 'show' : 'hide', true);
            }
        }
    },

    toggleHtmlEditor: function(noupdate)
    {
        if (!DIMP.conf_compose.rte_avail) {
            return;
        }
        noupdate = noupdate || false;
        if (DIMP.SpellChecker) {
            DIMP.SpellChecker.resume();
        }

        var config, text;

        if (IMP_Compose_Base.editor_on) {
            text = this.rte.getData();
            this.rte.destroy();

            this.RTELoading('show');
            DimpCore.doAction('html2Text', { identity: $F('identity'), text: text }, { callback: this.setMessageText.bind(this), ajaxopts: { asynchronous: false } });
            this.RTELoading('hide');
        } else {
            if (!noupdate) {
                DimpCore.doAction('text2Html', { identity: $F('identity'), text: $F('composeMessage') }, { callback: this.setMessageText.bind(this), ajaxopts: { asynchronous: false } });
            }

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

        IMP_Compose_Base.editor_on = !IMP_Compose_Base.editor_on;

        $('htmlcheckbox').setValue(IMP_Compose_Base.editor_on);
        $('html').setValue(Number(IMP_Compose_Base.editor_on));
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

    _onSpellCheckAfter: function()
    {
        if (IMP_Compose_Base.editor_on) {
            this.editor_wait = true;
            this.rte.setData($F('composeMessage'), function() { this.editor_wait = false; }.bind(this));
            $('composeMessage').next().show();
        }
        this.sc_submit = false;
    },

    _onSpellCheckBefore: function()
    {
        DIMP.SpellChecker.htmlAreaParent = IMP_Compose_Base.editor_on
            ? 'composeMessageParent'
            : null;

        if (IMP_Compose_Base.editor_on) {
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
            $('composeMessageParent').insert(new Element('TEXTAREA', { id: 'composeMessage', name: 'message', style: 'width:100%' }).insert(r.response.text));
        } else {
            ta.setValue(r.response.text);
        }

        if (!IMP_Compose_Base.editor_on) {
            this.resizeMsgArea();
        }
    },

    // opts = auto, focus, fwd_list, noupdate, show_editor
    fillForm: function(msg, header, opts)
    {
        // On IE, this can get loaded before DOM:loaded.
        if (!document.loaded) {
            this.fillForm.bind(this, msg, header, opts).defer();
            return;
        }

        var bcc_add,
            identity = IMP_Compose_Base.getIdentity($F('last_identity'));
        opts = opts || {};

        // Set auto-save-drafts now if not already active.
        if (DIMP.conf_compose.auto_save_interval_val &&
            !this.auto_save_interval) {
            this.auto_save_interval = new PeriodicalExecuter(function() {
                if ($('compose').visible()) {
                    var curr_hash = MD5.hash($('to', 'cc', 'bcc', 'subject').invoke('getValue').join('\0') + (IMP_Compose_Base.editor_on ? this.rte.getData() : $F('composeMessage')));
                    if (this.last_msg && curr_hash != this.last_msg) {
                        this.uniqueSubmit('autoSaveDraft');
                    }
                    this.last_msg = curr_hash;
                }
            }.bind(this), DIMP.conf_compose.auto_save_interval_val * 60);
            /* Immediately execute to get MD5 hash of empty message. */
            this.auto_save_interval.execute();
        }

        $('to').setValue(header.to);
        if (header.cc) {
            $('cc').setValue(header.cc);
        }
        if (DIMP.conf_compose.cc) {
            this.toggleCC('cc', true);
        }
        this.setPopdownLabel('sm', identity.id.smf_name, identity.id.smf_display);
        this.setSaveSentMail(identity.id.smf_save);
        if (header.bcc) {
            $('bcc').setValue(header.bcc);
        }
        if (identity.id.bcc) {
            bcc_add = $F('bcc');
            if (bcc_add) {
                bcc_add += ', ';
            }
            $('bcc').setValue(bcc_add + identity.id.bcc);
        }
        if (DIMP.conf_compose.bcc) {
            this.toggleCC('bcc', true);
        }
        $('subject').setValue(header.subject);

        this.processFwdList(opts.fwd_list);

        Field.focus(opts.focus || 'to');

        switch (opts.auto) {
        case 'forward_attach':
            $('noticerow', 'fwdattachnotice').invoke('show');
            $('composeMessage').stopObserving('keydown').observe('keydown', this.fadeNotice.bind(this, 'fwdattachnotice'));
            break

        case 'forward_body':
            $('noticerow', 'fwdbodynotice').invoke('show');
            break

        case 'reply_all':
            $('noticerow', 'replyallnotice').invoke('show');
            break

        case 'reply_list':
            $('noticerow', 'replylistnotice').invoke('show');
            break;
        }

        this.setBodyText(msg);
        this.resizeMsgArea();

        if (DIMP.conf_compose.show_editor || opts.show_editor) {
            if (!IMP_Compose_Base.editor_on) {
                this.toggleHtmlEditor(opts.noupdate);
            }
            if (opts.focus && (opts.focus == 'composeMessage')) {
                this.focusEditor();
            }
        }
    },

    fadeNotice: function(elt)
    {
        elt = $(elt);

        elt.fade({
            afterFinish: function() {
                if (!elt.siblings().any(Element.visible)) {
                    elt.up('TR').hide();
                    this.resizeMsgArea();
                }
            }.bind(this),
            duration: 0.4
        });
    },

    setBodyText: function(msg)
    {
        if (IMP_Compose_Base.editor_on) {
            this.editor_wait = true;
            this.rte.setData(msg, function() { this.editor_wait = false; }.bind(this));
        } else {
            $('composeMessage').setValue(msg);
            IMP_Compose_Base.setCursorPosition('composeMessage', DIMP.conf_compose.compose_cursor, IMP_Compose_Base.getIdentity($F('last_identity')).sig);
        }
    },

    processFwdList: function(f)
    {
        if (f && f.size()) {
            f.each(function(ptr) {
                this.addAttach(ptr.num, ptr.name, ptr.type, ptr.size);
            }, this);
        }
    },

    swapToAddressCallback: function(r)
    {
        if (r.response.header) {
            $('to').setValue(r.response.header.to);
        }
        $('to_loading_img').hide();
    },

    forwardAddCallback: function(r)
    {
        if (r.response.type) {
            switch (r.response.type) {
            case 'forward_attach':
                this.processFwdList(r.response.opts.fwd_list);
                break;

            case 'forward_body':
                this.setBodyText(r.response.body);
                break;
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
                    this.initAttachList();
                    this.resizeMsgArea();
                }.bind(this),
                duration: 0.4
            });
        }, this);
        if (!$('attach_list').childElements().size()) {
            $('attach_list').hide();
        }
        DimpCore.doAction('deleteAttach', { atc_indices: ids, imp_compose: $F('composeCache') });
    },

    initAttachList: function()
    {
        var u = $('upload'),
            u_parent = u.up();

        if (DIMP.conf_compose.attach_limit != -1 &&
            $('attach_list').childElements().size() >= DIMP.conf_compose.attach_limit) {
            $('upload_limit').show();
        } else if (!u_parent.visible()) {
            $('upload_limit').hide();

            if (Prototype.Browser.IE) {
                // Trick to allow us to clear the file input on IE without
                // creating a new node.  Need to re-add the event handler
                // however, as it won't survive this assignment.
                u.stopObserving();
                u_parent.innerHTML = u_parent.innerHTML;
                u = $('upload');
                u.observe('change', this.changeHandler.bindAsEventListener(this));
            }

            u.clear().up().show().next().show();
        }
    },

    resizeMsgArea: function()
    {
        var mah, rows,
            cmp = $('composeMessageParent'),
            de = document.documentElement,
            msg = $('composeMessage'),
            pad = 0;

        if (!document.loaded) {
            this.resizeMsgArea.bind(this).defer();
            return;
        }

        mah = document.viewport.getHeight() - cmp.offsetTop;

        if (IMP_Compose_Base.editor_on) {
            [ 'margin', 'padding', 'border' ].each(function(s) {
                [ 'Top', 'Bottom' ].each(function(h) {
                    var a = parseInt(cmp.getStyle(s + h), 10);
                    if (!isNaN(a)) {
                        pad += a;
                    }
                });
            });

            this.rte.resize('99%', mah - pad - 1, false);
        } else {
            /* Logic: Determine the size of a given textarea row, divide that
             * size by the available height, round down to the lowest integer
             * row, and resize the textarea. */
            rows = parseInt(mah / (msg.clientHeight / msg.readAttribute('rows')), 10);
            if (!isNaN(rows)) {
                /* Due to the funky (broken) way some browsers (FF) count
                 * rows, we need to overshoot row estimate and increment
                 * downward until textarea size does not cause window
                 * scrolling. */
                ++rows;
                do {
                    msg.writeAttribute({ rows: rows--, disabled: false });
                } while (de.scrollHeight - de.clientHeight);
            }
        }
    },

    uploadAttachment: function()
    {
        var u = $('upload');
        this.uniqueSubmit('addAttachment');
        u.up().hide().next().hide();
        $('upload_wait').update(DIMP.text_compose.uploading + ' (' + $F(u) + ')').show();
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
            this.resizeMsgArea();
        } else {
            t.fade({
                afterFinish: this.resizeMsgArea.bind(this),
                duration: 0.4
            });
        }
    },

    /* Open the addressbook window. */
    openAddressbook: function(params)
    {
        var uri = DIMP.conf_compose.URI_ABOOK;

        if (params) {
            uri = DimpCore.addURLParam(uri, params);
        }

        window.open(uri, 'contacts', 'toolbar=no,location=no,status=no,scrollbars=yes,resizable=yes,width=550,height=300,left=100,top=100');
    },

    /* Click observe handler. */
    clickHandler: function(parentfunc, e)
    {
        if (e.isRightClick()) {
            return;
        }

        var elt = e.element(),
            orig = elt,
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
                if (!this.disabled) {
                    this.uniqueSubmit('saveDraft');
                }
                break;

            case 'send_button':
                if (!this.disabled) {
                    this.uniqueSubmit('sendMessage');
                }
                break;

            case 'send_button_redirect':
                if (!this.disabled) {
                    this.uniqueSubmit('redirectMessage');
                }
                break;

            case 'htmlcheckbox':
                if (!IMP_Compose_Base.editor_on ||
                    window.confirm(DIMP.text_compose.toggle_html)) {
                    this.toggleHtmlEditor();
                } else {
                    $('htmlcheckbox').setValue(true);
                }
                break;

            case 'redirect_sendto':
                if (orig.match('TD.label SPAN')) {
                    this.openAddressbook({
                        formfield: 'redirect_to',
                        formname: 'redirect',
                        to_only: 1
                    });
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

            case 'fwdattachnotice':
            case 'fwdbodynotice':
            case 'identitychecknotice':
            case 'replyallnotice':
            case 'replylistnotice':
                this.fadeNotice(elt);
                if (!orig.match('SPAN.closeImg')) {
                    if (id.startsWith('reply')) {
                        $('to_loading_img').show();
                        DimpCore.doAction('getReplyData', { headeronly: 1, imp_compose: $F('composeCache'), type: 'reply' }, { callback: this.swapToAddressCallback.bind(this) });
                    } else if (id.startsWith('fwd')) {
                        DimpCore.doAction('GetForwardData', { dataonly: 1, imp_compose: $F('composeCache'), type: (id == 'fwdattachnotice' ? 'forward_body' : 'forward_attach') }, { callback: this.forwardAddCallback.bind(this) });
                        $('composeMessage').stopObserving('keydown');
                    } else if (id == 'identitychecknotice') {
                        $('identity').setValue(this.old_identity);
                        this.changeIdentity();
                    }
                }
                e.stop();
                return;
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
        DimpCore.growler_log = false;
        DimpCore.init();

        this.is_popup = (DIMP.baseWindow && DIMP.baseWindow.DimpBase);

        /* Initialize redirect elements (always needed). */
        $('redirect').observe('submit', Event.stop);
        new TextareaResize('redirect_to');
        if (DIMP.conf_compose.URI_ABOOK) {
            $('redirect_sendto').down('TD.label SPAN').addClassName('composeAddrbook');
        }

        /* Nothing more to do if this is strictly a redirect window. */
        if (DIMP.conf_compose.redirect) {
            $('dimpLoading').hide();
            $('redirect', 'pageContainer').invoke('show');
            return;
        }

        /* Attach event handlers. */
        if (Prototype.Browser.IE) {
            // IE doesn't bubble change events.
            $('identity', 'upload').invoke('observe', 'change', this.changeHandler.bindAsEventListener(this));
        } else {
            document.observe('change', this.changeHandler.bindAsEventListener(this));
        }
        Event.observe(window, 'resize', this.resizeMsgArea.bind(this));
        $('compose').observe('submit', Event.stop);
        $('submit_frame').observe('load', this.attachmentComplete.bind(this));

        // Initialize spell checker
        document.observe('SpellChecker:noerror', this._onSpellCheckNoError.bind(this));
        if (DIMP.conf_compose.rte_avail) {
            document.observe('SpellChecker:after', this._onSpellCheckAfter.bind(this));
            document.observe('SpellChecker:before', this._onSpellCheckBefore.bind(this));
        }

        /* Create sent-mail list. */
        if (DIMP.conf_compose.flist) {
            this.createPopdown('sm', {
                base: 'save_sent_mail',
                data: DIMP.conf_compose.flist,
                input: 'save_sent_mail_folder',
                label: 'sent_mail_folder_label'
            });
            this.setPopdownLabel('sm', IMP_Compose_Base.getIdentity($F('identity')).id.smf_name);
        }

        /* Create priority list. */
        if (DIMP.conf_compose.priority) {
            this.createPopdown('p', {
                base: 'priority_label',
                data: DIMP.conf_compose.priority,
                input: 'priority',
                label: 'priority_label'
            });
            this.setPopdownLabel('p', $F('priority'));
        }

        /* Create encryption list. */
        if (DIMP.conf_compose.encrypt) {
            this.createPopdown('e', {
                base: $('encrypt_label').up(),
                data: DIMP.conf_compose.encrypt,
                input: 'encrypt',
                label: 'encrypt_label'
            });
            this.setPopdownLabel('e', $F('encrypt'));
        }

        // Automatically resize compose address fields.
        new TextareaResize('to');
        new TextareaResize('cc');
        new TextareaResize('bcc');

        /* Add addressbook link formatting. */
        if (DIMP.conf_compose.URI_ABOOK) {
            $('sendto', 'sendcc', 'sendbcc', 'redirect_sendto').each(function(a) {
                a.down('TD.label SPAN').addClassName('composeAddrbook');
            });
        }

        $('dimpLoading').hide();
        $('pageContainer').show();

        this.resizeMsgArea();

        // Safari requires a submit target iframe to be at least 1x1 size or
        // else it will open content in a new window.  See:
        //   http://blog.caboo.se/articles/2007/4/2/ajax-file-upload
        if (Prototype.Browser.WebKit) {
            $('submit_frame').writeAttribute({ position: 'absolute', width: '1px', height: '1px' }).setStyle({ left: '-999px' }).show();
        }
    }

};

/* Attach event handlers. */
document.observe('dom:loaded', DimpCompose.onDomLoad.bind(DimpCompose));
document.observe('TextareaResize:resize', DimpCompose.resizeMsgArea.bind(DimpCompose));

/* Click handler. */
DimpCore.clickHandler = DimpCore.clickHandler.wrap(DimpCompose.clickHandler.bind(DimpCompose));

/* Catch dialog actions. */
document.observe('IMPDialog:success', function(e) {
    switch (e.memo) {
    case 'pgpPersonal':
    case 'pgpSymmetric':
    case 'smimePersonal':
        IMPDialog.noreload = true;
        DimpCompose.retrySubmit();
        break;
    }
});
