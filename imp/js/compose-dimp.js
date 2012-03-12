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
    //   old_action, old_identity, resizing, rte, rte_loaded, skip_spellcheck,
    //   spellcheck, sc_submit, uploading

    knl: {},

    confirmCancel: function()
    {
        var cc,
            sbd = $('send_button_redirect');

        if (window.confirm(DIMP.text.compose_cancel)) {
            if (!DIMP.conf.qreply &&
                this.baseAvailable()) {
                HordeCore.base.focus();
            }

            cc = (sbd && sbd.visible())
                ? $F('composeCacheRedirect')
                : $F('composeCache');

            DimpCore.doAction(DIMP.conf.auto_save_interval_val ? 'deleteDraft' : 'cancelCompose', { imp_compose: cc }, { ajaxopts: { asynchronous: DIMP.conf.qreply } });
            this.updateDraftsMailbox();
            return this.closeCompose();
        }
    },

    updateDraftsMailbox: function()
    {
        if (this.baseAvailable() &&
            HordeCore.base.DimpBase.view == DIMP.conf.drafts_mbox) {
            HordeCore.base.DimpBase.poll();
        }
    },

    closeCompose: function()
    {
        if (DIMP.conf.qreply) {
            this.closeQReply();
        } else if (this.is_popup) {
            HordeCore.closePopup();
        } else {
            HordeCore.redirect(DIMP.conf.URI_DIMP);
        }
    },

    closeQReply: function()
    {
        var al = $('attach_list').childElements();
        this.md5_hdrs = this.md5_msg = this.md5_msgOrig = '';

        if (al.size()) {
            this.removeAttach(al);
        }

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
        if (DIMP.conf.bcc) {
            $('bcc').setValue(identity.bcc);
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

        if (id == 'sm') {
            k.knl.setSelected(s);
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
        var c = (action == 'redirectMessage')
            ? $('redirect')
            : $('compose');

        if (DIMP.SpellChecker &&
            DIMP.SpellChecker.isActive()) {
            DIMP.SpellChecker.resume();
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
                    DIMP.conf.spellcheck &&
                    DIMP.SpellChecker &&
                    !DIMP.SpellChecker.isActive()) {
                    this.sc_submit = action;
                    DIMP.SpellChecker.spellCheck();
                    return;
                }

                if (($F('subject') == '') &&
                    !window.confirm(DIMP.text.nosubject)) {
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
            c.submit();
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
                    if (!DIMP.conf.qreply && this.baseAvailable()) {
                        HordeCore.notify_handler = HordeCore.base.HordeCore.showNotifications.bind(HordeCore.base.HordeCore);
                    }
                    if (DIMP.conf.close_draft) {
                        return this.closeCompose();
                    }
                }
                break;

            case 'saveTemplate':
                if (this.baseAvailable() &&
                    HordeCore.base.DimpBase.view == DIMP.conf.templates_mbox) {
                    HordeCore.base.DimpBase.poll();
                }
                return this.closeCompose();

            case 'sendMessage':
                if (this.baseAvailable()) {
                    if (d.flag) {
                        HordeCore.base.DimpBase.flagCallback(d);
                    }

                    if (d.mailbox) {
                        HordeCore.base.DimpBase.mailboxCallback(r);
                    }

                    if (d.draft_delete) {
                        HordeCore.base.DimpBase.poll();
                    }

                    if (d.log) {
                        HordeCore.base.DimpBase.updateMsgLog(d.log, { uid: d.uid, mbox: d.mbox });
                    }

                    if (!DIMP.conf.qreply) {
                        HordeCore.notify_handler = HordeCore.base.HordeCore.showNotifications.bind(HordeCore.base.HordeCore);
                    }
                }
                return this.closeCompose();

            case 'redirectMessage':
                if (this.baseAvailable()) {
                    if (d.log) {
                        d.log.each(function(l) {
                            HordeCore.base.DimpBase.updateMsgLog(l.log, {
                                mbox: l.mbox,
                                uid: l.uid
                            });
                        });
                    }

                    if (!DIMP.conf.qreply) {
                        HordeCore.notify_handler = HordeCore.base.HordeCore.showNotifications.bind(HordeCore.base.HordeCore);
                    }
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
        var redirect = $('redirect');

        this.disabled = disable;

        if (redirect.visible()) {
            HordeCore.loadingImg('sendingImg', 'redirect', disable);
            DimpCore.toggleButtons(redirect.select('DIV.dimpActions A'), disable);
            redirect.setStyle({ cursor: disable ? 'wait': null });
        } else {
            HordeCore.loadingImg('sendingImg', 'composeMessageParent', disable);
            DimpCore.toggleButtons($('compose').select('DIV.dimpActions A'), disable);
            [ $('compose') ].invoke(disable ? 'disable' : 'enable');
            if (DIMP.SpellChecker) {
                DIMP.SpellChecker.disable(disable);
            }
            if (ImpComposeBase.editor_on) {
                this.RTELoading(disable ? 'show' : 'hide', true);
            }

            $('compose').setStyle({ cursor: disable ? 'wait' : null });
        }
    },

    toggleHtmlEditor: function(noupdate)
    {
        if (!DIMP.conf.rte_avail) {
            return;
        }

        noupdate = noupdate || false;
        if (DIMP.SpellChecker) {
            DIMP.SpellChecker.resume();
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
        DIMP.SpellChecker.htmlAreaParent = ImpComposeBase.editor_on
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
            HordeCore.notify(DIMP.text.spell_noerror, 'horde.message');
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

    // opts = auto, focus, fwd_list, noupdate, priority, show_editor
    fillForm: function(msg, header, opts)
    {
        if (!document.loaded || !$('pageContainer').visible()) {
            this.fillForm.bind(this, msg, header, opts).defer();
            return;
        }

        var bcc_add,
            identity = ImpComposeBase.identities[$F('last_identity')];
        opts = opts || {};

        $('to').setValue(header.to);
        if (DIMP.conf.cc && header.cc) {
            this.toggleCC('cc');
            $('cc').setValue(header.cc);
        }
        this.setPopdownLabel('sm', identity.sm_name, identity.sm_display);
        this.setSaveSentMail(identity.sm_save);
        if (DIMP.conf.bcc) {
            bcc_add = header.bcc
                ? header.bcc
                : $F('bcc');
            if (identity.bcc) {
                if (!bcc_add.empty()) {
                    bcc_add += ', ';
                }
                bcc_add += identity.bcc;
            }
            if (!bcc_add.empty()) {
                this.toggleCC('bcc');
                $('bcc').setValue(bcc_add);
            }
        }
        $('subject').setValue(header.subject);

        if (DIMP.conf.priority && opts.priority) {
            this.setPopdownLabel('p', opts.priority);
        }

        if (opts.readreceipt && $('request_read_receipt')) {
            $('request_read_receipt').setValue(true);
        }

        this.processFwdList(opts.fwd_list);

        switch (opts.auto) {
        case 'forward_attach':
            $('noticerow', 'fwdattachnotice').invoke('show');
            $('composeMessage').stopObserving('keydown').observe('keydown', this.fadeNotice.bind(this, 'fwdattachnotice'));
            break

        case 'forward_body':
            $('noticerow', 'fwdbodynotice').invoke('show');
            break

        case 'reply_all':
            $('replyallnotice').down('SPAN.replyAllNoticeCount').setText(DIMP.text.replyall.sub('%d', opts.reply_recip));
            $('noticerow', 'replyallnotice').invoke('show');
            break

        case 'reply_list':
            $('replylistnotice').down('SPAN.replyListNoticeId').setText(opts.reply_list_id ? (' (' + opts.reply_list_id + ')') : '');
            $('noticerow', 'replylistnotice').invoke('show');
            break;
        }

        if (opts.reply_lang) {
            $('langnotice').down('SPAN.langNoticeList').setText(opts.reply_lang.join(', '));
            $('noticerow', 'langnotice').invoke('show');
        }

        this.setBodyText(msg);
        this.resizeMsgArea();

        Field.focus(opts.focus || 'to');

        if (DIMP.conf.show_editor || opts.show_editor) {
            if (!ImpComposeBase.editor_on) {
                this.toggleHtmlEditor(opts.noupdate);
            }
            if (opts.focus && (opts.focus == 'composeMessage')) {
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
        if (DIMP.conf.auto_save_interval_val &&
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
            }.bind(this), DIMP.conf.auto_save_interval_val * 60);

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
            ImpComposeBase.setCursorPosition('composeMessage', DIMP.conf.compose_cursor);
        }
    },

    processFwdList: function(f)
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
                this.processFwdList(r.opts.fwd_list);
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
        var span = new Element('SPAN').insert(opts.name),
            li = new Element('LI').insert(span).store('atc_id', opts.num);
        if (opts.fwdattach) {
            li.insert(' (' + opts.size + ' KB)');
            span.addClassName('attachNameFwdmsg');
        } else {
            li.insert(' [' + opts.type + '] (' + opts.size + ' KB) ').insert(new Element('SPAN', { className: 'button remove' }).insert(DIMP.text.remove));
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

        if (DIMP.conf.attach_limit != -1 &&
            $('attach_list').childElements().size() >= DIMP.conf.attach_limit) {
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

    resizeMsgArea: function(e)
    {
        if (this.resizing) {
            return;
        }

        if (!document.loaded || !$('pageContainer').visible()) {
            this.resizeMsgArea.bind(this).defer();
            return;
        }

        // IE 7/8 Bug - can't resize TEXTAREA in the resize event (Bug #10075)
        if (e && Prototype.Browser.IE) {
            this.resizeMsgArea.bind(this).delay(0.1);
            return;
        }

        var lh, mah, msg, msg_h, rows,
            cmp = $('composeMessageParent'),
            de = document.documentElement,
            pad = 0;

        /* Needed because IE 8 will trigger resize events when we change
         * the rows attribute, which will cause an infinite loop. */
        this.resizing = true;

        mah = document.viewport.getHeight() - cmp.offsetTop;

        if (this.rte_loaded) {
            [ 'margin', 'padding', 'border' ].each(function(s) {
                [ 'Top', 'Bottom' ].each(function(h) {
                    var a = parseInt(cmp.getStyle(s + h), 10);
                    if (!isNaN(a)) {
                        pad += a;
                    }
                });
            });

            this.rte.resize('99%', mah - pad - 1, false);
        } else if (!ImpComposeBase.editor_on) {
            /* Logic: Determine the size of a given textarea row, divide
             * that size by the available height, round down to the lowest
             * integer row, and resize the textarea. */
            msg = $('composeMessage');
            rows = parseInt(mah / (msg.getHeight() / msg.readAttribute('rows')), 10);

            if (!isNaN(rows)) {
                /* Due to the funky (broken) way some browsers (FF) count
                 * rows, we need to overshoot row estimate and decrement
                 * until textarea size does not cause window scrolling. */
                ++rows;
                do {
                    msg.writeAttribute({ rows: rows--, disabled: false });
                } while (rows && (de.scrollHeight - de.clientHeight) > 0);
            }
        }

        if ($('rteloading') && $('rteloading').visible()) {
            this.RTELoading();
        }

        this.resizing = false;
    },

    uploadAttachment: function()
    {
        var u = $('upload');
        this.uniqueSubmit('addAttachment');
        u.up().hide().next().hide();
        $('upload_wait').update(DIMP.text.uploading + ' (' + $F(u) + ')').show();
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
        var uri = DIMP.conf.URI_ABOOK;

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
    clickHandler: function(parentfunc, e)
    {
        if (e.isRightClick()) {
            return;
        }

        var elt = e.element(),
            orig = elt,
            atc_num, id, tmp;

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
                    window.confirm(DIMP.text.toggle_html)) {
                    this.toggleHtmlEditor();
                } else {
                    $('htmlcheckbox').setValue(true);
                }
                break;

            case 'redirect_sendto':
                if (orig.match('TD.label SPAN')) {
                    this.openAddressbook({
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
                    atc_num = orig.up('LI').retrieve('atc_id');
                    HordeCore.popupWindow(DIMP.conf.URI_VIEW, {
                        actionID: 'compose_attach_preview',
                        composeCache: $F('composeCache'),
                        id: atc_num
                    }, {
                        name: $F('composeCache') + '|' + atc_num
                    });
                }
                break;

            case 'save_sent_mail':
                this.setSaveSentMail($F(elt));
                break;

            case 'fwdattachnotice':
            case 'fwdbodynotice':
                this.fadeNotice(elt);
                DimpCore.doAction('GetForwardData', { dataonly: 1, imp_compose: $F('composeCache'), type: (id == 'fwdattachnotice' ? 'forward_body' : 'forward_attach') }, { callback: this.forwardAddCallback.bind(this) });
                $('composeMessage').stopObserving('keydown');
                e.stop();
                return;

            case 'identitychecknotice':
                this.fadeNotice(elt);
                $('identity').setValue(this.old_identity);
                this.changeIdentity();
                e.stop();
                return;

            case 'replyall_revert':
            case 'replylist_revert':
                this.fadeNotice(elt.up('LI'));
                $('to_loading_img').show();
                DimpCore.doAction('getReplyData', { headeronly: 1, imp_compose: $F('composeCache'), type: 'reply' }, { callback: this.swapToAddressCallback.bind(this) });
                e.stop();
                return;

            case 'writemsg':
                if (!this.disabled &&
                    e.element().hasClassName('dimpOptionPopdown')) {
                    tmp = e.element().retrieve('popdown_id');
                    this.knl[tmp].knl.show();
                    this.knl[tmp].knl.ignoreClick(e);
                    e.stop();
                }
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

    contextOnClick: function(parentfunc, e)
    {
        var id = e.memo.elt.readAttribute('id'), tmp;

        switch (id) {
        case 'ctx_msg_other_rr':
            tmp = !$F('request_read_receipt');
            $('request_read_receipt').setValue(tmp);
            DimpCore.toggleCheck($('ctx_msg_other_rr').down('DIV'), tmp);
            break;

        case 'ctx_msg_other_saveatc':
            tmp = !$F('save_attachments_select');
            $('save_attachments_select').setValue(tmp);
            DimpCore.toggleCheck($('ctx_msg_other_saveatc').down('DIV'), tmp);
            break;

        default:
            parentfunc(e);
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
            if (DIMP.conf.redirect) {
                e.memo.field = 'redirect_to';
            }
            break;
        }

        ImpComposeBase.updateAddressField($(e.memo.field), e.memo.value);
    },

    onDomLoad: function()
    {
        var tmp;

        DimpCore.init();

        this.is_popup = !Object.isUndefined(HordeCore.base);

        /* Initialize redirect elements. */
        if (DIMP.conf.redirect) {
            $('redirect').observe('submit', Event.stop);
            new TextareaResize('redirect_to');
            if (DIMP.conf.URI_ABOOK) {
                $('redirect_sendto').down('TD.label SPAN').addClassName('composeAddrbook');
            }
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
        Event.observe(window, 'resize', this.resizeMsgArea.bindAsEventListener(this));
        $('compose').observe('submit', Event.stop);

        HordeCore.handleSubmit($('compose'), {
            callback: this.uniqueSubmitCallback.bind(this)
        });

        // Initialize spell checker
        document.observe('SpellChecker:noerror', this._onSpellCheckNoError.bind(this));
        if (DIMP.conf.rte_avail) {
            document.observe('SpellChecker:after', this._onSpellCheckAfter.bind(this));
            document.observe('SpellChecker:before', this._onSpellCheckBefore.bind(this));
            document.observe('SpellChecker:error', this._onSpellCheckError.bind(this));
        }

        tmp = $('msg_other_options');
        if (tmp.childElements().size()) {
            DimpCore.addPopdown(tmp.down('A'), 'msg_other', {
                trigger: true
            });
            if (tmp = $('ctx_msg_other_rr')) {
                DimpCore.toggleCheck(tmp.down('DIV'), $F('request_read_receipt'));
            }
            if (tmp = $('ctx_msg_other_saveatc')) {
                DimpCore.toggleCheck(tmp.down('DIV'), $F('save_attachments_select'));
            }
        } else {
            tmp.hide();
        }

        /* Create sent-mail list. */
        if (DIMP.conf.flist) {
            this.createPopdown('sm', {
                base: 'save_sent_mail',
                data: DIMP.conf.flist,
                input: 'save_sent_mail',
                label: 'sent_mail_label'
            });
            this.setPopdownLabel('sm', ImpComposeBase.identities[$F('identity')].sm_name);
        }

        /* Create priority list. */
        if (DIMP.conf.priority) {
            this.createPopdown('p', {
                base: 'priority_label',
                data: DIMP.conf.priority,
                input: 'priority',
                label: 'priority_label'
            });
            this.setPopdownLabel('p', $F('priority'));
        }

        /* Create encryption list. */
        if (DIMP.conf.encrypt) {
            this.createPopdown('e', {
                base: $('encrypt_label').up(),
                data: DIMP.conf.encrypt,
                input: 'encrypt',
                label: 'encrypt_label'
            });
            this.setPopdownLabel('e', $F('encrypt'));
        }

        new TextareaResize('to');

        /* Add addressbook link formatting. */
        if (DIMP.conf.URI_ABOOK) {
            $('sendto', 'sendcc', 'sendbcc', 'redirect_sendto').compact().each(function(a) {
                a.down('TD.label SPAN').addClassName('composeAddrbook');
            });
        }

        $('dimpLoading').hide();
        $('pageContainer').show();

        this.resizeMsgArea();
    }

};

/* Attach event handlers. */
document.observe('dom:loaded', DimpCompose.onDomLoad.bind(DimpCompose));
document.observe('ImpContacts:update', DimpCompose.onContactsUpdate.bindAsEventListener(DimpCompose));
document.observe('TextareaResize:resize', DimpCompose.resizeMsgArea.bind(DimpCompose));

/* ContextSensitive functions. */
DimpCore.contextOnClick = DimpCore.contextOnClick.wrap(DimpCompose.contextOnClick.bind(DimpCompose));

/* Click handler. */
DimpCore.clickHandler = DimpCore.clickHandler.wrap(DimpCompose.clickHandler.bind(DimpCompose));

/* Catch dialog actions. */
document.observe('HordeDialog:success', function(e) {
    switch (e.memo) {
    case 'pgpPersonal':
    case 'pgpSymmetric':
    case 'smimePersonal':
        HordeDialog.noreload = true;
        DimpCompose.retrySubmit();
        break;
    }
});
