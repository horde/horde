/**
 * Provides the javascript for the basic view message page.
 *
 * Copyright 2010-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  IMP
 */

var ImpMessage = {

    // Set in PHP code: pop3delete, text

    arrowHandler: function(e)
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
            if (!window.confirm(this.text.spam_report)) {
                return;
            }
            break;

        case 'innocent_report':
            if (!window.confirm(this.text.innocent_report)) {
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

        if ((form == 1 && !$F(f1).empty()) ||
            (form == 2 && !$F(f2).empty())) {
            $('messages').down('[name=flag]').setValue((form == 1) ? $F(f1) : $F(f2));
            this.submit('flag_message');
        }
    },

    _transfer: function(actID)
    {
        var newMbox,
            elt = $('target1'),
            target = $F(elt),
            tmbox = $('targetMbox');

        tmbox.setValue(target);

        // Check for a mailbox actually being selected.
        if ($(elt[elt.selectedIndex]).hasClassName('flistCreate')) {
            newMbox = window.prompt(this.text.newmbox, '');
            if (newMbox !== null && !newMbox.empty()) {
                $('newMbox').setValue(1);
                tmbox.setValue(newMbox);
                this.submit(actID);
            }
        } else if (target.empty()) {
            window.alert(this.text.target_mbox);
        } else if (target.startsWith("notepad\0") ||
                   target.startsWith("tasklist\0")) {
            this.actIDconfirm = actID;
            HordeDialog.display({
                form_id: 'RB_ImpMessageConfirm',
                noinput: true,
                text: this.text.moveconfirm
            });
        } else {
            this.submit(actID);
        }
    },

    updateMailboxes: function(form)
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
            if (zindex === null || zindex.empty()) {
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
        HordeCore.initHandler('click');

        if (Prototype.Browser.IE) {
            $('flag1', 'target1', 'flag2', 'target2').compact().invoke('observe', 'change', this._changeHandler.bindAsEventListener(this));
            this._messageActionsHover();
        } else {
            document.observe('change', this._changeHandler.bindAsEventListener(this));
        }
    },

    onDialogClick: function(e)
    {
        switch (e.element().identify()) {
        case 'RB_ImpMessageConfirm':
            this.submit(this.actIDconfirm);
            break;
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
            this.updateMailboxes(id.substring(6));
        }
    },

    clickHandler: function(e)
    {
        $w(e.element().className).each(function(c) {
            switch (c) {
            case 'deleteAction':
                if (this.pop3delete && !window.confirm(this.pop3delete)) {
                    e.memo.stop();
                }
                break;

            case 'moveAction':
                this._transfer('move_message');
                break;

            case 'copyAction':
                this._transfer('copy_message');
                break;

            case 'spamAction':
                this.submit('spam_report');
                break;

            case 'innocentAction':
                this.submit('innocent_report');
                break;

            case 'stripAllAtc':
                if (!window.confirm(this.text.stripallwarn)) {
                    e.memo.stop();
                }
                break;

             case 'unblockImageLink':
                IMP_JS.unblockImages(e.memo);
                break;

            case 'stripAtc':
                if (!window.confirm(this.text.stripwarn)) {
                    e.memo.stop();
                }
                break;
            }
        }, this);
    }

};

document.observe('dom:loaded', ImpMessage.onDomLoad.bind(ImpMessage));
document.observe('keydown', ImpMessage.arrowHandler.bindAsEventListener(ImpMessage));
document.observe('HordeCore:click', ImpMessage.clickHandler.bindAsEventListener(ImpMessage));
document.observe('HordeDialog:onClick', ImpMessage.onDialogClick.bind(ImpMessage));
