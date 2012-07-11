/**
 * compose.js - Javascript code used in the DIMP compose view.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

var DimpCompose = {

    // Variables defaulting to empty/false:
    //   auto_save_interval, compose_cursor, disabled, drafts_mbox,
    //   editor_wait, is_popup, knl, md5_hdrs, md5_msg, md5_msgOrig,
    //   onload_show, old_action, old_identity, rte, rte_loaded,
    //   sc_submit, skip_spellcheck, spellcheck, uploading

    knl: {},

    confirmCancel: function()
    {
        var cc,
            sbd = $('send_button_redirect');

        if (window.confirm(DimpCore.text.compose_cancel)) {
            if (!DimpCore.conf.qreply &&
                this.baseAvailable()) {
                HordeCore.base.focus();
            }

            cc = (sbd && sbd.visible())
                ? $F('composeCacheRedirect')
                : $F('composeCache');

            DimpCore.doAction('cancelCompose', {
                imp_compose: cc
            });
            this.updateDraftsMailbox();
            return this.closeCompose();
        }
    },

    updateDraftsMailbox: function()
    {
        if (this.baseAvailable() &&
            HordeCore.base.DimpBase.view == DimpCore.conf.drafts_mbox) {
            HordeCore.base.DimpBase.poll();
        }
    },

    closeCompose: function()
    {
        if (DimpCore.conf.qreply) {
            this.closeQReply();
        } else if (this.is_popup) {
            HordeCore.closePopup();
        } else {
            HordeCore.redirect(DimpCore.conf.URI_MAILBOX);
        }
    },

    closeQReply: function()
    {
        this.md5_hdrs = this.md5_msg = this.md5_msgOrig = '';

        $('attach_list').hide().childElements().invoke('remove');
        $('composeCache').clear();
        $('qreply', 'sendcc', 'sendbcc').compact().invoke('hide');
        $('noticerow').down('UL.notices').childElements().invoke('hide');
        $('msgData', 'togglecc', 'togglebcc').compact().invoke('show');
        if (ImpComposeBase.editor_on) {
            this.toggleHtmlEditor();
        }
        $('compose').reset();

        this.setDisabled(false);

        // Disable auto-save-drafts now.
        if (this.auto_save_interval) {
            this.auto_save_interval.stop();
        }
    },

    changeIdentity: function()
    {
        var identity = ImpComposeBase.identities[$F('identity')];

        this.setPopdownLabel('sm', identity.sm_name, identity.sm_display);
        if (DimpCore.conf.bcc && identity.bcc) {
            $('bcc').setValue(($F('bcc') ? $F('bcc') + ', ' : '') + identity.bcc);
            this.toggleCC('bcc');
        }
        this.setSaveSentMail(identity.sm_save);
    },

    setSaveSentMail: function(set)
    {
        var ssm = $('save_sent_mail');

        if (ssm) {
            ssm.setValue(set);
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

        $(opts.label).insert({ after:
            new Element('SPAN', { className: 'iconImg popdownImg dimpOptionPopdown' }).store('popdown_id', id)
        });
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

        k.knl.setSelected(s);

        if (id == 'sm') {
            this.setSaveSentMail(true);
        }
    },

    retrySubmit: function(action)
    {
        if (this.old_action) {
            this.uniqueSubmit(this.old_action);
            delete this.old_action;
        }
    },

    uniqueSubmit: function(action)
    {
        var c = (action == 'redirectMessage') ? $('redirect') : $('compose'),
            sc = ImpComposeBase.getSpellChecker();

        if (sc && sc.isActive()) {
            sc.resume();
            this.skip_spellcheck = true;
        }

        if (this.editor_wait && ImpComposeBase.editor_on) {
            return this.uniqueSubmit.bind(this, action).defer();
        }

        if (action == 'sendMessage' ||
            action == 'saveDraft' ||
            action == 'saveTemplate') {
            switch (action) {
            case 'sendMessage':
                if (!this.skip_spellcheck &&
                    DimpCore.conf.spellcheck &&
                    sc &&
                    !sc.isActive()) {
                    this.sc_submit = action;
                    sc.spellCheck();
                    return;
                }

                if (($F('subject') == '') &&
                    !window.confirm(DimpCore.text.nosubject)) {
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

        this.skip_spellcheck = false;

        if (action == 'addAttachment') {
            // We need a submit action here because browser security models
            // won't let us access files on user's filesystem otherwise.
            this.uploading = true;
            HordeCore.submit(c);
        } else {
            // Move HTML text to textarea field for submission.
            if (ImpComposeBase.editor_on) {
                this.rte.updateElement();
            }

            // Use an AJAX submit here so that we can do javascript-y stuff
            // before having to close the window on success.
            DimpCore.doAction(action, c.serialize(true), {
                ajaxopts: {
                    onFailure: this.uniqueSubmitFailure.bind(this)
                },
                callback: this.uniqueSubmitCallback.bind(this)
            });

            // Can't disable until we send the message - or else nothing
            // will get POST'ed.
            if (action != 'autoSaveDraft') {
                this.setDisabled(true);
            }
        }
    },

    uniqueSubmitCallback: function(d)
    {
        if (d.imp_compose) {
            $('composeCache').setValue(d.imp_compose);
        }

        if (d.success || d.action == 'addAttachment') {
            switch (d.action) {
            case 'autoSaveDraft':
            case 'saveDraft':
                this.updateDraftsMailbox();

                if (d.action == 'saveDraft') {
                    if (!DimpCore.conf.qreply && this.baseAvailable()) {
                        HordeCore.notify_handler = HordeCore.base.HordeCore.showNotifications.bind(HordeCore.base.HordeCore);
                    }
                    if (DimpCore.conf.close_draft) {
                        $('attach_list').childElements().invoke('remove');
                        return this.closeCompose();
                    }
                }
                break;

            case 'saveTemplate':
                if (this.baseAvailable() &&
                    HordeCore.base.DimpBase.view == DimpCore.conf.templates_mbox) {
                    HordeCore.base.DimpBase.poll();
                }
                return this.closeCompose();

            case 'sendMessage':
                if (this.baseAvailable()) {
                    if (d.draft_delete) {
                        HordeCore.base.DimpBase.poll();
                    }

                    if (!DimpCore.conf.qreply) {
                        HordeCore.notify_handler = HordeCore.base.HordeCore.showNotifications.bind(HordeCore.base.HordeCore);
                    }
                }

                $('attach_list').childElements().invoke('remove');
                return this.closeCompose();

            case 'redirectMessage':
                if (this.baseAvailable() && !DimpCore.conf.qreply) {
                    HordeCore.notify_handler = HordeCore.base.HordeCore.showNotifications.bind(HordeCore.base.HordeCore);
                }

                return this.closeCompose();

            case 'addAttachment':
                this.uploading = false;
                if (d.success) {
                    this.addAttach(d.atc);
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
                this.resizeMsgArea();
            }

            if (!Object.isUndefined(d.encryptjs)) {
                this.old_action = d.action;
                eval(d.encryptjs.join(';'));
            }
        }

        this.setDisabled(false);
    },

    uniqueSubmitFailure: function(t, o)
    {
        if (this.disabled) {
            this.setDisabled(false);
            HordeCore.onFailure(t, o);
        }
    },

    setDisabled: function(disable)
    {
        var redirect = $('redirect'), sc;

        this.disabled = disable;

        if (redirect.visible()) {
            HordeCore.loadingImg('sendingImg', 'redirect', disable);
            DimpCore.toggleButtons(redirect.select('DIV.dimpActions A'), disable);
            redirect.setStyle({ cursor: disable ? 'wait': null });
        } else {
            HordeCore.loadingImg('sendingImg', 'composeMessageParent', disable);
            DimpCore.toggleButtons($('compose').select('DIV.dimpActions A'), disable);
            [ $('compose') ].invoke(disable ? 'disable' : 'enable');
            if (sc = ImpComposeBase.getSpellChecker()) {
                sc.disable(disable);
            }
            if (ImpComposeBase.editor_on) {
                this.RTELoading(disable ? 'show' : 'hide', true);
            }

            $('compose').setStyle({ cursor: disable ? 'wait' : null });
        }
    },

    toggleHtmlEditor: function(noupdate)
    {
        var sc;

        if (!DimpCore.conf.rte_avail) {
            return;
        }

        noupdate = noupdate || false;
        if (sc = ImpComposeBase.getSpellChecker()) {
           sc.resume();
        }

        var changed, text;

        if (ImpComposeBase.editor_on) {
            this.RTELoading('show');

            changed = (this.msgHash() != this.md5_msgOrig);
            text = this.rte.getData();

            DimpCore.doAction('html2Text', {
                changed: Number(changed),
                identity: $F('identity'),
                imp_compose: $F('composeCache'),
                text: text
            }, {
                callback: this.setMessageText.bind(this, false)
            });

            this.rte.destroy(true);
            delete this.rte;
        } else {
            this.RTELoading('show');

            if (!noupdate) {
                DimpCore.doAction('text2Html', {
                    changed: Number(this.msgHash() != this.md5_msgOrig),
                    identity: $F('identity'),
                    imp_compose: $F('composeCache'),
                    text: $F('composeMessage')
                }, {
                    callback: this.setMessageText.bind(this, true)
                });
            }

            if (Object.isUndefined(this.rte_loaded)) {
                CKEDITOR.on('instanceReady', function(evt) {
                    this.RTELoading('hide');
                    this.rte.focus();
                    this.rte_loaded = true;
                    this.resizeMsgArea();
                }.bind(this));
                CKEDITOR.on('instanceDestroyed', function(evt) {
                    this.RTELoading('hide');
                    this.rte_loaded = false;
                }.bind(this));
            }

            this.rte = CKEDITOR.replace('composeMessage', Object.clone(IMP.ckeditor_config));
        }

        ImpComposeBase.editor_on = !ImpComposeBase.editor_on;

        $('htmlcheckbox').setValue(ImpComposeBase.editor_on);
        $('html').setValue(Number(ImpComposeBase.editor_on));
    },

    RTELoading: function(cmd, notxt)
    {
        var o;

        if (!$('rteloading')) {
            $(document.body).insert(new Element('DIV', { id: 'rteloading' }).hide()).insert(new Element('SPAN', { id: 'rteloadingtxt' }).hide().insert(DimpCore.text.loading));
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
        if (ImpComposeBase.editor_on) {
            this.editor_wait = true;
            this.rte.setData($F('composeMessage'), function() { this.editor_wait = false; }.bind(this));
            $('composeMessage').next().show();
            this.RTELoading('hide');
        }
        this.sc_submit = false;
    },

    _onSpellCheckBefore: function()
    {
        ImpComposeBase.getSpellChecker().htmlAreaParent = ImpComposeBase.editor_on
            ? 'composeMessageParent'
            : null;

        if (ImpComposeBase.editor_on) {
            this.rte.updateElement();
            this.RTELoading('show', true);
            $('composeMessage').next().hide();
        }
    },

    _onSpellCheckError: function()
    {
        if (ImpComposeBase.editor_on) {
            this.RTELoading('hide');
        }
    },

    _onSpellCheckNoError: function()
    {
        if (this.sc_submit) {
            this.skip_spellcheck = true;
            this.uniqueSubmit(this.sc_submit);
        } else {
            HordeCore.notify(DimpCore.text.spell_noerror, 'horde.message');
            this._onSpellCheckAfter();
        }
    },

    setMessageText: function(rte, r)
    {
        var ta = $('composeMessage');
        if (!ta) {
            $('composeMessageParent').insert(new Element('TEXTAREA', { id: 'composeMessage', name: 'message', style: 'width:100%' }));
        }

        if (this.rte_loaded && rte) {
            this.rte.setData(r.text);
        } else if (!this.rte_loaded && !rte) {
            ta.setValue(r.text);
        } else {
            this.setMessageText.bind(this, rte, r).defer();
            return;
        }

        this.resizeMsgArea();
    },

    // ob = body, format, header, identity, imp_compose, opts, type
    // ob.opts = auto, focus, fwd_list, noupdate, priority, readreceipt,
    //           reply_lang, reply_recip, reply_list_id, show_editor
    fillForm: function(ob)
    {
        if (!document.loaded || $('dimpLoading').visible()) {
            this.fillForm.bind(this, ob).defer();
            return;
        }

        ob.opts = ob.opts || {};

        if (ob.imp_compose) {
            $('composeCache').setValue(ob.imp_compose);
        }

        $('to').setValue(ob.header.to);
        if (DimpCore.conf.cc && ob.header.cc) {
            this.toggleCC('cc');
            $('cc').setValue(ob.header.cc);
        }
        if (DimpCore.conf.bcc && ob.header.bcc) {
            this.toggleCC('bcc');
            $('bcc').setValue(ob.header.bcc);
        }

        $('identity').setValue(ob.identity);
        this.changeIdentity();

        $('subject').setValue(ob.header.subject);

        if (DimpCore.conf.priority && ob.opts.priority) {
            this.setPopdownLabel('p', ob.opts.priority);
        }

        if (ob.opts.readreceipt && $('request_read_receipt')) {
            $('request_read_receipt').setValue(true);
        }

        this.processAttach(ob.opts.atc);

        switch (ob.opts.auto) {
        case 'forward_attach':
            $('noticerow', 'fwdattachnotice').invoke('show');
            $('composeMessage').stopObserving('keydown').observe('keydown', this.fadeNotice.bind(this, 'fwdattachnotice'));
            break

        case 'forward_body':
            $('noticerow', 'fwdbodynotice').invoke('show');
            break

        case 'reply_all':
            $('replyallnotice').down('SPAN.replyAllNoticeCount').setText(DimpCore.text.replyall.sub('%d', ob.opts.reply_recip));
            $('noticerow', 'replyallnotice').invoke('show');
            break

        case 'reply_list':
            $('replylistnotice').down('SPAN.replyListNoticeId').setText(ob.opts.reply_list_id ? (' (' + ob.opts.reply_list_id + ')') : '');
            $('noticerow', 'replylistnotice').invoke('show');
            break;
        }

        if (ob.opts.reply_lang) {
            $('langnotice').down('SPAN.langNoticeList').setText(ob.opts.reply_lang.join(', '));
            $('noticerow', 'langnotice').invoke('show');
        }

        this.setBodyText(ob.body);
        this.resizeMsgArea();

        Field.focus(ob.opts.focus || 'to');

        if (ob.format == 'html') {
            if (!ImpComposeBase.editor_on) {
                this.toggleHtmlEditor(true);
            }
            if (ob.opts.focus && (ob.opts.focus == 'composeMessage')) {
                this.focusEditor();
            }
        }

        this.fillFormHash();
    },

    fillFormHash: function()
    {
        if (ImpComposeBase.editor_on && !this.rte_loaded) {
            this.fillFormHash.bind(this).defer();
            return;
        }

        // This value is used to determine if the text has changed when
        // swapping compose modes.
        this.md5_msgOrig = this.msgHash();

        // Set auto-save-drafts now if not already active.
        if (DimpCore.conf.auto_save_interval_val &&
            !this.auto_save_interval) {
            this.auto_save_interval = new PeriodicalExecuter(function() {
                if ($('compose').visible()) {
                    var hdrs = MD5.hash($('to', 'cc', 'bcc', 'subject').compact().invoke('getValue').join('\0')), msg;
                    if (this.md5_hdrs) {
                        msg = this.msgHash();
                        if (this.md5_hdrs != hdrs || this.md5_msg != msg) {
                            this.uniqueSubmit('autoSaveDraft');
                        }
                    } else {
                        msg = this.md5_msgOrig;
                    }
                    this.md5_hdrs = hdrs;
                    this.md5_msg = msg;
                }
            }.bind(this), DimpCore.conf.auto_save_interval_val * 60);

            /* Immediately execute to get MD5 hash of headers. */
            this.auto_save_interval.execute();
        }
    },

    msgHash: function()
    {
        return MD5.hash(ImpComposeBase.editor_on ? this.rte.getData() : $F('composeMessage'));
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
        if (ImpComposeBase.editor_on) {
            this.editor_wait = true;
            this.rte.setData(msg, function() { this.editor_wait = false; }.bind(this));
        } else {
            $('composeMessage').setValue(msg);
            ImpComposeBase.setCursorPosition('composeMessage', DimpCore.conf.compose_cursor);
        }
    },

    processAttach: function(f)
    {
        if (f && f.size()) {
            f.each(this.addAttach.bind(this));
        }
    },

    swapToAddressCallback: function(r)
    {
        if (r.header) {
            $('to').setValue(r.header.to);
            [ 'cc', 'bcc' ].each(function(t) {
                if (r.header[t] || $(t).visible()) {
                    if (!$(t).visible()) {
                        this.toggleCC(t);
                    }
                    $(t).setValue(r.header.cc);
                }
            }, this);
        }
        $('to_loading_img').hide();
    },

    forwardAddCallback: function(r)
    {
        if (r.type) {
            switch (r.type) {
            case 'forward_attach':
                this.processAttach(r.opts.atc);
                break;

            case 'forward_body':
                this.removeAttach([ $('attach_list').down() ]);
                this.setBodyText(r.body);
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

    // opts = (Object)
    //   fwdattach: (integer) Attachment is forwarded message
    //   name: (string) Attachment name
    //   num: (integer) Attachment number
    //   size: (integer) Size, in KB
    //   type: (string) MIME type
    addAttach: function(opts)
    {
        var span = new Element('SPAN').insert(opts.name.escapeHTML()),
            li = new Element('LI').insert(span).store('atc_id', opts.num);
        if (opts.fwdattach) {
            li.insert(' (' + opts.size + ' KB)');
            span.addClassName('attachNameFwdmsg');
        } else {
            li.insert(' [' + opts.type + '] (' + opts.size + ' KB) ').insert(new Element('SPAN', { className: 'button remove' }).insert(DimpCore.text.remove));
            if (opts.type != 'application/octet-stream') {
                span.addClassName('attachName');
            }
        }
        $('attach_list').insert(li).show();

        this.resizeMsgArea();
    },

    removeAttach: function(e)
    {
        var ids = [];

        e.each(function(n) {
            n = $(n);
            ids.push(n.retrieve('atc_id'));
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

        DimpCore.doAction('deleteAttach', { atc_indices: Object.toJSON(ids), imp_compose: $F('composeCache') });
    },

    initAttachList: function()
    {
        var u = $('upload'),
            u_parent = u.up();

        if (DimpCore.conf.attach_limit != -1 &&
            $('attach_list').childElements().size() >= DimpCore.conf.attach_limit) {
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

            u.clear().up().show();
        }
    },

    resizeMsgArea: function(e)
    {
        if (!document.loaded || $('dimpLoading').visible()) {
            this.resizeMsgArea.bind(this).defer();
            return;
        }

        // IE 7/8 Bug - can't resize TEXTAREA in the resize event (Bug #10075)
        if (e && Prototype.Browser.IE) {
            this.resizeMsgArea.bind(this).delay(0.1);
            return;
        }

        var cmp = $('composeMessageParent').getLayout(),
            mah = document.viewport.getHeight() - cmp.get('top') - cmp.get('margin-box-height') + cmp.get('height');

        if (this.rte_loaded) {
            this.rte.resize('99%', mah - 1, false);
        } else if (!ImpComposeBase.editor_on) {
            $('composeMessage').setStyle({ height: mah + 'px' });
        }

        if ($('rteloading') && $('rteloading').visible()) {
            this.RTELoading();
        }
    },

    uploadAttachment: function()
    {
        var u = $('upload');
        this.uniqueSubmit('addAttachment');
        u.up().hide();
        $('upload_wait').update(DimpCore.text.uploading + ' (' + $F(u) + ')').show();
    },

    toggleCC: function(type)
    {
        var t = $('toggle' + type),
            s = t.siblings().first();

        new TextareaResize(type);

        $('send' + type).show();
        if (s && s.visible()) {
            t.hide();
        } else {
            t.up('TR').hide();
        }

        this.resizeMsgArea();
    },

    /* Open the addressbook window. */
    openAddressbook: function(params)
    {
        var uri = DimpCore.conf.URI_ABOOK;

        if (params) {
            uri = HordeCore.addURLParam(uri, params);
        }

        window.open(uri, 'contacts', 'toolbar=no,location=no,status=no,scrollbars=yes,resizable=yes,width=550,height=300,left=100,top=100');
    },

    baseAvailable: function()
    {
        return (this.is_popup &&
                HordeCore.base &&
                !Object.isUndefined(HordeCore.base.DimpBase) &&
                !HordeCore.base.closed);
    },

    /* Click observe handler. */
    clickHandler: function(e)
    {
        /* Needed because reply/forward buttons need to be of type="submit"
         * for FF to correctly size. */
        if (e.memo.element().readAttribute('type') == 'submit') {
            e.memo.hordecore_stop = true;
            return;
        }

        var atc_num, tmp;

        switch (e.element().readAttribute('id')) {
        case 'togglebcc':
            this.toggleCC('bcc');
            this.resizeMsgArea();
            break;

        case 'togglecc':
            this.toggleCC('cc');
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

        case 'template_button':
            if (!this.disabled) {
                this.uniqueSubmit('saveTemplate');
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
            if (!ImpComposeBase.editor_on ||
                window.confirm(DimpCore.text.toggle_html)) {
                this.toggleHtmlEditor();
            } else {
                $('htmlcheckbox').setValue(true);
            }
            break;

        case 'redirect_sendto':
            if (e.memo.element().match('TD.label SPAN')) {
                this.openAddressbook({
                    to_only: 1
                });
            }
            break;

        case 'sendcc':
        case 'sendbcc':
        case 'sendto':
            if (e.memo.element().match('TD.label SPAN')) {
                this.openAddressbook();
            }
            break;

        case 'attach_list':
            tmp = e.memo.element();
            if (tmp.match('SPAN.remove')) {
                this.removeAttach([ tmp.up() ]);
            } else if (tmp.match('SPAN.attachName')) {
                atc_num = tmp.up('LI').retrieve('atc_id');
                HordeCore.popupWindow(DimpCore.conf.URI_VIEW, {
                    actionID: 'compose_attach_preview',
                    composeCache: $F('composeCache'),
                    id: atc_num
                }, {
                    name: $F('composeCache') + '|' + atc_num
                });
            }
            break;

        case 'save_sent_mail':
            this.setSaveSentMail($F(e.element()));
            break;

        case 'fwdattachnotice':
        case 'fwdbodynotice':
            this.fadeNotice(e.element());
            DimpCore.doAction('GetForwardData', {
                dataonly: 1,
                imp_compose: $F('composeCache'),
                type: (e.element().identify() == 'fwdattachnotice' ? 'forward_body' : 'forward_attach')
            }, {
                callback: this.forwardAddCallback.bind(this)
            });
            $('composeMessage').stopObserving('keydown');
            e.memo.stop();
            break;

        case 'identitychecknotice':
            this.fadeNotice(e.element());
            $('identity').setValue(this.old_identity);
            this.changeIdentity();
            e.memo.stop();
            break;

        case 'replyall_revert':
        case 'replylist_revert':
            this.fadeNotice(e.element().up('LI'));
            $('to_loading_img').show();
            DimpCore.doAction('getReplyData', {
                headeronly: 1,
                imp_compose: $F('composeCache'),
                type: 'reply'
            }, {
                callback: this.swapToAddressCallback.bind(this)
            });
            e.memo.stop();
            break;

        case 'writemsg':
            if (!this.disabled &&
                e.memo.element().hasClassName('dimpOptionPopdown')) {
                tmp = e.memo.element().retrieve('popdown_id');
                this.knl[tmp].knl.show();
                this.knl[tmp].knl.ignoreClick(e.memo);
                e.stop();
            }
            break;
        }
    },

    changeHandler: function(e)
    {
        switch (e.element().readAttribute('id')) {
        case 'identity':
            this.changeIdentity();
            break;

        case 'upload':
            this.uploadAttachment();
            break;
        }
    },

    contextOnClick: function(e)
    {
        switch (e.memo.elt.readAttribute('id')) {
        case 'ctx_msg_other_rr':
            $('request_read_receipt').setValue(!$F('request_read_receipt'));
            break;

        case 'ctx_msg_other_saveatc':
            $('save_attachments_select').setValue(!$F('save_attachments_select'));
            break;
        }
    },

    contextOnShow: function(e)
    {
        var tmp;

        switch (e.memo) {
        case 'ctx_msg_other':
            if (tmp = $('ctx_msg_other_rr')) {
                DimpCore.toggleCheck(tmp.down('SPAN'), $F('request_read_receipt'));
            }
            if (tmp = $('ctx_msg_other_saveatc')) {
                DimpCore.toggleCheck(tmp.down('SPAN'), $F('save_attachments_select'));
            }
            break;
        }
    },

    onContactsUpdate: function(e)
    {
        switch (e.memo.field) {
        case 'bcc':
        case 'cc':
            if (!$('send' + e.memo.field).visible()) {
                this.toggleCC(e.memo.field);
            }
            break;

        case 'to':
            if (DimpCore.conf.redirect) {
                e.memo.field = 'redirect_to';
            }
            break;
        }

        ImpComposeBase.updateAddressField($(e.memo.field), e.memo.value);
    },

    tasksHandler: function(t)
    {
        if (this.baseAvailable()) {
            if (t['imp:flag']) {
                HordeCore.base.DimpBase.flagCallback(t['imp:flag']);
            }

            if (t['imp:mailbox']) {
                HordeCore.base.DimpBase.mailboxCallback(t['imp:mailbox']);
            }

            if (t['imp:maillog']) {
                HordeCore.base.DimpBase.maillogCallback(t['imp:maillog']);
            }
        }
    },

    onDomLoad: function()
    {
        this.is_popup = !Object.isUndefined(HordeCore.base);

        /* Initialize redirect elements. */
        if (DimpCore.conf.redirect) {
            $('redirect').observe('submit', Event.stop);
            new TextareaResize('redirect_to');
            if (DimpCore.conf.URI_ABOOK) {
                $('redirect_sendto').down('TD.label SPAN').addClassName('composeAddrbook');
            }
            $('dimpLoading').hide();
            $('composeContainer', 'redirect').invoke('show');
            return;
        }

        /* Attach event handlers. */
        if (Prototype.Browser.IE) {
            // IE doesn't bubble change events.
            $('identity', 'upload').invoke('observe', 'change', this.changeHandler.bindAsEventListener(this));
        } else {
            document.observe('change', this.changeHandler.bindAsEventListener(this));
        }
        $('compose').observe('submit', Event.stop);

        HordeCore.initHandler('click');
        HordeCore.handleSubmit($('compose'), {
            callback: this.uniqueSubmitCallback.bind(this)
        });

        if ($H(DimpCore.context.ctx_msg_other).size()) {
            DimpCore.addPopdown($('msg_other_options').down('A'), 'msg_other', {
                trigger: true
            });
        } else {
            $('msg_other_options').hide();
        }

        /* Create sent-mail list. */
        if (DimpCore.conf.flist) {
            this.createPopdown('sm', {
                base: 'save_sent_mail',
                data: DimpCore.conf.flist,
                input: 'save_sent_mail_mbox',
                label: 'sent_mail_label'
            });
            this.setPopdownLabel('sm', ImpComposeBase.identities[$F('identity')].sm_name);
        }

        /* Create priority list. */
        if (DimpCore.conf.priority) {
            this.createPopdown('p', {
                base: 'priority_label',
                data: DimpCore.conf.priority,
                input: 'priority',
                label: 'priority_label'
            });
            this.setPopdownLabel('p', $F('priority'));
        }

        /* Create encryption list. */
        if (DimpCore.conf.encrypt) {
            this.createPopdown('e', {
                base: $('encrypt_label').up(),
                data: DimpCore.conf.encrypt,
                input: 'encrypt',
                label: 'encrypt_label'
            });
            this.setPopdownLabel('e', $F('encrypt'));
        }

        new TextareaResize('to');

        /* Add addressbook link formatting. */
        if (DimpCore.conf.URI_ABOOK) {
            $('sendto', 'sendcc', 'sendbcc', 'redirect_sendto').compact().each(function(a) {
                a.down('TD.label SPAN').addClassName('composeAddrbook');
            });
        }

        $('dimpLoading').hide();
        $('composeContainer', 'compose').compact().invoke('show');

        if (this.onload_show) {
            this.fillForm(this.onload_show);
            delete this.onload_show;
        } else {
            this.resizeMsgArea();
        }
    }

};

/* Attach event handlers. */
document.observe('dom:loaded', DimpCompose.onDomLoad.bind(DimpCompose));
document.observe('HordeCore:click', DimpCompose.clickHandler.bindAsEventListener(DimpCompose));
Event.observe(window, 'resize', DimpCompose.resizeMsgArea.bindAsEventListener(DimpCompose));

/* Other UI event handlers. */
document.observe('ImpContacts:update', DimpCompose.onContactsUpdate.bindAsEventListener(DimpCompose));
document.observe('TextareaResize:resize', DimpCompose.resizeMsgArea.bind(DimpCompose));

/* ContextSensitive functions. */
document.observe('ContextSensitive:click', DimpCompose.contextOnClick.bindAsEventListener(DimpCompose));
document.observe('ContextSensitive:show', DimpCompose.contextOnShow.bindAsEventListener(DimpCompose));

/* Initialize spellchecker. */
document.observe('SpellChecker:after', DimpCompose._onSpellCheckAfter.bind(DimpCompose));
document.observe('SpellChecker:before', DimpCompose._onSpellCheckBefore.bind(DimpCompose));
document.observe('SpellChecker:error', DimpCompose._onSpellCheckError.bind(DimpCompose));
document.observe('SpellChecker:noerror', DimpCompose._onSpellCheckNoError.bind(DimpCompose));

/* Catch dialog actions. */
document.observe('ImpPassphraseDialog:success', DimpCompose.retrySubmit.bind(DimpCompose));

/* Catch tasks. */
document.observe('HordeCore:runTasks', function(e) {
    this.tasksHandler(e.memo);
}.bindAsEventListener(DimpCompose));
