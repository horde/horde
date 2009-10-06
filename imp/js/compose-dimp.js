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
    //   auto_save_interval, button_pressed, compose_cursor, dbtext,
    //   drafts_mbox, editor_on, mp_padding, resizebcc, resizecc, resizeto,
    //   row_height, sbtext, skip_spellcheck, spellcheck, uploading
    last_msg: '',
    textarea_ready: true,

    confirmCancel: function()
    {
        if (window.confirm(DIMP.text_compose.cancel)) {
            DimpCore.doAction(DIMP.conf_compose.auto_save_interval_val ? 'DeleteDraft' : 'CancelCompose', { imp_compose: $F('composeCache') });
            this.updateDraftsMailbox();
            return this.closeCompose();
        }
    },

    updateDraftsMailbox: function()
    {
        if (DIMP.baseWindow &&
            DIMP.baseWindow.DimpBase &&
            DIMP.baseWindow.DimpBase.folder == DIMP.conf_compose.drafts_mbox) {
            DIMP.baseWindow.DimpBase.poll();
        }
    },

    closeCompose: function()
    {
        if (DIMP.conf_compose.qreply) {
            this.closeQReply();
        } else if ((DIMP.baseWindow && DIMP.baseWindow.DimpBase) ||
                   DIMP.conf_compose.popup) {
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

        $('composeCache').setValue('');
        $('qreply', 'sendcc', 'sendbcc').invoke('hide');
        [ $('msgData'), $('togglecc').up(), $('togglebcc').up() ].invoke('show');
        if (this.editor_on) {
            this.toggleHtmlEditor();
        }
        $('compose').reset();

        // Disable auto-save-drafts now.
        if (this.auto_save_interval) {
            this.auto_save_interval.stop();
        }
    },

    change_identity: function()
    {
        var lastSignature, msg, nextSignature, pos,
            id = $F('identity'),
            last = this.get_identity($F('last_identity')),
            msgval = $('message'),
            next = this.get_identity(id),
            ssm = $('save_sent_mail');

        $('sent_mail_folder_label').setText(next.id[5]);
        $('bcc').setValue(next.id[6]);
        if (ssm) {
            ssm.writeAttribute('checked', next.id[4]);
        }

        // Finally try and replace the signature.
        if (this.editor_on) {
            msg = FCKeditorAPI.GetInstance('message').GetHTML().replace(/\r\n/g, '\n');
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
                FCKeditorAPI.GetInstance('message').SetHTML(msg);
            } else {
                msgval.setValue(msg);
            }
            $('last_identity').setValue(id);
        }
    },

    get_identity: function(id, editor_on)
    {
        editor_on = Object.isUndefined(editor_on) ? this.editor_on : editor_on;
        return {
            id: DIMP.conf_compose.identities[id],
            sig: DIMP.conf_compose.identities[id][(editor_on ? 1 : 0)].replace(/^\n/, '')
        };
    },

    uniqueSubmit: function(action)
    {
        var db, params, sb,
            c = $('compose');

        if (DIMP.SpellCheckerObject &&
            DIMP.SpellCheckerObject.isActive()) {
            DIMP.SpellCheckerObject.resume();
            this.skip_spellcheck = true;
            if (!this.textarea_ready) {
                this.uniqueSubmit.bind(this, action).defer();
                return;
            }
        }

        if (action == 'send_message' || action == 'save_draft') {
            this.button_pressed = true;

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
                    DIMP.SpellCheckerObject.spellCheck(this.onNoSpellError.bind(this, action));
                    return;
                }

                if (!this.sbtext) {
                    sb = $('send_button');
                    this.sbtext = sb.getText();
                    sb.setText(DIMP.text_compose.sending);
                }
                break;

            case 'save_draft':
                if (!this.dbtext) {
                    db = $('draft_button');
                    this.dbtext = db.getText();
                    db.setText(DIMP.text_compose.saving);
                }
                break;
            }

            // Don't send/save until uploading is completed.
            if (this.uploading) {
                (function() { if (this.button_pressed) { this.uniqueSubmit(action); } }).bind(this).delay(0.25);
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
                FCKeditorAPI.GetInstance('message').UpdateLinkedField();
            }

            // Use an AJAX submit here so that we can do javascript-y stuff
            // before having to close the window on success.
            params = c.serialize(true);
            if (!DIMP.baseWindow || !DIMP.baseWindow.DimpBase) {
                params.nonotify = true;
            }
            DimpCore.doAction('*' + DIMP.conf.URI_COMPOSE, params, null, this.uniqueSubmitCallback.bind(this));
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
                this.button_pressed = false;

                this.updateDraftsMailbox();

                if (d.action == 'save_draft') {
                    if (DIMP.baseWindow &&
                        DIMP.baseWindow.DimpBase &&
                        !DIMP.conf_compose.qreply) {
                        DIMP.baseWindow.DimpCore.showNotifications(r.msgs);
                    }
                    if (DIMP.conf_compose.close_draft) {
                        return this.closeCompose();
                    }
                }
                break;

            case 'send_message':
                this.button_pressed = false;
                if (DIMP.baseWindow && DIMP.baseWindow.DimpBase) {
                    if (d.reply_type) {
                        DIMP.baseWindow.DimpBase.flag(d.reply_type == 'reply' ? '\\answered' : '$forwarded', true, { index: d.index, mailbox: d.reply_folder, noserver: true });
                    }

                    // @TODO: Needed?
                    if (d.folder) {
                        DIMP.baseWindow.DimpBase.createFolder(d.folder);
                    }

                    if (d.draft_delete) {
                        DIMP.baseWindow.DimpBase.poll();
                    }

                    if (d.log) {
                        DIMP.baseWindow.DimpBase.updateMsgLog(d.log, { index: d.index, mailbox: d.reply_folder });
                    }

                    if (!DIMP.conf_compose.qreply) {
                        DIMP.baseWindow.DimpCore.showNotifications(r.msgs);
                    }
                }
                return this.closeCompose();

            case 'add_attachment':
                this.uploading = false;
                if (d.success) {
                    this.addAttach(d.info.number, d.info.name, d.info.type, d.info.size);
                } else {
                    this.button_pressed = false;
                }
                if (DIMP.conf_compose.attach_limit != -1 &&
                    $('attach_list').childElements().size() > DIMP.conf_compose.attach_limit) {
                    $('upload').writeAttribute('disabled', false);
                    elt = new Element('DIV', [ DIMP.text_compose.atc_limit ]);
                } else {
                    elt = new Element('INPUT', { type: 'file', name: 'file_1' });
                }
                $('upload_wait').replace(elt.writeAttribute('id', 'upload'));
                this.resizeMsgArea();
                break;
            }
        } else {
            this.button_pressed = false;
        }

        $('compose').setStyle({ cursor: null });

        // Re-enable buttons if needed.
        if (!this.button_pressed) {
            if (this.sbtext) {
                $('send_button').setText(this.sbtext);
            }
            if (this.dbtext) {
                $('draft_button').setText(this.dbtext);
            }
            this.dbtext = this.sbtext = null;
        }

        if (!r.msgs_noauto) {
            DimpCore.showNotifications(r.msgs);
        }
    },

    onNoSpellError: function(action)
    {
        this.skip_spellcheck = true;
        this.uniqueSubmit(action);
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

        var text;

        if (this.editor_on) {
            this.editor_on = false;

            text = FCKeditorAPI.GetInstance('message').GetHTML();
            $('messageParent').childElements().invoke('hide');
            $('message').show();

            DimpCore.doAction('Html2Text', { text: text }, null, this.setMessageText.bind(this), { asynchronous: false });
        } else {
            this.editor_on = true;
            if (!noupdate) {
                DimpCore.doAction('Text2Html', { text: $F('message') }, null, this.setMessageText.bind(this), { asynchronous: false });
            }

            oFCKeditor.Height = this.getMsgAreaHeight();
            // Try to reuse the old fckeditor instance.
            try {
                FCKeditorAPI.GetInstance('message').SetHTML($F('message'));
                $('messageParent').childElements().invoke('show');
                $('message').hide();
            } catch (e) {
                this.RTELoading('show');
                FCKeditor_OnComplete = this.RTELoading.curry('hide');
                oFCKeditor.ReplaceTextarea();
            }
        }
        $('htmlcheckbox').checked = this.editor_on;
        $('html').setValue(this.editor_on ? 1 : 0);
    },

    RTELoading: function(cmd)
    {
        var o, r;
        if (!$('rteloading')) {
            r = new Element('DIV', { id: 'rteloading' }).clonePosition($('messageParent'));
            $(document.body).insert(r);
            o = r.viewportOffset();
            $(document.body).insert(new Element('SPAN', { id: 'rteloadingtxt' }).setStyle({ top: (o.top + 15) + 'px', left: (o.left + 15) + 'px' }).insert(DIMP.text.loading));
        }
        $('rteloading', 'rteloadingtxt').invoke(cmd);
    },

    toggleHtmlCheckbox: function()
    {
        if (!this.editor_on || window.confirm(DIMP.text_compose.toggle_html)) {
            this.toggleHtmlEditor();
        }
    },

    getMsgAreaHeight: function()
    {
        if (!this.mp_padding) {
            this.mp_padding = $('messageParent').getHeight() - $('message').getHeight();
        }

        return document.viewport.getHeight() - $('messageParent').cumulativeOffset()[1] - this.mp_padding;
    },

    initializeSpellChecker: function()
    {
        if (!DIMP.conf_compose.rte_avail) {
            return;
        }

        if (typeof DIMP.SpellCheckerObject != 'object') {
            // If we fired before the onload that initializes the spellcheck,
            // wait.
            this.initializeSpellChecker.bind(this).defer();
            return;
        }

        DIMP.SpellCheckerObject.onBeforeSpellCheck = function() {
            if (!this.editor_on) {
                return;
            }
            DIMP.SpellCheckerObject.htmlAreaParent = 'messageParent';
            DIMP.SpellCheckerObject.htmlArea = $('message').adjacent('iframe[id*=message]').first();
            $('message').setValue(FCKeditorAPI.GetInstance('message').GetHTML());
            this.textarea_ready = false;
        }.bind(this);
        DIMP.SpellCheckerObject.onAfterSpellCheck = function() {
            if (!this.editor_on) {
                return;
            }
            DIMP.SpellCheckerObject.htmlArea = DIMP.SpellCheckerObject.htmlAreaParent = null;
            var ed = FCKeditorAPI.GetInstance('message');
            ed.SetHTML($F('message'));
            ed.Events.AttachEvent('OnAfterSetHTML', function() { this.textarea_ready = true; }.bind(this));
        }.bind(this);
    },

    setMessageText: function(r)
    {
        var ta = $('message');
        if (!ta) {
            $('messageParent').insert(new Element('TEXTAREA', { id: 'message', name: 'message', style: 'width:100%;' }).insert(r.response.text));
        } else {
            ta.setValue(r.response.text);
        }

        if (!this.editor_on) {
            this.resizeMsgArea();
        }
    },

    fillForm: function(msg, header, focus, noupdate)
    {
        // On IE, this can get loaded before DOM;loaded. Check for an init
        // value and don't load until it is available.
        if (!this.resizeto) {
            this.fillForm.bind(this, msg, header, focus, noupdate).defer();
            return;
        }

        var bcc_add, fo,
            identity = this.get_identity($F('last_identity')),
            msgval = $('message');

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
                    ? FCKeditorAPI.GetInstance('message').GetHTML()
                    : $F(msgval);
                cur_msg = cur_msg.replace(/\r/g, '');
                if (!cur_msg.empty() && this.last_msg != cur_msg) {
                    this.uniqueSubmit('auto_save_draft');
                    this.last_msg = cur_msg;
                }
            }.bind(this), DIMP.conf_compose.auto_save_interval_val * 60);
        }

        if (this.editor_on) {
            fo = FCKeditorAPI.GetInstance('message');
            fo.SetHTML(msg);
            this.last_msg = fo.GetHTML().replace(/\r/g, '');
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
            this.toggleCC('cc');
        }
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
            this.toggleCC('bcc');
        }
        $('subject').setValue(header.subject);

        Field.focus(focus || 'to');
        this.resizeMsgArea();

        if (DIMP.conf_compose.show_editor) {
            if (!this.editor_on) {
                this.toggleHtmlEditor(noupdate || false);
            }
            if (focus == 'message') {
                this.focusEditor();
            }
        }
    },

    focusEditor: function()
    {
        try {
            FCKeditorAPI.GetInstance('message').Focus();
        } catch (e) {
            this.focusEditor.bind(this).defer();
        }
    },

    addAttach: function(atc_num, name, type, size)
    {
        var span = new Element('SPAN').insert(name),
            div = new Element('DIV').insert(span).insert(' [' + type + '] (' + size + ' KB) '),
            input = new Element('SPAN', { atc_id: atc_num, className: 'remove' }).insert(DIMP.text_compose.remove);
        div.insert(input);
        $('attach_list').insert(div);

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
            n.remove();
        });
        DimpCore.doAction('DeleteAttach', { atc_indices: ids, imp_compose: $F('composeCache') });
        this.resizeMsgArea();
    },

    resizeMsgArea: function()
    {
        var m, rows,
            de = document.documentElement,
            msg = $('message');

        if (!document.loaded) {
            this.resizeMsgArea.bind(this).defer();
            return;
        }

        if (this.editor_on) {
            m = $('messageParent').select('iframe').last();
            if (m) {
                m.setStyle({ height: this.getMsgAreaHeight() + 'px' });
            } else {
                this.resizeMsgArea.bind(this).defer();
            }
            return;
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

        $('composeloading').hide();
    },

    uploadAttachment: function()
    {
        var u = $('upload');
        this.uniqueSubmit('add_attachment');
        u.replace(new Element('DIV', { id: 'upload_wait' }).insert(DIMP.text_compose.uploading + ' ' + $F(u)));
    },

    attachmentComplete: function()
    {
        var sf = $('submit_frame'),
            doc = sf.contentDocument || sf.contentWindow.document;
        DimpCore.doActionComplete({ responseJSON: doc.body.innerHTML.evalJSON(true) }, this.uniqueSubmitCallback.bind(this));
    },

    toggleCC: function(type)
    {
        $('send' + type).show();
        $('toggle' + type).up().hide();
    },

    /* Sets the cursor to the given position. */
    setCursorPosition: function(input)
    {
        var pos, range;

        switch (DIMP.conf_compose.compose_cursor) {
        case 'top':
            pos = 0;
            $('message').setValue('\n' + $F('message'));
            break;

        case 'bottom':
            pos = $F('message').length;
            break;

        case 'sig':
            pos = $F('message').replace(/\r\n/g, '\n').lastIndexOf(this.get_identity($F('last_identity')).sig) - 1;
            break;

        default:
            return;
        }

        if (input.setSelectionRange) {
            /* This works in Mozilla */
            Field.focus(input);
            input.setSelectionRange(pos, pos);
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
                this.uniqueSubmit(id == 'send_button' ? 'send_message' : 'save_draft');
                break;

            case 'htmlcheckbox':
                this.toggleHtmlCheckbox();
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
            this.change_identity();
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

        // Safari requires a submit target iframe to be at least 1x1 size or
        // else it will open content in a new window.  See:
        //   http://blog.caboo.se/articles/2007/4/2/ajax-file-upload
        if (Prototype.Browser.WebKit) {
            $('submit_frame').writeAttribute({ position: 'absolute', width: '1px', height: '1px' }).setStyle({ left: '-999px' }).show();
        }

        /* Add addressbook link formatting. */
        if (DIMP.conf_compose.URI_ABOOK) {
            $('sendto', 'sendcc', 'sendbcc').each(function(a) {
                a.down('TD.label SPAN').addClassName('composeAddrbook');
            });
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
