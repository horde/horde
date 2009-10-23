/**
 * Provides the javascript for the compose.php script (standard view).
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

var ImpCompose = {
    /* Variables defined in compose.php:
     *   cancel_url, spellcheck, cursor_pos, identities, max_attachments,
     *   popup, redirect, reloaded, rtemode, smf_check, skip_spellcheck */
    display_unload_warning: true,
    textarea_ready: true,

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

    /**
     * Sets the cursor to the given position.
     */
    setCursorPosition: function(input, position)
    {
        if (input.setSelectionRange) {
            /* This works in Mozilla */
            Field.focus(input);
            input.setSelectionRange(position, position);
            if (position) {
                (function() { input.scrollTop = input.scrollHeight - input.offsetHeight; }).defer();
            }
        } else if (input.createTextRange) {
            /* This works in IE */
            var range = input.createTextRange();
            range.collapse(true);
            range.moveStart('character', position);
            range.moveEnd('character', 0);
            Field.select(range);
            range.scrollIntoView(true);
        }
    },

    changeIdentity: function(elt)
    {
        var id = $F(elt),
            last = this.identities[$F('last_identity')],
            next = this.identities[id],
            i = 0,
            bcc = $('bcc'),
            save = $('ssm'),
            smf = $('sent_mail_folder'),
            ed, lastSignature, msg, nextSignature, pos, re;

        // If the rich text editor is on, we'll use a regexp to find the
        // signature comment and replace its contents.
        if (this.rtemode) {
            ed = FCKeditorAPI.GetInstance('composeMessage');

            msg = ed.GetHTML.replace(/\r\n/g, '\n');

            lastSignature = '<p><!--begin_signature--><!--end_signature--></p>';
            nextSignature = '<p><!--begin_signature-->' + next[0].replace(/^ ?<br \/>\n/, '').replace(/ +/g, ' ') + '<!--end_signature--></p>';

            // Dot-all functionality achieved with [\s\S], see:
            // http://simonwillison.net/2004/Sep/20/newlines/
            msg = msg.replace(/<p class="imp-signature">\s*<!--begin_signature-->[\s\S]*?<!--end_signature-->\s*<\/p>/, lastSignature);
        } else {
            msg = $F('composeMessage').replace(/\r\n/g, '\n');

            lastSignature = last[0].replace(/^\n/, '');
            nextSignature = next[0].replace(/^\n/, '');
        }

        pos = (last[1]) ? msg.indexOf(lastSignature) : msg.lastIndexOf(lastSignature);
        if (pos != -1) {
            if (next[1] == last[1]) {
                msg = msg.substring(0, pos) + nextSignature + msg.substring(pos + lastSignature.length, msg.length);
            } else if (next[1]) {
                msg = nextSignature + msg.substring(0, pos) + msg.substring(pos + lastSignature.length, msg.length);
            } else {
                msg = msg.substring(0, pos) + msg.substring(pos + lastSignature.length, msg.length) + nextSignature;
            }

            msg = msg.replace(/\r\n/g, '\n').replace(/\n/g, '\r\n');

            $('last_identity').setValue(id);
            window.status = IMP.text.compose_sigreplace;
        } else {
            window.status = IMP.text.compose_signotreplace;
        }

        if (this.rtemode) {
            ed.SetHTML(msg);
        } else {
            $('composeMessage').setValue(msg);
        }


        if (this.smf_check) {
            $A(smf.options).detect(function(f) {
                if (f.value == next[2]) {
                    smf.selectedIndex = i;
                    return true;
                }
                ++i;
            });
        } else {
            if (smf.firstChild) {
                smf.replaceChild(document.createTextNode(next[2]), smf.firstChild);
            } else {
                smf.appendChild(document.createTextNode(next[2]));
            }
        }

        if (save) {
            save.checked = next[3];
        }
        if (bcc) {
            bccval = bcc.value;

            if (last[4]) {
                re = new RegExp(last[4] + ",? ?", 'gi');
                bccval = bccval.replace(re, "");
                if (bccval) {
                    bccval = bccval.replace(/, ?$/, "");
                }
            }

            if (next[4]) {
                if (bccval) {
                    bccval += ', ';
                }
                bccval += next[4];
            }

            bcc.setValue(bccval);
        }
    },

    uniqSubmit: function(actionID, e)
    {
        var form;

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
            if (($F('subject') == '') &&
                !window.confirm(IMP.text.compose_nosubject)) {
                return;
            }

            if (!this.skip_spellcheck &&
                this.spellcheck &&
                IMP.SpellCheckerObject &&
                !IMP.SpellCheckerObject.isActive()) {
                IMP.SpellCheckerObject.spellCheck(this.onNoSpellError.bind(this, actionID, e));
                return;
            }

            this.skip_spellcheck = false;

            if (IMP.SpellCheckerObject) {
                IMP.SpellCheckerObject.resume();
            }

            // fall through

        case 'add_attachment':
        case 'save_draft':
            form = $('compose');
            $('actionID').setValue(actionID);
            break;

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
        this._uniqSubmit(form);
    },

    _uniqSubmit: function(form)
    {
        if (this.textarea_ready) {
            form.submit();
        } else {
            this._uniqSubmit.bind(this, form).defer();
        }
    },

    onNoSpellError: function(actionID, e)
    {
        this.skip_spellcheck = true;
        this.uniqSubmit(actionID, e);
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

    initializeSpellChecker: function()
    {
        if (typeof IMP.SpellCheckerObject != 'object') {
            // If we fired before the onload that initializes the spellcheck,
            // wait.
            this.initializeSpellChecker.bind(this).defer();
            return;
        }

        IMP.SpellCheckerObject.onBeforeSpellCheck = this._beforeSpellCheck.bind(this);
        IMP.SpellCheckerObject.onAfterSpellCheck = this._afterSpellCheck.bind(this);
    },

    _beforeSpellCheck: function()
    {
        IMP.SpellCheckerObject.htmlAreaParent = 'composeMessageParent';
        IMP.SpellCheckerObject.htmlArea = $('composeMessage').adjacent('iframe[id*=message]').first();
        $('composeMessage').setValue(FCKeditorAPI.GetInstance('composeMessage').GetHTML());
        this.textarea_ready = false;
    },

    _afterSpellCheck: function()
    {
        IMP.SpellCheckerObject.htmlArea = IMP.SpellCheckerObject.htmlAreaParent = null;
        var ed = FCKeditorAPI.GetInstance('composeMessage');
        ed.SetHTML($('composeMessage').value);
        ed.Events.AttachEvent('OnAfterSetHTML', this._afterSetHTML.bind(this));
    },

    _afterSetHTML: function()
    {
        this.textarea_ready = true;
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

    onDomLoad: function()
    {
        /* Prevent Return from sending messages - it should bring us out of
         * autocomplete, not submit the whole form. */
        $$('INPUT').each(function(i) {
            /* Attach to everything but button and submit elements. */
            if (i.type != 'submit' && i.type != 'button') {
                i.observe('keydown', function(e) {
                    if (e.keyCode == 10 || e.keyCode == Event.KEY_RETURN) {
                        e.stop();
                        return false;
                    }
                });
            }
        });

        if (this.cursor_pos !== null && $('composeMessage')) {
            this.setCursorPosition($('composeMessage'), this.cursor_pos);
        }

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

            if (this.rtemode) {
                this.initializeSpellChecker();
            }

            if ($('to') && !$F('to')) {
                $('to').focus();
            } else if (!$F('subject')) {
                if (this.rtemode) {
                    $('subject').focus();
                } else {
                    $('composeMessage').focus();
                }
            }
        }

        document.observe('click', this.clickHandler.bindAsEventListener(this));
        document.observe('change', this.changeHandler.bindAsEventListener(this));

        this.resize();
    },

    resize: function()
    {
        var d, e = this.redirect ? $('redirect') : $('compose');

        if (this.popup && !this.reloaded) {
            e = e.getHeight();
            if (!e) {
                return this.resize.bind(this).defer();
            }
            d = Math.min(e.getHeight(), screen.height - 100) - document.viewport.getHeight();
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
