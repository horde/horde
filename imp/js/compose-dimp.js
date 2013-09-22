/**
 * compose.js - Javascript code used in the dynamic compose view.
 *
 * Copyright 2005-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

var DimpCompose = {

    // Variables defaulting to empty/false:
    //   atc_context, auto_save_interval, compose_cursor, disabled,
    //   drafts_mbox, editor_wait, fwdattach, is_popup, knl, md5_hdrs,
    //   md5_msg, md5_msgOrig, onload_show, old_action, old_identity, rte,
    //   rte_loaded, sc_submit, skip_spellcheck, spellcheck, tasks, uploading

    checkbox_context: $H({
        ctx_atc: $H({
            pgppubkey: 'pgp_attach_pubkey',
            save: 'save_attachments_select',
            vcard: 'vcard_attach'
        }),
        ctx_other: $H({
            rr: 'request_read_receipt'
        })
    }),
    knl: {},
    seed: 3,

    getCacheElt: function()
    {
        var r = $('redirect');
        return (r && r.visible())
            ? $('composeCacheRedirect')
            : $('composeCache');
    },

    actionParams: function(p)
    {
        p.imp_compose = $F(this.getCacheElt());
        return p;
    },

    confirmCancel: function(discard)
    {
        if (window.confirm(DimpCore.text.compose_cancel)) {
            if (!DimpCore.conf.qreply &&
                this.baseAvailable()) {
                HordeCore.base.focus();
            }

            DimpCore.doAction('cancelCompose', this.actionParams({
                discard: Number(Boolean(discard))
            }));
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

        $('attach_list').hide().childElements().each(this.removeAttachRow.bind(this));
        this.getCacheElt().clear();
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

        this.setPopdownLabel('sm', identity.sm_name, identity.sm_display, {
            opts: {
                input: 'save_sent_mail_mbox',
                label: 'sent_mail_label'
            }
        });
        if (identity.bcc) {
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
            new Element('SPAN', { className: 'iconImg horde-popdown' }).store('popdown_id', id)
        });
    },

    setPopdownLabel: function(id, s, l, k)
    {
        if (!k) {
            k = this.knl[id];
            if (!k) {
                return;
            }
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

        if (k.knl) {
            k.knl.setSelected(s);
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

            if ($F('subject').empty() &&
                !window.confirm(DimpCore.text.nosubject)) {
                return;
            }
            // Fall-through

        case 'saveDraft':
        case 'saveTemplate':
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
                this._addAttachmentEnd();
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
            if ((sc = ImpComposeBase.getSpellChecker())) {
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
        var changed, sc, text, tmp;

        if (!DimpCore.conf.rte_avail) {
            return;
        }

        noupdate = noupdate || false;
        if ((sc = ImpComposeBase.getSpellChecker())) {
           sc.resume();
        }

        if (ImpComposeBase.editor_on) {
            this.RTELoading('show');

            changed = (this.msgHash() != this.md5_msgOrig);
            text = this.rte.getData();

            DimpCore.doAction('html2Text', this.actionParams({
                changed: Number(changed),
                text: text
            }), {
                ajaxopts: { asynchronous: false },
                callback: this.setMessageText.bind(this, false)
            });

            this.rte.destroy(true);
            delete this.rte;
        } else {
            this.RTELoading('show');

            if (!noupdate) {
                tmp = $F('composeMessage');
                if (!tmp.blank()) {
                    DimpCore.doAction('text2Html', this.actionParams({
                        changed: Number(this.msgHash() != this.md5_msgOrig),
                        text: tmp
                    }), {
                        callback: this.setMessageText.bind(this, true)
                    });
                }
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

    // ob = addr, body, format, identity, opts, subject, type
    // ob.opts = auto, focus, fwd_list, noupdate, priority, readreceipt,
    //           reply_lang, reply_recip, reply_list_id, show_editor
    fillForm: function(ob)
    {
        if (!document.loaded || $('dimpLoading').visible()) {
            this.fillForm.bind(this, ob).defer();
            return;
        }

        switch (ob.type) {
        case 'forward_redirect':
            return;
        }

        ob.opts = ob.opts || {};

        if (ob.addr) {
            $('to').setValue(ob.addr.to.join(', '));
            if (ob.addr.cc.size()) {
                this.toggleCC('cc');
                $('cc').setValue(ob.addr.cc.join(', '));
            }
            if (ob.addr.bcc.size()) {
                this.toggleCC('bcc');
                $('bcc').setValue(ob.addr.cc.join(', '));
            }
        }

        $('identity').setValue(ob.identity);
        this.changeIdentity();

        $('subject').setValue(ob.subject);

        if (DimpCore.conf.priority && ob.opts.priority) {
            this.setPopdownLabel('p', ob.opts.priority);
        }

        if (ob.opts.readreceipt && $('request_read_receipt')) {
            $('request_read_receipt').setValue(true);
        }

        switch (ob.opts.auto) {
        case 'forward_attach':
            $('noticerow', 'fwdattachnotice').invoke('show');
            this.fwdattach = true;
            break;

        case 'forward_body':
            $('noticerow', 'fwdbodynotice').invoke('show');
            break;

        case 'reply_all':
            $('replyallnotice').down('SPAN.replyAllNoticeCount').setText(DimpCore.text.replyall.sub('%d', ob.opts.reply_recip));
            $('noticerow', 'replyallnotice').invoke('show');
            break;

        case 'reply_list':
            $('replylistnotice').down('SPAN.replyListNoticeId').setText(ob.opts.reply_list_id ? (' (' + ob.opts.reply_list_id + ')') : '');
            $('noticerow', 'replylistnotice').invoke('show');
            break;
        }

        if (ob.opts.reply_lang) {
            $('langnotice').down('SPAN.langNoticeList').setText(ob.opts.reply_lang.join(', '));
            $('noticerow', 'langnotice').invoke('show');
        }

        this.setBodyText(ob);
        this.resizeMsgArea();

        Field.focus(ob.opts.focus || 'to');

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
                    var hdrs = murmurhash3($('to', 'cc', 'bcc', 'subject').compact().invoke('getValue').join('\0'), this.seed), msg;
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

            /* Immediately execute to get hash of headers. */
            this.auto_save_interval.execute();
        }
    },

    msgHash: function()
    {
        return murmurhash3(ImpComposeBase.editor_on ? this.rte.getData() : $F('composeMessage'), this.seed);
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

    setBodyText: function(ob)
    {
        if (ImpComposeBase.editor_on) {
            this.editor_wait = true;
            this.rte.setData(ob.body, function() { this.editor_wait = false; }.bind(this));
        } else {
            $('composeMessage').setValue(ob.body);
            ImpComposeBase.setCursorPosition('composeMessage', DimpCore.conf.compose_cursor);
        }

        if (ob.format == 'html') {
            if (!ImpComposeBase.editor_on) {
                this.toggleHtmlEditor(true);
            }
            if (ob.opts &&
                ob.opts.focus &&
                (ob.opts.focus == 'composeMessage')) {
                this.focusEditor();
            }
        }
    },

    swapToAddressCallback: function(r)
    {
        if (r.addr) {
            $('to').setValue(r.addr.to.join(', '));
            [ 'cc', 'bcc' ].each(function(t) {
                if (r.addr[t].size() || $(t).visible()) {
                    if (!$(t).visible()) {
                        this.toggleCC(t);
                    }
                    $(t).setValue(r.addr[t].join(', '));
                }
            }, this);
        }
        $('to_loading_img').hide();
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
    //   icon: (string) Data url of icon data
    //   name: (string) Attachment name
    //   num: (integer) Attachment number
    //   size: (string) Size.
    //   type: (string) MIME type
    //   url: (string) Data view URL
    //   view: (boolean) Link to attachment preview page
    addAttach: function(opts)
    {
        var canvas, img,
            li = new Element('LI')
                .store('atc_id', opts.num)
                .store('atc_url', opts.url),
            span = new Element('SPAN')
                .addClassName(opts.view ? 'attachName' : 'attachNameNoview')
                .insert(opts.name.escapeHTML());

        if (opts.icon) {
            canvas = new Element('CANVAS', { height: '16px', width: '16px' });
            li.insert(canvas);
            img = new Image();
            img.onload = function() {
                canvas.getContext('2d').drawImage(img, 0, 0, 16, 16);
            };
            img.src = opts.icon;
        }

        li.insert(span);

        canvas.writeAttribute('title', opts.type);
        li.insert(
            new Element('SPAN')
                .addClassName('attachSize')
                .insert('(' + opts.size + ')')
        );

        $('attach_list').insert(li).show();

        DimpCore.addPopdown(li.down(':last'), 'atcfile', {
            no_offset: true
        });

        this.resizeMsgArea();
    },

    getAttach: function(id)
    {
        return $('attach_list').childElements().detect(function(e) {
            return e.retrieve('atc_id') == id;
        });
    },

    removeAttach: function(elt)
    {
        DimpCore.doAction('deleteAttach', this.actionParams({
            atc_indices: Object.toJSON([ elt.retrieve('atc_id') ])
        }), {
            callback: this.removeAttachCallback.bind(this)
        });
    },

    removeAttachCallback: function(r)
    {
        r.collect(this.getAttach.bind(this)).compact().each(function(elt) {
            elt.fade({
                afterFinish: function() {
                    this.removeAttachRow(elt);
                    this.initAttachList();
                }.bind(this),
                duration: 0.4
            });
        }, this);
    },

    removeAttachRow: function(elt)
    {
        DimpCore.DMenu.removeElement(elt.down('.horde-popdown').identify());
        elt.remove();
    },

    initAttachList: function()
    {
        var al = $('attach_list'),
            u = $('upload');

        u.clear();

        if (!al.childElements().size()) {
            al.hide();
        }

        this.resizeMsgArea();
    },

    _addAttachmentEnd: function()
    {
        this.uploading = false;
        $('upload_wait').hide();
        this.initAttachList();
    },

    resizeMsgArea: function(e)
    {
        if (!document.loaded || $('dimpLoading').visible()) {
            this.resizeMsgArea.bind(this).defer();
            return;
        }

        // IE 7/8 Bug - can't resize TEXTAREA in the resize event (Bug #10075)
        if (e && Prototype.Browser.IE && !document.addEventListener) {
            this.resizeMsgArea.bind(this).delay(0.1);
            return;
        }

        var mah,
            cmp = $('composeMessageParent'),
            qreply = $('qreply');

        if (!cmp || (qreply && !qreply.visible())) {
            return;
        }

        cmp = cmp.getLayout();

        try {
            mah = document.viewport.getHeight() - cmp.get('top') - cmp.get('margin-box-height') + cmp.get('height');
        } catch (ex) {
            return;
        }

        if (this.rte_loaded) {
            this.rte.resize('99%', mah - 1, false);
        }

        $('composeMessage').setStyle({ height: mah + 'px' });

        if ($('rteloading') && $('rteloading').visible()) {
            this.RTELoading();
        }
    },

    uploadAttachment: function()
    {
        var u = $('upload');
        this.uniqueSubmit('addAttachment');
        u.up().hide();
        $('upload_wait').update(DimpCore.text.uploading + ' (' + $F(u).escapeHTML() + ')').show();
    },

    uploadAttachmentAjax: function(data, params, callback)
    {
        params = $H(params).update({
            composeCache: $F(this.getCacheElt()),
            json_return: 1
        });
        HordeCore.addRequestParams(params);

        $A($R(0, data.length - 1)).each(function(i) {
            var fd = new FormData();

            params.merge({ file_upload: data[i] }).each(function(p) {
                fd.append(p.key, p.value);
            });

            HordeCore.doAction('addAttachment', {}, {
                ajaxopts: {
                    postBody: fd,
                    requestHeaders: { "Content-type": '' }
                },
                callback: callback
            });
        });
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

        $(type).focus();
    },

    sentMailListCallback: function(r)
    {
        $('save_sent_mail_load').remove();
        this.createPopdown('sm', {
            base: 'save_sent_mail',
            data: r.flist,
            input: 'save_sent_mail_mbox',
            label: 'sent_mail_label'
        });
        this.knl['sm'].knl.setSelected($F('save_sent_mail_mbox'));
        this.knl['sm'].knl.show();
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
        var elt = e.memo.element(), tmp;

        /* Needed because reply/forward buttons need to be of type="submit"
         * for FF to correctly size. */
        if ((elt.readAttribute('type') == 'submit') &&
            (elt.descendantOf('compose') || elt.descendantOf('redirect'))) {
            e.memo.hordecore_stop = true;
            return;
        }

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
        case 'redirect_close':
            this.confirmCancel();
            break;

        case 'discard_button':
            this.confirmCancel(true);
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
            if (elt.match('TD.label SPAN')) {
                this.openAddressbook({
                    to_only: 1
                });
            }
            break;

        case 'sendcc':
        case 'sendbcc':
        case 'sendto':
            if (DimpCore.conf.URI_ABOOK && elt.match('TD.label SPAN')) {
                this.openAddressbook();
            }
            break;

        case 'attach_list':
            if (elt.match('SPAN.attachName')) {
                HordeCore.popupWindow(elt.up('LI').retrieve('atc_url'));
            }
            break;

        case 'save_sent_mail':
            this.setSaveSentMail($F(e.element()));
            break;

        case 'compose_upload_add':
            // This is no longer needed as of Firefox 22.
            if (Prototype.Browser.Gecko && Object.isUndefined(Object.is)) {
                $('upload').click();
            }
            break;

        case 'fwdattachnotice':
            this.fadeNotice(e.element());
            DimpCore.doAction('getForwardData', this.actionParams({
                dataonly: 1,
                type: 'forward_body'
            }), {
                callback: this.setBodyText.bind(this)
            });
            this.fwdattach = false;
            e.memo.stop();
            break;

        case 'fwdbodynotice':
            this.fadeNotice(e.element());
            DimpCore.doAction('getForwardData', this.actionParams({
                dataonly: 1,
                type: 'forward_attach'
            }));
            this.fwdattach = false;
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
            DimpCore.doAction('getReplyData', this.actionParams({
                headeronly: 1,
                type: 'reply'
            }), {
                callback: this.swapToAddressCallback.bind(this)
            });
            e.memo.stop();
            break;

        case 'writemsg':
            if (!this.disabled && elt.hasClassName('horde-popdown')) {
                tmp = elt.retrieve('popdown_id');
                this.knl[tmp].knl.show();
                this.knl[tmp].knl.ignoreClick(e.memo);
                e.stop();
            }
            break;

        case 'save_sent_mail_load':
            DimpCore.doAction('sentMailList', {}, {
                callback: this.sentMailListCallback.bind(this)
            });
            break;
        }
    },

    keydownHandler: function(e)
    {
        switch (e.keyCode || e.charCode) {
        case Event.KEY_ESC:
            this.confirmCancel();
            break;

        case Event.KEY_RETURN:
            if (!this.disabled && e.ctrlKey) {
                this.uniqueSubmit('sendMessage');
            }
            break;
        }

        if (this.fwdattach && e.element() == $('composeMessage')) {
            this.fadeNotice('fwdattachnotice');
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
        var id = e.memo.elt.identify();

        switch (id) {
        case 'ctx_atcfile_delete':
            this.removeAttach(this.atc_context);
            break;

        default:
            this.checkbox_context.each(function(pair) {
                if (id.startsWith(pair.key + '_')) {
                    var t = pair.value.get(id.substring(pair.key.length + 1));
                    if (t) {
                        $(t).setValue(Number(!Number($F(t))));
                    }
                }
            });
            break;
        }
    },

    contextOnShow: function(e)
    {
        var tmp = this.checkbox_context.get(e.memo);

        if (tmp) {
            tmp.each(function(pair) {
                var t = $(e.memo + '_' + pair.key);
                if (t) {
                    DimpCore.toggleCheck(t.down('SPAN'), Number($F(pair.value)));
                }
            });
        }

        if (e.element().up('#attach_list')) {
            this.atc_context = e.element().up('LI');
        } else {
            delete this.atc_context;
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

        ImpComposeBase.updateAddressField(e);
    },

    tasksHandler: function(e)
    {
        var t = e.tasks || {};

        if (t['imp:compose']) {
            this.getCacheElt().setValue(t['imp:compose'].cacheid);
            if (t['imp:compose'].atclimit) {
                $('upload_limit').show();
                $('upload').up().hide();
            } else {
                $('upload_limit').hide();
                $('upload').up().show();
            }
        }

        if (t['imp:compose-atc']) {
            t['imp:compose-atc'].each(this.addAttach.bind(this));
        }

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

            this.tasksHandler({ tasks: this.tasks });

            if (this.onload_show) {
                this.fillForm(this.onload_show);
                delete this.onload_show;
            }
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

        if ($H(DimpCore.context.ctx_atc).size()) {
            $('atcdrop').observe('DragHandler:drop', function(e) {
                if (e.memo.dataTransfer) {
                    this.uploadAttachmentAjax(e.memo.dataTransfer.files);
                }
            }.bindAsEventListener(this));
            DragHandler.dropelt = $('atcdrop');
            DragHandler.droptarget = $('atctd');
            DimpCore.addPopdown($('upload'), 'atc', {
                no_offset: true
            });
        }

        if ($H(DimpCore.context.ctx_other).size()) {
            DimpCore.addPopdown($('other_options').down('A'), 'other', {
                trigger: true
            });
        } else {
            $('other_options').hide();
        }

        /* Create sent-mail list. */
        if ($('save_sent_mail_mbox')) {
            this.changeIdentity();
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

        this.tasksHandler({ tasks: this.tasks });

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
document.observe('keydown', DimpCompose.keydownHandler.bindAsEventListener(DimpCompose));
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

/* AJAX related events. */
document.observe('HordeCore:ajaxFailure', function(e) {
    if (this.uploading) {
        this._addAttachmentEnd();
    }
}.bindAsEventListener(DimpCompose));


/* Fix Ajax.Request#setRequestHeaders() behavior (Bug #12418).
 * (This is fixed in prototypejs as of December 2012.) */
Ajax.Request.prototype.setRequestHeaders = Ajax.Request.prototype.setRequestHeaders.wrap(function(orig) {
    if (Object.isFunction(this.transport.setRequestHeader)) {
        this.transport.setRequestHeader = this.transport.setRequestHeader.wrap(function(orig2, name, val) {
            // Don't add headers if value is empty. Due to Bug #44438 in Chrome,
            // we can't prevent default headers from being sent.
            if (!val.empty()) {
                orig2(name, val);
            }
        });
    } else {
        // Can't use wrap() here since setRequestHeader() on IE8 doesn't
        // inherit from Function.prototype (Bug #12474).
        this.transport.setRequestHeader = function(orig2, name, val) {
            if (!val.empty()) {
                orig2(name, val);
            }
        }.curry(this.transport.setRequestHeader);
    }
    orig();
});
