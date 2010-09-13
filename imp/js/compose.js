/**
 * Provides the javascript for the compose.php script (standard view).
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

var ImpCompose = {
    // Variables defined in compose.php:
    //   cancel_url, spellcheck, cursor_pos, last_msg, max_attachments,
    //   popup, redirect, reloaded, sc_submit, smf_check, skip_spellcheck
    display_unload_warning: true,

    confirmCancel: function(e)
    {
        if (window.confirm(IMP.text.compose_cancel)) {
            this.display_unload_warning = false;
            if (this.popup) {
                if (this.cancel_url) {
                    self.location = this.cancel_url;
                } else {
                    self.close();
                }
            } else {
                window.location = this.cancel_url;
            }
        } else {
            e.stop();
        }
    },

    changeIdentity: function(elt)
    {
        var id = $F(elt),
            last = IMP_Compose_Base.getIdentity($F('last_identity')),
            next = IMP_Compose_Base.getIdentity(id),
            bcc = $('bcc'),
            save = $('ssm'),
            smf = $('sent_mail_folder'),
            re;

        IMP_Compose_Base.replaceSignature(id);

        if (this.smf_check) {
            smf.setValue(next.id.smf_name);
        } else {
            smf.update(next.id.smf_display);
        }

        if (save) {
            save.setValue(next.id.smf_save);
        }
        if (bcc) {
            bccval = $F(bcc);

            if (last.id.bcc) {
                re = new RegExp(last.id.bcc + ",? ?", 'gi');
                bccval = bccval.replace(re, "");
                if (bccval) {
                    bccval = bccval.replace(/, ?$/, "");
                }
            }

            if (next.id.bcc) {
                if (bccval) {
                    bccval += ', ';
                }
                bccval += next.id.bcc;
            }

            bcc.setValue(bccval);
        }
    },

    uniqSubmit: function(actionID, e)
    {
        var cur_msg, form;

        if (!Object.isUndefined(e)) {
            e.stop();
        }

        switch (actionID) {
        case 'redirect':
            if ($F('to') == '') {
                alert(IMP.text.compose_recipient);
                $('to').focus();
                return;
            }

            form = $('redirect');
            break;

        case 'send_message':
            if (!this.skip_spellcheck &&
                this.spellcheck &&
                IMP.SpellChecker &&
                !IMP.SpellChecker.isActive()) {
                this.sc_submit = { a: actionID, e: e };
                IMP.SpellChecker.spellCheck();
                return;
            }

            if (($F('subject') == '') &&
                !window.confirm(IMP.text.compose_nosubject)) {
                return;
            }

            this.skip_spellcheck = false;

            if (IMP.SpellChecker) {
                IMP.SpellChecker.resume();
            }

            // fall through

        case 'add_attachment':
        case 'save_draft':
        case 'change_stationery':
            form = $('compose');
            $('actionID').setValue(actionID);
            break;

        case 'auto_save_draft':
            // Move HTML text to textarea field for submission.
            if (IMP_Compose_Base.editor_on) {
                CKEDITOR.instances.composeMessage.updateElement();
            }

            cur_msg = MD5.hash($('to', 'cc', 'bcc', 'subject').compact().invoke('getValue').join('\0') + $F('composeMessage'));
            if (this.last_msg && curr_hash != this.last_msg) {
                // Use an AJAX submit here so that the page doesn't reload.
                $('actionID').setValue(actionID);
                $('compose').request({ onComplete: this._autoSaveDraft.bind(this) });
            }
            this.last_msg = cur_msg;
            return;

        case 'toggle_editor':
            form = $('compose');
            break;

        default:
            return;
        }

        // Ticket #6727; this breaks on WebKit w/FCKeditor.
        if (!Prototype.Browser.WebKit) {
            form.setStyle({ cursor: 'wait' });
        }

        this.display_unload_warning = false;
        form.submit();
    },

    _autoSaveDraft: function(r, o)
    {
        if (r.responseJSON && r.responseJSON.response) {
            r = r.responseJSON.response;
            $('compose_formToken').setValue(r.formToken);
            $('compose_requestToken').setValue(r.requestToken);
        }
    },

    attachmentChanged: function()
    {
        var fields = [],
            usedFields = 0,
            lastRow, newRow, td;

        $('upload_atc').select('input[type="file"]').each(function(i) {
            fields[fields.length] = i;
        });

        if (this.max_attachments !== null &&
            fields.length == this.max_attachments) {
            return;
        }

        fields.each(function(i) {
            if (i.value.length > 0) {
                usedFields++;
            }
        });

        if (usedFields == fields.length) {
            lastRow = $('attachment_row_' + usedFields);
            if (lastRow) {
                td = new Element('TD', { align: 'left' }).insert(new Element('STRONG').insert(IMP.text.compose_file + ' ' + (usedFields + 1) + ':')).insert('&nbsp;')

                td.insert(new Element('INPUT', { type: 'file', id: 'upload_' + (usedFields + 1), name: 'upload_' + (usedFields + 1), size: 25 }));

                newRow = new Element('TR', { id: 'attachment_row_' + (usedFields + 1) }).insert(td);

                lastRow.parentNode.insertBefore(newRow, lastRow.nextSibling);
            }
        }
    },

    clickHandler: function(e)
    {
        if (e.isRightClick()) {
            return;
        }

        var elt = e.element(), name;

        while (Object.isElement(elt)) {
            if (elt.hasClassName('button')) {
                name = elt.readAttribute('name');
                switch (name) {
                case 'btn_add_attachment':
                case 'btn_redirect':
                case 'btn_save_draft':
                case 'btn_send_message':
                    this.uniqSubmit(name.substring(4), e);
                    break;

                case 'btn_cancel_compose':
                    this.confirmCancel(e);
                    break;
                }
            }

            elt = elt.up();
        }
    },

    changeHandler: function(e)
    {
        var elt = e.element(),
            id = elt.identify();

        switch (id) {
        case 'identity':
            this.changeIdentity(elt);
            break;

        case 'stationery':
            this.uniqSubmit('change_stationery', e);
            break;

        case 'sent_mail_folder':
            $('ssm').writeAttribute('checked', 'checked');
            break;

        default:
            if (id.substring(0, 7) == 'upload_') {
                this.attachmentChanged();
            }
            break;
        }
    },

    keyDownHandler: function(e)
    {
        if (e.keyCode == 10 || e.keyCode == Event.KEY_RETURN) {
            e.stop();
        }
    },

    onDomLoad: function()
    {
        var handler = this.keyDownHandler.bindAsEventListener(this);

        /* Prevent Return from sending messages - it should bring us out of
         * autocomplete, not submit the whole form. */
        $('compose').select('INPUT').each(function(i) {
            /* Attach to everything but button and submit elements. */
            if (i.type != 'submit' && i.type != 'button') {
                i.observe('keydown', handler);
            }
        });

        IMP_Compose_Base.setCursorPosition('composeMessage', this.cursor_pos, IMP_Compose_Base.getIdentity($F('last_identity')).sig);

        if (this.redirect) {
            $('to').focus();
        } else {
            if (Prototype.Browser.IE) {
                $('subject').observe('keydown', function(e) {
                    if (e.keyCode == Event.KEY_TAB && !e.shiftKey) {
                        e.stop();
                        $('composeMessage').focus();
                    }
                });
            }

            if (IMP_Compose_Base.editor_on) {
                document.observe('SpellChecker:after', this._onAfterSpellCheck.bind(this));
                document.observe('SpellChecker:before', this._onBeforeSpellCheck.bind(this));
            }

            if ($('to') && !$F('to')) {
                $('to').focus();
            } else if (!$F('subject')) {
                if (IMP_Compose_Base.editor_on) {
                    $('subject').focus();
                } else {
                    $('composeMessage').focus();
                }
            }
        }

        document.observe('click', this.clickHandler.bindAsEventListener(this));
        document.observe('change', this.changeHandler.bindAsEventListener(this));
        document.observe('SpellChecker:noerror', this._onNoErrorSpellCheck.bind(this));

        if (this.auto_save) {
            /* Immediately execute to get MD5 hash of empty message. */
            new PeriodicalExecuter(this.uniqSubmit.bind(this, 'auto_save_draft'), this.auto_save * 60).execute();
        }

        this.resize.bind(this).delay(0.25);
    },

    _onAfterSpellCheck: function()
    {
        CKEDITOR.instances.composeMessage.setData($F('composeMessage'));
        $('composeMessage').next().show();
        this.sc_submit = null;
    },

    _onBeforeSpellCheck: function()
    {
        IMP.SpellChecker.htmlAreaParent = 'composeMessageParent';
        $('composeMessage').next().hide();
        CKEDITOR.instances.composeMessage.updateElement();
    },

    _onNoErrorSpellCheck: function()
    {
        if (this.sc_submit) {
            this.skip_spellcheck = true;
            this.uniqSubmit(this.sc_submit.a, this.sc_submit.e);
        } else if (IMP_Compose_Base.editor_on) {
            this._onAfterSpellCheck();
        } else {
            this.sc_submit = null;
        }
    },

    resize: function()
    {
        var d, e = this.redirect ? $('redirect') : $('compose');

        if (this.popup && !this.reloaded) {
            e = e.getHeight();
            if (!e) {
                return this.resize.bind(this).defer();
            }
            d = Math.min(e, screen.height - 100) - document.viewport.getHeight();
            if (d > 0) {
                window.resizeBy(0, d);
            }
        }
    },

    onBeforeUnload: function()
    {
        if (this.display_unload_warning) {
            return IMP.text.compose_discard;
        }
    }

};

/* Code to run on window load. */
document.observe('dom:loaded', ImpCompose.onDomLoad.bind(ImpCompose));

/* Warn before closing the window. */
Event.observe(window, 'beforeunload', ImpCompose.onBeforeUnload.bind(ImpCompose));

/* Catch dialog actions. */
document.observe('IMPDialog:success', function(e) {
    switch (e.memo) {
    case 'pgpPersonal':
    case 'pgpSymmetric':
    case 'smimePersonal':
        IMPDialog.noreload = true;
        ImpCompose.uniqSubmit('send_message');
        break;
    }
});
