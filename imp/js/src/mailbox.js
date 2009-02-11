/**
 * Provides the javascript for the mailbox.php script (standard view).
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

var ImpMessage = {
    // The following variables are defined in mailbox.php:
    //  messagelist, sortlimit, unread
    keyId: null,
    startrange: null,

    anySelected: function()
    {
        return $H(this.messagelist).keys().detect(function(e) {
            return $('check' + e).checked;
        });
    },

    selectRow: function(id, select)
    {
        var rid = $(id.replace(/check/, 'row'));

        if (select) {
            rid.addClassName('selectedRow');
        } else {
            // Make sure to remove both regular and -over versions.
            rid.removeClassName('selectedRow').removeClassName('selectedRow-over');
        }

        $(id).checked = select;
    },

    confirmDialog: function(url, msg)
    {
        RedBox.overlay = true;
        RedBox.showHtml('<div id="RB_confirm"><p>' + msg + '</p><input type="button" class="button" onclick="window.location=\'' + url + '\';" value="' + IMP.text.yes + '" />' +
                        '<input type="button" class="button" onclick="RedBox.close();" value="' + IMP.text.no + '" /></div>');
    },

    submit: function(actID)
    {
        if (!this.anySelected()) {
            alert(IMP.text.mailbox_submit);
            return;
        }

        switch (actID) {
        case 'delete_messages':
            if (IMP.conf.pop3 && !confirm(IMP.text.mailbox_delete)) {
                return;
            }
            break;

        case 'spam_report':
            if (!confirm(IMP.text.spam_report)) {
                return;
            }
            break;

        case 'nostpam_report':
            if (!confirm(IMP.text.notspam_report)) {
                return;
            }
            break;
        }

        $('actionID').setValue(actID);
        $('messages').submit();
    },

    makeSelection: function(form)
    {
        var flag = '';

        switch (parseInt(form)) {
        case -1:
            if ($('checkAll').checked) {
                flag = '!';
            }
            flag += IMP.conf.IMP_ALL;
            break;

        case 1:
            flag = $F('filter1');
            break;

        default:
            flag = $F('filter2');
        }

        // Fixes Bug #6893
        if (flag.empty()) {
            return;
        } else if (flag.startsWith('!')) {
            this.selectFlagged(parseInt(flag.substring(1)), false);
        } else if (flag.startsWith('+')) {
            this.selectFlagged(flag.substring(0, 1), null);
        } else {
            this.selectFlagged(parseInt(flag), true);
        }

        // Reset the form.
        switch (parseInt(form)) {
        case 1:
            $('select1').reset();
            break;

        default:
            $('select2').reset();
        }
    },

    selectRange: function(e)
    {
        var id = e.element().readAttribute('id'),
            checkbox = $(id),
            count = 0,
            checked, elts;

        if (!checkbox) {
            return;
        }

        checked = checkbox.checked;

        if (this.startrange !== null && e.shiftKey) {
            elts = [ $(this.startrange).readAttribute('id'), checkbox.readAttribute('id') ];
            $H(this.messagelist).keys().detect(function(r) {
                r = 'check' + r;
                if (elts.indexOf(r) != -1) {
                    ++count;
                }
                if (count) {
                    this.selectRow(r, checked);
                    if (count == 2) {
                        return true;
                    }
                }
            }, this);
        } else {
            this.selectRow(id, checked);
        }

        this.startrange = id;
    },

    updateFolders: function(form)
    {
        var tm1 = $('targetMailbox1'),
            tm2 = $('targetMailbox2');

        if (tm2) {
            if ((form == 1 && $F(tm1) != "") ||
                (form == 2 && $F(tm2) != "")) {
                tm1.selectedIndex = tm2.selectedIndex = (form == 1)
                    ? tm1.selectedIndex
                    : tm2.selectedIndex;
            }
        }
    },

    _transfer: function(actID)
    {
        var newFolder, tmbox;

        if (this.anySelected()) {
            tmbox = $('targetMbox');
            tmbox.setValue($('targetMailbox1'));

            // Check for a mailbox actually being selected.
            if ($F(tmbox) == '*new*') {
                newFolder = prompt(IMP.text.newfolder, '');
                if (newFolder != null && newFolder != '') {
                    $('newMbox').setValue(1);
                    tmbox.setValue(newFolder);
                    this.submit(actID);
                }
            } else {
                if ($F(tmbox) == '') {
                    alert(IMP.text.target_mbox);
                } else {
                    this.submit(actID);
                }
            }
        } else {
            alert(IMP.text.mailbox_selectone);
        }
    },

    // Put everything reliant on IMAP flags in this section.
    selectFlagged: function(flag, val)
    {
        $H(this.messagelist).keys().each(function(e) {
            var check, elt = $('check' + e);
            if (flag == '+') {
                check = !elt.checked;
            } else if (flag & this.messagelist[e]) {
                check = val;
            } else {
                check = !val;
            }
            this.selectRow(elt.id, check);
        }, this);
    },

    flagMessages: function(form)
    {
        var f1 = $('flag1'), f2 = $('flag2');

        if ((form == 1 && $F(f1) != "") ||
            (form == 2 && $F(f2) != "")) {
            if (this.anySelected()) {
                // Can't use $() here.  See Bug #4736.
                document.messages.flag.value = (form == 1) ? $F(f1) : $F(f2);
                this.submit('flag_messages');
            } else {
                if (form == 1) {
                    f1.selectedIndex = 0;
                } else {
                    f2.selectedIndex = 0;
                }
                alert(IMP.text.mailbox_selectone);
            }
        }
    },

    getMessage: function(id, offset)
    {
        if (!offset) {
            return id;
        }

        var mlist = $H(this.messagelist).keys(),
            i = mlist.indexOf(id),
            j = i + offset;

        if (i != -1) {
            if (j >= 0 && j < mlist.length) {
                return mlist[j];
            }
        }

        return '';
    },

    changeHandler: function(e)
    {
        var id = e.element().readAttribute('id');

        if (id.startsWith('filter')) {
            this.makeSelection(id.substring(6));
        } else if (id.startsWith('flag')) {
            this.makeSelection(id.substring(4));
        } else if (id.startsWith('targetMailbox')) {
            this.updateFolders(id.substring(13));
        }
    },

    clickHandler: function(e)
    {
        var elt = e.element(),
            id = elt.readAttribute('id');

        if (elt.match('.msgactions A.widget')) {
            if (elt.hasClassName('moveAction')) {
                this._transfer('move_messages');
            } else if (elt.hasClassName('copyAction')) {
                this._transfer('copy_messages');
            } else if (elt.hasClassName('permdeleteAction')) {
                if (confirm(IMP.text.mailbox_delete)) {
                    this.submit('delete_messages');
                }
            } else if (elt.hasClassName('deleteAction')) {
                this.submit('delete_messages');
            } else if (elt.hasClassName('undeleteAction')) {
                this.submit('undelete_messages');
            } else if (elt.hasClassName('blacklistAction')) {
                this.submit('blacklist');
            } else if (elt.hasClassName('whitelistAction')) {
                this.submit('whitelist');
            } else if (elt.hasClassName('forwardAction')) {
                this.submit('fwd_digest');
            } else if (elt.hasClassName('spamAction')) {
                this.submit('spam_report');
            } else if (elt.hasClassName('notspamAction')) {
                this.submit('notspam_report');
            } else if (elt.hasClassName('viewAction')) {
                this.submit('view_messages');
            }

            e.stop();
            return;
        }

        if (!id) {
            return;
        }

        switch (id) {
        case 'checkheader':
        case 'checkAll':
            if (id == 'checkheader') {
                $('checkAll').checked = !$('checkAll').checked;
            }
            this.makeSelection(-1);
            return;
        }

        if (id.startsWith('check') && elt.hasClassName('checkbox')) {
            this.selectRange(e);
        } else if (!this.sortlimit &&
                  elt.match('TH') &&
                  elt.up('TABLE.messageList')) {
            document.location.href = elt.down('A').href;
        }
    },

    keyDownHandler: function(e)
    {
        var o = e.element(),
            key = e.keyCode,
            checkinc, loc, next, nextId, old, row, subjinc;

        if (e.altKey || e.ctrlKey) {
            switch (key) {
            case Event.KEY_UP:
                checkinc = -1;
                subjinc = -1;
                break;

            case Event.KEY_DOWN:
                checkinc = 1;
                subjinc = 1;
                break;

            default:
                return;
            }

            if (typeof this.messagelist == 'undefined') {
                return;
            }

            if (o.id.indexOf('check') == 0 && o.tagName == 'INPUT') {
                old = o.id.substring(5);
                this.keyId = this.getMessage(old, checkinc);
                next = $('subject' + this.keyId);
            } else if (o.id.indexOf('subject') == 0 && o.tagName == 'A') {
                old = o.id.substring(7);
                this.keyId = this.getMessage(old, subjinc);
                next = $('subject' + this.keyId);
            } else {
                this.keyId = ((checkinc + subjinc) > 0) ? $H(this.messagelist).keys().first() : $H(this.messagelist).keys().last();
                if (Event.KEY_UP || Event.KEY_DOWN) {
                    next = $('subject' + this.keyId);
                }
            }
        } else if (key == 32 &&
               o.id.indexOf('subject') == 0 &&
               o.tagName == 'A') {
            // Space key - toggle selection of the current message.
            this.startrange = 'check' + this.keyId;
            this.selectRow(this.startrange, !$(this.startrange).checked);
        } else if (!e.shiftKey) {
            if (key == Event.KEY_LEFT && $('prev')) {
                loc = $('prev').href;
            } else if (key == Event.KEY_RIGHT && $('next')) {
                loc = $('next').href;
            }

            if (loc) {
                document.location.href = loc;
            }
            return;
        } else {
            return;
        }

        if (next) {
            next.focus();
            row = $('row' + this.keyId);
            if (e.altKey) {
                nextId = next.id.replace(/subject/, 'check');
                this.selectRow(nextId, !$(nextId).checked);
            } else if (old != next.id && row.className.indexOf('-over') == -1) {
                row.className += '-over';
            }
            if (old) {
                row = $('row' + old);
                if (old != next.id) {
                    row.className = row.className.replace(/-over/, '');
                }
            }
        }

        e.stop();
    },

    submitHandler: function(e)
    {
        if (e.element().readAttribute('id').startsWith('select')) {
            e.stop();
        }
    }

};

document.observe('change', ImpMessage.changeHandler.bindAsEventListener(ImpMessage));
document.observe('click', ImpMessage.clickHandler.bindAsEventListener(ImpMessage));
document.observe('keydown', ImpMessage.keyDownHandler.bindAsEventListener(ImpMessage));
document.observe('submit', ImpMessage.submitHandler.bindAsEventListener(ImpMessage));

Event.observe(window, 'load', function() {
    if (window.fluid) {
        try {
            window.fluid.setDockBadge(ImpMessage.unread);
        } catch (e) {}
    }
});
