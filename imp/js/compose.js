/**
 * Dynamic compose view.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2005-2014 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */

var ImpCompose = {

    // attachlist,
    // atc_context,
    // auto_save_interval,
    // compose_cursor,
    // disabled,
    // drafts_mbox,
    // editor_on
    // fwdattach,
    // hash_hdrs,
    // hash_msg,
    // hash_msgOrig,
    // hash_sig,
    // hash_sigOrig,
    // identities
    // knl,
    // last_identity,
    // onload_show,
    // old_action,
    // old_identity,
    // rte_sig,
    // sc_submit,
    // skip_spellcheck,
    // spellcheck,
    // tasks

    ac: $H(),
    ac_limit: 20,
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
    classes: {},
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
        var base;

        if (window.confirm(DimpCore.text.compose_cancel)) {
            if (!DimpCore.conf.qreply &&
                (base = DimpCore.baseAvailable())) {
                base.focus();
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
        var base;

        if ((base = DimpCore.baseAvailable()) &&
            base.DimpBase.view == DimpCore.conf.drafts_mbox) {
            base.DimpBase.poll();
        }
    },

    closeCompose: function()
    {
        if (DimpCore.conf.qreply) {
            this.closeQReply();
        } else if (HordeCore.baseWindow() != window) {
            // We are only checking whether window.close can be done, not the
            // current status of the opening window.
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

        this.attachlist.reset();
        this.getCacheElt().clear();
        $('qreply', 'sendcc', 'sendbcc').compact().invoke('hide');
        $('compose_notices').childElements().invoke('hide');
        $('msgData', 'togglecc', 'togglebcc').compact().invoke('show');
        if (this.editor_on) {
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

        var identity = this.identities[$F('identity')];

        this.setPopdownLabel('sm', identity.sm_name, identity.sm_display, {
            opts: {
                input: 'save_sent_mail_mbox',
                label: 'sent_mail_label'
            }
        });
        if (identity.bcc) {
            $('bcc').setValue(($F('bcc') ? $F('bcc') + ', ' : '') + identity.bcc)
                .fire('AutoComplete:reset');
            this.toggleCC('bcc');
        }
        this.setSaveSentMail(identity.sm_save);
        this.setSignature(this.editor_on, identity);
        this.last_identity = $F('identity');

        return true;
    },

    setSignature: function(rte, identity)
    {
        var config, s = $('signature');

        if (!s) {
            this.setSignatureHash();
            return;
        }

        if (rte) {
            s.setValue(Object.isString(identity) ? identity : identity.hsig);

            if (this.rte_sig) {
                this.rte_sig.setData($F('signature'));
            } else {
                config = Object.clone(IMP.ckeditor_config);
                config.removePlugins = 'toolbar,elementspath';
                config.contentsCss = [ CKEDITOR.basePath + 'contents.css', CKEDITOR.basePath + 'nomargin.css' ];
                config.height = ($('signatureBorder') ? $('signatureBorder') : $('signature')).getLayout().get('height');
                this.rte_sig = new IMP_Editor('signature', config);
            }
        } else {
            if (this.rte_sig) {
                this.rte_sig.destroy();
                delete this.rte_sig;
            }
            s.setValue(Object.isString(identity) ? identity : identity.sig);
        }

        this.setSignatureHash();
    },

    setSignatureHash: function()
    {
        if (this.rte_sig && this.rte_sig.busy()) {
            this.setSignatureHash.bind(this).delay(0.1);
        } else {
            this.hash_sigOrig = this.sigHash();
        }
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
            l = (k.opts.data || []).find(function(f) {
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
            sc = this.getSpellChecker();

        if (sc && sc.isActive()) {
            sc.resume();
            this.skip_spellcheck = true;
        }

        if (this.rte && this.rte.busy()) {
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
            if (this.attachlist.busy()) {
                (function() { if (this.disabled) { this.uniqueSubmit(action); } }).bind(this).delay(0.25);
                return;
            }
            // Fall-through

        case 'redirectMessage':
            $(document).fire('AutoComplete:update');
            break;
        }

        this.skip_spellcheck = false;

        // Move HTML text to textarea field for submission.
        if (this.editor_on) {
            this.rte.updateElement();
            if (this.rte_sig) {
                this.rte_sig.updateElement();
            }
        }

        DimpCore.doAction(
            action,
            c.serialize(true),
            { callback: this.uniqueSubmitCallback.bind(this) }
        );

        // Can't disable until we send the message - or else nothing
        // will get POST'ed.
        if (action != 'autoSaveDraft') {
            this.setDisabled(true);
        }
    },

    uniqueSubmitCallback: function(d)
    {
        var base;

        if (d.success) {
            switch (d.action) {
            case 'autoSaveDraft':
            case 'saveDraft':
                this.updateDraftsMailbox();

                if (d.action == 'saveDraft') {
                    if (!DimpCore.conf.qreply &&
                        (base = DimpCore.baseAvailable())) {
                        HordeCore.notify_handler = base.HordeCore.showNotifications.bind(base.HordeCore);
                    }
                    if (DimpCore.conf.close_draft) {
                        $('attach_list').childElements().invoke('remove');
                        return this.closeCompose();
                    }
                }
                break;

            case 'saveTemplate':
                if ((base = DimpCore.baseAvailable()) &&
                    base.DimpBase.view == DimpCore.conf.templates_mbox) {
                    base.DimpBase.poll();
                }
                return this.closeCompose();

            case 'sendMessage':
                if ((base = DimpCore.baseAvailable())) {
                    if (d.draft_delete) {
                        base.DimpBase.poll();
                    }

                    if (!DimpCore.conf.qreply) {
                        HordeCore.notify_handler = base.HordeCore.showNotifications.bind(base.HordeCore);
                    }
                }

                $('attach_list').childElements().invoke('remove');
                return this.closeCompose();

            case 'redirectMessage':
                if (!DimpCore.conf.qreply &&
                    (base = DimpCore.baseAvailable())) {
                    HordeCore.notify_handler = base.HordeCore.showNotifications.bind(base.HordeCore);
                }

                return this.closeCompose();
            }
        } else {
            if (!Object.isUndefined(d.identity)) {
                this.old_identity = $F('identity');
                $('identity').setValue(d.identity);
                this.changeIdentity();
                $('compose_notices', 'identitychecknotice').invoke('show');
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

        if (redirect && redirect.visible()) {
            HordeCore.loadingImg('sendingImg', 'redirect', disable);
            DimpCore.toggleButtons(redirect.select('DIV.dimpActions A'), disable);
            redirect.setStyle({ cursor: disable ? 'wait': null });
        } else {
            HordeCore.loadingImg('sendingImg', 'composeMessageParent', disable);
            DimpCore.toggleButtons($('compose').select('DIV.dimpActions A'), disable);
            [ $('compose') ].invoke(disable ? 'disable' : 'enable');
            if ((sc = this.getSpellChecker())) {
                sc.disable(disable);
            }
            if (this.editor_on) {
                this.RTELoading(disable, true);
            }

            $('compose').setStyle({ cursor: disable ? 'wait' : null });
        }
    },

    toggleHtmlEditor: function(noupdate)
    {
        var action, changed, sc, tmp,
            active = this.editor_on,
            params = $H();

        if (!DimpCore.conf.rte_avail) {
            return;
        }

        noupdate = noupdate || false;
        if ((sc = this.getSpellChecker()) && sc.isActive()) {
            sc.resume();
        }

        this.RTELoading(true);

        if (this.rte && this.rte.busy()) {
            return this.toggleHtmlEditor.bind(this, noupdate).delay(0.1);
        }

        if (active) {
            action = 'html2Text',
            changed = ~~(this.msgHash() != this.hash_msgOrig);

            params.set('body', {
                changed: changed,
                text: this.rte.getData()
            });

            if ($('signature') && (this.sigHash() != this.hash_sigOrig)) {
                params.set('sig', {
                    changed: 1,
                    text: this.rte_sig.getData()
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
            this.setSignature(!active, this.identities[$F('identity')]);
        }
    },

    RTELoading: function(show, notxt)
    {
        var o;

        if (show) {
            $('rteloading').clonePosition('composeMessageParent').show();
            if (!notxt) {
                o = $('rteloading').viewportOffset();
                $('rteloadingtxt').setStyle({ top: (o.top + 15) + 'px', left: (o.left + 15) + 'px' }).show();
            }
        } else {
            $('rteloading', 'rteloadingtxt').invoke('hide');
        }
    },

    getSpellChecker: function()
    {
        return (HordeImple.SpellChecker && HordeImple.SpellChecker.spellcheck)
            ? HordeImple.SpellChecker.spellcheck
            : null;
    },

    _onSpellCheckAfter: function()
    {
        if (this.editor_on) {
            this.setBodyText({ body: $F('composeMessage') });
            $('composeMessage').next().show();
            this.RTELoading(false);
        }
        this.sc_submit = false;
    },

    _onSpellCheckBefore: function()
    {
        this.getSpellChecker().htmlAreaParent = this.editor_on
            ? 'composeMessageParent'
            : null;

        if (this.editor_on) {
            this.rte.updateElement();
            this.RTELoading(true, true);
            $('composeMessage').next().hide();
        }
    },

    _onSpellCheckError: function()
    {
        if (this.editor_on) {
            this.RTELoading(false);
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

        if (this.rte && opts.rte) {
            if (this.rte.busy()) {
                this.setMessageText.bind(this, opts, r).delay(0.1);
                return;
            }
            this.rte.setData(r.text.body);
        } else {
            ta.setValue(r.text.body);
        }

        this.RTELoading(false);

        this.setSignature(opts.rte, r.text.sig ? r.text.sig : this.identities[$F('identity')]);

        this.resizeMsgArea();

        if (!opts.changed) {
            delete this.hash_msgOrig;
        }

        this.fillFormHash();
    },

    rteInit: function(rte)
    {
        var config;

        if (rte && !this.rte) {
            config = Object.clone(IMP.ckeditor_config);
            config.extraPlugins = 'pasteattachment';
            this.rte = new IMP_Editor('composeMessage', config);

            this.rte.editor.on('getData', function(evt) {
                var elt = new Element('SPAN').insert(evt.data.dataValue),
                    elts = elt.select('IMG[dropatc_id]');
                if (elts.size()) {
                    elts.invoke('writeAttribute', 'dropatc_id', null);
                    elts.invoke('writeAttribute', 'src', null);
                    evt.data.dataValue = elt.innerHTML;
                }
            });
        } else if (!rte && this.rte) {
            this.rte.destroy();
            delete this.rte;
        }

        this.editor_on = rte;
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

        ob.opts = ob.opts || {};

        this.fillFormAddr(ob);

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
            $('compose_notices', 'fwdattachnotice').invoke('show');
            this.fwdattach = true;
            break;

        case 'forward_body':
            $('compose_notices', 'fwdbodynotice').invoke('show');
            break;

        case 'reply_all':
            $('replyallnotice').down('SPAN.replyAllNoticeCount').setText(DimpCore.text.replyall.sub('%d', ob.opts.reply_recip));
            $('compose_notices', 'replyallnotice').invoke('show');
            break;

        case 'reply_list':
            $('replylistnotice').down('SPAN.replyListNoticeId').setText(ob.opts.reply_list_id ? (' (' + ob.opts.reply_list_id + ')') : '');
            $('compose_notices', 'replylistnotice').invoke('show');
            break;
        }

        if (ob.opts.reply_lang) {
            $('langnotice').down('SPAN.langNoticeList').setText(ob.opts.reply_lang.join(', '));
            $('compose_notices', 'langnotice').invoke('show');
        }

        this.setBodyText(ob);
        this.resizeMsgArea();

        this.focus(ob.opts.focus || 'to');

        this.fillFormHash();
    },

    fillFormHash: function()
    {
        if (this.rte && this.rte.busy()) {
            this.fillFormHash.bind(this).delay(0.1);
            return;
        }

        // This value is used to determine if the text has changed when
        // swapping compose modes.
        if (!this.hash_msgOrig) {
            this.hash_msgOrig = this.msgHash();
        }

        // Set auto-save-drafts now if not already active. Only need if
        // compose template is output on current page (redirect doesn't
        // need autosave).
        if (DimpCore.conf.auto_save_interval_val &&
            !this.auto_save_interval &&
            $('compose')) {
            this.auto_save_interval = new PeriodicalExecuter(
                this.autoSaveDraft.bind(this),
                DimpCore.conf.auto_save_interval_val * 60
            );

            /* Immediately execute to get hash of headers. */
            this.auto_save_interval.execute();
        }
    },

    updateAddrField: function(hdr, addrs)
    {
        if (!addrs.size()) {
            return;
        }

        switch (hdr) {
        case 'bcc':
        case 'cc':
            if (!$('send' + hdr).visible()) {
                this.toggleCC(hdr);
            }
            break;

        case 'to':
            if (DimpCore.conf.redirect) {
                hdr = 'redirect_to';
            }
            break;
        }

        this.ac.get(hdr).addNewItems(addrs);
    },

    focus: function(elt)
    {
        elt = $(elt);
        try {
            // IE 8 requires try/catch to silence a warning.
            elt.focus();
        } catch (e) {}
        elt.fire('AutoComplete:focus');
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
            this.editor_on ? this.rte.getData() : $F('composeMessage')
        );
    },

    sigHash: function()
    {
        return $('signature')
            ? IMP_JS.fnv_1a(this.rte_sig ? this.rte_sig.getData() : $F('signature'))
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
        if (this.rte) {
            this.rte.setData(ob.body);
        } else {
            $('composeMessage').setValue(ob.body);
            this.setCursorPosition('composeMessage', DimpCore.conf.compose_cursor);
        }

        if (ob.format == 'html') {
            if (!this.editor_on) {
                this.toggleHtmlEditor(true);
            }
            if (ob.opts &&
                ob.opts.focus &&
                (ob.opts.focus == 'composeMessage')) {
                this.rte.focus();
            }
        }
    },

    setCursorPosition: function(input, type)
    {
        var pos, range;

        if (!(input = $(input))) {
            return;
        }

        switch (type) {
        case 'top':
            pos = 0;
            input.setValue('\n' + $F(input));
            break;

        case 'bottom':
            pos = $F(input).length;
            break;

        default:
            return;
        }

        if (input.setSelectionRange) {
            /* This works in Mozilla. */
            Field.focus(input);
            input.setSelectionRange(pos, pos);
            if (pos) {
                (function() { input.scrollTop = input.scrollHeight - input.offsetHeight; }).delay(0.1);
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

    fillFormAddr: function(r)
    {
        if (r.addr) {
            [ 'to', 'cc', 'bcc' ].each(function(a) {
                var add = [];
                $(a).setValue('').fire('AutoComplete:reset');
                r.addr[a].each(function(ob) {
                    add.push(new IMP_Autocompleter_Elt(ob.v, ob.l, ob.s));
                });
                this.updateAddrField(a, add);
            }, this);
        }
        $('to_loading_img').hide();
    },

    resizeMsgArea: function(e)
    {
        if (!document.loaded || $('dimpLoading').visible()) {
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

        if (this.rte) {
            this.rte.resize('99%', mah - 1);
        }

        $('composeMessage').setStyle({ height: mah + 'px' });

        if ($('rteloading').visible()) {
            this.RTELoading(true);
        }
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

        this.focus(type);
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

    doTasks: function()
    {
        if (this.tasks) {
            document.fire('HordeCore:runTasks', {
                response: {},
                tasks: this.tasks
            });
            delete this.tasks;
        }
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

    /* Autocomplete functions. */

    autocompleteValue: function(val)
    {
        var ob = [],
            pos = 0,
            chr, in_group, in_quote, tmp;

        chr = val.charAt(pos);
        while (chr !== "") {
            var orig_pos = pos;
            ++pos;

            if (!orig_pos || (val.charAt(orig_pos - 1) != '\\')) {
                switch (chr) {
                case ',':
                    if (!orig_pos) {
                        val = val.substr(1);
                    } else if (!in_group && !in_quote) {
                        ob.push(new IMP_Autocompleter_Elt(val.substr(0, orig_pos)));
                        val = val.substr(orig_pos + 2);
                        pos = 0;
                    }
                    break;

                case '"':
                    in_quote = !in_quote;
                    break;

                case ':':
                    if (!in_quote) {
                        in_group = true;
                    }
                    break;

                case ';':
                    if (!in_quote) {
                        in_group = false;
                    }
                    break;
                }
            }

            chr = val.charAt(pos);
        }

        return [ ob, val ];
    },

    autocompleteShortDisplay: function(l)
    {
        l = l.sub(/<[^>]*>$/, "").strip();
        if (l.startsWith('"') && l.endsWith('"')) {
            l = l.slice(1, -1).gsub(/\\/, "");
        }

        return l;
    },

    autocompleteProcess: function(r)
    {
        /* Clear all existing flags. */
        this.ac.each(function(pair) {
            pair.value.getElts()
                .invoke('removeClassName', 'impACListItemBad')
                .invoke('removeClassName', 'impACListItemWarn');
        });

        $H(r).each(function(pair) {
            var ac = this.ac.get(pair.key);
            $H(pair.value).each(function(pair2) {
                var entry = ac.getEntryById(pair2.key);
                if (entry) {
                    entry.elt.addClassName(pair2.value);
                }
            });
        }, this);
    },

    autocompleteServerRequest: function(t, data)
    {
        var d, i, re;

        for (i = t.length - 1; i > 0; --i) {
            d = data.get(t.substr(0, i));
            if (d) {
                if (d.size() >= this.ac_limit) {
                    return false;
                }

                re = new RegExp(latinize(t), "i");

                return d.findAll(function(a) {
                    return latinize(a.v).match(re);
                });
            }
        }

        return false;
    },

    autocompleteServerSuggestion: function(e, elt)
    {
        if (e.g) {
            elt.group = e.g;
        }
    },

    autocompleteOnAdd: function(v)
    {
        if (v.group) {
            v.elt.down('IMG').insert({ before:
                new Element('A', { className: 'impACGroupExpand' })
                    .insert(DimpCore.text.group_expand)
            });
        }
    },

    autocompleteOnEntryClick: function(ob)
    {
        if (ob.elt.hasClassName('impACGroupExpand')) {
            ob.ac.getEntryByElt(ob.entry).group.each(function(v) {
                ob.ac.addNewItems([ new IMP_Autocompleter_Elt(v.v, v.l, v.s) ]);
            });
            ob.ac.removeEntry(ob.entry);
            return true;
        }

        return false;
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
                callback: this.fillFormAddr.bind(this)
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
        }
    },

    contextOnClick: function(e)
    {
        var id = e.memo.elt.identify();

        switch (id) {
        case 'ctx_atcfile_delete':
            this.attachlist.removeAttach(this.atc_context);
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
        Object.keys(e.memo).each(function(k) {
            var add = [];
            e.memo[k].each(function(a) {
                add.push(new IMP_Autocompleter_Elt(a));
            });
            this.updateAddrField(k, add);
        }, this);
    },

    tasksHandler: function(e)
    {
        var base = DimpCore.baseAvailable(),
            t = e.tasks || {};

        if (t['imp:compose']) {
            this.getCacheElt().setValue(t['imp:compose'].cacheid);
            if ($('composeCache')) {
                $('composeHmac').setValue(t['imp:compose'].hmac);
            }
        }

        if (base) {
            if (t['imp:flag']) {
                base.DimpBase.flagCallback(t['imp:flag']);
            }

            if (t['imp:mailbox']) {
                base.DimpBase.mailboxCallback(t['imp:mailbox']);
            }

            if (t['imp:maillog']) {
                base.DimpBase.maillogCallback(t['imp:maillog']);
            }
        }
    },

    onDomLoad: function()
    {
        var tmp;

        /* Initialize autocompleters. */
        $('to', 'cc', 'bcc', 'redirect_to').compact().invoke('identify').each(function(id) {
            this.ac.set(
                id,
                new IMP_Autocompleter(id, {
                    autocompleterParams: {
                        limit: this.ac_limit,
                        type: 'email'
                    },
                    boxClass: 'hordeACBox impACBox',
                    boxClassFocus: 'impACBoxFocus',
                    deleteIcon: DimpCore.conf.ac_delete,
                    displayFilter: function(t) { return t.sub(/<[^>]*>$/, "").strip().escapeHTML(); },
                    growingInputClass: 'hordeACTrigger impACTrigger',
                    listClass: 'hordeACList impACList',
                    loadingText: DimpCore.text.loading,
                    loadingTextClass: 'impACLoadingText',
                    minChars: DimpCore.conf.ac_minchars,
                    noResultsText: DimpCore.text.noresults,
                    noResultsTextClass: 'impACNoResultsText',
                    onAdd: this.autocompleteOnAdd.bind(this),
                    onBeforeServerRequest: this.autocompleteServerRequest.bind(this),
                    onEntryClick: this.autocompleteOnEntryClick.bind(this),
                    onServerSuggestion: this.autocompleteServerSuggestion.bind(this),
                    processValueCallback: this.autocompleteValue.bind(this),
                    removeClass: 'hordeACItemRemove impACItemRemove',
                    shortDisplayCallback: this.autocompleteShortDisplay.bind(this),
                    trigger: id,
                    triggerContainer: Math.random().toString()
                })
            );
        }, this);

        /* Initialize redirect elements. */
        if (DimpCore.conf.redirect) {
            $('redirect').observe('submit', Event.stop);
            if (DimpCore.conf.URI_ABOOK) {
                $('redirect_sendto').down('TD.label SPAN').addClassName('composeAddrbook');
            }
            $('dimpLoading').hide();
            $('composeContainer', 'redirect').invoke('show');

            this.doTasks();
            this.focus('redirect_to');

            return;
        }

        /* Attach event handlers. */
        if (Prototype.Browser.IE) {
            // IE doesn't bubble change events.
            $('identity').observe('change', this.changeHandler.bindAsEventListener(this));
        } else {
            document.observe('change', this.changeHandler.bindAsEventListener(this));
        }
        $('compose').observe('submit', Event.stop);
        $('htmlcheckbox').up('LABEL').observe('mouseup', function() {
            if (!this.editor_on ||
                window.confirm(DimpCore.text.toggle_html)) {
                this.toggleHtmlEditor();
            }
        }.bind(this));

        HordeCore.initHandler('click');

        this.attachlist = new this.classes.Attachlist(this);

        if ((tmp = $('atcdrop'))) {
            tmp.observe('DragHandler:drop', this.attachlist.uploadAttach.bindAsEventListener(this.attachlist));
            DragHandler.dropelt = tmp;
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

        this.doTasks();

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

        this.RTELoading(false);
    }

};

ImpCompose.classes.Attachlist = Class.create({

    // ajax_atc_id,
    // curr_upload,
    // num_limit,
    // size_limit

    initialize: function(compose)
    {
        var tmp = $('upload');

        this.compose = compose;

        this.ajax_atc_id = 0;
        this.curr_upload = 0;

        /* Attach event handlers. */
        if (tmp) {
            document.observe('HordeCore:runTasks', this.tasksHandler.bindAsEventListener(this));
            if (Prototype.Browser.IE) {
                // IE doesn't bubble change events.
                tmp.observe('change', this.changeHandler.bindAsEventListener(this));
            } else {
                document.observe('change', this.changeHandler.bindAsEventListener(this));
            }
        }
    },

    reset: function()
    {
        this.curr_upload = 0;
        delete this.num_limit;
        $('attach_list').hide().childElements().each(this.removeAttachRow, this);
    },

    busy: function()
    {
        return !!(this.curr_upload);
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
                .addClassName('attach_file')
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

        this.compose.resizeMsgArea();
    },

    getAttach: function(id)
    {
        return $('attach_list').childElements().detect(function(e) {
            return e.retrieve('atc_id') == id;
        });
    },

    // elt: (array | Element)
    removeAttach: function(elt, quiet)
    {
        if (Object.isElement(elt)) {
            elt = [ elt.retrieve('atc_id') ];
        }

        if (elt.size()) {
            DimpCore.doAction('deleteAttach', this.compose.actionParams({
                atc_indices: Object.toJSON(elt),
                quiet: ~~(!!quiet)
            }), {
                callback: this.removeAttachCallback.bind(this)
            });
        }
    },

    removeAttachCallback: function(r)
    {
        r.collect(this.getAttach, this).compact().each(function(elt) {
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
        var al = $('attach_list');

        [ $('upload_add') ].invoke(this.num_limit === 0 ? 'hide' : 'show');
        [ $('upload_limit') ].invoke(this.num_limit === 0 ? 'show' : 'hide');

        if (!al.childElements().size()) {
            al.hide();
        }

        this.compose.resizeMsgArea();
    },

    uploadAttachWait: function(f)
    {
        var li = new Element('LI')
            .insert(
                new Element('SPAN')
                    .addClassName('attach_upload_text')
                    .insert((Object.isElement(f) ? $F(f) : (f.name || DimpCore.text.data)).escapeHTML())
                    .insert(new Element('SPAN').insert('(' + DimpCore.text.uploading + ')'))
            );

        $('attach_list').show().insert(li);

        return li;
    },

    // data: (Element | Event object | array)
    uploadAttach: function(data, params, callback)
    {
        var li, out = $H();

        if (Object.isElement(data)) {
            if (!data.files) {
                // We need a submit action here because browser security
                // models won't let us access files on user's filesystem
                // otherwise.
                ++this.curr_upload;
                li = this.uploadAttachWait(data);
                HordeCore.submit('compose', {
                    callback: function() {
                        --this.curr_upload;
                        li.remove();
                        this.compose.resizeMsgArea();
                    }.bind(this)
                });
                return;
            }
            data = data.files;
        } else if (!Object.isArray(data)) {
            data = data.memo;
        }

        if ($A(data).size() > this.num_limit) {
            HordeCore.notify(DimpCore.text.max_atc_num, 'horde.error');
            return;
        }

        /* First pass - check for size limitations. Do in groups, rather than
         * individually, since it is a UX nightmare if some files are attached
         * and others aren't. */
        if ($A(data).detect(function(d) {
            return (parseInt(d.size, 10) >= this.size_limit);
        }, this)) {
            HordeCore.notify(DimpCore.text.max_atc_size, 'horde.error');
            return;
        }

        params = $H(params).update({
            composeCache: $F(this.compose.getCacheElt()),
            json_return: 1
        });
        HordeCore.addRequestParams(params);

        /* Second pass - actually send the files. */
        $A(data).each(function(d) {
            var fd = new FormData(), li;

            params.merge({
                file_id: ++this.ajax_atc_id,
                file_upload: d
            }).each(function(p) {
                fd.append(p.key, p.value);
            });

            ++this.curr_upload;
            if (Object.isNumber(this.num_limit)) {
                --this.num_limit;
            }

            HordeCore.doAction('addAttachment', {}, {
                ajaxopts: {
                    postBody: fd,
                    requestHeaders: { "Content-type": null },
                    onComplete: function() {
                        --this.curr_upload;
                        li.remove();
                        this.compose.resizeMsgArea();
                    }.bind(this),
                    onCreate: function(e) {
                        if (e.transport && e.transport.upload) {
                            var p = new Element('SPAN')
                                .addClassName('attach_upload_progress')
                                .hide()
                                .insert(new Element('SPAN'));
                            li = this.uploadAttachWait(d);
                            li.insert(p);

                            e.transport.upload.onprogress = function(e2) {
                                if (e2.lengthComputable) {
                                    p.down('SPAN').setStyle({
                                        width: parseInt((e2.loaded / e2.total) * 100, 10) + "%"
                                    });
                                    if (!p.visible()) {
                                        li.down('.attach_upload_text')
                                            .addClassName('attach_upload_text_progress')
                                            .down('SPAN').remove();
                                        p.show();
                                    }
                                }
                            };
                        } else {
                            li = this.uploadAttachWait(d);
                        }
                    }.bind(this)
                },
                callback: callback || Prototype.emptyFunction
            });

            out.set(this.ajax_atc_id, d);
        }, this);

        return out;
    },

    /* Event observers. */

    changeHandler: function(e)
    {
        switch (e.element().readAttribute('id')) {
        case 'upload':
            this.uploadAttach($('upload'));
            break;
        }
    },

    tasksHandler: function(e)
    {
        var t = e.memo.tasks || {};

        if (t['imp:compose']) {
            this.num_limit = t['imp:compose'].atclimit;
            this.size_limit = t['imp:compose'].atcmax;
        }

        if (t['imp:compose-addr']) {
            this.compose.autocompleteProcess(t['imp:compose-addr']);
        }

        if (t['imp:compose-atc']) {
            t['imp:compose-atc'].each(this.addAttach, this);
        }
    }

});

/* Attach event handlers. */
document.observe('dom:loaded', ImpCompose.onDomLoad.bind(ImpCompose));
document.observe('HordeCore:click', ImpCompose.clickHandler.bindAsEventListener(ImpCompose));
document.observe('keydown', ImpCompose.keydownHandler.bindAsEventListener(ImpCompose));
Event.observe(window, 'resize', ImpCompose.resizeMsgArea.bindAsEventListener(ImpCompose));

/* Other UI event handlers. */
document.observe('AutoComplete:resize', ImpCompose.resizeMsgArea.bind(ImpCompose));
document.observe('ImpContacts:update', ImpCompose.onContactsUpdate.bindAsEventListener(ImpCompose));

/* ContextSensitive functions. */
document.observe('ContextSensitive:click', ImpCompose.contextOnClick.bindAsEventListener(ImpCompose));
document.observe('ContextSensitive:show', ImpCompose.contextOnShow.bindAsEventListener(ImpCompose));

/* Initialize spellchecker. */
document.observe('SpellChecker:after', ImpCompose._onSpellCheckAfter.bind(ImpCompose));
document.observe('SpellChecker:before', ImpCompose._onSpellCheckBefore.bind(ImpCompose));
document.observe('SpellChecker:error', ImpCompose._onSpellCheckError.bind(ImpCompose));
document.observe('SpellChecker:noerror', ImpCompose._onSpellCheckNoError.bind(ImpCompose));

/* Catch dialog actions. */
document.observe('ImpPassphraseDialog:success', ImpCompose.retrySubmit.bind(ImpCompose));

/* Catch tasks. */
document.observe('HordeCore:runTasks', function(e) {
    ImpCompose.tasksHandler(e.memo);
});

/* AJAX related events. */
document.observe('HordeCore:ajaxFailure', ImpCompose.onAjaxFailure.bindAsEventListener(ImpCompose));

/* IMP Editor events. */
document.observe('IMP_Editor:ready', function(e) {
    if (e.memo.name == 'composeMessage') {
        new CKEDITOR.dom.document(
            e.memo.getThemeSpace('contents').$.down('IFRAME').contentWindow.document)
        .on('keydown', function(evt) {
            this.keydownHandler(Event.extend(evt.data.$), true);
        }.bind(this));
    }
}.bindAsEventListener(ImpCompose));
document.observe('IMP_Editor:dataReady', function(e) {
    if (e.memo.name == 'composeMessage') {
        this.RTELoading(false);
        this.resizeMsgArea();
    }
}.bindAsEventListener(ImpCompose));
