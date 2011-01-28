/**
 * Provides the javascript for the message.php script (standard view).
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@curecanti.org>
 * @category Horde
 * @package  IMP
 */

var ImpMessage = {

    // Set in message.php: pop3delete, stripatc

    _arrowHandler: function(e)
    {
        if (e.altKey || e.shiftKey || e.ctrlKey) {
            return;
        }

        switch (e.keyCode || e.charCode) {
        case Event.KEY_LEFT:
            if ($('prev')) {
                document.location.href = $('prev').href;
            }
            break;

        case Event.KEY_RIGHT:
            if ($('next')) {
                document.location.href = $('next').href;
            }
            break;
        }
    },

    submit: function(actID)
    {
        switch (actID) {
        case 'spam_report':
            if (!window.confirm(IMP.text.spam_report)) {
                return;
            }
            break;

        case 'notspam_report':
            if (!window.confirm(IMP.text.notspam_report)) {
                return;
            }
            break;
        }

        $('actionID').setValue(actID);
        $('messages').submit();
    },

    flagMessage: function(form)
    {
        var f1 = $('flag1'), f2 = $('flag2');

        if ((form == 1 && $F(f1) != "") ||
            (form == 2 && $F(f2) != "")) {
            $('messages').down('[name=flag]').setValue((form == 1) ? $F(f1) : $F(f2));
            this.submit('flag_message');
        }
    },

    _transfer: function(actID)
    {
        var newFolder,
            target = $F('target1'),
            tmbox = $('targetMbox');

        tmbox.setValue(target);

        // Check for a mailbox actually being selected.
        if (target == "\0create") {
            newFolder = window.prompt(IMP.text.newfolder, '');
            if (newFolder != null && newFolder != '') {
                $('newMbox').setValue(1);
                tmbox.setValue(newFolder);
                this.submit(actID);
            }
        } else if (target.empty()) {
            window.alert(IMP.text.target_mbox);
        } else if (target.startsWith("\0notepad_") ||
                   target.startsWith("\0tasklist_")) {
            this.actIDconfirm = actID;
            IMPDialog.display({
                cancel_text: IMP.text.no,
                form_id: 'RB_ImpMessageConfirm',
                noinput: true,
                ok_text: IMP.text.yes,
                text: IMP.text.moveconfirm
            });
        } else {
            this.submit(actID);
        }
    },

    updateFolders: function(form)
    {
        var f = (form == 1) ? 2 : 1;
        $('target' + f).selectedIndex = $('target' + form).selectedIndex;
    },

    /* Function needed for IE compatibilty with drop-down menus. */
    _messageActionsHover: function()
    {
        var iefix = new Element('IFRAME', { scrolling: 'no', frameborder: 0 }).setStyle({ position: 'absolute' }).hide();

        // This can not appear in the new Element() call - Bug #5887
        iefix.writeAttribute('src', 'javascript:false;');

        $$('UL.msgactions LI').each(function(li) {
            var fixcopy, ul = li.down('UL'), zindex;
            if (!ul) {
                return;
            }

            fixcopy = $(iefix.clone(false));
            li.insert(fixcopy);
            fixcopy.clonePosition(ul);

            zindex = li.getStyle('zIndex');
            if (zindex === null || zindex == '') {
                li.setStyle({ zIndex: 2 });
                fixcopy.setStyle({ zIndex: 1 });
            } else {
                fixcopy.setStyle({ zIndex: parseInt(zindex, 10) - 1 });
            }

            li.observe('mouseout', function() {
                this.removeClassName('hover');
                li.down('iframe').hide();
            });
            li.observe('mouseover', function() {
                this.addClassName('hover');
                li.down('iframe').show();
            });
        });
    },

    onDomLoad: function()
    {
        // Set up left and right arrows to go to the previous/next page.
        document.observe('keydown', this._arrowHandler.bindAsEventListener(this));
        document.observe('change', this._changeHandler.bindAsEventListener(this));
        document.observe('click', this._clickHandler.bindAsEventListener(this));

        if (Prototype.Browser.IE) {
            this._messageActionsHover();
        }
    },

    _changeHandler: function(e)
    {
        var id = e.element().readAttribute('id');

        if (!id) {
            return;
        }

        if (id.startsWith('flag')) {
            this.flagMessage(id.substring(4));
        } else if (id.startsWith('target')) {
            this.updateFolders(id.substring(6));
        }
    },

    _clickHandler: function(e)
    {
        if (e.isRightClick()) {
            return;
        }

        var elt = e.element();

        while (Object.isElement(elt)) {
            if (elt.match('.msgactions A.widget')) {
                if (this.pop3delete && elt.hasClassName('deleteAction')) {
                    if (!window.confirm(this.pop3delete)) {
                        e.stop();
                        return;
                    }
                }
                if (elt.hasClassName('moveAction')) {
                    this._transfer('move_message');
                } else if (elt.hasClassName('copyAction')) {
                    this._transfer('copy_message');
                } else if (elt.hasClassName('spamAction')) {
                    this.submit('spam_report');
                } else if (elt.hasClassName('notspamAction')) {
                    this.submit('notspam_report');
                } else if (elt.hasClassName('stripAllAtc')) {
                    if (!window.confirm(this.stripatc)) {
                        e.stop();
                        return;
                    }
                }
            } else if (elt.hasClassName('unblockImageLink')) {
                IMP.unblockImages(e);
            } else if (elt.match('SPAN.toggleQuoteShow')) {
                [ elt, elt.next() ].invoke('toggle');
                elt.next(1).blindDown({ duration: 0.2, queue: { position: 'end', scope: 'showquote', limit: 2 } });
            } else if (elt.match('SPAN.toggleQuoteHide')) {
                [ elt, elt.previous() ].invoke('toggle');
                elt.next().blindUp({ duration: 0.2, queue: { position: 'end', scope: 'showquote', limit: 2 } });
            }

            elt = elt.up();
        }
    }

};

document.observe('dom:loaded', ImpMessage.onDomLoad.bind(ImpMessage));

document.observe('IMPDialog:onClick', function(e) {
    switch (e.element().identify()) {
    case 'RB_ImpMessageConfirm':
        this.submit(this.actIDconfirm);
        break;
    }
}.bindAsEventListener(ImpMessage));
