/**
 * Dynamic compose view.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2005-2014 Horde LLC
 * @license    GPLv2 (http://www.horde.org/licenses/gpl)
 */

var DimpCompose = {

    // Variables defaulting to empty/false:
    //   atc_context, auto_save_interval, compose_cursor, disabled,
    //   drafts_mbox, editor_wait, fwdattach, hash_hdrs, hash_msg,
    //   hash_msgOrig, hash_sig, hash_sigOrig, knl, last_identity,
    //   onload_show, old_action, old_identity, sc_submit,
    //   skip_spellcheck, spellcheck, tasks, upload_limit

    ajax_atc_id: 0,
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
                DimpCore.baseAvailable()) {
                HordeCore.base.focus();
            }

            DimpCore.doAction('cancelCompose', this.actionParams({
                discard: ~~(!!discard)
            }));
            this.updateDraftsMailbox();
            return this.closeCompose();
        }
    },

    updateDraftsMailbox: function()
    {
        if (DimpCore.baseAvailable() &&
            HordeCore.base.DimpBase.view == DimpCore.conf.drafts_mbox) {
            HordeCore.base.DimpBase.poll();
        }
    },

    closeCompose: function()
    {
        if (DimpCore.conf.qreply) {
            this.closeQReply();
        } else if (HordeCore.base) {
            // Want HordeCore.base directly here, since we are only checking
            // whether window.close can be done, not the current status
            // of the opening window.
            window.close();
        } else {
            // Sanity checking: if popup cannot be manually closed, output
            // information message without allowing further actions on the
            // page.
            $$('body')[0].update(DimpCore.text.compose_close);
        }
    },

    closeQReply: function()
    {
        this.hash_hdrs = this.hash_msg = this.hash_msgOrig = this.hash_sig = this.hash_sigOrig = '';
        this.upload_limit = false;

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
        if (!Object.isUndefined(this.hash_sigOrig) &&
            this.hash_sigOrig != this.sigHash() &&
            !window.confirm(DimpCore.text.change_identity)) {
            return false;
        }

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
        ImpComposeBase.setSignature(ImpComposeBase.editor_on, identity);
        this.last_identity = $F('identity');

        return true;
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
        var input;

        if (!k) {
            k = this.knl[id];
            if (!k) {
                return;
            }
        }

        input = $(k.opts.input);
        if (!input) {
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

        input.setValue(s);
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

        if (this.editor_wait) {
            return this.uniqueSubmit.bind(this, action).delay(0.1);
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
            if ($('upload_wait').visible()) {
                (function() { if (this.disabled) { this.uniqueSubmit(action); } }).bind(this).delay(0.25);
                return;
            }
            // Fall-through

        case 'redirectMessage':
            $(document).fire('AutoComplete:update');
            break;
        }

        this.skip_spellcheck = false;

        if (action == 'addAttachment') {
            // We need a submit action here because browser security models
            // won't let us access files on user's filesystem otherwise.
            HordeCore.submit(c);
        } else {
            // Move HTML text to textarea field for submission.
            if (ImpComposeBase.editor_on) {
                ImpComposeBase.rte.updateElement();
                if (!Object.isUndefined(ImpComposeBase.rte_sig)) {
                    ImpComposeBase.rte_sig.updateElement();
                }
            }

            DimpCore.doAction(
                action,
                ImpComposeBase.sendParams(c.serialize(true), action == 'sendMessage'),
                { callback: this.uniqueSubmitCallback.bind(this) }
            );

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
                    if (!DimpCore.conf.qreply && DimpCore.baseAvailable()) {
                        HordeCore.notify_handler = HordeCore.base.HordeCore.showNotifications.bind(HordeCore.base.HordeCore);
                    }
                    if (DimpCore.conf.close_draft) {
                        $('attach_list').childElements().invoke('remove');
                        return this.closeCompose();
                    }
                }
                break;

            case 'saveTemplate':
                if (DimpCore.baseAvailable() &&
                    HordeCore.base.DimpBase.view == DimpCore.conf.templates_mbox) {
                    HordeCore.base.DimpBase.poll();
                }
                return this.closeCompose();

            case 'sendMessage':
                if (DimpCore.baseAvailable()) {
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
                if (DimpCore.baseAvailable() && !DimpCore.conf.qreply) {
                    HordeCore.notify_handler = HordeCore.base.HordeCore.showNotifications.bind(HordeCore.base.HordeCore);
                }

                return this.closeCompose();

            case 'addAttachment':
                this.addAttachmentEnd();
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
        var action, changed, sc, tmp,
            active = ImpComposeBase.editor_on,
            params = $H();

        if (!DimpCore.conf.rte_avail) {
            return;
        }

        noupdate = noupdate || false;
        if ((sc = ImpComposeBase.getSpellChecker()) && sc.isActive()) {
            sc.resume();
        }

        if (this.editor_wait) {
            return this.toggleHtmlEditor.bind(this, noupdate).delay(0.1);
        }

        this.RTELoading('show');

        if (active) {
            action = 'html2Text',
            changed = ~~(this.msgHash() != this.hash_msgOrig);

            params.set('body', {
                changed: changed,
                text: ImpComposeBase.rte.getData()
            });

            if ($('signature') && (this.sigHash() != this.hash_sigOrig)) {
                params.set('sig', {
                    changed: 1,
                    text: ImpComposeBase.rte_sig.getData()
                });
            }
        } else if (!noupdate) {
            action = 'text2Html';

            tmp = $F('composeMessage');
            if (!tmp.blank()) {
                changed = ~~(this.msgHash() != this.hash_msgOrig);
                params.set('body', {
                    changed: changed,
                    text: tmp
                });
            }

            if ($('signature')) {
                tmp = $F('signature');
                if (!tmp.blank() && (this.sigHash() != this.hash_sigOrig)) {
                    params.set('sig', {
                        changed: 1,
                        text: tmp
                    });
                }
            }
        }

        if (params.size()) {
            DimpCore.doAction(action, this.actionParams({
                data: Object.toJSON(params)
            }), {
                ajaxopts: { asynchronous: false },
                callback: this.setMessageText.bind(this, {
                    changed: changed,
                    rte: !active
                })
            });
        } else {
            this.rteInit(!active);
            ImpComposeBase.setSignature(!active, ImpComposeBase.identities[$F('identity')]);
        }
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
            this.setBodyText({ body: $F('composeMessage') });
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
            ImpComposeBase.rte.updateElement();
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

    setMessageText: function(opts, r)
    {
        var ta = $('composeMessage');
        if (!ta) {
            $('composeMessageParent').insert(
                new Element('TEXTAREA', {
                    id: 'composeMessage',
                    name: 'message',
                    style: 'width:100%'
                })
            );
        }

        this.rteInit(opts.rte);

        if (ImpComposeBase.rte_loaded && opts.rte) {
            ImpComposeBase.rte.setData(r.text.body);
        } else if (!ImpComposeBase.rte_loaded && !opts.rte) {
            ta.setValue(r.text.body);
        } else {
            this.setMessageText.bind(this, opts, r).delay(0.1);
            return;
        }

        ImpComposeBase.setSignature(opts.rte, r.text.sig ? r.text.sig : ImpComposeBase.identities[$F('identity')]);

        this.resizeMsgArea();

        if (!opts.changed) {
            delete this.hash_msgOrig;
        }

        this.fillFormHash();
    },

    rteInit: function(rte)
    {
        var config;

        if (rte && !ImpComposeBase.rte) {
            if (Object.isUndefined(ImpComposeBase.rte_loaded)) {
                CKEDITOR.on('instanceReady', function(evt) {
                    new CKEDITOR.dom.document(
                        evt.editor.getThemeSpace('contents').$.down('IFRAME').contentWindow.document)
                    .on('keydown', function(evt) {
                        this.keydownHandler(Event.extend(evt.data.$), true);
                    }.bind(this));
                }.bind(this));
                CKEDITOR.on('instanceDestroyed', function(evt) {
                    this.RTELoading('hide');
                    ImpComposeBase.rte_loaded = false;
                }.bind(this));
            }

            config = Object.clone(IMP.ckeditor_config);
            config.extraPlugins = 'pasteattachment';
            ImpComposeBase.rte = CKEDITOR.replace('composeMessage', config);

            ImpComposeBase.rte.on('dataReady', function(evt) {
                this.RTELoading('hide');
                evt.editor.focus();
                ImpComposeBase.rte_loaded = true;
                this.resizeMsgArea();
            }.bind(this));
            ImpComposeBase.rte.on('getData', function(evt) {
                var elt = new Element('SPAN').insert(evt.data.dataValue),
                    elts = elt.select('IMG[dropatc_id]');
                if (elts.size()) {
                    elts.invoke('writeAttribute', 'dropatc_id', null);
                    elts.invoke('writeAttribute', 'src', null);
                    evt.data.dataValue = elt.innerHTML;
                }
            }.bind(this));
        } else if (!rte && ImpComposeBase.rte) {
            ImpComposeBase.rte.destroy(true);
            delete ImpComposeBase.rte;
        }

        ImpComposeBase.editor_on = rte;
        $('htmlcheckbox').setValue(rte);
        $('html').setValue(~~rte);
    },

    // ob = addr, body, format, identity, opts, subject, type
    // ob.opts = auto, focus, fwd_list, noupdate, priority, readreceipt,
    //           reply_lang, reply_recip, reply_list_id, show_editor
    fillForm: function(ob)
    {
        if (!document.loaded || $('dimpLoading').visible()) {
            this.fillForm.bind(this, ob).delay(0.1);
            return;
        }

        switch (ob.type) {
        case 'forward_redirect':
            return;
        }

        ob.opts = ob.opts || {};

        if (ob.addr) {
            $('to').setValue(ob.addr.to.join(', '))
                .fire('AutoComplete:reset');
            if (ob.addr.cc.size()) {
                this.toggleCC('cc');
                $('cc').setValue(ob.addr.cc.join(', '))
                    .fire('AutoComplete:reset');
            }
            if (ob.addr.bcc.size()) {
                this.toggleCC('bcc');
                $('bcc').setValue(ob.addr.bcc.join(', '))
                    .fire('AutoComplete:reset');
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

        ImpComposeBase.focus(ob.opts.focus || 'to');

        this.fillFormHash();
    },

    fillFormHash: function()
    {
        if (ImpComposeBase.editor_on && !ImpComposeBase.rte_loaded) {
            this.fillFormHash.bind(this).delay(0.1);
            return;
        }

        // This value is used to determine if the text has changed when
        // swapping compose modes.
        if (!this.hash_msgOrig) {
            this.hash_msgOrig = this.msgHash();
        }
        this.hash_sigOrig = this.sigHash();

        // Set auto-save-drafts now if not already active.
        if (DimpCore.conf.auto_save_interval_val &&
            !this.auto_save_interval) {
            this.auto_save_interval = new PeriodicalExecuter(
                this.autoSaveDraft.bind(this),
                DimpCore.conf.auto_save_interval_val * 60
            );

            /* Immediately execute to get hash of headers. */
            this.auto_save_interval.execute();
        }
    },

    autoSaveDraft: function()
    {
        var hdrs, msg, sig;

        if (!$('compose').visible()) {
            return;
        }

        hdrs = IMP_JS.fnv_1a(
            [$('to', 'cc', 'bcc', 'subject').compact().invoke('getValue'),
             $('attach_list').select('SPAN.attachName').pluck('textContent')
            ].flatten().join('\0')
        );

        if (Object.isUndefined(this.hash_hdrs)) {
            msg = this.hash_msgOrig;
            sig = this.hash_sigOrig;
        } else {
            msg = this.msgHash();
            sig = this.sigHash();
            if (this.hash_hdrs != hdrs ||
                this.hash_msg != msg ||
                this.hash_sig != sig) {
                this.uniqueSubmit('autoSaveDraft');
            }
        }

        this.hash_hdrs = hdrs;
        this.hash_msg = msg;
        this.hash_sig = sig;
    },

    msgHash: function()
    {
        return IMP_JS.fnv_1a(
            ImpComposeBase.editor_on ? ImpComposeBase.rte.getData() : $F('composeMessage')
        );
    },

    sigHash: function()
    {
        return $('signature')
            ? IMP_JS.fnv_1a(ImpComposeBase.rte_sig ? ImpComposeBase.rte_sig.getData() : $F('signature'))
            : 0;
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
            ImpComposeBase.rte.setData(ob.body, function() {
                this.editor_wait = false;
            }.bind(this));
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
            $('to').setValue(r.addr.to.join(', '))
                .fire('AutoComplete:reset');
            [ 'cc', 'bcc' ].each(function(t) {
                if (r.addr[t].size() || $('send' + t).visible()) {
                    if (!$('send' + t).visible()) {
                        this.toggleCC(t);
                    }
                    $(t).setValue(r.addr[t].join(', '))
                        .fire('AutoComplete:reset');
                }
            }, this);
        }
        $('to_loading_img').hide();
    },

    focusEditor: function()
    {
        if (ImpComposeBase.rte.focus) {
            ImpComposeBase.rte.focus();
        } else {
            this.focusEditor.bind(this).delay(0.1);
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
            // IE8 doesn't support canvas
            if (canvas.getContext) {
                li.insert(canvas);
                img = new Image();
                img.onload = function() {
                    canvas.getContext('2d').drawImage(img, 0, 0, 16, 16);
                };
                img.src = opts.icon;
            }
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

        if (this.upload_limit) {
            $('upload_limit').show();
            u.up().hide();
        } else {
            $('upload_limit').hide();
            u.up().show();
        }

        if (!al.childElements().size()) {
            al.hide();
        }

        this.resizeMsgArea();
    },

    addAttachmentEnd: function()
    {
        var u = $('upload_wait');

        if (u.visible()) {
            u.hide();
            this.initAttachList();
        }
    },

    resizeMsgArea: function(e)
    {
        if (!document.loaded || $('dimpLoading').visible()) {
            this.resizeMsgArea.bind(this).delay(0.1);
            return;
        }

        // IE 7/8 Bug - can't resize TEXTAREA in the resize event (Bug #10075)
        if (e && Prototype.Browser.IE && !document.addEventListener) {
            this.resizeMsgArea.bind(this).delay(0.1);
            return;
        }

        var mah,
            cmp = $('composeMessageParent'),
            sp = $('signatureParent'),
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
        if (sp) {
            mah -= sp.getHeight();
        }

        if (ImpComposeBase.rte_loaded) {
            ImpComposeBase.rte.resize('99%', mah - 1, false);
        }

        $('composeMessage').setStyle({ height: mah + 'px' });

        if ($('rteloading') && $('rteloading').visible()) {
            this.RTELoading('hide');
        }
    },

    uploadAttachmentWait: function(f)
    {
        var t;

        $('upload').up().hide();

        if (Object.isElement(f)) {
            if (f.files) {
                f = f.files;
            } else {
                f = null;
                t = $F(f).escapeHTML();
            }
        }

        if (f) {
            t = (f.length > 1)
                ? DimpCore.text.multiple_atc.sub('%d', f.length)
                : f[0].name.escapeHTML();
        }

        $('upload_wait').update(DimpCore.text.uploading + ' (' + t + ')')
            .show();
    },

    uploadAttachmentAjax: function(data, params, callback)
    {
        var out = $H();

        params = $H(params).update({
            composeCache: $F(this.getCacheElt()),
            json_return: 1
        });
        HordeCore.addRequestParams(params);

        this.uploadAttachmentWait(data);

        $A($R(0, data.length - 1)).each(function(i) {
            var fd = new FormData();

            params.merge({
                file_id: ++this.ajax_atc_id,
                file_upload: data[i]
            }).each(function(p) {
                fd.append(p.key, p.value);
            });

            HordeCore.doAction('addAttachment', {}, {
                ajaxopts: {
                    postBody: fd,
                    requestHeaders: { "Content-type": null }
                },
                callback: function(r) {
                    if (callback) {
                        callback(r);
                    }
                    this.addAttachmentEnd();
                }.bind(this)
            });

            out.set(this.ajax_atc_id, data[i]);
        }, this);

        return out;
    },

    toggleCC: function(type)
    {
        var t = $('toggle' + type),
            s = t.siblings().first();

        $('send' + type).show();
        if (s && s.visible()) {
            t.hide();
        } else {
            t.up('TR').hide();
        }

        this.resizeMsgArea();

        ImpComposeBase.focus(type);
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
        this.knl.sm.knl.setSelected($F('save_sent_mail_mbox'));
        this.knl.sm.knl.show();
    },

    /* Open the addressbook window. */
    openAddressbook: function(params)
    {
        var uri = DimpCore.conf.URI_ABOOK;

        if (params) {
            uri = HordeCore.addURLParam(uri, params);
        }

        window.open(uri, 'contacts', 'toolbar=no,location=no,status=no,scrollbars=yes,resizable=yes,width=800,height=350,left=100,top=100');
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

        case 'signatureToggle':
            if ($('signatureBorder').visible()) {
                $('signatureToggle').removeClassName('signatureExpanded');
                $('signatureBorder').hide();
                HordeCore.doAction('setPrefValue', {
                    pref: 'signature_expanded',
                    value: 0
                });
            } else {
                $('signatureToggle').addClassName('signatureExpanded');
                $('signatureBorder').show();
                HordeCore.doAction('setPrefValue', {
                    pref: 'signature_expanded',
                    value: 1
                });
            }
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
            e.memo.stop();
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
                if (tmp && this.knl[tmp]) {
                    this.knl[tmp].knl.show();
                    this.knl[tmp].knl.ignoreClick(e.memo);
                    e.memo.stop();
                }
            }
            break;

        case 'save_sent_mail_load':
            DimpCore.doAction('sentMailList', {}, {
                callback: this.sentMailListCallback.bind(this)
            });
            break;
        }
    },

    keydownHandler: function(e, fade)
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

        if (this.fwdattach &&
            (fade || e.element() == $('composeMessage'))) {
            this.fadeNotice('fwdattachnotice');
        }
    },

    changeHandler: function(e)
    {
        switch (e.element().readAttribute('id')) {
        case 'identity':
            if (!this.changeIdentity()) {
                $('identity').setValue(this.last_identity);
            }
            break;

        case 'upload':
            this.uniqueSubmit('addAttachment');
            this.uploadAttachmentWait($('upload'));
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
                        $(t).setValue(~~(!(~~$F(t))));
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
                    DimpCore.toggleCheck(t.down('SPAN'), ~~$F(pair.value));
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
            this.upload_limit = t['imp:compose'].atclimit;
        }

        if (t['imp:compose-atc']) {
            t['imp:compose-atc'].each(this.addAttach.bind(this));
        }

        if (DimpCore.baseAvailable()) {
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
        /* Initialize redirect elements. */
        if (DimpCore.conf.redirect) {
            $('redirect').observe('submit', Event.stop);
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
        $('htmlcheckbox').up('LABEL').observe('mouseup', function() {
            if (!ImpComposeBase.editor_on ||
                window.confirm(DimpCore.text.toggle_html)) {
                this.toggleHtmlEditor();
            }
        }.bind(this));

        HordeCore.initHandler('click');
        HordeCore.handleSubmit($('compose'), {
            callback: this.uniqueSubmitCallback.bind(this)
        });

        if ($H(DimpCore.context.ctx_atc).size()) {
            $('atcdrop').observe('DragHandler:drop', function(e) {
                this.uploadAttachmentAjax(e.memo);
            }.bindAsEventListener(this));
            DragHandler.dropelt = $('atcdrop');
            DragHandler.droptarget = $('atcdiv');
            DragHandler.hoverclass = 'atcdrop_over';
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
    },

    onAjaxFailure: function(e)
    {
        switch (e.memo[0].request.action) {
        case 'redirectMessage':
        case 'saveDraft':
        case 'saveTemplate':
        case 'sendMessage':
            if (this.disabled) {
                this.setDisabled(false);
            }
            break;
        }

        this.addAttachmentEnd();

        if ($('rteloading') && $('rteloading').visible()) {
            this.RTELoading('hide');
        }
    }

};

/* Attach event handlers. */
document.observe('dom:loaded', DimpCompose.onDomLoad.bind(DimpCompose));
document.observe('HordeCore:click', DimpCompose.clickHandler.bindAsEventListener(DimpCompose));
document.observe('keydown', DimpCompose.keydownHandler.bindAsEventListener(DimpCompose));
Event.observe(window, 'resize', DimpCompose.resizeMsgArea.bindAsEventListener(DimpCompose));

/* Other UI event handlers. */
document.observe('AutoComplete:resize', DimpCompose.resizeMsgArea.bind(DimpCompose));
document.observe('ImpContacts:update', DimpCompose.onContactsUpdate.bindAsEventListener(DimpCompose));

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
    DimpCompose.tasksHandler(e.memo);
});

/* AJAX related events. */
document.observe('HordeCore:ajaxFailure', DimpCompose.onAjaxFailure.bindAsEventListener(DimpCompose));
